<?php
/**
 * Server-side validation helpers.
 * These mirror the rules enforced live in assets/js/validation.js —
 * the client-side checks are for UX only, these are the ones that count.
 */

function validate_name(string $value): bool
{
    // Letters, spaces, hyphens, apostrophes. 1-100 chars.
    return (bool) preg_match("/^[A-Za-zÀ-ÿ' -]{1,100}$/u", trim($value));
}

function validate_middle_initial(string $value): bool
{
    if ($value === '') {
        return true; // optional
    }
    return (bool) preg_match('/^[A-Za-z]{1,5}\.?$/', trim($value));
}

function validate_email_address(string $value): bool
{
    return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
}

function validate_free_text(string $value, int $min = 1, int $max = 191): bool
{
    $len = strlen(trim($value));
    return $len >= $min && $len <= $max;
}

function validate_telephone(string $value): bool
{
    $value = trim($value);
    if (!preg_match('/^[0-9+\-() ]{7,20}$/', $value)) {
        return false;
    }
    return strlen(preg_replace('/[^0-9]/', '', $value)) >= 7;
}

function validate_decimal(string $value, bool $allowEmpty = true): bool
{
    $value = trim($value);
    if ($value === '') {
        return $allowEmpty;
    }
    return (bool) preg_match('/^\d{1,15}(\.\d{1,2})?$/', $value);
}

function validate_integer(string $value, bool $allowEmpty = true): bool
{
    $value = trim($value);
    if ($value === '') {
        return $allowEmpty;
    }
    return (bool) preg_match('/^\d{1,9}$/', $value);
}

function validate_date(string $value, bool $allowEmpty = true): bool
{
    $value = trim($value);
    if ($value === '') {
        return $allowEmpty;
    }
    $d = DateTime::createFromFormat('Y-m-d', $value);
    return $d && $d->format('Y-m-d') === $value;
}

function validate_choice(string $value, array $choices): bool
{
    return in_array($value, $choices, true);
}

/**
 * Password rule: 8-20 characters, at least one uppercase letter,
 * one lowercase letter, and one number.
 * Returns an array of failed rule keys (empty array = valid).
 */
function password_rule_failures(string $password): array
{
    $failures = [];

    if (strlen($password) < 8 || strlen($password) > 20) {
        $failures[] = 'length';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $failures[] = 'upper';
    }
    if (!preg_match('/[a-z]/', $password)) {
        $failures[] = 'lower';
    }
    if (!preg_match('/[0-9]/', $password)) {
        $failures[] = 'number';
    }

    return $failures;
}