<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/secondary_directory.php';

function archive_check(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

foreach (['acsee', 'csee', 'ftna'] as $level) {
    for ($year = 2010; $year <= 2023; $year++) {
        $base = et_secondary_base($level, $year);
        archive_check(
            $base === 'https://maktaba.tetea.org/exam-results/' . strtoupper($level) . $year . '/',
            strtoupper($level) . ' ' . $year . ' must use the historical archive'
        );
        $expectedDirectory = $base . ($level === 'ftna' ? 'ftna.htm' : 'index.htm');
        archive_check(
            et_secondary_directory_source($level, $year) === $expectedDirectory,
            strtoupper($level) . ' ' . $year . ' directory path must be deterministic'
        );
    }
}

echo "PASS: historical secondary archive routing covers ACSEE, CSEE and FTNA (2010-2023)\n";
