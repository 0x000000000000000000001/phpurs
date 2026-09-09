<?php
$calls = 0;
$exports['opaque'] = function($value) { return $value; };
$exports['countedSeed'] = function($unit) use (&$calls) { ++$calls; return 4; };
$exports['readCalls'] = function() use (&$calls) { return $calls; };
$exports['invoke'] = function(\Closure $f) { return $f(null); };
