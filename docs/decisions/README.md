# Decision records

Short records for choices that are expensive to reverse: pass protocol, FFI
contract, runtime ABI, budgets, documentation, cache identity and async
semantics. New records copy the template below; superseded records keep their
text and change their status.

| ADR | Decision | Status |
| --- | --- | --- |
| [0001](0001-passes-are-pure.md) | Passes are pure module rewrites | accepted |
| [0002](0002-ffi-is-an-inline-fragment.md) | FFI stays an inline PHP fragment | accepted |
| [0003](0003-runtime-abi-is-versioned.md) | The PHP runtime ABI is versioned | accepted |
| [0004](0004-budgets-are-safety-valves.md) | Budgets are safety valves | accepted |
| [0005](0005-docs-language-and-structure.md) | English docs, French working backlog | accepted |
| [0006](0006-cache-key-includes-preceding-modules.md) | Cache keys include the preceding stream | accepted |
| [0007](0007-effectfnaff-replays.md) | `EffectFnAff` values replay | accepted |

## Template

```markdown
# ADR-00N — Title

- Status: proposed | accepted | superseded by ADR-00M
- Date: YYYY-MM-DD
- Context: what forces a decision
- Decision: the chosen option
- Alternatives: what was rejected and why
- Consequences: costs, follow-ups, tests
- References: tasks, docs, tests, PRs
```

## Review log

- 2026-10-31 — initial review: ADR-001…007 accepted; budgets centralized in
  `Phpurs.Budget`; three module exceptions (`CodeGen/Expr`, `EnumRegions`,
  `Printer/Expr`) and sixteen function exceptions remain, each tracked by M3.
  Next review: 2027-01-31.
