<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Role;
use App\Models\User;

class MigrateVillamarToJupiterCommand extends Command
{
    protected $signature = 'villamar:migrate {--skip-truncate : Do not truncate tables before migrating}';
    protected $description = 'Clear Jupiter database and migrate all data from villamar.sql with complete relationships, client/supplier invoices, designations, and settlements';

    protected array $fishTypesMap = [];
    protected array $packageTypesMap = [];
    protected array $guanoTypesMap = [
        1 => 'Guano Entier',
        2 => 'Guano HGT',
        3 => 'Guano HG',
        4 => 'Guano Filet',
    ];
    protected array $trucksMap = [];

    public function handle()
    {
        $this->info("======================================================================");
        $this->info("   VILLAMAR -> JUPITER FULL DATA MIGRATION & RELATIONSHIP SYNC       ");
        $this->info("======================================================================");

        $startTime = microtime(true);

        // Verify villamar database is accessible
        try {
            $villamarTableCount = DB::connection('mysql')->select("SELECT count(*) as cnt FROM information_schema.tables WHERE table_schema = 'villamar'")[0]->cnt ?? 0;
            if ($villamarTableCount === 0) {
                $this->error("Database 'villamar' is empty or not found! Please import villamar.sql first.");
                return Command::FAILURE;
            }
            $this->info("Found source database 'villamar' with {$villamarTableCount} tables.");
        } catch (\Throwable $e) {
            $this->error("Failed to connect to MySQL: " . $e->getMessage());
            return Command::FAILURE;
        }

        // Disable foreign key checks during migration
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        if (!$this->option('skip-truncate')) {
            $this->stepClearJupiterData();
        }

        $this->stepSeedBaseSystem();
        $this->stepMigratePartnersAndMasterData();
        $this->loadLookupMaps();
        $this->stepMigrateWarehouses();
        $this->stepMigrateWorkforce();
        $this->stepMigrateChargesAndReceptions();
        $this->stepMigrateProductions();
        $this->stepMigrateShippings();
        $this->stepMigrateInvoicesAndSettlements();
        $this->stepSyncWarehouseBalances();

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $duration = round(microtime(true) - $startTime, 2);
        $this->info("======================================================================");
        $this->info("   MIGRATION COMPLETED SUCCESSFULLY IN {$duration}s !                 ");
        $this->info("======================================================================");

        return Command::SUCCESS;
    }

    protected function loadLookupMaps()
    {
        $this->fishTypesMap = DB::table('fish_types')->pluck('name_in_invoice', 'id')->toArray();
        $this->packageTypesMap = DB::table('package_types')->pluck('name_in_invoice', 'id')->toArray();
        $this->trucksMap = DB::table('trucks')->pluck('id', 'registration_plate')->toArray();
        $this->info("Loaded lookup maps: " . count($this->fishTypesMap) . " fish types, " . count($this->packageTypesMap) . " package types, " . count($this->trucksMap) . " trucks.");
    }

    protected function resolveTruckIds($matriculeRaw): array
    {
        $ids = [];
        if (empty($matriculeRaw)) return $ids;

        $plates = [];
        if (is_string($matriculeRaw)) {
            $trimmed = trim($matriculeRaw);
            if (str_starts_with($trimmed, '[')) {
                $decoded = json_decode($trimmed, true);
                if (is_array($decoded)) {
                    foreach ($decoded as $p) {
                        if (!empty($p) && is_string($p)) {
                            $subPlates = explode(',', $p);
                            foreach ($subPlates as $sp) $plates[] = $sp;
                        }
                    }
                }
            } else {
                $plates = explode(',', $trimmed);
            }
        } elseif (is_array($matriculeRaw)) {
            $plates = $matriculeRaw;
        }

        foreach ($plates as $pl) {
            $pl = trim((string)$pl);
            if (empty($pl) || $pl === '********' || $pl === '—————' || $pl === '-----' || $pl === 'null') continue;

            if (!isset($this->trucksMap[$pl])) {
                $newId = DB::table('trucks')->insertGetId([
                    'registration_plate' => $pl,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->trucksMap[$pl] = $newId;
            }
            $ids[] = $this->trucksMap[$pl];
        }

        return array_values(array_unique($ids));
    }

    protected function parseTrackingBons($singleBon, $jsonBons): array
    {
        $list = [];
        if (!empty($singleBon) && is_string($singleBon) && $singleBon !== '********' && $singleBon !== 'null') {
            $list[] = trim($singleBon);
        }

        if (!empty($jsonBons)) {
            $arr = is_array($jsonBons) ? $jsonBons : json_decode($jsonBons, true);
            if (is_array($arr)) {
                foreach ($arr as $b) {
                    if (!empty($b) && is_string($b) && $b !== '********' && $b !== 'null') {
                        $list[] = trim($b);
                    }
                }
            }
        }

        return array_values(array_unique(array_filter($list)));
    }

    protected function stepClearJupiterData()
    {
        $this->warn("\n[1/9] Clearing all existing Jupiter database data...");

        $tablesToTruncate = [
            'detail_charges', 'charges', 'charge_truck',
            'detail_productions', 'productions',
            'detail_shippings', 'shippings', 'shipping_truck',
            'detail_warehouse_transfers', 'warehouse_transfers', 'warehouse_transfer_truck',
            'detail_inventories', 'inventories',
            'warehouse_balances', 'warehouse_balance_operations', 'warehouse_stock_resets',
            'client_payments', 'client_invoices', 'client_fiscal_year_closing_lines', 'client_fiscal_year_closings',
            'supplier_payments', 'supplier_invoices', 'fiscal_year_closing_lines', 'fiscal_year_closings',
            'storage_invoice_lines', 'storage_invoices',
            'fortnight_activities', 'workforce_fortnight_payments', 'workforce_movements', 'team_workforce', 'teams', 'workforces', 'section_corporals', 'sections', 'corporals',
            'fish_type_waps', 'fish_types', 'fish_process_types', 'fish_families',
            'package_type_stocks', 'package_type_components', 'package_secondary_configs', 'package_types',
            'guano_types', 'fisheries', 'origins', 'ships', 'trucks', 'suppliers', 'clients',
            'warehouses', 'warehouse_categories', 'warehouse_transfer_categories', 'detail_inventory_categories',
            'charge_categories', 'detail_charge_categories',
            'production_categories', 'detail_production_categories',
            'shipping_categories', 'detail_shipping_categories',
            'quality_control_items', 'quality_controls', 'record_issues',
            'notifications', 'audit_logs', 'chat_messages', 'ai_query_messages',
            'formulas', 'users', 'roles'
        ];

        foreach ($tablesToTruncate as $tbl) {
            DB::statement("TRUNCATE TABLE `{$tbl}`");
        }

        $this->info("Cleared " . count($tablesToTruncate) . " tables cleanly.");
    }

    protected function stepSeedBaseSystem()
    {
        $this->warn("\n[2/9] Seeding Base Roles, Admin User, Categories, and Base Types...");

        // 1. Roles & Admin User
        DB::table('roles')->insert([
            'id' => 1,
            'name' => 'admin',
            'is_super_admin' => 1,
            'permissions' => json_encode(Role::allPermissionKeys()),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->insert([
            'id' => 1,
            'name' => 'Test User',
            'email' => 'test@test.com',
            'password' => Hash::make('password'),
            'role_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Copy other users from villamar if any
        $oldUsers = DB::select("SELECT * FROM villamar.users WHERE email != 'test@test.com'");
        foreach ($oldUsers as $u) {
            $fullName = trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? '')) ?: 'User ' . $u->id;
            DB::table('users')->insertOrIgnore([
                'id' => $u->id,
                'name' => $fullName,
                'email' => $u->email,
                'password' => $u->password ?? Hash::make('password'),
                'role_id' => 1,
                'created_at' => $u->created_at ?? now(),
                'updated_at' => $u->updated_at ?? now(),
            ]);
        }

        // 2. Formulas
        DB::table('formulas')->insert([
            'id' => 1,
            'name_fr' => 'Rendement Guano',
            'name_en' => 'Rendement Guano',
            'name_es' => 'Rendement Guano',
            'description' => 'Rendement for Guano',
            'enabled' => 1,
            'type' => 'charge',
            'created_by' => 1,
            'expressions' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. Guano Types
        $guanoTypes = [
            ['id' => 1, 'name_fr' => 'Guano Entier', 'name_en' => 'Guano Entier', 'name_es' => 'Guano Entier', 'name_in_invoice' => 'Guano Entier', 'scientific_name' => 'Guano Entier', 'description' => 'Guano Entier'],
            ['id' => 2, 'name_fr' => 'Guano HGT', 'name_en' => 'Guano HGT', 'name_es' => 'Guano HGT', 'name_in_invoice' => 'Guano HGT', 'scientific_name' => 'Guano HGT', 'description' => 'Guano HGT'],
            ['id' => 3, 'name_fr' => 'Guano HG', 'name_en' => 'Guano HG', 'name_es' => 'Guano HG', 'name_in_invoice' => 'Guano HG', 'scientific_name' => 'Guano HG', 'description' => 'Guano HG'],
            ['id' => 4, 'name_fr' => 'Guano Filet', 'name_en' => 'Guano Filet', 'name_es' => 'Guano Filet', 'name_in_invoice' => 'Guano Filet', 'scientific_name' => 'Guano Filet', 'description' => 'Guano Filet'],
        ];
        foreach ($guanoTypes as $gt) {
            $gt['created_at'] = now();
            $gt['updated_at'] = now();
            DB::table('guano_types')->insertOrIgnore($gt);
        }

        // 4. Warehouse Categories
        $whCats = [
            ['id' => 1, 'name' => 'Entrepôt Frais'],
            ['id' => 2, 'name' => 'Entrepôt Congelé'],
            ['id' => 3, 'name' => 'Entrepôt Guano'],
            ['id' => 4, 'name' => 'Entrepôt Emballage'],
        ];
        foreach ($whCats as $wc) {
            $wc['created_at'] = now();
            $wc['updated_at'] = now();
            DB::table('warehouse_categories')->insert($wc);
        }

        // 5. Charge Categories & Detail Charge Categories
        $chargeCats = [
            ['id' => 1, 'name' => 'Du frais à la production', 'name_fr' => 'Du frais à la production', 'name_en' => 'Fresh To Production', 'name_es' => 'Du frais à la production', 'is_production' => 1],
            ['id' => 2, 'name' => 'Frais Revendue', 'name_fr' => 'Frais Revendue', 'name_en' => 'Fresh To Sell', 'name_es' => 'Frais Revendue', 'is_production' => 0],
            ['id' => 3, 'name' => 'Congele', 'name_fr' => 'Congele', 'name_en' => 'Frozen', 'name_es' => 'Congele', 'is_production' => 0],
            ['id' => 4, 'name' => 'Guano Revendue', 'name_fr' => 'Guano Revendue', 'name_en' => 'Guano To Sell', 'name_es' => 'Guano Revendue', 'is_production' => 0],
            ['id' => 5, 'name' => 'Carton', 'name_fr' => 'Carton', 'name_en' => 'Carton', 'name_es' => 'Carton', 'is_production' => 0],
            ['id' => 6, 'name' => 'RSW', 'name_fr' => 'RSW', 'name_en' => 'RSW', 'name_es' => 'RSW', 'is_production' => 0],
            ['id' => 7, 'name' => 'Glace', 'name_fr' => 'Glace', 'name_en' => 'Ice', 'name_es' => 'Glace', 'is_production' => 0],
            ['id' => 8, 'name' => 'Transport', 'name_fr' => 'Transport', 'name_en' => 'Transport', 'name_es' => 'Transport', 'is_production' => 0],
            ['id' => 9, 'name' => 'Maintenance', 'name_fr' => 'Maintenance', 'name_en' => 'Maintenance', 'name_es' => 'Maintenance', 'is_production' => 0],
            ['id' => 10, 'name' => 'Autres Charges', 'name_fr' => 'Autres Charges', 'name_en' => 'Other Charges', 'name_es' => 'Autres Charges', 'is_production' => 0],
        ];
        foreach ($chargeCats as $cc) {
            $cc['enable_reception_tracking_number'] = 1;
            $cc['enable_tracking_onp_numbers'] = 1;
            $cc['enable_fishery_id'] = 1;
            $cc['enable_ship_id'] = 1;
            $cc['enable_origin_id'] = 1;
            $cc['enable_truck_id'] = 1;
            $cc['enable_suppliers'] = 1;
            $cc['enable_invoices'] = 1;
            $cc['created_at'] = now();
            $cc['updated_at'] = now();
            DB::table('charge_categories')->insert($cc);
        }

        $detailChargeCats = [
            ['id' => 1, 'charge_category_id' => 1, 'name_fr' => 'Frais Poisson', 'name_en' => 'Fresh Fish', 'name_es' => 'Frais Poisson', 'article_type' => 'FishType', 'fish_type_filter' => 'fresh'],
            ['id' => 2, 'charge_category_id' => 2, 'name_fr' => 'Frais Poisson', 'name_en' => 'Fresh Fish', 'name_es' => 'Frais Poisson', 'article_type' => 'FishType', 'fish_type_filter' => 'fresh'],
            ['id' => 3, 'charge_category_id' => 3, 'name_fr' => 'Congele Poisson', 'name_en' => 'Frozen Fish', 'name_es' => 'Congele Poisson', 'article_type' => 'FishType', 'fish_type_filter' => 'frozen'],
            ['id' => 4, 'charge_category_id' => 4, 'name_fr' => 'Guano', 'name_en' => 'Guano', 'name_es' => 'Guano', 'article_type' => 'GuanoType', 'fish_type_filter' => 'all'],
            ['id' => 5, 'charge_category_id' => 5, 'name_fr' => 'Emballage', 'name_en' => 'Package', 'name_es' => 'Emballage', 'article_type' => 'PackageType', 'fish_type_filter' => 'all'],
            ['id' => 6, 'charge_category_id' => 6, 'name_fr' => 'RSW', 'name_en' => 'RSW', 'name_es' => 'RSW', 'article_type' => 'FishType', 'fish_type_filter' => 'all'],
            ['id' => 7, 'charge_category_id' => 7, 'name_fr' => 'Glace', 'name_en' => 'Ice', 'name_es' => 'Glace', 'article_type' => 'FishType', 'fish_type_filter' => 'all'],
            ['id' => 8, 'charge_category_id' => 8, 'name_fr' => 'Transport', 'name_en' => 'Transport', 'name_es' => 'Transport', 'article_type' => 'FishType', 'fish_type_filter' => 'all'],
            ['id' => 9, 'charge_category_id' => 9, 'name_fr' => 'Maintenance', 'name_en' => 'Maintenance', 'name_es' => 'Maintenance', 'article_type' => 'FishType', 'fish_type_filter' => 'all'],
            ['id' => 10, 'charge_category_id' => 10, 'name_fr' => 'Autres Charges', 'name_en' => 'Other Charges', 'name_es' => 'Autres Charges', 'article_type' => 'FishType', 'fish_type_filter' => 'all'],
        ];
        foreach ($detailChargeCats as $dcc) {
            $dcc['allow_weight'] = 1;
            $dcc['allow_unit_price'] = 1;
            $dcc['allow_price'] = 1;
            $dcc['allow_package_type'] = 1;
            $dcc['allow_nbr_box'] = 1;
            $dcc['allow_warehouse'] = 1;
            $dcc['include_in_invoice'] = 1;
            $dcc['created_at'] = now();
            $dcc['updated_at'] = now();
            DB::table('detail_charge_categories')->insert($dcc);
        }

        // 6. Production Categories & Detail Production Categories
        $prodCats = [
            ['id' => 1, 'name' => 'Production Congélation', 'name_fr' => 'Production Congélation', 'name_en' => 'Freezing Production', 'name_es' => 'Producción Congelación', 'enable_suppliers' => 1, 'enable_clients' => 0],
            ['id' => 2, 'name' => 'Production Sous-Traitance', 'name_fr' => 'Production Sous-Traitance', 'name_en' => 'Subcontracting Production', 'name_es' => 'Producción Subcontratación', 'enable_suppliers' => 0, 'enable_clients' => 1],
            ['id' => 3, 'name' => 'Production Guano', 'name_fr' => 'Production Guano', 'name_en' => 'Guano Production', 'name_es' => 'Producción Guano', 'enable_suppliers' => 1, 'enable_clients' => 1],
        ];
        foreach ($prodCats as $pc) {
            $pc['enable_reception_tracking_number'] = 1;
            $pc['enable_tracking_onp_numbers'] = 1;
            $pc['enable_production_tracking_number'] = 1;
            $pc['enable_production_type'] = 1;
            $pc['enable_fishery_id'] = 1;
            $pc['enable_ship_id'] = 1;
            $pc['enable_origin_id'] = 1;
            $pc['enable_truck_id'] = 0;
            $pc['enable_invoices'] = 1;
            $pc['created_at'] = now();
            $pc['updated_at'] = now();
            DB::table('production_categories')->insert($pc);
        }

        $detailProdCats = [
            ['id' => 1, 'production_category_id' => 1, 'name_fr' => 'Poisson Congelé Produit', 'name_en' => 'Frozen Fish Output', 'name_es' => 'Producción Pescado Congelado', 'article_type' => 'FishType', 'warehouse_operation' => 'stock_in'],
            ['id' => 2, 'production_category_id' => 3, 'name_fr' => 'Guano Produit', 'name_en' => 'Guano Output', 'name_es' => 'Producción Guano', 'article_type' => 'GuanoType', 'warehouse_operation' => 'stock_in'],
            ['id' => 3, 'production_category_id' => 1, 'name_fr' => 'Emballage Consommé', 'name_en' => 'Package Consumed', 'name_es' => 'Embalaje Consumido', 'article_type' => 'PackageType', 'warehouse_operation' => 'stock_out'],
            ['id' => 4, 'production_category_id' => 2, 'name_fr' => 'Poisson Sous-Traitance Produit', 'name_en' => 'Subcontracting Output', 'name_es' => 'Salida Subcontratación', 'article_type' => 'FishType', 'warehouse_operation' => 'stock_in'],
        ];
        foreach ($detailProdCats as $dpc) {
            $dpc['allow_weight'] = 1;
            $dpc['allow_unit_price'] = 1;
            $dpc['allow_price'] = 1;
            $dpc['allow_package_type'] = 1;
            $dpc['allow_nbr_box'] = 1;
            $dpc['allow_warehouse'] = 1;
            $dpc['include_in_invoice'] = 0;
            $dpc['created_at'] = now();
            $dpc['updated_at'] = now();
            DB::table('detail_production_categories')->insert($dpc);
        }

        // 7. Shipping Categories & Detail Shipping Categories
        $shipCats = [
            ['id' => 1, 'name' => 'Vente Locale', 'name_fr' => 'Vente Locale', 'name_en' => 'Local Sale', 'name_es' => 'Venta Local', 'direction' => 'local'],
            ['id' => 2, 'name' => 'Vente Export', 'name_fr' => 'Vente Export', 'name_en' => 'Export Sale', 'name_es' => 'Venta Export', 'direction' => 'export'],
            ['id' => 3, 'name' => 'Sortie Sous-Traitance', 'name_fr' => 'Sortie Sous-Traitance', 'name_en' => 'Subcontracting Exit', 'name_es' => 'Salida Subcontratación', 'direction' => 'local'],
            ['id' => 4, 'name' => 'Vente Emballage', 'name_fr' => 'Vente Emballage', 'name_en' => 'Packaging Sale', 'name_es' => 'Venta Embalaje', 'direction' => 'local'],
            ['id' => 5, 'name' => 'Vente Transport', 'name_fr' => 'Vente Transport', 'name_en' => 'Transport Sale', 'name_es' => 'Venta Transporte', 'direction' => 'local'],
            ['id' => 6, 'name' => 'Vente Glace', 'name_fr' => 'Vente Glace', 'name_en' => 'Ice Sale', 'name_es' => 'Venta Hielo', 'direction' => 'local'],
        ];
        foreach ($shipCats as $sc) {
            $direction = $sc['direction'];
            unset($sc['direction']);
            $sc['enable_shipping_tracking_number'] = 1;
            $sc['enable_tracking_onp_numbers'] = 0;
            $sc['enable_fishery_id'] = 0;
            $sc['enable_ship_id'] = ($direction === 'export' ? 1 : 0);
            $sc['enable_origin_id'] = ($direction === 'export' ? 1 : 0);
            $sc['enable_truck_id'] = 1;
            $sc['enable_clients'] = 1;
            $sc['enable_direction'] = 1;
            $sc['enable_invoices'] = 1;
            $sc['created_at'] = now();
            $sc['updated_at'] = now();
            DB::table('shipping_categories')->insert($sc);
        }

        $detailShipCats = [
            ['id' => 1, 'shipping_category_id' => 1, 'name_fr' => 'Poisson Congelé', 'name_en' => 'Frozen Fish', 'name_es' => 'Pescado Congelado', 'article_type' => 'FishType', 'warehouse_operation' => 'stock_out'],
            ['id' => 2, 'shipping_category_id' => 1, 'name_fr' => 'Poisson Frais', 'name_en' => 'Fresh Fish', 'name_es' => 'Pescado Fresco', 'article_type' => 'FishType', 'warehouse_operation' => 'stock_out'],
            ['id' => 3, 'shipping_category_id' => 1, 'name_fr' => 'Guano', 'name_en' => 'Guano', 'name_es' => 'Guano', 'article_type' => 'GuanoType', 'warehouse_operation' => 'stock_out'],
            ['id' => 4, 'shipping_category_id' => 2, 'name_fr' => 'Poisson Congelé Export', 'name_en' => 'Frozen Fish Export', 'name_es' => 'Pescado Congelado Export', 'article_type' => 'FishType', 'warehouse_operation' => 'stock_out'],
            ['id' => 5, 'shipping_category_id' => 3, 'name_fr' => 'Sortie Sous-Traitance', 'name_en' => 'Subcontracting Exit', 'name_es' => 'Salida Subcontratación', 'article_type' => 'FishType', 'warehouse_operation' => 'stock_out'],
            ['id' => 6, 'shipping_category_id' => 4, 'name_fr' => 'Emballage Client', 'name_en' => 'Client Packaging', 'name_es' => 'Embalaje Cliente', 'article_type' => 'PackageType', 'warehouse_operation' => 'stock_out'],
        ];
        foreach ($detailShipCats as $dsc) {
            $dsc['allow_weight'] = 1;
            $dsc['allow_unit_price'] = 1;
            $dsc['allow_price'] = 1;
            $dsc['allow_package_type'] = 1;
            $dsc['allow_nbr_box'] = 1;
            $dsc['allow_warehouse'] = 1;
            $dsc['include_in_invoice'] = 1;
            $dsc['created_at'] = now();
            $dsc['updated_at'] = now();
            DB::table('detail_shipping_categories')->insert($dsc);
        }

        $this->info("Base system initialized.");
    }

    protected function stepMigratePartnersAndMasterData()
    {
        $this->warn("\n[3/9] Migrating Partners, Fish Types, Origins, Ships, Containers...");

        // 1. Providers -> Insert into BOTH Suppliers and Clients
        $providers = DB::select("SELECT * FROM villamar.providers");
        $supplierCount = 0;
        $clientCount = 0;
        $seenProviderNames = [];

        foreach ($providers as $p) {
            $baseName = trim($p->name_provider ?: ('Partner ' . $p->id));
            $name = $baseName;
            if (isset($seenProviderNames[$name])) {
                $name = $baseName . ' (' . $p->id . ')';
            }
            $seenProviderNames[$name] = true;

            DB::table('suppliers')->insertOrIgnore([
                'id' => $p->id,
                'name' => $name,
                'email' => $p->email ?? null,
                'phone' => $p->phone ?? null,
                'address' => $p->address ?? null,
                'ice' => $p->ice ?? null,
                'created_at' => $p->created_at ?? now(),
                'updated_at' => $p->updated_at ?? now(),
            ]);
            $supplierCount++;

            DB::table('clients')->insertOrIgnore([
                'id' => $p->id,
                'name' => $name,
                'email' => $p->email ?? null,
                'phone' => $p->phone ?? null,
                'address' => $p->address ?? null,
                'ice' => $p->ice ?? null,
                'created_at' => $p->created_at ?? now(),
                'updated_at' => $p->updated_at ?? now(),
            ]);
            $clientCount++;
        }
        $this->info("Migrated {$supplierCount} suppliers and {$clientCount} clients.");

        // 2. Origins
        $origins = DB::select("SELECT * FROM villamar.origins");
        foreach ($origins as $o) {
            DB::table('origins')->insertOrIgnore([
                'id' => $o->id,
                'name' => $o->name_origin,
                'description' => $o->name_origin,
                'created_at' => $o->created_at ?? now(),
                'updated_at' => $o->updated_at ?? now(),
            ]);
        }
        $this->info("Migrated " . count($origins) . " origins.");

        // 3. Places -> Fisheries
        $places = DB::select("SELECT * FROM villamar.places");
        foreach ($places as $pl) {
            DB::table('fisheries')->insertOrIgnore([
                'id' => $pl->id,
                'name' => $pl->name_place,
                'name_fr' => $pl->name_place,
                'name_en' => $pl->name_place,
                'name_es' => $pl->name_place,
                'created_at' => $pl->created_at ?? now(),
                'updated_at' => $pl->updated_at ?? now(),
            ]);
        }
        $this->info("Migrated " . count($places) . " fisheries.");

        // 4. Families -> Fish Families
        $families = DB::select("SELECT * FROM villamar.families");
        foreach ($families as $f) {
            DB::table('fish_families')->insertOrIgnore([
                'id' => $f->id,
                'name_fr' => $f->name,
                'name_en' => $f->name,
                'name_es' => $f->name,
                'created_at' => $f->created_at ?? now(),
                'updated_at' => $f->updated_at ?? now(),
            ]);
        }
        $this->info("Migrated " . count($families) . " fish families.");

        // 5. Types -> Fish Types & Process Types
        $types = DB::select("SELECT * FROM villamar.types");
        $processMap = [];
        foreach ($types as $t) {
            $procName = trim($t->process ?? '');
            if (!empty($procName) && !isset($processMap[$procName])) {
                $pid = DB::table('fish_process_types')->insertGetId([
                    'name' => $procName,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $processMap[$procName] = $pid;
            }
        }

        foreach ($types as $t) {
            $procId = !empty($t->process) && isset($processMap[$t->process]) ? $processMap[$t->process] : null;
            DB::table('fish_types')->insertOrIgnore([
                'id' => $t->id,
                'name_fr' => $t->name_type,
                'name_en' => $t->name_type,
                'name_es' => $t->name_type,
                'name_in_invoice' => $t->name_type,
                'type' => ($t->code_article === 'congele' ? 'frozen' : 'fresh'),
                'process' => $t->process,
                'fish_family_id' => ($t->family_id && $t->family_id > 0 ? $t->family_id : null),
                'fish_process_type_id' => $procId,
                'created_at' => $t->created_at ?? now(),
                'updated_at' => $t->updated_at ?? now(),
            ]);
        }
        $this->info("Migrated " . count($types) . " fish types.");

        // 6. Containers -> Package Types
        $containers = DB::select("SELECT * FROM villamar.containers");
        foreach ($containers as $c) {
            DB::table('package_types')->insertOrIgnore([
                'id' => $c->id,
                'name_fr' => $c->name_container,
                'name_en' => $c->name_container,
                'name_es' => $c->name_container,
                'name_in_invoice' => $c->name_container,
                'size' => $c->size ?? 0,
                'type_emballage' => $c->type ?? 'Cartons',
                'created_at' => $c->created_at ?? now(),
                'updated_at' => $c->updated_at ?? now(),
            ]);
        }
        $this->info("Migrated " . count($containers) . " package types.");

        // 7. Ships & Trucks
        $ships = DB::select("SELECT * FROM villamar.ships");
        $shipCount = 0;
        $truckCount = 0;
        foreach ($ships as $s) {
            if ($s->ship == 1) {
                DB::table('ships')->insertOrIgnore([
                    'id' => $s->id,
                    'name' => $s->ship_name,
                    'created_at' => $s->created_at ?? now(),
                    'updated_at' => $s->updated_at ?? now(),
                ]);
                $shipCount++;
            } else {
                DB::table('trucks')->insertOrIgnore([
                    'id' => $s->id,
                    'registration_plate' => $s->ship_name,
                    'created_at' => $s->created_at ?? now(),
                    'updated_at' => $s->updated_at ?? now(),
                ]);
                $truckCount++;
            }
        }
        $this->info("Migrated {$shipCount} ships and {$truckCount} trucks.");
    }

    protected function stepMigrateWarehouses()
    {
        $this->warn("\n[4/9] Migrating Warehouses from chambres...");

        // Insert default primary warehouses for Fresh, Guano, and Emballage
        DB::table('warehouses')->insertOrIgnore([
            ['id' => 991, 'name' => 'Entrepôt Frais Principal', 'description' => 'Main fresh warehouse', 'warehouse_category_id' => 1, 'warehouse_type' => 'FishType', 'has_sejour' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 992, 'name' => 'Entrepôt Guano', 'description' => 'Main guano warehouse', 'warehouse_category_id' => 3, 'warehouse_type' => 'GuanoType', 'has_sejour' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 993, 'name' => 'Entrepôt Emballage', 'description' => 'Main packaging warehouse', 'warehouse_category_id' => 4, 'warehouse_type' => 'PackageType', 'has_sejour' => 0, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $chambres = DB::select("SELECT * FROM villamar.chambres");
        foreach ($chambres as $ch) {
            $name = trim($ch->name_chambre ?? ('Chambre ' . $ch->id));
            $desc = trim($ch->description ?? '');
            $fullName = $desc ? "{$name} - {$desc}" : $name;

            DB::table('warehouses')->insertOrIgnore([
                'id' => $ch->id,
                'name' => $fullName,
                'description' => $desc ?: $name,
                'warehouse_category_id' => 2, // Frozen
                'warehouse_type' => 'FishType',
                'has_sejour' => 1,
                'created_at' => $ch->created_at ?? now(),
                'updated_at' => $ch->updated_at ?? now(),
            ]);
        }
        $this->info("Migrated " . count($chambres) . " warehouses from chambres.");
    }

    protected function stepMigrateWorkforce()
    {
        $this->warn("\n[5/9] Migrating Workforce, Corporals, Sections, Teams, and Fortnights...");

        // 1. Corporals, Teams, and Section-Corporal assignments
        $corporals = DB::select("SELECT * FROM villamar.corporals");
        foreach ($corporals as $c) {
            $fullName = trim(($c->name ?? '') . ' ' . ($c->prenom ?? '')) ?: ('Corporal ' . $c->id);
            DB::table('corporals')->insertOrIgnore([
                'id' => $c->id,
                'full_name' => $fullName,
                'sex' => ($c->sexe === 'femme' ? 'female' : 'male'),
                'cni' => $c->cni ?? $c->cnie ?? null,
                'daily_price' => 0,
                'is_quinzaine' => $c->fortnight ?? 1,
                'created_at' => $c->created_at ?? now(),
                'updated_at' => $c->updated_at ?? now(),
            ]);

            // Create corresponding Team for this corporal
            DB::table('teams')->insertOrIgnore([
                'id' => $c->id,
                'name' => 'Équipe ' . $fullName,
                'corporal_id' => $c->id,
                'description' => 'Équipe dirigée par ' . $fullName,
                'created_at' => $c->created_at ?? now(),
                'updated_at' => $c->updated_at ?? now(),
            ]);

            // Section <-> Corporal relationship
            if (!empty($c->section_id) && $c->section_id > 0) {
                DB::table('section_corporals')->insertOrIgnore([
                    'section_id' => $c->section_id,
                    'corporal_id' => $c->id,
                    'created_at' => $c->created_at ?? now(),
                    'updated_at' => $c->updated_at ?? now(),
                ]);
            }
        }
        $this->info("Migrated " . count($corporals) . " corporals, teams, and section assignments.");

        // 2. Sections
        $sections = DB::select("SELECT * FROM villamar.sections");
        foreach ($sections as $s) {
            DB::table('sections')->insertOrIgnore([
                'id' => $s->id,
                'name' => $s->nome_section ?? $s->name ?? ('Section ' . $s->id),
                'daily_price' => $s->fixed ?? 0,
                'is_price_modifiable' => $s->edit_fixed ?? 1,
                'created_at' => $s->created_at ?? now(),
                'updated_at' => $s->updated_at ?? now(),
            ]);
        }
        $this->info("Migrated " . count($sections) . " sections.");

        // 3. Ouvriers -> Workforces & Team-Workforce membership
        $ouvriers = DB::select("SELECT * FROM villamar.ouvriers");
        $seenMatricules = [];
        $teamWorkforceBatch = [];

        foreach ($ouvriers as $o) {
            $fullName = trim(($o->name ?? '') . ' ' . ($o->prenom ?? ''));
            $matricule = trim((string)($o->matricule ?: $o->id));
            if (isset($seenMatricules[$matricule])) {
                $matricule = $matricule . '-' . $o->id;
            }
            $seenMatricules[$matricule] = true;

            DB::table('workforces')->insertOrIgnore([
                'id' => $o->id,
                'full_name' => $fullName ?: 'Ouvrier ' . $o->id,
                'matricule' => $matricule,
                'nationality' => $o->nationality ?? 'Moroccan',
                'sex' => ($o->sexe === 'femme' ? 'female' : 'male'),
                'cni' => $o->cni ?? $o->cnie ?? null,
                'fixed_daily_pay' => 0,
                'daily_price' => 0,
                'created_at' => $o->created_at ?? now(),
                'updated_at' => $o->updated_at ?? now(),
            ]);

            if (!empty($o->corporal_id) && $o->corporal_id > 0) {
                $teamWorkforceBatch[] = [
                    'team_id' => $o->corporal_id,
                    'workforce_id' => $o->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }
        if (!empty($teamWorkforceBatch)) {
            DB::table('team_workforce')->insertOrIgnore($teamWorkforceBatch);
        }
        $this->info("Migrated " . count($ouvriers) . " workforces and " . count($teamWorkforceBatch) . " team assignments.");

        // 4. Pointages -> FortnightActivities (Extract days JSON with exact prices)
        $pointagesCount = DB::select("SELECT count(*) as cnt FROM villamar.pointages WHERE days IS NOT NULL AND JSON_LENGTH(days) > 0")[0]->cnt ?? 0;
        $this->info("Migrating {$pointagesCount} pointage entries into fortnight_activities...");

        $offset = 0;
        $limit = 1000;
        $activityBatch = [];
        $totalActivities = 0;
        $totalPriceInserted = 0;

        while ($offset < $pointagesCount) {
            $rows = DB::select("SELECT id, ouvrier_id, section_id, date, days, created_at, updated_at FROM villamar.pointages WHERE days IS NOT NULL AND JSON_LENGTH(days) > 0 LIMIT {$limit} OFFSET {$offset}");
            if (empty($rows)) break;

            foreach ($rows as $p) {
                $days = json_decode($p->days, true);
                if (!is_array($days)) continue;

                foreach ($days as $d) {
                    $workDate = $d['start'] ?? null;
                    if (!$workDate || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $workDate)) {
                        continue;
                    }

                    $dayNum = (int)date('j', strtotime($workDate));
                    $fortnight = ($dayNum <= 15) ? 'Q1' : 'Q2';
                    $secId = !empty($d['section_id']) ? (int)$d['section_id'] : (!empty($p->section_id) ? (int)$p->section_id : null);
                    $price = isset($d['montant']) ? (float)$d['montant'] : 0.00;

                    $activityBatch[] = [
                        'workforce_id' => $p->ouvrier_id,
                        'date' => $workDate,
                        'fortnight_period' => $fortnight,
                        'section_id' => $secId,
                        'corporal_id' => null,
                        'price' => $price,
                        'is_fixed_price' => 0,
                        'created_at' => $p->created_at ?? now(),
                        'updated_at' => $p->updated_at ?? now(),
                    ];

                    $totalActivities++;
                    $totalPriceInserted += $price;

                    if (count($activityBatch) >= 2500) {
                        DB::table('fortnight_activities')->insert($activityBatch);
                        $activityBatch = [];
                    }
                }
            }
            $offset += $limit;
        }

        if (!empty($activityBatch)) {
            DB::table('fortnight_activities')->insert($activityBatch);
        }

        $formattedPrice = number_format($totalPriceInserted, 2);
        $this->info("Finished fortnight activities: migrated {$totalActivities} daily activities totaling {$formattedPrice} MAD.");
    }

    protected function stepMigrateChargesAndReceptions()
    {
        $this->warn("\n[6/9] Migrating Purchases & Receptions (frais, achat_frais, congeles, congelations, guanos, emballages, rsws, glaces, etc.) into charges and detail_charges...");

        $this->migrateFraisCharges();
        $this->migrateSimpleReceptionTable('villamar.achat_frais', 2, 2, 'FishType');
        $this->migrateSimpleReceptionTable('villamar.congeles', 3, 3, 'FishType');
        $this->migrateSimpleReceptionTable('villamar.congelations', 3, 3, 'FishType');
        $this->migrateSimpleReceptionTable('villamar.guanos', 4, 4, 'GuanoType');
        $this->migrateSimpleReceptionTable('villamar.emballages', 5, 5, 'PackageType');
        $this->migrateSimpleReceptionTable('villamar.rsws', 6, 6, 'FishType');
        $this->migrateSimpleReceptionTable('villamar.glaces', 7, 7, 'FishType');
        $this->migrateSimpleReceptionTable('villamar.transports', 8, 8, 'FishType');
        $this->migrateSimpleReceptionTable('villamar.maintenances', 9, 9, 'FishType');
        $this->migrateSimpleReceptionTable('villamar.others', 10, 10, 'FishType');
    }

    protected function migrateFraisCharges()
    {
        $this->info("Processing frais receptions into charges (Category 1: Du frais à la production)...");

        $fraisRows = DB::select("SELECT * FROM villamar.frais");
        $chargesBatch = [];
        $detailChargesBatch = [];
        $chargeTruckBatch = [];

        foreach ($fraisRows as $f) {
            $trackingNumbers = $this->parseTrackingBons($f->num_bon_reception ?? null, $f->num_bon_receptions ?? null);
            $onpNumbers = $this->parseTrackingBons($f->num_bon_onp ?? null, $f->num_bon_onps ?? null);

            $chargesBatch[] = [
                'id' => $f->id,
                'charge_category_id' => 1,
                'date' => $f->date_reception ?? '2021-01-01',
                'total_weight' => $f->total_weight_reception ?? 0,
                'total_price' => $f->total_price_reception ?? 0,
                'supplier_id' => ($f->provider_id && $f->provider_id > 0 ? $f->provider_id : null),
                'origin_id' => ($f->origin_id && $f->origin_id > 0 ? $f->origin_id : null),
                'fishery_id' => ($f->place_id && $f->place_id > 0 ? $f->place_id : null),
                'reception_tracking_number' => json_encode($trackingNumbers),
                'tracking_onp_numbers' => json_encode($onpNumbers),
                'allow_invoice' => $f->is_facture ? 1 : 0,
                'invoice_id' => ($f->is_facture && $f->bill_id ? $f->bill_id : null),
                'created_at' => $f->created_at ?? now(),
                'updated_at' => $f->updated_at ?? now(),
            ];

            // Link trucks
            $truckIds = $this->resolveTruckIds($f->matricules ?? $f->matricule ?? null);
            foreach ($truckIds as $tId) {
                $chargeTruckBatch[] = ['charge_id' => $f->id, 'truck_id' => $tId];
            }

            // Parse types_reception JSON
            $receptionTypes = json_decode($f->types_reception ?? '[]', true) ?: [];
            if (!empty($receptionTypes)) {
                foreach ($receptionTypes as $item) {
                    $tId = $item['type_id'] ?? null;
                    $desc = ($tId && isset($this->fishTypesMap[$tId])) ? $this->fishTypesMap[$tId] : 'Poisson Frais';
                    $w = (float)($item['weight'] ?? 0);
                    $p = (float)($item['price'] ?? 0);
                    $tot = (float)($item['total_weight_price'] ?? ($w * $p));

                    $detailChargesBatch[] = [
                        'charge_id' => $f->id,
                        'detail_charge_category_id' => 1,
                        'article_description' => $desc,
                        'stockable_type' => 'App\\Models\\FishType',
                        'stockable_id' => $tId,
                        'weight' => $w,
                        'unit_price' => $p,
                        'total_price' => $tot,
                        'supplier_id' => ($f->provider_id && $f->provider_id > 0 ? $f->provider_id : null),
                        'created_at' => $f->created_at ?? now(),
                        'updated_at' => $f->updated_at ?? now(),
                    ];
                }
            } else if ($f->total_weight_reception > 0) {
                $w = (float)($f->total_weight_reception ?? 0);
                $p = (float)($f->total_price_reception ?? 0);
                $detailChargesBatch[] = [
                    'charge_id' => $f->id,
                    'detail_charge_category_id' => 1,
                    'article_description' => 'Poisson Frais',
                    'stockable_type' => 'App\\Models\\FishType',
                    'stockable_id' => null,
                    'weight' => $w,
                    'unit_price' => $w > 0 ? round($p / $w, 2) : 0,
                    'total_price' => $p,
                    'supplier_id' => ($f->provider_id && $f->provider_id > 0 ? $f->provider_id : null),
                    'created_at' => $f->created_at ?? now(),
                    'updated_at' => $f->updated_at ?? now(),
                ];
            }

            if (count($chargesBatch) >= 1000) {
                DB::table('charges')->insertOrIgnore($chargesBatch);
                $chargesBatch = [];
            }
            if (count($detailChargesBatch) >= 2000) {
                DB::table('detail_charges')->insertOrIgnore($detailChargesBatch);
                $detailChargesBatch = [];
            }
            if (count($chargeTruckBatch) >= 2000) {
                DB::table('charge_truck')->insertOrIgnore($chargeTruckBatch);
                $chargeTruckBatch = [];
            }
        }

        if (!empty($chargesBatch)) DB::table('charges')->insertOrIgnore($chargesBatch);
        if (!empty($detailChargesBatch)) DB::table('detail_charges')->insertOrIgnore($detailChargesBatch);
        if (!empty($chargeTruckBatch)) DB::table('charge_truck')->insertOrIgnore($chargeTruckBatch);

        $this->info("Completed frais charges migration (" . count($fraisRows) . " charges).");
    }

    protected function migrateSimpleReceptionTable(string $sourceTable, int $chargeCatId, int $detailCatId, string $stockableType)
    {
        $shortName = str_replace('villamar.', '', $sourceTable);
        $this->info("Processing {$shortName} into charges (Category {$chargeCatId})...");

        $maxChargeId = DB::table('charges')->max('id') ?? 0;
        $rows = DB::select("SELECT * FROM {$sourceTable}");
        $chargesBatch = [];
        $detailChargesBatch = [];
        $chargeTruckBatch = [];

        foreach ($rows as $r) {
            $chargeId = ++$maxChargeId;

            // Determine tracking numbers
            $trackingNumbers = $this->parseTrackingBons($r->num_bon_reception ?? null, $r->num_bon_receptions ?? null);
            $onpNumbers = $this->parseTrackingBons($r->num_bon_onp ?? null, $r->num_bon_onps ?? null);

            // Link trucks
            $truckIds = $this->resolveTruckIds($r->matricules ?? $r->matricule ?? null);
            foreach ($truckIds as $tId) {
                $chargeTruckBatch[] = ['charge_id' => $chargeId, 'truck_id' => $tId];
            }

            // Parse items based on table specifics
            $rowDetails = [];

            if ($shortName === 'emballages') {
                $items = isset($r->types_reception) ? json_decode($r->types_reception, true) : null;
                if (!empty($items) && is_array($items)) {
                    foreach ($items as $it) {
                        $containerId = $it['container_id'] ?? $it['type_id'] ?? 1;
                        $desc = $this->packageTypesMap[$containerId] ?? 'Emballage';
                        $w = (float)($it['qte'] ?? $it['weight'] ?? 0);
                        $u = (float)($it['price'] ?? 0);
                        $tot = (float)($it['qte_price'] ?? $it['total_weight_price'] ?? ($w * $u));

                        $rowDetails[] = [
                            'charge_id' => $chargeId,
                            'detail_charge_category_id' => $detailCatId,
                            'article_description' => $desc,
                            'stockable_type' => 'App\\Models\\PackageType',
                            'stockable_id' => $containerId,
                            'weight' => $w,
                            'unit_price' => $u,
                            'total_price' => $tot,
                            'package_type_id' => $containerId,
                            'supplier_id' => ($r->provider_id && $r->provider_id > 0 ? $r->provider_id : null),
                            'created_at' => $r->created_at ?? now(),
                            'updated_at' => $r->updated_at ?? now(),
                        ];
                    }
                }
            } elseif ($shortName === 'guanos') {
                $guanoType = $r->type_guano ?? 'Entier';
                $guanoId = ($guanoType === 'HGT' ? 2 : 1);
                $desc = $this->guanoTypesMap[$guanoId] ?? 'Guano';
                $w = (float)($r->total_weight_reception_guano ?? 0);
                $u = (float)($r->pu ?? 0);
                $tot = (float)($r->total_price_reception_guano ?? ($w * $u));

                $rowDetails[] = [
                    'charge_id' => $chargeId,
                    'detail_charge_category_id' => $detailCatId,
                    'article_description' => $desc,
                    'stockable_type' => 'App\\Models\\GuanoType',
                    'stockable_id' => $guanoId,
                    'weight' => $w,
                    'unit_price' => $u,
                    'total_price' => $tot,
                    'supplier_id' => ($r->provider_id && $r->provider_id > 0 ? $r->provider_id : null),
                    'created_at' => $r->created_at ?? now(),
                    'updated_at' => $r->updated_at ?? now(),
                ];
            } else {
                // Table might have types_reception or types_production
                $items = isset($r->types_reception) ? json_decode($r->types_reception, true) : null;
                if (empty($items) && isset($r->types_production)) {
                    $items = json_decode($r->types_production, true);
                }

                if (!empty($items) && is_array($items)) {
                    foreach ($items as $it) {
                        $tId = $it['type_id'] ?? null;
                        $desc = ($tId && isset($this->fishTypesMap[$tId])) ? $this->fishTypesMap[$tId] : ($shortName === 'achat_frais' ? 'Poisson Frais' : 'Poisson Congelé');
                        $w = (float)($it['weight'] ?? $it['total_weight_container'] ?? 0);
                        $u = (float)($it['price'] ?? $it['pu'] ?? 0);
                        $tot = (float)($it['total_weight_price'] ?? $it['price'] ?? ($w * $u));

                        $rowDetails[] = [
                            'charge_id' => $chargeId,
                            'detail_charge_category_id' => $detailCatId,
                            'article_description' => $desc,
                            'stockable_type' => "App\\Models\\{$stockableType}",
                            'stockable_id' => $tId,
                            'weight' => $w,
                            'unit_price' => $u,
                            'total_price' => $tot,
                            'supplier_id' => ($r->provider_id && $r->provider_id > 0 ? $r->provider_id : null),
                            'created_at' => $r->created_at ?? now(),
                            'updated_at' => $r->updated_at ?? now(),
                        ];
                    }
                }
            }

            // If no detailed items parsed, create fallback from header weight/price
            $headerWeight = (float)($r->total_weight_reception ?? $r->total_weight_reception_guano ?? $r->total_weight_production ?? $r->total_weight ?? 0);
            $headerPrice = (float)($r->total_price_reception ?? $r->total_price_reception_guano ?? $r->total_price_production ?? $r->total_price ?? 0);

            if (empty($rowDetails) && ($headerWeight > 0 || $headerPrice > 0)) {
                $desc = match($shortName) {
                    'glaces' => 'Glace',
                    'rsws' => 'RSW',
                    'transports' => 'Transport',
                    'maintenances' => 'Maintenance',
                    'others' => ($r->description ?? 'Autres Charges'),
                    'achat_frais' => 'Poisson Frais',
                    'congelations', 'congeles' => 'Poisson Congelé',
                    'emballages' => 'Emballage',
                    'guanos' => 'Guano',
                    default => ucfirst($shortName),
                };

                $u = $headerWeight > 0 ? round($headerPrice / $headerWeight, 2) : 0;
                $rowDetails[] = [
                    'charge_id' => $chargeId,
                    'detail_charge_category_id' => $detailCatId,
                    'article_description' => $desc,
                    'stockable_type' => "App\\Models\\{$stockableType}",
                    'stockable_id' => null,
                    'weight' => $headerWeight,
                    'unit_price' => $u,
                    'total_price' => $headerPrice,
                    'supplier_id' => ($r->provider_id && $r->provider_id > 0 ? $r->provider_id : null),
                    'created_at' => $r->created_at ?? now(),
                    'updated_at' => $r->updated_at ?? now(),
                ];
            }

            // Sum up computed details
            $computedWeight = 0;
            $computedPrice = 0;
            foreach ($rowDetails as $rd) {
                $computedWeight += $rd['weight'];
                $computedPrice += $rd['total_price'];
                $detailChargesBatch[] = $rd;
            }

            $finalWeight = ($computedWeight > 0) ? $computedWeight : $headerWeight;
            $finalPrice = ($computedPrice > 0) ? $computedPrice : $headerPrice;

            $chargesBatch[] = [
                'id' => $chargeId,
                'charge_category_id' => $chargeCatId,
                'date' => $r->date_reception ?? $r->date_production ?? $r->date ?? '2021-01-01',
                'total_weight' => $finalWeight,
                'total_price' => $finalPrice,
                'supplier_id' => ($r->provider_id && $r->provider_id > 0 ? $r->provider_id : null),
                'origin_id' => (isset($r->origin_id) && $r->origin_id > 0 ? $r->origin_id : null),
                'fishery_id' => (isset($r->place_id) && $r->place_id > 0 ? $r->place_id : null),
                'reception_tracking_number' => json_encode($trackingNumbers),
                'tracking_onp_numbers' => json_encode($onpNumbers),
                'allow_invoice' => (isset($r->is_facture) && $r->is_facture) ? 1 : 0,
                'invoice_id' => (isset($r->is_facture) && $r->is_facture && !empty($r->bill_id)) ? $r->bill_id : null,
                'created_at' => $r->created_at ?? now(),
                'updated_at' => $r->updated_at ?? now(),
            ];

            if (count($chargesBatch) >= 1000) {
                DB::table('charges')->insertOrIgnore($chargesBatch);
                $chargesBatch = [];
            }
            if (count($detailChargesBatch) >= 2000) {
                DB::table('detail_charges')->insertOrIgnore($detailChargesBatch);
                $detailChargesBatch = [];
            }
            if (count($chargeTruckBatch) >= 2000) {
                DB::table('charge_truck')->insertOrIgnore($chargeTruckBatch);
                $chargeTruckBatch = [];
            }
        }

        if (!empty($chargesBatch)) DB::table('charges')->insertOrIgnore($chargesBatch);
        if (!empty($detailChargesBatch)) DB::table('detail_charges')->insertOrIgnore($detailChargesBatch);
        if (!empty($chargeTruckBatch)) DB::table('charge_truck')->insertOrIgnore($chargeTruckBatch);
    }

    protected function stepMigrateProductions()
    {
        $this->warn("\n[7/9] Migrating Productions (frais production, congelation_clients, guano_usines) into productions & detail_productions...");

        // 1. Frais Production (Category 1: Production Congélation)
        $this->info("Processing freezing productions from frais...");
        $fraisRows = DB::select("SELECT * FROM villamar.frais WHERE (types_production IS NOT NULL AND types_production != '[]') OR total_weight_production > 0");
        $prodBatch = [];
        $detailProdBatch = [];

        foreach ($fraisRows as $f) {
            $trackingRec = $this->parseTrackingBons($f->num_bon_reception ?? null, $f->num_bon_receptions ?? null);
            $trackingProd = $this->parseTrackingBons($f->num_bon_production ?? null, $f->num_bon_productions ?? null);

            $prodBatch[] = [
                'id' => $f->id,
                'production_category_id' => 1,
                'date' => $f->date_production ?? $f->date_reception ?? '2021-01-01',
                'charge_id' => $f->id,
                'total_weight' => $f->total_weight_production ?? 0,
                'fresh_reception_weight' => $f->total_weight_reception ?? 0,
                'frozen_production_weight' => $f->total_weight_production ?? 0,
                'total_price' => $f->total_price_reception ?? 0,
                'supplier_id' => ($f->provider_id && $f->provider_id > 0 ? $f->provider_id : null),
                'origin_id' => ($f->origin_id && $f->origin_id > 0 ? $f->origin_id : null),
                'fishery_id' => ($f->place_id && $f->place_id > 0 ? $f->place_id : null),
                'reception_tracking_number' => json_encode($trackingRec),
                'production_tracking_number' => json_encode($trackingProd),
                'production_type' => 'standard',
                'allow_invoice' => $f->is_facture ? 1 : 0,
                'supplier_invoice_id' => ($f->is_facture && $f->bill_id ? $f->bill_id : null),
                'created_at' => $f->created_at ?? now(),
                'updated_at' => $f->updated_at ?? now(),
            ];

            $typesProd = json_decode($f->types_production ?? '[]', true) ?: [];
            foreach ($typesProd as $tp) {
                $tId = $tp['type_id'] ?? null;
                $desc = ($tId && isset($this->fishTypesMap[$tId])) ? $this->fishTypesMap[$tId] : 'Poisson Congelé';

                $detailProdBatch[] = [
                    'production_id' => $f->id,
                    'detail_production_category_id' => 1,
                    'article_description' => $desc,
                    'stockable_type' => 'App\\Models\\FishType',
                    'stockable_id' => $tId,
                    'weight' => (float)($tp['total_weight_container'] ?? 0),
                    'unit_price' => 0,
                    'total_price' => 0,
                    'package_type_id' => $tp['container_id'] ?? 1,
                    'nbr_box' => (float)($tp['nbr'] ?? 0),
                    'warehouse_id' => ($tp['chambre_id'] && $tp['chambre_id'] > 0 ? $tp['chambre_id'] : 13),
                    'warehouse_operation' => 'stock_in',
                    'created_at' => $f->created_at ?? now(),
                    'updated_at' => $f->updated_at ?? now(),
                ];
            }

            if (count($prodBatch) >= 1000) {
                DB::table('productions')->insertOrIgnore($prodBatch);
                $prodBatch = [];
            }
            if (count($detailProdBatch) >= 2000) {
                DB::table('detail_productions')->insertOrIgnore($detailProdBatch);
                $detailProdBatch = [];
            }
        }
        if (!empty($prodBatch)) DB::table('productions')->insertOrIgnore($prodBatch);
        if (!empty($detailProdBatch)) DB::table('detail_productions')->insertOrIgnore($detailProdBatch);

        // 2. Congelation Clients -> Productions (Category 2: Production Sous-Traitance)
        $this->info("Processing congelation_clients into subcontracting productions...");
        $maxProdId = DB::table('productions')->max('id') ?? 0;
        $congelClientRows = DB::select("SELECT * FROM villamar.congelation_clients");
        $ccBatch = [];
        $detailCcBatch = [];

        foreach ($congelClientRows as $cc) {
            $prodId = ++$maxProdId;
            $trackingRec = $this->parseTrackingBons($cc->num_bon_reception ?? null, $cc->num_bon_receptions ?? null);
            $trackingProd = $this->parseTrackingBons($cc->num_bon_production ?? null, $cc->num_bon_productions ?? null);

            $ccBatch[] = [
                'id' => $prodId,
                'production_category_id' => 2, // Sous-Traitance
                'date' => $cc->date_production ?? $cc->date_reception ?? '2021-01-01',
                'total_weight' => $cc->total_weight_production ?? 0,
                'frozen_production_weight' => $cc->total_weight_production ?? 0,
                'total_price' => $cc->total_price_production ?? 0,
                'client_id' => ($cc->provider_id && $cc->provider_id > 0 ? $cc->provider_id : null),
                'origin_id' => ($cc->origin_id && $cc->origin_id > 0 ? $cc->origin_id : null),
                'fishery_id' => ($cc->place_id && $cc->place_id > 0 ? $cc->place_id : null),
                'reception_tracking_number' => json_encode($trackingRec),
                'production_tracking_number' => json_encode($trackingProd),
                'production_type' => 'sous_traitance',
                'allow_invoice' => $cc->is_facture ? 1 : 0,
                'client_invoice_id' => ($cc->is_facture && $cc->bill_id ? $cc->bill_id : null),
                'created_at' => $cc->created_at ?? now(),
                'updated_at' => $cc->updated_at ?? now(),
            ];

            $typesProd = json_decode($cc->types_production ?? '[]', true) ?: [];
            foreach ($typesProd as $tp) {
                $tId = $tp['type_id'] ?? null;
                $desc = ($tId && isset($this->fishTypesMap[$tId])) ? $this->fishTypesMap[$tId] : 'Poisson Sous-Traitance';
                $w = (float)($tp['total_weight_container'] ?? $tp['weight'] ?? 0);
                $u = (float)($tp['pu'] ?? 0);
                $p = (float)($tp['price'] ?? ($w * $u));

                $detailCcBatch[] = [
                    'production_id' => $prodId,
                    'detail_production_category_id' => 4, // Poisson Sous-Traitance Produit
                    'article_description' => $desc,
                    'stockable_type' => 'App\\Models\\FishType',
                    'stockable_id' => $tId,
                    'weight' => $w,
                    'unit_price' => $u,
                    'total_price' => $p,
                    'package_type_id' => $tp['container_id'] ?? 1,
                    'nbr_box' => (float)($tp['nbr'] ?? 0),
                    'warehouse_id' => ($tp['chambre_id'] && $tp['chambre_id'] > 0 ? $tp['chambre_id'] : 13),
                    'warehouse_operation' => 'stock_in',
                    'created_at' => $cc->created_at ?? now(),
                    'updated_at' => $cc->updated_at ?? now(),
                ];
            }

            if (count($ccBatch) >= 1000) {
                DB::table('productions')->insertOrIgnore($ccBatch);
                $ccBatch = [];
            }
            if (count($detailCcBatch) >= 2000) {
                DB::table('detail_productions')->insertOrIgnore($detailCcBatch);
                $detailCcBatch = [];
            }
        }
        if (!empty($ccBatch)) DB::table('productions')->insertOrIgnore($ccBatch);
        if (!empty($detailCcBatch)) DB::table('detail_productions')->insertOrIgnore($detailCcBatch);

        // 3. Guano Usines -> Productions (Category 3: Production Guano)
        $this->info("Processing guano_usines into guano productions...");
        $guanoUsinesRows = DB::select("SELECT * FROM villamar.guano_usines");
        $guanoBatch = [];
        $detailGuanoBatch = [];

        foreach ($guanoUsinesRows as $gu) {
            $prodId = ++$maxProdId;
            $bonList = $this->parseTrackingBons($gu->num_bon_sortie ?? null, null);

            $guanoSubItems = [];
            $typesSortie = json_decode($gu->types_sortie ?? '[]', true) ?: [];

            if (!empty($typesSortie)) {
                foreach ($typesSortie as $ts) {
                    $rawType = $ts['type_id'] ?? 'Entier';
                    $gid = match(strtoupper(trim((string)$rawType))) {
                        'HGT', '2' => 2,
                        'HG', '3' => 3,
                        'FILET', '4' => 4,
                        default => 1,
                    };
                    $desc = $this->guanoTypesMap[$gid];
                    $w = (float)($ts['weight'] ?? 0);
                    $u = (float)($ts['pu'] ?? 0);
                    $p = (float)($ts['price'] ?? ($w * $u));

                    $guanoSubItems[] = [
                        'gid' => $gid,
                        'desc' => $desc,
                        'weight' => $w,
                        'unit_price' => $u,
                        'total_price' => $p,
                    ];
                }
            } else {
                // Check columns
                $columnsToCheck = [
                    1 => ['w' => 'total_weight_sortie_guano_entier', 'p' => 'total_price_sortie_guano_entier', 'u' => 'pu_entier', 'desc' => 'Guano Entier'],
                    2 => ['w' => 'total_weight_sortie_guano_hgt', 'p' => 'total_price_sortie_guano_hgt', 'u' => 'pu_hgt', 'desc' => 'Guano HGT'],
                    3 => ['w' => 'total_weight_sortie_guano_hg', 'p' => 'total_price_sortie_guano_hg', 'u' => 'pu_hg', 'desc' => 'Guano HG'],
                    4 => ['w' => 'total_weight_sortie_guano_filet', 'p' => 'total_price_sortie_guano_filet', 'u' => 'pu_filet', 'desc' => 'Guano Filet'],
                ];
                foreach ($columnsToCheck as $gid => $cDef) {
                    $w = (float)($gu->{$cDef['w']} ?? 0);
                    $p = (float)($gu->{$cDef['p']} ?? 0);
                    $u = (float)($gu->{$cDef['u']} ?? ($w > 0 ? round($p / $w, 2) : 0));
                    if ($w > 0 || $p > 0) {
                        $guanoSubItems[] = [
                            'gid' => $gid,
                            'desc' => $cDef['desc'],
                            'weight' => $w,
                            'unit_price' => $u,
                            'total_price' => $p,
                        ];
                    }
                }
            }

            $totW = 0;
            $totP = 0;
            foreach ($guanoSubItems as $gsi) {
                $totW += $gsi['weight'];
                $totP += $gsi['total_price'];

                $detailGuanoBatch[] = [
                    'production_id' => $prodId,
                    'detail_production_category_id' => 2, // Guano Produit
                    'article_description' => $gsi['desc'],
                    'stockable_type' => 'App\\Models\\GuanoType',
                    'stockable_id' => $gsi['gid'],
                    'weight' => $gsi['weight'],
                    'unit_price' => $gsi['unit_price'],
                    'total_price' => $gsi['total_price'],
                    'package_type_id' => null,
                    'nbr_box' => 0,
                    'warehouse_id' => 992, // Guano warehouse
                    'warehouse_operation' => 'stock_in',
                    'created_at' => $gu->created_at ?? now(),
                    'updated_at' => $gu->updated_at ?? now(),
                ];
            }

            $guanoBatch[] = [
                'id' => $prodId,
                'production_category_id' => 3, // Guano Production
                'date' => $gu->date_sortie ?? '2021-01-01',
                'total_weight' => $totW,
                'guano_production_weight' => $totW,
                'total_price' => $totP,
                'client_id' => ($gu->provider_id && $gu->provider_id > 0 ? $gu->provider_id : null),
                'origin_id' => ($gu->origin_id && $gu->origin_id > 0 ? $gu->origin_id : null),
                'production_tracking_number' => json_encode($bonList),
                'production_type' => 'guano',
                'allow_invoice' => $gu->is_facture ? 1 : 0,
                'client_invoice_id' => ($gu->is_facture && $gu->bill_id ? $gu->bill_id : null),
                'created_at' => $gu->created_at ?? now(),
                'updated_at' => $gu->updated_at ?? now(),
            ];

            if (count($guanoBatch) >= 1000) {
                DB::table('productions')->insertOrIgnore($guanoBatch);
                $guanoBatch = [];
            }
            if (count($detailGuanoBatch) >= 2000) {
                DB::table('detail_productions')->insertOrIgnore($detailGuanoBatch);
                $detailGuanoBatch = [];
            }
        }
        if (!empty($guanoBatch)) DB::table('productions')->insertOrIgnore($guanoBatch);
        if (!empty($detailGuanoBatch)) DB::table('detail_productions')->insertOrIgnore($detailGuanoBatch);

        $this->info("Completed all productions migration.");
    }

    protected function stepMigrateShippings()
    {
        $this->warn("\n[8/9] Migrating Sales & Shipments (ventes, stock_exits, emballage_clients) into shippings & detail_shippings...");

        // 1. Ventes -> Shippings
        $this->info("Processing sales (ventes) into shippings...");
        $ventes = DB::select("SELECT * FROM villamar.ventes");
        $shippingsBatch = [];
        $detailShippingsBatch = [];
        $shippingTruckBatch = [];

        foreach ($ventes as $v) {
            $isExport = ($v->type_vente === 'export');
            $shipCatId = $isExport ? 2 : 1;
            $bonList = $this->parseTrackingBons($v->num_bon_sortie ?? null, $v->num_bon_sorties ?? null);

            $shippingsBatch[] = [
                'id' => $v->id,
                'shipping_category_id' => $shipCatId,
                'date' => $v->date_sortie ?? '2021-01-01',
                'total_weight' => $v->total_weight_sortie ?? 0,
                'total_price' => $v->total_price_sortie ?? 0,
                'client_id' => ($v->provider_id && $v->provider_id > 0 ? $v->provider_id : null),
                'direction' => $isExport ? 'export' : 'local',
                'shipping_tracking_number' => json_encode($bonList),
                'allow_invoice' => $v->is_facture ? 1 : 0,
                'invoice_id' => ($v->is_facture && $v->bill_id ? $v->bill_id : null),
                'created_at' => $v->created_at ?? now(),
                'updated_at' => $v->updated_at ?? now(),
            ];

            // Link trucks
            $truckIds = $this->resolveTruckIds($v->matricules ?? $v->matricule ?? null);
            foreach ($truckIds as $tId) {
                $shippingTruckBatch[] = ['shipping_id' => $v->id, 'truck_id' => $tId];
            }

            // Parse types_sortie JSON
            $typesSortie = json_decode($v->types_sortie ?? '[]', true) ?: [];
            if (!empty($typesSortie)) {
                foreach ($typesSortie as $ts) {
                    $isGuano = ($v->type === 'guano');
                    $detailCatId = $isExport ? 4 : ($isGuano ? 3 : 1);
                    $w = (float)($ts['weight'] ?? 0);
                    $u = (float)($ts['pu'] ?? 0);
                    $p = (float)($ts['price'] ?? ($w * $u));

                    if ($isGuano) {
                        $rawType = $ts['type_id'] ?? 'Entier';
                        $gid = match(strtoupper(trim((string)$rawType))) {
                            'HGT', '2' => 2,
                            'HG', '3' => 3,
                            'FILET', '4' => 4,
                            default => 1,
                        };
                        $stockType = 'App\\Models\\GuanoType';
                        $stockId = $gid;
                        $desc = $this->guanoTypesMap[$gid];
                    } else {
                        $stockType = 'App\\Models\\FishType';
                        $stockId = $ts['type_id'] ?? null;
                        $desc = ($stockId && isset($this->fishTypesMap[$stockId])) ? $this->fishTypesMap[$stockId] : 'Poisson Congelé';
                    }

                    $detailShippingsBatch[] = [
                        'shipping_id' => $v->id,
                        'detail_shipping_category_id' => $detailCatId,
                        'article_description' => $desc,
                        'stockable_type' => $stockType,
                        'stockable_id' => $stockId,
                        'weight' => $w,
                        'unit_price' => $u,
                        'total_price' => $p,
                        'package_type_id' => $ts['container_id'] ?? null,
                        'nbr_box' => (float)($ts['nbr'] ?? 0),
                        'warehouse_id' => ($ts['chambre_id'] && $ts['chambre_id'] > 0 ? $ts['chambre_id'] : 13),
                        'warehouse_operation' => 'stock_out',
                        'created_at' => $v->created_at ?? now(),
                        'updated_at' => $v->updated_at ?? now(),
                    ];
                }
            } else if ($v->total_weight_sortie > 0) {
                $w = (float)$v->total_weight_sortie;
                $p = (float)$v->total_price_sortie;
                $u = $w > 0 ? round($p / $w, 2) : 0;
                $detailShippingsBatch[] = [
                    'shipping_id' => $v->id,
                    'detail_shipping_category_id' => ($isExport ? 4 : 1),
                    'article_description' => 'Poisson Congelé',
                    'stockable_type' => 'App\\Models\\FishType',
                    'stockable_id' => null,
                    'weight' => $w,
                    'unit_price' => $u,
                    'total_price' => $p,
                    'warehouse_id' => 13,
                    'warehouse_operation' => 'stock_out',
                    'created_at' => $v->created_at ?? now(),
                    'updated_at' => $v->updated_at ?? now(),
                ];
            }

            if (count($shippingsBatch) >= 1000) {
                DB::table('shippings')->insertOrIgnore($shippingsBatch);
                $shippingsBatch = [];
            }
            if (count($detailShippingsBatch) >= 2000) {
                DB::table('detail_shippings')->insertOrIgnore($detailShippingsBatch);
                $detailShippingsBatch = [];
            }
            if (count($shippingTruckBatch) >= 2000) {
                DB::table('shipping_truck')->insertOrIgnore($shippingTruckBatch);
                $shippingTruckBatch = [];
            }
        }
        if (!empty($shippingsBatch)) DB::table('shippings')->insertOrIgnore($shippingsBatch);
        if (!empty($detailShippingsBatch)) DB::table('detail_shippings')->insertOrIgnore($detailShippingsBatch);
        if (!empty($shippingTruckBatch)) DB::table('shipping_truck')->insertOrIgnore($shippingTruckBatch);

        // 2. Stock Exits -> Shippings (Category 3: Sortie Sous-Traitance)
        $this->info("Processing stock_exits into sous-traitance shippings...");
        $maxShippingId = DB::table('shippings')->max('id') ?? 0;
        $stockExits = DB::select("SELECT * FROM villamar.stock_exits");
        $seBatch = [];
        $detailSeBatch = [];
        $seTruckBatch = [];

        foreach ($stockExits as $se) {
            $shippingId = ++$maxShippingId;
            $bonList = $this->parseTrackingBons($se->num_bon_sortie ?? null, $se->num_bon_sorties ?? null);

            $seBatch[] = [
                'id' => $shippingId,
                'shipping_category_id' => 3, // Sortie Sous-Traitance
                'date' => $se->date_sortie ?? '2021-01-01',
                'total_weight' => $se->total_weight_sortie ?? 0,
                'total_price' => $se->total_price_sortie ?? 0,
                'client_id' => ($se->provider_id && $se->provider_id > 0 ? $se->provider_id : null),
                'direction' => 'local',
                'shipping_tracking_number' => json_encode($bonList),
                'allow_invoice' => (isset($se->is_facture) && $se->is_facture) ? 1 : 0,
                'invoice_id' => (isset($se->is_facture) && $se->is_facture && !empty($se->bill_id)) ? $se->bill_id : null,
                'created_at' => $se->created_at ?? now(),
                'updated_at' => $se->updated_at ?? now(),
            ];

            // Link trucks
            $truckIds = $this->resolveTruckIds($se->matricules ?? $se->matricule ?? null);
            foreach ($truckIds as $tId) {
                $seTruckBatch[] = ['shipping_id' => $shippingId, 'truck_id' => $tId];
            }

            $typesSortie = json_decode($se->types_sortie ?? '[]', true) ?: [];
            foreach ($typesSortie as $ts) {
                $tId = $ts['type_id'] ?? null;
                $desc = ($tId && isset($this->fishTypesMap[$tId])) ? $this->fishTypesMap[$tId] : 'Poisson Sous-Traitance';
                $w = (float)($ts['weight'] ?? 0);
                $u = (float)($ts['pu'] ?? 0);
                $p = (float)($ts['price'] ?? ($w * $u));

                $detailSeBatch[] = [
                    'shipping_id' => $shippingId,
                    'detail_shipping_category_id' => 5, // Sortie Sous-Traitance
                    'article_description' => $desc,
                    'stockable_type' => 'App\\Models\\FishType',
                    'stockable_id' => $tId,
                    'weight' => $w,
                    'unit_price' => $u,
                    'total_price' => $p,
                    'package_type_id' => $ts['container_id'] ?? null,
                    'nbr_box' => (float)($ts['nbr'] ?? 0),
                    'warehouse_id' => ($ts['chambre_id'] && $ts['chambre_id'] > 0 ? $ts['chambre_id'] : 13),
                    'warehouse_operation' => 'stock_out',
                    'created_at' => $se->created_at ?? now(),
                    'updated_at' => $se->updated_at ?? now(),
                ];
            }

            if (count($seBatch) >= 1000) {
                DB::table('shippings')->insertOrIgnore($seBatch);
                $seBatch = [];
            }
            if (count($detailSeBatch) >= 2000) {
                DB::table('detail_shippings')->insertOrIgnore($detailSeBatch);
                $detailSeBatch = [];
            }
            if (count($seTruckBatch) >= 2000) {
                DB::table('shipping_truck')->insertOrIgnore($seTruckBatch);
                $seTruckBatch = [];
            }
        }
        if (!empty($seBatch)) DB::table('shippings')->insertOrIgnore($seBatch);
        if (!empty($detailSeBatch)) DB::table('detail_shippings')->insertOrIgnore($detailSeBatch);
        if (!empty($seTruckBatch)) DB::table('shipping_truck')->insertOrIgnore($seTruckBatch);

        // 3. Emballage Clients -> Shippings (Category 4: Vente Emballage)
        $emballageClients = DB::select("SELECT * FROM villamar.emballage_clients");
        $ecBatch = [];
        $detailEcBatch = [];
        foreach ($emballageClients as $ec) {
            $shippingId = ++$maxShippingId;
            $weight = (float)($ec->total_weight_reception ?? $ec->total_weight_sortie ?? 0);
            $price = (float)($ec->total_price_reception ?? $ec->total_price_sortie ?? 0);
            $bonList = $this->parseTrackingBons($ec->num_bon_sortie ?? null, null);
            $containerId = $ec->container_id ?? 1;
            $desc = $this->packageTypesMap[$containerId] ?? 'Emballage Client';

            $ecBatch[] = [
                'id' => $shippingId,
                'shipping_category_id' => 4,
                'date' => $ec->date_sortie ?? '2021-01-01',
                'total_weight' => $weight,
                'total_price' => $price,
                'client_id' => ($ec->provider_id && $ec->provider_id > 0 ? $ec->provider_id : null),
                'direction' => 'local',
                'shipping_tracking_number' => json_encode($bonList),
                'allow_invoice' => (isset($ec->is_facture) && $ec->is_facture) ? 1 : 0,
                'invoice_id' => (isset($ec->is_facture) && $ec->is_facture && !empty($ec->bill_id)) ? $ec->bill_id : null,
                'created_at' => $ec->created_at ?? now(),
                'updated_at' => $ec->updated_at ?? now(),
            ];

            if ($weight > 0 || $price > 0) {
                $detailEcBatch[] = [
                    'shipping_id' => $shippingId,
                    'detail_shipping_category_id' => 6, // Emballage Client
                    'article_description' => $desc,
                    'stockable_type' => 'App\\Models\\PackageType',
                    'stockable_id' => $containerId,
                    'weight' => $weight,
                    'unit_price' => $weight > 0 ? round($price / $weight, 2) : 0,
                    'total_price' => $price,
                    'package_type_id' => $containerId,
                    'nbr_box' => 0,
                    'warehouse_id' => 993, // Packaging warehouse
                    'warehouse_operation' => 'stock_out',
                    'created_at' => $ec->created_at ?? now(),
                    'updated_at' => $ec->updated_at ?? now(),
                ];
            }
        }
        if (!empty($ecBatch)) DB::table('shippings')->insertOrIgnore($ecBatch);
        if (!empty($detailEcBatch)) DB::table('detail_shippings')->insertOrIgnore($detailEcBatch);

        $this->info("Completed all shippings migration.");
    }

    protected function stepMigrateInvoicesAndSettlements()
    {
        $this->warn("\n[9/9] Checking every is_facture = true and bill_id in sales & purchases to create Client & Supplier Invoices and Settlements...");

        // =========================================================================
        // A. CLIENT INVOICES & CLIENT SETTLEMENTS (from ventes, congelation_clients, guano_usines, emballage_clients)
        // =========================================================================
        $this->info("Processing Client Invoices and Client Settlements...");

        $clientBills = DB::select("
            SELECT 
                b.id as bill_id,
                b.date_bill,
                b.num_bill,
                b.provider_id as client_id,
                b.type_bill,
                b.types_sortie,
                COALESCE(b.total_weight_sortie, 0) as bill_weight,
                COALESCE(b.total_price_sortie, 0) as bill_price
            FROM villamar.bills b
        ");

        // Group actual weights and prices from ventes
        $ventesGrouped = DB::select("
            SELECT 
                bill_id,
                provider_id,
                SUM(total_weight_sortie) as sum_weight,
                SUM(total_price_sortie) as sum_price,
                MAX(date_sortie) as max_date,
                GROUP_CONCAT(DISTINCT num_bon_sortie SEPARATOR ',') as bons
            FROM villamar.ventes
            WHERE is_facture = 1 AND bill_id IS NOT NULL AND bill_id > 0
            GROUP BY bill_id, provider_id
        ");
        $ventesMap = [];
        foreach ($ventesGrouped as $vg) {
            $ventesMap[$vg->bill_id] = $vg;
        }

        // Group from congelation_clients
        $ccGrouped = DB::select("
            SELECT 
                bill_id,
                provider_id,
                SUM(total_weight_production) as sum_weight,
                SUM(total_price_production) as sum_price,
                MAX(date_production) as max_date,
                GROUP_CONCAT(DISTINCT num_bon_production SEPARATOR ',') as bons
            FROM villamar.congelation_clients
            WHERE is_facture = 1 AND bill_id IS NOT NULL AND bill_id > 0
            GROUP BY bill_id, provider_id
        ");
        $ccMap = [];
        foreach ($ccGrouped as $cg) {
            $ccMap[$cg->bill_id] = $cg;
        }

        // Group from guano_usines
        $guGrouped = DB::select("
            SELECT 
                bill_id,
                provider_id,
                SUM(COALESCE(total_weight_sortie_guano_hgt,0) + COALESCE(total_weight_sortie_guano_entier,0) + COALESCE(total_weight_sortie_guano_filet,0) + COALESCE(total_weight_sortie_guano_hg,0)) as sum_weight,
                SUM(COALESCE(total_price_sortie_guano_hgt,0) + COALESCE(total_price_sortie_guano_entier,0) + COALESCE(total_price_sortie_guano_filet,0) + COALESCE(total_price_sortie_guano_hg,0)) as sum_price,
                MAX(date_sortie) as max_date,
                GROUP_CONCAT(DISTINCT num_bon_sortie SEPARATOR ',') as bons
            FROM villamar.guano_usines
            WHERE is_facture = 1 AND bill_id IS NOT NULL AND bill_id > 0
            GROUP BY bill_id, provider_id
        ");
        $guMap = [];
        foreach ($guGrouped as $gg) {
            $guMap[$gg->bill_id] = $gg;
        }

        // Group from emballage_clients
        $ecGrouped = DB::select("
            SELECT 
                bill_id,
                provider_id,
                SUM(COALESCE(total_weight_reception, total_weight_sortie, 0)) as sum_weight,
                SUM(COALESCE(total_price_reception, total_price_sortie, 0)) as sum_price,
                MAX(date_sortie) as max_date,
                GROUP_CONCAT(DISTINCT num_bon_sortie SEPARATOR ',') as bons
            FROM villamar.emballage_clients
            WHERE is_facture = 1 AND bill_id IS NOT NULL AND bill_id > 0
            GROUP BY bill_id, provider_id
        ");
        $ecMap = [];
        foreach ($ecGrouped as $eg) {
            $ecMap[$eg->bill_id] = $eg;
        }

        // Combine all bill_ids
        $allClientBillIds = array_unique(array_merge(
            array_column($clientBills, 'bill_id'),
            array_keys($ventesMap),
            array_keys($ccMap),
            array_keys($guMap),
            array_keys($ecMap)
        ));

        $clientBillsMap = [];
        foreach ($clientBills as $cb) {
            $clientBillsMap[$cb->bill_id] = $cb;
        }

        $clientInvoicesBatch = [];
        $clientPaymentsBatch = [];

        foreach ($allClientBillIds as $bid) {
            if (!$bid || $bid <= 0) continue;

            $cb = $clientBillsMap[$bid] ?? null;
            $vg = $ventesMap[$bid] ?? null;
            $cg = $ccMap[$bid] ?? null;
            $gg = $guMap[$bid] ?? null;
            $eg = $ecMap[$bid] ?? null;

            // Calculate aggregated weight & price from invoiced rows
            $sumWeight = (float)($vg->sum_weight ?? 0) + (float)($cg->sum_weight ?? 0) + (float)($gg->sum_weight ?? 0) + (float)($eg->sum_weight ?? 0);
            $sumPrice = (float)($vg->sum_price ?? 0) + (float)($cg->sum_price ?? 0) + (float)($gg->sum_price ?? 0) + (float)($eg->sum_price ?? 0);

            // If line items didn't sum up, check JSON types_sortie on the bill
            $jsonWeight = 0;
            $jsonPrice = 0;
            if ($cb && !empty($cb->types_sortie)) {
                $items = json_decode($cb->types_sortie, true);
                if (is_array($items)) {
                    foreach ($items as $it) {
                        $jsonWeight += (float)($it['weight'] ?? $it['weight_bill'] ?? 0);
                        $jsonPrice += (float)($it['price'] ?? $it['total_weight_price'] ?? 0);
                    }
                }
            }

            $finalWeight = ($sumWeight > 0) ? $sumWeight : (($jsonWeight > 0) ? $jsonWeight : (float)($cb->bill_weight ?? 0));
            $finalPrice = ($sumPrice > 0) ? $sumPrice : (($jsonPrice > 0) ? $jsonPrice : (float)($cb->bill_price ?? 0));

            $clientId = $cb->client_id ?? $vg->provider_id ?? $cg->provider_id ?? $gg->provider_id ?? $eg->provider_id ?? null;
            if (!$clientId) continue;

            $invDate = $cb->date_bill ?? $vg->max_date ?? $cg->max_date ?? $gg->max_date ?? $eg->max_date ?? '2021-01-01';
            $typeInv = (isset($cb->type_bill) && $cb->type_bill === 'export') ? 'export' : 'local';

            // Collect tracking numbers / bons
            $bonList = [];
            if (!empty($cb->num_bill)) $bonList[] = (string)$cb->num_bill;
            if (!empty($vg->bons)) $bonList = array_merge($bonList, explode(',', $vg->bons));
            if (!empty($cg->bons)) $bonList = array_merge($bonList, explode(',', $cg->bons));
            if (!empty($gg->bons)) $bonList = array_merge($bonList, explode(',', $gg->bons));
            if (!empty($eg->bons)) $bonList = array_merge($bonList, explode(',', $eg->bons));
            $bonList = array_values(array_unique(array_filter($bonList)));

            $clientInvoicesBatch[] = [
                'id' => $bid,
                'date' => $invDate,
                'type_invoice' => $typeInv,
                'tracking_numbers' => json_encode($bonList),
                'client_id' => $clientId,
                'client_ids' => json_encode([(string)$clientId]),
                'total_price' => $finalPrice,
                'total_weight' => $finalWeight,
                'print_counter' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Client Settlement
            if ($finalPrice > 0) {
                $clientPaymentsBatch[] = [
                    'client_invoice_id' => $bid,
                    'client_id' => $clientId,
                    'date' => $invDate,
                    'amount' => $finalPrice,
                    'payment_type' => 'cash',
                    'reference' => 'REG-CLI-' . ($cb->num_bill ?? $bid),
                    'notes' => 'Settlement for client invoice #' . ($cb->num_bill ?? $bid),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (count($clientInvoicesBatch) >= 1000) {
                DB::table('client_invoices')->insertOrIgnore($clientInvoicesBatch);
                $clientInvoicesBatch = [];
            }
            if (count($clientPaymentsBatch) >= 1000) {
                DB::table('client_payments')->insertOrIgnore($clientPaymentsBatch);
                $clientPaymentsBatch = [];
            }
        }
        if (!empty($clientInvoicesBatch)) DB::table('client_invoices')->insertOrIgnore($clientInvoicesBatch);
        if (!empty($clientPaymentsBatch)) DB::table('client_payments')->insertOrIgnore($clientPaymentsBatch);

        $this->info("Created " . count($allClientBillIds) . " Client Invoices and corresponding Client Settlements.");

        // =========================================================================
        // B. SUPPLIER INVOICES & SUPPLIER SETTLEMENTS (from achats: frais, congelations, congeles, achat_frais, guanos, emballages, maintenances)
        // =========================================================================
        $this->info("Processing Supplier Invoices and Supplier Settlements from purchase tables...");

        $supplierBills = DB::select("
            SELECT 
                bp.id as bill_id,
                bp.date_bill,
                bp.num_bill,
                bp.provider_id as supplier_id,
                bp.type_bill,
                bp.types_sortie,
                COALESCE(bp.total_weight_sortie, 0) as bill_weight,
                COALESCE(bp.total_price_sortie, 0) as bill_price
            FROM villamar.bill_providers bp
        ");
        $supplierBillsMap = [];
        foreach ($supplierBills as $sb) {
            $supplierBillsMap[$sb->bill_id] = $sb;
        }

        // Aggregate across all purchase tables where is_facture = 1 and bill_id is present
        $purchaseUnionSql = "
            SELECT bill_id, provider_id, total_weight_reception as weight, total_price_reception as price, date_reception as date_item, num_bon_reception as bon FROM villamar.frais WHERE is_facture = 1 AND bill_id IS NOT NULL AND bill_id > 0
            UNION ALL
            SELECT bill_id, provider_id, total_weight_production as weight, total_price_production as price, date_production as date_item, num_bon_production as bon FROM villamar.congelations WHERE is_facture = 1 AND bill_id IS NOT NULL AND bill_id > 0
            UNION ALL
            SELECT bill_id, provider_id, total_weight_production as weight, total_price_production as price, date_production as date_item, num_bon_production as bon FROM villamar.congeles WHERE is_facture = 1 AND bill_id IS NOT NULL AND bill_id > 0
            UNION ALL
            SELECT bill_id, provider_id, total_weight_reception as weight, total_price_reception as price, date_reception as date_item, num_bon_reception as bon FROM villamar.achat_frais WHERE is_facture = 1 AND bill_id IS NOT NULL AND bill_id > 0
            UNION ALL
            SELECT bill_id, provider_id, total_weight_reception_guano as weight, total_price_reception_guano as price, date_reception as date_item, num_bon_reception as bon FROM villamar.guanos WHERE is_facture = 1 AND bill_id IS NOT NULL AND bill_id > 0
            UNION ALL
            SELECT bill_id, provider_id, total_weight_reception as weight, total_price_reception as price, date_reception as date_item, num_bon_reception as bon FROM villamar.emballages WHERE is_facture = 1 AND bill_id IS NOT NULL AND bill_id > 0
            UNION ALL
            SELECT bill_id, provider_id, 0 as weight, total_price_reception as price, date_reception as date_item, num_bon_reception as bon FROM villamar.maintenances WHERE is_facture = 1 AND bill_id IS NOT NULL AND bill_id > 0
        ";

        $purchasesGrouped = DB::select("
            SELECT 
                bill_id,
                provider_id,
                SUM(COALESCE(weight, 0)) as sum_weight,
                SUM(COALESCE(price, 0)) as sum_price,
                MAX(date_item) as max_date,
                GROUP_CONCAT(DISTINCT bon SEPARATOR ',') as bons
            FROM ({$purchaseUnionSql}) as p_union
            GROUP BY bill_id, provider_id
        ");

        $purchasesMap = [];
        foreach ($purchasesGrouped as $pg) {
            $purchasesMap[$pg->bill_id] = $pg;
        }

        $allSupplierBillIds = array_unique(array_merge(
            array_column($supplierBills, 'bill_id'),
            array_keys($purchasesMap)
        ));

        $supplierInvoicesBatch = [];
        $supplierPaymentsBatch = [];

        foreach ($allSupplierBillIds as $sbid) {
            if (!$sbid || $sbid <= 0) continue;

            $sb = $supplierBillsMap[$sbid] ?? null;
            $pg = $purchasesMap[$sbid] ?? null;

            $sumWeight = (float)($pg->sum_weight ?? 0);
            $sumPrice = (float)($pg->sum_price ?? 0);

            // If sum is 0, parse types_sortie from bill_providers
            $jsonWeight = 0;
            $jsonPrice = 0;
            if ($sb && !empty($sb->types_sortie)) {
                $items = json_decode($sb->types_sortie, true);
                if (is_array($items)) {
                    foreach ($items as $it) {
                        $jsonWeight += (float)($it['weight'] ?? $it['weight_bill'] ?? 0);
                        $jsonPrice += (float)($it['price'] ?? $it['total_weight_price'] ?? 0);
                    }
                }
            }

            $finalWeight = ($sumWeight > 0) ? $sumWeight : (($jsonWeight > 0) ? $jsonWeight : (float)($sb->bill_weight ?? 0));
            $finalPrice = ($sumPrice > 0) ? $sumPrice : (($jsonPrice > 0) ? $jsonPrice : (float)($sb->bill_price ?? 0));

            $supplierId = $sb->supplier_id ?? $pg->provider_id ?? null;
            if (!$supplierId) continue;

            $invDate = $sb->date_bill ?? $pg->max_date ?? '2021-01-01';
            $typeInv = (isset($sb->type_bill) && $sb->type_bill === 'export') ? 'export' : 'local';

            $bonList = [];
            if (!empty($sb->num_bill)) $bonList[] = (string)$sb->num_bill;
            if (!empty($pg->bons)) $bonList = array_merge($bonList, explode(',', $pg->bons));
            $bonList = array_values(array_unique(array_filter($bonList)));

            $supplierInvoicesBatch[] = [
                'id' => $sbid,
                'date' => $invDate,
                'tracking_numbers' => json_encode($bonList),
                'type_invoice' => $typeInv,
                'total_price' => $finalPrice,
                'total_weight' => $finalWeight,
                'supplier_id' => $supplierId,
                'print_counter' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Supplier Settlement
            if ($finalPrice > 0) {
                $supplierPaymentsBatch[] = [
                    'supplier_invoice_id' => $sbid,
                    'supplier_id' => $supplierId,
                    'date' => $invDate,
                    'amount' => $finalPrice,
                    'payment_type' => 'cash',
                    'reference' => 'REG-FOURN-' . ($sb->num_bill ?? $sbid),
                    'notes' => 'Settlement for supplier invoice #' . ($sb->num_bill ?? $sbid),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (count($supplierInvoicesBatch) >= 1000) {
                DB::table('supplier_invoices')->insertOrIgnore($supplierInvoicesBatch);
                $supplierInvoicesBatch = [];
            }
            if (count($supplierPaymentsBatch) >= 1000) {
                DB::table('supplier_payments')->insertOrIgnore($supplierPaymentsBatch);
                $supplierPaymentsBatch = [];
            }
        }
        if (!empty($supplierInvoicesBatch)) DB::table('supplier_invoices')->insertOrIgnore($supplierInvoicesBatch);
        if (!empty($supplierPaymentsBatch)) DB::table('supplier_payments')->insertOrIgnore($supplierPaymentsBatch);

        $this->info("Created " . count($allSupplierBillIds) . " Supplier Invoices and corresponding Supplier Settlements.");
    }

    protected function stepSyncWarehouseBalances()
    {
        $this->warn("\n[Optional Sync] Calculating warehouse balances from detail_productions and detail_shippings...");

        // Populate warehouse balances from detail productions (stock_in)
        $detailProds = DB::select("
            SELECT 
                dp.warehouse_id,
                dp.stockable_type,
                dp.stockable_id,
                dp.package_type_id,
                dp.nbr_box as package_count,
                dp.weight as total_weight,
                p.date,
                p.supplier_id,
                s.name as supplier_name,
                p.id as source_id
            FROM main_project.detail_productions dp
            JOIN main_project.productions p ON p.id = dp.production_id
            LEFT JOIN main_project.suppliers s ON s.id = p.supplier_id
            WHERE dp.warehouse_operation = 'stock_in' AND dp.warehouse_id IS NOT NULL AND dp.weight > 0
        ");

        $wbBatch = [];
        foreach ($detailProds as $dp) {
            $wbBatch[] = [
                'warehouse_id' => $dp->warehouse_id,
                'stockable_type' => $dp->stockable_type,
                'stockable_id' => $dp->stockable_id ?? 1,
                'warehouse_operation' => 'stock_in',
                'date' => $dp->date,
                'total_weight' => $dp->total_weight,
                'remaining_weight' => $dp->total_weight,
                'total_price' => 0,
                'unit_price' => 0,
                'package_count' => $dp->package_count ?? 0,
                'package_type_id' => $dp->package_type_id,
                'supplier_name' => $dp->supplier_name,
                'source_type' => 'Production',
                'source_id' => $dp->source_id,
                'is_finished' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($wbBatch) >= 1500) {
                DB::table('warehouse_balances')->insertOrIgnore($wbBatch);
                $wbBatch = [];
            }
        }
        if (!empty($wbBatch)) DB::table('warehouse_balances')->insertOrIgnore($wbBatch);

        // Update total weights on warehouses
        DB::statement("
            UPDATE warehouses w
            LEFT JOIN (
                SELECT warehouse_id, SUM(remaining_weight) as cur_weight 
                FROM warehouse_balances 
                GROUP BY warehouse_id
            ) as wb ON wb.warehouse_id = w.id
            SET w.total_weight = COALESCE(wb.cur_weight, 0)
        ");

        $this->info("Warehouse balances synchronized and total weights updated.");
    }
}
