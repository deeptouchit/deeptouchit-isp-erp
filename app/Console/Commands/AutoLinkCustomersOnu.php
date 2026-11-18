<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;

class AutoLinkCustomersOnu extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'isp:auto-link-onus {--tenant= : Optional tenant ID} {--force : Compatibility option; existing bindings are always preserved}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Link unambiguous customer and ONU matches while preserving existing bindings';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $tenants = Tenant::query()->when($this->option('tenant'), fn ($q, $id) => $q->whereKey($id))->get();
        foreach ($tenants as $tenant) {
            $count = app(\App\Services\Network\OnuLinkService::class)->linkTenant($tenant->id);
            $this->info("Tenant {$tenant->id}: {$count} new links.");
        }
        return self::SUCCESS;
    }
}
