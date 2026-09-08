<?php
$exports['opaque'] = function($value) { return $value; };
$exports['invokeClosure'] = function(\Closure $f, $x) { return $f($x); };
$exports['invokeRecord'] = function($handler, $x) {
    return (function(\Closure $f) use ($x) { return $f($x); })($handler->callback);
};
$exports['roundTripForeign'] = function($value) { return $value; };
$exports['retain'] = function($handler) {
    return function() use ($handler) {
        $GLOBALS['functionBoundaryRetained'] = $handler->callback;
    };
};
$exports['recall'] = function($x) {
    return function() use ($x) { return ($GLOBALS['functionBoundaryRetained'])($x); };
};
