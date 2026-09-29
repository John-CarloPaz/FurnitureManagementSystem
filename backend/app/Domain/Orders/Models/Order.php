<?php

namespace App\Domain\Orders\Models;

use App\Domain\Delivery\Models\DeliveryAssignment;
use App\Domain\Orders\Enums\OrderState;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $order_number
 * @property int $customer_id
 * @property OrderState $status
 * @property string $subtotal
 * @property string $delivery_fee
 * @property string $tax_amount
 * @property string $discount_amount
 * @property int|null $voucher_id
 * @property string|null $voucher_code
 * @property string $total
 * @property string $downpayment
 * @property string $amount_paid
 * @property string $payment_status
 * @property string|null $payment_method
 * @property string|null $delivery_address
 * @property string|null $notes
 * @property Carbon|null $placed_at
 * @property Carbon|null $confirmed_at
 * @property Carbon|null $delivered_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Order extends Model
{
    protected $fillable = [
        'order_number', 'customer_id', 'status',
        'subtotal', 'delivery_fee', 'tax_amount', 'discount_amount', 'voucher_id', 'voucher_code',
        'total', 'downpayment', 'amount_paid', 'payment_status', 'payment_method',
        'delivery_address', 'notes', 'placed_at', 'confirmed_at', 'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderState::class,
            'delivery_address' => 'encrypted',
            'placed_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'delivered_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'downpayment' => 'decimal:2',
            'amount_paid' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /** @return BelongsTo<Voucher, $this> */
    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    /** @return HasMany<OrderItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /** @return HasMany<OrderStateTransition, $this> */
    public function transitions(): HasMany
    {
        return $this->hasMany(OrderStateTransition::class)->latest();
    }

    /** @return HasMany<OrderPayment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(OrderPayment::class)->latest();
    }

    /** @return HasOne<DeliveryAssignment, $this> */
    public function deliveryAssignment(): HasOne
    {
        return $this->hasOne(DeliveryAssignment::class);
    }

    /** @return HasMany<ReturnRequest, $this> */
    public function returnRequests(): HasMany
    {
        return $this->hasMany(ReturnRequest::class)->latest();
    }

    /** @return HasMany<IssueReport, $this> */
    public function issueReports(): HasMany
    {
        return $this->hasMany(IssueReport::class)->latest();
    }
}
