<?php

namespace App\Http\Requests\Admin\Servers;

use App\Enums\Infrastructure\Servers\RemoteOperation;
use Illuminate\Foundation\Http\FormRequest;

class ManageServerServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && ($this->user()->role === 'admin' || $this->user()->can('servers.services'));
    }

    public function rules(): array
    {
        return [
            'service' => ['required', 'string', 'in:' . implode(',', RemoteOperation::ALLOWED_SERVICES)],
            'action' => ['required', 'string', 'in:restart,reload,start,stop,status'],
        ];
    }
}
