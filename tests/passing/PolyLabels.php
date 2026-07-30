<?php
$exports['unsafeGet'] = function($s, $o) {
    if (is_array($o)) return $o[$s];
    if (is_object($o)) return $o->{$s};
    return null;
};

$exports['unsafeSet'] = function($s, $a, $o) {
    if (is_array($o)) {
        $o1 = $o;
        $o1[$s] = $a;
        return $o1;
    } else {
        $o1 = clone $o;
        $o1->{$s} = $a;
        return $o1;
    }
};
