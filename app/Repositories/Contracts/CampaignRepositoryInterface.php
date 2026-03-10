<?php

namespace App\Repositories\Contracts;

use App\Models\Campaign;
use Illuminate\Pagination\LengthAwarePaginator;

interface CampaignRepositoryInterface
{
    public function paginateWithStats(int $perPage = 15): LengthAwarePaginator;

    public function create(array $data): Campaign;

    public function findOrFail(int $id): Campaign;
}
