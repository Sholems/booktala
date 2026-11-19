<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for SettingsController::update(). Access is checked by the route middleware.
 */
class SettingsUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vat_rate' => 'nullable|numeric|min:0|max:100',
            'email' => 'nullable|email|max:255',
            'mail_from_address' => 'nullable|email|max:255',
            'mail_port' => 'nullable|integer|min:1|max:65535',
            // No SVG: it can carry scripts. Favicons may be .ico.
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
            'favicon' => 'nullable|file|mimes:png,ico|max:512',
        ];
    }
}
