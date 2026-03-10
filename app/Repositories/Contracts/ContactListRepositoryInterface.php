<?php

namespace App\Repositories\Contracts;

use App\Models\Contact;
use App\Models\ContactList;
use Illuminate\Database\Eloquent\Collection;

interface ContactListRepositoryInterface
{
    public function all(): Collection;

    public function create(array $data): ContactList;

    public function findOrFail(int $id): ContactList;

    public function addContact(ContactList $list, Contact $contact): void;
}
