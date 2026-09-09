<?php
// Public references remain available; compare them with the actual private graph.
require $argv[1] . '/Test.Church/index.php';
$cases = 0;
foreach ([0, 1, 2, 3, 5, 7, 10] as $n) {
    $public = \Test\Church\majTest_majChurch_c100k($n);
    $private = \Test\Church\majTest_majChurch___phpurs_bind_0_c100k($n);
    foreach ([-11, 0, 17] as $seed) {
        $expected = $n ** 5;
        foreach ([$public, $private] as $producer) {
            $calls = 0;
            $f = function($x) use (&$calls) { ++$calls; return $x + 3; };
            $bound = $producer($f);
            if ($calls !== 0) throw new RuntimeException('early callback');
            for ($repeat = 1; $repeat <= 2; ++$repeat) {
                if ($bound($seed) !== $seed + 3 * $expected || $calls !== $repeat * $expected) {
                    throw new RuntimeException('count or retained result');
                }
            }
            $alternate = $producer(fn($x) => $x - 2);
            if ($alternate($seed) !== $seed - 2 * $expected || $bound($seed) !== $seed + 3 * $expected) {
                throw new RuntimeException('binding isolation');
            }
            if ($producer($f, $seed) !== $seed + 3 * $expected || $calls !== 4 * $expected) {
                throw new RuntimeException('saturated application');
            }
            ++$cases;
        }
    }
}
echo json_encode(['cases'=>$cases,'depths'=>[0,1,2,3,5,7,10], 'reused_bindings'=>true, 'call_counts'=>true, 'alternate_callbacks'=>true, 'saturated_calls'=>true]) . "\n";
