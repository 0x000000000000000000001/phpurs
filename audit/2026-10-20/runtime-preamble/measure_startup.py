"""Measure module loading with and without OPcache for two output trees."""
import argparse
import json
import statistics
import subprocess
from pathlib import Path

ROOT = Path(__file__).resolve().parent


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--control', type=Path, required=True)
    parser.add_argument('--selective', type=Path, required=True)
    parser.add_argument('--autoload', type=Path, required=True)
    args = parser.parse_args()
    rows = []
    for opcache in [0, 1]:
        for variant, entry in [('control', args.control), ('selective', args.selective)]:
            samples = []
            for _ in range(5):
                run = subprocess.run(['php', '-d', 'xdebug.mode=off', f'-d', f'opcache.enable_cli={opcache}',
                                      '-d', 'opcache.file_cache=', '-d', 'opcache.jit=disable',
                                      str(ROOT / 'startup.php'), str(entry), str(args.autoload)],
                                     text=True, capture_output=True, timeout=120)
                assert run.returncode == 0 and not run.stderr, (opcache, variant, run.stdout, run.stderr)
                samples.append(json.loads(run.stdout)['ms'])
            rows.append({'opcache': opcache, 'variant': variant, 'samples': samples,
                         'median': statistics.median(samples)})
            print(opcache, variant, statistics.median(samples), flush=True)
    (ROOT / 'startup.json').write_text(json.dumps(rows, indent=2) + '\n')


if __name__ == '__main__':
    main()
