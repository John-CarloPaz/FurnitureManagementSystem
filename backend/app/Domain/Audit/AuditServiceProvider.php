<?php

namespace App\Domain\Audit;

use App\Domain\Access\Models\Invitation;
use App\Domain\Delivery\Models\DeliveryAssignment;
use App\Domain\Delivery\Models\ProofOfDelivery;
use App\Domain\Manufacturing\Models\ManufacturingStage;
use App\Domain\Manufacturing\Models\QualityInspection;
use App\Domain\Manufacturing\Models\WorkOrder;
use App\Domain\ModelGeneration\Models\ModelGeneration;
use App\Domain\Models3D\Models\Model3DVersion;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Models\OrderItem;
use App\Domain\Products\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AuditServiceProvider extends ServiceProvider
{
    /**
     * Business entities whose every create/update/delete is audited. Registering an
     * observer here keeps the model classes untouched.
     *
     * @var list<class-string<Model>>
     */
    private const AUDITED = [
        Product::class,
        Order::class,
        OrderItem::class,
        User::class,
        ManufacturingStage::class,
        WorkOrder::class,
        QualityInspection::class,
        DeliveryAssignment::class,
        ProofOfDelivery::class,
        ModelGeneration::class,
        Model3DVersion::class,
        Invitation::class,
    ];

    public function boot(): void
    {
        foreach (self::AUDITED as $model) {
            $model::observe(AuditObserver::class);
        }
    }
}
