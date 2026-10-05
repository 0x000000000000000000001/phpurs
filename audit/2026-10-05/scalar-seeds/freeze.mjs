// Freeze both compilers from identical generated JS; replace only ThunkFusion.
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import {fileURLToPath} from 'node:url';
import {createHash} from 'node:crypto';
import {build} from 'esbuild';

const repo=fileURLToPath(new URL('../../../',import.meta.url));
const artifacts=path.resolve(process.argv[2]);
const target=path.join(repo,'output/Phpurs.ThunkFusion/index.js');
const old=fs.readFileSync(path.join(artifacts,'baseline-thunk-fusion.js'),'utf8');
const sources=new Map();
const hash=x=>createHash('sha256').update(x).digest('hex');
const results={};
for(const variant of ['integrated','baseline']) {
  const output=await build({
    absWorkingDir:repo,
    stdin:{contents:"import {main} from './output/Main/index.js'; main();",resolveDir:repo,sourcefile:'audit-entry.js'},
    bundle:true,platform:'node',format:'cjs',write:false,metafile:true,
    plugins:[{name:'frozen-inputs',setup(builder) {
      builder.onLoad({filter:/\.js$/},({path:file})=>{
        if(variant==='integrated') sources.set(file,fs.readFileSync(file,'utf8'));
        assert.ok(sources.has(file),file);
        return {contents:variant==='baseline' && file===target ? old : sources.get(file),loader:'js'};
      });
    }}],
  });
  const bytes=output.outputFiles[0].contents;
  fs.writeFileSync(path.join(artifacts,variant+'.cjs'),bytes);
  results[variant]={sha256:hash(bytes),bytes:bytes.length,thunkFusionSHA256:hash(variant==='baseline'?old:sources.get(target))};
}
assert.notEqual(old,sources.get(target));
for(const [file,source] of sources) assert.equal(fs.readFileSync(file,'utf8'),source,'compiler changed during freeze: '+file);
const inputs=Object.fromEntries([...sources].sort().map(([file,source])=>[path.relative(repo,file),hash(source)]));
fs.writeFileSync(path.join(artifacts,'compiler-inputs.json'),JSON.stringify(inputs,null,2)+'\n');
results.sharedInputs=Object.keys(inputs).length-1;
results.onlyChangedInput='output/Phpurs.ThunkFusion/index.js';
fs.writeFileSync(path.join(artifacts,'compilers.json'),JSON.stringify(results,null,2)+'\n');
console.log(JSON.stringify(results,null,2));
