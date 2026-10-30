async(page)=>{
 const checks=[],errors=[],check=(ok,label)=>{if(!ok)throw new Error(label);checks.push(label);};
 for(const role of ['admin_it','system_manager','support_technician']){
  const context=await page.context().browser().newContext({viewport:{width:1366,height:1000}}),p=await context.newPage(),requests=[];
  let fail=false,empty=false,clamp=false;
  p.on('pageerror',e=>errors.push(e.message));
  const json=(route,data,status=200)=>route.fulfill({status,contentType:'application/json',body:JSON.stringify({success:status===200,data,message:status===200?'':'QA unavailable'})});
  await context.route('**/*',async route=>{
   const req=route.request(),url=new URL(req.url());if(url.hostname!=='127.0.0.1')return route.abort();
   if(req.isNavigationRequest()){const response=await route.fetch();return route.fulfill({response,body:(await response.text()).replace('data-mode="mock"','data-mode="live"')});}
   if(url.pathname!=='/admin.php')return route.continue();
   const act=url.searchParams.get('act');
   if(act==='data')return json(route,{assets:[],departments:[],pagination:{page:1,per_page:5,total:0},user:{id:111,role,display_name:'QA',role_label:role},summary:{total:0,by_status:{},by_department:[],alerts:[],alerts_total:0}});
   if(act==='categories')return json(route,[]);
   if(act==='tickets')return json(route,{rows:[],page:1,total:0,per_page:20});
   if(act==='ticket-statistics'){
    requests.push({year:url.searchParams.get('year'),asset:url.searchParams.get('asset_id'),page:Number(url.searchParams.get('page')),method:req.method()});
    if(fail)return json(route,null,503);
    const number=empty?1:clamp?1:Number(url.searchParams.get('page'));
    return json(route,{year:Number(url.searchParams.get('year')),page:number,total:empty?0:21,per_page:20,summary:{ticket_count:empty?0:52,cost:empty?'0':'9999999999999999.99'},rows:empty?[]:Array.from({length:number===1?20:1},(_,i)=>({asset_code:`QA-${number}-${i} <img src=x>`,type:'repair',status:'resolved',n:1,cost:'12.25'}))});
   }
   return json(route,{rows:[],alerts:[],total:0,active_total:0,alerts_total:0,page:1,per_page:20});
  });
  try{
   await p.goto('http://127.0.0.1:18766/#tickets');await p.locator('#ticket-live').waitFor({state:'visible'});
   await p.locator('#ticket-year').fill('2026');await p.locator('#ticket-filter-asset').fill('7');
   await p.locator('#ticket-stat-form button[type=submit]').click();await p.waitForFunction(()=>document.getElementById('ticket-stat-page').textContent.includes('Trang 1/2'));
   check(await p.locator('#ticket-statistics tbody tr').count()===20,`${role} first page rows`);
   check(await p.locator('#ticket-stat-prev').isDisabled(),`${role} first previous disabled`);
   check((await p.locator('#ticket-stat-summary').textContent()).includes('52 phiếu')&&(await p.locator('#ticket-stat-summary').textContent()).includes('9.999.999.999.999.999,99'),`${role} global exact totals`);
   await p.locator('#ticket-year').fill('2025');await p.locator('#ticket-filter-asset').fill('8');
   await p.locator('#ticket-stat-next').click();await p.waitForFunction(()=>document.getElementById('ticket-stat-page').textContent.includes('Trang 2/2'));
   check(requests.at(-1).year==='2026'&&requests.at(-1).asset==='7'&&requests.at(-1).page===2,`${role} paging keeps applied filters`);
   check(await p.locator('#ticket-statistics tbody tr').count()===1&&await p.locator('#ticket-stat-next').isDisabled(),`${role} last page`);
   check(await p.locator('#ticket-statistics img').count()===0,`${role} escaped group identity`);
   fail=true;await p.locator('#ticket-stat-prev').click();await p.waitForFunction(()=>document.getElementById('ticket-message').textContent.includes('HTTP 503'));
   check((await p.locator('#ticket-stat-page').textContent()).includes('Trang 2/2')&&await p.locator('#ticket-statistics tbody tr').count()===1,`${role} error retains previous result`);
   fail=false;clamp=true;await p.locator('#ticket-stat-prev').click();await p.waitForFunction(()=>document.getElementById('ticket-stat-page').textContent.includes('Trang 1/2'));
   await p.locator('#ticket-stat-next').click();await p.waitForFunction(()=>!document.getElementById('ticket-live').hasAttribute('aria-busy'));
   check((await p.locator('#ticket-stat-page').textContent()).includes('Trang 1/2')&&await p.locator('#ticket-stat-prev').isDisabled(),`${role} server clamp synchronized`);
   for(const width of [1366,768,390]){
    await p.setViewportSize({width,height:1000});
    check(await p.locator('#ticket-stat-pagination').evaluate(n=>{const r=n.getBoundingClientRect();return r.left>=0&&r.right<=innerWidth&&document.documentElement.scrollWidth<=innerWidth;}),`${role} pagination bounds ${width}`);
   }
   if(role==='admin_it')await p.screenshot({path:'.playwright-cli/phase11-ticket-statistics-390.png',fullPage:true});
   empty=true;await p.locator('#ticket-stat-form button[type=submit]').click();await p.waitForFunction(()=>document.getElementById('ticket-statistics').textContent.includes('Chưa có phiếu'));
   check(requests.at(-1).year==='2025'&&requests.at(-1).asset==='8'&&requests.at(-1).page===1,`${role} submitting applies edits and resets page`);
   check(await p.locator('#ticket-stat-pagination').isHidden()&&(await p.locator('#ticket-stat-summary').textContent()).includes('0 phiếu'),`${role} empty result totals`);
   await p.locator('#ticket-year').fill('2011');await p.locator('#ticket-stat-form button[type=submit]').click();await p.waitForFunction(()=>document.getElementById('ticket-message').textContent.includes('năm 2011'));
   check(requests.at(-1).year==='2011'&&(await p.locator('#ticket-message').textContent()).includes('Không có phiếu'),'2011 reflects selected opened year, clear empty feedback');
   await p.locator('#ticket-year-current').click();check(Number(await p.locator('#ticket-year').inputValue())===Number(new Intl.DateTimeFormat('en',{year:'numeric',timeZone:'Asia/Ho_Chi_Minh'}).format(new Date())),'Reset to current VN year without fetching');
   check(requests.every(r=>r.method==='GET'),`${role} no POST`);
  }finally{await context.close();}
 }
 check(errors.length===0,'No JavaScript runtime errors');return {checks:checks.length,results:checks,mode:'static/API mock, no DB'};
}
