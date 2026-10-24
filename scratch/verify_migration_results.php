<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

echo "======================================================================\n";
echo "              MIGRATION INTEGRITY & VERIFICATION REPORT               \n";
echo "======================================================================\n\n";

// 1. Table Counts
$tables = [
    'users', 'roles', 'suppliers', 'clients', 'origins', 'fisheries', 'fish_types',
    'fish_families', 'fish_process_types', 'package_types', 'ships', 'trucks',
    'warehouses', 'warehouse_categories', 'warehouse_balances',
    'corporals', 'sections', 'teams', 'team_workforce', 'section_corporals', 'workforces', 'fortnight_activities',
    'charge_categories', 'detail_charge_categories', 'charges', 'detail_charges',
    'production_categories', 'detail_production_categories', 'productions', 'detail_productions',
    'shipping_categories', 'detail_shipping_categories', 'shippings', 'detail_shippings',
    'client_invoices', 'client_payments',
    'supplier_invoices', 'supplier_payments',
];

echo "--- 1. TABLE ROW COUNTS IN JUPITER ---\n";
foreach ($tables as $t) {
    $cnt = DB::table($t)->count();
    echo str_pad($t, 32) . ": " . number_format($cnt) . " rows\n";
}

// 2. Financial Totals & Verification
echo "\n--- 2. FINANCIAL & OPERATIONAL TOTALS ---\n";

$activityStats = DB::select("
    SELECT 
        COUNT(*) as total_activities,
        SUM(price) as total_amount,
        COUNT(DISTINCT workforce_id) as active_workers,
        COUNT(DISTINCT date) as distinct_dates,
        COUNT(CASE WHEN price > 0 THEN 1 END) as positive_price_activities
    FROM fortnight_activities
")[0];

echo "Workforce Activities:\n";
echo "  Total Activities: " . number_format($activityStats->total_activities) . "\n";
echo "  Positive Price Activities: " . number_format($activityStats->positive_price_activities) . "\n";
echo "  Distinct Active Workers: " . number_format($activityStats->active_workers) . "\n";
echo "  Distinct Dates: " . number_format($activityStats->distinct_dates) . "\n";
echo "  Total Payroll Price: " . number_format($activityStats->total_amount, 2) . " MAD\n";

$cliInvStats = DB::select("SELECT COUNT(*) as cnt, SUM(total_price) as sum_price, SUM(total_weight) as sum_weight FROM client_invoices")[0];
$cliPayStats = DB::select("SELECT COUNT(*) as cnt, SUM(amount) as sum_amount FROM client_payments")[0];

echo "\nClient Invoices & Settlements:\n";
echo "  Client Invoices Count: " . number_format($cliInvStats->cnt) . "\n";
echo "  Total Invoiced Price: " . number_format($cliInvStats->sum_price, 2) . " MAD\n";
echo "  Total Invoiced Weight: " . number_format($cliInvStats->sum_weight, 2) . " KG\n";
echo "  Client Payments (Settlements) Count: " . number_format($cliPayStats->cnt) . "\n";
echo "  Total Settlements Amount: " . number_format($cliPayStats->sum_amount, 2) . " MAD\n";

$supInvStats = DB::select("SELECT COUNT(*) as cnt, SUM(total_price) as sum_price, SUM(total_weight) as sum_weight FROM supplier_invoices")[0];
$supPayStats = DB::select("SELECT COUNT(*) as cnt, SUM(amount) as sum_amount FROM supplier_payments")[0];

echo "\nSupplier Invoices & Settlements:\n";
echo "  Supplier Invoices Count: " . number_format($supInvStats->cnt) . "\n";
echo "  Total Invoiced Price: " . number_format($supInvStats->sum_price, 2) . " MAD\n";
echo "  Total Invoiced Weight: " . number_format($supInvStats->sum_weight, 2) . " KG\n";
echo "  Supplier Payments (Settlements) Count: " . number_format($supPayStats->cnt) . "\n";
echo "  Total Settlements Amount: " . number_format($supPayStats->sum_amount, 2) . " MAD\n";

// 3. Foreign Key & Relationship Integrity
echo "\n--- 3. RELATIONSHIP & FOREIGN KEY INTEGRITY ---\n";

$badChargesInv = DB::select("SELECT COUNT(*) as cnt FROM charges WHERE invoice_id IS NOT NULL AND invoice_id NOT IN (SELECT id FROM supplier_invoices)")[0]->cnt;
echo "Charges with invalid invoice_id: {$badChargesInv}\n";

$badShippingsInv = DB::select("SELECT COUNT(*) as cnt FROM shippings WHERE invoice_id IS NOT NULL AND invoice_id NOT IN (SELECT id FROM client_invoices)")[0]->cnt;
echo "Shippings with invalid invoice_id: {$badShippingsInv}\n";

$badProdSupInv = DB::select("SELECT COUNT(*) as cnt FROM productions WHERE supplier_invoice_id IS NOT NULL AND supplier_invoice_id NOT IN (SELECT id FROM supplier_invoices)")[0]->cnt;
echo "Productions with invalid supplier_invoice_id: {$badProdSupInv}\n";

$badProdCliInv = DB::select("SELECT COUNT(*) as cnt FROM productions WHERE client_invoice_id IS NOT NULL AND client_invoice_id NOT IN (SELECT id FROM client_invoices)")[0]->cnt;
echo "Productions with invalid client_invoice_id: {$badProdCliInv}\n";

$badCliPayInv = DB::select("SELECT COUNT(*) as cnt FROM client_payments WHERE client_invoice_id IS NOT NULL AND client_invoice_id NOT IN (SELECT id FROM client_invoices)")[0]->cnt;
echo "Client payments with invalid client_invoice_id: {$badCliPayInv}\n";

$badSupPayInv = DB::select("SELECT COUNT(*) as cnt FROM supplier_payments WHERE supplier_invoice_id IS NOT NULL AND supplier_invoice_id NOT IN (SELECT id FROM supplier_invoices)")[0]->cnt;
echo "Supplier payments with invalid supplier_invoice_id: {$badSupPayInv}\n";

// 4. Admin User Credentials Verification
echo "\n--- 4. ADMIN USER VERIFICATION ---\n";
$user = User::where('email', 'test@test.com')->first();
if ($user && Hash::check('password', $user->password)) {
    echo "Admin User (test@test.com / password): VALID (Role ID: {$user->role_id}, Name: {$user->name})\n";
} else {
    echo "Admin User (test@test.com / password): FAILED!\n";
}

echo "\n======================================================================\n";
echo "                      END OF VERIFICATION REPORT                      \n";
echo "======================================================================\n";
