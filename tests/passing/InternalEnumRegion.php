<?php
$exports['opaque'] = function($value) { return $value; };
$exports['inspectColor'] = function(\Main\Main_Red|\Main\Main_Blue $color) {
    return $color instanceof \Main\Main_Red && $color->tag === 'Red';
};
$exports['inspectBox'] = function($box) {
    $chain = $box->chain;
    if (!($box->color instanceof \Main\Main_Red)) return false;
    while ($chain instanceof \Main\Main_Link) {
        if (!($chain->value0 instanceof \Main\Main_Red || $chain->value0 instanceof \Main\Main_Blue)) return false;
        $chain = $chain->value2;
    }
    return $chain instanceof \Main\Main_End;
};
$exports['retainColor'] = function(\Closure $fn) {
    return function() use ($fn) { $GLOBALS['internal_enum_fixture_callback'] = $fn; };
};
$exports['recalledColor'] = function() {
    $color = $GLOBALS['internal_enum_fixture_callback'](null);
    return $color instanceof \Main\Main_Red && $color->tag === 'Red';
};
