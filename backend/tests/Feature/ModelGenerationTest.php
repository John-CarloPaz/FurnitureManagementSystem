<?php

namespace Tests\Feature;

use App\Domain\ModelGeneration\Contracts\ModelGenerator;
use App\Domain\ModelGeneration\Jobs\ProcessModelGeneration;
use App\Domain\Models3D\Actions\UploadModelVersionAction;
use App\Domain\Products\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ModelGenerationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('local');
    }

    private function admin(): User
    {
        $u = User::factory()->create();
        $u->assignRole('admin');

        return $u;
    }

    public function test_uploading_a_photo_on_create_queues_a_generation(): void
    {
        config(['model_generation.meshy.key' => 'test-key']);
        Queue::fake();
        Sanctum::actingAs($this->admin());

        $res = $this->post('/api/v1/products', [
            'name' => 'Rattan Chair',
            'base_price' => 3000,
            'image' => UploadedFile::fake()->image('chair.jpg'),
        ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.model_generation.status', 'pending');

        $productId = $res->json('data.id');
        $this->assertDatabaseHas('model_generations', ['product_id' => $productId, 'status' => 'pending', 'provider' => 'meshy']);
        $this->assertDatabaseHas('product_images', ['product_id' => $productId, 'is_primary' => true]);
        Queue::assertPushed(ProcessModelGeneration::class);
    }

    public function test_photo_without_a_configured_provider_stores_image_but_skips_generation(): void
    {
        config(['model_generation.meshy.key' => null]);
        Queue::fake();
        Sanctum::actingAs($this->admin());

        $res = $this->post('/api/v1/products', [
            'name' => 'Plain Bench',
            'base_price' => 1000,
            'image' => UploadedFile::fake()->image('bench.jpg'),
        ], ['Accept' => 'application/json'])->assertCreated();

        $this->assertDatabaseHas('product_images', ['product_id' => $res->json('data.id')]);
        $this->assertDatabaseCount('model_generations', 0);
        Queue::assertNotPushed(ProcessModelGeneration::class);
    }

    public function test_options_reports_whether_generation_is_enabled(): void
    {
        Sanctum::actingAs($this->admin());

        config(['model_generation.meshy.key' => null]);
        $this->getJson('/api/v1/products/options')->assertJsonPath('data.model_generation_enabled', false);

        config(['model_generation.meshy.key' => 'a-key']);
        $this->getJson('/api/v1/products/options')->assertJsonPath('data.model_generation_enabled', true);
    }

    public function test_job_submits_then_finalizes_into_a_current_model_version(): void
    {
        config(['model_generation.meshy.key' => 'test-key']);
        Queue::fake(); // stops the poll re-dispatch from recursing; we drive steps manually
        Http::fake([
            '*image-to-3d' => Http::response(['result' => 'task-123']),
            '*image-to-3d/*' => Http::response([
                'status' => 'SUCCEEDED',
                'progress' => 100,
                'model_urls' => ['glb' => 'https://assets.meshy.ai/model.glb'],
            ]),
            '*.glb' => Http::response('GLB_BYTES'),
        ]);

        $admin = $this->admin();
        $product = Product::create(['name' => 'Stool', 'slug' => 'stool', 'base_price' => 1200]);
        $imagePath = UploadedFile::fake()->image('stool.jpg')->store("product-images/{$product->id}");
        $gen = $product->modelGenerations()->create([
            'image_path' => $imagePath, 'provider' => 'meshy', 'status' => 'pending', 'requested_by' => $admin->id,
        ]);

        $generator = app(ModelGenerator::class);
        $versions = app(UploadModelVersionAction::class);

        // Step 1: submit
        (new ProcessModelGeneration($gen->id))->handle($generator, $versions);
        $gen->refresh();
        $this->assertSame('processing', $gen->status->value);
        $this->assertSame('task-123', $gen->provider_task_id);

        // Step 2: poll → download → record version
        (new ProcessModelGeneration($gen->id))->handle($generator, $versions);
        $gen->refresh();
        $this->assertSame('succeeded', $gen->status->value);
        $this->assertNotNull($gen->model_version_id);
        $this->assertDatabaseHas('model_3d_versions', ['id' => $gen->model_version_id, 'format' => 'glb']);

        $product->load('model');
        $this->assertSame($gen->model_version_id, $product->model->current_version_id);
        Storage::disk('local')->assertExists("models/products/{$product->id}/generated-{$gen->id}.glb");
    }

    public function test_job_fails_when_the_stored_model_file_is_empty(): void
    {
        config(['model_generation.meshy.key' => 'test-key']);
        Queue::fake();
        Http::fake([
            '*image-to-3d' => Http::response(['result' => 'task-e']),
            '*image-to-3d/*' => Http::response(['status' => 'SUCCEEDED', 'progress' => 100, 'model_urls' => ['glb' => 'https://assets.meshy.ai/empty.glb']]),
            '*.glb' => Http::response('', 200), // empty download → no phantom version
        ]);

        $product = Product::create(['name' => 'Vase', 'slug' => 'vase', 'base_price' => 400]);
        $imagePath = UploadedFile::fake()->image('vase.jpg')->store("product-images/{$product->id}");
        $gen = $product->modelGenerations()->create(['image_path' => $imagePath, 'provider' => 'meshy', 'status' => 'pending']);

        $generator = app(ModelGenerator::class);
        $versions = app(UploadModelVersionAction::class);
        (new ProcessModelGeneration($gen->id))->handle($generator, $versions); // submit
        (new ProcessModelGeneration($gen->id))->handle($generator, $versions); // poll → empty → fail

        $gen->refresh();
        $this->assertSame('failed', $gen->status->value);
        $this->assertNull($gen->model_version_id);
        $this->assertDatabaseCount('model_3d_versions', 0); // no phantom version
    }

    public function test_job_marks_failed_when_the_provider_fails(): void
    {
        config(['model_generation.meshy.key' => 'test-key']);
        Queue::fake();
        Http::fake([
            '*image-to-3d' => Http::response(['result' => 'task-x']),
            '*image-to-3d/*' => Http::response(['status' => 'FAILED', 'task_error' => ['message' => 'Unusable image']]),
        ]);

        $product = Product::create(['name' => 'Lamp', 'slug' => 'lamp', 'base_price' => 500]);
        $imagePath = UploadedFile::fake()->image('lamp.jpg')->store("product-images/{$product->id}");
        $gen = $product->modelGenerations()->create([
            'image_path' => $imagePath, 'provider' => 'meshy', 'status' => 'pending',
        ]);

        $generator = app(ModelGenerator::class);
        $versions = app(UploadModelVersionAction::class);

        (new ProcessModelGeneration($gen->id))->handle($generator, $versions); // submit
        (new ProcessModelGeneration($gen->id))->handle($generator, $versions); // poll → failed

        $gen->refresh();
        $this->assertSame('failed', $gen->status->value);
        $this->assertSame('Unusable image', $gen->error);
    }
}
