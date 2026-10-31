// Enforce the maintainability conventions that neither the compiler nor the
// formatters can check. The baseline records accepted debt; anything new fails.
import fs from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = fileURLToPath(new URL('..', import.meta.url));
const srcRoot = path.join(root, 'src');
const baselinePath = path.join(root, 'tools', 'conventions-baseline.json');
const updateBaseline = process.argv.includes('--update-baseline');

const moduleLimit = 400;
const functionLimit = 60;

const skipKeywords = new Set([
  'module',
  'import',
  'type',
  'data',
  'newtype',
  'class',
  'instance',
  'derive',
  'foreign',
  'infix',
  'infixl',
  'infixr',
]);

// Layer boundaries: lower layers never depend on orchestration or printing.
const layerRules = [
  {
    layer: /^Phpurs\.PhpAst(\.|$)/,
    allows: (name) => /^Phpurs\.PhpAst(\.|$)/.test(name),
    message: 'PhpAst is a base layer and may only depend on PhpAst modules',
  },
  {
    layer: /^Phpurs\.Optimize(\.|$)/,
    allows: (name) => /^Phpurs\.Optimize(\.|$)/.test(name),
    message: 'Optimize helpers may only depend on Optimize modules',
  },
  {
    layer: /^Phpurs\.ArrayRefs(\.|$)/,
    allows: (name) => /^(Phpurs\.ArrayRefs|Phpurs\.PhpAst)(\.|$)/.test(name),
    message: 'ArrayRefs may only depend on ArrayRefs and PhpAst modules',
  },
  {
    layer: /^Phpurs\.Printer(\.|$)/,
    allows: (name) =>
      /^(Phpurs\.Printer|Phpurs\.PhpAst)(\.|$)/.test(name) || name === 'Phpurs.RuntimeNames',
    message: 'Printer may only depend on Printer, PhpAst and RuntimeNames',
  },
];

const forbiddenImports = [
  {
    from: /^Phpurs\./,
    to: /^Main(\.|$)/,
    message: 'compiler modules must not depend on the Main orchestration layer',
  },
  {
    from: /^Phpurs\.CodeGen(\.|$)/,
    to: /^Phpurs\.Printer(\.|$)/,
    message: 'CodeGen must not depend on Printer; printing happens after lowering',
  },
  {
    from: /^Phpurs\.(PartialBindings|ThunkFusion|StateFusion|CallbackSpecialization|ArrayCallbacks|EnumRegions)$/,
    to: /^Phpurs\.CodeGen\.(Expr|Declarations|Bindings|Context|Tco|Effects)(\.|$)/,
    message: 'TAST passes must not depend on the CodeGen lowering internals',
  },
  {
    from: /^Main\.(Selection|Options|Prepare)$/,
    to: /^Main(\.|$)/,
    message: 'leaf Main modules must not depend on other Main modules',
  },
];

const errors = [];
const warnings = [];

function reportError(file, line, rule, message) {
  errors.push({ file, line, rule, message });
}

function reportWarning(file, line, rule, message) {
  warnings.push({ file, line, rule, message });
}

async function walk(directory) {
  const entries = await fs.readdir(directory, { withFileTypes: true });
  const files = [];
  for (const entry of entries.sort((a, b) => a.name.localeCompare(b.name))) {
    const full = path.join(directory, entry.name);
    if (entry.isDirectory()) files.push(...(await walk(full)));
    else if (entry.isFile() && entry.name.endsWith('.purs')) files.push(full);
  }
  return files;
}

// Count physical lines without an extra empty line for the trailing newline.
function lineCount(source) {
  const lines = source.split('\n');
  return source.endsWith('\n') ? lines.length - 1 : lines.length;
}

// Return top-level declarations with their measured span. Only the first line
// of a declaration and its final content line matter: comments and blank lines
// between declarations are attributed to neither side.
function declarations(source) {
  const lines = source.split('\n');
  const found = [];
  let current = null;
  let blockComment = false;

  const close = () => {
    if (current === null) return;
    found.push({
      name: current.name,
      line: current.line,
      length: current.lastContent - current.line + 1,
    });
    current = null;
  };

  for (let index = 0; index < lines.length; index++) {
    const line = lines[index];
    const trimmed = line.trim();

    if (blockComment) {
      if (line.includes('-}')) blockComment = false;
      continue;
    }
    if (trimmed === '') continue;
    if (line.trimStart().startsWith('{-') && !line.includes('-}')) {
      blockComment = true;
      if (!/^\s/.test(line)) close();
      continue;
    }
    if (/^\s/.test(line)) {
      if (current !== null) current.lastContent = index;
      continue;
    }
    if (line.startsWith('--') || line.startsWith('|')) {
      close();
      continue;
    }

    const keyword = line.match(/^([a-z]+)/)?.[1];
    if (keyword && skipKeywords.has(keyword)) {
      close();
      continue;
    }

    const nameMatch = line.match(/^([A-Za-z_][\w']*|\([^)]*\))/);
    if (!nameMatch) {
      close();
      continue;
    }
    const name = nameMatch[1];
    if (current !== null && current.name === name) continue;
    close();
    current = { name, line: index + 1, lastContent: index };
  }
  close();
  return found;
}

function importsOf(source) {
  return [...source.matchAll(/^import\s+([\w.]+)/gm)].map((match) => ({
    name: match[1],
    line: source.slice(0, match.index).split('\n').length,
  }));
}

function moduleNameOf(source) {
  return source.match(/^module\s+([\w.]+)/m)?.[1] ?? '<unknown>';
}

async function readBaseline() {
  try {
    return JSON.parse(await fs.readFile(baselinePath, 'utf8'));
  } catch (error) {
    if (error.code === 'ENOENT') {
      return { reviewed: '', modules: {}, functions: {}, headers: [], rawSentinel: {} };
    }
    throw error;
  }
}

const relative = (file) => path.relative(root, file).split(path.sep).join('/');
const baseline = await readBaseline();
const files = await walk(srcRoot);

const modules = [];
for (const file of files) {
  const source = await fs.readFile(file, 'utf8');
  const lines = source.split('\n');
  modules.push({
    rel: relative(file),
    source,
    moduleName: moduleNameOf(source),
    size: lineCount(source),
    hasHeader: lines.slice(0, 10).some((line) => line.includes('-- |')),
    declarations: declarations(source),
    imports: importsOf(source),
    sentinels: lines
      .map((line, index) => ({ line, index }))
      .filter(({ line }) => /PhpRaw\s+""/.test(line) && !/\bPhpRaw\s+""\s*->/.test(line)),
  });
}

const baselineModules = baseline.modules ?? {};
const baselineFunctions = baseline.functions ?? {};
const baselineHeaders = baseline.headers ?? [];
const baselineSentinels = baseline.rawSentinel ?? {};

const acceptedModules = {};
const acceptedFunctions = {};
const acceptedHeaders = [];
const acceptedSentinels = {};

// Safety valves are not pass settings: numeric thresholds belong to
// Phpurs.Budget. RewriteLimit's default is a CLI option and stays exempt.
const budgetExempt = new Set(['src/Phpurs/Budget.purs', 'src/Phpurs/RewriteLimit.purs']);
for (const module of modules) {
  if (!budgetExempt.has(module.rel)) {
    module.source.split('\n').forEach((line, index) => {
      const code = line.replace(/--.*$/, '').replace(/"(?:[^"\\]|\\.)*"/g, '""');
      const match = code.match(/\b(?:[1-9][0-9]{2,}|[6-9][0-9])\b/);
      if (match)
        reportError(
          module.rel,
          index + 1,
          'budget-literal',
          `numeric threshold ${match[0]} outside Phpurs.Budget`,
        );
    });
  }

  if (!module.hasHeader) {
    if (updateBaseline || baselineHeaders.includes(module.rel)) acceptedHeaders.push(module.rel);
    else reportError(module.rel, 1, 'header', 'module needs a `-- |` responsibility header');
  }

  if (module.size > moduleLimit) {
    const allowed = baselineModules[module.rel];
    if (updateBaseline || (typeof allowed === 'number' && module.size <= allowed))
      acceptedModules[module.rel] = module.size;
    else
      reportError(
        module.rel,
        1,
        'module-size',
        `module has ${module.size} lines (limit ${moduleLimit}); split it or record justified debt`,
      );
  }

  for (const declaration of module.declarations) {
    if (declaration.length <= functionLimit) continue;
    const key = `${module.rel}::${declaration.name}`;
    const allowed = baselineFunctions[key];
    if (updateBaseline || (typeof allowed === 'number' && declaration.length <= allowed)) {
      acceptedFunctions[key] = declaration.length;
    } else {
      reportError(
        module.rel,
        declaration.line,
        'function-size',
        `${declaration.name} has ${declaration.length} lines (limit ${functionLimit})`,
      );
    }
  }

  if (module.sentinels.length > 0) {
    const allowed = baselineSentinels[module.rel] ?? 0;
    if (updateBaseline || module.sentinels.length <= allowed) {
      acceptedSentinels[module.rel] = module.sentinels.length;
    } else {
      for (const sentinel of module.sentinels.slice(allowed))
        reportError(
          module.rel,
          sentinel.index + 1,
          'raw-sentinel',
          'empty PhpRaw sentinel; use a typed AST node',
        );
    }
  }

  for (const imported of module.imports) {
    if (!/^(Phpurs|Main)(\.|$)/.test(imported.name)) continue;
    // Budget is a leaf of shared safety valves; every layer may use it.
    if (imported.name === 'Phpurs.Budget') continue;
    for (const rule of layerRules) {
      if (!rule.layer.test(module.moduleName) || rule.allows(imported.name)) continue;
      reportError(module.rel, imported.line, 'imports', `${rule.message}: ${imported.name}`);
    }
    for (const rule of forbiddenImports) {
      if (!rule.from.test(module.moduleName) || !rule.to.test(imported.name)) continue;
      reportError(module.rel, imported.line, 'imports', `${rule.message}: ${imported.name}`);
    }
  }
}

// Flag repaid debt so the baseline ratchets down instead of staying stale.
const modulesByPath = new Map(modules.map((module) => [module.rel, module]));
for (const [rel, allowed] of Object.entries(baselineModules)) {
  const module = modulesByPath.get(rel);
  if (!module)
    reportWarning(rel, 1, 'module-size', 'file no longer exists; remove it from the baseline');
  else if (module.size <= moduleLimit)
    reportWarning(rel, 1, 'module-size', 'module is under the limit; remove it from the baseline');
  else if (module.size < allowed)
    reportWarning(
      rel,
      1,
      'module-size',
      `module shrank from ${allowed} to ${module.size}; update the baseline`,
    );
}

const measuredFunctions = new Map();
for (const module of modules)
  for (const declaration of module.declarations)
    measuredFunctions.set(`${module.rel}::${declaration.name}`, declaration.length);
for (const [key, allowed] of Object.entries(baselineFunctions)) {
  const actual = measuredFunctions.get(key);
  if (actual === undefined)
    reportWarning(
      key.split('::')[0],
      1,
      'function-size',
      `${key} no longer exists; remove it from the baseline`,
    );
  else if (actual <= functionLimit)
    reportWarning(
      key.split('::')[0],
      1,
      'function-size',
      `${key} is under the limit; remove it from the baseline`,
    );
  else if (actual < allowed)
    reportWarning(
      key.split('::')[0],
      1,
      'function-size',
      `${key} shrank from ${allowed} to ${actual}; update the baseline`,
    );
}

for (const rel of baselineHeaders) {
  const module = modulesByPath.get(rel);
  if (!module)
    reportWarning(rel, 1, 'header', 'file no longer exists; remove it from the baseline');
  else if (module.hasHeader)
    reportWarning(rel, 1, 'header', 'header added; remove this file from the baseline');
}

for (const [rel, allowed] of Object.entries(baselineSentinels)) {
  const module = modulesByPath.get(rel);
  const actual = module?.sentinels.length ?? 0;
  if (actual === 0)
    reportWarning(rel, 1, 'raw-sentinel', 'sentinels removed; remove this file from the baseline');
  else if (actual < allowed)
    reportWarning(
      rel,
      1,
      'raw-sentinel',
      `sentinels dropped from ${allowed} to ${actual}; update the baseline`,
    );
}

if (updateBaseline) {
  const sortObject = (object) =>
    Object.fromEntries(Object.entries(object).sort(([left], [right]) => left.localeCompare(right)));
  const next = {
    reviewed: new Date().toISOString().slice(0, 10),
    note: 'Accepted maintainability debt. Ratchet down: remove an entry when the file no longer violates the limit.',
    modules: sortObject(acceptedModules),
    functions: sortObject(acceptedFunctions),
    headers: acceptedHeaders.sort(),
    rawSentinel: sortObject(acceptedSentinels),
  };
  await fs.writeFile(baselinePath, `${JSON.stringify(next, null, 2)}\n`);
  console.log(`Baseline written: ${relative(baselinePath)}`);
  console.log(
    `${Object.keys(next.modules).length} modules, ${Object.keys(next.functions).length} functions, ` +
      `${next.headers.length} headers, ${Object.keys(next.rawSentinel).length} sentinel files recorded.`,
  );
  process.exit(0);
}

for (const error of errors)
  console.error(`${error.file}:${error.line}: ${error.rule}: ${error.message}`);
for (const warning of warnings)
  console.warn(`${warning.file}:${warning.line}: ${warning.rule}: ${warning.message}`);

const debt = [
  Object.keys(acceptedModules).length && `${Object.keys(acceptedModules).length} modules`,
  Object.keys(acceptedFunctions).length && `${Object.keys(acceptedFunctions).length} functions`,
  acceptedHeaders.length && `${acceptedHeaders.length} headers`,
  Object.keys(acceptedSentinels).length &&
    `${Object.keys(acceptedSentinels).length} sentinel files`,
].filter(Boolean);

console.log(
  `${modules.length} modules checked: ${errors.length} errors, ${warnings.length} warnings, ` +
    `accepted debt: ${debt.join(', ') || 'none'}.`,
);
if (errors.length > 0) process.exitCode = 1;
