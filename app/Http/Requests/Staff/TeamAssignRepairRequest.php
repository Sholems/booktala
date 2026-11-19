<?php

namespace App\Http\Requests\Staff;

use App\Rules\WorksAtProperty;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for TeamController::assignRepair(). Access is checked by the route middleware and the controller.
 */
class TeamAssignRepairRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['assigned_to' => ['required', new WorksAtProperty($this->route('maintenanceReport')->property_id)]];
    }
}
