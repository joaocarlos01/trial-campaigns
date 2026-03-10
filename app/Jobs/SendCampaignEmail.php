<?php

namespace App\Jobs;

use App\Models\CampaignSend;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;


class SendCampaignEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [30, 60, 120];
    public int $timeout = 60;

    public function __construct(
        private readonly int $campaignSendId
    ) {}

    public function handle(): void
    {
        
        $send = DB::transaction(function () {
            return CampaignSend::with(['contact', 'campaign'])
                ->where('id', $this->campaignSendId)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->first();
        });

        if (! $send) {
            return;
        }

        try {
            $this->sendEmail(
                $send->contact->email,
                $send->campaign->subject,
                $send->campaign->body
            );

            $send->update([
                'status'  => 'sent',
                'sent_at' => now(),
            ]);
        } catch (\Exception $e) {
            $send->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            Log::error('Campaign send failed', [
                'send_id'     => $send->id,
                'campaign_id' => $send->campaign_id,
                'contact_id'  => $send->contact_id,
                'error'       => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function sendEmail(string $to, string $subject, string $body): void
    {
        Log::info('Sending email', compact('to', 'subject'));

    }

    public function failed(\Throwable $e): void
    {
        Log::critical('SendCampaignEmail exhausted all retries', [
            'send_id' => $this->campaignSendId,
            'error'   => $e->getMessage(),
        ]);
    }
}
