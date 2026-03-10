<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\Contact;
use App\Models\ContactList;
use Illuminate\Database\Seeder;
use App\Models\CampaignSend;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 50 contacts, mix of active and unsubscribed
        $active       = Contact::factory()->count(40)->create();
        $unsubscribed = Contact::factory()->unsubscribed()->count(10)->create();
        $allContacts  = $active->merge($unsubscribed);

        // Two contact lists
        $newsletter = ContactList::factory()->create(['name' => 'Newsletter']);
        $promotions = ContactList::factory()->create(['name' => 'Promotions']);

        $newsletter->contacts()->attach($active->random(30));
        $promotions->contacts()->attach($active->random(20));

        // A sent campaign with full send stats
        $sentCampaign = Campaign::factory()->sent()->create([
            'subject'         => 'Welcome to our newsletter!',
            'contact_list_id' => $newsletter->id,
        ]);

        $newsletterContacts = $newsletter->contacts;
        foreach ($newsletterContacts as $contact) {
            CampaignSend::factory()
                ->state(['campaign_id' => $sentCampaign->id, 'contact_id' => $contact->id])
                ->sent()
                ->create();
        }

        // A draft campaign scheduled for the future
        Campaign::factory()->scheduled()->create([
            'subject'         => 'Summer Promotions',
            'contact_list_id' => $promotions->id,
        ]);

        // A draft campaign with no scheduled date
        Campaign::factory()->create([
            'subject'         => 'Draft — not yet scheduled',
            'contact_list_id' => $newsletter->id,
        ]);
    }
}
