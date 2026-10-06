<?php
$module = $argv[2];
if (!in_array($module, ['ListOps','ArrayOps'], true)) throw new RuntimeException('unknown isolated module');
require $argv[1] . '/Test.' . $module . '/index.php';
$act = $GLOBALS['Test_' . $module . '_act'];
function sample($act, $iterations) {
    $started = hrtime(true);
    for ($i = 0; $i < $iterations; ++$i) {
        $result = $act();
        if ($result !== 202950) throw new RuntimeException('traversal result');
        $GLOBALS['phpurs_traversal_result'] = $result;
    }
    return (hrtime(true) - $started) / 1000;
}
for ($i = 0; $i < 3; ++$i) sample($act, 1);
$iterations = 1;
while (sample($act, $iterations) < 20000 && $iterations < 1048576) $iterations *= 2;
$samples = [];
for ($i = 0; $i < 11; ++$i) $samples[] = sample($act, $iterations) / $iterations;
$sorted = $samples;
sort($sorted);
$status = opcache_get_status(false);
if (!$status['opcache_enabled'] || !$status['jit']['on']) throw new RuntimeException('JIT/OPcache inactive');
echo json_encode(['module' => $module, 'output' => $GLOBALS['phpurs_traversal_result'], 'iterations' => $iterations,
    'samplesUs' => $samples, 'minUs' => $sorted[0], 'medianUs' => $sorted[5], 'iqrUs' => $sorted[8] - $sorted[2],
    'peakBytes' => memory_get_peak_usage(true), 'php' => PHP_VERSION, 'jit' => $status['jit'],
    'opcacheEnabled' => $status['opcache_enabled'], 'fileCache' => ini_get('opcache.file_cache'),
    'xdebugLoaded' => extension_loaded('xdebug')]), PHP_EOL;
