import * as adt from './adt.mjs';
import * as arrays from './arrays.mjs';
import * as branches from './branches.mjs';
import * as bundle from './bundle.mjs';
import * as entrypoints from './entrypoints.mjs';
import * as ffi from './ffi.mjs';
import * as nullable from './nullable.mjs';
import * as runtime from './runtime.mjs';
import * as numbers from './numbers.mjs';
import * as records from './records.mjs';
import * as strings from './strings.mjs';
import * as tco from './tco.mjs';

// Adding a case: create `cases/<name>.mjs` exporting `name` and `render()`,
// then register it here and run `npm run golden:update`.
export const cases = [
  adt,
  tco,
  records,
  strings,
  numbers,
  arrays,
  branches,
  nullable,
  ffi,
  runtime,
  entrypoints,
  bundle,
];
