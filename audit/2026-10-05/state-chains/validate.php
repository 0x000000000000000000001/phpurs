<?php
set_error_handler(function($level, $message, $file, $line) { throw new ErrorException($message, 0, $level, $file, $line); });
require $argv[1] . '/Test.StateMonad/index.php';
$chain = $GLOBALS['Test_StateMonad_chainModifications'];
$modify = $GLOBALS['Test_StateMonad_modify'];
$bind = $GLOBALS['Test_StateMonad_bindState'];
$pure = $GLOBALS['Test_StateMonad_pureState'];
$strict = '\Test\StateMonad\phpurs_probe_state_strict';
$scalar = '\Test\StateMonad\phpurs_probe_state_scalar';
function ensure($test, $message) { if (!$test) throw new RuntimeException($message); }
$cases = 0;
foreach ([0,1,2,17,60,127,2048] as $depth) {
    foreach ([-31,0,11,2147483647,PHP_INT_MAX-1] as $initial) {
        $original = $chain($depth, $initial);
        ensure(serialize($strict($depth, $initial)) === serialize($original), 'strict record parity');
        ensure(serialize($scalar($depth, $initial)) === serialize($original->state), 'scalar parity');
        ensure($original->val === $GLOBALS['Data_Unit_unit'], 'Unit field');
        ++$cases;
    }
}
$events = [];
$argument = function($name, $value) use (&$events) { $events[] = $name; return $value; };
foreach ([$chain, $strict, $scalar] as $execute) {
    $events = [];
    $value = $execute($argument('depth', 17), $argument('state', 11));
    ensure((is_object($value) ? $value->state : $value) === 28, 'argument result');
    ensure($events === ['depth', 'state'], 'argument order/count');
}
// The retained/public State is still a real, reusable Closure with fresh records.
$root = $chain(17);
ensure($root instanceof Closure, 'public State representation');
$first = $root(4);
$second = $root(9);
$again = $root(4);
ensure([$first->state, $second->state, $again->state] === [21,26,21], 'retained State runs');
ensure($first !== $again && serialize($first) === serialize($again), 'record identity');
$foreign = require $argv[2];
ensure($foreign['typeOf']($root) === 'function' && $foreign['tagOf']($root) === 'Function', 'Foreign boundary');
$acceptClosure = fn(Closure $action) => $action(11);
ensure($acceptClosure($root)->state === 28, 'Closure-typed boundary');

// Unknown callbacks stay on the public path. Construction does not call them.
$calls = 0;
$delta = 2;
$callback = function($state) use (&$calls, &$delta) { ++$calls; return $state + $delta; };
$action = $modify($callback);
ensure($calls === 0 && $action instanceof Closure, 'lazy callback construction');
ensure($action(10)->state === 12 && $calls === 1, 'first callback');
$delta = 7;
ensure($action(10)->state === 17 && $calls === 2, 'repeated callback capture');
$sentinel = new RuntimeException('same callback exception');
$throwing = $modify(function($state) use ($sentinel) { throw $sentinel; });
$caught = false;
try { $throwing(0); }
catch (RuntimeException $error) { $caught = true; ensure($error === $sentinel, 'exception identity'); }
ensure($caught, 'callback exception missing');
$callableValue = fn($state) => $state + 3;
ensure($pure($callableValue, 11)->val === $callableValue, 'callable val stays a value');

$events = [];
$initial = function($state) use (&$events) { $events[] = 'first'; return (object)['val' => 7, 'state' => $state + 2]; };
$next = function($value) use (&$events) {
    $events[] = ['bind', $value];
    return function($state) use (&$events, $value) { $events[] = ['second', $state]; return (object)['val' => $value + 1, 'state' => $state + 3]; };
};
$bound = $bind($initial, $next);
ensure($events === [], 'bind construction');
$value = $bound(11);
ensure($events === ['first', ['bind', 7], ['second', 13]] && $value->val === 8 && $value->state === 16, 'bind value/state flow');

// A negative chain diverges. Probe only the workers' original fallback target.
$workers = file_get_contents(__DIR__ . '/workers.php');
$workers = substr($workers, strlen("<?php\n"));
$workers = str_replace(['phpurs_probe_state_strict', 'phpurs_probe_state_scalar', 'majTest_majStatemajMonad_chainmajModifications'],
    ['phpurs_negative_strict', 'phpurs_negative_scalar', '\\phpurs_negative_target'], $workers);
eval('namespace Test\\StateMonad; ' . $workers);
$GLOBALS['negative_calls'] = 0;
$GLOBALS['negative_sentinel'] = new RuntimeException('negative route');
function phpurs_negative_target($depth, $state) {
    ++$GLOBALS['negative_calls'];
    ensure($depth === -7 && $state === 11, 'negative arguments');
    throw $GLOBALS['negative_sentinel'];
}
foreach (['\\Test\\StateMonad\\phpurs_negative_strict', '\\Test\\StateMonad\\phpurs_negative_scalar'] as $execute) {
    $caught = false;
    try { $execute(-7, 11); }
    catch (RuntimeException $error) { $caught = true; ensure($error === $GLOBALS['negative_sentinel'], 'negative exception identity'); }
    ensure($caught, 'negative fallback missing');
    $value = $execute(17, 11);
    ensure((is_object($value) ? $value->state : $value) === 28, 'nonnegative guard');
}
ensure($GLOBALS['negative_calls'] === 2, 'negative route count');

// Scalar types alone do not justify either callback inlining or n + state.
$inspect = fn($state) => count(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));
$throughModify = $modify($inspect, 0)->state;
$directCallback = $inspect(0);
ensure($throughModify !== $directCallback, 'stack observation counterexample');
$stepwise = $scalar(2048, PHP_INT_MAX - 1);
$shortcut = (PHP_INT_MAX - 1) + 2048;
ensure(serialize($stepwise) !== serialize($shortcut), 'rounding counterexample');
echo json_encode(['numericCases' => $cases, 'comparisonsPerCase' => 2, 'argumentOrderAndCount' => true,
    'publicClosure' => true, 'freshPublicRecords' => true, 'foreign' => 'function/Function/Closure',
    'retainedResults' => [21,26,21], 'lazyCallbackCalls' => $calls, 'bindOrder' => $events,
    'callbackExceptionIdentity' => true, 'callableValIdentity' => true, 'negativeFallbacks' => $GLOBALS['negative_calls'],
    'rejectedStackInlining' => [$throughModify, $directCallback],
    'rejectedAdditionShortcut' => [serialize($stepwise), serialize($shortcut)]]), PHP_EOL;
