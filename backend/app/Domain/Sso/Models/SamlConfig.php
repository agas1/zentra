<?php

namespace App\Domain\Sso\Models;

use App\Domain\Workspace\Models\Workspace;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SamlConfig extends Model
{
    use HasUuids;

    protected $table = 'saml_configs';

    protected $fillable = [
        'workspace_id',
        'idp_entity_id',
        'idp_sso_url',
        'idp_slo_url',
        'idp_x509_cert',
        'domain',
        'sso_enforced',
        'is_active',
        'attribute_mapping',
    ];

    protected $casts = [
        'sso_enforced' => 'boolean',
        'is_active' => 'boolean',
        'attribute_mapping' => 'array',
    ];

    protected $hidden = [
        'idp_x509_cert',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function getAttributeMap(): array
    {
        return $this->attribute_mapping ?? [
            'email' => 'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/emailaddress',
            'first_name' => 'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/givenname',
            'last_name' => 'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/surname',
        ];
    }
}
