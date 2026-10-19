<?php
$variant = $argv[1];
$root = $argv[2];
require $root . '/' . $variant . '/Test.Records/index.php';
$act = $GLOBALS['Test_Records_act'];
function sample($act, $iterations) {
    $started = hrtime(true);
    for ($i = 0; $i < $iterations; ++$i) {
        $result = $act();
        if ($result !== 20000) throw new RuntimeException('result ' . $result);
        $GLOBALS['phpurs_r5_result'] = $result;
    }
    return (hrtime(true) - $started) / 1000;
}
for ($i = 0; $i < 3; ++$i) sample($act, 1);
$iterations = 1;
while (sample($act, $iterations) < 20000 && $iterations < 1048576) $iterations *= 2;
$samples = [];
for ($i = 0; $i < 11; ++$i) $samples[] = sample($act, $iterations) / $iterations;
$sorted = $samples; sort($sorted);
$status = opcache_get_status(false);
if (!$status['opcache_enabled'] || !$status['jit']['on']) throw new RuntimeException('JIT/OPcache inactive');
echo json_encode(['variant' => $variant, 'output' => $GLOBALS['phpurs_r5_result'], 'iterations' => $iterations,
    'samplesUs' => $samples, 'medianUs' => $sorted[5], 'minUs' => $sorted[0], 'iqrUs' => $sorted[8] - $sorted[2]]), PHP_EOL;
