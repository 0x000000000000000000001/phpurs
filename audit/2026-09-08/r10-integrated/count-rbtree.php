<?php
require $argv[1] . '/Test.RBTree/index.php';
// Exclude the three public module-initialization singletons from hot counts.
$GLOBALS['r10_counts'] = [];
$result = $GLOBALS['Test_RBTree_act']();
if ($result !== '22') throw new RuntimeException('Wrong RBTree result');
echo json_encode(['result'=>$result,'counts'=>$GLOBALS['r10_counts']]) . "\n";
