<?php

namespace App\Domain\Board\Models;

use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Card extends Model
{
    use HasUuids;

    protected $fillable = [
        'list_id',
        'board_id',
        'title',
        'description',
        'position',
        'cover_url',
        'due_date',
        'due_completed',
        'is_archived',
        'archive_reason',
        'unarchive_at',
        'unarchive_list_id',
        'created_by_id',
        'parent_card_id',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'float',
            'due_date' => 'datetime',
            'due_completed' => 'boolean',
            'is_archived' => 'boolean',
            'unarchive_at' => 'datetime',
        ];
    }

    public function list(): BelongsTo
    {
        return $this->belongsTo(BoardList::class, 'list_id');
    }

    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    public function unarchiveList(): BelongsTo
    {
        return $this->belongsTo(BoardList::class, 'unarchive_list_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function parentCard(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_card_id');
    }

    public function subCards(): HasMany
    {
        return $this->hasMany(self::class, 'parent_card_id')->orderBy('position');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'card_members');
    }

    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(Label::class, 'card_labels');
    }

    public function checklists(): HasMany
    {
        return $this->hasMany(Checklist::class)->orderBy('position');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(CardAttachment::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(CardComment::class)->orderByDesc('created_at');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(CardActivity::class)->orderByDesc('created_at');
    }

    public function customFieldValues(): HasMany
    {
        return $this->hasMany(CardCustomFieldValue::class);
    }

    public function isOverdue(): bool
    {
        return $this->due_date && !$this->due_completed && $this->due_date->isPast();
    }
}
