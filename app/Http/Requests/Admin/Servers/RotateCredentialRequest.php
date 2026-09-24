<?php

namespace App\Http\Requests\Admin\Servers;

use App\Enums\Infrastructure\ServerCredentialType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class RotateCredentialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && ($this->user()->role === 'admin' || $this->user()->can('servers.credentials') || $this->user()->can('servers.edit'));
    }

    public function rules(): array
    {
        return [
            'credential_type' => ['required', new Enum(ServerCredentialType::class)],
            'secret' => ['required', 'string', 'min:1'],
            'username' => ['nullable', 'string', 'max:50'],
            'name' => ['nullable', 'string', 'max:100'],
        ];
    }
}
