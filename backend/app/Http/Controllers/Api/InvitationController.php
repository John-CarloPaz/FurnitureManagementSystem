<?php

namespace App\Http\Controllers\Api;

use App\Domain\Access\Actions\AcceptInvitationAction;
use App\Domain\Access\Actions\CreateInvitationAction;
use App\Domain\Access\Models\Invitation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Invitations\AcceptInvitationRequest;
use App\Http\Requests\Invitations\StoreInvitationRequest;
use App\Http\Resources\InvitationResource;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InvitationController extends Controller
{
    /** All invitations, newest first (permission: invitations.viewAny). */
    public function index(): AnonymousResourceCollection
    {
        return InvitationResource::collection(
            Invitation::query()->with('inviter')->latest()->get(),
        );
    }

    /** Issue (or reissue) an invitation and email the link (permission: invitations.create). */
    public function store(StoreInvitationRequest $request, CreateInvitationAction $action): JsonResponse
    {
        $result = $action->execute(
            $request->string('email'),
            $request->string('role'),
            $request->user(),
        );

        return (new InvitationResource($result->invitation->load('inviter')))
            ->additional(['meta' => ['email_sent' => $result->emailSent]])
            ->response()
            ->setStatusCode(201);
    }

    /** Revoke a pending invitation (permission: invitations.revoke). */
    public function destroy(Invitation $invitation): JsonResponse
    {
        $invitation->delete();

        return response()->json(null, 204);
    }

    /** Public: validate a token so the accept page can show who/what it's for. */
    public function showByToken(string $token): JsonResponse
    {
        $invitation = Invitation::where('token', $token)->first();

        if (! $invitation || ! $invitation->isPending()) {
            return response()->json([
                'data' => ['valid' => false, 'reason' => $invitation ? $invitation->status() : 'not_found'],
            ], 404);
        }

        return response()->json([
            'data' => ['valid' => true, 'email' => $invitation->email, 'role' => $invitation->role],
        ]);
    }

    /** Public: accept the invite — create the user, assign the role, and log them in. */
    public function accept(AcceptInvitationRequest $request, string $token, AcceptInvitationAction $action): JsonResponse
    {
        $invitation = Invitation::where('token', $token)->first();

        if (! $invitation || ! $invitation->isPending()) {
            return response()->json(['message' => 'This invitation is no longer valid.'], 422);
        }

        $user = $action->execute($invitation, $request->string('name'), $request->string('password'));

        return response()->json([
            'data' => [
                'token' => $user->createToken('spa')->plainTextToken,
                'user' => new UserResource($user),
            ],
        ], 201);
    }
}
