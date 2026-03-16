<?php

namespace App\Domain\Board\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BoardList extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'board_id',
        'name',
        'position',
        'is_archived',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'float',
            'is_archived' => 'boolean',
        ];
    }

    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    public function cards(): HasMany
    {
        return $this->hasMany(Card::class, 'list_id')
            ->where('is_archived', false)
            ->orderBy('position');
    }
}
