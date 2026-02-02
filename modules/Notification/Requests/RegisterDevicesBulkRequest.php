<?php

namespace Modules\Notification\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterDevicesBulkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'devices' => 'required|array|min:1|max:100',
            'devices.*.app_user_id' => 'required|integer',
            'devices.*.device_id' => 'required|string|max:255',
            'devices.*.push_token' => 'required|string',
            'devices.*.platform' => 'required|in:ios,android',
            'devices.*.app_version' => 'nullable|string|max:50',
            'devices.*.os_version' => 'nullable|string|max:50',
            'devices.*.model' => 'nullable|string|max:100',
            'devices.*.locale' => 'nullable|string|max:10',
            'devices.*.timezone' => 'nullable|string|max:50',
        ];
    }

    public function messages(): array
    {
        return [
            'devices.required' => 'Devices array is required',
            'devices.min' => 'At least 1 device is required',
            'devices.max' => 'Maximum 100 devices allowed per request',
            'devices.*.app_user_id.required' => 'User ID is required for each device',
            'devices.*.device_id.required' => 'Device ID is required for each device',
            'devices.*.push_token.required' => 'Push token is required for each device',
            'devices.*.platform.required' => 'Platform is required for each device',
            'devices.*.platform.in' => 'Platform must be ios or android',
        ];
    }
}