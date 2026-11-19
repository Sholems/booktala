<?php

namespace App\Policies;

use App\Models\ManagementAgreement;
use App\Models\User;
use App\Support\PropertyAccess;

class ManagementAgreementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('finance.manage');
    }

    public function view(User $user, ManagementAgreement $agreement): bool
    {
        return $user->can('finance.manage') && PropertyAccess::canAccess($user, $agreement->unit_id);
    }

    public function create(User $user): bool
    {
        return $user->can('finance.manage');
    }

    public function review(User $user, ManagementAgreement $agreement): bool
    {
        return $user->can('finance.manage')
            && PropertyAccess::canAccess($user, $agreement->unit_id)
            && $agreement->status === ManagementAgreement::STATUS_DRAFT;
    }

    public function retire(User $user, ManagementAgreement $agreement): bool
    {
        return $user->can('finance.manage')
            && PropertyAccess::canAccess($user, $agreement->unit_id)
            && $agreement->status === ManagementAgreement::STATUS_REVIEWED;
    }

    public function delete(User $user, ManagementAgreement $agreement): bool
    {
        return $user->can('finance.manage')
            && PropertyAccess::canAccess($user, $agreement->unit_id)
            && $agreement->status === ManagementAgreement::STATUS_DRAFT;
    }

    public function audit(User $user): bool
    {
        return $user->can('finance.manage');
    }
}
