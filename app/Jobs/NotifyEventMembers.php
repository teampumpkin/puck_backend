<?php

namespace App\Jobs;

use App\Models\V4Event;
use App\Models\V4User;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NotifyEventMembers implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $eventId,
        public string $type,
        public ?string $reason = null
    ) {
    }

    public function handle(NotificationService $notifications): void
    {
        $event = V4Event::withTrashed()->find($this->eventId);
        if (! $event) {
            return;
        }
        $imageUrl = optional($event->media()->first())->url ?? '';

        $organizer = optional($event->creator)->name ?? 'The organizer';
        $verb = $this->type === 'event_cancelled' ? 'cancelled' : 'deleted';
        $reason = trim((string) $this->reason);

        if ($reason !== '') {
            $title = $this->type === 'event_cancelled' ? 'Event cancelled' : 'Event deleted';
            $body = "{$organizer} {$verb} the event \"{$event->name}\". Reason: {$reason}";
        } else {
            // No reason given — self-contained title/body instead of a bare "Reason:".
            $title = "\"{$event->name}\" {$verb}";
            $body = "{$organizer} {$verb} this event.";
        }

        foreach (V4User::whereIn('id', $event->currentMemberIds())->get() as $member) {
            try {
                $notifications->sendToUserWithImage(
                    $member,
                    $title,
                    $body,
                    $imageUrl,
                    [],
                    $this->type,
                    "/events/detail/{$event->id}"
                );
            } catch (\Exception $e) {
                Log::error('NotifyEventMembers failed', ['event' => $this->eventId, 'e' => $e->getMessage()]);
            }
        }
    }
}
