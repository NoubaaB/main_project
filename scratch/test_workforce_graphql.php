<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $schema = App\GraphQL\AppSchema::build(true);
    $rootValue = App\GraphQL\AppResolver::getRootValue();

    $tests = [
        'workforceAbsences' => '
            query {
                workforceAbsences(threshold: 7) {
                    threshold_days
                    reference_date
                    summary {
                        total_workforces
                        active_count
                        absent_count
                        left_company_count
                    }
                    workforces {
                        id
                        full_name
                        status
                        days_since_last_activity
                    }
                }
            }
        ',
        'workforceStatistics' => '
            query {
                workforceStatistics(date_start: "2026-01-01", date_end: "2026-10-31") {
                    grand_total
                    total_activities
                    daily {
                        date
                        total
                        count
                    }
                    sections {
                        id
                        name
                        total
                    }
                }
            }
        ',
        'workforceMovementStatistics' => '
            query {
                workforceMovementStatistics(start_date: "2026-01-01", end_date: "2026-10-31", granularity: "monthly") {
                    granularity
                    summary {
                        total_activities
                        total_amount
                        distinct_workforces_count
                    }
                }
            }
        ',
        'fortnightSummary' => '
            query {
                fortnightSummary(year: 2026, month: 10) {
                    first_fortnight { total working_days }
                    second_fortnight { total working_days }
                    month_total
                }
            }
        ',
        'fortnightDailyTotals' => '
            query {
                fortnightDailyTotals(year: 2026, month: 10) {
                    date
                    total_price
                    count
                }
            }
        ',
        'fortnightBreakdown' => '
            query {
                fortnightBreakdown(year: 2026, month: 10) {
                    sections { id name month_total }
                    corporals { id name month_total }
                    workforces { id name month_total }
                }
            }
        '
    ];

    foreach ($tests as $name => $gql) {
        $res = GraphQL\GraphQL::executeQuery($schema, $gql, $rootValue, ['user' => null]);
        $arr = $res->toArray();
        if (!empty($arr['errors'])) {
            echo "FAILED {$name}: " . json_encode($arr['errors']) . "\n";
        } else {
            echo "PASSED {$name}\n";
        }
    }
} catch (\Throwable $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
}
