<?php

namespace App\Http\Controllers\Api;

use App\Domain\Audit\Models\AuditLog;
use App\Http\Controllers\Controller;
use App\Http\Resources\AuditLogResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AuditLogController extends Controller
{
    /** Newest-first audit trail, optionally filtered by entity / event / actor (audit.view). */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = AuditLog::query()->latest('created_at');

        if ($entity = $request->query('entity')) {
            $query->where('auditable_type', $entity);
        }
        if ($event = $request->query('event')) {
            $query->where('event', $event);
        }
        if ($userId = $request->query('user_id')) {
            $query->where('user_id', $userId);
        }

        return AuditLogResource::collection($query->paginate(30));
    }
}
