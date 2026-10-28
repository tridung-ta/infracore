/* No HTTP/PHP/database: isolated manual-fixture contracts and synthetic download structure. */
const assert=require('node:assert/strict'),model=require('../assets/ita/preview/review-model.cjs'),files=require('../assets/ita/preview/review-files.cjs');let checks=0;
function check(ok){assert.ok(ok);checks++;}
model.control({reset:true});const a=model.read('asset',{id:'1',page:'1'});check(Array.isArray(a.history));check(a.history_pagination.per_page===20);
model.control({mode:'million-ui'});check(model.status().allocated_assets===6);check(model.read('data',{}).pagination.total===1000000);check(model.read('data',{page:'200000'}).assets.length===5);
model.control({mode:'normal'});const batch=model.write('import-upload',{});model.write('import-confirm',{batch_id:batch.batch_id,confirm_token:batch.confirm_token});check(model.status().allocated_assets===8);model.write('import-confirm',{batch_id:batch.batch_id,confirm_token:batch.confirm_token});check(model.status().allocated_assets===8);
model.write('license-save',{operation:'save',software_name:'MOCK',total_seats:'2',license_key:'MOCK-NEVER-IN-DTO'});check(!JSON.stringify(model.read('license',{id:'7'})).includes('MOCK-NEVER-IN-DTO'));
const c=model.read('access',{id:'112'});model.write('access',{user_id:112,enabled:true,department_ids:[10],permissions:[...c.permissions,'asset.update'],version:c.version});model.control({role:'custom'});check(model.read('data',{}).user.id===112);check(model.read('data',{}).user.permissions.includes('asset.update'));
const xlsx=files.xlsx();check(xlsx.readUInt32LE(0)===0x04034b50);check(xlsx.readUInt32LE(xlsx.length-22)===0x06054b50);
const pdf=files.pdf(),text=pdf.toString('ascii'),start=Number(/startxref\n(\d+)/.exec(text)[1]);check(text.startsWith('%PDF-1.4'));check(text.slice(start,start+4)==='xref');check(text.includes('/Count 1'));
const entries=text.slice(start).split('\n').slice(3,8);entries.forEach((entry,i)=>check(text.slice(Number(entry.slice(0,10))).startsWith((i+1)+' 0 obj')));
model.control({reset:true});check(model.status().allocated_assets===6);console.log(checks+' manual mock/download contract checks passed; no PHP/DB/host.');
