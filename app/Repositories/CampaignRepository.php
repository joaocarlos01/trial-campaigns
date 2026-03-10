<?php

namespace App\Repositories;

use App\Models\Campaign;
use App\Repositories\Contracts\CampaignRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class CampaignRepository implements CampaignRepositoryInterface
{
    public function paginateWithStats(int $perPage = 15): LengthAwarePaginator
    {
        return Campaign::withCount([
            'sends',
            'sends as pending_sends_count' => fn ($q) => $q->where('status', 'pending'),
            'sends as sent_sends_count'    => fn ($q) => $q->where('status', 'sent'),
            'sends as failed_sends_count'  => fn ($q) => $q->where('status', 'failed'),
        ])
        ->with('contactList:id,name')
        ->orderByDesc('id')
        ->paginate($perPage);
    }

    public function create(array $data): Campaign
    {
        return Campaign::create($data)->fresh();
    }

    public function findOrFail(int $id): Campaign
    {
        return Campaign::withCount([
            'sends',
            'sends as pending_sends_count' => fn ($q) => $q->where('status', 'pending'),
            'sends as sent_sends_count'    => fn ($q) => $q->where('status', 'sent'),
            'sends as failed_sends_count'  => fn ($q) => $q->where('status', 'failed'),
        ])
        ->with('contactList:id,name')
        ->findOrFail($id);
    }
}
