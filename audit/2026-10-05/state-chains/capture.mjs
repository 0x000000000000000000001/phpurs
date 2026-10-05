// Rebuild through the normal pipeline and record the optimized State module.
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import {mainWithCache} from '../../../output/Main/index.js';
import {monadEffectAff} from '../../../output/Effect.Aff/index.js';
import {Nothing} from '../../../output/Data.Maybe/index.js';

const artifacts=process.env.PHPURS_STATE_AUDIT;
assert.ok(artifacts);
const lift=monadEffectAff.liftEffect;
let captured=false;
function tagged(_key,value) {
  if(value && typeof value==='object' && !Array.isArray(value)) {
    const prototype=Object.getPrototypeOf(value);
    if(prototype && prototype!==Object.prototype) return {$ctor:prototype.constructor.name,...value};
  }
  return value;
}
process.on('exit',code=>{
  if(code===0) assert.ok(captured,'State module not captured');
});
mainWithCache({
  load:()=>lift(()=>Nothing.value),
  store:state=>lift(()=>{
    if(state.backend.name==='Test.StateMonad') {
      assert.equal(captured,false);
      captured=true;
      fs.writeFileSync(path.join(artifacts,'optimized.json'),JSON.stringify({
        name:state.backend.name,bindings:state.backend.bindings,dataDecls:state.backend.dataDecls,
      },tagged,2)+'\n');
    }
  }),
})();
