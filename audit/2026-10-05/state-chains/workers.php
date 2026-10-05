<?php
// Pinned-program diagnostic workers, inserted into the State module's namespace.
// Each positive step still performs one addition. Negative depths keep the
// original route; validation instruments that target rather than diverging.
function phpurs_probe_state_strict($remaining, $state) {
    if ($remaining < 0) return majTest_majStatemajMonad_chainmajModifications($remaining, $state);
    while ($remaining !== 0) {
        $remaining = $remaining - 1;
        $result = majTest_majStatemajMonad_modify(function($value) { return $value + 1; }, $state);
        $state = $result->state;
    }
    return (object)['val' => $GLOBALS['Data_Unit_unit'], 'state' => $state];
}

function phpurs_probe_state_scalar($remaining, $state) {
    if ($remaining < 0) return (majTest_majStatemajMonad_chainmajModifications($remaining, $state))->state;
    while ($remaining !== 0) {
        $remaining = $remaining - 1;
        $state = $state + 1;
    }
    return $state;
}
