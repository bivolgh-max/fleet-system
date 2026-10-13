import fs from 'node:fs';
import path from 'node:path';
import {fileURLToPath} from 'node:url';
import {PHP} from '@php-wasm/universal';
import {loadNodeRuntime} from '@php-wasm/node';
const root=path.resolve(path.dirname(fileURLToPath(import.meta.url)),'..');
const php=new PHP(await loadNodeRuntime('8.3',{emscriptenOptions:{processId:process.pid}}));
function copy(dir,virtual){php.mkdir(virtual);for(const x of fs.readdirSync(dir,{withFileTypes:true})){if(['evidence','node_modules','.git','config.local.php'].includes(x.name))continue;const src=path.join(dir,x.name),dst=virtual+'/'+x.name;if(x.isDirectory())copy(src,dst);else php.writeFile(dst,fs.readFileSync(src));}}
copy(root,'/app');
const env={FLEET_DB_DSN:'sqlite:/demo.sqlite'};
const setup=await php.run({code:`<?php require '/app/app/bootstrap.php';require '/app/app/demo_data.php';$db=connect();$db->exec(file_get_contents('/app/database/schema.sqlite.sql'));$repo=new FleetRepository($db);seed_demo($repo);$repo->query('INSERT INTO users(name,email,password_hash) VALUES (?,?,?)',['Demo Administrator','qa@example.com',password_hash('QA-only-password',PASSWORD_BCRYPT,['cost'=>4])]);echo 'ready';`,env});
if(setup.text!=='ready'||setup.errors)throw new Error('Test database setup failed: '+setup.text+' '+setup.errors);
let cookie='';const checks=[];const pages={};
async function request(uri,body=null,useCookies=true){
  const headers={};if(useCookies&&cookie)headers.Cookie=cookie;if(body!==null)headers['Content-Type']='application/x-www-form-urlencoded';
  const out=await php.run({scriptPath:'/app/public/index.php',relativeUri:uri,method:body===null?'GET':'POST',headers,body:body===null?undefined:new TextEncoder().encode(new URLSearchParams(body).toString()),env});
  const set=Object.entries(out.headers).find(([k])=>k.toLowerCase()==='set-cookie')?.[1];
  if(useCookies&&set?.length)cookie=set.at(-1).split(';')[0];
  return out;
}
function record(id,test,ok,details=''){checks.push({id,test,status:ok?'PASS':'FAIL',...(details?{actual:details}:{})});}
function token(html){return html.match(/name="csrf" value="([^"]+)"/)?.[1]||'';}
let out=await request('/index.php?page=vehicles',null,false);
record('H01','Unauthenticated private page redirects to login',out.httpStatusCode===303);
out=await request('/index.php?page=login');
record('H02','Login page includes CSRF token',out.httpStatusCode===200&&token(out.text).length===64&&!out.errors);pages.login=out.text;
let csrf=token(out.text);
out=await request('/index.php?page=login',{email:'qa@example.com',password:'QA-only-password'});
record('H03','Login POST without token is rejected',out.httpStatusCode===422&&out.text.includes('security token'));
const beforeLogin=cookie;
out=await request('/index.php?page=login',{csrf,email:'qa@example.com',password:'QA-only-password'});
record('H04','Valid login redirects and regenerates session',out.httpStatusCode===303&&!out.errors&&cookie!==beforeLogin);
for(const [route,title] of Object.entries({dashboard:'Fleet overview',vehicles:'Vehicles',drivers:'Drivers',assignments:'Driver assignments',maintenance:'Service history',repairs:'Repair history',reports:'Maintenance cost report',vehicle_form:'Add vehicle',driver_form:'Add driver',record_form:'Add maintenance record',vehicle:'Vehicle record'})){
  out=await request('/index.php?page='+route+(route==='vehicle'?'&id=1':''));
  record('H-'+route,'Authenticated '+route+' page renders',out.httpStatusCode===200&&out.text.includes('<h1>'+title+'</h1>')&&!out.errors&&!out.text.includes('could not be loaded'),out.errors);
  pages[route]=out.text;if(route==='dashboard')csrf=token(out.text);
}
out=await request('/index.php?page=vehicle_form',{csrf,registration:'HTTP01',make:'Ford',model:'Transit',model_year:'2020',mileage:'1000',status:'Available',mot_expiry:'2027-01-01',insurance_expiry:'2027-02-01',next_service_date:'',next_service_mileage:''});
record('H16','Vehicle form POST saves and redirects',out.httpStatusCode===303&&!out.errors);
out=await request('/index.php?page=vehicles&search=HTTP01');record('H17','Saved vehicle appears in filtered list',out.text.includes('HTTP01')&&!out.errors);
out=await request('/index.php?page=vehicle_form',{csrf,registration:'HTTP01',make:'Ford',model:'Transit',model_year:'2020',mileage:'1000',status:'Available',mot_expiry:'',insurance_expiry:'',next_service_date:'',next_service_mileage:''});
record('H18','Duplicate vehicle form gives validation error',out.httpStatusCode===422&&out.text.includes('already exists')&&!out.errors);
out=await request('/index.php?page=dashboard');
record('H19','No store cache control and security headers set',Object.keys(out.headers).some(k=>k.toLowerCase()==='cache-control')&&Object.keys(out.headers).some(k=>k.toLowerCase()==='content-security-policy'));
out=await request('/index.php?page=logout',{csrf});record('H20','Logout POST destroys session and redirects',out.httpStatusCode===303&&!out.errors);
out=await request('/index.php?page=vehicles');record('H21','Private page inaccessible after logout',out.httpStatusCode===303);
const report={runtime:'PHP 8.3.33 WebAssembly HTTP simulation',database:'SQLite test database; not MySQL',executed_at:new Date().toISOString(),total:checks.length,passed:checks.filter(x=>x.status==='PASS').length,failed:checks.filter(x=>x.status==='FAIL').length,tests:checks};
fs.writeFileSync(root+'/evidence/http_tests.json',JSON.stringify(report,null,2));
// Internal QA snapshots only. Remove hidden CSRF values before any later capture.
fs.mkdirSync(root+'/tests/.tmp/interface_html',{recursive:true});
for(const [name,html]of Object.entries(pages))fs.writeFileSync(root+'/tests/.tmp/interface_html/'+name+'.html',html.replace(/name="csrf" value="[^"]+"/g,'name="csrf" value=""'));
console.log(JSON.stringify(report,null,2));await php.exit();
