<?php
$inputs = [];
$calls = 0;
$exports['opaque'] = fn($x) => $x;
$exports['countedDepth'] = function($x) use (&$inputs) { $inputs[] = 'depth'; return $x; };
$exports['countedInitial'] = function($x) use (&$inputs) { $inputs[] = 'initial'; return $x; };
$exports['readInputs'] = function() use (&$inputs) { return implode(',', $inputs); };
$exports['observedStep'] = function($x) use (&$calls) { ++$calls; return $x + 2; };
$exports['readCalls'] = function() use (&$calls) { return $calls; };
$exports['invoke'] = function(\Closure $f, $initial) { return $f($initial)->store; };
