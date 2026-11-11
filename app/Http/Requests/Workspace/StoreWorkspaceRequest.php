<?php

namespace App\Http\Requests\Workspace;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkspaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'partner_one_name' => ['required', 'string', 'max:100'],
            'partner_two_name' => ['required', 'string', 'max:100'],
            'wedding_date' => ['nullable', 'date', 'after_or_equal:today'],
            'timezone' => ['required', 'timezone'],
        ];
    }
}
