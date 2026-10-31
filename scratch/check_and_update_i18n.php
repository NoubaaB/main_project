<?php

$keysToCheck = [
    'tracking_badge_singular' => [
        'en' => 'Tracking N°',
        'es' => 'N° Seg.',
        'fr' => 'N° Suivi',
    ],
    'tracking_badge_plural' => [
        'en' => 'Tracking N°',
        'es' => 'N° Seg.',
        'fr' => 'N° Suivi',
    ],
    'chronological_events' => [
        'en' => 'Chronological Movements & Events',
        'es' => 'Movimientos y Eventos Cronológicos',
        'fr' => 'Événements & Mouvements Chronologiques',
    ],
    'all_events' => [
        'en' => 'All Events',
        'es' => 'Todos los Eventos',
        'fr' => 'Tous les Événements',
    ],
    'remaining_stock' => [
        'en' => 'Remaining Stock',
        'es' => 'Stock Restante',
        'fr' => 'Solde en Stock',
    ],
    'lots_badge' => [
        'en' => 'Tracking N°',
        'es' => 'N° Seg.',
        'fr' => 'N° Suivi',
    ],
    'empty' => [
        'en' => 'Empty',
        'es' => 'Vacío',
        'fr' => 'Vide',
    ],
    'article_type_name' => [
        'en' => 'Article Type',
        'es' => 'Tipo de Artículo',
        'fr' => 'Type d\'Article',
    ],
    'partner' => [
        'en' => 'Partner (Boat / Supplier / Client)',
        'es' => 'Socio (Barco / Proveedor / Cliente)',
        'fr' => 'Partenaire (Bateau / Fournisseur / Client)',
    ],
    'warehouse' => [
        'en' => 'Chamber / Warehouse',
        'es' => 'Cámara / Almacén',
        'fr' => 'Chambre / Entrepôt',
    ],
    'inflows' => [
        'en' => 'Inflows',
        'es' => 'Entradas',
        'fr' => 'Entrées',
    ],
    'transfers' => [
        'en' => 'Transfers',
        'es' => 'Transferencias',
        'fr' => 'Transferts',
    ],
    'production' => [
        'en' => 'Production Workshop',
        'es' => 'Taller Producción',
        'fr' => 'Atelier Production',
    ],
    'shippings' => [
        'en' => 'Shippings / Sales',
        'es' => 'Envíos / Ventas',
        'fr' => 'Expéditions / Ventes',
    ],
    'step_1_title' => [
        'en' => '1. Tracking N° & Entrance Vouchers',
        'es' => '1. N° Seg. y Vales de Entrada',
        'fr' => '1. N° Réception & Bons d\'Entrée',
    ],
    'step_1_desc' => [
        'en' => 'Identify entrance vouchers',
        'es' => 'Identificar los vales de entrada',
        'fr' => 'Identifier les bons d\'entrée',
    ],
    'step_2_title' => [
        'en' => '2. Article Selection',
        'es' => '2. Selección de Artículos',
        'fr' => '2. Choix des Articles',
    ],
    'step_2_desc' => [
        'en' => 'Select species & articles to trace',
        'es' => 'Seleccionar especies a rastrear',
        'fr' => 'Sélectionner espèces à tracer',
    ],
    'step_3_title' => [
        'en' => '3. Chronology & Audit',
        'es' => '3. Cronología y Auditoría',
        'fr' => '3. Chronologie & Audit',
    ],
    'step_3_desc' => [
        'en' => 'Full stock lifecycle journey',
        'es' => 'Recorrido completo del ciclo de vida',
        'fr' => 'Parcours complet du stock',
    ],
    'matching_entrance_vouchers' => [
        'en' => 'Detected Entrance Vouchers',
        'es' => 'Vales de Entrada Detectados',
        'fr' => 'Bons d\'Entrée Détectés',
    ],
    'select_voucher_to_continue' => [
        'en' => 'Click on a voucher to explore its articles',
        'es' => 'Haga clic en un vale para explorar sus artículos',
        'fr' => 'Cliquez sur un bon pour explorer ses articles',
    ],
];

foreach (['en', 'es', 'fr'] as $lang) {
    $paths = [
        "resources/js/locales/{$lang}/app.json",
        "locals/{$lang}.json"
    ];

    foreach ($paths as $p) {
        if (!file_exists($p)) continue;
        $json = json_decode(file_get_contents($p), true) ?: [];
        $updated = 0;
        foreach ($keysToCheck as $k => $trans) {
            $val = $trans[$lang] ?? null;
            if ($val !== null) {
                if (!isset($json[$k]) || $json[$k] !== $val) {
                    $json[$k] = $val;
                    $updated++;
                }
            }
        }
        file_put_contents($p, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
        echo "Updated {$updated} keys in {$p}\n";
    }
}
echo "Done!\n";

