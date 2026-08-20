<?php

namespace App\Domain\Orders\Actions;

use App\Domain\Orders\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RecordPaymentAction
{
    public function execute(Order $order, float $amount, ?string $method, User $recorder, ?string $note = null): Order
    {
        return DB::transaction(function () use ($order, $amount, $method, $recorder, $note) {
            $order->payments()->create([
                'amount' => $amount,
                'method' => $method,
                'recorded_by' => $recorder->id,
                'note' => $note,
            ]);

            $paid = (float) $order->amount_paid + $amount;
            $total = (float) $order->total;
            $status = $paid <= 0 ? 'UNPAID' : ($paid >= $total ? 'PAID' : 'PARTIAL');

            $order->update(['amount_paid' => $paid, 'payment_status' => $status]);

            return $order->refresh();
        });
    }
}
