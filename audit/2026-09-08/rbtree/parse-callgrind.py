"""Stream Xdebug's profile; separate self costs from call-edge costs."""
import collections
import json
import pathlib
import re

root = pathlib.Path(__file__).resolve().parent
files, names = {}, {}
stats = collections.defaultdict(lambda: {"calls": 0, "self_ticks": 0, "self_memory_delta": 0})
edges = collections.defaultdict(lambda: {"calls": 0, "inclusive_ticks": 0})
current_file = current_fn = callee = None
pending_calls = None
summary = None
with (root / "raw/cachegrind.rbtree").open() as stream:
    for line in stream:
        if line.startswith(("fl=", "fn=", "cfl=", "cfn=")):
            kind, value = line.rstrip().split("=", 1)
            match = re.fullmatch(r"\((\d+)\)(?: (.*))?", value)
            ident, label = int(match[1]), match[2]
            table = files if kind.endswith("fl") else names
            if label is not None:
                table[ident] = label
            if kind == "fl":
                current_file = ident
            elif kind == "fn":
                current_fn = ident
                stats[ident]["file_id"] = current_file
            elif kind == "cfn":
                callee = ident
        elif line.startswith("calls="):
            pending_calls = int(line.split("=", 1)[1].split()[0])
        elif line.startswith("summary:"):
            summary = [int(value) for value in line.split()[1:]]
        elif line and line[0].isdigit():
            location, ticks, memory = map(int, line.split())
            if pending_calls is not None:
                stats[callee]["calls"] += pending_calls
                edge = edges[current_fn, callee]
                edge["calls"] += pending_calls
                edge["inclusive_ticks"] += ticks
                pending_calls = None
            else:
                stats[current_fn]["self_ticks"] += ticks
                stats[current_fn]["self_memory_delta"] += memory

total = sum(row["self_ticks"] for row in stats.values())
functions = []
for ident, row in sorted(stats.items(), key=lambda item: -item[1]["self_ticks"]):
    functions.append({"name": names[ident], "file": files[row["file_id"]],
                      "calls": row["calls"], "self_ms": row["self_ticks"] / 100000,
                      "self_percent": row["self_ticks"] / total * 100,
                      "self_memory_delta_bytes": row["self_memory_delta"]})
result = {"events": ["Time_(10ns)", "Memory_(bytes)"], "summary": summary,
          "self_sum_ticks": total, "self_sum_ms": total / 100000,
          "note": "Recursive edge inclusive times overlap; memory deltas are not allocation counts.",
          "functions": functions,
          "edges": [{"caller": names[caller], "callee": names[callee],
                     "calls": row["calls"], "inclusive_ms": row["inclusive_ticks"] / 100000}
                    for (caller, callee), row in sorted(edges.items(), key=lambda item: -item[1]["inclusive_ticks"])]}
(root / "callgrind-summary.json").write_text(json.dumps(result, indent=2) + "\n")
print(json.dumps({"summary_ms": summary[0] / 100000, "self_sum_ms": total / 100000,
                  "top": functions[:16]}, indent=2))
