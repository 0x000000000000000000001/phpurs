"""Build baseline and fused Record copies from the frozen benchmark output."""
import argparse
import hashlib
import json
import shutil
from pathlib import Path

ROOT = Path(__file__).resolve().parent
REPO = ROOT.parents[2]
SOURCE = REPO.parents[1] / 'altbak.pub-phpurs/run/bak/php/modes/pure/output/Test.Records/index.php'
OLD = '''  $__obj0 = clone $v1_1;
  ($__obj0)->{'a'} = (($v1_1)->{'a'} + 1);
  $__obj1 = clone ($v1_1)->{'b'};
  ($__obj1)->{'c'} = ((($v1_1)->{'b'})->{'c'} + 2);
  $__obj2 = clone (($v1_1)->{'b'})->{'d'};
  ($__obj2)->{'e'} = (((($v1_1)->{'b'})->{'d'})->{'e'} + 3);
  ($__obj2)->{'f'} = (((($v1_1)->{'b'})->{'d'})->{'f'} + ($v_0 % 5));
  ($__obj1)->{'d'} = $__obj2;
  ($__obj0)->{'b'} = $__obj1;'''
NEW = '''  $__obj0 = (object)['a' => (($v1_1)->{'a'} + 1), 'b' => (object)['c' => ((($v1_1)->{'b'})->{'c'} + 2), 'd' => (object)['e' => (((($v1_1)->{'b'})->{'d'})->{'e'} + 3), 'f' => (((($v1_1)->{'b'})->{'d'})->{'f'} + ($v_0 % 5))]]];'''


def digest(path):
    return hashlib.sha256(path.read_bytes()).hexdigest()


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--artifacts', type=Path, required=True)
    args = parser.parse_args()
    artifacts = args.artifacts.resolve()
    assert not artifacts.exists()
    artifacts.mkdir(parents=True)
    baseline = artifacts / 'baseline'
    fused = artifacts / 'fused'
    for name, target in [('baseline', baseline), ('fused', fused)]:
        shutil.copytree(SOURCE.parent.parent, target)
        if name == 'fused':
            file = target / 'Test.Records/index.php'
            text = file.read_text()
            assert text.count(OLD) == 1
            file.write_text(text.replace(OLD, NEW))
    result = {'sourceSHA256': digest(SOURCE), 'baseline': str(baseline), 'fused': str(fused)}
    (artifacts / 'preparation.json').write_text(json.dumps(result, indent=2) + '\n')
    print(json.dumps(result, indent=2))


if __name__ == '__main__':
    main()
