<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for SiteController::attachProperty(). Access is checked by the route middleware.
 */
class SiteAttachPropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['property_id' => 'required|exists:properties,id'];
    }
}
