<?php

namespace App\Console\Commands;

use App\Models\V4ChatMuteSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UnmuteExpiredChats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chat:unmute-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Unmute chats whose mute duration has expired';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Log::info('MyScheduledCommand started at ' . now());
        $muteSettings = V4ChatMuteSetting::where(function ($query) {
            $query->where('muted_until', '<', now())
                ->orWhereNull('muted_until'); // Skip users with "infinite" mute
        })
            ->where('active', true)
            ->get();

        foreach ($muteSettings as $muteSetting) {
            $this->info("Unmuted user {$muteSetting->user_id} in chat {$muteSetting->chat_id}");

            $baseUrl = config('services.chat.host');

            $response = Http::withHeaders([
                'Content-Type'  => 'application/json',
            ])->post($baseUrl . '/conversation/unmute-expired', [
                'userId' => $muteSetting->user_id,
                'conversationId' =>  $muteSetting->chat_id,
            ]);
            $muteSetting->update(['active' => false]); // Deactivate the mute setting
            Log::info('MyScheduledCommand Successfully at ' . $response);
        }
        Log::info('MyScheduledCommand finished at ' . now());
    }
}
