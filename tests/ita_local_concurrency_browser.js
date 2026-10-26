async (page) => {
 // PREPARED ONLY. Copy to a private local file, fill after explicit DB approval.
 // Storage states contain session secrets: outside Git/webroot, never attach them.
 const c={origin:'https://infracore.test',schema:'',ack:'',localOnly:false,noTunnel:false,
  scenario:'ticket',states:['',''],users:[0,0],assetId:3004,licenseId:7001,targets:[3002,3003]};
 if(!/^infracore_local_[a-z0-9_]{1,40}$/.test(c.schema)||c.ack!==`Tôi xác nhận cho chạy trên DB local ${c.schema}`||!c.localOnly||!c.noTunnel)
  throw new Error('Local DB approval/config required; no browser request sent.');
 const origin=new URL(c.origin);
 if(origin.origin!=='https://infracore.test'||c.states.some(x=>!x)||c.states[0]===c.states[1]||c.users[0]===c.users[1]||c.users.some(x=>![900001,900002,900003].includes(x)))
  throw new Error('Use trusted local vhost, two private states and distinct TEST actors.');
 if(!['ticket','license','version'].includes(c.scenario))throw new Error('Unknown scenario.');
 const contexts=[],pages=[],summary={scenario:c.scenario,label:'kiểm trên local, DB cô lập'};
 const assert=(ok,label)=>{if(!ok)throw new Error(label);};
 const browser=page.context().browser();
 try {
  for(let i=0;i<2;i++){
   const ctx=await browser.newContext({storageState:c.states[i],serviceWorkers:'block'});contexts.push(ctx);
   await ctx.route('**/*',route=>{
    const r=route.request(),u=new URL(r.url());
    if(u.origin!==origin.origin)return route.abort();
    if(r.method()==='POST'&&!(u.pathname==='/admin.php'&&u.searchParams.get('op')==='ita'&&['ticket-save','license-save','asset-save'].includes(u.searchParams.get('act'))))return route.abort();
    if(r.method()==='GET'&&u.pathname.endsWith('.php')&&!(u.pathname==='/admin.php'&&u.searchParams.get('op')==='ita'))return route.abort();
    return route.continue();
   });
   const p=await ctx.newPage();pages.push(p);await p.goto(c.origin+'/admin.php?op=ita');
  }
  const cookies=await Promise.all(contexts.map(x=>x.cookies(c.origin)));
  const sid=cookies.map(xs=>xs.find(x=>x.name==='ITA_SESSION')?.value);
  assert(sid.every(Boolean)&&sid[0]!==sid[1],'Distinct ITA sessions required (never print cookie values).');
  async function get(p,act,params={}){
   return p.evaluate(async ({act,params})=>{
    const u=new URL('/admin.php',location.origin);u.searchParams.set('op','ita');u.searchParams.set('act',act);
    for(const[k,v]of Object.entries(params))u.searchParams.set(k,String(v));
    const r=await fetch(u,{cache:'no-store',redirect:'error'});const j=await r.json();
    if(!r.ok||!j.success)throw new Error('Preflight/readback failed '+r.status);return j.data;
   },{act,params});
  }
  for(let i=0;i<2;i++){
   const d=await get(pages[i],'data');assert(Number(d.user.id)===c.users[i],'Expected TEST actor required.');
   assert(['admin_it','system_manager'].includes(d.user.role),'Writer role required.');
   assert(await pages[i].locator('meta[name="ita-csrf"]').getAttribute('content'),'CSRF required.');
  }
  let act,bodies,before;
  if(c.scenario==='license'){
   const ds=await Promise.all(pages.map(p=>get(p,'license',{id:c.licenseId})));
   before=ds[0].license;
   assert(before.software_name.startsWith('TEST ')&&before.license_type==='per_device','Fake per-device license required.');
   assert(Number(before.total_seats)-Number(before.used_seats)===1&&ds[1].license.version===before.version,'Exactly last seat and shared version required.');
   for(let i=0;i<2;i++){
    const a=await get(pages[i],'asset',{id:c.targets[i]});assert(a.code.startsWith('TEST-'),'Fake target required.');
    assert(!ds[i].allocations.some(x=>!x.released_at&&Number(x.asset_id)===c.targets[i]),'Target already allocated.');
   }
   assert(c.targets[0]!==c.targets[1],'Distinct targets required.');
   act='license-save';bodies=c.targets.map(target_id=>({operation:'allocate',id:c.licenseId,version:before.version,target_id,confirm_overage:false,override_reason:''}));
  }else{
   const ds=await Promise.all(pages.map(p=>get(p,'asset',{id:c.assetId})));before=ds[0];
   assert(before.code.startsWith('TEST-')&&ds[1].version===before.version,'Fake asset/shared version required.');
   if(c.scenario==='ticket'){
    const list=await get(pages[0],'tickets',{asset_id:c.assetId,status:'pending'});assert(Number(list.total)===0,'Use asset without pending ticket.');
    act='ticket-save';bodies=[0,1].map(i=>({operation:'create',asset_id:c.assetId,type:'repair',severity:'medium',note:'TEST concurrent '+i}));
   }else{
    act='asset-save';bodies=[0,1].map(i=>({operation:'note',id:c.assetId,version:before.version,reason:'TEST concurrency '+i+' '+Date.now()}));
   }
  }
  // Both pages reach the client barrier before Promise.all releases the POSTs.
  // This does NOT guarantee overlapping SQL critical sections; inspect server evidence.
  await Promise.all(pages.map(p=>p.evaluate(()=>{window.__itaRaceReady=true;})));
  const results=await Promise.all(pages.map((p,i)=>p.evaluate(async({act,body})=>{
   if(!window.__itaRaceReady)throw new Error('Barrier not ready');
   const token=document.querySelector('meta[name="ita-csrf"]').content;
   const sent=Date.now();const r=await fetch('/admin.php?op=ita&act='+act,{method:'POST',redirect:'error',cache:'no-store',
    headers:{'Content-Type':'application/json','X-CSRF-Token':token},body:JSON.stringify(body)});
   const j=await r.json();return{status:r.status,success:j.success===true,sent,finished:Date.now()};
  },{act,body:bodies[i]})));
  summary.requests=results;
  assert(results.filter(r=>r.status===200&&r.success).length===1,'Exactly one successful mutation required.');
  const loser=results.find(r=>!r.success);
  assert(loser&&loser.status===409,'Shared-version race must reject with409; auth/CSRF/scope/capacity errors are not a race pass.');
  if(c.scenario==='ticket'){
   const list=await get(pages[0],'tickets',{asset_id:c.assetId,status:'pending'});assert(Number(list.total)===1,'Readback: exactly one pending.');
   const asset=await get(pages[0],'asset',{id:c.assetId});assert(asset.status==='maintenance','Readback: maintenance.');
  }else if(c.scenario==='license'){
   const d=await get(pages[0],'license',{id:c.licenseId});assert(Number(d.license.used_seats)===Number(before.used_seats)+1&&Number(d.license.used_seats)<=Number(d.license.total_seats),'Readback: no overage.');
  }else{
   const a=await get(pages[0],'asset',{id:c.assetId});const winner=results.findIndex(r=>r.success);
   assert(a.fields.status_reason===bodies[winner].reason,'Readback: winner note retained.');
  }
  summary.remaining='DB rows/audit and actual SQL overlap must be verified separately; license409 may be version guard, not proof of capacity rejection with a fresh version. No cleanup/reset performed.';
  return summary;
 }finally{await Promise.all(contexts.map(ctx=>ctx.close()));}
}
