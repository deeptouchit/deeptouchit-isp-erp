<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\TenantCoverageZone;
use App\Models\TenantCustomer;
use App\Models\TenantReseller;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TenantCoverageZoneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Get primary active tenant
        $tenant = Tenant::first();
        $tenantId = $tenant ? $tenant->id : 1;

        // 2. Clear existing zone references in customers and wipe coverage zones table
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        if (Schema::hasColumn('tenant_customers', 'zone_id')) {
            DB::table('tenant_customers')->where('tenant_id', $tenantId)->update(['zone_id' => null]);
        }

        DB::table('tenant_coverage_zones')->where('tenant_id', $tenantId)->delete();
        // Reset auto increment if empty
        if (DB::table('tenant_coverage_zones')->count() === 0) {
            DB::statement('ALTER TABLE tenant_coverage_zones AUTO_INCREMENT = 1;');
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 3. Resolve Reseller IDs
        // Reseller 1 (e.g. RES-001 or id 1) and Reseller 2 (e.g. RES-002 or id 2)
        $reseller1 = TenantReseller::where('tenant_id', $tenantId)
            ->where(function($q) {
                $q->where('id', 1)->orWhere('code', 'RES-001');
            })->first();

        $reseller2 = TenantReseller::where('tenant_id', $tenantId)
            ->where(function($q) {
                $q->where('id', 2)->orWhere('code', 'RES-002');
            })->first();

        $reseller1Id = $reseller1 ? $reseller1->id : 1;
        $reseller2Id = $reseller2 ? $reseller2->id : 2;

        // 4. Exact Defined Coverage Zones
        $zones = [
            [
                'id' => 1,
                'name' => 'গড়েরবাড়ী',
                'code' => 'ZN-001',
                'reseller_id' => null,
                'city_upazila' => 'গাবতলি, বগুড়া',
                'sub_areas' => 'পশ্চিমপাড়া, পূর্বপাড়া, গুচ্ছগ্রাম',
                'is_active' => true,
            ],
            [
                'id' => 2,
                'name' => 'লালখাঁপাড়া',
                'code' => 'ZN-002',
                'reseller_id' => null,
                'city_upazila' => 'গাবতলি, বগুড়া',
                'sub_areas' => 'দহপাড়া, দক্ষিণপাড়া, মধ্যেপাড়া',
                'is_active' => true,
            ],
            [
                'id' => 3,
                'name' => 'ভান্ডাড়া',
                'code' => 'ZN-003',
                'reseller_id' => null,
                'city_upazila' => 'গাবতলি, বগুড়া',
                'sub_areas' => 'চারমাথা, পূর্বপাড়া, পশ্চিমপাড়া, মধ্যেপাড়া',
                'is_active' => true,
            ],
            [
                'id' => 4,
                'name' => 'পনিরপাড়া',
                'code' => 'ZN-004',
                'reseller_id' => null,
                'city_upazila' => 'গাবতলি, বগুড়া',
                'sub_areas' => 'পাসপাড়া',
                'is_active' => true,
            ],
            [
                'id' => 5,
                'name' => 'ভান্ডাড়া পনিরপাড়া',
                'code' => 'ZN-005',
                'reseller_id' => null,
                'city_upazila' => 'গাবতলি, বগুড়া',
                'sub_areas' => null,
                'is_active' => true,
            ],
            [
                'id' => 6,
                'name' => 'নিজ দূর্গাহাটা',
                'code' => 'ZN-006',
                'reseller_id' => null,
                'city_upazila' => 'গাবতলি, বগুড়া',
                'sub_areas' => 'বুড়িতলা, উত্তরপাড়া, দক্ষিণপাড়া, ফকিরপাড়া, আগপাড়া, ঘোষপাড়া, বাজার, সিএনজি স্ট্যান্ড',
                'is_active' => true,
            ],
            [
                'id' => 7,
                'name' => 'মাঠিয়ানচড়া',
                'code' => 'ZN-007',
                'reseller_id' => null,
                'city_upazila' => 'গাবতলি, বগুড়া',
                'sub_areas' => null,
                'is_active' => true,
            ],
            [
                'id' => 8,
                'name' => 'দরগাতলি',
                'code' => 'ZN-008',
                'reseller_id' => null,
                'city_upazila' => 'গাবতলি, বগুড়া',
                'sub_areas' => null,
                'is_active' => true,
            ],
            [
                'id' => 9,
                'name' => 'মাঝিপাড়া',
                'code' => 'ZN-009',
                'reseller_id' => null,
                'city_upazila' => 'গাবতলি, বগুড়া',
                'sub_areas' => null,
                'is_active' => true,
            ],
            [
                'id' => 10,
                'name' => 'নয়াপাড়া',
                'code' => 'ZN-010',
                'reseller_id' => null,
                'city_upazila' => 'গাবতলি, বগুড়া',
                'sub_areas' => null,
                'is_active' => true,
            ],
            [
                'id' => 11,
                'name' => 'পাঁচকাউনিয়া',
                'code' => 'ZN-011',
                'reseller_id' => null,
                'city_upazila' => 'গাবতলি, বগুড়া',
                'sub_areas' => 'পূর্বপাড়া, পশ্চিমপাড়া',
                'is_active' => true,
            ],
            [
                'id' => 12,
                'name' => 'শিলদহবাড়ী',
                'code' => 'ZN-012',
                'reseller_id' => null,
                'city_upazila' => 'গাবতলি, বগুড়া',
                'sub_areas' => 'পশ্চিমপাড়া, উত্তরপাড়া, দক্ষিণপাড়া, মধ্যোপাড়া',
                'is_active' => true,
            ],
            [
                'id' => 13,
                'name' => 'ঠাকুরবের',
                'code' => 'ZN-013',
                'reseller_id' => null,
                'city_upazila' => 'গাবতলি, বগুড়া',
                'sub_areas' => null,
                'is_active' => true,
            ],
            [
                'id' => 14,
                'name' => 'বটিয়াভাঙ্গা',
                'code' => 'ZN-014',
                'reseller_id' => $reseller1Id, // Reseller 1
                'city_upazila' => 'গাবতলি, বগুড়া',
                'sub_areas' => 'কৃতনিয়া, উত্তরপাড়া, চেয়ারম্যান পাড়া, মুন্নাপাড়া, সরকার পাড়া, পাসপাড়া, ইলেন বাজার, চারমাথা, ঘোনাপাড়া, শাজাহান মেম্বার পাড়া, ঠাকুরবেড়',
                'is_active' => true,
            ],
            [
                'id' => 15,
                'name' => 'বটিয়াভাঙ্গা দক্ষিণ',
                'code' => 'ZN-015',
                'reseller_id' => null,
                'city_upazila' => 'গাবতলি, বগুড়া',
                'sub_areas' => null,
                'is_active' => true,
            ],
            [
                'id' => 16,
                'name' => 'বেতুয়ারকান্দি',
                'code' => 'ZN-016',
                'reseller_id' => $reseller2Id, // Reseller 2
                'city_upazila' => 'গাবতলি, বগুড়া',
                'sub_areas' => null,
                'is_active' => true,
            ],
            [
                'id' => 17,
                'name' => 'ফ্রি লাইন',
                'code' => 'ZN-017',
                'reseller_id' => null,
                'city_upazila' => 'গাবতলি, বগুড়া',
                'sub_areas' => null,
                'is_active' => true,
            ],
            [
                'id' => 22,
                'name' => 'সরকারি প্রতিষ্ঠান',
                'code' => 'ZN-022',
                'reseller_id' => null,
                'city_upazila' => 'গাবতলি, বগুড়া',
                'sub_areas' => 'বেতুয়ারকান্দি সঃ প্রাঃ বিঃ',
                'is_active' => true,
            ],
        ];

        // 5. Insert records ensuring exact ID numbers
        $hasDescriptionCol = Schema::hasColumn('tenant_coverage_zones', 'description');
        $hasSubAreasCol = Schema::hasColumn('tenant_coverage_zones', 'sub_areas');

        foreach ($zones as $zoneData) {
            $insertData = [
                'id' => $zoneData['id'],
                'tenant_id' => $tenantId,
                'reseller_id' => $zoneData['reseller_id'],
                'code' => $zoneData['code'],
                'name' => $zoneData['name'],
                'city_upazila' => $zoneData['city_upazila'],
                'is_active' => $zoneData['is_active'],
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if ($hasDescriptionCol) {
                $insertData['description'] = $zoneData['sub_areas'] ?? null;
            }
            if ($hasSubAreasCol) {
                $insertData['sub_areas'] = $zoneData['sub_areas'] ?? null;
            }

            DB::table('tenant_coverage_zones')->insert($insertData);
        }

        // 6. Automatically link customers back to zones if their customer address/zone matches name
        try {
            $insertedZones = DB::table('tenant_coverage_zones')->where('tenant_id', $tenantId)->get();
            foreach ($insertedZones as $z) {
                DB::table('tenant_customers')
                    ->where('tenant_id', $tenantId)
                    ->where(function($q) use ($z) {
                        $q->where('zone', $z->name)
                          ->orWhere('zone', 'like', "%{$z->name}%");
                    })
                    ->update(['zone_id' => $z->id]);
            }
        } catch (\Throwable $e) {
            // Safe fallback
        }
    }
}
