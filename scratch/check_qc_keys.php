<?php

$en = json_decode(file_get_contents(__DIR__ . '/../resources/js/locales/en/app.json'), true) ?: [];

$qcKeys = [
    'qc_grade_extra', 'qc_grade_a', 'qc_grade_b', 'qc_grade_non_admis',
    'qc_decision_accepted_premium', 'qc_decision_accepted', 'qc_decision_accepted_restricted', 'qc_decision_rejected', 'qc_decision_approved', 'qc_decision_conditional',
    'qc_approval_pending', 'qc_approval_approved', 'qc_approval_rejected', 'qc_approval_needs_reinspection',
    'qc_skin_grade', 'qc_eye_grade', 'qc_gills_grade', 'qc_abdomen_grade', 'qc_backbone_grade', 'qc_organs_grade', 'qc_texture_grade', 'qc_odor_grade'
];

$missing = [];
foreach ($qcKeys as $k) {
    if (!isset($en[$k])) {
        $missing[] = $k;
    }
}

echo "Missing QC keys: " . count($missing) . "\n";
foreach ($missing as $m) {
    echo "  - $m\n";
}

