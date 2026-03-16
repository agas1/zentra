<?php

namespace App\Domain\PowerUp\Models;

use App\Domain\User\Models\User;
use App\Domain\Workspace\Models\Workspace;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkspacePowerUp extends Model
{
    use HasUuids;

    protected $fillable = [
        'workspace_id',
        'power_up_slug',
        'is_enabled',
        'config',
        'connected_by_id',
        'connected_at',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'config' => 'array',
            'connected_at' => 'datetime',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function powerUp(): BelongsTo
    {
        return $this->belongsTo(PowerUp::class, 'power_up_slug', 'slug');
    }

    public function connectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'connected_by_id');
    }

    public function getConfigValue(string $key, mixed $default = null): mixed
    {
        return data_get($this->config, $key, $default);
    }

    public function setConfigValue(string $key, mixed $value): void
    {
        $config = $this->config ?? [];
        data_set($config, $key, $value);
        $this->update(['config' => $config]);
    }
}
