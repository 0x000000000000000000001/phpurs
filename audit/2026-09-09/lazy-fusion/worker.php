<?php
// Diagnostic worker for the verified buildThunks/force shape. This is not a
// replacement for the public builder, nor a general rule for unknown seeds.
function phpurs_probe_force_chain(int $depth, $seed, $unit) {
    // The original builder diverges for negative depths before invoking seed.
    // Preserve its route rather than turning that case into a terminating loop.
    if ($depth < 0) {
        return majTest_majLazymajEvaluation_buildmajThunks($depth, $seed, $unit);
    }
    $value = $seed($depth === 0 ? $unit : $GLOBALS['Data_Unit_unit']);
    // Keep each addition and its order, including PHP's overflow behavior.
    // Replacing the whole loop with seed()+depth is a different transformation.
    for ($remaining = $depth; $remaining > 0; --$remaining) {
        $value = $value + 1;
    }
    return $value;
}
