import fs from 'node:fs/promises';
import path from 'node:path';
import {build} from 'esbuild';
import {spawnSync} from 'node:child_process';
const backend=process.cwd(), root=path.join(backend,'audit/2026-09-09/partial-bindings');
const worktree=path.resolve(backend,'../../altbak.pub-phpurs');
await fs.cp(path.join(worktree,'run/bak/php/output'),path.join(root,'raw/input'),{recursive:true});
const outfile=path.join(root,'raw/inspect.mjs');
await build({stdin:{contents:"import {main} from './output/Main/index.js';main();",resolveDir:backend},outfile,bundle:true,platform:'node',format:'esm',banner:{js:"import {createRequire} from 'node:module'; const require=createRequire(import.meta.url);"},plugins:[{name:'dump-typed-input',setup(b){b.onLoad({filter:/Phpurs\.CodeGen\/index\.js$/},async a=>{
let source=await fs.readFile(a.path,'utf8');
source=source.replace('var translate = function (imports) {\n    return function (input) {',`var translate = function (imports) {\n    return function (input) {\nif(input.name==='Test.Church') require('node:fs').writeFileSync(${JSON.stringify(path.join(root,'raw/typed-input.json'))}, JSON.stringify(input.bindings,(k,v)=>v&&typeof v==='object'&&!Array.isArray(v)&&v.constructor.name!=='Object'?{kind:v.constructor.name,...v}:v,2));`);
return {contents:source,loader:'js',resolveDir:path.dirname(a.path)};
});}}]});
const r=spawnSync('node',[outfile,'--output',path.join(root,'raw/input'),'--main','App'],{cwd:worktree,encoding:'utf8'});
await fs.writeFile(path.join(root,'inspect.log'),r.stdout+r.stderr);process.exit(r.status);
