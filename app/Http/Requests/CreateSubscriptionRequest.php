<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'plan_id' => 'required|exists:hosting_plans,id',
            'domain' => 'required|string|max:255|regex:/^[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
            'php_version' => 'nullable|string|in:8.1,8.2,8.3,8.5',
            'period' => 'required|in:monthly,yearly',
        ];
    }
}
