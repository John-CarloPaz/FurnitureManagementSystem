<?php

use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DeliveryController;
use App\Http\Controllers\Api\DssController;
use App\Http\Controllers\Api\ManufacturingController;
use App\Http\Controllers\Api\ModelVersionController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WorkOrderController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1  — conventions in docs/design/API.md
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->group(function () {
    Route::get('/health', function () {
        $db = 'ok';
        try {
            DB::connection()->getPdo();
        } catch (Throwable $e) {
            $db = 'down';
        }

        return response()->json([
            'data' => ['status' => $db === 'ok' ? 'healthy' : 'degraded', 'service' => config('app.name'), 'database' => $db],
        ], $db === 'ok' ? 200 : 503);
    });

    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        // ---- Notifications (own, in-app) ----
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::post('/notifications/read-all', [NotificationController::class, 'readAll']); // before {id}
        Route::post('/notifications/{id}/read', [NotificationController::class, 'read']);

        // ---- Users ----
        Route::get('/users', [UserController::class, 'index'])->middleware('permission:users.view');
        Route::post('/users', [UserController::class, 'store'])->middleware('permission:users.manage');

        // ---- Catalog (Products) — authorized via ProductPolicy ----
        Route::get('/products/options', [ProductController::class, 'options']); // before {product}
        Route::get('/products', [ProductController::class, 'index']);
        Route::post('/products', [ProductController::class, 'store']);
        Route::get('/products/{product}', [ProductController::class, 'show']);
        Route::patch('/products/{product}', [ProductController::class, 'update']);
        Route::delete('/products/{product}', [ProductController::class, 'destroy']);
        Route::post('/products/{product}/publish', [ProductController::class, 'publish']);
        Route::post('/products/{product}/unpublish', [ProductController::class, 'unpublish']);
        Route::post('/products/{product}/archive', [ProductController::class, 'archive']);

        // 3D model versions (per product)
        Route::get('/products/{product}/model-versions', [ModelVersionController::class, 'index']);
        Route::post('/products/{product}/model-versions', [ModelVersionController::class, 'store']);
        Route::get('/model-versions/{version}/download', [ModelVersionController::class, 'download']);

        // ---- Orders (fulfillment) — authorized via OrderPolicy ----
        Route::get('/orders', [OrderController::class, 'index']);
        Route::post('/orders', [OrderController::class, 'store']);
        Route::get('/orders/{order}', [OrderController::class, 'show']);
        Route::post('/orders/{order}/transition', [OrderController::class, 'transition']);
        Route::get('/orders/{order}/transitions', [OrderController::class, 'transitions']);
        Route::post('/orders/{order}/payments', [OrderController::class, 'payments']);

        // ---- Manufacturing (per-item production stages) ----
        Route::get('/shop-floor', [ManufacturingController::class, 'shopFloor'])
            ->middleware('permission:manufacturing.view');
        Route::get('/orders/{order}/production', [ManufacturingController::class, 'production'])
            ->middleware('permission:manufacturing.view');
        // Stage authz is per stage type (production = operatives, QC = QA/PM) in the controller.
        Route::post('/order-items/{orderItem}/stages/{stage}/start', [ManufacturingController::class, 'startStage']);
        Route::post('/order-items/{orderItem}/stages/{stage}/complete', [ManufacturingController::class, 'completeStage']);
        Route::post('/order-items/{orderItem}/stages/{stage}/flag', [ManufacturingController::class, 'flagStage']);

        // ---- Work orders (assign operatives to items) ----
        Route::get('/work-orders', [WorkOrderController::class, 'index'])->middleware('permission:manufacturing.view');
        Route::post('/work-orders', [WorkOrderController::class, 'store'])->middleware('permission:workorders.assign');
        Route::patch('/work-orders/{workOrder}', [WorkOrderController::class, 'update'])->middleware('permission:workorders.assign');

        // ---- Delivery (assignment → dispatch → proof) — authorized via DeliveryPolicy ----
        Route::get('/deliveries', [DeliveryController::class, 'index']);
        Route::get('/deliveries/unassigned', [DeliveryController::class, 'unassigned']); // before {assignment}
        Route::get('/deliveries/drivers', [DeliveryController::class, 'drivers']);
        Route::get('/deliveries/{assignment}', [DeliveryController::class, 'show']);
        Route::post('/orders/{order}/delivery', [DeliveryController::class, 'store']);
        Route::post('/deliveries/{assignment}/dispatch', [DeliveryController::class, 'dispatchDelivery']);
        Route::post('/deliveries/{assignment}/location', [DeliveryController::class, 'location']);
        Route::post('/deliveries/{assignment}/proof', [DeliveryController::class, 'proof']);

        // ---- Analytics / KPIs ----
        Route::get('/kpi', [AnalyticsController::class, 'dashboard'])->middleware('permission:kpi.view');
        Route::get('/kpi/shop-floor', [AnalyticsController::class, 'shopFloor'])->middleware('permission:kpi.view.shopfloor');
        Route::get('/kpi/delivery', [AnalyticsController::class, 'delivery'])->middleware('permission:kpi.view.delivery');

        // ---- Model-Driven DSS ----
        Route::post('/dss/schedule', [DssController::class, 'schedule'])->middleware('permission:manufacturing.schedule');
        Route::get('/dss/bottlenecks', [DssController::class, 'bottlenecks'])->middleware('permission:kpi.view.shopfloor');
        Route::post('/dss/route-optimize', [DssController::class, 'routeOptimize'])->middleware('permission:delivery.assign');
    });

    // Signed, session-less proof-photo stream.
    Route::get('/delivery-proofs/{proof}/file', [DeliveryController::class, 'file'])
        ->name('delivery-proofs.file')
        ->middleware('signed:relative');

    // Signed, session-less 3D file stream (temporary URL from /download).
    Route::get('/model-versions/{version}/file', [ModelVersionController::class, 'file'])
        ->name('model-versions.file')
        ->middleware('signed:relative');
});
