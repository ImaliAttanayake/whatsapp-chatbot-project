<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Services\ChatAssignmentService;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ChatController extends Controller
{
    public function __construct(private ChatAssignmentService $assignments)
    {
    }

    private function listLimit(?int $limit = null): int
    {
        $default = (int) config('chat.list_limit', 40);
        $limit = $limit ?? $default;
        if ($limit <= 0) {
            return $default;
        }
        return min($limit, 200);
    }

    private function contactsQuery()
    {
        // Message history is loaded live from SLT API, not from local messages table.
        $user = auth()->user();

        return Contact::query()
            ->with([
                'lockedBy:id,name',
                'assignedAdmin:id,name',
                'humanHandoffAssignedTo:id,name',
            ])
            // Each chat belongs to one admin. Everyone else only sees it while
            // it is still unassigned (nobody online at arrival, or released).
            ->when(!$user->isSuperAdmin(), function ($query) use ($user) {
                $query->where(function ($q) use ($user) {
                    $q->whereNull('assigned_admin_id')
                        ->orWhere('assigned_admin_id', $user->id);
                });
            })
            ->orderByRaw("CASE human_handoff_status WHEN 'needs_human' THEN 0 WHEN 'assigned_to_agent' THEN 1 ELSE 2 END")
            ->orderByRaw('COALESCE(unread_message_count, 0) DESC')
            ->orderByRaw('COALESCE(last_message_at, updated_at) DESC')
            ->latest('updated_at');
    }

    public function index()
    {
        $listLimit = $this->listLimit();
        $contacts = $this->contactsQuery()->limit($listLimit)->get();

        return view('chats.index', compact('contacts', 'listLimit'));
    }

    public function show(Contact $contact)
    {
        if (!$this->assignments->canView($contact, auth()->user())) {
            throw new AccessDeniedHttpException('This chat is assigned to another admin.');
        }

        $contact->loadMissing([
            'lockedBy:id,name',
            'assignedAdmin:id,name',
            'humanHandoffAssignedTo:id,name',
        ]);
        $listLimit = $this->listLimit();
        $contacts = $this->contactsQuery()->limit($listLimit)->get();
        $assignableAdmins = $this->assignments->assignableAdmins();

        return view('chats.show', compact('contacts', 'contact', 'listLimit', 'assignableAdmins'));
    }

    public function list(Request $request)
    {
        $listLimit = $this->listLimit($request->integer('limit'));
        $contacts = $this->contactsQuery()->limit($listLimit)->get();
        $activeContactId = $request->query('active_contact_id');
        $showLock = $request->boolean('show_lock', false);
        $showActive = $request->boolean('show_active', false);
        $showPreview = $request->boolean('show_preview', false);
        return view('chats.partials.list-items', compact(
            'contacts',
            'activeContactId',
            'showLock',
            'showActive',
            'showPreview'
        ));
    }
}
