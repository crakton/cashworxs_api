<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements JWTSubject
{
    use HasUlids;
    use HasFactory;
    use Notifiable;

    protected $table = 'users';

    protected $fillable = [
        'is_admin',
        'full_name',
        'phone_number',
        'password',
        'phone_verified_at',
        'verified',
        'email',
        'email_verified_at',
        'provider',
        'provider_id',
        'onesignal_user_id',
        'fcm_token',
        'state_id',
        'role',
        'push_notifications_enabled',
        'onesignal_registered_at',
        'notification_preferences',
        'can_receive_notifications'
    ];

    protected $hidden = [
        'password',
        'remember_token'
    ];

    protected $casts = [
        'phone_verified_at' => 'datetime',
        'email_verified_at' => 'datetime',
        'onesignal_registered_at' => 'datetime',
        'verified' => 'boolean',
        'is_admin' => 'boolean',
        'can_receive_notifications' => 'boolean',
        'push_notifications_enabled' => 'boolean',
        'notification_preferences' => 'json',
    ];

    // Role constants
    const ROLE_USER = 'user';
    const ROLE_ADMIN = 'admin';
    const ROLE_OPERATOR = 'operator';
    const ROLE_IRS_SPECIALIST = 'irs_specialist';

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    // Relationships
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    public function sentNotifications()
    {
        return $this->hasMany(Notification::class, 'sender_id');
    }

    public function personalNotifications()
    {
        return $this->hasMany(Notification::class, 'user_id');
    }

    public function notificationRecipients()
    {
        return $this->hasMany(NotificationRecipient::class);
    }

    public function stateInfo()
    {
        return $this->belongsTo(State::class, 'state_id', 'id');
    }

    // Enhanced role helper methods
    public function hasRole($role)
    {
        if (is_string($role)) {
            // Check both the role column and roles relationship
            return $this->role === $role || $this->roles->contains('name', $role);
        }
        return $this->roles->contains($role);
    }

    public function hasAnyRole($roles)
    {
        if (is_string($roles)) {
            $roles = [$roles];
        }

        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    public function hasAllRoles($roles)
    {
        if (is_string($roles)) {
            $roles = [$roles];
        }

        foreach ($roles as $role) {
            if (!$this->hasRole($role)) {
                return false;
            }
        }

        return true;
    }

    public function assignRole($role)
    {
        if (is_string($role)) {
            $roleModel = Role::where('name', $role)->first();
            if ($roleModel) {
                $this->roles()->syncWithoutDetaching([$roleModel->id]);
            }
        } elseif ($role instanceof Role) {
            $this->roles()->syncWithoutDetaching([$role->id]);
        }
    }

    public function removeRole($role)
    {
        if (is_string($role)) {
            $roleModel = Role::where('name', $role)->first();
            if ($roleModel) {
                $this->roles()->detach($roleModel->id);
            }
        } elseif ($role instanceof Role) {
            $this->roles()->detach($role->id);
        }
    }

    public function syncRoles($roles)
    {
        $roleIds = [];
        foreach ($roles as $role) {
            if (is_string($role)) {
                $roleModel = Role::where('name', $role)->first();
                if ($roleModel) {
                    $roleIds[] = $roleModel->id;
                }
            } elseif ($role instanceof Role) {
                $roleIds[] = $role->id;
            }
        }
        $this->roles()->sync($roleIds);
    }

    public function hasPermission($permission)
    {
        return $this->roles->flatMap->permissions->contains('name', $permission);
    }

    public function hasAnyPermission($permissions)
    {
        if (is_string($permissions)) {
            $permissions = [$permissions];
        }

        $userPermissions = $this->roles->flatMap->permissions->pluck('name')->toArray();
        
        foreach ($permissions as $permission) {
            if (in_array($permission, $userPermissions)) {
                return true;
            }
        }

        return false;
    }

    // Check if user is admin (using both role column and roles relationship)
    public function isAdmin()
    {
        return $this->is_admin || $this->hasRole('admin');
    }

    // Check if user is operator
    public function isOperator()
    {
        return $this->hasRole('operator');
    }

    // Check if user is IRS specialist
    public function isIrsSpecialist()
    {
        return $this->hasRole('irs_specialist');
    }

    public static function canReceiveNotifications()
    {
        // Replace with actual query logic, for example:
        return self::where('can_receive_notifications', true);
    }

    // Fixed scope to use state_id instead of state
    public function scopeByState($query, $stateId)
    {
        return $query->where('state_id', $stateId);
    }

    public function scopeCanReceiveNotifications($query)
    {
        return $query->where('push_notifications_enabled', true)
                    ->where(function($q) {
                        $q->whereNotNull('onesignal_user_id')
                          ->orWhereNotNull('fcm_token');
                    });
    }

    // Scope for filtering by roles
    public function scopeWithRole($query, $role)
    {
        return $query->whereHas('roles', function($q) use ($role) {
            $q->where('name', $role);
        })->orWhere('role', $role);
    }

    public function scopeWithAnyRole($query, $roles)
    {
        if (is_string($roles)) {
            $roles = [$roles];
        }

        return $query->where(function($q) use ($roles) {
            $q->whereHas('roles', function($subQ) use ($roles) {
                $subQ->whereIn('name', $roles);
            })->orWhereIn('role', $roles);
        });
    }
}