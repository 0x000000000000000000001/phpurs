<?php
$events = [];
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
$exports['readEvents'] = function() use (&$events) { return implode(',', $events); };
