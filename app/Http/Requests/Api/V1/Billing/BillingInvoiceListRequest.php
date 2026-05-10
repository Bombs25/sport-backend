<?php

namespace App\Http\Requests\Api\V1\Billing;

use Illuminate\Foundation\Http\FormRequest;

class BillingInvoiceListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'month' => ['sometimes', 'integer', 'between:1,12', 'required_with:year'],
            'year' => ['sometimes', 'integer', 'between:2000,2100', 'required_with:month'],
            'limit' => ['sometimes', 'integer', 'between:1,100'],
        ];
    }
}
