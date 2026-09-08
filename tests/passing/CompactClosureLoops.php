<?php
$exports['opaque'] = function($value) { return $value; };
$exports['invoke'] = function(\Closure $f, $x) { return $f($x); };
$exports['inspect'] = function(\Closure $f) { return true; };
