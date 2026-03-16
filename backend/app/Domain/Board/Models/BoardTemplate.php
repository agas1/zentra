<?php

namespace App\Domain\Board\Models;

use App\Domain\User\Models\User;
use App\Domain\Workspace\Models\Workspace;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoardTemplate extends Model
{
    use HasUuids;

    protected $fillable = [
        'workspace_id',
        'name',
        'description',
        'lists',
        'labels',
        'custom_fields',
        'automations',
        'is_default',
        'created_by_id',
    ];

    protected function casts(): array
    {
        return [
            'lists' => 'array',
            'labels' => 'array',
            'custom_fields' => 'array',
            'automations' => 'array',
            'is_default' => 'boolean',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}
