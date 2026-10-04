<?php

namespace App\Http\Controllers\Api;

use App\Domain\Notifications\Actions\SendIssueStatusEmail;
use App\Domain\Orders\Enums\OrderState;
use App\Domain\Orders\Models\IssueReport;
use App\Domain\Orders\Models\Order;
use App\Http\Controllers\Controller;
use App\Http\Resources\IssueReportResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class IssueController extends Controller
{
    /** Staff (issues.viewAny) see everything; customers see only their own. */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = IssueReport::query()->with(['order', 'reporter'])->latest();

        if (! $request->user()?->can('issues.viewAny')) {
            $query->where('user_id', $request->user()?->id);
        }

        return IssueReportResource::collection($query->get());
    }

    /** Customer reports a problem with their own delivered order. */
    public function store(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();
        abort_unless($order->customer_id === $user?->id, 403);

        if (! in_array($order->status, [OrderState::DELIVERED, OrderState::COMPLETED], true)) {
            throw ValidationException::withMessages(['order' => ['You can only report an issue after the order is delivered.']]);
        }

        $validated = $request->validate([
            'category' => ['required', Rule::in(IssueReport::CATEGORIES)],
            'description' => ['required', 'string', 'max:1000'],
        ]);

        $issue = $order->issueReports()->create([
            'user_id' => $user->id,
            'category' => $validated['category'],
            'description' => $validated['description'],
            'status' => 'OPEN',
        ]);

        return (new IssueReportResource($issue->load(['order', 'reporter'])))->response()->setStatusCode(201);
    }

    /** Staff (issues.manage) move an issue along and record how it was resolved. */
    public function update(Request $request, IssueReport $issueReport, SendIssueStatusEmail $mailer): IssueReportResource
    {
        abort_unless($request->user()?->can('issues.manage'), 403);

        $validated = $request->validate([
            'status' => ['required', Rule::in(IssueReport::STATUSES)],
            'resolution_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $previousStatus = $issueReport->status;

        $issueReport->update([
            'status' => $validated['status'],
            'resolution_note' => $validated['resolution_note'] ?? $issueReport->resolution_note,
            'handled_by' => $request->user()->id,
        ]);

        $issueReport->load(['order', 'reporter']);

        if ($validated['status'] !== $previousStatus) {
            $mailer->execute($issueReport);
        }

        return new IssueReportResource($issueReport);
    }
}
