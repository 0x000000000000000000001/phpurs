"""Reuse manifest/driver helpers from the preceding integration audit."""
import importlib.util
from pathlib import Path

ROOT = Path(__file__).resolve().parent
spec = importlib.util.spec_from_file_location('callback_audit_helpers', ROOT.parent / 'callback-specialization/prepare.py')
helpers = importlib.util.module_from_spec(spec)
spec.loader.exec_module(helpers)
REPO, WORKSPACE, BENCH = helpers.REPO, helpers.WORKSPACE, helpers.BENCH
digest, save, php_manifest, load_driver = helpers.digest, helpers.save, helpers.php_manifest, helpers.load_driver
MODULE = 'Test.ArrayOps/index.php'
VARIANTS = ['baseline', 'fold', 'filter', 'integrated']
FLAGS = ['-d', 'xdebug.mode=off', '-d', 'opcache.enable_cli=1', '-d', 'opcache.file_cache=',
         '-d', 'opcache.file_update_protection=0', '-d', 'opcache.jit_buffer_size=128M', '-d', 'opcache.jit=1255']
