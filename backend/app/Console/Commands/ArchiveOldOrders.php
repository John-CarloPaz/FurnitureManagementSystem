<?php

namespace App\Console\Commands;

use App\Domain\Orders\Enums\OrderState;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Moves terminal orders (CLOSED/CANCELLED) older than N months into orders_archive
 * and removes them from the live table (caps §1.4). Scheduled monthly in routes/console.php.
 *
 * Note: order_state_transitions cascade-delete with the order. If long-term audit
 * retention is required, extend this to copy transitions into an archive table too.
 */
class ArchiveOldOrders extends Command
{
    protected $signature = 'orders:archive {--months=12 : Age threshold in months}';

    protected $description = 'Archive terminal orders older than the given number of months';

    public function handle(): int
    {
        $cutoff = Carbon::now()->subMonths((int) $this->option('months'));
        $terminal = [OrderState::COMPLETED->value, OrderState::CANCELLED->value];

        $ids = DB::table('orders')
            ->whereIn('status', $terminal)
            ->where('created_at', '<', $cutoff)
            ->pluck('id');

        if ($ids->isEmpty()) {
            $this->info('No orders to archive.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($ids) {
            $columns = ['id', 'order_number', 'customer_id', 'status', 'total', 'created_at', 'updated_at'];

            DB::table('orders_archive')->insertUsing(
                $columns,
                DB::table('orders')->whereIn('id', $ids)->select($columns),
            );

            DB::table('orders')->whereIn('id', $ids)->delete();
        });

        $this->info("Archived {$ids->count()} order(s) older than {$cutoff->toDateString()}.");

        return self::SUCCESS;
    }
}
