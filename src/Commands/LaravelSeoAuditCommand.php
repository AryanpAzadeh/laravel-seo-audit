<?php

namespace AryaAzadeh\LaravelSeoAudit\Commands;

use Illuminate\Console\Command;

class LaravelSeoAuditCommand extends Command
{
    public $signature = 'laravel-seo-audit';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
