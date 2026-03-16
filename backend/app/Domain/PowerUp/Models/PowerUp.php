<?php

namespace App\Domain\PowerUp\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PowerUp extends Model
{
    use HasUuids;

    protected $fillable = [
        'slug',
        'name',
        'description',
        'icon',
        'category',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function workspaceInstalls(): HasMany
    {
        return $this->hasMany(WorkspacePowerUp::class, 'power_up_slug', 'slug');
    }
}
