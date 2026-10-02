import fs from 'node:fs';
import path from 'node:path';
import v8 from 'node:v8';
import { createHash, randomUUID } from 'node:crypto';
import * as Semantics from '../PureScript.Backend.Optimizer.Semantics/index.js';
import * as Syntax from '../PureScript.Backend.Optimizer.Syntax/index.js';
import * as CoreFn from '../PureScript.Backend.Optimizer.CoreFn/index.js';
import * as Analysis from '../PureScript.Backend.Optimizer.Analysis/index.js';
import * as DataMap from '../Data.Map.Internal/index.js';
import * as DataTuple from '../Data.Tuple/index.js';
import * as DataMaybe from '../Data.Maybe/index.js';
import * as DataEither from '../Data.Either/index.js';
import * as DataList from '../Data.List.Types/index.js';
import * as DataNonEmptyArray from '../Data.Array.NonEmpty.Internal/index.js';

const magic = Buffer.from('PHPURS-MODULE-STATE 1\n');
const digest = bytes => createHash('sha256').update(bytes).digest('hex');
const prototypes = new Map();
const tags = new Map();
for (const [prefix, exports] of Object.entries({ Semantics, Syntax, CoreFn, Analysis, DataMap, DataTuple, DataMaybe, DataEither, DataList, DataNonEmptyArray })) {
  for (const [name, value] of Object.entries(exports)) {
    if (typeof value === 'function' && /^[A-Z]/.test(name) && value.prototype) {
      const tag = `${prefix}.${name}`;
      if (!prototypes.has(value.prototype)) prototypes.set(value.prototype, tag);
      tags.set(tag, value.prototype);
    }
  }
}

// Flatten to a graph table. This preserves shared constructors without mutating
// PBO's objects, and avoids recursive traversal of deeply nested expressions.
function encode(state) {
  const seen = new Map();
  const nodes = [];
  const pending = [];
  const ref = value => {
    if (value === null || typeof value !== 'object') {
      if (typeof value === 'function' || typeof value === 'symbol') throw new Error('Non-data cache value');
      return value;
    }
    if (!seen.has(value)) {
      seen.set(value, nodes.length);
      nodes.push(null);
      pending.push(value);
    }
    return { ref: seen.get(value) };
  };
  const root = ref(state);
  while (pending.length) {
    const value = pending.pop();
    let node;
    if (Array.isArray(value)) {
      if (Object.keys(value).length !== value.length) throw new Error('Non-dense cache array');
      node = ['array', value.map(ref)];
    } else {
      const proto = Object.getPrototypeOf(value);
      const tag = proto === Object.prototype ? 'record' : proto === null ? 'null-record' : prototypes.get(proto);
      if (!tag) throw new Error('Unknown cache constructor');
      node = [tag, Object.entries(value).map(([key, child]) => [key, ref(child)])];
    }
    nodes[seen.get(value)] = node;
  }
  return v8.serialize({ root, nodes });
}

function decode(bytes) {
  const graph = v8.deserialize(bytes);
  if (!Array.isArray(graph.nodes)) throw new Error('Invalid cache graph');
  const values = graph.nodes.map(([tag, fields]) => {
    if (!Array.isArray(fields)) throw new Error('Invalid cache fields');
    if (tag === 'array') return new Array(fields.length);
    if (tag === 'record') return {};
    if (tag === 'null-record') return Object.create(null);
    if (!tags.has(tag)) throw new Error('Unknown cache constructor tag');
    return Object.create(tags.get(tag));
  });
  const deref = value => {
    if (value === null || typeof value !== 'object') return value;
    if (!Number.isInteger(value.ref) || value.ref < 0 || value.ref >= values.length) throw new Error('Invalid cache reference');
    return values[value.ref];
  };
  graph.nodes.forEach(([tag, fields], index) => {
    if (tag === 'array') fields.forEach((value, i) => { values[index][i] = deref(value); });
    else {
      const keys = new Set();
      for (const [key, value] of fields) {
        if (typeof key !== 'string' || keys.has(key)) throw new Error('Invalid cache field');
        keys.add(key);
        Object.defineProperty(values[index], key, { value: deref(value), writable: true, enumerable: true, configurable: true });
      }
    }
  });
  return deref(graph.root);
}

const isMap = value => value instanceof DataMap.Leaf || value instanceof DataMap.Node;
const isText = value => value instanceof DataMaybe.Nothing || (value instanceof DataMaybe.Just && typeof value.value0 === 'string');
function validState(state) {
  const backend = state?.backend;
  return backend && typeof backend.name === 'string' && isMap(state.arities)
    && isText(state.modularPhp) && isText(state.bundlePhp)
    && ['comments', 'bindings', 'dataDecls', 'classDecls'].every(key => Array.isArray(backend[key]))
    && ['imports', 'dataTypes', 'exports', 'reExports', 'foreign', 'implementations', 'directives'].every(key => isMap(backend[key]));
}

function target(directory, key) {
  if (!/^[0-9a-f]{64}$/.test(key)) throw new Error('Invalid cache fingerprint');
  return path.join(directory, 'v1', key + '.bin');
}

export const loadModuleStateImpl = just => nothing => directory => key => moduleName => () => {
  try {
    const bytes = fs.readFileSync(target(directory, key));
    if (!bytes.subarray(0, magic.length).equals(magic)) return nothing;
    const end = bytes.indexOf(10, magic.length);
    if (end < 0) return nothing;
    const header = JSON.parse(bytes.subarray(magic.length, end).toString('utf8'));
    if (header.key !== key || header.moduleName !== moduleName) return nothing;
    const payload = bytes.subarray(end + 1);
    if (header.sha256 !== digest(payload)) return nothing;
    const state = decode(payload);
    if (!validState(state) || state.backend.name !== moduleName) return nothing;
    return just(state);
  } catch {
    return nothing;
  }
};

export const saveModuleStateImpl = directory => key => state => () => {
  let temporary;
  let fd;
  try {
    if (!validState(state)) return false;
    const file = target(directory, key);
    const payload = encode(state);
    const header = Buffer.from(JSON.stringify({ key, moduleName: state.backend.name, sha256: digest(payload) }) + '\n');
    fs.mkdirSync(path.dirname(file), { recursive: true });
    temporary = file + '.' + randomUUID() + '.tmp';
    fd = fs.openSync(temporary, 'wx');
    fs.writeFileSync(fd, Buffer.concat([magic, header, payload]));
    fs.closeSync(fd);
    fd = undefined;
    fs.renameSync(temporary, file);
    temporary = undefined;
    return true;
  } catch {
    return false;
  } finally {
    if (fd !== undefined) { try { fs.closeSync(fd); } catch {} }
    if (temporary !== undefined) { try { fs.unlinkSync(temporary); } catch {} }
  }
};
