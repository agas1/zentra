<?php

namespace App\Domain\Board\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomField extends Model
{
    use HasUuids;

    protected $fillable = [
        'board_id',
        'name',
        'type',
        'options',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
        ];
    }

    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    public function values(): HasMany
    {
        return $this->hasMany(CardCustomFieldValue::class);
    }
}
