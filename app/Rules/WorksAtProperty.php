<?php

namespace App\Rules;

use App\Support\PropertyAccess;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * The chosen staff member must currently be assigned to the property's site,
 * so work is never handed to someone at another location.
 */
class WorksAtProperty implements ValidationRule
{
    public function __construct(private readonly ?int $propertyId)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $works = $this->propertyId !== null
            && PropertyAccess::staffAtProperty($this->propertyId)->whereKey($value)->exists();

        if (!$works) {
            $fail('That staff member is not assigned to this property\'s site. Assign them to the site first, or pick someone who works there.');
        }
    }
}
