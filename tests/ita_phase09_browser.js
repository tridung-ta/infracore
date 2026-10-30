async(page)=>{
 const source=await (await page.request.get('http://127.0.0.1:18766/assets/ita/mock/forecast.json')).json(),checks=[],errors=[];
 const check=(ok,label)=>{if(!ok)throw new Error(label);checks.push(label);};
 for(const role of ['admin_it','system_manager','support_technician']){
  const context=await page.context().browser().newContext({viewport:{width:1366,height:1000}}),p=await context.newPage(),requests=[];
  let fail=false,planVersion='version-1',badExport='';
  p.on('pageerror',e=>errors.push(e.message));
  const saved=()=>({...source,saved:true,plan_id:1,version:planVersion,status:'draft',planned_headcount_increase:3,note:'Mock'});
  await context.route('**/*',async route=>{
   const req=route.request(),url=new URL(req.url());if(url.hostname!=='127.0.0.1')return route.abort();
   const json=(data,status=200,message='')=>route.fulfill({status,contentType:'application/json',body:JSON.stringify({success:status===200,data,message})});
   if(req.isNavigationRequest()){const r=await route.fetch();return route.fulfill({response:r,body:(await r.text()).replace('data-mode="mock"','data-mode="live"').replace('name="ita-csrf" content=""','name="ita-csrf" content="mock-csrf"')});}
   if(url.pathname!=='/admin.php')return route.continue();
   const action=url.searchParams.get('act');
   if(action==='categories')return json([]);
   if(action==='data')return json({assets:[],pagination:{page:1,per_page:5,total:0},departments:[],user:{id:1,role,display_name:'Mock',role_label:role},summary:{total:0,by_status:{},by_department:[],alerts:[],alerts_total:0}});
   requests.push({action,method:req.method(),headers:req.headers(),body:req.postDataJSON?.()});
   if(role==='support_technician')return json(null,403,'Không có quyền');
   const data=structuredClone(source);data.config.can_configure=role==='admin_it';data.plans=[{id:1,department_id:10,status:'draft',version:planVersion,planned_headcount_increase:3,note:'Mock',has_snapshot:true}];
   if(action==='forecast')return fail?json(null,503,'Lỗi giả lập'):json(data);
   if(action==='forecast-plan')return json(saved());
   if(action==='forecast-plans')return json(data.plans);
   if(action==='forecast-plan-save'){if(fail)return json(null,409,'Kế hoạch đã đổi');planVersion='version-2';return json(saved());}
   if(action==='forecast-config-save')return json(data.config);
   if(action==='forecast-export')return route.fulfill({status:200,contentType:badExport==='html'?'text/html':'application/pdf',body:badExport?'<html>login</html>':'%PDF-mock'});
   return json(null,404);
  });
  try{
   await p.goto('http://127.0.0.1:18766/#forecast');
   if(role==='support_technician'){
    await p.locator('#forecast-message').filter({hasText:'không có quyền'}).waitFor();check(await p.locator('#forecast-nav').isHidden(),'Support nav hidden');check(!requests.some(r=>r.action==='forecast'),'Support no forecast request');continue;
   }
   await p.locator('#forecast-content').waitFor({state:'visible'});await p.waitForFunction(()=>document.getElementById('forecast-panel').getAttribute('aria-busy')==='false');
   check((await p.locator('#forecast-summary').textContent()).includes('135 300 000,00'),role+' exact summary');
   check((await p.locator('#forecast-nav').textContent()).trim()==='Dự báo & ngân sách',role+' Vietnamese nav intact');
   check((await p.locator('#forecast-source').textContent()).includes('đang dùng giá trị mặc định'),role+' defaults note intact');
   check(await p.locator('#forecast-config').isHidden()=== (role!=='admin_it'),role+' config permissions');
   await p.locator('#forecast-plans summary').click();await p.locator('#forecast-plan-department').selectOption('10');
   await p.locator('#forecast-headcount').fill('4');await p.locator('#forecast-save-plan').click();
   check((await p.locator('#forecast-message').textContent()).includes('xác nhận'),role+' explicit recalculate confirmation');
   check(!requests.some(r=>r.action==='forecast-plan-save'),role+' no unconfirmed POST');
   await p.locator('#forecast-confirm').check();fail=true;await p.locator('#forecast-save-plan').click();await p.locator('#forecast-message').filter({hasText:'Kế hoạch đã đổi'}).waitFor();
   check(await p.locator('#forecast-headcount').inputValue()==='4',role+' failed save preserves input');
   check(requests.filter(r=>r.action==='forecast-plan-save').length===1,role+' no automatic retry');fail=false;
   await p.locator('#forecast-save-plan').click();await p.locator('#forecast-message').filter({hasText:'Đã lưu kế hoạch'}).waitFor();
   const post=requests.filter(r=>r.action==='forecast-plan-save').at(-1);
   check(post.method==='POST'&&post.headers['x-csrf-token']==='mock-csrf',role+' POST CSRF');
   check(post.body.version==='version-1'&&post.body.confirm_recalculate===true&&post.body.planned_headcount_increase==='4',role+' version headcount payload');
   check((await p.locator('#forecast-source').textContent()).startsWith('Snapshot đã lưu'),role+' snapshot source');
   await p.locator('#forecast-view-plan').click();await p.locator('#forecast-message').filter({hasText:'Đang xem số liệu snapshot'}).waitFor();
   check(requests.some(r=>r.action==='forecast-plan'),role+' saved snapshot GET');
   const downloadPromise=p.waitForEvent('download');await p.locator('#forecast-pdf').click();await downloadPromise;
   const exp=requests.find(r=>r.action==='forecast-export');check(exp.body.plan_id===1&&exp.body.format==='pdf',role+' export saved ID, no client money');
   badExport='html';await p.locator('#forecast-pdf').click();await p.locator('#forecast-message').filter({hasText:'chưa trả đúng file'}).waitFor();check(await p.locator('#forecast-message').getAttribute('role')==='alert',role+' HTML200 refused');
   badExport='signature';await p.locator('#forecast-pdf').click();await p.locator('#forecast-message').filter({hasText:'File báo cáo không hợp lệ'}).waitFor();check(await p.locator('#forecast-message').getAttribute('role')==='alert',role+' wrong PDF signature refused');badExport='';
   fail=true;await p.locator('#forecast-filters button').click();await p.locator('#forecast-message').filter({hasText:'Lỗi giả lập'}).waitFor();check(await p.locator('#forecast-retry').isVisible(),role+' retry visible');fail=false;
   await p.locator('#forecast-retry').click();await p.locator('#forecast-message').filter({hasText:'Đã tính dự báo'}).waitFor();
   for(const width of [1366,768,390]){
    await p.setViewportSize({width,height:1000});await p.locator('#forecast-config').evaluate(n=>n.open=true);
    const bounds=await p.evaluate(()=>({width:innerWidth,scroll:document.documentElement.scrollWidth,bad:[...document.querySelectorAll('#forecast-panel input,#forecast-panel select,#forecast-panel button')].filter(n=>n.offsetParent!==null).map(n=>n.getBoundingClientRect()).filter(b=>b.x<0||b.right>innerWidth+1).length}));
    check(bounds.scroll<=bounds.width+1&&bounds.bad===0,role+' bounds '+width);
    if(role==='admin_it')await p.screenshot({path:`.playwright-cli/phase09-${width}.png`,fullPage:true});
   }
  }finally{await context.close();}
 }
 const context=await page.context().browser().newContext(),p=await context.newPage();let api=0;
 await context.route('**/admin.php?*',r=>{api++;return r.abort();});await p.goto('http://127.0.0.1:18766/#forecast');await p.locator('#forecast-content').waitFor({state:'visible'});
 check((await p.locator('#forecast-mode').textContent()).includes('MOCK')&&api===0,'Static MOCK no real API');await context.close();
 check(errors.length===0,'No runtime errors');return {checks:checks.length,results:checks,scope:'browser API mock/static, no DB'};
}
