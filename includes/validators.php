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