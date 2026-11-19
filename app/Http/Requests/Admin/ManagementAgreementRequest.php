<?php

namespace App\Http\Requests\Admin;

use App\Support\PropertyAccess;
use Illuminate\Foundation\Http\FormRequest;

class ManagementAgreementRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->user()?->can('finance.manage')) {
            return false;
        }

        $unitId = $this->integer('unit_id');
        if ($unitId) {
            PropertyAccess::authorize($this->user(), $unitId);
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'unit_id' => ['required', 'integer', 'exists:properties,id'],
            'owner_id' => ['required', 'integer', 'exists:owners,id'],
            'commission_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'deduct_expenses' => ['nullable', 'boolean'],
            'caution_forfeiture_recipient' => ['nullable', 'in:company,owner'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
