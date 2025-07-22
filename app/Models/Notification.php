<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;
    use HasUlids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'title',
        'message',
        'type',
        'state_id',
        'user_id',
        'sender_id',
        'status',
        'metadata',
        'scheduled_at',
    ];

    protected $casts = [
        'metadata' => 'json',
        'scheduled_at' => 'datetime',
    ];

    // Type constants
    const TYPE_ADMIN = 'admin';
    const TYPE_STATE = 'state';
    const TYPE_PERSONAL = 'personal';

    // Status constants
    const STATUS_DRAFT = 'draft';
    const STATUS_SENT = 'sent';
    const STATUS_FAILED = 'failed';

    // Relationships
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function recipients()
    {
        return $this->hasMany(NotificationRecipient::class);
    }

    public function stateInfo()
    {
        return $this->belongsTo(State::class, 'state_id', 'id');
    }

    // Scopes
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByState($query, $state_id)
    {
        return $query->where('state_id', $state_id);
    }

    public function scopeScheduled($query)
    {
        return $query->whereNotNull('scheduled_at')
                    ->where('scheduled_at', '<=', now())
                    ->where('status', self::STATUS_DRAFT);
    }

    // Helper methods
    public function getRecipientUsers()
    {
        switch ($this->type) {
            case self::TYPE_ADMIN:
                return User::canReceiveNotifications()->get();
            
            case self::TYPE_STATE:
                return User::canReceiveNotifications()
                          ->scopeByState($this->state_id)
                          ->get();
            
            case self::TYPE_PERSONAL:
                return User::canReceiveNotifications()
                          ->where('id', $this->user_id)
                          ->get();
            
            default:
                return collect();
        }
    }
}