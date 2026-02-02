<?php
// Modules/Notification/Services/ClientService.php

namespace Modules\Notification\Services;

use Illuminate\Http\JsonResponse;
use Modules\Core\Traits\ApiResponse;
use Modules\Notification\Models\Client;

class ClientService
{
    use ApiResponse;

    /**
     * Update client
     */
    public function update(array $data, Client $client): JsonResponse
    {
        try {
            $allowedFields = ['description', 'fcm_path'];

            $updateData = array_intersect_key($data, array_flip($allowedFields));
            $updateData['updated_at'] = now();

            $client->update($updateData);

            return $this->buildSuccess($client, 'Client updated successfully');

        } catch (\Exception $e) {
            return $this->buildError(500, 'Update failed: ' . $e->getMessage());
        }
    }
}