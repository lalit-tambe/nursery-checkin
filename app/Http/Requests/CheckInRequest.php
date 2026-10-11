<?php

namespace App\Http\Requests;

use App\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Login is already handled, as the brief says.
    }

    public function rules(): array
    {
        return [
            'child_id' => [
                'required',
                'integer',
                // The child must belong to THIS tenant. This rule does not use
                // the model filter, so the tenant check is written here by hand.
                Rule::exists('children', 'id')
                    ->where('tenant_id', app(TenantContext::class)->id()),
            ],
        ];
    }
}