<?php

declare(strict_types=1);

$paths = array_values(array_filter(array_slice($argv, 1), static fn (string $a): bool => !str_starts_with($a, '--')));
if (count($paths) !== 2) {
    fwrite(STDERR, "Usage: php compare.php <base.json|run-dir> <new.json|run-dir> [--threshold=5]\n");
    exit(2);
}

$threshold = 5.0;
foreach ($argv as $argument) {
    if (str_starts_with($argument, '--threshold=')) {
        $threshold = (float) substr($argument, 12);
    }
}

$load = static function (string $path): array {
    $file = is_dir($path) ? rtrim($path, '/') . '/results.json' : $path;
    $data = json_decode((string) file_get_contents($file), true, flags: JSON_THROW_ON_ERROR);
    $rows = [];
    foreach ($data['results'] as $row) {
        $rows[$row['benchmark'] . '|' . $row['mode'] . '|' . ($row['step'] ?? '-')] = $row;
    }

    return [$data['environment'], $rows];
};

[$baseEnv, $base] = $load($paths[0]);
[$newEnv, $new] = $load($paths[1]);

printf("base: %s %s (dirty: %s)\nnew:  %s %s (dirty: %s)\n", $baseEnv['commit'], $baseEnv['date'], $baseEnv['dirty'], $newEnv['commit'], $newEnv['date'], $newEnv['dirty']);
foreach (['php', 'sapi', 'os', 'cpus', 'cpu_model', 'container', 'filesystem', 'opcache', 'jit', 'xdebug', 'mode'] as $key) {
    if (($baseEnv[$key] ?? null) !== ($newEnv[$key] ?? null)) {
        printf("WARNING: environment differs in %s: %s vs %s — numbers are not comparable\n", $key, $baseEnv[$key] ?? '?', $newEnv[$key] ?? '?');
    }
}

$delta = static fn (float $before, float $after): float => $before == 0.0 ? 0.0 : ($after - $before) / $before * 100;
$regressions = 0;

$row = "%-40s %-6s %-12s %-12s %-9s %-7s %-9s %-9s %-12s %-12s %s\n";
printf("\n" . $row, 'Benchmark', 'Step', 'Base', 'New', 'Δ time', 'Noise', 'Δ CPU', 'Δ alloc', 'Δ retained', 'Δ read/call', 'Verdict');
foreach ($base as $key => $before) {
    if (!isset($new[$key])) {
        continue;
    }
    $after = $new[$key];

    if ($before['mode'] === 'growth') {
        $verdict = !$after['plateau'] && $before['plateau'] ? 'REGRESSION' : ($after['plateau'] ? 'plateau' : 'grows');
        $regressions += $verdict === 'REGRESSION' ? 1 : 0;
        printf($row, $before['benchmark'], '-', sprintf('%.2f B/c', $before['bytesPerCall']), sprintf('%.2f B/c', $after['bytesPerCall']), '-', '-', '-', '-', '-', '-', $verdict);
        continue;
    }

    $timeDelta = $delta((float) $before['time']['median'], (float) $after['time']['median']);
    $noise = max($before['time']['noise'] ?? 0.0, $after['time']['noise'] ?? 0.0) * 100;
    $allocDelta = $delta((float) $before['memory']['allocated'], (float) $after['memory']['allocated']);
    $retainedDelta = $after['memory']['retained'] - $before['memory']['retained'];
    $cpuDelta = isset($before['cpu'], $after['cpu'])
        ? sprintf('%+.1f%%', $delta((float) $before['cpu']['perCall'], (float) $after['cpu']['perCall']))
        : '-';
    $readDelta = isset($before['io'], $after['io'])
        ? sprintf('%+.0f B', $after['io']['readPerCall'] - $before['io']['readPerCall'])
        : '-';

    $timeSignificant = abs($timeDelta) > max($threshold, $noise);
    $verdict = match (true) {
        $timeSignificant && $timeDelta > 0, $allocDelta > $threshold => 'REGRESSION',
        $timeSignificant => 'faster',
        default => '~',
    };
    $regressions += $verdict === 'REGRESSION' ? 1 : 0;

    printf(
        $row,
        $before['benchmark'],
        $before['step'],
        sprintf('%.4f ms', $before['time']['median']),
        sprintf('%.4f ms', $after['time']['median']),
        sprintf('%+.1f%%', $timeDelta),
        sprintf('%.1f%%', $noise),
        $cpuDelta,
        sprintf('%+.1f%%', $allocDelta),
        sprintf('%+d B', $retainedDelta),
        $readDelta,
        $verdict,
    );
}

exit($regressions > 0 ? 1 : 0);
