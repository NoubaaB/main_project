<?php
$keys = [
    'en' => [
        'shipping_categories_breakdown' => 'All Categories Shipping Breakdown',
        'categories_shipping_desc' => 'Consolidated total revenue and physical weight across all shipping categories',
        'all_shipping_categories' => 'All Shipping Categories',
        'breakdown_view' => 'Breakdown View',
        'both_breakdowns' => 'Both Breakdowns',
        'charges_breakdown' => 'Charges Breakdown',
        'shippings_breakdown' => 'Shipping Breakdown',
        'export_sales' => 'Export Sales',
        'local_sales' => 'Local Sales',
        'local_subcontracting' => 'Local / Other',
        'share_of_shippings' => 'Share of Total Shippings',
        'no_shipping_categories_match' => 'No shipping categories match your search or filter',
        'search_shipping_categories' => 'Search shipping categories...',
        'total_revenue' => 'Total Revenue',
        'domestic_sale' => 'Domestic Sale',
    ],
    'fr' => [
        'shipping_categories_breakdown' => "Répartition de Toutes les Catégories d'Expédition",
        'categories_shipping_desc' => "Revenu total et poids physique consolidés à travers toutes les catégories d'expédition",
        'all_shipping_categories' => "Toutes les catégories d'expédition",
        'breakdown_view' => 'Vue de répartition',
        'both_breakdowns' => 'Les deux répartitions',
        'charges_breakdown' => 'Répartition des charges',
        'shippings_breakdown' => 'Répartition des expéditions',
        'export_sales' => 'Ventes Export',
        'local_sales' => 'Ventes Locales',
        'local_subcontracting' => 'Local / Autre',
        'share_of_shippings' => 'Part du total des expéditions',
        'no_shipping_categories_match' => "Aucune catégorie d'expédition ne correspond à votre recherche ou filtre",
        'search_shipping_categories' => "Rechercher des catégories d'expédition...",
        'total_revenue' => 'Revenu Total',
        'domestic_sale' => 'Vente Locale',
    ],
    'es' => [
        'shipping_categories_breakdown' => 'Desglose de Todas las Categorías de Envíos',
        'categories_shipping_desc' => 'Ingresos totales consolidados y peso físico en todas las categorías de envío',
        'all_shipping_categories' => 'Todas las categorías de envío',
        'breakdown_view' => 'Vista de desglose',
        'both_breakdowns' => 'Ambos desgloses',
        'charges_breakdown' => 'Desglose de gastos',
        'shippings_breakdown' => 'Desglose de envíos',
        'export_sales' => 'Ventas de Exportación',
        'local_sales' => 'Ventas Locales',
        'local_subcontracting' => 'Local / Otro',
        'share_of_shippings' => 'Cuota del total de envíos',
        'no_shipping_categories_match' => 'Ninguna categoría de envío coincide con su búsqueda o filtro',
        'search_shipping_categories' => 'Buscar categorías de envío...',
        'total_revenue' => 'Ingresos Totales',
        'domestic_sale' => 'Venta Local',
    ],
];

foreach (['en', 'fr', 'es'] as $lang) {
    // 1. resources/js/locales/{lang}/app.json
    $path1 = __DIR__ . "/../resources/js/locales/{$lang}/app.json";
    if (file_exists($path1)) {
        $json = json_decode(file_get_contents($path1), true);
        foreach ($keys[$lang] as $k => $v) {
            $json[$k] = $v;
        }
        file_put_contents($path1, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n");
        echo "Updated $path1\n";
    }

    // 2. locals/{lang}.json
    $path2 = __DIR__ . "/../locals/{$lang}.json";
    if (file_exists($path2)) {
        $json2 = json_decode(file_get_contents($path2), true);
        foreach ($keys[$lang] as $k => $v) {
            $json2[$k] = $v;
        }
        file_put_contents($path2, json_encode($json2, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n");
        echo "Updated $path2\n";
    }
}

