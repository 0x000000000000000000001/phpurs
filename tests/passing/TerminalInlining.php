<?php
$GLOBALS['terminalInlineEvents'] = [];
$exports['opaque'] = function($x) { return $x; };
$exports['stamp'] = function($x) { return function() use ($x) { $GLOBALS['terminalInlineEvents'][] = $x; return $x; }; };
$exports['reset'] = function() { $GLOBALS['terminalInlineEvents'] = []; };
$exports['events'] = function() { return implode(',', $GLOBALS['terminalInlineEvents']); };
