<?php
// Exercise the existing generated LazyEvaluation action without changing its workload.
$output = realpath($argv[1] ?? '');
$mode = $argv[2] ?? 'single';
if ($output === false || !is_file($output . '/Test.LazyEvaluation/index.php')) {
    throw new RuntimeException('Pass the generated PHP output directory as argument 1');
}
set_error_handler(function($level, $message, $file, $line) {
    throw new ErrorException($message, 0, $level, $file, $line);
});
require_once $output . '/Test.LazyEvaluation/index.php';

function checkedAct() {
    $result = $GLOBALS['Test_LazyEvaluation_act']();
    if ($result !== '1000000') {
        throw new RuntimeException('Unexpected LazyEvaluation result: ' . var_export($result, true));
    }
    return $result;
}

$before = gc_status();
$started = hrtime(true);
if ($mode === 'bench') {
    // Keep the timing, warm-up and best-of-ten policy from Bench.purs.
    $bench = \Bench\majBench_runmajBench($GLOBALS['Test_LazyEvaluation_describe'], 'checkedAct');
    $best = $bench();
    $result = '1000000';
} elseif ($mode === 'sample') {
    for ($i = 0; $i < 3; $i++) checkedAct();
    echo "READY\n";
    flush();
    for ($i = 0; $i < (int)($argv[3] ?? 12); $i++) $result = checkedAct();
} elseif ($mode === 'single') {
    $result = checkedAct();
} elseif ($mode === 'ffi') {
    $ffi = require __DIR__ . '/../../../../phpurs-foreign/src/Foreign.php';
    $thunk = \Test\LazyEvaluation\majTest_majLazymajEvaluation_buildmajThunks(3, fn($u) => 7);
    $result = ['typeOf' => $ffi['typeOf']($thunk), 'tagOf' => $ffi['tagOf']($thunk), 'value' => $thunk(null)];
} elseif ($mode === 'validate') {
    $values = [];
    foreach ([0, 1, 2, 17, 1000] as $depth) {
        foreach ([-3, 0, 11] as $seed) {
            $thunk = \Test\LazyEvaluation\majTest_majLazymajEvaluation_buildmajThunks($depth, fn($u) => $seed);
            $first = $thunk(null);
            $second = $thunk(null);
            if ($first !== $depth + $seed || $second !== $first) throw new RuntimeException('Wrong thunk value');
            $values[] = $first;
        }
    }
    $result = $values;
} else {
    throw new RuntimeException('Unknown mode: ' . $mode);
}
$status = function_exists('opcache_get_status') ? opcache_get_status(false) : false;
echo json_encode([
    'mode' => $mode,
    'result' => $result,
    'best_of_ten_us' => $best ?? null,
    'elapsed_ms' => (hrtime(true) - $started) / 1000000,
    'peak_memory_bytes' => memory_get_peak_usage(true),
    'gc_before' => $before,
    'gc_after' => gc_status(),
    'php' => PHP_VERSION,
    'xdebug_loaded' => extension_loaded('xdebug'),
    'jit' => $status['jit'] ?? null,
], JSON_UNESCAPED_SLASHES) . "\n";
