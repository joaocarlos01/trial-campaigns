<?php

namespace App\Repositories\Contracts;

use App\Models\Contact;
use Illuminate\Pagination\LengthAwarePaginator;

interface ContactRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function create(array $data): Contact;

    public function findOrFail(int $id): Contact;

    public function unsubscribe(int $id): Contact;
}
