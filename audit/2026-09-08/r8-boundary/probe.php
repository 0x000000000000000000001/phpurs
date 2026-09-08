<?php
// Runtime diagnostic only, using the fixture's actual generated FFI wrappers.
$output = __DIR__ . '/raw/tests/output';
set_error_handler(function($level, $message, $file, $line) {
    throw new ErrorException($message, 0, $level, $file, $line);
});
ob_start();
require $output . '/Main/main.mod.php';
$fixtureOutput = ob_get_clean();
if ($fixtureOutput !== "Done\n") throw new RuntimeException($fixtureOutput);
$generated = $GLOBALS['functionBoundaryRetained']; // Generated closure captures 11.
$compact = new class(11) {
    public function __construct(private $seed) {}
    public function __invoke($x) {
        $result = $this->seed + $x;
        return func_num_args() > 1 ? $result(...array_slice(func_get_args(), 1)) : $result;
    }
};
$wrapped = Closure::fromCallable($compact);
function attemptBoundary($action) {
    try { return ['value' => $action()]; }
    catch (Throwable $error) { return ['error' => get_class($error)]; }
}
function tags($value) {
    return [$GLOBALS['Foreign_typeOf']($value), $GLOBALS['Foreign_tagOf']($value)];
}
function observe($f, $nested) {
    $handler = (object)['callback' => $nested, 'label' => 'captured'];
    $returned = $GLOBALS['Main_roundTripForeign']($f);
    $returnedRecord = $GLOBALS['Main_roundTripForeign']($handler);
    ($GLOBALS['Main_retain']($handler))();
    return [
        'direct' => $f(1),
        'record_call' => ($handler->callback)(1),
        'foreign_function_tags' => tags($f),
        'foreign_record_tags' => tags($handler),
        'foreign_callback_tags' => tags($handler->callback),
        'typed_closure' => attemptBoundary(fn() => $GLOBALS['Main_invokeClosure']($f, 1)),
        'typed_record_callback' => attemptBoundary(fn() => $GLOBALS['Main_invokeRecord']($handler, 1)),
        'foreign_return' => $returned(2),
        'foreign_return_tags' => tags($returned),
        'foreign_record_return' => attemptBoundary(fn() => $GLOBALS['Main_invokeRecord']($returnedRecord, 2)),
        'retained_calls' => [($GLOBALS['Main_recall'](1))(), ($GLOBALS['Main_recall'](2))()],
    ];
}
$rows = [
    'generated_closure' => observe($generated, $generated),
    'compact_object' => observe($compact, $compact),
    'only_direct_converted' => observe($wrapped, $compact),
    'direct_and_record_converted' => observe($wrapped, $wrapped),
];
$baseline = $rows['generated_closure'];
if ($baseline !== $rows['direct_and_record_converted']) throw new RuntimeException('Boundary conversion changed observations');
if ($baseline['typed_closure'] !== ['value' => 12] || $baseline['foreign_function_tags'] !== ['function', 'Function']) throw new RuntimeException('Invalid baseline');
if ($rows['compact_object']['typed_closure'] !== ['error' => 'TypeError'] || $rows['compact_object']['foreign_function_tags'] !== ['object', 'Object']) throw new RuntimeException('Missing compact-object counterexample');
if ($rows['only_direct_converted']['typed_closure'] !== ['value' => 12] || $rows['only_direct_converted']['typed_record_callback'] !== ['error' => 'TypeError']) throw new RuntimeException('Missing nested-record counterexample');
foreach ($rows as $row) {
    if ($row['direct'] !== 12 || $row['record_call'] !== 12 || $row['foreign_return'] !== 13 || $row['retained_calls'] !== [12, 13]) throw new RuntimeException('Capture or invocation changed');
}
echo json_encode(['php' => PHP_VERSION, 'observations' => $rows], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
