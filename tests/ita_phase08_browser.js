async(page)=>{
 const source=await (await page.request.get('http://127.0.0.1:18766/assets/ita/mock/lifecycle.json')).json(),checks=[],errors=[];
 const check=(ok,label)=>{if(!ok)throw new Error(label);checks.push(label);};
 const summarize=rows=>{const known=rows.filter(r=>r.age_years!==null);return {total:rows.length,failed_assets:rows.filter(r=>r.repair_count>0).length,age_known:known.length,average_age_years:known.length?known.reduce((n,r)=>n+Number(r.age_years),0)/known.length:null,missing_scores:rows.filter(r=>r.tier==='missing').length};};
 const grouped=(rows,key)=>[...new Set(rows.map(r=>r[key+'_id']))].map(id=>({group_id:id,group_name:rows.find(r=>r[key+'_id']===id)[key+'_name'],...summarize(rows.filter(r=>r[key+'_id']===id))}));
 for(const role of ['admin_it','system_manager','support_technician']){
  const context=await page.context().browser().newContext({viewport:{width:1366,height:1000}}),p=await context.newPage(),requests=[];let fail=false;
  p.on('pageerror',e=>errors.push(e.message));
  await context.route('**/*',async route=>{
   const req=route.request(),url=new URL(req.url());if(url.hostname!=='127.0.0.1')return route.abort();
   const json=(data,status=200,message='')=>route.fulfill({status,contentType:'application/json',body:JSON.stringify({success:status===200,data,message})});
   if(req.isNavigationRequest()){const r=await route.fetch();return route.fulfill({response:r,body:(await r.text()).replace('data-mode="mock"','data-mode="live"')});}
   if(url.pathname!=='/admin.php')return route.continue();
   const action=url.searchParams.get('act');
   if(action==='categories')return json([]);
   if(action==='data')return json({assets:[],pagination:{page:1,per_page:5,total:0},departments:[],user:{id:111,role,display_name:'QA',role_label:role},summary:{total:0,by_status:{},by_department:[],alerts:[],alerts_total:0}});
   if(!['lifecycle','lifecycle-detail'].includes(action))return json(null,404);
   requests.push({action,method:req.method(),q:Object.fromEntries(url.searchParams)});
   if(role==='support_technician')return json(null,403,'Không có quyền');
   if(fail){fail=false;return json(null,422,'Cấu hình vòng đời không hợp lệ');}
   const rows=source.rows.filter(r=>role==='admin_it'||r.department_id===1);
   if(action==='lifecycle-detail'){
    const row=rows.find(r=>r.id===Number(url.searchParams.get('id')));return row?json({asset:{...row,name:'QA <script>alert(1)</script>'},window:source.window,config:source.config}):json(null,403,'Ngoài phạm vi');
   }
   const tier=url.searchParams.get('tier'),category=url.searchParams.get('category_id'),department=url.searchParams.get('department_id'),q=url.searchParams.get('q')||'';
   const filtered=rows.filter(r=>(!tier||r.tier===tier)&&(!category||String(r.category_id)===category)&&(!department||String(r.department_id)===department)&&(!q||(r.asset_code+' '+r.name).includes(q)));
   const current=Math.max(1,Math.min(Number(url.searchParams.get('page'))||1,Math.ceil(filtered.length/2)||1));
   return json({...source,rows:filtered.slice((current-1)*2,current*2),page:current,per_page:2,total:filtered.length,summary:summarize(filtered),by_category:grouped(filtered,'category'),by_department:grouped(filtered,'department')});
  });
  try{
   await p.goto('http://127.0.0.1:18766/#lifecycle');
   if(role==='support_technician'){
    await p.waitForFunction(()=>document.getElementById('lifecycle-message').textContent.includes('không có quyền'));
    check(await p.locator('#lifecycle-content').isHidden(),'Support sees no lifecycle data');check(await p.locator('#lifecycle-nav').isHidden(),'Support lifecycle navigation hidden');check(requests.length===0,'Support UI does not request protected reports');continue;
   }
   await p.locator('#lifecycle-content').waitFor({state:'visible'});
   check(await p.locator('#lifecycle-summary dt').count()===4,`Structured summary ${role}`);
   const readsBeforeSwitch=requests.length;
   await p.locator('[data-lifecycle-group=department]').focus();await p.keyboard.press('Space');
   check(await p.locator('[data-group=department]').isVisible()&&await p.locator('[data-group=category]').isHidden(),`Keyboard group switch ${role}`);
   check(requests.length===readsBeforeSwitch,`Group switch uses existing response ${role}`);
   await p.locator('#lifecycle-show-method').click();
   check(await p.locator('#lifecycle-method').evaluate(n=>n.open),`Defaults note opens calculation help ${role}`);
   check(new URL(p.url()).hash==='#lifecycle',`Calculation help keeps current route ${role}`);
   await p.locator('#lifecycle-method>summary').click();
   await p.locator('#lifecycle-more-filters>summary').click();await p.locator('#lifecycle-department').selectOption('1');await p.locator('#lifecycle-filters button[type=submit]').click();
   await p.waitForFunction(()=>!document.getElementById('lifecycle-panel').hasAttribute('aria-busy'));
   check(requests.at(-1).q.department_id==='1',`Advanced department filter ${role}`);
   await p.locator('#lifecycle-clear').click();await p.waitForFunction(()=>!document.getElementById('lifecycle-panel').hasAttribute('aria-busy'));
   check(requests.at(-1).q.department_id===''&&requests.at(-1).q.tier===''&&requests.at(-1).q.page==='1',`Clear filters resets server query ${role}`);
   check(await p.locator('[data-group=department]').isVisible(),`Group choice survives reload ${role}`);
   await p.locator('#lifecycle-more-filters>summary').click();
   check(await p.locator('#lifecycle-defaults').isVisible(),`Missing settings note ${role}`);
   check((await p.locator('#lifecycle-rows').textContent()).includes('MOCK-NAS-02'),`Sorted raw score first ${role}`);
   check(await p.locator('#lifecycle-rows meter').first().evaluate(n=>n.max>1&&n.value>1),`Score above 1 meter ${role}`);
   check((await p.locator('#lifecycle-rows').textContent()).includes('0,800'),`Three decimal score display ${role}`);
   await p.locator('#lifecycle-rows button').first().click();await p.locator('#lifecycle-detail').waitFor({state:'visible'});
   check(await p.locator('#lifecycle-detail-parts dl').count()===3,`Three readable score components ${role}`);
   check(await p.locator('#lifecycle-rows tr.is-selected').count()===1,`Selected device highlighted ${role}`);
   await p.locator('.lifecycle-detail-source>summary').click();
   check((await p.locator('#lifecycle-detail-values').innerText()).includes(source.rows[0].score_raw),`Exact raw score stays accessible ${role}`);
   await p.emulateMedia({reducedMotion:'reduce'});check(await p.locator('#lifecycle-detail').evaluate(n=>getComputedStyle(n).animationName)==='none',`Reduced motion ${role}`);await p.emulateMedia({reducedMotion:'no-preference'});
   check(await p.locator('#lifecycle-detail script').count()===0,`Escaped detail ${role}`);
   check((await p.locator('#lifecycle-detail-values').textContent()).includes('Điểm gốc'),`Raw score explanation ${role}`);
   await p.locator('#lifecycle-detail-close').click();
   check(await p.locator('#lifecycle-rows button').first().evaluate(n=>n===document.activeElement),`Close restores row focus ${role}`);
   await p.locator('#lifecycle-next').click();await p.waitForFunction(()=>document.getElementById('lifecycle-page').textContent.includes('Trang 2'));check(requests.at(-1).q.page==='2',`Server pagination ${role}`);
   await p.locator('#lifecycle-tier').selectOption('replace');await p.locator('#lifecycle-filters button[type=submit]').click();await p.waitForFunction(()=>document.getElementById('lifecycle-page').textContent.includes('1 thiết bị'));
   check(requests.at(-1).q.tier==='replace'&&requests.at(-1).q.page==='1',`Filter resets page ${role}`);
   check((await p.locator('#lifecycle-summary').textContent()).includes('1 thiết bị'),`Filtered summary ${role}`);
   await p.locator('#lifecycle-tier').selectOption('');await p.locator('#lifecycle-search').fill('no-match');await p.locator('#lifecycle-filters button[type=submit]').click();await p.waitForFunction(()=>document.getElementById('lifecycle-message').textContent.includes('0 thiết bị'));check((await p.locator('#lifecycle-rows').textContent()).includes('Không có thiết bị'),`Empty state ${role}`);
   await p.locator('#lifecycle-search').fill('');fail=true;await p.locator('#lifecycle-filters button[type=submit]').click();await p.locator('#lifecycle-retry').waitFor({state:'visible'});
   check(await p.locator('#lifecycle-content').isHidden(),`Configuration error removes stale results ${role}`);
   await p.locator('#lifecycle-retry').click();await p.locator('#lifecycle-content').waitFor({state:'visible'});
   check(requests.every(r=>r.method==='GET'),`Read-only transport ${role}`);
   if(role==='admin_it')for(const width of [1366,768,390]){
    await p.setViewportSize({width,height:1000});check(await p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),`No document overflow ${width}`);check(await p.locator('#lifecycle-filters').evaluate(n=>{const r=n.getBoundingClientRect();return r.left>=0&&r.right<=innerWidth;}),`Filter bounds ${width}`);if(width===390)check(await p.locator('#lifecycle-rows .lifecycle-score').first().evaluate(n=>{const r=n.getBoundingClientRect();return r.left>=0&&r.right<=innerWidth;}),'Mobile score visible without horizontal scrolling');await p.screenshot({path:`.playwright-cli/phase08-${width}.png`,fullPage:true});
   }
  }finally{await context.close();}
 }
 // Static MOCK path must not call production endpoints.
 const c=await page.context().browser().newContext(),p=await c.newPage();let productionCalls=0;
 await c.route('**/admin.php?*',r=>{productionCalls++;return r.abort();});
 await p.goto('http://127.0.0.1:18766/#lifecycle');await p.locator('#lifecycle-content').waitFor({state:'visible'});
 check((await p.locator('#lifecycle-mode').textContent()).includes('MOCK'),'Static preview clearly marked MOCK');check(productionCalls===0,'Static preview no production API');await c.close();
 check(errors.length===0,'No JavaScript runtime errors');return {checks:checks.length,results:checks,scope:'browser API mock + static fixture, no DB'};
}
