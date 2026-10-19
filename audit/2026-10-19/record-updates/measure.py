"""Run the Records prototype in mirrored processes and write medians."""
import argparse
import json
import statistics
import subprocess
from pathlib import Path

ROOT = Path(__file__).resolve().parent
FLAGS = ['-d', 'xdebug.mode=off', '-d', 'opcache.enable_cli=1', '-d', 'opcache.file_cache=',
         '-d', 'opcache.file_update_protection=0', '-d', 'opcache.jit_buffer_size=128M', '-d', 'opcache.jit=1255']
ORDER = ['baseline', 'fused', 'fused', 'baseline', 'baseline', 'fused', 'fused', 'baseline']


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--artifacts', type=Path, required=True)
    args = parser.parse_args()
    artifacts = args.artifacts.resolve()
    rows = []
    for variant in ORDER:
        run = subprocess.run(['php', *FLAGS, str(ROOT / 'measure-records.php'), variant, str(artifacts)],
                             text=True, capture_output=True, timeout=120)
        assert run.returncode == 0 and not run.stderr, (variant, run.stdout, run.stderr)
        rows.append(json.loads(run.stdout))
        print(variant, rows[-1]['medianUs'], flush=True)
    summary = {}
    for variant in ['baseline', 'fused']:
        values = [row['medianUs'] for row in rows if row['variant'] == variant]
        summary[variant] = {'samplesUs': values, 'medianUs': statistics.median(values)}
    (artifacts / 'records-abba.json').write_text(json.dumps({'rows': rows, 'summary': summary}, indent=2) + '\n')
    print(json.dumps(summary, indent=2))


if __name__ == '__main__':
    main()
