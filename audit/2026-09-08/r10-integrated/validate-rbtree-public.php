<?php
// Compare canonical values across representations, including retained old roots.
set_error_handler(function($level, $message, $file, $line) {
    throw new ErrorException($message, 0, $level, $file, $line);
});
require $argv[1] . '/Test.RBTree/index.php';
function canonical($t) {
    if ($t === null || $t instanceof \Test\RBTree\Test_RBTree_E) return ['E'];
    if (!($t instanceof \Test\RBTree\Test_RBTree_T)) throw new RuntimeException('Bad tree');
    $c = $t->value0;
    if (!($c instanceof \Test\RBTree\Test_RBTree_R || $c instanceof \Test\RBTree\Test_RBTree_B)) throw new RuntimeException('Public color representation changed');
    if ($c instanceof \Test\RBTree\Test_RBTree_R) $c = 0;
    if ($c instanceof \Test\RBTree\Test_RBTree_B) $c = 1;
    if ($c !== 0 && $c !== 1) throw new RuntimeException('Bad color');
    return ['T', $c, canonical($t->value1), $t->value2, canonical($t->value3)];
}
function digest($tree) { return hash('sha256', json_encode(canonical($tree), JSON_THROW_ON_ERROR) . serialize($tree)); }
$orders = [[], range(1, 257), range(257, 1)];
mt_srand(260908);
$random = range(-128, 128);
shuffle($random);
$orders[] = $random;
$orders[] = array_merge($random, $random);
$results = [];
foreach ($orders as $order) {
    $tree = $GLOBALS['Test_RBTree_E'];
    $roots = [$tree];
    $digests = [digest($tree)];
    $hash = hash_init('sha256');
    foreach ($order as $key) {
        $tree = \Test\RBTree\majTest_majRmajBmajTree_insert($key, $tree);
        $value = digest($tree);
        hash_update($hash, $value);
        $roots[] = $tree;
        $digests[] = $value;
    }
    foreach ($roots as $i => $old) {
        if (digest($old) !== $digests[$i]) throw new RuntimeException('An old root changed');
    }
    $results[] = hash_final($hash);
}
echo json_encode(['fingerprints' => $results, 'insertions' => 1285, 'retained_roots_unchanged' => true]) . "\n";
