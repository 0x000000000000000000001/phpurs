import { performance } from 'node:perf_hooks';

const labels = ['corefn.read', 'corefn.decode', 'corefn.sort', 'ffi', 'cache.plan',
  'cache.load', 'optimize', 'translate', 'print', 'php.mkdir', 'php.write',
  'cache.store', 'entrypoint', 'composer', 'cleanup', 'diagnostics'];
const empty = () => ({ calls: 0, completed: 0, failed: 0, cancelled: 0, ms: 0, maxMs: 0 });
const moduleRow = (profile, name) => {
  if (!profile.modules.has(name)) profile.modules.set(name, { name, source: null, phases: {} });
  return profile.modules.get(name);
};

export const disabled = null;
export const enabled = profile => profile !== null;
export const create = () => ({ started: performance.now(), active: new Set(), optimization: null,
  phases: Object.fromEntries(labels.map(label => [label, empty()])), modules: new Map() });

export const begin = profile => label => name => () => {
  if (profile === null) return null;
  const span = { profile, label, name, started: performance.now() };
  profile.active.add(span);
  return span;
};

export const finish = span => status => () => {
  if (span === null || !span.profile.active.delete(span)) return;
  const elapsed = performance.now() - span.started;
  const add = phases => {
    const metric = phases[span.label] ??= empty();
    metric.calls++;
    metric[status]++;
    metric.ms += elapsed;
    metric.maxMs = Math.max(metric.maxMs, elapsed);
  };
  add(span.profile.phases);
  if (span.name !== '') add(moduleRow(span.profile, span.name).phases);
};

export const measurePure = profile => label => name => action => () => {
  if (profile === null) return action();
  const span = begin(profile)(label)(name)();
  try {
    const result = action();
    finish(span)('completed')();
    return result;
  } catch (error) {
    finish(span)('failed')();
    throw error;
  }
};

// PHPurs uses the sequential PBO builder. These boundaries exclude cache lookup,
// PHP codegen and post-codegen purmeta publication, and include optimizer setup.
export const beginOptimization = profile => name => () => {
  if (profile === null) return;
  moduleRow(profile, name).source = 'optimized';
  profile.optimization = begin(profile)('optimize')(name)();
};
export const endOptimization = profile => () => {
  if (profile === null) return;
  finish(profile.optimization)('completed')();
  profile.optimization = null;
};
export const restoredModule = profile => name => () => {
  if (profile !== null) moduleRow(profile, name).source = 'cache';
};

export const reportJson = profile => status => () => {
  // A synchronous optimizer exception can bypass onCodegenModule. Retain that
  // partial interval under the build's failure/cancellation outcome.
  for (const span of profile.active) finish(span)(status)();
  return JSON.stringify({ version: 1, status, totalMs: performance.now() - profile.started,
    phases: profile.phases, modules: [...profile.modules.values()] });
};
