<?php

namespace AryaAzadeh\LaravelSeoAudit\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditIssue extends Model
{
    protected $table = 'seo_audit_issues';

    protected $guarded = [];

    protected $casts = [
        'context' => 'array',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(AuditRun::class, 'run_id');
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(AuditPage::class, 'page_id');
    }
}
