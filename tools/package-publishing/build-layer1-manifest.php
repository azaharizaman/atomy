<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$packagesDir = $root . '/packages';
$outputDir = $root . '/docs/package-publishing';
$jsonOutput = $outputDir . '/nexus-layer1-packages.manifest.json';
$markdownOutput = $outputDir . '/nexus-layer1-packages.manifest.md';
$packagistVendor = getenv('PACKAGIST_VENDOR') ?: 'azaharizaman';
$githubOwner = getenv('GITHUB_OWNER') ?: 'azaharizaman';

if (! is_dir($packagesDir)) {
    fwrite(STDERR, "Missing packages directory: {$packagesDir}\n");
    exit(1);
}

$packages = [];
$seenNewNames = [];
$seenRepos = [];
$issues = [];

foreach (glob($packagesDir . '/*/composer.json') ?: [] as $composerPath) {
    $relativeComposerPath = substr($composerPath, strlen($root) + 1);
    $json = json_decode((string) file_get_contents($composerPath), true);

    if (! is_array($json)) {
        $issues[] = "{$relativeComposerPath}: invalid JSON";
        continue;
    }

    $currentName = $json['name'] ?? null;
    if (! is_string($currentName) || $currentName === '') {
        $issues[] = "{$relativeComposerPath}: missing composer package name";
        continue;
    }

    if (str_starts_with($currentName, 'nexus/')) {
        $oldSlug = substr($currentName, strlen('nexus/'));
        $oldName = $currentName;
    } elseif (str_starts_with($currentName, $packagistVendor . '/nexus-')) {
        $oldSlug = substr($currentName, strlen($packagistVendor . '/nexus-'));
        $oldName = 'nexus/' . $oldSlug;
    } else {
        $issues[] = "{$relativeComposerPath}: package name is not nexus/* or {$packagistVendor}/nexus-* ({$currentName})";
        continue;
    }

    $newSlug = 'nexus-' . $oldSlug;
    $newName = $packagistVendor . '/' . $newSlug;
    $repoName = $newSlug;

    if (isset($seenNewNames[$newName])) {
        $issues[] = "{$relativeComposerPath}: duplicate target package {$newName}";
    }

    if (isset($seenRepos[$repoName])) {
        $issues[] = "{$relativeComposerPath}: duplicate target repo {$repoName}";
    }

    $seenNewNames[$newName] = true;
    $seenRepos[$repoName] = true;

    $packagePath = dirname($relativeComposerPath);
    $packages[] = [
        'path' => $packagePath,
        'composer' => $relativeComposerPath,
        'current_name' => $currentName,
        'old_name' => $oldName,
        'new_name' => $newName,
        'github_owner' => $githubOwner,
        'github_repo' => $repoName,
        'github_url' => "https://github.com/{$githubOwner}/{$repoName}",
        'description' => $json['description'] ?? '',
        'type' => $json['type'] ?? 'library',
        'license' => $json['license'] ?? null,
    ];
}

usort($packages, static fn (array $a, array $b): int => strcmp($a['old_name'], $b['old_name']));

if ($issues !== []) {
    fwrite(STDERR, "Manifest validation failed:\n- " . implode("\n- ", $issues) . "\n");
    exit(1);
}

if (! is_dir($outputDir) && ! mkdir($outputDir, 0777, true) && ! is_dir($outputDir)) {
    fwrite(STDERR, "Could not create output directory: {$outputDir}\n");
    exit(1);
}

$manifest = [
    'generated_at' => gmdate('c'),
    'packagist_vendor' => $packagistVendor,
    'github_owner' => $githubOwner,
    'package_count' => count($packages),
    'packages' => $packages,
];

file_put_contents(
    $jsonOutput,
    json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL
);

$markdown = [
    '# Nexus Layer 1 Packagist Publishing Manifest',
    '',
    '| Path | Current Composer Name | Target Composer Name | GitHub Repo |',
    '|------|-----------------------|----------------------|-------------|',
];

foreach ($packages as $package) {
    $markdown[] = sprintf(
        '| `%s` | `%s` | `%s` | `%s` |',
        $package['path'],
        $package['old_name'],
        $package['new_name'],
        $package['github_repo']
    );
}

file_put_contents($markdownOutput, implode(PHP_EOL, $markdown) . PHP_EOL);

printf(
    "Wrote %d package entries\n- %s\n- %s\n",
    count($packages),
    substr($jsonOutput, strlen($root) + 1),
    substr($markdownOutput, strlen($root) + 1)
);
