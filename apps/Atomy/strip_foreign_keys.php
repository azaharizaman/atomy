<?php

/**
 * Migration Foreign Key & Index Stripper
 *
 * This script automates the process of commenting out foreign key and index definitions
 * from Laravel migration files to resolve PostgreSQL circular dependency issues.
 *
 * PURPOSE:
 * PostgreSQL requires all referenced tables to exist before creating foreign keys.
 * In complex ERP schemas with circular references (e.g., employees ↔ departments),
 * migrations fail with "relation does not exist" errors. This script separates
 * table creation from constraint creation by commenting out FK/index definitions.
 *
 * USAGE:
 * Run this script from the Atomy application root directory:
 *     php strip_foreign_keys.php
 *
 * PARAMETERS:
 * None. The script operates on hardcoded paths:
 *     - Source: database/migrations/*.php
 *     - Exclusion: 2025_12_31_999999_add_all_foreign_keys_and_indexes.php
 *
 * BEHAVIOR:
 * 1. Scans all migration files in database/migrations/
 * 2. Skips the consolidated foreign key migration file
 * 3. For each migration, identifies lines containing:
 *    - $table->foreign()
 *    - $table->foreignUlid()
 *    - $table->foreignId()
 *    - $table->index()
 *    - $table->unique()
 * 4. Comments out the entire definition (including multi-line definitions)
 * 5. Overwrites the original migration file with commented version
 *
 * ASSUMPTIONS:
 * - Migration files are valid PHP with standard Laravel schema builder syntax
 * - Foreign key/index definitions use the documented Laravel methods
 * - Multi-line definitions are terminated with a semicolon (;)
 * - The consolidated migration file name is exactly as specified
 *
 * EDGE CASES:
 * - Nested method calls: Only the outermost foreign()/index() call is detected
 * - Inline comments: Existing comments within definitions are preserved
 * - Empty lines: Whitespace and formatting are preserved
 * - Already commented: Re-running the script on commented lines is safe (no-op)
 *
 * WARNINGS:
 * - This script MODIFIES migration files in place (destructive operation)
 * - Ensure migrations are under version control before running
 * - The consolidated migration must manually define all stripped constraints
 * - Running migrations without the consolidated file will result in missing FKs/indexes
 *
 * MIGRATION ARCHITECTURE STRATEGY:
 * After running this script, the migration sequence becomes:
 * 1. Migrations 001-126: Create tables and columns only (no constraints)
 * 2. Migration 127 (consolidated): Add all foreign keys and indexes
 *
 * This approach ensures all tables exist before any foreign key references them,
 * eliminating circular dependency errors in PostgreSQL.
 *
 * @see ARCHITECTURAL_COMPLIANCE_SUMMARY.md - Migration refactor rationale
 * @see database/migrations/2025_12_31_999999_add_all_foreign_keys_and_indexes.php
 */

$migrationsDir = __DIR__ . '/database/migrations';
$consolidatedFile = '2025_12_31_999999_add_all_foreign_keys_and_indexes.php';

$files = glob($migrationsDir . '/*.php');

foreach ($files as $file) {
    $filename = basename($file);
    
    if ($filename === $consolidatedFile) {
        continue;
    }
    
    $content = file_get_contents($file);
    $lines = explode("\n", $content);
    $output = [];
    $skip = false;
    $skipUntilSemicolon = false;
    
    foreach ($lines as $line) {
        $trimmed = trim($line);
        
        // Check if this is the start of a foreign key or index definition
        if (preg_match('/\$table->(foreign|foreignUlid|foreignId|index|unique)\(/', $trimmed)) {
            // Check if it ends with semicolon on same line
            if (strpos($trimmed, ';') !== false) {
                // Single line - just comment it out
                $output[] = preg_replace('/^(\s*)/', '$1// ', $line);
            } else {
                // Multi-line - start skipping
                $skipUntilSemicolon = true;
                $output[] = preg_replace('/^(\s*)/', '$1// ', $line);
            }
            continue;
        }
        
        // If we're skipping a multi-line definition
        if ($skipUntilSemicolon) {
            $output[] = preg_replace('/^(\s*)/', '$1// ', $line);
            if (strpos($trimmed, ';') !== false) {
                $skipUntilSemicolon = false;
            }
            continue;
        }
        
        $output[] = $line;
    }
    
    file_put_contents($file, implode("\n", $output));
    echo "✓ Processed: $filename\n";
}

echo "\n✅ All migrations processed!\n";
