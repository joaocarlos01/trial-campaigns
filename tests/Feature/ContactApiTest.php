<?php

namespace Tests\Feature;

use App\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_contacts_paginated(): void
    {
        Contact::factory()->count(20)->create();

        $response = $this->getJson('/api/contacts');

        $response->assertOk()
            ->assertJsonStructure([
                'data'         => [['id', 'name', 'email', 'status']],
                'current_page',
                'per_page',
                'total',
            ]);

        $this->assertCount(15, $response->json('data'));
    }

    public function test_can_create_a_contact(): void
    {
        $response = $this->postJson('/api/contacts', [
            'name'  => 'Ana Silva',
            'email' => 'ana@example.com',
        ]);

        $response->assertCreated()
            ->assertJsonFragment(['email' => 'ana@example.com', 'status' => 'active']);

        $this->assertDatabaseHas('contacts', ['email' => 'ana@example.com']);
    }

    public function test_create_contact_rejects_duplicate_email(): void
    {
        Contact::factory()->create(['email' => 'duplicate@example.com']);

        $this->postJson('/api/contacts', [
            'name'  => 'Another Person',
            'email' => 'duplicate@example.com',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_create_contact_requires_name_and_email(): void
    {
        $this->postJson('/api/contacts', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email']);
    }

    public function test_can_unsubscribe_a_contact(): void
    {
        $contact = Contact::factory()->create(['status' => 'active']);

        $this->postJson("/api/contacts/{$contact->id}/unsubscribe")
            ->assertOk()
            ->assertJsonFragment(['status' => 'unsubscribed']);

        $this->assertDatabaseHas('contacts', [
            'id'     => $contact->id,
            'status' => 'unsubscribed',
        ]);
    }

    public function test_unsubscribe_returns_404_for_missing_contact(): void
    {
        $this->postJson('/api/contacts/999/unsubscribe')
            ->assertNotFound();
    }
}
