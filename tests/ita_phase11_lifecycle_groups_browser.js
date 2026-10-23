async(page)=>{
 const source=await (await page.request.get('http://127.0.0.1:18766/assets/ita/mock/lifecycle.json')).json(),checks=[],errors=[];
 const check=(ok,label)=>{if(!ok)throw new Error(label);checks.push(label);};
 for(const role of ['admin_it','system_manager','support_technician']){
  const context=await page.context().browser().newContext({viewport:{width:1366,height:1000}}),p=await context.newPage(),requests=[];
  const groups=(count,prefix)=>Array.from({length:count},(_,i)=>({group_id:i+1,group_name:i===0?null:`${prefix} ${i+1} <img src=x>`,total:10,failed_assets:3,age_known:8,average_age_years:'2.25'}));
  p.on('pageerror',e=>errors.push(e.message));
  await context.route('**/*',async route=>{
   const req=route.request(),url=new URL(req.url());if(url.hostname!=='127.0.0.1')return route.abort();
   const json=(data,status=200)=>route.fulfill({status,contentType:'application/json',body:JSON.stringify({success:status===200,data,message:''})});
   if(req.isNavigationRequest()){const r=await route.fetch();return route.fulfill({response:r,body:(await r.text()).replace('data-mode="mock"','data-mode="live"')});}
   if(url.pathname!=='/admin.php')return route.continue();
   const act=url.searchParams.get('act');
   if(act==='categories')return json([]);
   if(act==='data')return json({assets:[],departments:[],pagination:{page:1,per_page:5,total:0},user:{id:111,role,display_name:'QA',role_label:role},summary:{total:0,by_status:{},by_department:[],alerts:[],alerts_total:0}});
   if(act!=='lifecycle')return json({rows:[],total:0,active_total:0,alerts_total:0});
   requests.push(req.url());if(role==='support_technician')return json(null,403);
   const q=url.searchParams.get('q'),count=q==='none'?0:q==='narrow'?3:41,departments=groups(count?Math.min(count,21):0,'Dept');
   departments.forEach((r,i)=>{const total=count===41?(i===20?10:20):10;r.total=total;r.failed_assets=total*0.3;r.age_known=total*0.8;});
   return json({...source,rows:source.rows.slice(0,1),page:1,per_page:20,total:count*10,summary:{total:count*10,age_known:count*8,average_age_years:'2.25',missing_scores:0},by_category:groups(count,'Category'),by_department:departments});
  });
  try{
   await p.goto('http://127.0.0.1:18766/#lifecycle');
   if(role==='support_technician'){await p.waitForFunction(()=>document.getElementById('lifecycle-message').textContent.includes('không có quyền'));check(requests.length===0&&await p.locator('#lifecycle-content').isHidden(),'Support does not access group report');continue;}
   await p.locator('#lifecycle-content').waitFor({state:'visible'});
   const initialReads=requests.length,summary=await p.locator('#lifecycle-summary').textContent(),rows=await p.locator('#lifecycle-rows').textContent();
   check(await p.locator('[data-group=category] tbody tr').count()===20&&await p.locator('#lifecycle-group-prev').isDisabled(),`${role} first 20 groups`);
   check((await p.locator('[data-group=category]').textContent()).includes('Chưa phân bổ')&&await p.locator('#lifecycle-group-tables img').count()===0,`${role} null group and escaped name`);
   check((await p.locator('[data-group=category] tbody tr').first().textContent()).includes('3/10 · 30%'),`${role} full group denominator unchanged`);
   await p.locator('#lifecycle-group-next').focus();await p.keyboard.press('Enter');
   check((await p.locator('#lifecycle-group-page').textContent()).includes('Trang 2/3')&&(await p.locator('[data-group=category]').textContent()).includes('Category 21'),`${role} keyboard next page`);
   await p.locator('#lifecycle-group-next').click();check(await p.locator('[data-group=category] tbody tr').count()===1&&await p.locator('#lifecycle-group-next').isDisabled(),`${role} final page bounded`);
   await p.locator('[data-lifecycle-group=department]').click();
   check((await p.locator('#lifecycle-group-page').textContent()).includes('Trang 1/2')&&await p.locator('#lifecycle-group-prev').isDisabled(),`${role} department independent first page`);
   await p.locator('#lifecycle-group-next').click();await p.locator('[data-lifecycle-group=category]').click();
   check((await p.locator('#lifecycle-group-page').textContent()).includes('Trang 3/3'),`${role} category page remembered`);
   await p.locator('[data-lifecycle-group=department]').click();check((await p.locator('#lifecycle-group-page').textContent()).includes('Trang 2/2'),`${role} department page remembered`);
   check(requests.length===initialReads&&await p.locator('#lifecycle-summary').textContent()===summary&&await p.locator('#lifecycle-rows').textContent()===rows,`${role} group paging no HTTP or summary/device changes`);
   for(const width of [1366,768,390]){await p.setViewportSize({width,height:1000});await p.locator('#lifecycle-group-next').scrollIntoViewIfNeeded();check(await p.locator('#lifecycle-group-next').evaluate(n=>{const r=n.getBoundingClientRect();return r.left>=0&&r.right<=innerWidth&&document.documentElement.scrollWidth<=innerWidth;}),`${role} group pager bounds ${width}`);}
   if(role==='admin_it')await p.screenshot({path:'.playwright-cli/phase11-lifecycle-groups-390.png',fullPage:true});
   await p.locator('#lifecycle-search').fill('narrow');await p.locator('#lifecycle-filters button[type=submit]').click();await p.waitForFunction(()=>document.getElementById('lifecycle-group-page').textContent.includes('3 nhóm'));
   check((await p.locator('#lifecycle-group-page').textContent()).includes('Trang 1/1')&&await p.locator('#lifecycle-group-next').isDisabled(),`${role} refreshed groups reset and clamp`);
   await p.locator('#lifecycle-search').fill('none');await p.locator('#lifecycle-filters button[type=submit]').click();await p.waitForFunction(()=>document.getElementById('lifecycle-group-page').textContent.includes('0 nhóm'));
   check((await p.locator('#lifecycle-group-tables').textContent()).includes('Chưa có dữ liệu nhóm')&&await p.locator('#lifecycle-group-prev').isDisabled()&&await p.locator('#lifecycle-group-next').isDisabled(),`${role} empty groups explained`);
  }finally{await context.close();}
 }
 check(errors.length===0,'No JS runtime errors');return {checks:checks.length,results:checks,mode:'static/API mock, FE only, no DB'};
}
