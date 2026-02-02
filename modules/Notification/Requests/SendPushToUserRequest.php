<?php
// Modules/Notification/Http/Requests/SendPushToUserRequest.php

namespace Modules\Notification\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendPushToUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'required_without:pin|integer',
            'pin' => 'required_without:user_id|string',
            'message' => 'required|array',
            'message.title' => 'required|string|max:255',
            'message.body' => 'required|string|max:1000',
            'meta' => 'nullable|array',
            'meta.channel' => 'nullable|in:system,marketing,transaction',
            'meta.type' => 'nullable|string',
            'meta.route' => 'nullable|string',
            'meta.sound' => 'nullable|string',
            'meta.priority' => 'nullable|in:high,normal',
            'meta.collapse_key' => 'nullable|string',
            'idempotency_key' => 'nullable|string|max:255',
            'ttl_seconds' => 'nullable|integer|min:0',
        ];
    }
}