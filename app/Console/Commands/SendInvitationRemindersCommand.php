<?php

namespace App\Console\Commands;

use App\Jobs\SendInvitationReminderJob;
use App\Models\Invitation;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SendInvitationRemindersCommand extends Command
{
    protected $signature = 'workspace:send-invitation-reminders';

    protected $description = 'Send reminder emails for pending invitations older than 3 days';

    public function handle(): int
    {
        Invitation::query()
            ->whereNull('accepted_at')
            ->where('created_at', '<=', now()->subDays(3))
            ->where('expires_at', '>', now())
            ->chunkById(100, function ($invitations): void {
                foreach ($invitations as $invitation) {
                    $token = Str::random(64);
                    $invitation->update(['token_hash' => hash('sha256', $token)]);

                    SendInvitationReminderJob::dispatch($invitation, $token);
                }
            });

        $this->info('Invitation reminders queued.');

        return self::SUCCESS;
    }
}
