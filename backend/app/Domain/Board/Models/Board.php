<?php

namespace App\Domain\Board\Models;

use App\Domain\User\Models\User;
use App\Domain\Workspace\Models\Workspace;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Board extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'workspace_id',
        'name',
        'client_name',
        'description',
        'background_color',
        'background_image',
        'theme',
        'is_starred',
        'is_archived',
        'created_by_id',
    ];

    protected function casts(): array
    {
        return [
            'is_starred' => 'boolean',
            'is_archived' => 'boolean',
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

    public function lists(): HasMany
    {
        return $this->hasMany(BoardList::class)->orderBy('position');
    }

    public function cards(): HasMany
    {
        return $this->hasMany(Card::class);
    }

    public function labels(): HasMany
    {
        return $this->hasMany(Label::class);
    }

    public function customFields(): HasMany
    {
        return $this->hasMany(CustomField::class)->orderBy('position');
    }

    public function automations(): HasMany
    {
        return $this->hasMany(BoardAutomation::class)->orderBy('position');
    }
}
