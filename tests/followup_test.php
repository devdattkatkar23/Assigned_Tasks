<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

$timezone = 'Asia/Kolkata';

$tests = [];

function addTest(
    array &$tests,
    string $name,
    string $expected,
    string $actual
): void {

    $tests[] = [
        'name' => $name,
        'expected' => $expected,
        'actual' => $actual,
        'passed' => $expected === $actual
    ];
}


/*
|--------------------------------------------------------------------------
| Test 1
|--------------------------------------------------------------------------
*/

$now = new DateTime(
    '2026-09-18 10:00:00',
    new DateTimeZone($timezone)
);

addTest(
    $tests,
    'Past follow-up',
    'Overdue',
    followUpStatus(
        '2026-09-17 10:00:00',
        $timezone,
        $now
    )
);


/*
|--------------------------------------------------------------------------
| Test 2
|--------------------------------------------------------------------------
*/

addTest(
    $tests,
    'Today follow-up',
    'Due Today',
    followUpStatus(
        '2026-09-18 15:00:00',
        $timezone,
        $now
    )
);


/*
|--------------------------------------------------------------------------
| Test 3
|--------------------------------------------------------------------------
*/

addTest(
    $tests,
    'Upcoming follow-up',
    'Upcoming',
    followUpStatus(
        '2026-09-19 10:00:00',
        $timezone,
        $now
    )
);


/*
|--------------------------------------------------------------------------
| Test 4
|--------------------------------------------------------------------------
*/

addTest(
    $tests,
    'No follow-up',
    'No Follow-up',
    followUpStatus(
        null,
        $timezone,
        $now
    )
);


/*
|--------------------------------------------------------------------------
| Test 5
|--------------------------------------------------------------------------
*/

addTest(
    $tests,
    'Empty follow-up',
    'No Follow-up',
    followUpStatus(
        '',
        $timezone,
        $now
    )
);


/*
|--------------------------------------------------------------------------
| Test 6
|--------------------------------------------------------------------------
| Midnight edge case
|--------------------------------------------------------------------------
*/

$midnight = new DateTime(
    '2026-09-18 00:00:00',
    new DateTimeZone($timezone)
);

addTest(
    $tests,
    'Midnight same day',
    'Due Today',
    followUpStatus(
        '2026-09-18 23:59:59',
        $timezone,
        $midnight
    )
);


/*
|--------------------------------------------------------------------------
| Test 7
|--------------------------------------------------------------------------
| Just before midnight
|--------------------------------------------------------------------------
*/

$beforeMidnight = new DateTime(
    '2026-09-18 23:59:59',
    new DateTimeZone($timezone)
);

addTest(
    $tests,
    'Next day after midnight',
    'Upcoming',
    followUpStatus(
        '2026-09-19 00:00:00',
        $timezone,
        $beforeMidnight
    )
);


/*
|--------------------------------------------------------------------------
| Test 8
|--------------------------------------------------------------------------
| Timezone edge case
|--------------------------------------------------------------------------
*/

$utcNow = new DateTime(
    '2026-09-18 04:30:00',
    new DateTimeZone('UTC')
);

addTest(
    $tests,
    'UTC converted to India timezone',
    'Due Today',
    followUpStatus(
        '2026-09-18 11:00:00',
        $timezone,
        $utcNow
    )
);


/*
|--------------------------------------------------------------------------
| Test 9
|--------------------------------------------------------------------------
*/

addTest(
    $tests,
    'Invalid date',
    'No Follow-up',
    followUpStatus(
        'not-a-date',
        $timezone,
        $now
    )
);


/*
|--------------------------------------------------------------------------
| Output
|--------------------------------------------------------------------------
*/

$total = count($tests);

$passed = 0;

foreach ($tests as $test) {

    if ($test['passed']) {
        $passed++;
    }

    echo
        ($test['passed'] ? 'PASS' : 'FAIL')
        . ' - '
        . $test['name']
        . ' | Expected: '
        . $test['expected']
        . ' | Actual: '
        . $test['actual']
        . '<br>';
}

echo "<hr>";

echo
    "Passed: {$passed}/{$total}<br>";

echo
    "Failed: "
    . ($total - $passed);