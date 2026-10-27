async(page)=>{
 const browser=page.context().browser(),checks=[],errors=[],requests=[],posts=[];
 const check=(ok,label)=>{if(!ok)throw Error(label);checks.push(label);};
 const catalog=['asset.read','asset.create','asset.update','report.read','network.read','network.manage','access.read','access.manage','scope.store'];
 async function session(owner){
  const context=await browser.newContext({viewport:{width:1366,height:1000}}),p=await context.newPage();p.on('pageerror',e=>errors.push(e.message));
  await context.route('**/*',async route=>{
   const req=route.request(),u=new URL(req.url());if(u.hostname!=='127.0.0.1')return route.abort();
   const json=data=>route.fulfill({contentType:'application/json',body:JSON.stringify({success:true,data,message:''})});
   if(req.isNavigationRequest()){const response=await route.fetch();return route.fulfill({response,body:(await response.text()).replace('data-mode="mock"','data-mode="live"')});}
   if(u.pathname!=='/admin.php')return route.continue();const act=u.searchParams.get('act');requests.push(act);
   if(req.method()==='POST'){posts.push(JSON.parse(req.postData()));return json({});}
   if(act==='data')return json({assets:[],pagination:{page:1,per_page:5,total:0},departments:[{value:'10',label:'Bộ phận A'}],summary:{total:0,by_status:{},by_department:[],alerts:[],alerts_total:0},user:{id:1,role:'support_technician',display_name:'MOCK',capabilities_enabled:true,permissions:owner?catalog:['asset.read','asset.create','report.read'],permission_catalog:owner?catalog:[]}});
   if(act==='users')return json({rows:[{id:111,username:'MOCK-C',fullname:'Tài khoản C',role:'support_technician',enabled:1}],page:1,total:1,per_page:20});
   if(act==='access')return json({id:111,username:'MOCK-C',role:'support_technician',enabled:1,department_ids:[10],capabilities_enabled:true,permissions:['asset.read','asset.create','report.read'],version:'a'.repeat(64)});
   if(act==='categories')return json([]);
   return json({rows:[],page:1,total:0,per_page:20});
  });return {context,p};
 }
 const owner=await session(true);
 try{
  const p=owner.p;await p.goto('http://127.0.0.1:18766/#users');await p.locator('#user-rows button').click();await p.locator('#permission-live-editor').waitFor();
  check(await p.locator('#access-role').isHidden(),'Legacy role input hidden in capability mode');
  check(await p.locator('#permission-live-editor input:checked').count()===3,'C exact three function grants shown');
  check(await p.locator('#permission-live-editor input[value="report.read"]').isChecked(),'C report right independent of legacy Support role');
  check(!await p.locator('#permission-live-editor input[value="asset.update"]').isChecked(),'No implied update right');
  await p.locator('#permission-function-search').fill('mạng');check(await p.locator('#permission-live-editor details:not([hidden])').count()===1,'Function search narrows module groups');
  await p.locator('#permission-function-search').fill('');
  for(const width of [1366,768,390]){await p.setViewportSize({width,height:1000});check(await p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1),'No page overflow '+width);await p.screenshot({path:`.playwright-cli/phase14-permissions-${width}.png`,fullPage:true});}
  const payload=await p.evaluate(()=>window.ItaPermissionEditor.payload());check(payload.version==='a'.repeat(64)&&payload.permissions.length===3&&!('role'in payload),'Editor payload carries version and exact permissions, no role');
  check(await p.locator('[data-view="users"]').isVisible(),'Support-labelled manager can open access UI by capability');
 }finally{await owner.context.close();}
 const c=await session(false);
 try{
  const p=c.p;await p.goto('http://127.0.0.1:18766/#assets');await p.waitForFunction(()=>window.itaLiveContext?.user?.capabilities_enabled);
  check(await p.locator('[data-view="reports"]').isVisible(),'C report navigation visible');check(await p.locator('[data-view="users"]').isHidden(),'C access navigation hidden');
  check(await p.locator('[data-view="licenses"]').isHidden(),'C license navigation hidden');
  check(await p.evaluate(()=>!window.ItaPolicy.maintenance()),'Support metadata cannot force C into maintenance-only report');
  check(await p.evaluate(()=>window.ItaPolicy.can('asset.create')&&!window.ItaPolicy.can('asset.update')),'C effective UI policy uses individual grants');
  check(await p.locator('#notification-button').isHidden(),'No alert bell without alert.read');
  await p.evaluate(()=>location.hash='users');await p.locator('#capability-view-denied').waitFor({state:'visible'});
  check(await p.locator('#users-panel').isHidden(),'Direct unauthorized access view suppressed');
  check(posts.length===0,'No real or mock access writes while inspecting');check(errors.length===0,'No browser runtime errors');
 }finally{await c.context.close();}
 return {checks:checks.length,results:checks,scope:'API mock capability UI, separate contexts; no DB/host or persistence proof'};
}
