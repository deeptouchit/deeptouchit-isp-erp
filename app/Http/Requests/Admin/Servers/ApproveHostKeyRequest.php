<?php

namespace App\Http\Requests\Admin\Servers;

use Illuminate\Foundation\Http\FormRequest;

class ApproveHostKeyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && ($this->user()->role === 'admin' || $this->user()->can('servers.host_key') || $this->user()->can('servers.verify'));
    }

    public function rules(): array
    {
        return [
            'fingerprint' => ['required', 'string', 'regex:/^SHA256:[A-Za-z0-9+\/=_:\-]+$/'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
