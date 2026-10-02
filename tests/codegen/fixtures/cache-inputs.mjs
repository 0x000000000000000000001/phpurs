import fs from 'node:fs';
import path from 'node:path';

export function write(root, file, contents) {
  const target = path.join(root, file);
  fs.mkdirSync(path.dirname(target), { recursive: true });
  fs.writeFileSync(target, contents);
}
const variable = (moduleName, identifier, type) => ({
  type: 'Var', annotation: { type }, value: { ...(moduleName ? { moduleName: moduleName.split('.') } : {}), identifier },
});
const literal = value => ({ type: 'Literal', annotation: { type: 0 }, value: { literalType: 'IntLiteral', value } });
const app = (abstraction, argument, type) => ({ type: 'App', annotation: { type }, abstraction, argument });
const abs = (argument, body, type) => ({ type: 'Abs', annotation: { type }, argument, body });
const binding = (identifier, expression, type) => ({ bindType: 'NonRec', annotation: { type }, identifier, expression });
export function corefn(name, imports = [], decls = [], foreign = [], comments = []) {
  return {
    builtWith: '0.15.16', moduleName: name.split('.'), modulePath: `src/${name}.purs`,
    sourceSpan: { start: [1, 1], end: [1, 1] },
    imports: imports.map(name => ({ moduleName: name.split('.'), annotation: {} })),
    exports: [...decls.map(d => d.identifier), ...foreign], foreign,
    foreignAnnotations: Object.fromEntries(foreign.map(name => [name, { type: name === 'emit' ? 5 : 2 }])),
    decls, reExports: {}, comments: comments.map(LineComment => ({ LineComment })),
    typeTable: ['Int', { type: 'Func', args: [0], ret: 0 }, { type: 'Func', args: [0], ret: 1 },
      'Unit', { type: 'Adt', fqn: ['Effect', 'Effect'], args: [3] }, { type: 'Func', args: [0], ret: 4 }],
    dataDecls: [], classDecls: [],
  };
}

export function writeFixture(root) {
  const inputs = [
    corefn('Library', [], [
      binding('constant', literal(42), 0),
      binding('keep', abs('x', abs('y', variable(null, 'x', 0), 1), 2), 2),
    ], ['add'], ['@inline export keep never']),
    corefn('Consumer', ['Library'], [
      binding('answer', app(app(variable('Library', 'keep', 2), variable('Library', 'constant', 0), 1), literal(0), 0), 0),
      binding('partial', app(variable('Library', 'add', 2), literal(1), 1), 1),
    ]),
    corefn('Main', ['Consumer'], [
      binding('main', app(variable('Main', 'emit', 5), app(variable('Consumer', 'partial', 1), variable('Consumer', 'answer', 0), 0), 4), 4),
    ], ['emit']),
  ];
  const foreign = {
    Library: '<?php\n$exports["add"] = function($a, $b) { return $a + $b; };\n',
    Main: '<?php\n$exports["emit"] = function($value) { return function() use ($value) { echo $value . "\\n"; }; };\n',
  };
  for (const [name, source] of Object.entries(foreign)) write(root, `src/${name}.php`, source);
  for (const input of inputs) write(root, `output/${input.moduleName.join('.')}/corefn.json`, JSON.stringify(input));
  return { inputs, foreign };
}
