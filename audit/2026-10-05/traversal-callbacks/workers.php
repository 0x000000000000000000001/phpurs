<?php
// Pinned FFI traversal bodies. Retain the entry's callback argument evaluation,
// array materialization/order, and the native Int argument/return checks.
function phpurs_probe_array_add($callback, $init, $xs) {
    $acc = $init;
    for ($i = 0, $len = \count($xs); $i < $len; $i++) {
        $acc = \Data\Semiring\majData_majSemiring_intmajAdd($acc, $xs[$i]);
    }
    return $acc;
}

function phpurs_probe_array_evens($callback, $xs) {
    $res = [];
    foreach ($xs as $x) {
        if (($x % 2) === 0) $res[] = $x;
    }
    return $res;
}
