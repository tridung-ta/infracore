async(page)=>{
 const source=await (await page.request.get('http://127.0.0.1:18766/assets/ita/mock/forecast.json')).json(),checks=[],errors=[];
 const check=(ok,label)=>{if(!ok)throw new Error(label);checks.push(label);};
 for(const role of ['admin_it','system_manager','support_technician']){
  const context=await page.context().browser().newContext({viewport:{width:1366,height:1000}}),p=await context.newPage(),requests=[];let empty=false;
  const data=saved=>({...structuredClone(source),saved,plan_id:1,rows:empty?[]:Array.from({length:41},(_,i)=>({...source.rows[0],department_name:`${saved?'Saved':'Current'} ${i+1} <img src=x>`,replacement:i===0?'9999999999999999.99':'1.00',maintenance:i===40?null:'2.00',growth:'3.00',issues:i===40?['Chưa đủ dữ liệu']:[]})),summary:{replacement:'10000000000000039.99',maintenance:'80.00',growth:'123.00',contingency:'1000000000000024.30',total:'11000000000000267.29',complete:false},plans:[{id:1,department_id:10,status:'draft',version:'v1',planned_headcount_increase:0,note:'QA',has_snapshot:true}],config:{...source.config,can_configure:role==='admin_it'}});
  p.on('pageerror',e=>errors.push(e.message));
  await context.route('**/*',async route=>{
   const req=route.request(),url=new URL(req.url());if(url.hostname!=='127.0.0.1')return route.abort();
   const json=(d,status=200)=>route.fulfill({status,contentType:'application/json',body:JSON.stringify({success:status===200,data:d,message:''})});
   if(req.isNavigationRequest()){const r=await route.fetch();return route.fulfill({response:r,body:(await r.text()).replace('data-mode="mock"','data-mode="live"')});}
   if(url.pathname!=='/admin.php')return route.continue();const act=url.searchParams.get('act');
   if(act==='categories')return json([]);
   if(act==='data')return json({assets:[],departments:[],pagination:{page:1,per_page:5,total:0},user:{id:111,role,display_name:'QA',role_label:role},summary:{total:0,by_status:{},by_department:[],alerts:[],alerts_total:0}});
   requests.push({act,method:req.method(),body:req.postDataJSON?.()});
   if(act==='forecast')return json(data(false));if(act==='forecast-plan')return json(data(true));
   if(act==='forecast-export')return route.fulfill({status:200,contentType:'application/pdf',body:'%PDF-mock'});
   return json({rows:[],total:0,active_total:0,alerts_total:0});
  });
  try{
   await p.goto('http://127.0.0.1:18766/#forecast');
   if(role==='support_technician'){await p.waitForFunction(()=>document.getElementById('forecast-message').textContent.includes('không có quyền'));check(await p.locator('#forecast-content').isHidden()&&!requests.some(r=>r.act==='forecast'),'Support does not request budget');continue;}
   await p.locator('#forecast-content').waitFor({state:'visible'});const summary=await p.locator('#forecast-summary').textContent(),sourceText=await p.locator('#forecast-source').textContent(),count=requests.length;
   check(await p.locator('#forecast-rows tr').count()===20&&await p.locator('#forecast-row-prev').isDisabled(),`${role} first20`);
   check((await p.locator('#forecast-rows').textContent()).includes('9 999 999 999 999 999,99')&&await p.locator('#forecast-rows img').count()===0,`${role} decimal exact and escaped names`);
   await p.locator('#forecast-row-next').focus();await p.keyboard.press('Enter');check((await p.locator('#forecast-row-page').textContent()).includes('Trang 2/3'),`${role} keyboard paging`);
   await p.locator('#forecast-row-next').click();check(await p.locator('#forecast-rows tr').count()===1&&await p.locator('#forecast-row-next').isDisabled()&&(await p.locator('#forecast-rows').textContent()).includes('Chưa đủ dữ liệu'),`${role} last page retains missing data`);
   check(await p.locator('#forecast-summary').textContent()===summary&&await p.locator('#forecast-source').textContent()===sourceText&&requests.length===count,`${role} paging no request or totals/source changes`);
   for(const width of [1366,768,390]){await p.setViewportSize({width,height:1000});await p.locator('#forecast-row-next').scrollIntoViewIfNeeded();check(await p.locator('#forecast-row-next').evaluate(n=>{const r=n.getBoundingClientRect();return r.left>=0&&r.right<=innerWidth&&document.documentElement.scrollWidth<=innerWidth;}),`${role} pager bounds ${width}`);}
   if(role==='admin_it')await p.screenshot({path:'.playwright-cli/phase11-forecast-rows-390.png',fullPage:true});
   await p.locator('#forecast-plans>summary').click();await p.locator('#forecast-plan-department').selectOption('10');await p.locator('#forecast-view-plan').click();await p.waitForFunction(()=>document.getElementById('forecast-source').textContent.includes('Snapshot đã lưu'));
   check((await p.locator('#forecast-row-page').textContent()).includes('Trang 1/3')&&(await p.locator('#forecast-rows').textContent()).includes('Saved 1'),`${role} snapshot resets table page`);
   await p.locator('#forecast-row-next').click();await p.locator('#forecast-pdf').click();await p.waitForFunction(()=>document.getElementById('forecast-message').textContent.includes('Đã tải báo cáo'));
   const exported=requests.findLast(r=>r.act==='forecast-export');check(exported.body.plan_id===1&&!('page' in exported.body)&&!('rows' in exported.body)&&!('total' in exported.body),`${role} export still snapshot source without page/client money`);
   empty=true;await p.locator('#forecast-filters button').click();await p.waitForFunction(()=>document.getElementById('forecast-row-page').textContent.includes('0 dòng'));
   check((await p.locator('#forecast-rows').textContent()).includes('Chưa có dòng')&&await p.locator('#forecast-row-prev').isDisabled()&&await p.locator('#forecast-row-next').isDisabled(),`${role} empty table resets paging`);
  }finally{await context.close();}
 }
 check(errors.length===0,'No JS runtime errors');return {checks:checks.length,results:checks,mode:'static/API mock, no DB'};
}
