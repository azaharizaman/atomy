<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$manifestPath = $root . '/docs/package-publishing/nexus-layer1-packages.manifest.json';
$write = in_array('--write', $argv, true);

if (! is_file($manifestPath)) {
    fwrite(STDERR, "Missing manifest. Run tools/package-publishing/build-layer1-manifest.php first.\n");
    exit(1);
}

$manifest = json_decode((string) file_get_contents($manifestPath), true);
if (! is_array($manifest) || ! isset($manifest['packages']) || ! is_array($manifest['packages'])) {
    fwrite(STDERR, "Invalid manifest: {$manifestPath}\n");
    exit(1);
}

$nameMap = [];
foreach ($manifest['packages'] as $package) {
    $nameMap[$package['old_name']] = $package['new_name'];
}

$dependencySections = [
    'require',
    'require-dev',
    'conflict',
    'replace',
    'provide',
    'suggest',
];

$changedFiles = [];
$iterator = new RecursiveIteratorIterator(
    new RecursiveCallbackFilterIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        static function (SplFileInfo $file): bool {
            if (! $file->isDir()) {
                return true;
            }

            return ! in_array($file->getFilename(), ['.git', '.worktrees', 'vendor', 'node_modules'], true);
        }
    )
);

foreach ($iterator as $file) {
    if (! $file instanceof SplFileInfo || $file->getFilename() !== 'composer.json') {
        continue;
    }

    $path = $file->getPathname();
    $relativePath = substr($path, strlen($root) + 1);
    $data = json_decode((string) file_get_contents($path), true);

    if (! is_array($data)) {
        fwrite(STDERR, "Skipping invalid JSON: {$relativePath}\n");
        continue;
    }

    $changed = false;

    if (isset($data['name'], $nameMap[$data['name']])) {
        $data['name'] = $nameMap[$data['name']];
        $changed = true;
    }

    foreach ($dependencySections as $section) {
        if (! isset($data[$section]) || ! is_array($data[$section])) {
            continue;
        }

        $rewritten = [];
        foreach ($data[$section] as $dependencyName => $constraint) {
            $targetName = $nameMap[$dependencyName] ?? $dependencyName;
            $rewritten[$targetName] = $constraint;
            $changed = $changed || $targetName !== $dependencyName;
        }

        $data[$section] = $rewritten;
    }

    if (! $changed) {
        continue;
    }

    $changedFiles[] = $relativePath;

    if ($write) {
        file_put_contents(
            $path,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL
        );
    }
}

if ($changedFiles === []) {
    echo "No composer package-name changes needed.\n";
    exit(0);
}

echo ($write ? 'Updated' : 'Would update') . ' ' . count($changedFiles) . " composer.json files:\n";
foreach ($changedFiles as $changedFile) {
    echo "- {$changedFile}\n";
}

if (! $write) {
    echo "\nDry run only. Re-run with --write to modify files.\n";
}
