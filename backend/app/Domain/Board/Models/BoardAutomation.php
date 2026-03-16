<?php

namespace App\Domain\Board\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoardAutomation extends Model
{
    use HasUuids;

    protected $fillable = [
        'board_id',
        'name',
        'trigger_type',
        'trigger_config',
        'action_type',
        'action_config',
        'is_active',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'trigger_config' => 'array',
            'action_config' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }
}
