<?php

namespace App\Domain\Workspace\Models;

use App\Domain\Billing\Models\SubscriptionHistory;
use App\Domain\Board\Models\Board;
use App\Domain\Plan\Models\Plan;
use App\Domain\Sso\Models\SamlConfig;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Workspace extends Model
{
    use HasUuids, SoftDeletes;

    public const PERSONAS = ['agency', 'studio', 'freelancer', 'client'];

    protected $fillable = [
        'name',
        'slug',
        'plan_id',
        'owner_id',
        'is_active',
        'persona',
        'stripe_customer_id',
        'stripe_subscription_id',
        'subscription_status',
        'billing_cycle',
        'subscription_ends_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'subscription_ends_at' => 'datetime',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'workspace_members')
            ->withPivot('role');
    }

    public function boards(): HasMany
    {
        return $this->hasMany(Board::class);
    }

    public function isOwner(User $user): bool
    {
        return $this->owner_id === $user->id;
    }

    public function memberRole(User $user): ?string
    {
        // If members are already loaded, use the collection (avoids extra query)
        if ($this->relationLoaded('members')) {
            $member = $this->members->firstWhere('id', $user->id);
            return $member?->pivot?->role;
        }

        return $this->members()
            ->where('user_id', $user->id)
            ->select('users.id')
            ->first()
            ?->pivot?->role;
    }

    public function canAddMember(): bool
    {
        if ($this->plan->max_members < 0) return true;
        return $this->members()->count() < $this->plan->max_members;
    }

    public function samlConfig(): HasOne
    {
        return $this->hasOne(SamlConfig::class);
    }

    public function subscriptionHistory(): HasMany
    {
        return $this->hasMany(SubscriptionHistory::class);
    }

    public function hasActiveSubscription(): bool
    {
        return in_array($this->subscription_status, ['active', 'trialing']);
    }

    public function isOnFreePlan(): bool
    {
        return $this->plan?->slug === 'free';
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
