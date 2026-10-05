<?php
$calls = 0;
$exports['opaque'] = function($value) { return $value; };
$exports['countedSeed'] = function($unit) use (&$calls) { ++$calls; return 4; };
$exports['readCalls'] = function() use (&$calls) { return $calls; };
$exports['invoke'] = function(\Closure $f) { return $f(null); };
$depthCalls = 0;
$exports['countedDepth'] = function($n) use (&$depthCalls) { ++$depthCalls; return $n; };
$exports['readDepthCalls'] = function() use (&$depthCalls) { return $depthCalls; };
$valueCalls = 0;
$exports['countedValue'] = function($n) use (&$valueCalls) { ++$valueCalls; return $n; };
$exports['readValueCalls'] = function() use (&$valueCalls) { return $valueCalls; };
