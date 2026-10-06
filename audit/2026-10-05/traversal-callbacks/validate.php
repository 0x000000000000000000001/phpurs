<?php
set_error_handler(function($level, $message, $file, $line) { throw new ErrorException($message, 0, $level, $file, $line); });
require $argv[1] . '/Test.ListOps/index.php';
require $argv[1] . '/Test.ArrayOps/index.php';
$variant = $argv[2];
$add = $GLOBALS['Data_Semiring_intAdd'];
$listFold = $GLOBALS['Test_ListOps_foldl'];
$arrayFold = $GLOBALS['Data_Foldable_foldlArray'];
$arrayFilter = $GLOBALS['Data_Array_filterImpl'];
function ensure($test, $message) { if (!$test) throw new RuntimeException($message); }
function linked($values) {
    $result = new \Test\ListOps\Test_ListOps_Nil();
    for ($i = count($values) - 1; $i >= 0; --$i) $result = new \Test\ListOps\Test_ListOps_Cons($values[$i], $result);
    return $result;
}
function outcome($action) {
    try { return ['value', serialize($action())]; }
    catch (TypeError $error) { return ['error', get_class($error), $error->getMessage()]; }
}
$sumCases = 0;
foreach ([-20,-9,-1,0,1,2,17,127,900,3000] as $n) {
    $pairs = intdiv(abs($n), 2);
    $expectedArray = ($n < 0 ? -1 : 1) * $pairs * ($pairs + 1);
    $expectedList = $n < 0 ? 0 : $expectedArray;
    ensure($GLOBALS['Test_ListOps_sumEvens']($n) === $expectedList, 'list sum');
    ensure($GLOBALS['Test_ArrayOps_sumEvens']($n) === $expectedArray, 'array sum');
    ++$sumCases;
}
$lists = [[], [0], [1,2,3], [-7,0,11], range(-30,30), [2147483647,1],
    [PHP_INT_MAX-1,1], [PHP_INT_MAX-1,1,1], [PHP_INT_MAX,1,-1]];
$numericCases = 0;
$errors = 0;
foreach ($lists as $values) {
    $list = linked($values);
    $before = serialize([$values, $list]);
    foreach ([0,11,-31] as $initial) {
        $reference = outcome(fn() => $listFold($add, $initial, $list));
        ensure(outcome(fn() => $arrayFold($add, $initial, $values)) === $reference, 'array/list fold parity');
        if ($variant === 'list-add') ensure(outcome(fn() => \Test\ListOps\phpurs_probe_list_add($add, $initial, $list)) === $reference, 'specialized list parity');
        if (str_starts_with($variant, 'array-')) ensure(outcome(fn() => \Test\ArrayOps\phpurs_probe_array_add($add, $initial, $values)) === $reference, 'specialized array parity');
        if ($reference[0] === 'error') ++$errors;
        ++$numericCases;
    }
    if (str_starts_with($variant, 'array-')) {
        $predicate = fn($value) => ($value % 2) === 0;
        ensure(\Test\ArrayOps\phpurs_probe_array_evens($predicate, $values) === $arrayFilter($predicate, $values), 'specialized filter parity');
    }
    ensure(serialize([$values, $list]) === $before, 'retained inputs mutated');
}
if (str_starts_with($variant, 'array-')) {
    $sparse = [9 => 4, 12 => 3, 'last' => -2];
    $predicate = fn($value) => ($value % 2) === 0;
    ensure(\Test\ArrayOps\phpurs_probe_array_evens($predicate, $sparse) === [4,-2], 'filter order and dense keys');
}

// Unknown callbacks retain both curried stages, in order, on the public path.
$events = [];
$callback = function($acc) use (&$events) {
    $events[] = ['partial', $acc];
    return function($value) use ($acc, &$events) { $events[] = ['apply', $value]; return $acc - $value; };
};
$expectedEvents = [['partial', 10], ['apply', 2], ['partial', 8], ['apply', 3]];
foreach ([$listFold, $arrayFold] as $index => $fold) {
    $values = $index === 0 ? linked([2,3]) : [2,3];
    $events = [];
    $partial = $fold($callback, 10);
    ensure($partial instanceof Closure && $events === [], 'lazy partial fold');
    ensure($partial($values) === 5 && $events === $expectedEvents, 'callback stage order');
    $events = [];
    ensure($partial($values) === 5 && $events === $expectedEvents, 'retained partial fold');
    foreach ([true, false] as $firstStage) {
        $same = new RuntimeException('callback sentinel');
        $throwing = function($acc) use ($same, $firstStage) {
            if ($firstStage) throw $same;
            return function($value) use ($same) { throw $same; };
        };
        $caught = false;
        try { $fold($throwing, 0, $values); }
        catch (RuntimeException $error) { $caught = true; ensure($error === $same, 'exception identity'); }
        ensure($caught, 'missing callback exception');
    }
}
$visits = [];
$limit = 2;
$predicate = function($value) use (&$visits, &$limit) { $visits[] = $value; return $value >= $limit; };
$partial = $GLOBALS['Data_Array_filter']($predicate);
ensure($partial instanceof Closure && $visits === [], 'retained filter');
ensure($partial([1,2,3]) === [2,3] && $visits === [1,2,3], 'filter callback order');
$limit = 3;
$visits = [];
ensure($partial([1,2,3]) === [3] && $visits === [1,2,3], 'filter capture reread');
$foreign = require $argv[3];
ensure($foreign['typeOf']($partial) === 'function' && $foreign['tagOf']($partial) === 'Function', 'Foreign closure');
ensure((fn(Closure $f) => $f([1,2,3]))($partial) === [3], 'Closure-typed FFI');

// A raw + worker would bypass an existing observable Int return check.
$checked = outcome(fn() => $arrayFold($add, 0, [PHP_INT_MAX,1]));
$raw = 0;
foreach ([PHP_INT_MAX,1] as $value) $raw = $raw + $value;
ensure($checked[0] === 'error' && is_float($raw), 'unchecked arithmetic counterexample');
// Saturating an arbitrary curried callback does not preserve its first stage.
$firstStageCalls = 0;
$curried = function($acc) use (&$firstStageCalls) { ++$firstStageCalls; return fn($value) => $acc + $value; };
$wrong = $curried(10, 2);
ensure($wrong instanceof Closure && $firstStageCalls === 1, 'unknown arity counterexample');
echo json_encode(['variant' => $variant, 'consumerCases' => $sumCases * 2, 'foldCases' => $numericCases,
    'overflowCases' => $errors, 'retainedInputs' => true, 'curriedStageOrder' => $expectedEvents,
    'publicPartialsReusable' => true, 'exceptionsInBothStages' => true, 'mutablePredicateCapture' => true,
    'foreign' => 'function/Function/Closure', 'uncheckedAddRejected' => $checked,
    'uncheckedAddValue' => serialize($raw), 'unknownArityRejected' => true]), PHP_EOL;
