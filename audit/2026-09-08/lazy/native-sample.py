"""Sample only the PHP child launched here, after its three warm-up actions."""
import json
import pathlib
import subprocess
import sys

root = pathlib.Path(__file__).resolve().parent
output = pathlib.Path("/Users/0x1/Documents/htdocs/altbak.pub-phpurs/run/bak/php/output")
label = sys.argv[1] if len(sys.argv) > 1 else 'native'
if label != 'native':
    output = root / 'raw' / label
command = ["php", "-d", "xdebug.mode=off", "-d", "opcache.enable_cli=1",
           "-d", f"opcache.file_cache={output.parent / 'opcache'}",
           "-d", "opcache.jit_buffer_size=128M", "-d", "opcache.jit=1255",
           str(root / "runner.php"), str(output), "sample", "80"]
with (root / f"{label}-run.log").open("w") as log:
    php = subprocess.Popen(command, stdout=subprocess.PIPE, stderr=log, text=True)
    for line in php.stdout:
        log.write(line)
        log.flush()
        if line.strip() == "READY":
            sample = subprocess.run(["/usr/bin/sample", str(php.pid), "5", "1", "-mayDie",
                                     "-file", str(root / f"raw/{label}-sample.txt")],
                                    text=True, capture_output=True)
            (root / f"{label}-sample-status.json").write_text(json.dumps({
                "php_pid": php.pid, "command": command, "sample_returncode": sample.returncode,
                "sample_stdout": sample.stdout, "sample_stderr": sample.stderr}, indent=2) + "\n")
    code = php.wait()
    print(json.dumps({"php_returncode": code, "sample_returncode": sample.returncode}))
    if code or sample.returncode:
        raise SystemExit(1)
