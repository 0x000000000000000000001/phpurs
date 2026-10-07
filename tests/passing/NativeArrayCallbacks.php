<?php
$events = [];
$threshold = 2;
$exports['opaque'] = fn($value) => $value;
$exports['newCallback'] = function() use (&$events) {
    return function($acc) use (&$events) {
        $events[] = 'first:' . $acc;
        return function($item) use (&$events, $acc) {
            $events[] = 'second:' . $item;
            return $acc + $item;
        };
    };
};
$exports['newPredicate'] = function() use (&$events, &$threshold) {
    return function($item) use (&$events, &$threshold) {
        $events[] = 'visit:' . $item;
        return $item > $threshold;
    };
};
$exports['setThreshold'] = function($value) use (&$threshold) {
    return function() use ($value, &$threshold) { $threshold = $value; return null; };
};
$exports['readEvents'] = function() use (&$events) { return implode(',', $events); };
