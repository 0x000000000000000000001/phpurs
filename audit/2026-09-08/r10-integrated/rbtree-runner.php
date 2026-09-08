<?php
// Exercise the existing generated RBTree action without changing its workload.
$output = realpath($argv[1] ?? '');
$mode = $argv[2] ?? 'single';
if ($output === false || !is_file($output . '/Test.RBTree/index.php')) {
    throw new RuntimeException('Pass the generated PHP output directory as argument 1');
}
set_error_handler(function($level, $message, $file, $line) {
    throw new ErrorException($message, 0, $level, $file, $line);
});
require_once $output . '/Test.RBTree/index.php';

function checkedAct() {
    $result = $GLOBALS['Test_RBTree_act']();
    if ($result !== '22') {
        throw new RuntimeException('Unexpected RBTree result: ' . var_export($result, true));
    }
    return $result;
}

$before = gc_status();
$started = hrtime(true);
if ($mode === 'bench') {
    // Keep the timing, warm-up and best-of-ten policy from Bench.purs.
    $bench = \Bench\majBench_runmajBench($GLOBALS['Test_RBTree_describe'], 'checkedAct');
    $best = $bench();
    $result = '22';
} elseif ($mode === 'full') {
    require $output . '/App/main.mod.php';
    $result = 'full-suite';
} elseif ($mode === 'sample') {
    for ($i = 0; $i < 3; $i++) checkedAct();
    echo "READY\n";
    flush();
    for ($i = 0; $i < (int)($argv[3] ?? 12); $i++) $result = checkedAct();
} elseif ($mode === 'single') {
    $result = checkedAct();
} elseif ($mode === 'validate') {
    // Compare exact tree structure against the original on diverse insertion orders.
    $orders = [[], range(1, 257), range(257, 1)];
    mt_srand(260908);
    $random = range(-128, 128);
    shuffle($random);
    $orders[] = $random;
    $orders[] = array_merge($random, $random);
    $fingerprints = [];
    foreach ($orders as $order) {
        $tree = new \Test\RBTree\Test_RBTree_E();
        $hash = hash_init('sha256');
        foreach ($order as $key) {
            $tree = \Test\RBTree\majTest_majRmajBmajTree_insert($key, $tree);
            hash_update($hash, json_encode($tree, JSON_THROW_ON_ERROR));
        }
        $fingerprints[] = hash_final($hash);
    }
    $result = $fingerprints;
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
    'audit_counts' => $GLOBALS['rbtree_audit_counts'] ?? null,
], JSON_UNESCAPED_SLASHES) . "\n";
