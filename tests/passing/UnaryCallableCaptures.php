<?php
$exports['opaque'] = function($value) { return $value; };
$exports['invoke'] = function(\Closure $f, $x) { return $f($x); };
$exports['overapply'] = function($f, $x, $y) { return $f($x, $y); };
