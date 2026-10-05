"""Validate the completed guard campaign and retain compact, reproducible evidence."""
from collections import defaultdict
import hashlib
import importlib.util
import json
import os
from pathlib import Path
import re
from statistics import median
import sys

from analyze import analyze
from prepare import WORKSPACE, digest, save

artifacts = Path(sys.argv[1]).resolve()
load = lambda name: json.loads((artifacts / name).read_text())
preparation = load('preparation.json')
assert digest(artifacts / 'backend.mjs') == preparation['compilerSHA256']
result = {'date': '2026-10-05', 'artifacts': str(artifacts),
          'provenance': {key: value for key, value in preparation.items() if key != 'projects'},
          'protocol': {'limits': [10000, 11, 100], 'repetitions': 3,
                       'order': [[10000, 11, 100], [11, 100, 10000], [100, 10000, 11]],
                       'persistentCache': False, 'purmetaCacheMiB': 64, 'GOPURS_JOBS': 1,
                       'buildProfile': True, 'verboseInTimedRuns': False,
                       'outputsPreexistingAndByteIdentical': True,
                       'phpMtimeNanoseconds': 946684800000000000,
                       'diagnosticInstrumentationInTimedRuns': False},
          'checks': {'buildWarnings': 0, 'buildErrors': 0, 'rewriteLimitTestsPassed': 4},
          'decision': {'default': 10000, 'reason': 'A sufficient guard preserves the optimization schedule and PHP; retain the existing convergence headroom.'},
          'projects': {}}

for project, minimum in [('b8x', 11), ('bench', 8)]:
    diagnostics = []
    ast_counts = []
    for label, limit in [('diagnostic', 10000), ('diagnostic-min', minimum)]:
        stem = f'{label}-{project}-1-{limit}'
        diagnostics.append(analyze(artifacts / 'builds' / (stem + '.rewrite.json')))
        text = (artifacts / 'builds' / (stem + '.stdout')).read_text()
        ast_counts.append({name: int(nodes) for name, nodes in re.findall(
            r'^Generating PHP code for (\S+) \(Total AST Nodes: (\d+)\)$', text, re.M)})
    assert diagnostics[0] == diagnostics[1]
    assert diagnostics[0]['maximumPasses'] == minimum
    assert ast_counts[0] == ast_counts[1] and ast_counts[0]
    diagnostic = diagnostics[0]
    expected = load(project + '-outputs.json')
    php_files = [file for file in expected if file.endswith('.php')]
    generated_bytes = sum(expected[file]['bytes'] for file in php_files)
    module_count = len(preparation['projects'][project]['modules'])
    assert len(ast_counts[0]) == module_count
    data = load('measured-' + project + '.json')
    assert data['compilerSHA256'] == preparation['compilerSHA256'] and data['inputsUnchanged']
    assert len(data['trials']) == 9 and data['repetitions'] == 3 and data['limits'] == [10000, 11, 100]
    assert [row['limit'] for row in data['trials']] == sum(result['protocol']['order'], [])
    group = defaultdict(list)
    profiles = {}
    for row in data['trials']:
        assert row['exitCode'] == 0 and not row['diagnostic'] and row['phpWrites'] == 0
        assert row['phpFiles'] == len(php_files) and row['phpBytes'] == generated_bytes
        assert row['identicalFiles'] == len(expected) and row['completedModules'] == module_count
        profile = load('builds/' + row['label'] + '.profile.json')
        assert profile['status'] == 'completed' and profile['phases']['diagnostics']['calls'] == 0
        profiles[row['label']] = {m['name']: m for m in profile['modules']}
        group[row['limit']].append(row)
    summaries = {}
    for limit, rows in sorted(group.items()):
        assert len(rows) == 3
        summary = {}
        metrics = {'processMs': [row['processMs'] for row in rows],
                   'optimizeMs': [row['phaseMs']['optimize'] for row in rows],
                   'translateMs': [row['phaseMs']['translate'] for row in rows],
                   'peakRSSKiB': [row['peakRSSKiB'] for row in rows]}
        if project == 'bench':
            for row in rows:
                assert row['runtime']['values_validated'] and len(row['runtime']['values']) == 14
                assert row['runtime']['values'] == rows[0]['runtime']['values']
                assert row['phpVersion'] == rows[0]['phpVersion'] and row['phpFlags'] == rows[0]['phpFlags']
            metrics['runtimeMs'] = [row['runtime']['total_ms'] for row in rows]
        for name, values in metrics.items():
            summary[name] = {'samples': values, 'min': min(values), 'median': median(values), 'max': max(values)}
        summaries[str(limit)] = summary
    boundary = load('boundary-' + project + '.json')['trials']
    assert any(row['limit'] == minimum - 1 and row['exitCode'] != 0 for row in boundary)
    assert all('runtime' not in row for row in boundary if row['exitCode'] != 0)
    selected = []
    if project == 'b8x':
        pilot = load('builds/diagnostic-b8x-1-10000.profile.json')
        selected = [m['name'] for m in sorted(pilot['modules'], key=lambda m: -m['phases']['optimize']['ms'])[:5]]
        selected += ['Core.Feat.Review.Message.Query.GetArticleQuote.Projection.Projection', 'Data.CodePoint.Unicode.Internal']
    else:
        selected = ['Data.Map.Internal', 'Data.DateTime.Instant', 'Test.RBTree', 'Test.AstTree']
    selected_rows = []
    for name in selected:
        selected_rows.append({'name': name, 'analysis': diagnostic['modules'][name],
                              'phpBytes': expected[name + '/index.php']['bytes'],
                              'optimizedAstNodes': ast_counts[0][name],
                              'optimizeMedianMs': {str(limit): median(profiles[row['label']][name]['phases']['optimize']['ms']
                                                                    for row in rows) for limit, rows in sorted(group.items())}})
    result['projects'][project] = {
        'modules': module_count, 'astNodes': sum(ast_counts[0].values()), 'minimumObservedGuard': minimum,
        'diagnostic': {key: value for key, value in diagnostic.items() if key != 'modules'},
        'identicalFiles': len(expected), 'phpFiles': len(php_files), 'phpBytes': generated_bytes,
        'outputManifestSHA256': digest(artifacts / (project + '-outputs.json')),
        'statistics': summaries, 'selectedModules': selected_rows,
        'boundaries': [{key: value for key, value in row.items() if key not in ['modulePhpBytes', 'phaseMs']}
                       for row in boundary],
        'trials': [{key: value for key, value in row.items() if key != 'modulePhpBytes'} for row in data['trials']],
    }
    print(project, 'threshold', minimum, 'PHP bytes', generated_bytes)
    for limit, values in summaries.items():
        print(limit, {name: round(metric['median'], 3) for name, metric in values.items()})

captured = load('inputs.json')
manifest = {name: row['sha256'] for name, row in sorted(captured.items())}
assert hashlib.sha256(json.dumps(manifest, sort_keys=True).encode()).hexdigest() == preparation['inputManifestSHA256']
for file, row in captured.items():
    assert digest(artifacts / 'snapshot' / file) == digest(artifacts / 'work' / file) == row['sha256']
driver_path = WORKSPACE / 'altbak.pub-phpurs/bin/php/driver.py'
spec = importlib.util.spec_from_file_location('php_driver', driver_path)
driver = importlib.util.module_from_spec(spec)
spec.loader.exec_module(driver)
bench_relative = preparation['projects']['bench']['relativeRoot']
bench = artifacts / 'work' / bench_relative
original_bench = WORKSPACE / bench_relative
assert driver.artifacts(bench) == driver.artifacts(original_bench)
assert driver.vendor_artifacts(bench) == driver.vendor_artifacts(original_bench)
for directory, dirs, names in os.walk(artifacts / 'bench-vendor', followlinks=False):
    for name in dirs + names:
        source = Path(directory) / name
        target = bench / 'vendor' / source.relative_to(artifacts / 'bench-vendor')
        if source.is_symlink():
            assert target.is_symlink() and os.readlink(source) == os.readlink(target)
        elif source.is_file():
            assert digest(source) == digest(target)
for record in captured.values():
    assert digest(Path(record['source'])) == record['sha256']
reference = {'benchmarkIdenticalArtifacts': len(driver.artifacts(bench)),
             'vendorUnchanged': True, 'originalSourcesUnchanged': True,
             'diagnosticSHA256': digest(artifacts / 'diagnostic.mjs'),
             'driverInputs': driver.inputs(), 'vendor': driver.vendor_artifacts(bench)}
save(artifacts / 'reference-check.json', reference)
assert reference['benchmarkIdenticalArtifacts'] == 350
assert reference['vendorUnchanged'] and reference['originalSourcesUnchanged']
assert reference['driverInputs'] == load('bench-driver-inputs.json')
assert reference['vendor'] == load('bench-vendor.json')
result['referenceChecks'] = {
    'benchmarkIdenticalArtifacts': 350, 'vendorUnchanged': True, 'originalSourcesUnchanged': True,
    'diagnosticSHA256': reference['diagnosticSHA256'],
    'driverInputsSHA256': digest(artifacts / 'bench-driver-inputs.json'),
    'vendorManifestSHA256': digest(artifacts / 'bench-vendor.json'),
    'finalBenchmarkValidation': load('reference-check-bench.json')['trials'][0]['runtime']['values_validated'],
}
save(Path(sys.argv[2]), result)
print('All evidence checked:', sys.argv[2])
