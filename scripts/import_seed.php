<?php
/**
 * scripts/import_seed.php
 *
 * Simple CLI script to import docs/database/seed.sql into the configured database.
 * Usage: php scripts/import_seed.php
 *
 * It loads DB config from includes/db_connect.php (which reads ../.env). Run this
 * on the server where PHP CLI and MySQL are available.
 */

chdir(__DIR__ . '/..'); // Make project root the working dir
require_once __DIR__ . '/../includes/db_connect.php';

$seedFile = __DIR__ . '/../docs/database/seed.sql';
if (!file_exists($seedFile)) {
    fwrite(STDERR, "Seed file not found: $seedFile\n");
    exit(2);
}

$sql = file_get_contents($seedFile);
if ($sql === false) {
    fwrite(STDERR, "Failed to read seed file\n");
    exit(2);
}

fwrite(STDOUT, "This will import '$seedFile' into database '{$db}'.\n");
fwrite(STDOUT, "Are you sure you want to continue? Type 'yes' to proceed: ");
$handle = fopen('php://stdin', 'r');
$line = trim(fgets($handle));
if (strtolower($line) !== 'yes') {
    fwrite(STDOUT, "Aborted by user.\n");
    exit(0);
}

// Use multi_query to execute the file. Some servers require splitting; we'll attempt multi_query first.
if ($conn->multi_query($sql)) {
    do {
        if ($res = $conn->store_result()) {
            $res->free();
        }
    } while ($conn->more_results() && $conn->next_result());

    if ($conn->errno) {
        fwrite(STDERR, "Import finished with errors: ({$conn->errno}) {$conn->error}\n");
        exit(1);
    }

    fwrite(STDOUT, "Seed import completed successfully.\n");
    exit(0);
} else {
    // Fallback: try to split statements by ';' — crude but works for many SQL dumps
    $stmts = array_filter(array_map('trim', explode(";\n", $sql)));
    foreach ($stmts as $stmt) {
        if ($stmt === '') continue;
        if (!$conn->query($stmt)) {
            fwrite(STDERR, "Error executing statement: ({$conn->errno}) {$conn->error}\n");
            fwrite(STDERR, "Failed statement snippet: " . substr($stmt,0,200) . "\n");
            exit(1);
        }
    }
    fwrite(STDOUT, "Seed import completed (fallback split).\n");
    exit(0);
}

