<?php
$output = realpath($argv[1]);
require $output . '/App/index.php';
$order = $argv[2];
$tests = $order === 'tree-first' ? ['RBTree', 'Church'] : ['Church', 'RBTree'];
$times = [];
foreach ($tests as $name) {
    $times[$name] = (\Bench\majBench_runmajBench($GLOBALS['Test_'.$name.'_describe'], $GLOBALS['Test_'.$name.'_act']))();
}
echo json_encode(['order'=>$order, 'times_us'=>$times, 'gc'=>gc_status(), 'jit'=>opcache_get_status(false)['jit']]) . "\n";
