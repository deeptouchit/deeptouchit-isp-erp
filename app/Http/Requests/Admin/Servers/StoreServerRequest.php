<?php

namespace App\Http\Requests\Admin\Servers;

use App\Enums\Infrastructure\ServerAuthType;
use App\Enums\Infrastructure\ServerEnvironment;
use App\Enums\Infrastructure\ServerType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreServerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && ($this->user()->role === 'admin' || $this->user()->can('servers.create'));
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'hostname' => ['required', 'string', 'max:255', 'regex:/^([a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])(\.([a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]{0,61}[a-zA-Z0-9]))*$/'],
            'ip_address' => ['required', 'ip'],
            'primary_ip' => ['nullable', 'ip'],
            'ipv6' => ['nullable', 'ip'],
            'server_group_id' => ['nullable', 'integer', 'exists:server_groups,id'],
            'server_type' => ['nullable', new Enum(ServerType::class)],
            'environment' => ['nullable', new Enum(ServerEnvironment::class)],
            'ssh_port' => ['nullable', 'integer', 'between:1,65535'],
            'ssh_user' => ['nullable', 'string', 'max:50', 'regex:/^[a-zA-Z0-9_\-]+$/'],
            'auth_type' => ['nullable', new Enum(ServerAuthType::class)],
            'ssh_password' => ['nullable', 'string', 'max:255'],
            'ssh_key' => ['nullable', 'string'],
            'ssh_host_key_policy' => ['nullable', 'string', 'in:strict,tofu,unverified'],
        ];
    }
}
