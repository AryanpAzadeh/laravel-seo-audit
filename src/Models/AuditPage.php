<?php

namespace AryaAzadeh\LaravelSeoAudit\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AuditPage extends Model
{
    protected $table = 'seo_audit_pages';

    protected $guarded = [];

    public function run(): BelongsTo
    {
        return $this->belongsTo(AuditRun::class, 'run_id');
    }

    public function issues(): HasMany
    {
        return $this->hasMany(AuditIssue::class, 'page_id');
    }
}
