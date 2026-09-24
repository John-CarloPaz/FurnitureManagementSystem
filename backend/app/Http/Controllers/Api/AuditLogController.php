<?php

namespace App\Http\Controllers\Api;

use App\Domain\Audit\Models\AuditLog;
use App\Http\Controllers\Controller;
use App\Http\Resources\AuditLogResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    /** Newest-first audit trail, optionally filtered by entity / event / actor (audit.view). */
    public function index(Request $request): AnonymousResourceCollection
    {
        return AuditLogResource::collection($this->filtered($request)->paginate(30));
    }

    /** Same trail as a CSV download (audit.view). */
    public function export(Request $request): StreamedResponse
    {
        $query = $this->filtered($request);

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['When', 'Who', 'Event', 'Method', 'Path', 'Entity', 'Entity ID', 'Changes', 'IP']);
            foreach ($query->reorder('id', 'desc')->lazy() as $log) {
                fputcsv($out, [
                    $log->created_at?->toDateTimeString(),
                    $log->user_name,
                    $log->event,
                    $log->method,
                    $log->path,
                    $log->auditable_type,
                    $log->auditable_id,
                    $log->changes !== null ? json_encode($log->changes) : '',
                    $log->ip_address,
                ]);
            }
            fclose($out);
        }, 'audit-log-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * @return Builder<AuditLog>
     */
    private function filtered(Request $request): Builder
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

        return $query;
    }
}
