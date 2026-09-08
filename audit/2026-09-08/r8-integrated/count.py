"""Count separately from timing, without JIT or active generated-file changes."""
import json
import pathlib
import subprocess

root = pathlib.Path(__file__).resolve().parent
rows = {}
for name in ['baseline', 'integrated']:
    original = root / 'raw' / name / 'output'
    output = root / 'raw' / (name + '-counts')
    output.mkdir(exist_ok=True)
    for module in original.iterdir():
        if module.name != 'Test.LazyEvaluation' and not (output / module.name).exists():
            (output / module.name).symlink_to(module.resolve(), target_is_directory=module.is_dir())
    source = (original / 'Test.LazyEvaluation/index.php').read_text()
    start = source.index('function majTest_majLazymajEvaluation_buildmajThunks(')
    end = source.index("\n$GLOBALS['Test_LazyEvaluation_buildThunks']", start)
    body = source[start:end]
    def add_count(text, before, counter):
        assert text.count(before) == 1, (name, before)
        return text.replace(before, "$GLOBALS['r8_counts']['" + counter + "']++;\n" + before)
    body = add_count(body, '  $__num = \\func_num_args();\n  $__fn', 'build_calls')
    body = add_count(body, '  $__tco_1 =', 'nodes')
    body = add_count(body, "  $__res = (($v1_1)($GLOBALS['Data_Unit_unit']) + 1);", 'node_calls')
    if name != 'baseline':
        assert body.count('\\Closure::fromCallable($__res)') == 1
        body = body.replace('\\Closure::fromCallable($__res)', 'r8_count_conversion($__res)')
    source = source[:start] + body + source[end:]
    source += "\nfunction r8_count_conversion($value) { $GLOBALS['r8_counts']['conversions']++; return \\Closure::fromCallable($value); }\n"
    (output / 'Test.LazyEvaluation').mkdir(exist_ok=True)
    (output / 'Test.LazyEvaluation/index.php').write_text(source)
    for mode in ['act', 'public-roots']:
        action = "$value = $GLOBALS['Test_LazyEvaluation_act']();"
        if mode == 'public-roots':
            action = """$value = 0;
for ($i = 0; $i < 1000; $i++) {
    $f = $GLOBALS['Test_LazyEvaluation_buildThunks'](1000, fn($unit) => 0);
    if (!($f instanceof \\Closure)) throw new \\RuntimeException('Public root is not a Closure');
    $value += $f(null);
}"""
        php = "<?php\nrequire " + json.dumps(str(output / 'Test.LazyEvaluation/index.php')) + ";\n"
        php += "$GLOBALS['r8_counts'] = ['build_calls' => 0, 'nodes' => 0, 'node_calls' => 0, 'conversions' => 0];\n"
        php += action + "\nif ((string)$value !== '1000000') throw new \\RuntimeException('Wrong result');\necho json_encode($GLOBALS['r8_counts']);"
        result = subprocess.run(['php', '-d', 'opcache.enable_cli=0'], input=php, text=True, capture_output=True)
        assert result.returncode == 0 and not result.stderr, (name, result.stdout, result.stderr)
        counts = json.loads(result.stdout)
        assert counts['build_calls'] == 1000 and counts['nodes'] == counts['node_calls'] == 1000000, counts
        expected = 0 if name == 'baseline' else 1000
        assert counts['conversions'] == expected, counts
        rows[name + '/' + mode] = counts
        print(name, mode, counts, flush=True)
(root / 'counts.json').write_text(json.dumps(rows, indent=2) + '\n')
