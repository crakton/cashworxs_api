<?php

namespace App\Policies;

use App\Models\IdConfig;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class IdConfigPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user, Organization $organization)
    {
        return $user->hasAnyRole(['admin', 'operator']) && 
               $user->can('manage_organizations');
    }

    public function view(User $user, IdConfig $idConfig, Organization $organization)
    {
        return $user->hasAnyRole(['admin', 'operator']) && 
               $idConfig->organization_id === $organization->id;
    }

    public function create(User $user, Organization $organization)
    {
        return $user->hasRole('admin') && 
               $user->can('manage_organizations');
    }

    public function update(User $user, IdConfig $idConfig, Organization $organization)
    {
        return $user->hasRole('admin') && 
               $idConfig->organization_id === $organization->id;
    }

    public function delete(User $user, IdConfig $idConfig, Organization $organization)
    {
        return $user->hasRole('admin') && 
               $idConfig->organization_id === $organization->id;
    }
}