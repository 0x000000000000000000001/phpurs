<?php
$calls = 0;
$exports['opaque'] = function($x) { return $x; };
$exports['counted'] = function($x) use (&$calls) { ++$calls; return $x + 3; };
$exports['readCalls'] = function() use (&$calls) { return $calls; };
$exports['invoke'] = function(\Closure $f, $x) { return $f($x); };
