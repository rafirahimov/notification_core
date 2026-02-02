<?php
// Modules/Notification/Services/TagService.php

namespace Modules\Notification\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Modules\Core\Traits\ApiResponse;
use Modules\Notification\Models\AppUserTag;
use Modules\Notification\Models\Client;
use Modules\Notification\Models\Tag;

class TagService
{
    use ApiResponse;

    /**
     * List all tags
     */
    public function list(Client $client): JsonResponse
    {
        try {
            $tags = Tag::query()
                ->where('bundle_id', $client->bundle_id)
                ->orderBy('name')
                ->get(['id', 'name', 'created_at'])
                ->map(function ($tag) use ($client) {
                    return [
                        'id' => $tag->id,
                        'name' => $tag->name,
                        'user_count' => AppUserTag::query()
                            ->where('bundle_id', $client->bundle_id)
                            ->where('tag_id', $tag->id)
                            ->distinct('app_user_id')
                            ->count('app_user_id'),
                        'created_at' => $tag->created_at,
                    ];
                });

            return $this->buildSuccess($tags);

        } catch (\Exception $e) {
            return $this->buildError(500, 'Failed to fetch tags: ' . $e->getMessage());
        }
    }

    /**
     * Create new tag
     */
    public function create(array $data, Client $client): JsonResponse
    {
        DB::beginTransaction();

        try {
            // Tag name unique check
            $exists = Tag::query()
                ->where('bundle_id', $client->bundle_id)
                ->where('name', $data['name'])
                ->exists();

            if ($exists) {
                return $this->buildError(400, 'Tag name already exists');
            }

            $tag = Tag::create([
                'client_id' => $client->id,
                'bundle_id' => $client->bundle_id,
                'name' => $data['name'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            return $this->buildSuccess([
                'id' => $tag->id,
                'name' => $tag->name,
                'created_at' => $tag->created_at,
            ], 'Tag created successfully', 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->buildError(500, 'Tag creation failed: ' . $e->getMessage());
        }
    }

    /**
     * Show tag details
     */
    public function show(int $tagId, Client $client): JsonResponse
    {
        try {
            $tag = Tag::query()
                ->where('id', $tagId)
                ->where('bundle_id', $client->bundle_id)
                ->first();

            if (!$tag) {
                return $this->buildError(404, 'Tag not found');
            }

            $userCount = AppUserTag::query()
                ->where('bundle_id', $client->bundle_id)
                ->where('tag_id', $tag->id)
                ->distinct('app_user_id')
                ->count('app_user_id');

            return $this->buildSuccess([
                'id' => $tag->id,
                'name' => $tag->name,
                'user_count' => $userCount,
                'created_at' => $tag->created_at,
                'updated_at' => $tag->updated_at,
            ]);

        } catch (\Exception $e) {
            return $this->buildError(500, 'Failed to fetch tag: ' . $e->getMessage());
        }
    }

    /**
     * Update tag
     */
    public function update(int $tagId, array $data, Client $client): JsonResponse
    {
        DB::beginTransaction();

        try {
            $tag = Tag::query()
                ->where('id', $tagId)
                ->where('bundle_id', $client->bundle_id)
                ->first();

            if (!$tag) {
                return $this->buildError(404, 'Tag not found');
            }

            // Name unique check (exclude current tag)
            $exists = Tag::query()
                ->where('bundle_id', $client->bundle_id)
                ->where('name', $data['name'])
                ->where('id', '!=', $tagId)
                ->exists();

            if ($exists) {
                return $this->buildError(400, 'Tag name already exists');
            }

            $tag->update([
                'name' => $data['name'],
                'updated_at' => now(),
            ]);

            DB::commit();

            return $this->buildSuccess($tag, 'Tag updated successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->buildError(500, 'Tag update failed: ' . $e->getMessage());
        }
    }

    /**
     * Delete tag
     */
    public function delete(int $tagId, Client $client): JsonResponse
    {
        DB::beginTransaction();

        try {
            $tag = Tag::query()
                ->where('id', $tagId)
                ->where('bundle_id', $client->bundle_id)
                ->first();

            if (!$tag) {
                return $this->buildError(404, 'Tag not found');
            }

            // Delete all user associations
            AppUserTag::query()
                ->where('bundle_id', $client->bundle_id)
                ->where('tag_id', $tagId)
                ->delete();

            // Delete tag
            $tag->delete();

            DB::commit();

            return $this->buildSuccess(null, 'Tag deleted successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->buildError(500, 'Tag deletion failed: ' . $e->getMessage());
        }
    }

    /**
     * Add users to tag
     */
    public function addUsers(int $tagId, array $data, Client $client): JsonResponse
    {
        DB::beginTransaction();

        try {
            $tag = Tag::query()
                ->where('id', $tagId)
                ->where('bundle_id', $client->bundle_id)
                ->first();

            if (!$tag) {
                return $this->buildError(404, 'Tag not found');
            }

            $addedCount = 0;
            $skippedCount = 0;

            foreach ($data['user_ids'] as $userId) {
                // Check if already exists
                $exists = AppUserTag::query()
                    ->where('bundle_id', $client->bundle_id)
                    ->where('tag_id', $tagId)
                    ->where('app_user_id', $userId)
                    ->exists();

                if ($exists) {
                    $skippedCount++;
                    continue;
                }

                AppUserTag::query()->create([
                    'app_user_id' => $userId,
                    'bundle_id' => $client->bundle_id,
                    'tag_id' => $tagId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $addedCount++;
            }

            DB::commit();

            return $this->buildSuccess([
                'tag_id' => $tagId,
                'added' => $addedCount,
                'skipped' => $skippedCount,
                'total' => count($data['user_ids']),
            ], 'Users added to tag');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->buildError(500, 'Failed to add users: ' . $e->getMessage());
        }
    }

    /**
     * Remove users from tag
     */
    public function removeUsers(int $tagId, array $data, Client $client): JsonResponse
    {
        DB::beginTransaction();

        try {
            $tag = Tag::query()
                ->where('id', $tagId)
                ->where('bundle_id', $client->bundle_id)
                ->first();

            if (!$tag) {
                return $this->buildError(404, 'Tag not found');
            }

            $deleted = AppUserTag::query()
                ->where('bundle_id', $client->bundle_id)
                ->where('tag_id', $tagId)
                ->whereIn('app_user_id', $data['user_ids'])
                ->delete();

            DB::commit();

            return $this->buildSuccess([
                'tag_id' => $tagId,
                'removed' => $deleted,
                'requested' => count($data['user_ids']),
            ], 'Users removed from tag');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->buildError(500, 'Failed to remove users: ' . $e->getMessage());
        }
    }

    /**
     * Get tag users
     */
    public function getUsers(int $tagId, Client $client): JsonResponse
    {
        try {
            $tag = Tag::query()
                ->where('id', $tagId)
                ->where('bundle_id', $client->bundle_id)
                ->first();

            if (!$tag) {
                return $this->buildError(404, 'Tag not found');
            }

            $users = AppUserTag::query()
                ->where('bundle_id', $client->bundle_id)
                ->where('tag_id', $tagId)
                ->orderBy('created_at', 'desc')
                ->get(['app_user_id', 'created_at']);

            return $this->buildSuccess([
                'tag_id' => $tagId,
                'tag_name' => $tag->name,
                'total_users' => $users->count(),
                'users' => $users,
            ]);

        } catch (\Exception $e) {
            return $this->buildError(500, 'Failed to fetch users: ' . $e->getMessage());
        }
    }
}