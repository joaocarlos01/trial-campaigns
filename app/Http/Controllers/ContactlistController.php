<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddContactToListRequest;
use App\Http\Requests\StoreContactListRequest;
use App\Repositories\Contracts\ContactListRepositoryInterface;
use App\Repositories\Contracts\ContactRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ContactlistController extends Controller
{
     public function __construct(
        private readonly ContactListRepositoryInterface $lists,
        private readonly ContactRepositoryInterface $contacts
    ) {}

    public function index(): JsonResponse
    {
        return response()->json($this->lists->all());
    }

    public function store(StoreContactListRequest $request): JsonResponse
    {
        $list = $this->lists->create($request->validated());

        return response()->json($list, Response::HTTP_CREATED);
    }

    public function addContact(AddContactToListRequest $request, int $id): JsonResponse
    {
        $list    = $this->lists->findOrFail($id);
        $contact = $this->contacts->findOrFail($request->validated('contact_id'));

        $this->lists->addContact($list, $contact);

        return response()->json(['message' => 'Contact added to list.']);
    }
}
