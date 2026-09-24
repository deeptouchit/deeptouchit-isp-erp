<?php

namespace App\Http\Requests\Admin\Servers;

use Illuminate\Foundation\Http\FormRequest;

class ToggleMaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && ($this->user()->role === 'admin' || $this->user()->can('servers.maintenance'));
    }

    public function rules(): array
    {
        return [
            'enabled' => ['required', 'boolean'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
