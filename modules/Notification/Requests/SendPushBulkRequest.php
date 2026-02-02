<?php

namespace Modules\Notification\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendPushBulkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'targets' => 'required|array|min:1|max:1000',
            'targets.*.type' => 'required|in:user_id,pin,device_id',
            'targets.*.value' => 'required',
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

    public function messages(): array
    {
        return [
            'targets.required' => 'Targets array is required',
            'targets.min' => 'At least 1 target is required',
            'targets.max' => 'Maximum 1000 targets allowed per request',
            'targets.*.type.required' => 'Target type is required',
            'targets.*.type.in' => 'Target type must be user_id, pin, or device_id',
            'targets.*.value.required' => 'Target value is required',
        ];
    }
}