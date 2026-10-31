<?php

$newKeys = [
    'en' => [
        'workforce_print_modal_title' => 'Workforce Statistics Print Layout',
        'workforce_print_modal_sub' => 'Customize sections, columns, orientation and display options before printing',
        'official_workforce_report' => 'Official Workforce Report',
        'full_summary' => 'Full Summary',
        'daily_evolution' => 'Daily Evolution',
        'sections_breakdown' => 'Sections Breakdown',
        'corporals_breakdown' => 'Corporals Breakdown',
        'workforces_ranking' => 'Workforce Ranking',
        'movement_tracker' => 'Movement Tracker',
        'daily_evolution_sub' => 'Summary of daily presences and costs',
        'sections_sub' => 'Breakdown of costs and working days by workshop / section',
        'corporals_sub' => 'Breakdown of expenses and workforce supervised by corporal',
        'workforces_sub' => 'Honor roll, headcounts, attendance and allocated amounts',
        'tracker_sub' => 'Chronological audit of arrivals, departures and assignments',
        'full_summary_sub' => 'Executive summary of staff headcounts and labor costs',
        'workforce_module_system' => 'Workforce Management System',
        'visa_hr_manager' => 'Visa HR / Management',
    ],
    'fr' => [
        'workforce_print_modal_title' => 'Mise en Page d\'Impression des Statistiques de Main-d\'œuvre',
        'workforce_print_modal_sub' => 'Personnalisez les sections, colonnes, l\'orientation et les options d\'affichage avant impression',
        'official_workforce_report' => 'Rapport Officiel de Main-d\'œuvre',
        'full_summary' => 'Synthèse Complète',
        'daily_evolution' => 'Évolution Journalière',
        'sections_breakdown' => 'Répartition par Section',
        'corporals_breakdown' => 'Répartition par Caporal',
        'workforces_ranking' => 'Classement des Ouvriers',
        'movement_tracker' => 'Suivi des Mouvements',
        'daily_evolution_sub' => 'Récapitulatif des présences et des coûts journaliers',
        'sections_sub' => 'Répartition des coûts et journées de travail par atelier / section',
        'corporals_sub' => 'Répartition des charges et effectifs supervisés par caporal',
        'workforces_sub' => 'Tableau d\'honneur, effectifs, assiduité et montants attribués',
        'tracker_sub' => 'Audit chronologique des arrivées, départs et affectations',
        'full_summary_sub' => 'Synthèse exécutive globale des effectifs et des coûts salariaux',
        'workforce_module_system' => 'Système de Gestion de la Main-d\'œuvre',
        'visa_hr_manager' => 'Visa Direction / Ressources Humaines',
    ],
    'es' => [
        'workforce_print_modal_title' => 'Diseño de Impresión de Estadísticas de Mano de Obra',
        'workforce_print_modal_sub' => 'Personalice secciones, columnas, orientación y opciones de visualización antes de imprimir',
        'official_workforce_report' => 'Informe Oficial de Mano de Obra',
        'full_summary' => 'Resumen Completo',
        'daily_evolution' => 'Evolución Diaria',
        'sections_breakdown' => 'Desglose por Secciones',
        'corporals_breakdown' => 'Desglose por Capataces',
        'workforces_ranking' => 'Clasificación de Trabajadores',
        'movement_tracker' => 'Seguimiento de Movimientos',
        'daily_evolution_sub' => 'Resumen de asistencias y costes diarios',
        'sections_sub' => 'Desglose de costes y días laborables por taller / sección',
        'corporals_sub' => 'Desglose de gastos y personal supervisado por capataz',
        'workforces_sub' => 'Cuadro de honor, plantilla, asistencia y cantidades asignadas',
        'tracker_sub' => 'Auditoría cronológica de llegadas, salidas y asignaciones',
        'full_summary_sub' => 'Resumen ejecutivo global de plantilla y costes laborales',
        'workforce_module_system' => 'Sistema de Gestión de Mano de Obra',
        'visa_hr_manager' => 'Firma Dirección / Recursos Humanos',
    ],
];

$files = [
    'en' => ['resources/js/locales/en/app.json', 'locals/en.json'],
    'fr' => ['resources/js/locales/fr/app.json', 'locals/fr.json'],
    'es' => ['resources/js/locales/es/app.json', 'locals/es.json'],
];

foreach ($files as $lang => $paths) {
    foreach ($paths as $path) {
        if (!file_exists($path)) continue;
        $data = json_decode(file_get_contents($path), true);
        $count = 0;
        foreach ($newKeys[$lang] as $k => $v) {
            if (!isset($data[$k])) {
                $data[$k] = $v;
                $count++;
            }
        }
        ksort($data);
        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo "Updated $path: added $count keys.\n";
    }
}

