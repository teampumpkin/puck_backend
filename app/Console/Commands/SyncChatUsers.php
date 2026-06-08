<?php

namespace App\Console\Commands;

use App\Helpers\ChatUserSyncHelper;
use App\Models\V4User;
use Illuminate\Console\Command;

class SyncChatUsers extends Command
{
    protected $signature = 'chat:sync-users
        {--user= : Sync a single user by V4User id}
        {--chunk=200 : Chunk size when syncing all users}';

    protected $description = 'Upsert V4Users into the chat microservice so /conversation/create can resolve them.';

    public function handle(): int
    {
        $singleId = $this->option('user');
        $chunk = max(1, (int) $this->option('chunk'));

        if ($singleId) {
            $user = V4User::find($singleId);
            if (!$user) {
                $this->error("User {$singleId} not found.");
                return self::FAILURE;
            }
            $ok = ChatUserSyncHelper::sync($user);
            $this->line(($ok ? '✔' : '✖') . " user_id={$user->id}");
            return $ok ? self::SUCCESS : self::FAILURE;
        }

        $total = V4User::count();
        $this->info("Syncing {$total} users in chunks of {$chunk}...");

        $okCount = 0;
        $failCount = 0;

        V4User::orderBy('id')->chunk($chunk, function ($users) use (&$okCount, &$failCount) {
            foreach ($users as $user) {
                $ok = ChatUserSyncHelper::sync($user);
                $ok ? $okCount++ : $failCount++;
                $this->output->write($ok ? '.' : 'F');
            }
        });

        $this->newLine();
        $this->info("Done. ok={$okCount} failed={$failCount}");
        return $failCount === 0 ? self::SUCCESS : self::FAILURE;
    }
}
