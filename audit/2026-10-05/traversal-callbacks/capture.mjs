// Capture the optimized traversal sites while rebuilding through PHPurs.
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import {mainWithCache} from '../../../output/Main/index.js';
import {monadEffectAff} from '../../../output/Effect.Aff/index.js';
import {Nothing} from '../../../output/Data.Maybe/index.js';

const artifacts=process.env.PHPURS_TRAVERSAL_AUDIT;
assert.ok(artifacts);
const wanted=new Set(['Test.ListOps','Test.ArrayOps','Test.Primes']);
const seen=new Set();
const lift=monadEffectAff.liftEffect;
function tagged(_key,value) {
  if(value && typeof value==='object' && !Array.isArray(value)) {
    const prototype=Object.getPrototypeOf(value);
    if(prototype && prototype!==Object.prototype) return {$ctor:prototype.constructor.name,...value};
  }
  return value;
}
process.on('exit',code=>{ if(code===0) assert.deepEqual(seen,wanted); });
mainWithCache({
  load:()=>lift(()=>Nothing.value),
  store:state=>lift(()=>{
    const {name,bindings,dataDecls}=state.backend;
    if(wanted.has(name)) {
      assert.ok(!seen.has(name));
      seen.add(name);
      fs.writeFileSync(path.join(artifacts,name+'.optimized.json'),JSON.stringify({name,bindings,dataDecls},tagged,2)+'\n');
    }
  }),
})();
