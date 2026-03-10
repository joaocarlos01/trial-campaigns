<?php

namespace Tests\Feature;

use App\Jobs\SendCampaignEmail;
use App\Models\Campaign;
use App\Models\CampaignSend;
use App\Models\Contact;
use App\Models\ContactList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CampaignApiTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Listing & showing
    // -------------------------------------------------------------------------

    public function test_can_list_campaigns_with_send_stats(): void
    {
        $list     = ContactList::factory()->create();
        $campaign = Campaign::factory()->create(['contact_list_id' => $list->id]);

        CampaignSend::factory()->create(['campaign_id' => $campaign->id, 'status' => 'sent']);
        CampaignSend::factory()->create(['campaign_id' => $campaign->id, 'status' => 'failed']);

        $response = $this->getJson('/api/campaigns');

        $response->assertOk()
            ->assertJsonPath('data.0.sent_sends_count', 1)
            ->assertJsonPath('data.0.failed_sends_count', 1)
            ->assertJsonPath('data.0.pending_sends_count', 0);
    }

    public function test_can_show_a_single_campaign_with_stats(): void
    {
        $campaign = Campaign::factory()->create();
        CampaignSend::factory()->count(3)->create(['campaign_id' => $campaign->id, 'status' => 'sent']);

        $this->getJson("/api/campaigns/{$campaign->id}")
            ->assertOk()
            ->assertJsonPath('sent_sends_count', 3);
    }

    // -------------------------------------------------------------------------
    // Creating
    // -------------------------------------------------------------------------

    public function test_can_create_a_campaign(): void
    {
        $list = ContactList::factory()->create();

        $response = $this->postJson('/api/campaigns', [
            'subject'         => 'Hello World',
            'body'            => 'Email body content.',
            'contact_list_id' => $list->id,
        ]);

        $response->assertCreated()
            ->assertJsonFragment(['subject' => 'Hello World', 'status' => 'draft']);

        $this->assertDatabaseHas('campaigns', ['subject' => 'Hello World']);
    }

    public function test_create_campaign_rejects_past_scheduled_at(): void
    {
        $list = ContactList::factory()->create();

        $this->postJson('/api/campaigns', [
            'subject'         => 'Late',
            'body'            => 'Body',
            'contact_list_id' => $list->id,
            'scheduled_at'    => now()->subDay()->toDateTimeString(),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['scheduled_at']);
    }

    // -------------------------------------------------------------------------
    // Dispatching
    // -------------------------------------------------------------------------

    public function test_dispatching_a_draft_campaign_queues_jobs_for_active_contacts(): void
    {
        Queue::fake();

        $list     = ContactList::factory()->create();
        $campaign = Campaign::factory()->create(['contact_list_id' => $list->id, 'status' => 'draft']);

        $activeContacts      = Contact::factory()->count(3)->create(['status' => 'active']);
        $unsubscribedContact = Contact::factory()->create(['status' => 'unsubscribed']);

        // syncWithoutDetaching accepts arrays/collections safely and respects the unique constraint
        $list->contacts()->syncWithoutDetaching($activeContacts->pluck('id')->toArray());
        $list->contacts()->syncWithoutDetaching([$unsubscribedContact->id]);

        $this->postJson("/api/campaigns/{$campaign->id}/dispatch")
            ->assertOk()
            ->assertJsonFragment(['message' => 'Campaign dispatched successfully.']);

        // Only the 3 active contacts should have jobs queued
        Queue::assertPushed(SendCampaignEmail::class, 3);

        $this->assertDatabaseHas('campaigns', ['id' => $campaign->id, 'status' => 'sending']);
        $this->assertDatabaseCount('campaign_sends', 3);
    }

    public function test_dispatching_a_non_draft_campaign_is_rejected(): void
    {
        Queue::fake();

        $campaign = Campaign::factory()->create(['status' => 'sending']);

        $this->postJson("/api/campaigns/{$campaign->id}/dispatch")
            ->assertUnprocessable()
            ->assertJsonFragment(['error' => 'Campaign must be in draft status to be dispatched.']);

        Queue::assertNothingPushed();
    }

    public function test_dispatching_same_campaign_twice_does_not_create_duplicate_sends(): void
    {
        Queue::fake();

        $list     = ContactList::factory()->create();
        $campaign = Campaign::factory()->create(['contact_list_id' => $list->id, 'status' => 'draft']);
        $contact  = Contact::factory()->create(['status' => 'active']);
        $list->contacts()->syncWithoutDetaching([$contact->id]);

        // First dispatch
        $this->postJson("/api/campaigns/{$campaign->id}/dispatch")->assertOk();

        // Use query builder instead of Eloquent update() to bypass dirty-checking.
        // The test's local $campaign model still holds status='draft' (the service
        // updated a different model instance), so Eloquent sees no change and silently
        // skips the SQL. Query builder always executes the UPDATE.
        Campaign::where('id', $campaign->id)->update(['status' => 'draft']);

        // Second dispatch
        $this->postJson("/api/campaigns/{$campaign->id}/dispatch")->assertOk();

        // Still only one CampaignSend row — idempotency holds
        $this->assertDatabaseCount('campaign_sends', 1);
        // Job was only queued once (wasRecentlyCreated = false on second pass)
        Queue::assertPushed(SendCampaignEmail::class, 1);
    }

    // -------------------------------------------------------------------------
    // Job behaviour
    // -------------------------------------------------------------------------

    public function test_send_job_marks_send_as_sent(): void
    {
        $send = CampaignSend::factory()->create(['status' => 'pending']);

        (new \App\Jobs\SendCampaignEmail($send->id))->handle();

        $this->assertDatabaseHas('campaign_sends', ['id' => $send->id, 'status' => 'sent']);
    }

    public function test_send_job_is_idempotent_and_skips_already_sent_rows(): void
    {
        // Simulate a job that already completed (status = 'sent') being delivered again
        $send = CampaignSend::factory()->sent()->create();

        // Should not throw or change anything
        (new \App\Jobs\SendCampaignEmail($send->id))->handle();

        $this->assertDatabaseHas('campaign_sends', ['id' => $send->id, 'status' => 'sent']);
    }
}