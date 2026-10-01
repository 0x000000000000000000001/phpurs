"""Measure backend rebuilds on the frozen b8x corpus, with fresh-output controls.

Usage and input snapshot provenance are documented in report.md. All mutations
and generated files stay below the explicitly selected, initially empty directory.
"""
import argparse
import hashlib
import json
import os
from pathlib import Path
import platform
import re
import shutil
import statistics
import subprocess
import time


AUDIT = Path(__file__).resolve().parent
REPO = AUDIT.parents[2]
SCENARIOS = {
    'unchanged': None,
    'leaf': {
        'module': 'Inter.Api.Main', 'binding': 'handle',
        'literalType': 'StringLiteral', 'before': 'Not found.', 'after': 'Not found (M0 leaf).',
    },
    'dependency': {
        'module': 'Core.Message.Command.Command', 'binding': 'defaultMaxConcurrencyRetries',
        'literalType': 'IntLiteral', 'before': 50, 'after': 51,
    },
}


def save(path, value):
    path.write_text(json.dumps(value, indent=2, ensure_ascii=False) + '\n')


def digest(data):
    return hashlib.sha256(data).hexdigest()


def manifest(directory):
    return {str(file.relative_to(directory)): digest(file.read_bytes())
            for file in sorted(directory.rglob('*')) if file.is_file()}


def differences(before, after):
    return sorted(file for file in before.keys() | after.keys() if before.get(file) != after.get(file))


def compare(left, right):
    files = manifest(left)
    assert files == manifest(right), f'Output differs: {left} vs {right}'
    # Retain a direct byte comparison in addition to the recorded hashes.
    for file in files:
        assert (left / file).read_bytes() == (right / file).read_bytes(), file
    return len(files)


def graph_from(output):
    graph = {}
    for file in sorted(output.glob('*/corefn.json')):
        module = json.loads(file.read_text())
        name = '.'.join(module['moduleName'])
        assert name == file.parent.name
        assert all(key in module for key in ['typeTable', 'dataDecls', 'classDecls'])
        graph[name] = {'.'.join(imp['moduleName']) for imp in module['imports']} - {name}
    return graph


def importers(graph, target):
    direct = {name for name, imports in graph.items() if target in imports}
    transitive, pending = set(), list(direct)
    while pending:
        name = pending.pop()
        if name in transitive:
            continue
        transitive.add(name)
        pending.extend(other for other, imports in graph.items() if name in imports)
    return {'direct': sorted(direct), 'transitive': sorted(transitive - {target})}


def mutate(root, mutation):
    if mutation is None:
        return None
    file = root / 'b8x/output' / mutation['module'] / 'corefn.json'
    original = file.read_bytes()
    module = json.loads(original)
    bindings = [(i, b) for i, b in enumerate(module['decls']) if b.get('identifier') == mutation['binding']]
    assert len(bindings) == 1
    index, binding = bindings[0]
    locations = []

    def visit(node, pointer):
        if isinstance(node, dict):
            if node.get('literalType') == mutation['literalType'] and node.get('value') == mutation['before']:
                locations.append(pointer + '/value')
                node['value'] = mutation['after']
            else:
                for key, value in node.items():
                    visit(value, pointer + '/' + key)
        elif isinstance(node, list):
            for i, value in enumerate(node):
                visit(value, pointer + '/' + str(i))

    visit(binding, f'/decls/{index}')
    assert len(locations) == 1
    # Preserve every other input byte, including annotations and type tables.
    literal = lambda value: json.dumps({'literalType': mutation['literalType'], 'value': value},
                                      separators=(',', ':'), ensure_ascii=False).encode()
    needle = literal(mutation['before'])
    assert original.count(needle) == 1
    updated = original.replace(needle, literal(mutation['after']), 1)
    assert json.loads(updated) == module
    file.write_bytes(updated)
    return {**mutation, 'pointer': locations[0], 'beforeSHA256': digest(original), 'afterSHA256': digest(updated)}


def run_build(artifacts, project, label, graph):
    counts_path = artifacts / f'{label}.io.json'
    command = ['node', '--import', str(artifacts / 'count-io.mjs'), str(artifacts / 'backend.mjs'),
               '--main', 'Inter.Api.Main', '--bundle']
    environment = {**os.environ, 'GOPURS_JOBS': '1', 'PHPURS_AUDIT_COUNTS': str(counts_path)}
    started = time.monotonic()
    with (artifacts / f'{label}.log').open('wb') as log:
        result = subprocess.run(command, cwd=project, env=environment,
                                stdout=log, stderr=subprocess.STDOUT, timeout=300)
    process_ms = round((time.monotonic() - started) * 1000)
    text = (artifacts / f'{label}.log').read_text()
    assert result.returncode == 0, f'{label}: exit {result.returncode}'
    assert not re.search(r'Failed to decode|Failed to read purmeta|DIRECTIVE PARSE ERRORS|\(failed\)', text), label
    modules = re.findall(r'^Generating PHP code for (.*?) \(Total AST Nodes:', text, re.MULTILINE)
    assert len(modules) == len(set(modules)) and set(modules) <= graph.keys()
    save(artifacts / f'{label}.modules.json', modules)
    io = json.loads(counts_path.read_text())
    assert io['reads']['corefn']['files'] == len(graph)
    phases = {name: int(ms) for name, ms in re.findall(r'^\[phpurs\] (.*?): (\d+) ms$', text, re.MULTILINE)}
    assert 'backend total' in phases
    writes = {kind: {'files': len(entries), 'bytes': sum(entry['bytes'] for entry in entries)}
              for kind, entries in io['writes'].items()}
    summary = {
        'label': label, 'modulesGenerated': len(modules), 'modulesSkipped': len(graph) - len(modules),
        'modulesSHA256': digest('\n'.join(modules).encode()),
        'reads': io['reads'], 'writes': writes, 'peakRSSKiB': io['peakRSSKiB'],
        'phasesMs': phases, 'processMs': process_ms,
        'writtenPHP': sorted(entry['file'].removeprefix('output/') for entry in io['writes']['php']),
    }
    print(f"{label}: {len(modules)} modules, {writes['php']['files']} PHP writes, "
          f"{phases['backend total']} ms, {io['peakRSSKiB']} KiB", flush=True)
    return summary


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--snapshot', type=Path, required=True, help='Frozen root containing b8x/ and phpurs/')
    parser.add_argument('--artifacts', type=Path, required=True, help='New or empty artifact directory')
    parser.add_argument('--backend', type=Path, default=REPO / 'bin/phpurs.js')
    parser.add_argument('--reference-output', type=Path, help='Previously validated b8x output tree')
    parser.add_argument('--repetitions', type=int, default=3)
    args = parser.parse_args()
    assert args.repetitions >= 1
    snapshot, artifacts = args.snapshot.resolve(), args.artifacts.resolve()
    assert not artifacts.exists() or not any(artifacts.iterdir()), 'Artifacts directory must be empty'
    assert not artifacts.is_relative_to(snapshot) and not snapshot.is_relative_to(artifacts)
    artifacts.mkdir(parents=True, exist_ok=True)
    shutil.copy2(args.backend, artifacts / 'backend.mjs')
    shutil.copy2(AUDIT / 'count-io.mjs', artifacts / 'count-io.mjs')
    shutil.copy2(__file__, artifacts / 'measure.py')
    inputs = manifest(snapshot)
    assert not any(name.endswith('.purmeta') or name.endswith('/index.php') for name in inputs)
    save(artifacts / 'inputs.json', inputs)
    graph = graph_from(snapshot / 'b8x/output')
    graph_details = {name: importers(graph, mutation['module'])
                     for name, mutation in SCENARIOS.items() if mutation}
    assert graph_details['leaf']['direct'] == []
    assert graph_details['dependency']['direct']
    save(artifacts / 'graph.json', graph_details)
    summary = {
        'backendSHA256': digest((artifacts / 'backend.mjs').read_bytes()),
        'inputManifestSHA256': digest(json.dumps(inputs, sort_keys=True).encode()),
        'inputFiles': len(inputs), 'inputModules': len(graph),
        'node': subprocess.check_output(['node', '--version'], text=True).strip(),
        'platform': platform.platform(), 'machine': platform.machine(),
        'nodeOptions': os.environ.get('NODE_OPTIONS'), 'moduleReadConcurrency': 1,
        'options': ['--main', 'Inter.Api.Main', '--bundle'], 'repetitions': args.repetitions,
        'mutations': {}, 'fresh': {}, 'trials': {name: [] for name in SCENARIOS},
        'importers': {name: {kind: len(modules) for kind, modules in detail.items()}
                      for name, detail in graph_details.items()},
    }
    baseline = artifacts / 'fresh-unchanged'
    expected = {}
    for name, mutation in SCENARIOS.items():
        tree = artifacts / f'fresh-{name}'
        shutil.copytree(snapshot, tree, symlinks=True)
        detail = mutate(tree, mutation)
        if detail:
            summary['mutations'][name] = detail
        changed_inputs = differences(inputs, manifest(tree))
        assert changed_inputs == ([f"b8x/output/{mutation['module']}/corefn.json"] if mutation else [])
        fresh = run_build(artifacts, tree / 'b8x', f'fresh-{name}', graph)
        assert fresh['modulesGenerated'] == len(graph)
        output = tree / 'b8x/output'
        expected[name] = manifest(output)
        if name == 'unchanged' and args.reference_output:
            summary['previousReferenceIdenticalFiles'] = compare(output, args.reference_output.resolve())
        fresh['changedPHP'] = [file for file in differences(expected['unchanged'], expected[name]) if file.endswith('.php')]
        if name == 'leaf':
            assert fresh['changedPHP'] == ['Inter.Api.Main/index.php', 'Inter.Api.Main/main.bundle.php']
        if name == 'dependency':
            assert 'Core.Message.Command.Command/index.php' in fresh['changedPHP']
            assert 'Core.Feat.Membership.Message.Command.ChangeUserEmail.Command/index.php' in fresh['changedPHP']
            # This consumer contains the literal after optimization while its
            # own CoreFn is unchanged: a per-module input hash is insufficient.
            consumer = output / 'Core.Feat.Membership.Message.Command.ChangeUserEmail.Command/index.php'
            assert '"maxRetries" => 51' in consumer.read_text()
        # The complete PHP write list is retained in the raw I/O trace.
        del fresh['writtenPHP']
        summary['fresh'][name] = fresh
        save(artifacts / 'results.json', summary)

    names = list(SCENARIOS)
    work = artifacts / 'work'
    for repeat in range(args.repetitions):
        # Rotate order; every trial starts from the same warmed, unmodified build.
        for name in names[repeat % len(names):] + names[:repeat % len(names)]:
            if work.exists():
                shutil.rmtree(work)
            shutil.copytree(baseline, work, symlinks=True)
            mutate(work, SCENARIOS[name])
            output = work / 'b8x/output'
            php_files = [file for file in expected['unchanged'] if file.endswith('.php')]
            for file in php_files:
                os.utime(output / file, ns=(946684800000000000, 946684800000000000))
            before_mtimes = {file: (output / file).stat().st_mtime_ns for file in php_files}
            result = run_build(artifacts, work / 'b8x', f'{name}-{repeat + 1}', graph)
            result['identicalFreshFiles'] = compare(output, artifacts / f'fresh-{name}/b8x/output')
            changed = summary['fresh'][name]['changedPHP']
            assert result['writtenPHP'] == changed
            changed_mtimes = [file for file in php_files if (output / file).stat().st_mtime_ns != before_mtimes[file]]
            assert sorted(changed_mtimes) == changed
            result['unchangedPHPMtimes'] = len(php_files) - len(changed)
            summary['trials'][name].append(result)
            save(artifacts / 'results.json', summary)

    summary['statistics'] = {}
    for name, trials in summary['trials'].items():
        assert len({trial['modulesSHA256'] for trial in trials}) == 1
        stats = {}
        for key, values in {
            'backendMs': [trial['phasesMs']['backend total'] for trial in trials],
            'peakRSSKiB': [trial['peakRSSKiB'] for trial in trials],
            'processMs': [trial['processMs'] for trial in trials],
        }.items():
            stats[key] = {'min': min(values), 'median': statistics.median(values), 'max': max(values)}
        summary['statistics'][name] = stats
    assert manifest(snapshot) == inputs, 'The frozen snapshot must remain unchanged'
    save(artifacts / 'results.json', summary)
    print(json.dumps(summary['statistics'], indent=2), flush=True)


if __name__ == '__main__':
    main()
