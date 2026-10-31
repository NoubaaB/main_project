<?php
$files = [
    'fr' => [
        'resources/js/locales/fr/app.json',
        'locals/fr.json',
    ],
    'en' => [
        'resources/js/locales/en/app.json',
        'locals/en.json',
    ],
    'es' => [
        'resources/js/locales/es/app.json',
        'locals/es.json',
    ]
];

$translations = [
    'fr' => [
        'voucher_type_charge' => 'Charge / Réception',
        'voucher_type_production' => 'Production',
        'voucher_type_inventory' => 'Inventaire',
        'voucher_type_transfer' => 'Transfert',
        'deselect_all' => 'Tout Désélectionner',
        'initial_weight' => 'Poids Reçu',
        'lot_age' => 'Ancienneté',
        'notice' => 'Remarque',
        'others' => 'autres',
        'warning' => 'Attention',
    ],
    'en' => [
        'voucher_type_charge' => 'Inbound Charge / Reception',
        'voucher_type_production' => 'Production',
        'voucher_type_inventory' => 'Inventory',
        'voucher_type_transfer' => 'Transfer',
        'deselect_all' => 'Deselect All',
        'initial_weight' => 'Received Weight',
        'lot_age' => 'Age',
        'notice' => 'Notice',
        'others' => 'others',
        'warning' => 'Warning',
    ],
    'es' => [
        'voucher_type_charge' => 'Carga / Recepción',
        'voucher_type_production' => 'Producción',
        'voucher_type_inventory' => 'Inventario',
        'voucher_type_transfer' => 'Transferencia',
        'deselect_all' => 'Deseleccionar Todo',
        'initial_weight' => 'Peso Recibido',
        'lot_age' => 'Antigüedad',
        'notice' => 'Aviso',
        'others' => 'otros',
        'warning' => 'Advertencia',
    ],
];

foreach ($files as $lang => $paths) {
    foreach ($paths as $path) {
        if (!file_exists($path)) {
            echo "Skipping non-existent: $path\n";
            continue;
        }
        $data = json_decode(file_get_contents($path), true) ?: [];
        $added = 0;
        foreach ($translations[$lang] as $k => $v) {
            if (!isset($data[$k])) {
                $data[$k] = $v;
                $added++;
            }
        }
        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo "Updated $path with $added new keys.\n";
    }
}

