<?php

namespace App\Services;

use App\Jobs\SendCampaignEmail;
use App\Models\Campaign;
use App\Models\CampaignSend;
use Illuminate\Support\Facades\DB;

class CampaignService
{
    /**
     * Dispatch a campaign to all active contacts in its list.
     */
    public function dispatch(Campaign $campaign): void
    {
        //$contacts = $campaign->contactList->contacts()
        //    ->where('status', 'active')
        //    ->get();
//
        //foreach ($contacts as $contact) {
        //    $send = CampaignSend::create([
        //        'campaign_id' => $campaign->id,
        //        'contact_id'  => $contact->id,
        //        'status'      => 'pending',
        //    ]);
//
        //    SendCampaignEmail::dispatch($send->id);
        //}
//
        //$campaign->update(['status' => 'sending']);

        // Como estava feito iria carregar todos os contatos da lista ao mesmo tempo
        // Não exista verificação de envio 
        // Alterei o status update antes do loop

        DB::transaction(fn() => $campaign->update(['status' => 'sending'])); // atomic, before loop

        $campaign->contactList->contacts()
            ->where('status', 'active')
            ->chunkById(200, function ($contacts) use ($campaign) {
                foreach ($contacts as $contact) {
                    $send = CampaignSend::firstOrCreate(
                        ['campaign_id' => $campaign->id, 'contact_id' => $contact->id],
                        ['status' => 'pending']
                    );
                    if ($send->wasRecentlyCreated) {
                        SendCampaignEmail::dispatch($send->id);
                    }
                }
            });
    }

    public function buildPayload(Campaign $campaign, array $extra = []): array
    {
        $base = [
            'subject' => $campaign->subject,
            'body'    => $campaign->body,
        ];

        return [...$base, ...$extra];
    }

    public function resolveReplyTo(Campaign $campaign)
    {
        if (empty($campaign->reply_to)) {
            return null;
        }

        return $campaign->reply_to;
    }
}