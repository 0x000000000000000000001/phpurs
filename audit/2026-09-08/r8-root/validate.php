<?php
$output = realpath($argv[1]);
set_error_handler(function($level, $message, $file, $line) {
    throw new ErrorException($message, 0, $level, $file, $line);
});
require $output . '/Test.LazyEvaluation/index.php';
$ffi = require __DIR__ . '/../../../../phpurs-foreign/src/Foreign.php';
$build = $GLOBALS['Test_LazyEvaluation_buildThunks'];
function check($condition, $message) { if (!$condition) throw new RuntimeException($message); }
function typedCall(Closure $f) { return $f(null); }
function errorFrom($action) {
    try { return ['unexpected' => $action()]; }
    catch (Throwable $error) { return ['type' => get_class($error), 'message' => $error->getMessage()]; }
}
$rows = [];
foreach ([0, 1, 2, 17, 1000] as $depth) {
    foreach ([-3, 0, 11] as $seed) {
        $base = fn($u) => $seed;
        $thunk = $build($depth, $base);
        check($thunk instanceof Closure, 'Public root must remain a Closure');
        if ($depth === 0) check($thunk === $base, 'Zero depth must return the original closure');
        $record = (object)['callback' => $thunk];
        $partial = $build($depth);
        $fromPartial = $partial($base);
        $actual = [$thunk(null), $thunk(null), typedCall($thunk), typedCall($record->callback),
                   $fromPartial(null), $build($depth, $base, null), $partial($base, null)];
        check($actual === array_fill(0, 7, $depth + $seed), 'Value/curry/capture changed');
        $tags = [$ffi['typeOf']($thunk), $ffi['tagOf']($thunk), $ffi['tagOf']($record->callback)];
        check($tags === ['function', 'Function', 'Function'], 'Foreign no longer recognizes root/callback');
        $rows[] = ['depth' => $depth, 'seed' => $seed, 'values' => $actual, 'tags' => $tags];
    }
}
// A public root can become the captured base of another chain.
$inner = $build(3, fn($u) => 7);
$outer = $build(2, $inner);
$extended = [$inner(null), $outer(null), $inner(null)];
check($extended === [10, 12, 10], 'Extending a public root changed either chain');
// Captured mutable state remains shared and deferred; independent chain depths.
$calls = 0;
$base = function($u) use (&$calls) { return ++$calls; };
$a = $build(17, $base); $b = $build(2, $base);
check($calls === 0, 'Construction forced the base');
$mutable = [$a(null), $a(null), $b(null), $a(null), $calls];
check($mutable === [18, 19, 5, 21, 4], 'Mutable capture changed');
// Keep the exact exception object and repeat the same action.
$sentinel = new RuntimeException('base failure');
$thrower = $build(17, function($u) use ($sentinel) { throw $sentinel; });
for ($i = 0; $i < 2; $i++) {
    try { $thrower(null); throw new LogicException('Exception was swallowed'); }
    catch (RuntimeException $error) { check($error === $sentinel, 'Exception identity changed'); }
}
$over = errorFrom(fn() => $build(2, fn($u) => 7)(null, null));
check($over['type'] === 'Error', 'Overapplication of an Int result must still fail');
// Do not wrap arbitrary inputs merely because they are callable at depth zero.
$external = new class { public function __invoke($u) { return 12; } };
check($build(0, $external) === $external, 'Unchanged external callable was converted');
echo json_encode(['cases' => $rows, 'extended' => $extended, 'mutable' => $mutable, 'exception_identity' => true,
                  'overapplication_error' => $over, 'zero_depth_identity' => true], JSON_PRETTY_PRINT) . "\n";
