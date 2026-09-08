<?php
require $argv[1] . '/Test.RBTree/index.php';
$empty = $GLOBALS['Test_RBTree_E'];
$tree = \Test\RBTree\majTest_majRmajBmajTree_insert(1, $empty);
$second = \Test\RBTree\majTest_majRmajBmajTree_insert(2, $empty);
$observations = [
    'empty_is_E' => $empty instanceof \Test\RBTree\Test_RBTree_E,
    'color_is_B' => $tree->value0 instanceof \Test\RBTree\Test_RBTree_B,
    'left_is_E' => $tree->value1 instanceof \Test\RBTree\Test_RBTree_E,
    'tree_is_T' => $tree instanceof \Test\RBTree\Test_RBTree_T,
    'distinct_roots_share_color' => $tree->value0 === $second->value0,
    'serialized_tree' => json_encode($tree, JSON_THROW_ON_ERROR)
];
echo json_encode($observations) . "\n";
