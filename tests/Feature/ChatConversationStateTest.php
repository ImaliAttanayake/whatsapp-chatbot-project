<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\User;
use App\Services\ChatConversationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ChatConversationStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_contact_state_tracks_latest_message_and_unread_status(): void
    {
        $contact = Contact::create([
            'mobile' => '94712345678',
            'name' => '94712345678',
        ]);

        $service = app(ChatConversationService::class);

        $service->syncContactState($contact, [
            [
                'id' => 'in-1',
                'uuid' => 'msg-in-1',
                'direction' => 'in',
                'body' => 'Need help with my package',
                'sent_at' => '2026-03-23T10:00:00+05:30',
                'time_hint' => null,
            ],
            [
                'id' => 'out-1',
                'uuid' => 'msg-out-1',
                'direction' => 'out',
                'body' => 'A human agent is replying now',
                'sent_at' => '2026-03-23T10:01:00+05:30',
                'time_hint' => null,
            ],
        ]);

        $fresh = $contact->fresh();

        $this->assertSame('out', $fresh->last_message_direction);
        $this->assertSame('A human agent is replying now', $fresh->last_message_preview);
        $this->assertSame('Need help with my package', $fresh->last_inbound_message_preview);
        $this->assertSame('msg-in-1', $fresh->last_inbound_message_key);
        $this->assertTrue($fresh->has_unread);
        $this->assertSame(1, $fresh->unread_count);
        $this->assertSame(1, (int) $fresh->unread_message_count);
        $this->assertFalse((bool) $fresh->human_handoff_active);
        $this->assertSame('open', $fresh->human_handoff_status);
        $this->assertFalse((bool) $fresh->bot_paused);

        $fresh->update([
            'last_read_at' => Carbon::parse('2026-03-23T10:00:00+05:30'),
        ]);

        $this->assertFalse($fresh->fresh()->has_unread);

        $fresh->update([
            'last_read_at' => Carbon::parse('2026-03-23T09:59:00+05:30'),
        ]);

        $this->assertTrue($fresh->fresh()->has_unread);
    }

    public function test_sync_contact_state_detects_customer_handoff_requests_and_counts_unread_messages(): void
    {
        $contact = Contact::create([
            'mobile' => '94712345679',
            'name' => '94712345679',
            'last_read_at' => Carbon::parse('2026-03-23T09:59:00+05:30'),
        ]);

        $service = app(ChatConversationService::class);

        $service->syncContactState($contact, [
            [
                'id' => 'in-1',
                'uuid' => 'msg-in-1',
                'direction' => 'in',
                'body' => 'I need a human agent please',
                'sent_at' => '2026-03-23T10:00:00+05:30',
                'time_hint' => null,
            ],
            [
                'id' => 'in-2',
                'uuid' => 'msg-in-2',
                'direction' => 'in',
                'body' => 'Are you there?',
                'sent_at' => '2026-03-23T10:01:00+05:30',
                'time_hint' => null,
            ],
        ]);

        $fresh = $contact->fresh();

        $this->assertTrue((bool) $fresh->human_handoff_active);
        $this->assertSame('needs_human', $fresh->human_handoff_status);
        $this->assertTrue((bool) $fresh->bot_paused);
        $this->assertTrue($fresh->needs_human);
        $this->assertSame('msg-in-1', $fresh->human_handoff_message_key);
        $this->assertSame('I need a human agent please', $fresh->human_handoff_message_preview);
        $this->assertTrue($fresh->has_unread);
        $this->assertSame(2, $fresh->unread_count);
        $this->assertSame(2, (int) $fresh->unread_message_count);
    }

    public function test_assign_human_handoff_marks_the_contact_as_owned_by_the_agent(): void
    {
        $contact = Contact::create([
            'mobile' => '94712345670',
            'name' => '94712345670',
        ]);
        $agent = User::factory()->create();

        $service = app(ChatConversationService::class);
        $service->assignHumanHandoff($contact, $agent->id, Carbon::parse('2026-03-23T10:05:00+05:30'));

        $fresh = $contact->fresh();

        $this->assertTrue((bool) $fresh->human_handoff_active);
        $this->assertSame('assigned_to_agent', $fresh->human_handoff_status);
        $this->assertTrue((bool) $fresh->bot_paused);
        $this->assertFalse($fresh->needs_human);
        $this->assertSame($agent->id, $fresh->human_handoff_assigned_user_id);
        $this->assertTrue($fresh->human_handoff_assigned_at?->equalTo(Carbon::parse('2026-03-23T10:05:00+05:30')));
        $this->assertTrue($fresh->human_handoff_requested_at?->equalTo(Carbon::parse('2026-03-23T10:05:00+05:30')));
    }

    public function test_resolved_handoff_is_not_reopened_by_old_api_messages_but_new_request_refreshes_it(): void
    {
        $contact = Contact::create([
            'mobile' => '94712345671',
            'name' => '94712345671',
        ]);

        $service = app(ChatConversationService::class);
        $oldRequest = [
            'id' => 'in-1',
            'uuid' => 'msg-in-1',
            'direction' => 'in',
            'body' => 'human agent',
            'sent_at' => '2026-03-23T10:00:00+05:30',
            'time_hint' => null,
        ];

        $service->syncContactState($contact, [$oldRequest]);
        $service->resetHumanHandoff($contact->fresh(), 'resolved');

        $service->syncContactState($contact->fresh(), [$oldRequest]);
        $resolved = $contact->fresh();
        $this->assertSame('resolved', $resolved->human_handoff_status);
        $this->assertFalse((bool) $resolved->bot_paused);
        $this->assertFalse($resolved->needs_human);

        $service->syncContactState($resolved, [
            $oldRequest,
            [
                'id' => 'in-2',
                'uuid' => 'msg-in-2',
                'direction' => 'in',
                'body' => 'I still need a human agent',
                'sent_at' => '2026-03-23T10:10:00+05:30',
                'time_hint' => null,
            ],
        ]);

        $fresh = $contact->fresh();
        $this->assertSame('needs_human', $fresh->human_handoff_status);
        $this->assertTrue((bool) $fresh->bot_paused);
        $this->assertSame('msg-in-2', $fresh->human_handoff_message_key);
        $this->assertTrue($fresh->human_handoff_requested_at?->equalTo(Carbon::parse('2026-03-23T10:10:00+05:30')));
    }
}
