<?php

namespace App\Http\Requests\Staff;

use App\Support\PropertyAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Validation for MaintenanceController::store(). Access is checked by the route middleware.
 */
class MaintenanceStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Staff report problems at their own sites only.
            'property_id' => ['required', Rule::in(PropertyAccess::visiblePropertyIds(Auth::user())->pluck('id'))],
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
            'priority' => 'required|in:low,medium,high,urgent,emergency',
        ];
    }
}
