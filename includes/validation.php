<?php

function grf_is_valid_exam_year(mixed $year, int $minimumYear = 2010, int $maximumYear = 2026): bool
{
    return is_int($year) && $year >= $minimumYear && $year <= $maximumYear;
}

function grf_is_valid_secondary_candidate(string $candidate): bool
{
    return preg_match('/^(?:[SP]Q?\d{4}\/\d{4})$/i', $candidate) === 1;
}

function grf_is_valid_primary_candidate(string $candidate): bool
{
    return preg_match('/^PS\d{7}-\d{3,4}$/i', $candidate) === 1;
}
