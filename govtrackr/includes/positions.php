<?php
// includes/positions.php
// Central source of truth for positions, colleges, and abbreviations.

// ── POSITION ORDER ─────────────────────────────────────────
define('POSITIONS', [
    'University-Wide' => [
        'President',
        'Vice-President',
        'Director',
        'University Wide Senator',
    ],
    'Per College' => [
        'Governor',
        'Vice-Governor',
        'Councilor',
        'Senator',
    ],
]);

define('POSITION_ORDER', [
    'President',
    'Vice-President',
    'Director',
    'University Wide Senator',
    'Governor',
    'Vice-Governor',
    'Councilor',
    'Senator',
]);

// ── COLLEGES (full name => abbreviation) ───────────────────
define('COLLEGES', [
    'School of Business and Accountancy'             => 'SBA',
    'School of Engineering and Architecture'         => 'SEA',
    'School of Arts and Sciences'                    => 'SAS',
    'School of Education'                            => 'SED',
    'School of Hospitality and Tourism Management'   => 'SHTM',
    'School of Nursing and Allied Medical Sciences'  => 'SNAMS',
    'School of Computing'                            => 'SOC',
    'College of Criminal Justice Education and Forensics' => 'CCJEF',
]);

// Abbreviation => full name (reverse map)
define('COLLEGE_ABBR', array_flip(COLLEGES));

/**
 * Get full college name from abbreviation.
 * e.g. college_full('SOC') => 'School of Computing'
 */
function college_full(string $abbr): ?string {
    return COLLEGE_ABBR[$abbr] ?? null;
}

/**
 * Get abbreviation from full college name.
 * e.g. college_abbr('School of Computing') => 'SOC'
 */
function college_abbr(string $full): ?string {
    return COLLEGES[$full] ?? null;
}

/**
 * Returns a MySQL FIELD() expression for correct position ordering.
 */
function position_field_expr(string $col = 'c.position'): string {
    $escaped = array_map(fn($p) => "'" . addslashes($p) . "'", POSITION_ORDER);
    return "FIELD($col, " . implode(',', $escaped) . ")";
}
