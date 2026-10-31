// fast-check properties for the structural contracts shared by every pass.
import assert from 'node:assert/strict';
import test from 'node:test';
import fc from 'fast-check';
import { Just, Nothing } from '../../../output/Data.Maybe/index.js';
import { empty } from '../../../output/Data.Map/index.js';
import { toUnfoldable } from '../../../output/Data.Set/index.js';
import { unfoldableArray } from '../../../output/Data.Unfoldable/index.js';
import { Right } from '../../../output/Data.Either/index.js';
import * as Ast from '../../../output/Phpurs.PhpAst/index.js';
import { children, mapChildren } from '../../../output/Phpurs.PhpAst.Traversal/index.js';
import { printExpr } from '../../../output/Phpurs.Printer/index.js';
import { rename } from '../../../output/Phpurs.TailInline.Rewrite/index.js';
import { fingerprintBytes, NoForeign, planKeys } from '../../../output/Phpurs.CacheKey/index.js';
import { freeVars } from '../../../output/Phpurs.FreeVars/index.js';
import { analyze } from '../../../output/PureScript.Backend.Optimizer.Codegen.Tco/index.js';
import * as S from '../../../output/PureScript.Backend.Optimizer.Syntax/index.js';
import * as C from '../../../output/PureScript.Backend.Optimizer.CoreFn/index.js';

const leaf = fc.oneof(
  fc.integer({ min: -1000, max: 1000 }).map((n) => new Ast.PhpInt(n)),
  fc.stringMatching(/^[a-zA-Z_][a-zA-Z0-9_]*$/).map((s) => new Ast.PhpVar(s)),
  fc.constant(Ast.PhpNull.value),
  fc.boolean().map((b) => new Ast.PhpBoolean(b)),
);
const expr = fc.letrec((tie) => ({
  expr: fc.oneof(
    { maxDepth: 3 },
    leaf,
    fc.array(tie('expr'), { maxLength: 3 }).map((xs) => new Ast.PhpCall(new Ast.PhpVar('f'), xs)),
    fc
      .array(tie('expr'), { maxLength: 2 })
      .map((xs) => new Ast.PhpIf(new Ast.PhpBoolean(true), xs, [])),
    fc.array(tie('expr'), { maxLength: 2 }).map((xs) => new Ast.PhpArray(xs)),
    fc.tuple(tie('expr'), tie('expr')).map(([a, b]) => new Ast.PhpBinOp('+', a, b)),
    tie('expr').map((e) => new Ast.PhpReturn(e)),
  ),
})).expr;

test('property: mapChildren identity preserves every expression', () => {
  fc.assert(
    fc.property(expr, (e) => {
      assert.deepEqual(mapChildren((x) => x)(e), e);
      assert.equal(children(e).length, children(e).length);
    }),
    { numRuns: 60 },
  );
});

test('property: free variables are monotone over application and arrays', () => {
  const local = fc.string({ minLength: 1, maxLength: 6 }).map((s) => new S.Local(new Just(s), 0));
  const int = fc.integer().map((n) => new S.Lit(new C.LitInt(n)));
  const syntax = fc.letrec((tie) => ({
    syn: fc.oneof(
      { maxDepth: 3 },
      local,
      int,
      fc
        .tuple(tie('syn'), fc.array(tie('syn'), { maxLength: 3 }))
        .map(([head, tail]) => new S.App(head, tail)),
      fc.array(tie('syn'), { maxLength: 3 }).map((xs) => new S.Lit(new C.LitArray(xs))),
    ),
  })).syn;
  const names = (s) => toUnfoldable(unfoldableArray)(freeVars(analyze([])(s))).sort();
  fc.assert(
    fc.property(syntax, syntax, (a, b) => {
      const expected = [...new Set([...names(a), ...names(b)])].sort();
      assert.deepEqual(names(new S.App(a, [b])), expected);
      assert.deepEqual(names(new S.Lit(new C.LitArray([a, b]))), expected);
    }),
    { numRuns: 60 },
  );
});

test('property: renaming composes without collisions', () => {
  fc.assert(
    fc.property(
      expr,
      fc.string({ minLength: 1, maxLength: 5 }),
      fc.string({ minLength: 1, maxLength: 5 }),
      (e, p, q) => {
        assert.deepEqual(rename(p)(rename(q)(e)), rename(p + q)(e));
      },
    ),
    { numRuns: 40 },
  );
});

test('property: cache keys are deterministic and sensitive', () => {
  const context = (version, backend) => ({
    toolchain: {
      phpursVersion: version,
      pboVersion: 'pbo',
      backend: fingerprintBytes(Buffer.from(backend)),
      nodeVersion: process.versions.node,
      v8Version: process.versions.v8,
      platform: process.platform,
      arch: process.arch,
    },
    options: {
      cwd: process.cwd(),
      outputDir: 'output',
      ffiRoots: ['.'],
      emitModules: true,
      emitBundle: false,
      mainModule: Nothing.value,
      autoloadPath: Nothing.value,
      rewriteLimit: 10000,
    },
    directives: fingerprintBytes(Buffer.from('directives')),
  });
  const input = (name) => ({
    name,
    coreFn: fingerprintBytes(Buffer.from(name)),
    foreignInput: NoForeign.value,
    dependencies: [],
  });
  fc.assert(
    fc.property(fc.string({ maxLength: 20 }), (fs) => {
      const plan = (version, backend) => {
        const result = planKeys(context(version, backend))([input('Main')]);
        assert.ok(result instanceof Right, result.value0);
        return result.value0.key;
      };
      assert.equal(plan('v1', fs), plan('v1', fs));
      assert.notEqual(plan('v1', fs), plan('v2', fs));
      assert.notEqual(plan('v1', fs), plan('v1', fs + '!'));
    }),
    { numRuns: 40 },
  );
});

test('property: printer emits no error marker and balanced parentheses', () => {
  fc.assert(
    fc.property(expr, (e) => {
      const printed = printExpr(empty)(e);
      assert.doesNotMatch(printed, /ERROR|Impossible|undefined/);
      let depth = 0;
      for (const c of printed) {
        if (c === '(') depth++;
        else if (c === ')') depth--;
        assert.ok(depth >= 0, printed);
      }
      assert.equal(depth, 0, printed);
    }),
    { numRuns: 60 },
  );
});
