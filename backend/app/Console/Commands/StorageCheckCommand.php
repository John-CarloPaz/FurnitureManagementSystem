<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Diagnoses the default filesystem disk end to end (write → read → delete). Run it on
 * the server to confirm S3 is wired before blaming a feature — a failed write here is
 * why an uploaded/generated asset can't be served back (e.g. the 3D viewer hangs).
 */
class StorageCheckCommand extends Command
{
    protected $signature = 'storage:check';

    protected $description = 'Verify the default filesystem disk can write + read (diagnoses S3 config)';

    public function handle(): int
    {
        $disk = (string) config('filesystems.default');
        $this->line("Default filesystem disk: <info>{$disk}</info>");

        $path = 'diagnostics/storage-check-'.now()->timestamp.'.txt';

        try {
            $wrote = Storage::put($path, 'ok');
            $exists = Storage::exists($path);
            $read = $exists ? Storage::get($path) : null;
            Storage::delete($path);

            if ($wrote && $exists && $read === 'ok') {
                $this->info("✔ Wrote, read, and deleted {$path} — the '{$disk}' disk is working.");

                return self::SUCCESS;
            }

            $this->error('✘ Storage round-trip failed (put='.var_export($wrote, true).', exists='.var_export($exists, true).').');
            $this->warn("Check the '{$disk}' disk credentials (for s3: AWS_ACCESS_KEY_ID / AWS_SECRET_ACCESS_KEY / AWS_DEFAULT_REGION / AWS_BUCKET).");

            return self::FAILURE;
        } catch (Throwable $e) {
            $this->error("✘ Storage error on the '{$disk}' disk: ".$e->getMessage());

            return self::FAILURE;
        }
    }
}
