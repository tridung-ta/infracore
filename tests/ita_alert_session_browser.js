async(page)=>{
 const checks=[],errors=[],check=(ok,label)=>{if(!ok)throw Error(label);checks.push(label);};
 const context=await page.context().browser().newContext(),p=await context.newPage();let count=8,identity=111,version='A',fail=false,posts=0,stage='initial';
 p.on('pageerror',e=>errors.push(e.message));
 await context.route('**/*',async route=>{
  const req=route.request(),u=new URL(req.url());if(u.hostname!=='127.0.0.1')return route.abort();
  if(req.isNavigationRequest()){const response=await route.fetch();return route.fulfill({response,body:(await response.text()).replace('data-mode="mock"','data-mode="live"')});}
  if(u.pathname!=='/admin.php')return route.continue();if(req.method()==='POST'){posts++;return route.abort();}
  const json=(data,status=200)=>route.fulfill({status,contentType:'application/json',body:JSON.stringify({success:status===200,data,message:status===200?'':'MOCK unavailable'})}),act=u.searchParams.get('act');
  if(act==='data')return json({assets:[],pagination:{page:1,per_page:5,total:0},departments:[{value:'10',label:'Bộ phận A'}],user:{id:identity,role:'admin_it',display_name:'MOCK'},summary:{total:0,by_status:{},by_department:[],alerts:[],alerts_total:0}});
  if(act==='categories')return json([]);
  if(act==='alerts'){const filtered=u.searchParams.get('department_id')==='10';return json({as_of:'2026-10-28T09:00:00+07:00',active_total:filtered?3:count,total:filtered?3:count,page:1,per_page:20,rows:[{code:'TEST-'+version,name:'Thiết bị giả',title:'Cần bảo trì',reason:'maintenance',level:'warning'}]},fail&&u.searchParams.has('page')?503:200);}
  return json({rows:[],page:1,total:0,per_page:20});
 });
 try{
  await p.goto('http://127.0.0.1:18766/#assets');await p.waitForFunction(()=>document.getElementById('nav-alert-count').textContent==='8');
  check(await p.locator('#nav-alert-count').isVisible(),'Eight active alerts initially remind');
  fail=true;await p.locator('#notification-button').click();await p.locator('#dash-message').filter({hasText:'MOCK unavailable'}).waitFor();
  check(await p.locator('#nav-alert-count').isVisible(),'Failed center load never marks viewed');
  stage='retry';fail=false;await p.locator('#dash-refresh').click();await p.locator('#dash-output table').waitFor();
  check(await p.locator('#nav-alert-count').isHidden(),'Successful view hides numeric reminder');check(await p.locator('.notification-dot').isHidden(),'Successful view clears bell dot');
  check((await p.locator('#dash-bell-label').textContent()).includes('8 cảnh báo cần xử lý'),'Active risks retained with clear label');
  for(const width of [1366,768,390]){await p.setViewportSize({width,height:1000});check(await p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1),'Alert session bounds '+width);await p.screenshot({path:'.playwright-cli/phase15-alert-'+width+'.png',fullPage:true});}
  await p.locator('#dash-department').selectOption('10');await p.locator('#dash-filters button[type=submit]').click();await p.waitForFunction(()=>document.getElementById('dash-output').textContent.includes('3 kết quả khớp'));
  check((await p.locator('#dash-bell-label').textContent()).includes('8 cảnh báo'),'Department filter does not overwrite canonical count');
  stage='reload';await p.reload();await p.waitForFunction(()=>document.getElementById('dash-bell-label').textContent.includes('8 cảnh báo'));
  check(await p.locator('#nav-alert-count').isHidden(),'Session view state survives reload');
  stage='changed count';await p.evaluate(()=>location.hash='assets');count=9;await p.evaluate(()=>dispatchEvent(new Event('ita:refresh')));await p.waitForFunction(()=>document.getElementById('nav-alert-count').textContent==='9');
  check(await p.locator('#nav-alert-count').isVisible(),'Changed global snapshot reminds again');
  stage='second visit';await p.locator('#notification-button').click();await p.waitForFunction(()=>document.getElementById('nav-alert-count').hidden);
  stage='changed identity';await p.evaluate(()=>location.hash='assets');version='B';await p.evaluate(()=>dispatchEvent(new Event('ita:refresh')));await p.waitForFunction(()=>!document.getElementById('nav-alert-count').hidden);
  check(true,'Changed alert identity detected even with same count');
  identity=222;await p.reload();await p.waitForFunction(()=>window.itaLiveContext?.user.id===222&&document.getElementById('nav-alert-count').textContent==='9');check(await p.locator('#nav-alert-count').isVisible(),'Session state isolated per account');
  check(await p.evaluate(()=>Object.entries({shell:'20261028-usercreate',dashboard:'20261028-feedback',tickets:'20261028-feedback','assignments-live':'20261028-feedback','form-guidance':'20261028-feedback'}).every(([name,version])=>[...document.scripts].some(script=>script.src.endsWith('/ita-'+name+'.js?v='+version)))),'All five modified assets have their current cache URLs');
  await p.addInitScript(()=>{Storage.prototype.getItem=()=>{throw Error('MOCK storage disabled');};Storage.prototype.setItem=()=>{throw Error('MOCK storage disabled');};});
  identity=333;await p.reload();await p.waitForFunction(()=>window.itaLiveContext?.user.id===333&&document.getElementById('nav-alert-count').textContent==='9');await p.locator('#notification-button').click();await p.waitForFunction(()=>document.getElementById('nav-alert-count').hidden);
  check(true,'Storage unavailable falls back to in-memory view state');
  check(posts===0,'Viewing reminders never sends POST');check(errors.length===0,'No browser runtime errors');
  return {checks:checks.length,results:checks,scope:'API mock, session-only UI; no DB/host read-state persistence'};
 }catch(error){throw Error(stage+': '+error.message+'; '+await p.locator('#dash-bell-label').textContent());}finally{await context.close();}
}
