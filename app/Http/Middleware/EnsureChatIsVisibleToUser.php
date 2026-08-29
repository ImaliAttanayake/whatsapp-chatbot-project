<?php

namespace App\Http\Middleware;

use App\Models\Contact;
use App\Services\ChatAssignmentService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks the chat JSON/lock endpoints for a chat that belongs to another admin,
 * so ownership cannot be bypassed by calling the API directly.
 */
class EnsureChatIsVisibleToUser
{
    public function __construct(private ChatAssignmentService $assignments)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $contact = $request->route('contact');

        if ($contact instanceof Contact
            && !$this->assignments->canView($contact, $request->user())) {
            return response()->json([
                'ok' => false,
                'error' => 'This chat is assigned to '
                    . ($contact->assignedAdmin?->name ?? 'another admin') . '.',
            ], 403);
        }

        return $next($request);
    }
}
