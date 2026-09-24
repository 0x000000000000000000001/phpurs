<?php
// Mesure isolée de RBTree pour comparer des variantes de la classe privée.
// Usage : php bench-rbtree.php <répertoire contenant Test.RBTree/index.php>
$dir = rtrim($argv[1], '/');
require_once "$dir/Test.RBTree/index.php";
$act = $GLOBALS['Test_RBTree_act'];
$expected = null;
for ($i = 0; $i < 3; $i++) { $expected = $act(); }
$times = [];
for ($i = 0; $i < 12; $i++) {
    $t = hrtime(true);
    $r = $act();
    $times[] = (hrtime(true) - $t) / 1e6;
    if ($r !== $expected) { fwrite(STDERR, "unstable result\n"); exit(1); }
}
sort($times);
printf("%s median=%.3f min=%.3f max=%.3f\n", basename($dir), $times[6], $times[0], $times[11]);
