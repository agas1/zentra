<?php

namespace App\Domain\Billing\Models;

use App\Domain\Plan\Models\Plan;
use App\Domain\Workspace\Models\Workspace;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionHistory extends Model
{
    use HasUuids;

    protected $table = 'subscription_history';

    protected $fillable = [
        'workspace_id',
        'stripe_subscription_id',
        'stripe_invoice_id',
        'event',
        'plan_id',
        'amount',
        'currency',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
