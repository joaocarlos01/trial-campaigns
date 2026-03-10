<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContactRequest;
use App\Repositories\Contracts\ContactRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ContactController extends Controller
{
     public function __construct(
        private readonly ContactRepositoryInterface $contacts
    ) {}

    public function index(): JsonResponse
    {
        return response()->json($this->contacts->paginate());
    }

    public function store(StoreContactRequest $request): JsonResponse
    {
        $contact = $this->contacts->create($request->validated());

        return response()->json($contact, Response::HTTP_CREATED);
    }

    public function unsubscribe(int $id): JsonResponse
    {
        $contact = $this->contacts->unsubscribe($id);

        return response()->json($contact);
    }
}
