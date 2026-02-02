<?php
// Modules/Notification/Controllers/TagController.php

namespace Modules\Notification\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Traits\ApiResponse;
use Modules\Notification\Services\TagService;

class TagController
{
    use ApiResponse;

    public function __construct(
        private readonly TagService $tagService
    ) {}

    /**
     * List all tags
     */
    public function index(): JsonResponse
    {
        $client = app('notification.client');
        return $this->tagService->list($client);
    }

    /**
     * Create new tag
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $client = app('notification.client');
        return $this->tagService->create($request->all(), $client);
    }

    /**
     * Get tag details
     */
    public function show(int $tagId): JsonResponse
    {
        $client = app('notification.client');
        return $this->tagService->show($tagId, $client);
    }

    /**
     * Update tag
     */
    public function update(Request $request, int $tagId): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $client = app('notification.client');
        return $this->tagService->update($tagId, $request->all(), $client);
    }

    /**
     * Delete tag
     */
    public function destroy(int $tagId): JsonResponse
    {
        $client = app('notification.client');
        return $this->tagService->delete($tagId, $client);
    }

    /**
     * Add users to tag
     */
    public function addUsers(Request $request, int $tagId): JsonResponse
    {
        $request->validate([
            'user_ids' => 'required|array|min:1|max:1000',
            'user_ids.*' => 'required|integer',
        ]);

        $client = app('notification.client');
        return $this->tagService->addUsers($tagId, $request->all(), $client);
    }

    /**
     * Remove users from tag
     */
    public function removeUsers(Request $request, int $tagId): JsonResponse
    {
        $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'required|integer',
        ]);

        $client = app('notification.client');
        return $this->tagService->removeUsers($tagId, $request->all(), $client);
    }


    /**
     * Get tag users
     */
    public function users(int $tagId): JsonResponse
    {
        $client = app('notification.client');
        return $this->tagService->getUsers($tagId, $client);
    }
}