<?php

namespace AryaAzadeh\LaravelSeoAudit\Enums;

enum Severity: string
{
    case Info = 'info';
    case Warning = 'warning';
    case Error = 'error';
    case Critical = 'critical';

    public static function weight(self $severity): int
    {
        return match ($severity) {
            self::Info => 0,
            self::Warning => 2,
            self::Error => 10,
            self::Critical => 20,
        };
    }
}
