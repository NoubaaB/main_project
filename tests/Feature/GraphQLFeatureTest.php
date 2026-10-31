<?php

use App\Models\FishFamily;
use App\Models\FishType;
use App\Models\Warehouse;
use App\Models\WarehouseBalance;
use App\Models\WarehouseCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = $this->createAdminUser();
    Sanctum::actingAs($this->user);

    $this->category = WarehouseCategory::create([
        'name' => 'Chambres GraphQL',
    ]);

    $this->warehouse = Warehouse::create([
        'name' => 'Chambre GraphQL Alpha',
        'capacity' => 100000,
        'warehouse_category_id' => $this->category->id,
    ]);

    $this->family = FishFamily::create([
        'name_fr' => 'Pelagique',
        'name_en' => 'Pelagic',
        'name_es' => 'Pelagico',
    ]);

    $this->fishType = FishType::create([
        'name_fr' => 'Sardine Fraiche GQL',
        'name_en' => 'Fresh Sardine GQL',
        'name_es' => 'Sardina GQL',
        'name_in_invoice' => 'SARDINE GQL',
        'fish_family_id' => $this->family->id,
    ]);

    $this->balance = WarehouseBalance::create([
        'warehouse_id' => $this->warehouse->id,
        'stockable_type' => FishType::class,
        'stockable_id' => $this->fishType->id,
        'reception_tracking_number' => '511',
        'warehouse_operation' => 'stock_in',
        'date' => now()->format('Y-m-d'),
        'total_weight' => 8000,
        'remaining_weight' => 6000,
        'package_count' => 400,
        'unit_price' => 14.5,
        'total_price' => 116000,
        'supplier_name' => 'Fournisseur GQL',
        'source_type' => 'DetailCharge',
        'source_id' => 1,
        'is_finished' => false,
    ]);
});

test('graphql warehouses query returns list of warehouses', function () {
    $query = 'query { warehouses { id name capacity } }';
    $response = $this->postJson('/api/graphql', ['query' => $query]);

    $response->assertStatus(200);
    $data = $response->json('data.warehouses');
    expect($data)->toHaveCount(1);
    expect($data[0]['name'])->toBe('Chambre GraphQL Alpha');
});

test('graphql chambersOverview query returns chamber metrics', function () {
    $query = 'query { chambersOverview { id name active_lots total_weight valuation is_empty } }';
    $response = $this->postJson('/api/graphql', ['query' => $query]);

    $response->assertStatus(200);
    $data = $response->json('data.chambersOverview');
    expect($data)->toHaveCount(1);
    expect((float)$data[0]['total_weight'])->toBe(6000.0);
    expect($data[0]['active_lots'])->toBe(1);
});

test('graphql speciesOverview query returns aggregated species data', function () {
    $query = 'query { speciesOverview { stockable_id article_name total_weight } }';
    $response = $this->postJson('/api/graphql', ['query' => $query]);

    $response->assertStatus(200);
    $data = $response->json('data.speciesOverview');
    expect($data)->toHaveCount(1);
    expect((float)$data[0]['total_weight'])->toBe(6000.0);
});

test('graphql stockParLot query returns paginated lots with summary', function () {
    $query = 'query($status: String) { stockParLot(status: $status) { total_count summary { in_stock_lots total_remaining_weight } lots { lot_number remaining_weight } } }';
    $response = $this->postJson('/api/graphql', [
        'query' => $query,
        'variables' => ['status' => 'in_stock'],
    ]);

    $response->assertStatus(200);
    expect($response->json('data.stockParLot.total_count'))->toBeGreaterThanOrEqual(1);
    expect((float)$response->json('data.stockParLot.summary.total_remaining_weight'))->toBe(6000.0);
});

test('graphql warehouseAnalytics query returns intelligence suite', function () {
    $query = 'query { warehouseAnalytics { summary { in_weight out_weight } aging { total_lots optimal { lots_count } } } }';
    $response = $this->postJson('/api/graphql', ['query' => $query]);

    $response->assertStatus(200);
    expect($response->json('data.warehouseAnalytics.summary'))->not->toBeNull();
    expect($response->json('data.warehouseAnalytics.aging'))->not->toBeNull();
});

test('graphql traceabilityVouchers and articles queries return valid lifecycle data', function () {
    $query1 = 'query($tracking_numbers: [String]) { traceabilityVouchers(tracking_numbers: $tracking_numbers) { key voucher_type remaining_weight } }';
    $response1 = $this->postJson('/api/graphql', [
        'query' => $query1,
        'variables' => ['tracking_numbers' => ['511']],
    ]);

    $response1->assertStatus(200);
    expect($response1->json('data.traceabilityVouchers'))->toBeArray();

    $query2 = 'query($balance_ids: [Int]!) { traceabilityVoucherArticles(balance_ids: $balance_ids) { id article_name remaining_weight } }';
    $response2 = $this->postJson('/api/graphql', [
        'query' => $query2,
        'variables' => ['balance_ids' => [$this->balance->id]],
    ]);

    $response2->assertStatus(200);
    $articles = $response2->json('data.traceabilityVoucherArticles');
    expect($articles)->toHaveCount(1);
    expect((float)$articles[0]['remaining_weight'])->toBe(6000.0);
});

test('graphql warehouse mutations create and delete warehouse', function () {
    $createMutation = 'mutation($name: String!, $capacity: Float) { createWarehouse(name: $name, capacity: $capacity) { id name capacity } }';
    $resCreate = $this->postJson('/api/graphql', [
        'query' => $createMutation,
        'variables' => ['name' => 'New GQL Chamber', 'capacity' => 45000],
    ]);

    $resCreate->assertStatus(200);
    $createdId = $resCreate->json('data.createWarehouse.id');
    expect($createdId)->not->toBeNull();
    expect($resCreate->json('data.createWarehouse.name'))->toBe('New GQL Chamber');

    $deleteMutation = 'mutation($id: ID!) { deleteWarehouse(id: $id) }';
    $resDelete = $this->postJson('/api/graphql', [
        'query' => $deleteMutation,
        'variables' => ['id' => (string) $createdId],
    ]);

    $resDelete->assertStatus(200);
    expect($resDelete->json('data.deleteWarehouse'))->toBeTrue();
});

test('graphql returns validation errors on missing query', function () {
    $response = $this->postJson('/api/graphql', ['query' => '']);
    $response->assertStatus(400);
    expect($response->json('errors.0.message'))->toBe('GraphQL query is required.');
});

test('graphql can query productions and productionAnalytics', function () {
    $query = 'query { productions(all: true) { id date total_weight } productionAnalytics(mode: "daily") { fresh_reception { x y } } }';
    $response = $this->postJson('/api/graphql', ['query' => $query]);

    $response->assertStatus(200);
    expect($response->json('data.productions'))->toBeArray();
    expect($response->json('data.productionAnalytics.fresh_reception'))->toBeArray();
});

test('graphql can query charges and shippings', function () {
    $query = 'query { charges(all: true) { id date reception_tracking_number } shippings(all: true) { id date shipping_tracking_number } }';
    $response = $this->postJson('/api/graphql', ['query' => $query]);

    $response->assertStatus(200);
    expect($response->json('data.charges'))->toBeArray();
    expect($response->json('data.shippings'))->toBeArray();
});

test('graphql can query invoices and settlements', function () {
    $query = 'query {
        supplierInvoices { id date tracking_numbers }
        clientInvoices { id date tracking_numbers }
        supplierPayments(year: 2026) { id date amount }
        clientPayments(year: 2026) { id date amount }
        supplierBalance(year: 2026) { carry_forward_total balance { id name } }
        clientBalance(year: 2026) { carry_forward_total balance { id name } }
        supplierYearSummary(year: 2026) { summary { id name } }
        clientYearSummary(year: 2026) { summary { id name } }
    }';
    $response = $this->postJson('/api/graphql', ['query' => $query]);

    $response->assertStatus(200);
    expect($response->json('data.supplierInvoices'))->toBeArray();
    expect($response->json('data.clientInvoices'))->toBeArray();
    expect($response->json('data.supplierPayments'))->toBeArray();
    expect($response->json('data.clientPayments'))->toBeArray();
    expect($response->json('data.supplierBalance.balance'))->toBeArray();
    expect($response->json('data.clientBalance.balance'))->toBeArray();
});

test('graphql can query order-transfers, inventory and user-statistics', function () {
    $query = 'query {
        orderTransfers { id date amount transfer_number }
        inventories(all: true) { id date inventory_tracking_numbers }
        userStatistics { date_range { start end } today_stats { total active_users } }
    }';
    $response = $this->postJson('/api/graphql', ['query' => $query]);

    $response->assertStatus(200);
    expect($response->json('data.orderTransfers'))->toBeArray();
    expect($response->json('data.inventories'))->toBeArray();
    expect($response->json('data.userStatistics.today_stats'))->not->toBeNull();
});

test('graphql can query workforce statistics, absences, and fortnight management', function () {
    $workforce = \App\Models\Workforce::create([
        'full_name' => 'Jean Dupont GQL',
        'matricule' => 'WF-GQL-001',
        'sex' => 'male',
        'status' => 'active',
        'daily_price' => 150.00,
    ]);

    $section = \App\Models\Section::create([
        'name' => 'Section Decorticage GQL',
        'daily_price' => 150.00,
    ]);

    $corporal = \App\Models\Corporal::create([
        'full_name' => 'Caporal Alpha GQL',
        'sex' => 'male',
        'daily_price' => 200.00,
    ]);

    $activity = \App\Models\FortnightActivity::create([
        'workforce_id' => $workforce->id,
        'date' => now()->format('Y-m-d'),
        'fortnight_period' => ((int) now()->format('d')) <= 15 ? 'Q1' : 'Q2',
        'price' => 150.00,
        'section_id' => $section->id,
        'corporal_id' => null,
        'is_fixed_price' => false,
    ]);

    $query = 'query($year: Int!, $month: Int!, $start: String!, $end: String!) {
        workforces { id full_name matricule status }
        workforce(id: ' . $workforce->id . ') { id full_name matricule }
        workforceAbsences(threshold: 7) {
            threshold_days
            summary { total_workforces active_count absent_count }
            workforces { id full_name status }
        }
        workforceStatistics(date_start: $start, date_end: $end) {
            grand_total
            total_activities
            daily { date total count }
            sections { id name total }
        }
        workforceMovementStatistics(start_date: $start, end_date: $end) {
            summary { total_activities total_amount }
        }
        fortnightSummary(year: $year, month: $month) {
            first_fortnight { total working_days }
            second_fortnight { total working_days }
            month_total
        }
        fortnightDailyTotals(year: $year, month: $month) {
            date
            total_price
            count
        }
        fortnightActivities(year: $year, month: $month) {
            id
            date
            price
            workforce { id full_name }
            section { id name }
            corporal { id full_name }
        }
        fortnightActivitiesByDate(date: "' . now()->format('Y-m-d') . '") {
            id
            date
            price
            workforce { id full_name }
        }
        fortnightBreakdown(year: $year, month: $month) {
            sections { id name month_total }
            corporals { id name month_total }
            workforces { id name month_total }
        }
    }';

    $response = $this->postJson('/api/graphql', [
        'query' => $query,
        'variables' => [
            'year' => (int) now()->format('Y'),
            'month' => (int) now()->format('m'),
            'start' => now()->startOfMonth()->format('Y-m-d'),
            'end' => now()->endOfMonth()->format('Y-m-d'),
        ],
    ]);

    $response->assertStatus(200);
    $data = $response->json('data');

    expect($data['workforces'])->toBeArray();
    expect($data['workforce']['full_name'])->toBe('Jean Dupont GQL');
    expect($data['workforceAbsences']['summary']['total_workforces'])->toBeGreaterThanOrEqual(1);
    expect($data['workforceStatistics']['total_activities'])->toBeGreaterThanOrEqual(1);
    expect((float)$data['workforceStatistics']['grand_total'])->toBeGreaterThanOrEqual(150.0);
    expect($data['fortnightSummary']['month_total'])->toBeGreaterThanOrEqual(150.0);
    expect($data['fortnightDailyTotals'])->toBeArray();
    expect($data['fortnightActivities'])->toBeArray();
    expect($data['fortnightActivitiesByDate'])->toBeArray();
    expect($data['fortnightBreakdown']['sections'])->toBeArray();
});

test('graphql can execute workforce absence settings and fortnight activity mutations', function () {
    $workforce = \App\Models\Workforce::create([
        'full_name' => 'Mutation Test Worker',
        'matricule' => 'WF-MUT-002',
        'sex' => 'female',
        'status' => 'active',
        'daily_price' => 160.00,
    ]);

    // 1. Mutation: saveWorkforceAbsenceSetting
    $setSettingQuery = 'mutation {
        saveWorkforceAbsenceSetting(threshold_days: 14) {
            threshold_days
            message
        }
    }';
    $respSetting = $this->postJson('/api/graphql', ['query' => $setSettingQuery]);
    $respSetting->assertStatus(200);
    expect($respSetting->json('data.saveWorkforceAbsenceSetting.threshold_days'))->toBe(14);

    // 2. Mutation: createFortnightActivity
    $mutSection = \App\Models\Section::create([
        'name' => 'Sec Mutation GQL',
        'daily_price' => 160.00,
    ]);

    $createActQuery = 'mutation($wfId: Int!, $date: String!, $price: Float!, $secId: Int!) {
        createFortnightActivity(workforce_id: $wfId, date: $date, price: $price, section_id: $secId) {
            message
            activity {
                id
                workforce_id
                date
                price
            }
        }
    }';
    $respCreate = $this->postJson('/api/graphql', [
        'query' => $createActQuery,
        'variables' => [
            'wfId' => (int) $workforce->id,
            'date' => now()->format('Y-m-d'),
            'price' => 160.0,
            'secId' => (int) $mutSection->id,
        ],
    ]);
    $respCreate->assertStatus(200);
    $newActId = $respCreate->json('data.createFortnightActivity.activity.id');
    expect($newActId)->not->toBeNull();

    // 3. Mutation: updateFortnightActivity
    $updateActQuery = 'mutation($id: ID!, $price: Float!) {
        updateFortnightActivity(id: $id, price: $price) {
            message
            activity {
                id
                price
            }
        }
    }';
    $respUpdate = $this->postJson('/api/graphql', [
        'query' => $updateActQuery,
        'variables' => [
            'id' => (string) $newActId,
            'price' => 175.0,
        ],
    ]);
    $respUpdate->assertStatus(200);
    expect((float)$respUpdate->json('data.updateFortnightActivity.activity.price'))->toBe(175.0);

    // 4. Mutation: deleteFortnightActivity
    $delActQuery = 'mutation($id: ID!) {
        deleteFortnightActivity(id: $id)
    }';
    $respDel = $this->postJson('/api/graphql', [
        'query' => $delActQuery,
        'variables' => ['id' => (string) $newActId],
    ]);
    $respDel->assertStatus(200);
    expect($respDel->json('data.deleteFortnightActivity'))->toBeTrue();

    // Reset absence setting
    $resetQuery = 'mutation { saveWorkforceAbsenceSetting(threshold_days: 7) { threshold_days } }';
    $this->postJson('/api/graphql', ['query' => $resetQuery]);
});
