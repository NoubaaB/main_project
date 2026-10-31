<?php

$locales = ['en', 'fr', 'es'];
$translations = [];
foreach ($locales as $l) {
    $translations[$l] = json_decode(file_get_contents(__DIR__ . "/../locals/{$l}.json"), true) ?: [];
}

// Check the 27 static keys first:
$static27 = [
    'all_types',
    'blocked',
    'cannot_be_undone',
    'category_deleted',
    'charts',
    'city',
    'contact',
    'fish_process_types',
    'fixed_year_desc',
    'general_information',
    'must_be_positive',
    'navigate_to',
    'no_pages_found',
    'presets',
    'push_notifications_error',
    'rate_created',
    'rate_deleted',
    'rate_updated',
    'total_value_cleared',
    'total_weight_cleared',
    'upload',
    'validation_error',
    'visual_analytics',
    'warehouse_deleted',
    'website',
    'weighted_average_price',
    'years'
];

// Potential dynamic keys from analysis
$dynamicKeysToCheck = [
    'forget_password?',
    'invoice_n°',
    'present invoice',
    'shipper_/_exporter',
    'recipient_/_importer',
    'minutes_ago',
    'hours_ago',
    'days_ago',
    'male',
    'female',
    'item',
    'entity_created_title',
    'entity_created_message',
    'entity_updated_title',
    'entity_updated_message',
    'entity_deleted_title',
    'entity_deleted_message',
    'delete_suppilier_invoice',
    'full_name',
    'section_name',
    'daily_price',
    'team_name',
    'chef_of_team',
    'matricule',
    'corporals',
    'sections',
    'teams',
    'workforces',
];

// Let's also check all permission keys
// Find all permissions defined in the app
$files = [];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/../resources/js'));
foreach ($iterator as $f) {
    if ($f->isDir()) continue;
    $ext = pathinfo($f->getPathname(), PATHINFO_EXTENSION);
    if (in_array($ext, ['vue', 'js'])) $files[] = $f->getPathname();
}

$permKeys = [];
foreach ($files as $file) {
    $c = file_get_contents($file);
    if (preg_match_all('/(?:hasPermission|perm:)\s*[\'"]([a-zA-Z0-9_\.]+)[\'"]/', $c, $m)) {
        foreach ($m[1] as $p) {
            $permKeys['perm_' . str_replace('.', '_', $p)] = true;
        }
    }
    // Also permission groups
    if (preg_match_all('/group:\s*[\'"]([a-zA-Z0-9_]+)[\'"]/', $c, $m)) {
        foreach ($m[1] as $g) {
            $permKeys['perm_group_' . $g] = true;
        }
    }
}

$allToCheck = array_unique(array_merge($static27, $dynamicKeysToCheck, array_keys($permKeys)));

$missing = [];
foreach ($allToCheck as $k) {
    if (!isset($translations['en'][$k]) || $translations['en'][$k] === '') {
        $missing[] = $k;
    }
}

echo "Total potential keys tested: " . count($allToCheck) . "\n";
echo "Total missing from translations: " . count($missing) . "\n\n";

foreach ($missing as $m) {
    echo "  - {$m}\n";
}

