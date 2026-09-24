<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TenantExpenseCategory extends Model
{
    use HasFactory;

    protected $table = 'tenant_expense_categories';

    protected $fillable = [
        'tenant_id',
        'name',
        'type',
        'code',
        'monthly_budget',
        'is_active',
        'icon',
        'description',
    ];

    protected $casts = [
        'monthly_budget' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(TenantExpenseTransaction::class, 'category_id');
    }

    /**
     * Pre-populate standard ISP Chart of Accounts for a new tenant
     */
    public static function seedDefaultCategories(int $tenantId): void
    {
        $defaults = [
            // Expense Categories
            ['name' => 'Upstream Bandwidth & IIG/NTTN', 'type' => 'EXPENSE', 'code' => 'EXP-01', 'monthly_budget' => 50000.00, 'icon' => 'fa-network-wired'],
            ['name' => 'Fiber Cable, Splicing & Pole Rent', 'type' => 'EXPENSE', 'code' => 'EXP-02', 'monthly_budget' => 15000.00, 'icon' => 'fa-bezier-curve'],
            ['name' => 'Office, POP & Tower Rent', 'type' => 'EXPENSE', 'code' => 'EXP-03', 'monthly_budget' => 20000.00, 'icon' => 'fa-building'],
            ['name' => 'Electricity & Generator Power', 'type' => 'EXPENSE', 'code' => 'EXP-04', 'monthly_budget' => 10000.00, 'icon' => 'fa-bolt'],
            ['name' => 'Staff Salary & Field Allowances', 'type' => 'EXPENSE', 'code' => 'EXP-05', 'monthly_budget' => 60000.00, 'icon' => 'fa-users-gear'],
            ['name' => 'Hardware, OLT, Switch & ONU Assets', 'type' => 'EXPENSE', 'code' => 'EXP-06', 'monthly_budget' => 25000.00, 'icon' => 'fa-server'],
            ['name' => 'Cloud SaaS, Radius & SMS Credits', 'type' => 'EXPENSE', 'code' => 'EXP-07', 'monthly_budget' => 5000.00, 'icon' => 'fa-cloud'],
            ['name' => 'Office Entertainment & Stationary', 'type' => 'EXPENSE', 'code' => 'EXP-08', 'monthly_budget' => 5000.00, 'icon' => 'fa-mug-hot'],
            ['name' => 'BTRC Licensing, Govt Tax & VAT', 'type' => 'EXPENSE', 'code' => 'EXP-09', 'monthly_budget' => 10000.00, 'icon' => 'fa-landmark'],
            ['name' => 'Marketing, Leaflets & Promotion', 'type' => 'EXPENSE', 'code' => 'EXP-10', 'monthly_budget' => 5000.00, 'icon' => 'fa-bullhorn'],
            ['name' => 'Vehicle Fuel & Emergency Conveyance', 'type' => 'EXPENSE', 'code' => 'EXP-11', 'monthly_budget' => 8000.00, 'icon' => 'fa-motorcycle'],
            ['name' => 'General Miscellaneous Expense', 'type' => 'EXPENSE', 'code' => 'EXP-12', 'monthly_budget' => 5000.00, 'icon' => 'fa-receipt'],

            // Non-Retail Direct Income Categories
            ['name' => 'New Connection & Installation Fees', 'type' => 'INCOME', 'code' => 'INC-01', 'monthly_budget' => 20000.00, 'icon' => 'fa-plug-circle-bolt'],
            ['name' => 'ONU & WiFi Router Device Sales', 'type' => 'INCOME', 'code' => 'INC-02', 'monthly_budget' => 30000.00, 'icon' => 'fa-microchip'],
            ['name' => 'Optical Fiber Line Laying Surcharge', 'type' => 'INCOME', 'code' => 'INC-03', 'monthly_budget' => 10000.00, 'icon' => 'fa-route'],
            ['name' => 'Hardware Repair & Service Charges', 'type' => 'INCOME', 'code' => 'INC-04', 'monthly_budget' => 5000.00, 'icon' => 'fa-screwdriver-wrench'],
            ['name' => 'Other Miscellaneous Income', 'type' => 'INCOME', 'code' => 'INC-05', 'monthly_budget' => 5000.00, 'icon' => 'fa-hand-holding-dollar'],
        ];

        foreach ($defaults as $d) {
            self::firstOrCreate(
                ['tenant_id' => $tenantId, 'name' => $d['name']],
                array_merge($d, ['tenant_id' => $tenantId, 'is_active' => true])
            );
        }
    }
}
