<?php

namespace AryaAzadeh\LaravelSeoAudit\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \AryaAzadeh\LaravelSeoAudit\LaravelSeoAudit
 */
class LaravelSeoAudit extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \AryaAzadeh\LaravelSeoAudit\LaravelSeoAudit::class;
    }
}
