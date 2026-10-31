<?php

$en = json_decode(file_get_contents(__DIR__ . '/../resources/js/locales/en/app.json'), true) ?: [];
$testKeys = [
    "qc_grade_extra", "qc_grade_a", "qc_grade_b", "qc_grade_rejected", "qc_grade_A", "qc_grade_B", "qc_grade_C",
    "qc_decision_approved", "qc_decision_conditional", "qc_decision_rejected",
    "qc_approval_pending", "qc_approval_approved", "qc_approval_rejected",
    "issue_status_open", "issue_status_in_progress", "issue_status_resolved", "issue_status_closed", "issue_status_updated",
    "light_mode", "dark_mode", "theme_mode", "theme",
    "click_to_view_day_activities", "average_per_worker", "add_new_activity_for_date", "search_by_worker_or_section",
    "try_adjusting_search", "click_add_activity_to_start", "delete_all_day_activities", "activity_recorded",
    "confirm_delete_all_day_activities", "all_activities_deleted",
    "Radar", "sync_items"
];
foreach ($testKeys as $k) {
    echo $k . ": " . (isset($en[$k]) ? "EXISTS (" . $en[$k] . ")" : "MISSING") . "\n";
}

