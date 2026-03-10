<?php

namespace App\Repositories;

use App\Models\Contact;
use App\Models\ContactList;
use App\Repositories\Contracts\ContactListRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ContactListRepository implements ContactListRepositoryInterface
{
    public function all(): Collection
    {
        return ContactList::withCount('contacts')->orderBy('id')->get();
    }

    public function create(array $data): ContactList
    {
        return ContactList::create($data);
    }

    public function findOrFail(int $id): ContactList
    {
        return ContactList::findOrFail($id);
    }

    public function addContact(ContactList $list, Contact $contact): void
    {
    
        $list->contacts()->syncWithoutDetaching([$contact->id]);
    }
}
