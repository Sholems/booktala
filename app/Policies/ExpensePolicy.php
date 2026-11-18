<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;
use App\Support\PropertyAccess;

class ExpensePolicy
{
    public function view(User $user, Expense $expense): bool
    {
        return PropertyAccess::canAccess($user, $expense->property_id) && $user->can('finance.manage');
    }
}
