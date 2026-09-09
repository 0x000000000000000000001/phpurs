<?php
// Compare public/private values after every insertion, then revisit old roots.
set_error_handler(function($level, $message, $file, $line) {
    throw new ErrorException($message, 0, $level, $file, $line);
});
require $argv[1].'/Test.RBTree/index.php';
$nullable = $argv[2] === 'nullable';
function check($condition, $message) {
    if (!$condition) throw new RuntimeException($message);
}
function inspectTree($t, $public) {
    $empty = $public
        ? $t instanceof \Test\RBTree\Test_RBTree_E
        : ($t === null || $t instanceof \Test\RBTree\Test_RBTree___phpurs_enum_0_E);
    if ($empty) return ['canonical'=>['E'], 'black'=>1, 'color'=>1, 'height'=>0, 'count'=>0, 'min'=>null, 'max'=>null];
    check($public ? $t instanceof \Test\RBTree\Test_RBTree_T : $t instanceof \Test\RBTree\Test_RBTree___phpurs_enum_0_T, 'Wrong tree representation');
    $color = $t->value0;
    if ($public) {
        check($color instanceof \Test\RBTree\Test_RBTree_R || $color instanceof \Test\RBTree\Test_RBTree_B, 'Public color changed');
        $color = $color instanceof \Test\RBTree\Test_RBTree_R ? 0 : 1;
    }
    check($color === 0 || $color === 1, 'Wrong color');
    $left = inspectTree($t->value1, $public);
    $right = inspectTree($t->value3, $public);
    check(is_int($t->value2), 'Non-integer key');
    check($left['count'] === 0 || $left['max'] < $t->value2, 'Left ordering');
    check($right['count'] === 0 || $right['min'] > $t->value2, 'Right ordering');
    check($left['black'] === $right['black'], 'Unequal black height');
    check($color !== 0 || ($left['color'] === 1 && $right['color'] === 1), 'Adjacent red nodes');
    return [
        'canonical'=>['T', $color, $left['canonical'], $t->value2, $right['canonical']],
        'black'=>$left['black'] + $color, 'color'=>$color,
        'height'=>1 + max($left['height'], $right['height']),
        'count'=>1 + $left['count'] + $right['count'],
        'min'=>$left['count'] ? $left['min'] : $t->value2,
        'max'=>$right['count'] ? $right['max'] : $t->value2,
    ];
}
function digest($value) { return hash('sha256', json_encode($value, JSON_THROW_ON_ERROR)); }
$orders = [[], range(1, 257), range(257, 1)];
mt_srand(260908);
$random = range(-128, 128);
shuffle($random);
$orders[] = $random;
$orders[] = array_merge($random, $random);
$fingerprints = [];
$publicFingerprints = [];
$insertions = 0;
$retainedCount = 0;
foreach ($orders as $order) {
    $public = $GLOBALS['Test_RBTree_E'];
    $private = $nullable ? null : new \Test\RBTree\Test_RBTree___phpurs_enum_0_E();
    $roots = [[$public, $private, digest(inspectTree($public, true)), serialize($public)]];
    check(\Test\RBTree\majTest_majRmajBmajTree___phpurs_enum_0_depth($private) === 0, 'Empty depth');
    $canonicalHash = hash_init('sha256');
    $publicHash = hash_init('sha256');
    $keys = [];
    foreach ($order as $key) {
        $public = \Test\RBTree\majTest_majRmajBmajTree_insert($key, $public);
        $private = \Test\RBTree\majTest_majRmajBmajTree___phpurs_enum_0_insert($key, $private);
        $a = inspectTree($public, true);
        $b = inspectTree($private, false);
        check($a === $b, 'Public/private structural mismatch');
        check($b['color'] === 1, 'Root must be black');
        $keys[$key] = true;
        check($b['count'] === count($keys), 'Lost key or duplicated key');
        check(\Test\RBTree\majTest_majRmajBmajTree___phpurs_enum_0_depth($private) === $b['height'], 'Private depth');
        $value = digest($a);
        hash_update($canonicalHash, $value);
        hash_update($publicHash, serialize($public));
        $roots[] = [$public, $private, $value, serialize($public)];
        ++$insertions;
    }
    foreach ($roots as [$oldPublic, $oldPrivate, $oldDigest, $oldSerialized]) {
        check(digest(inspectTree($oldPublic, true)) === $oldDigest, 'Old public root changed');
        check(digest(inspectTree($oldPrivate, false)) === $oldDigest, 'Old private root changed');
        check(serialize($oldPublic) === $oldSerialized, 'Old public serialization changed');
        ++$retainedCount;
    }
    $fingerprints[] = hash_final($canonicalHash);
    $publicFingerprints[] = hash_final($publicHash);
}
$partial = \Test\RBTree\majTest_majRmajBmajTree_insert(7);
check($partial instanceof Closure, 'Public curry representation');
$partialTree = $partial($GLOBALS['Test_RBTree_E']);
check(inspectTree($partialTree, true)['canonical'] === ['T',1,['E'],7,['E']], 'Public partial call');
$typeError = false;
try {
    new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $GLOBALS['Test_RBTree_E'], [], $GLOBALS['Test_RBTree_E']);
} catch (TypeError $e) { $typeError = true; }
check($typeError, 'Public key type check disappeared');
echo json_encode([
    'insertions'=>$insertions, 'retained_root_pairs'=>$retainedCount,
    'fingerprints'=>$fingerprints, 'public_serialization_fingerprints'=>$publicFingerprints,
    'red_black_invariants'=>true, 'public_partial_call'=>true, 'public_type_error'=>true,
]) . "\n";
