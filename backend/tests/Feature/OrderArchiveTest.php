<?php

namespace Tests\Feature;

use App\Domain\Orders\Enums\OrderState;
use App\Domain\Orders\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OrderArchiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_archives_terminal_orders_older_than_a_year(): void
    {
        $customer = User::factory()->create();

        $old = Order::create([
            'order_number' => 'ORD-OLD',
            'customer_id' => $customer->id,
            'status' => OrderState::COMPLETED,
            'total' => 100,
        ]);
        DB::table('orders')->where('id', $old->id)->update(['created_at' => now()->subMonths(13)]);

        $recent = Order::create([
            'order_number' => 'ORD-NEW',
            'customer_id' => $customer->id,
            'status' => OrderState::COMPLETED,
            'total' => 200,
        ]);

        $this->artisan('orders:archive')->assertSuccessful();

        $this->assertDatabaseMissing('orders', ['id' => $old->id]);
        $this->assertDatabaseHas('orders_archive', ['id' => $old->id, 'order_number' => 'ORD-OLD']);
        $this->assertDatabaseHas('orders', ['id' => $recent->id]);
    }
}
