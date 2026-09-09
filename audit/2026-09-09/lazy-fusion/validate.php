<?php
set_error_handler(function($level, $message, $file, $line) {
    throw new ErrorException($message, 0, $level, $file, $line);
});
require $argv[1] . '/Test.LazyEvaluation/index.php';
$builder = $GLOBALS['Test_LazyEvaluation_buildThunks'];
$original = fn($n, $seed, $unit) => $builder($n, $seed, $unit);
$fused = '\\Test\\LazyEvaluation\\phpurs_probe_force_chain';
$unit = $GLOBALS['Data_Unit_unit'];
function ensure($test, $message) { if (!$test) throw new RuntimeException($message); }
function observations($force, $unit) {
    $numbers = [];
    foreach ([0,1,2,17,127,1000] as $n) {
        foreach ([-3,0,11,2147483646,PHP_INT_MAX-1] as $seed) {
            $value = $force($n, fn($_) => $seed, $unit);
            $numbers[] = [get_debug_type($value), serialize($value)];
        }
    }
    $events = [];
    $arg = function($tag, $value) use (&$events) { $events[] = $tag; return $value; };
    $seed = function($received) use (&$events, $unit) {
        $events[] = ['seed', $received === $unit]; return 8;
    };
    $value = $force($arg('depth',17), $arg('seed argument',$seed), $arg('unit argument',new stdClass()));
    $events[] = ['result', $value];
    $calls = 0;
    $exception = new RuntimeException('seed exception', 71);
    try {
        $force(17, function($_) use (&$calls,$exception) { ++$calls; throw $exception; }, $unit);
        throw new RuntimeException('Exception disappeared');
    } catch (RuntimeException $caught) {
        ensure($caught === $exception, 'Exception identity changed');
    }
    $units = [];
    foreach ([0,1,17] as $n) {
        $foreignUnit = new stdClass();
        $units[] = $force($n, fn($u) => $u === $foreignUnit ? 100 : 0, $foreignUnit);
    }
    return compact('numbers','events','calls','units');
}
$a = observations($original,$unit);
$b = observations($fused,$unit);
ensure($a === $b, 'Immediate evaluation observations differ');

// Public construction must stay lazy and return a genuine reusable Closure.
$events = [];
$state = 4;
$seed = function($_) use (&$state,&$events) { $events[] = $state; return $state; };
$partial = $builder(17);
$root = $partial($seed);
ensure($root instanceof Closure && $events === [], 'Construction evaluated the seed');
$first = $root($unit);
$state = 9;
$second = $root($unit);
ensure([$first,$second,$events] === [21,26,[4,9]], 'Retained callback behavior changed');
$ffi = require $argv[2];
ensure($ffi['typeOf']($root) === 'function' && $ffi['tagOf']($root) === 'Function', 'Foreign representation changed');
$acceptClosure = function(Closure $fn) use ($unit) { return $fn($unit); };
ensure($acceptClosure($root) === 26, 'Typed Closure FFI failed');

// Counterexamples establish why a future compiler rule must reject arbitrary
// callbacks and preserve the arithmetic sequence, despite a scalar signature.
$stackSeed = fn($_) => count(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));
$stackOriginal = $original(17,$stackSeed,$unit);
$stackFused = $fused(17,$stackSeed,$unit);
ensure($stackOriginal !== $stackFused, 'Expected stack-observing counterexample');
$seedMax = PHP_INT_MAX-1;
$stepwise = $original(2048,fn($_) => $seedMax,$unit);
$shortcut = $seedMax+2048;
ensure($stepwise !== $shortcut, 'Expected overflow/rounding counterexample');
echo json_encode([
    'numeric_cases'=>count($a['numbers']), 'ordinary_observations_match'=>true,
    'events'=>$a['events'], 'exception_calls'=>$a['calls'], 'unit_cases'=>$a['units'],
    'public_repeated_forces'=>[$first,$second], 'public_ffi'=>'function/Function/Closure',
    'rejected_stack_callback'=>[$stackOriginal,$stackFused],
    'rejected_addition_shortcut'=>[serialize($stepwise),serialize($shortcut)],
]) . "\n";
