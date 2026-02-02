<?php

namespace Modules\Notification\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'app_user_id' => 'required|integer',
            'device_id' => 'required|string|max:255',
            'push_token' => 'required|string',
            'platform' => 'required|in:ios,android',
            'app_version' => 'nullable|string|max:50',
            'os_version' => 'nullable|string|max:50',
            'model' => 'nullable|string|max:100',
            'locale' => 'nullable|string|max:10',
            'timezone' => 'nullable|string|max:50',
        ];
    }

    public function messages(): array
    {
        return [
            'app_user_id.required' => 'User ID is required',
            'device_id.required' => 'Device ID is required',
            'push_token.required' => 'Push token is required',
            'platform.required' => 'Platform is required',
            'platform.in' => 'Platform must be ios or android',
        ];
    }
}