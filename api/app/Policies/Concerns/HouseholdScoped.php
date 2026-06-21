<?php

namespace App\Policies\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

trait HouseholdScoped
{
    public function viewAny(User $user): bool
    {
        return $user->household_id !== null;
    }

    public function view(User $user, Model $model): bool
    {
        return $this->sameHousehold($user, $model);
    }

    public function create(User $user): bool
    {
        return $user->household_id !== null;
    }

    public function update(User $user, Model $model): bool
    {
        return $this->sameHousehold($user, $model);
    }

    public function delete(User $user, Model $model): bool
    {
        return $this->sameHousehold($user, $model);
    }

    public function restore(User $user, Model $model): bool
    {
        return $this->sameHousehold($user, $model);
    }

    public function forceDelete(User $user, Model $model): bool
    {
        return $this->sameHousehold($user, $model);
    }

    protected function sameHousehold(User $user, Model $model): bool
    {
        return $user->household_id !== null && $user->household_id === $model->household_id;
    }
}
