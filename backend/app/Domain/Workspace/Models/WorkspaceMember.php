<?php

namespace App\Domain\Workspace\Models;

use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class WorkspaceMember extends Pivot
{
    use HasUuids;

    protected $table = 'workspace_members';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'workspace_id',
        'user_id',
        'role',
    ];

    protected $keyType = 'string';

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
