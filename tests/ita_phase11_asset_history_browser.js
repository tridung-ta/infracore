async(page)=>{
 const checks=[],errors=[],check=(ok,label)=>{if(!ok)throw new Error(label);checks.push(label);};
 for(const role of ['admin_it','system_manager','support_technician']){
  const context=await page.context().browser().newContext({viewport:{width:1366,height:1000}}),p=await context.newPage(),requests=[];
  let failure=false,empty=false,clamp=false,holdHistory=false,holdDetail=false,releaseHistory=null,releaseDetail=null;
  const asset=id=>({id,code:`QA-${id}`,name:`Asset ${id}`,serial:'QA',category:'QA',department:'QA',status:'in_use',warranty:'QA',specs:'QA',location:'QA',fields:{category_id:2,department_id:10},version:'v1',history:[],history_pagination:{page:1,per_page:20,total:41},history_has_more:true});
  p.on('pageerror',e=>errors.push(e.message));
  const json=(route,data,status=200)=>route.fulfill({status,contentType:'application/json',body:JSON.stringify({success:status===200,data,message:status===200?'':'QA history denied'})});
  await context.route('**/*',async route=>{
   const req=route.request(),url=new URL(req.url());if(url.hostname!=='127.0.0.1')return route.abort();
   if(req.isNavigationRequest()){const response=await route.fetch();return route.fulfill({response,body:(await response.text()).replace('data-mode="mock"','data-mode="live"')});}
   if(url.pathname!=='/admin.php')return route.continue();
   const act=url.searchParams.get('act');
   if(act==='data')return json(route,{assets:[asset(7),asset(8)],departments:[],pagination:{page:1,per_page:5,total:2},user:{id:111,role,display_name:'QA',role_label:role},summary:{total:2,by_status:{in_use:2},by_department:[],alerts:[],alerts_total:0}});
   if(act==='categories')return json(route,[]);
   if(act==='asset'){
    const id=Number(url.searchParams.get('id')),requested=Number(url.searchParams.get('page')||1);requests.push({id,page:requested,method:req.method()});
    if(holdHistory&&url.searchParams.has('page')){holdHistory=false;await new Promise(resolve=>{releaseHistory=resolve;});}
    if(holdDetail&&!url.searchParams.has('page')){holdDetail=false;await new Promise(resolve=>{releaseDetail=resolve;});}
    if(failure&&url.searchParams.has('page'))return json(route,null,403);
    const number=clamp||empty?1:Math.min(requested,3),count=empty?0:number===3?1:20;
    return json(route,{...asset(id),history:Array.from({length:count},(_,i)=>({id:100-(number-1)*20-i,user_id:111,action:`asset.note.${id}.${number}`,created_at:'2026-10-23 01:00:00',before_json:null,after_json:i===1?'invalid':JSON.stringify({note:'<img src=x> history'})})),history_pagination:{page:number,per_page:20,total:empty?0:41},history_has_more:number<3});
   }
   return json(route,{rows:[],alerts:[],total:0,active_total:0,alerts_total:0,page:1,per_page:20});
  });
  const open=async index=>{await p.locator('#asset-rows button').nth(index).click();await p.locator('#asset-dialog').waitFor({state:'visible'});await p.locator('#asset-history-tab').click();};
  const loaded=async text=>{await p.waitForFunction(value=>document.getElementById('asset-history-page')?.textContent.includes(value)&&!document.getElementById('asset-history-panel')?.hasAttribute('aria-busy'),text);};
  try{
   await p.goto('http://127.0.0.1:18766/#assets');await p.locator('#asset-rows button').first().waitFor();await open(0);
   check(await p.locator('.asset-history details').count()===20&&await p.locator('#asset-history-prev').isDisabled(),`${role} first page`);
   check(await p.locator('.asset-history img').count()===0,`${role} history text escaped`);
   await p.locator('#asset-history-next').click();await loaded('Trang 2/3');
   check(requests.at(-1).id===7&&requests.at(-1).page===2,`${role} page2 request`);
   await p.locator('#asset-history-next').click();await loaded('Trang 3/3');
   check(await p.locator('.asset-history details').count()===1&&await p.locator('#asset-history-next').isDisabled(),`${role} last page`);
   failure=true;await p.locator('#asset-history-prev').click();await p.waitForFunction(()=>document.querySelector('.asset-history [role=alert]')?.textContent.includes('QA history denied'));
   check(await p.locator('.asset-history details').count()===0,`${role} failure clears history content`);
   failure=false;clamp=true;await p.locator('#asset-history-prev').click();await loaded('Trang 1/3');
   check(await p.locator('#asset-history-prev').isDisabled(),`${role} clamp synchronization`);clamp=false;
   for(const width of [1366,768,390]){await p.setViewportSize({width,height:1000});await p.locator('.asset-history nav').scrollIntoViewIfNeeded();check(await p.locator('.asset-history nav').evaluate(n=>{const r=n.getBoundingClientRect();return r.left>=0&&r.right<=innerWidth&&r.top>=0&&r.bottom<=innerHeight&&document.documentElement.scrollWidth<=innerWidth;}),`${role} reachable paging bounds ${width}`);}
   if(role==='admin_it')await p.screenshot({path:'.playwright-cli/phase11-asset-history-390.png',fullPage:true});
   holdHistory=true;await p.locator('#asset-history-next').click();await p.waitForFunction(()=>document.querySelector('.asset-history').hasAttribute('aria-busy'));
   check(await p.locator('#asset-history-next').isDisabled(),`${role} loading disables paging`);
   await p.locator('#close-dialog').click();await open(1);releaseHistory();await p.waitForTimeout(150);
   check((await p.locator('#dialog-title').textContent())==='Asset 8'&&(await p.locator('.asset-history').textContent()).includes('asset.note.8.1')&&!(await p.locator('.asset-history').textContent()).includes('asset.note.7.2'),`${role} late history cannot replace another asset`);
   await p.locator('#close-dialog').click();holdDetail=true;releaseDetail=null;
   await p.locator('#asset-rows button').first().click();await p.waitForTimeout(50);await open(1);releaseDetail();await p.waitForTimeout(150);
   check((await p.locator('#dialog-title').textContent())==='Asset 8',`${role} late initial detail cannot replace another asset`);
   empty=true;await p.locator('#asset-history-next').click();await loaded('Trang 1/1');
   check((await p.locator('.asset-history').textContent()).includes('Chưa có lịch sử')&&await p.locator('#asset-history-next').isDisabled(),`${role} empty history`);
   check(requests.every(r=>r.method==='GET'),`${role} history has no POST`);
  }finally{if(releaseHistory)releaseHistory();if(releaseDetail)releaseDetail();await context.close();}
 }
 check(errors.length===0,'No JavaScript runtime errors');return {checks:checks.length,results:checks,mode:'static/API mock, no DB'};
}
