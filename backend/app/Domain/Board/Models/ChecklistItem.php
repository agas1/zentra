<?php

namespace App\Domain\Board\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChecklistItem extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'checklist_id',
        'title',
        'is_checked',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'is_checked' => 'boolean',
            'position' => 'float',
        ];
    }

    public function checklist(): BelongsTo
    {
        return $this->belongsTo(Checklist::class);
    }
}
