<?php
set_error_handler(function($level, $message, $file, $line) {
    throw new ErrorException($message, 0, $level, $file, $line);
});
require $argv[1] . '/Main/index.php';
$GLOBALS['enum_probe'] = [];
$score = $GLOBALS['Main_score'];
$scalar = $score(8);
$scoreCounts = $GLOBALS['enum_probe'];
$GLOBALS['enum_probe'] = [];
$build = $GLOBALS['Main_build'];
$partial = $build(8);
$tree = $partial(new \Main\Main_Red(), new \Main\Main_End());
$old = serialize($tree);
$extended = $build(2, new \Main\Main_Blue(), $tree);
$publicCounts = $GLOBALS['enum_probe'];
if (serialize($tree) !== $old) throw new RuntimeException('Old public root mutated');
$boxed = $tree instanceof \Main\Main_Link
    && ($tree->value0 instanceof \Main\Main_Red || $tree->value0 instanceof \Main\Main_Blue);
echo json_encode(['score'=>$scalar, 'public_tree'=>$old, 'public_boxed'=>$boxed,
    'public_weight'=>\Main\majMain_weight($tree),
    'extended_weight'=>\Main\majMain_weight($extended),
    'private_score_counts'=>$scoreCounts, 'public_build_counts'=>$publicCounts]) . "\n";
