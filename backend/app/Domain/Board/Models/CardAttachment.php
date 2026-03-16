<?php

namespace App\Domain\Board\Models;

use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class CardAttachment extends Model
{
    use HasUuids;

    protected $fillable = [
        'card_id',
        'filename',
        'path',
        'mime_type',
        'size',
        'is_cover',
        'uploaded_by_id',
        'source',
        'external_url',
        'external_id',
    ];

    protected $appends = ['url'];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'is_cover' => 'boolean',
        ];
    }

    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_id');
    }

    public function getUrlAttribute(): string
    {
        if ($this->source === 'google_drive' && $this->external_url) {
            return $this->external_url;
        }

        return '/storage/' . $this->path;
    }

    public function isExternal(): bool
    {
        return $this->source !== 'local';
    }
}
