<?php
$locales = [
    'res_fr' => 'resources/js/locales/fr/app.json',
    'res_en' => 'resources/js/locales/en/app.json',
    'res_es' => 'resources/js/locales/es/app.json',
    'loc_fr' => 'locals/fr.json',
    'loc_en' => 'locals/en.json',
    'loc_es' => 'locals/es.json',
];

$test = [
    'voucher_type_charge', 'voucher_type_production', 'voucher_type_inventory', 'voucher_type_transfer',
    'direction_inflow', 'direction_outflow', 'direction_transfer',
    'event_inflow_charge', 'event_inflow_production', 'event_inflow_transfer', 'event_inflow_inventory', 'event_inflow_default',
    'event_outflow_shipping', 'event_outflow_production', 'event_outflow_transfer', 'event_outflow_reset', 'event_outflow_default',
    'partner_inventory', 'partner_factory', 'partner_outflow', 'partner_processing_workshop',
    'deselect_all', 'initial_weight', 'lot_age', 'notice', 'others', 'warning'
];

foreach ($locales as $name => $path) {
    $dict = json_decode(file_get_contents($path), true) ?: [];
    echo "=== $name ===" . PHP_EOL;
    foreach ($test as $k) {
        if (!isset($dict[$k])) {
            echo "  MISSING: $k" . PHP_EOL;
        }
    }
}

