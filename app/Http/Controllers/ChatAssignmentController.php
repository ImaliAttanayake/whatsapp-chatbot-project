<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\User;
use App\Services\ChatAssignmentService;
use Illuminate\Http\Request;

class ChatAssignmentController extends Controller
{
    public function __construct(private ChatAssignmentService $assignments)
    {
    }

    /**
     * Hand this chat to another admin, or release it back to the shared pool.
     */
    public function update(Contact $contact, Request $request)
    {
        $data = $request->validate([
            'user_id' => ['nullable', 'exists:users,id'],
        ]);

        $target = $data['user_id']
            ? User::whereKey($data['user_id'])
                ->whereIn('role', ['admin', 'superadmin'])
                ->first()
            : null;

        if ($data['user_id'] && !$target) {
            return response()->json([
                'ok' => false,
                'error' => 'That user is not an admin.',
            ], 422);
        }

        $this->assignments->assignTo($contact, $target);

        return response()->json([
            'ok' => true,
            'message' => $target
                ? "Chat transferred to {$target->name}."
                : 'Chat released back to the shared inbox.',
            'assigned_to' => $target ? ['id' => $target->id, 'name' => $target->name] : null,
        ]);
    }

    /**
     * Claim an unassigned chat for myself.
     */
    public function claim(Contact $contact)
    {
        if ($contact->assigned_admin_id && $contact->assigned_admin_id !== auth()->id()) {
            return response()->json([
                'ok' => false,
                'error' => 'Already assigned to ' . ($contact->assignedAdmin?->name ?? 'another admin') . '.',
            ], 409);
        }

        $this->assignments->assignTo($contact, auth()->user());

        return response()->json([
            'ok' => true,
            'message' => 'Chat assigned to you.',
        ]);
    }
}
