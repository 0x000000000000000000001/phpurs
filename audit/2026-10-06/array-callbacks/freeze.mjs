// Capture once; vary only the ArrayCallbacks pass, keeping earlier passes on.
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import {fileURLToPath} from 'node:url';
import {createHash} from 'node:crypto';
import {build} from 'esbuild';

const repo=fileURLToPath(new URL('../../../',import.meta.url));
const artifacts=path.resolve(process.argv[2]);
const target=path.join(repo,'output/Phpurs.ArrayCallbacks/index.js');
const sources=new Map(),compilers={};
const hash=value=>createHash('sha256').update(value).digest('hex');
for(const variant of ['integrated','baseline','fold','filter']) {
  const result=await build({absWorkingDir:repo,
    stdin:{contents:"import {main} from './output/Main/index.js'; main();",resolveDir:repo,sourcefile:'array-callback-audit.js'},
    bundle:true,platform:'node',format:'cjs',write:false,
    plugins:[{name:'freeze',setup(builder){builder.onLoad({filter:/\.js$/},({path:file})=>{
      if(variant==='integrated')sources.set(file,fs.readFileSync(file,'utf8'));
      assert.ok(sources.has(file),file);
      let contents=sources.get(file);
      if(file===target && variant==='baseline')
        contents="import {empty} from '../Data.Map/index.js'; export const optimize=_=>_=>module_=>({module_,workers:[],arities:empty});";
      else if(file===target && variant!=='integrated') {
        assert.equal(contents.split('var optimize =').length,2);
        contents=contents.replace('var optimize =','var allOptimize =');
        const removed=variant==='fold'?'FilterImpl':'FoldlArray';
        contents+=`\nvar optimize = contracts => allOptimize(Data_Set["delete"](Phpurs_ForeignTraversals.ordTraversal)(Phpurs_ForeignTraversals.${removed}.value)(contracts));\n`;
      }
      return {contents,loader:'js'};
    });}}],
  });
  const bytes=result.outputFiles[0].contents;
  fs.writeFileSync(path.join(artifacts,variant+'.cjs'),bytes);
  compilers[variant]={sha256:hash(bytes),bytes:bytes.length};
}
for(const [file,source] of sources)assert.equal(fs.readFileSync(file,'utf8'),source,'compiler changed during freeze');
fs.writeFileSync(path.join(artifacts,'compiler-inputs.json'),JSON.stringify(Object.fromEntries([...sources].sort().map(([file,source])=>[path.relative(repo,file),hash(source)])),null,2)+'\n');
compilers.onlyChangedInput='output/Phpurs.ArrayCallbacks/index.js';
compilers.sharedInputs=sources.size-1;
fs.writeFileSync(path.join(artifacts,'compilers.json'),JSON.stringify(compilers,null,2)+'\n');
console.log(JSON.stringify(compilers,null,2));
