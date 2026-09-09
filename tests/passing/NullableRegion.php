<?php
$inspectChain = function(\Main\Main_Stop|\Main\Main_Cell $chain) {
    while ($chain instanceof \Main\Main_Cell) {
        if (!is_int($chain->value0)) return false;
        $chain = $chain->value1;
    }
    return $chain instanceof \Main\Main_Stop && $chain->tag === 'Stop';
};
$exports['opaque'] = function($value) { return $value; };
$exports['inspectChain'] = $inspectChain;
$exports['inspectWrapped'] = function(\Main\Main_Missing|\Main\Main_Present $wrapped) use ($inspectChain) {
    return $wrapped instanceof \Main\Main_Missing ? $wrapped->tag === 'Missing' : $inspectChain($wrapped->value0);
};
$exports['observe'] = function(callable $f, $chain) use ($inspectChain) {
    if (!$inspectChain($chain)) throw new \Exception('public tree lost');
    return $f($chain);
};
