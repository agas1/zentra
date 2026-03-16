<?php

namespace App\Domain\Api\Models;

use App\Domain\Workspace\Models\Workspace;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApiKey extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'workspace_id',
        'name',
        'key_hash',
        'key_prefix',
        'last_used_at',
        'expires_at',
    ];

    protected $hidden = [
        'key_hash',
    ];

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Generate a new API key for the workspace.
     * Returns the model and the raw key (shown once).
     */
    public static function generate(Workspace $workspace, string $name): array
    {
        $rawKey = 'flw_' . bin2hex(random_bytes(32));

        $apiKey = static::create([
            'workspace_id' => $workspace->id,
            'name' => $name,
            'key_hash' => hash('sha256', $rawKey),
            'key_prefix' => substr($rawKey, 0, 12),
        ]);

        return [
            'api_key' => $apiKey,
            'raw_key' => $rawKey,
        ];
    }

    /**
     * Find an API key by its raw value.
     */
    public static function findByRawKey(string $rawKey): ?static
    {
        return static::where('key_hash', hash('sha256', $rawKey))->first();
    }
}
