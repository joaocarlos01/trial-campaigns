<?php

namespace App\Repositories;

use App\Models\Contact;
use App\Repositories\Contracts\ContactRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class ContactRepository implements ContactRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Contact::orderBy('id')->paginate($perPage);
    }

    public function create(array $data): Contact
    {
        return Contact::create($data)->fresh();
    }

    public function findOrFail(int $id): Contact
    {
        return Contact::findOrFail($id);
    }

    public function unsubscribe(int $id): Contact
    {
        $contact = $this->findOrFail($id);
        $contact->update(['status' => 'unsubscribed']);

        return $contact->fresh();
    }
}
