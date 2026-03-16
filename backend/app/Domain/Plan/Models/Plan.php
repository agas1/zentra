<?php

namespace App\Domain\Plan\Models;

use App\Domain\Workspace\Models\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'max_members',
        'max_boards',
        'max_storage_mb',
        'max_labels',
        'max_attachment_size_mb',
        'features',
        'price_monthly',
        'stripe_price_monthly_id',
        'stripe_price_annual_id',
        'price_annual',
        'is_default',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'price_monthly' => 'decimal:2',
            'price_annual' => 'decimal:2',
            'features' => 'array',
        ];
    }

    public function workspaces(): HasMany
    {
        return $this->hasMany(Workspace::class);
    }

    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features ?? []);
    }

    public function isUnlimited(string $field): bool
    {
        return ($this->{$field} ?? 0) === -1;
    }

    public function getStripePriceId(string $cycle = 'monthly'): ?string
    {
        return $cycle === 'annual'
            ? $this->stripe_price_annual_id
            : $this->stripe_price_monthly_id;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }
}
