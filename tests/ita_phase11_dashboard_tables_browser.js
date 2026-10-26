async(page)=>{
 const checks=[],errors=[],check=(ok,label)=>{if(!ok)throw new Error(label);checks.push(label);};
 for(const role of ['admin_it','system_manager','support_technician']){
  const context=await page.context().browser().newContext({viewport:{width:1366,height:1000}}),p=await context.newPage();
  let reads=0,empty=false,fail=false,hold=false,release;
  const groups=Array.from({length:41},(_,i)=>({name:i===40?'<img src=x> QA 41':'QA '+(i+1),total:10,by_status:{in_use:5,maintenance:2,broken:1,retired:2},analyzed:8,failed_assets:2,fault_rate:.25,replace:1,missing_scores:1}));
  p.on('pageerror',e=>errors.push(e.message));
  const json=(route,data,status=200)=>route.fulfill({status,contentType:'application/json',body:JSON.stringify({success:status===200,data,message:status===200?'':'QA read failure'})});
  await context.route('**/*',async route=>{
   const req=route.request(),u=new URL(req.url());if(u.hostname!=='127.0.0.1')return route.abort();
   if(req.isNavigationRequest()){const r=await route.fetch();return route.fulfill({response:r,body:(await r.text()).replace('data-mode="mock"','data-mode="live"')});}
   if(u.pathname!=='/admin.php')return route.continue();
   const act=u.searchParams.get('act');
   if(act==='data')return json(route,{assets:[],departments:[],pagination:{page:1,total:0,per_page:5},user:{id:111,role,display_name:'QA',role_label:role},summary:{total:0,by_status:{},by_department:[],alerts:[],alerts_total:0}});
   if(act==='categories')return json(route,[]);
   if(act==='dashboard'){
    reads++;if(hold){hold=false;await new Promise(resolve=>release=resolve);}
    if(fail)return json(route,null,503);
    return json(route,{as_of:'2026-10-26T10:00:00+07:00',config:{using_defaults:true},groups:empty?[]:groups,alerts_total:4,summary:{total:410,managed:328,analyzed:328,failed_assets:82,fault_rate:.25,replace:41},replacements:empty?[]:groups.map((g,i)=>({asset_code:'QA-'+(i+1),name:g.name,score_display:'1.250',tier:'replace'}))});
   }
   if(act==='report-preview')return json(route,{as_of:'2026-10-26T10:00:00+07:00',source:'QA',headers:['Mã'],rows:[['QA maintenance']],data_count:1,missing:0,page:1,per_page:20,total:1});
   return json(route,{rows:[],total:0,active_total:4,alerts_total:0});
  });
  try{
   await p.goto('http://127.0.0.1:18766/#overview');
   if(role==='support_technician'){
    await p.waitForFunction(()=>document.getElementById('dash-output').textContent.includes('QA maintenance'));
    check(reads===0&&await p.locator('[data-dash-paged]').count()===0,'Support keeps maintenance report; no dashboard/group paging');continue;
   }
   await p.waitForFunction(()=>document.querySelectorAll('#dash-output [data-dash-paged]').length===4&&document.getElementById('dash-output').getAttribute('aria-busy')==='false');
   const sections=p.locator('#dash-output [data-dash-paged]');
   check(await sections.count()===4&&await sections.first().locator('tbody tr').count()===20,'Four independent paged tables render 20 rows');
   await p.evaluate(()=>{window.qaChartRefs=[...document.querySelectorAll('#dash-output .dash-chart')].map(n=>echarts.getInstanceByDom(n));window.qaKpi=document.querySelector('#dash-output .kpi-grid').textContent;window.qaTime=document.getElementById('dash-time').textContent;});
   const before=reads,first=sections.nth(0),replacement=sections.nth(3);
   await first.locator('button').last().click();check((await first.locator('tbody tr').first().textContent()).includes('QA 21')&&await first.locator('tbody tr').count()===20,'Second table page starts at original row21');
   await first.locator('button').last().click();check(await first.locator('tbody tr').count()===1&&await first.locator('button').last().isDisabled(),'Last page contains row41 and disables next');
   check((await first.locator('tbody').textContent()).includes('<img src=x>')&&await first.locator('img').count()===0,'Late-page group name remains escaped text');
   check((await sections.nth(1).locator('nav span').textContent()).includes('Trang 1 / 3'),'Group tables keep independent page state');
   await replacement.locator('button').last().focus();await p.keyboard.press('Enter');check((await replacement.locator('tbody').textContent()).includes('QA-21'),'Keyboard pages replacements');
   check(await replacement.locator('button').last().evaluate(n=>n===document.activeElement),'Paging retains button focus');
   check(reads===before,'Table pagination performs no API read');
   check(await p.evaluate(()=>window.qaChartRefs.every((c,i)=>c===echarts.getInstanceByDom(document.querySelectorAll('#dash-output .dash-chart')[i])&&!c.isDisposed()&&c.getOption().xAxis[0].data.length===41)),'Chart instances and all41 categories retained');
   check(await p.evaluate(()=>document.querySelector('#dash-output .kpi-grid').textContent===window.qaKpi&&document.getElementById('dash-time').textContent===window.qaTime),'KPI and evaluation time unchanged');
   check((await sections.nth(1).locator('tbody tr').first().textContent()).includes('25.0%'),'Fault rate uses original group value');
   for(const width of [1366,768,390]){
    await p.setViewportSize({width,height:1000});check(await p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1),'No page overflow '+role+' '+width);
    check(await sections.first().locator('.dash-table').evaluate(n=>{const r=n.getBoundingClientRect();return r.left>=0&&r.right<=innerWidth+1&&getComputedStyle(n).overflowX==='auto';}),'Table scroll bounded '+width);
   }
   if(role==='admin_it')await p.screenshot({path:'.playwright-cli/phase11-dashboard-tables-390.png',fullPage:true});
   hold=true;await p.locator('#dash-refresh').click();await p.waitForFunction(()=>document.getElementById('dash-output').getAttribute('aria-busy')==='true');
   check(await first.locator('button').first().isDisabled()&&await replacement.locator('button').last().isDisabled(),'Read in progress locks all client table navigation');
   release();await p.waitForFunction(()=>document.getElementById('dash-output').getAttribute('aria-busy')==='false');check((await sections.first().locator('nav span').textContent()).includes('Trang 1 / 3'),'New response resets every table to page1');
   fail=true;await p.locator('#dash-refresh').click();await p.waitForFunction(()=>document.getElementById('dash-message').textContent.includes('QA read failure'));check(await sections.first().locator('button').last().isEnabled(),'Failed read preserves previous data and unlocks its navigation');
   fail=false;empty=true;await p.locator('#dash-refresh').click();await p.waitForFunction(()=>document.querySelector('#dash-output [data-dash-paged] nav span').textContent.includes('0 dòng'));
   check(await sections.first().locator('button').first().isDisabled()&&await sections.first().locator('button').last().isDisabled()&&(await sections.first().locator('tbody').textContent()).includes('Không có kết quả'),'Empty response explains state and disables both directions');
  }finally{await context.close();}
 }
 check(errors.length===0,'No runtime errors');return {checks:checks.length,results:checks,scope:'Static layout/API mock only; client pagination, not SQL/DB/host'};
}
