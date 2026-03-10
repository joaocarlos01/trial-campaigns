<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCampaignRequest;
use App\Repositories\Contracts\CampaignRepositoryInterface;
use App\Services\CampaignService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class CampaignController extends Controller
{
   public function __construct(
        private readonly CampaignRepositoryInterface $campaigns,
        private readonly CampaignService $campaignService
    ) {}

    public function index(): JsonResponse
    {
        return response()->json($this->campaigns->paginateWithStats());
    }

    public function store(StoreCampaignRequest $request): JsonResponse
    {
        $campaign = $this->campaigns->create($request->validated());

        return response()->json($campaign, Response::HTTP_CREATED);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json($this->campaigns->findOrFail($id));
    }

    public function dispatch(int $id): JsonResponse
    {
        $campaign = $this->campaigns->findOrFail($id);

        $this->campaignService->dispatch($campaign);

        return response()->json(['message' => 'Campaign dispatched successfully.']);
    }
}
