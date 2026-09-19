<?php

declare(strict_types=1);


/*
|--------------------------------------------------------------------------
| Escape HTML
|--------------------------------------------------------------------------
*/

function e(mixed $value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}


/*
|--------------------------------------------------------------------------
| Redirect
|--------------------------------------------------------------------------
*/

function redirect(string $url): never
{
    header("Location: {$url}");
    exit;
}


/*
|--------------------------------------------------------------------------
| Allowed statuses
|--------------------------------------------------------------------------
*/

function allowed_statuses(): array
{
    return [
        'New',
        'Contacted',
        'Qualified',
        'Converted',
        'Not Interested'
    ];
}


/*
|--------------------------------------------------------------------------
| Validate phone
|--------------------------------------------------------------------------
*/

function valid_phone(string $phone): bool
{
    /*
     * Allows:
     * +91 9876543210
     * 9876543210
     * 020-12345678
     * +1-555-123-4567
     *
     * Minimum 7 digits, maximum 15 digits.
     */

    $digits = preg_replace('/\D/', '', $phone);

    if ($digits === null) {
        return false;
    }

    $length = strlen($digits);

    return $length >= 7 && $length <= 15;
}


/*
|--------------------------------------------------------------------------
| Validate date
|--------------------------------------------------------------------------
*/

function valid_date(string $date): bool
{
    $parsed = DateTime::createFromFormat(
        'Y-m-d',
        $date
    );

    return $parsed !== false
        && $parsed->format('Y-m-d') === $date;
}


/*
|--------------------------------------------------------------------------
| Follow-up validation
|--------------------------------------------------------------------------
*/

function validate_followup_rules(
    string $status,
    ?string $followup
): array {

    $errors = [];

    $followup = $followup ?: null;

    /*
     * Qualified requires future follow-up.
     */
    if ($status === 'Qualified') {

        if ($followup === null) {

            $errors[] =
                'Qualified enquiries require a follow-up date.';

        } elseif (!valid_date($followup)) {

            $errors[] =
                'Follow-up date is invalid.';

        } else {

            $today = new DateTime('today');

            $followupDate = new DateTime($followup);

            if ($followupDate <= $today) {

                $errors[] =
                    'Qualified follow-up must be a future date.';
            }
        }
    }

    /*
     * Converted / Not Interested cannot have a future follow-up.
     */
    if (
        in_array(
            $status,
            ['Converted', 'Not Interested'],
            true
        )
        && $followup !== null
    ) {

        if (!valid_date($followup)) {

            $errors[] =
                'Follow-up date is invalid.';

        } else {

            $today = new DateTime('today');
            $followupDate = new DateTime($followup);

            if ($followupDate > $today) {

                $errors[] =
                    'Converted or Not Interested enquiries cannot have a future follow-up.';
            }
        }
    }

    return $errors;
}


/*
|--------------------------------------------------------------------------
| Validate enquiry data
|--------------------------------------------------------------------------
*/

function validate_enquiry(array $data): array
{
    $errors = [];

    $name = trim((string) ($data['name'] ?? ''));
    $email = trim((string) ($data['email'] ?? ''));
    $phone = trim((string) ($data['phone'] ?? ''));
    $company = trim((string) ($data['company'] ?? ''));
    $source = trim((string) ($data['source'] ?? ''));
    $requirement = trim((string) ($data['requirement'] ?? ''));
    $budget = $data['estimated_budget'] ?? '';
    $status = trim((string) ($data['status'] ?? ''));
    $followup = trim((string) ($data['next_followup_date'] ?? ''));

    /*
     * Required fields
     */

    if ($name === '') {
        $errors[] = 'Name is required.';
    }

    if ($email === '') {

        $errors[] = 'Email is required.';

    } elseif (!filter_var(
        $email,
        FILTER_VALIDATE_EMAIL
    )) {

        $errors[] = 'Please enter a valid email.';
    }

    if ($phone === '') {

        $errors[] = 'Phone is required.';

    } elseif (!valid_phone($phone)) {

        $errors[] = 'Please enter a valid phone number.';
    }

    if ($company === '') {
        $errors[] = 'Company is required.';
    }

    if ($source === '') {
        $errors[] = 'Source is required.';
    }

    if ($requirement === '') {
        $errors[] = 'Requirement is required.';
    }

    /*
     * Budget
     */

    if ($budget === '') {

        $errors[] = 'Estimated budget is required.';

    } elseif (
        !is_numeric($budget)
        || (float) $budget < 0
    ) {

        $errors[] =
            'Estimated budget must be a valid non-negative number.';
    }

    /*
     * Status
     */

    if (!in_array(
        $status,
        allowed_statuses(),
        true
    )) {

        $errors[] = 'Invalid status.';
    }

    /*
     * Follow-up
     */

    $followupValue = $followup === ''
        ? null
        : $followup;

    if (
        $followupValue !== null
        && !valid_date($followupValue)
    ) {

        $errors[] = 'Invalid follow-up date.';
    }

    /*
     * Business rules
     */

    if (in_array(
        $status,
        allowed_statuses(),
        true
    )) {

        $errors = array_merge(
            $errors,
            validate_followup_rules(
                $status,
                $followupValue
            )
        );
    }

    return $errors;
}


/*
|--------------------------------------------------------------------------
| Follow-up utility challenge
|--------------------------------------------------------------------------
*/

function followUpStatus(
    ?string $dateTime,
    string $timezone,
    ?DateTime $now = null
): string {

    if (
        $dateTime === null ||
        trim($dateTime) === ''
    ) {
        return 'No Follow-up';
    }

    try {

        $tz = new DateTimeZone($timezone);

        $followup = new DateTime(
            $dateTime,
            $tz
        );

        if ($now === null) {

            $now = new DateTime(
                'now',
                $tz
            );

        } else {

            $now = clone $now;

            $now->setTimezone($tz);
        }

        /*
         * Past date/time
         */
        if ($followup < $now) {
            return 'Overdue';
        }

        /*
         * Same calendar date
         */
        if (
            $followup->format('Y-m-d')
            === $now->format('Y-m-d')
        ) {
            return 'Due Today';
        }

        return 'Upcoming';

    } catch (Throwable $e) {

        return 'No Follow-up';
    }
}
