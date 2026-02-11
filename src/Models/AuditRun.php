<?php

namespace AryaAzadeh\LaravelSeoAudit\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AuditRun extends Model
{
    protected $table = 'seo_audit_runs';

    protected $guarded = [];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'totals' => 'array',
    ];

    public function pages(): HasMany
    {
        return $this->hasMany(AuditPage::class, 'run_id');
    }

    public function issues(): HasMany
    {
        return $this->hasMany(AuditIssue::class, 'run_id');
    }
}
