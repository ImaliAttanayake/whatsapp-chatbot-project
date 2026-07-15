<?php

namespace App\Console\Commands;

use App\Models\Contact;
use App\Models\Message;
use App\Services\SltWhatsappClient;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class WhatsappSync extends Command
{
    protected $signature = 'whatsapp:sync {--contact_id=} {--limit=20}';
    protected $description = 'Sync inbound messages from SLT WhatsApp API into DB';

    public function handle(SltWhatsappClient $client): int
    {
        $limit = (int) $this->option('limit');

        $q = Contact::query();
        if ($this->option('contact_id')) {
            $q->where('id', $this->option('contact_id'));
        }

        $contacts = $q->get();
        if ($contacts->isEmpty()) {
            $this->warn("No contacts to sync.");
            return self::SUCCESS;
        }

        foreach ($contacts as $contact) {
            try {
                $payload = $client->getMessages($contact->mobile, $limit);
            } catch (\Throwable $e) {
                $this->warn("Skipped {$contact->mobile}: " . $this->shortError($e));
                continue;
            }

            // NOTE: you MUST adjust this mapping to match SLT's actual JSON format.
            // We assume messages are in $payload['messages'] or $payload['data'].
            $items = $payload['messages'] ?? $payload['data'] ?? [];

            $newCount = 0;

            foreach ($items as $m) {
                $uuid = $m['uuid'] ?? $m['id'] ?? null;
                $body = $m['message'] ?? $m['text'] ?? $m['body'] ?? null;

                // Determine direction if provided; otherwise treat as inbound from API
                $dir = ($m['direction'] ?? $m['type'] ?? 'in') === 'out' ? 'out' : 'in';

                $ts = $m['timestamp'] ?? $m['time'] ?? null;
                $sentAt = $ts ? Carbon::parse($ts) : now();

                if (!$uuid) {
                    // If SLT doesn't give uuid, create a pseudo uuid to avoid duplicates
                    $uuid = 'pseudo_' . sha1($contact->id . '|' . $sentAt->timestamp . '|' . ($body ?? ''));
                }

                $exists = Message::where('contact_id', $contact->id)->where('uuid', $uuid)->exists();
                if ($exists) continue;

                Message::create([
                    'contact_id' => $contact->id,
                    'uuid' => $uuid,
                    'direction' => $dir,
                    'body' => $body,
                    'sent_at' => $sentAt,
                    'raw' => $m,
                ]);

                $newCount++;
            }

            $contact->update(['last_synced_at' => now()]);
            $this->info("Synced {$newCount} new messages for {$contact->mobile}");
        }

        return self::SUCCESS;
    }

    private function shortError(\Throwable $e): string
    {
        $message = trim($e->getMessage());
        if ($message !== '') {
            return $message;
        }

        return class_basename($e);
    }
}
