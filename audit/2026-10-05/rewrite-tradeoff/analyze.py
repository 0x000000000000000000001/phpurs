"""Summarize per-binding passes and compare schedules independently of guard values."""
from collections import Counter, defaultdict
import hashlib
import json
from pathlib import Path
import sys


def analyze(path):
    data = json.loads(path.read_text())
    assert data['code'] == 0
    histogram = Counter()
    modules = defaultdict(lambda: {'bindings': 0, 'passes': 0, 'maxPasses': 0,
                                   'chunkBindings': 0, 'chunkNodes': 0, 'chunkLeaves': 0})
    normalized = []
    for row in data['bindings']:
        passes = row['passes']
        assert passes and not passes[-1]['rewrite'] and all(p['rewrite'] for p in passes[:-1])
        assert all(p['remaining'] == row['limit'] - i for i, p in enumerate(passes))
        assert all(p['size'] <= 2000 for p in passes[:-1])
        assert bool(row['chunkNodes']) == (passes[-1]['size'] > 2000)
        count = len(passes)
        histogram[count] += 1
        module = modules[row['module']]
        module['bindings'] += 1
        module['passes'] += count
        module['maxPasses'] = max(module['maxPasses'], count)
        module['chunkBindings'] += bool(row['chunkNodes'])
        module['chunkNodes'] += row['chunkNodes']
        module['chunkLeaves'] += row['chunkLeaves']
        normalized.append({**{key: value for key, value in row.items() if key not in ['limit', 'passes']},
                           'passes': [{key: value for key, value in p.items() if key != 'remaining'} for p in passes]})
    maximum = max(histogram)
    return {
        'bindings': len(data['bindings']), 'totalPasses': sum(k * v for k, v in histogram.items()),
        'passHistogram': dict(sorted(histogram.items())), 'maximumPasses': maximum,
        'chunkBindings': sum(m['chunkBindings'] for m in modules.values()),
        'chunkNodes': sum(m['chunkNodes'] for m in modules.values()),
        'chunkLeaves': sum(m['chunkLeaves'] for m in modules.values()),
        'maximumBindings': [row['module'] + '.' + row['binding'] for row in data['bindings'] if len(row['passes']) == maximum],
        'scheduleSHA256': hashlib.sha256(json.dumps(normalized, sort_keys=True, separators=(',', ':')).encode()).hexdigest(),
        'modules': dict(sorted(modules.items())),
    }


if __name__ == '__main__':
    result = analyze(Path(sys.argv[1]))
    Path(sys.argv[2]).write_text(json.dumps(result, indent=2) + '\n')
    print(json.dumps({key: value for key, value in result.items() if key != 'modules'}, indent=2))
