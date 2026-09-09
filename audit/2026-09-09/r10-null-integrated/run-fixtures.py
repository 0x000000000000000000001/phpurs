"""Run targeted fixtures with installed sibling packages in an isolated project.

The repository's bin/test requires phpurs-math, absent from this checkout.
None of these fixtures imports Math. Keep the regular runner untouched.
"""
import json
import os
import pathlib
import shutil
import subprocess

root = pathlib.Path(__file__).resolve().parents[3]
audit = pathlib.Path(__file__).resolve().parent
project = audit / 'raw/tests'
(project / 'src').mkdir(parents=True, exist_ok=True)
packages = ['assert', 'prelude', 'effect', 'console', 'foreign', 'integers', 'strings', 'arrays', 'numbers', 'partial', 'st', 'refs']
config = 'package:\n  name: internal-enum-fixtures\n  dependencies:\n'
config += ''.join(f'    - {package}\n' for package in packages)
config += 'workspace:\n  packageSet:\n    registry: 77.10.1\n  extraPackages:\n'
config += ''.join(f'    {package}:\n      path: "{root.parent / ("phpurs-" + package)}"\n' for package in packages)
(project / 'spago.yaml').write_text(config)
environment = dict(os.environ)
environment['PATH'] = str(root.parents[1] / 'altbak.pub-phpurs/run/bak/js/node_modules/.bin') + os.pathsep + environment['PATH']
# Never reuse standard CoreFn produced by a different purs binary.
shutil.rmtree(project / 'output', ignore_errors=True)
names = ['NullableRegion', 'ImmediateThunkFusion', 'InternalEnumRegion', 'CompactClosureLoops', 'FunctionFFIBoundary', 'TerminalInlining', 'UnaryCallableCaptures', 'BranchBindingSlots', 'RecursiveDictionaryInitialization', 'DeepCase', 'ShadowedRename', 'ShadowedTCOLet', 'TCOCase', 'TCOMutRec', 'CaseMultipleExpressions']
results = []
for name in names:
    for previous in (project / 'src').iterdir():
        shutil.rmtree(previous) if previous.is_dir() else previous.unlink()
    for suffix in ['.purs', '.js', '.php']:
        source = root / 'tests/passing' / (name + suffix)
        if source.exists():
            shutil.copy2(source, project / 'src' / ('Main' + suffix))
    extras = root / 'tests/passing' / name
    if extras.is_dir():
        shutil.copytree(extras, project / 'src', dirs_exist_ok=True)
    with (audit / f'fixture-{name}.log').open('w') as log:
        for command in [['spago', 'build', '-q'],
                        [str(root / 'bin/phpurs'), '--output', 'output', '--main', 'Main']]:
            subprocess.run(command, cwd=project, env=environment, stdout=log, stderr=subprocess.STDOUT, check=True)
        module = json.loads((project / 'output/Main/corefn.json').read_text())
        assert 'typeTable' in module and 'dataDecls' in module, 'The fixture must use the TAST compiler'
        shutil.copy2(project / 'output/Main/index.php', audit / 'raw' / (name + '.php'))
        php = (project / 'output/Main/index.php').read_text()
        if name == 'NullableRegion':
            assert 'Main___phpurs_enum_0_Cell' in php, 'The fixture must select a nullable-only region'
            assert 'new \\Main\\Main___phpurs_enum_0_Stop()' not in php
            assert 'instanceof \\Main\\Main___phpurs_enum_0_Cell' not in php
        if name == 'ImmediateThunkFusion':
            assert '__phpurs_fuse_0_grow' in php, 'The fixture must exercise R11'
            assert "$GLOBALS['Main___phpurs_fuse_" not in php, 'No private globals'
        if name == 'InternalEnumRegion':
            assert 'final class Main___phpurs_enum_0_Link' in php, 'The fixture must exercise R10'
            assert "$GLOBALS['Main___phpurs_enum_" not in php, 'Private workers are not public bindings'
        if name == 'CompactClosureLoops':
            assert php.count('implements Phpurs_InternalCallable') == 1, 'Only grow should compact'
            observed = php[php.index('function majMain_growmajObserved('):php.index("$GLOBALS['Main_growObserved']")]
            assert 'fromCallable' not in observed and 'implements Phpurs_InternalCallable' not in observed
        run = subprocess.run(['php', '-d', 'memory_limit=512M', 'output/Main/main.mod.php'],
                             cwd=project, text=True, capture_output=True)
        log.write(run.stdout + run.stderr)
    assert run.returncode == 0 and not run.stderr and 'Fail' not in run.stdout, (name, run.stdout, run.stderr)
    # The corresponding JavaScript FFI must preserve the same observations.
    js = subprocess.run(['node', '--input-type=module', '-e', "import {main} from './output/Main/index.js'; main();"],
                        cwd=project, text=True, capture_output=True)
    assert js.returncode == 0 and not js.stderr and js.stdout == run.stdout, (js.stdout, js.stderr)
    results.append({'fixture': name, 'php_exit_code': run.returncode, 'js_exit_code': js.returncode, 'stdout': run.stdout})
    (audit / 'fixtures.json').write_text(json.dumps(results, indent=2) + '\n')
    print(name + ': passed', flush=True)
