import fs from 'node:fs';
import path from 'node:path';
import {fileURLToPath} from 'node:url';
import { PHP } from '@php-wasm/universal';
import { loadNodeRuntime } from '@php-wasm/node';
const root=path.resolve(path.dirname(fileURLToPath(import.meta.url)),'..');
const php=new PHP(await loadNodeRuntime('8.3',{emscriptenOptions:{processId:process.pid}}));
function copy(dir,virtual){php.mkdir(virtual);for(const entry of fs.readdirSync(dir,{withFileTypes:true})){if(['node_modules','.git','config.local.php','evidence'].includes(entry.name))continue;const source=path.join(dir,entry.name),target=virtual+'/'+entry.name;if(entry.isDirectory())copy(source,target);else php.writeFile(target,fs.readFileSync(source));}}
copy(root,'/app');
const checked=[];
function files(dir){return fs.readdirSync(dir,{withFileTypes:true}).flatMap(x=>x.isDirectory()?files(path.join(dir,x.name)):[path.join(dir,x.name)]);}
for(const file of files(root).filter(x=>x.endsWith('.php'))){const script='/app/'+path.relative(root,file);const result=await php.run({code:`<?php try{token_get_all(file_get_contents(${JSON.stringify(script)}), TOKEN_PARSE);echo 'OK';}catch(Throwable $e){echo $e->getMessage();}`,env:{}});checked.push({file:path.relative(root,file),result:result.text});}
const tests=await php.run({scriptPath:'/app/tests/run.php'});
fs.mkdirSync(root+'/evidence',{recursive:true});
fs.writeFileSync(root+'/evidence/syntax_results.json',JSON.stringify({runtime:'PHP 8.3 WebAssembly',checks:checked},null,2));
fs.writeFileSync(root+'/evidence/automated_tests.json',tests.text);
console.log(JSON.stringify({syntax:checked,tests:JSON.parse(tests.text),stderr:tests.errors,exitCode:tests.exitCode},null,2));
await php.exit();
