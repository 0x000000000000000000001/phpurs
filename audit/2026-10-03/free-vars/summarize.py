"""Check frozen evidence and write the compact B4 free-variable results."""
import hashlib
import json
from pathlib import Path
from statistics import median
import sys

base = Path(sys.argv[1])
destination = Path(sys.argv[2])
load = lambda name: json.loads((base / name).read_text())
digest = lambda path: hashlib.sha256(path.read_bytes()).hexdigest()
visits = {label: {row['name']: row for row in load(label + '-visits.json')}
          for label in ['before', 'after']}
trials = load('module-trials.json')
memory = {label: load(label + '-memory-final.json') for label in ['before', 'after']}
modules = []
fixtures = load('states.json')['modules']
for label, sample in memory.items():
    assert sample['librarySHA256'] == digest(base / (label + '-library.mjs'))
for fixture in fixtures:
    assert digest(base / 'states/v1' / (fixture['key'] + '.bin')) == fixture['stateSHA256']
    name = fixture['name']
    expected = visits['before'][name]['phpAstSHA256']
    after = visits['after'][name]
    assert after['phpAstSHA256'] == expected and after['repeats'] == 0
    assert after['calls'] == after['uniqueNodes'] == visits['before'][name]['uniqueNodes']
    medians = {label: [] for label in ['before', 'after']}
    for key, trial in trials.items():
        label = key.split('-')[1]
        assert trial['librarySHA256'] == digest(base / (label + '-library.mjs'))
        row = next(row for row in trial['modules'] if row['name'] == name)
        assert row['phpAstSHA256'] == expected and len(row['timesMs']) == 15
        medians[label].append(median(row['timesMs']))
    centers = {label: median(values) for label, values in medians.items()}
    for sample in memory.values():
        row = next(row for row in sample['rows'] if row['name'] == name)
        assert row['phpAstSHA256'] == expected and row['liveKeys'] == row['liveValues'] == 0
    modules.append({'name': name, 'stateSHA256': fixture['stateSHA256'], 'phpAstSHA256': expected,
                    'visitsBefore': visits['before'][name]['calls'], 'visitsAfter': after['calls'],
                    'processMediansMs': medians, 'medianOfMediansMs': centers,
                    'reductionPercent': 100 * (1 - centers['after'] / centers['before'])})

profile_path = next(base.glob('before-profile*.cpuprofile'))
profile = json.loads(profile_path.read_text())
nodes = {node['id']: node for node in profile['nodes']}
parents = {child: node['id'] for node in profile['nodes'] for child in node.get('children', [])}
inclusive = 0
for sample, delta in zip(profile['samples'], profile['timeDeltas']):
    while sample:
        if nodes[sample]['callFrame']['functionName'] == 'freeVars':
            inclusive += delta
            break
        sample = parents.get(sample)
cpu = {'samplingIntervalMicroseconds': 250, 'totalSampleMs': sum(profile['timeDeltas']) / 1000,
       'inclusiveFreeVarsMs': inclusive / 1000, 'profileSHA256': digest(profile_path)}

full = load('validation/results.json')
for label in ['before', 'after']:
    assert full[label + 'SHA256'] == digest(base / (label + '.mjs'))
for label, trial in full['trials'].items():
    assert trial['identicalFiles'] == 5372 and trial['phpFiles'] == 2686 and trial['phpWrites'] == 0
    profile = load('validation/' + label + '.profile.json')
    assert profile['status'] == 'completed'
    for phase in ['corefn.decode', 'optimize', 'translate']:
        assert profile['phases'][phase]['completed'] == full['modules'] == 2684
    trial.pop('targetModule')  # This shared driver's original target was Unicode.
    trial['moduleTranslateMs'] = {row['name']: row['phases']['translate']['ms']
                                 for row in profile['modules'] if row['name'] in visits['before']}
assert full['inputsUnchanged'] and len(full['trials']) == 4
full['options'] = ['--main', 'Inter.Api.Main', '--bundle', '--no-cache', '--profile-build']
full['purmetaCacheMiB'] = 64
full['medianTranslateMs'] = {variant: median(row['phaseMs']['translate'] for label, row in full['trials'].items()
                                           if label.endswith(variant)) for variant in ['before', 'after']}
full['translateReductionPercent'] = 100 * (1 - full['medianTranslateMs']['after'] / full['medianTranslateMs']['before'])
benchmark = load('benchmark/results.json')
assert benchmark['inputsUnchanged']
for label, row in benchmark['trials'].items():
    assert row['compilerSHA256'] == full[label + 'SHA256']
    assert row['artifacts'] == 350 and row['generatedPhpFiles'] == 307
    assert row['results']['values_validated'] and len(row['results']['values']) == 14
regression = {label: load(label + '-recomputation.json') for label in ['before', 'after']}
assert not regression['before']['passes'] and regression['after']['passes']
result = {
    'date': '2026-10-03', 'topic': 'B4 free-variable reuse', 'artifacts': str(base),
    'phpursBase': '8d8295456101a5eea908161bd8d02112275ff703',
    'pboBase': 'c9386b4d572503bb7b6d1ce9547ec30dcded2920',
    'baselineIncludesLinearAccumulator': True,
    'validation': {'buildWarnings': 0, 'buildErrors': 0, 'codegenChecksPassed': 83},
    'cpuDiagnostic': cpu, 'recomputation': regression,
    'moduleProtocol': {'warmups': 4, 'samplesPerProcess': 15,
                       'pairedOrder': ['before-after', 'after-before', 'before-after'],
                       'loadingAndHashingTimed': False, 'translationCreatesFreshTcoNodes': True},
    'modules': modules, 'memoryDiagnostic': memory, 'fullBuilds': full, 'benchmarks': benchmark,
    'cleanup': load('cleanup.json'),
    'excludedAttempt': {'directory': 'validation-enospc', 'reason': 'ENOSPC during second trial; restarted full ABBA after cleanup'},
}
destination.write_text(json.dumps(result, indent=2) + '\n')
print('All compact evidence checks passed:', destination)
