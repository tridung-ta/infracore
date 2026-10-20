async(page)=>{
 const browser=page.context().browser(),checks=[],errors=[];
 const check=(ok,label)=>{if(!ok)throw new Error(label);checks.push(label);};
 async function scenario(role){
  const context=await browser.newContext({viewport:{width:1366,height:1000}}),p=await context.newPage(),posts=[];
  let fail='',ticket=null,waiting=false;
  const asset=()=>({id:7,asset_code:'QA-7',name:'QA <img src=x>',status:ticket?'maintenance':'in_use',current_user_id:null,department_id:10,category_id:2,version:'asset-v',awaiting_ready:waiting});
  p.on('pageerror',e=>errors.push(e.message));
  const json=(route,data,status=200,message='')=>route.fulfill({status,contentType:'application/json',body:JSON.stringify({success:status===200,data,message})});
  await context.route('**/*',async route=>{
   const req=route.request(),url=new URL(req.url());if(url.hostname!=='127.0.0.1')return route.abort();
   if(req.isNavigationRequest()){const response=await route.fetch();return route.fulfill({response,body:(await response.text()).replace('data-mode="mock"','data-mode="live"').replace('name="ita-csrf" content=""','name="ita-csrf" content="qa-token"')});}
   if(url.pathname!=='/admin.php')return route.continue();
   const act=url.searchParams.get('act');
   if(act==='categories')return json(route,[]);
   if(act==='data')return json(route,{assets:[{...asset(),code:'QA-7',category:'QA',department:'QA',serial:'QA',specs:'QA',warranty:'QA',location:'QA'}],departments:[{value:'10',label:'QA'}],pagination:{page:1,per_page:5,total:1},user:{id:111,role,display_name:'QA',role_label:role},summary:{total:1,by_status:{in_use:1},by_department:[],alerts:[],alerts_total:0}});
   if(act==='asset')return json(route,{...asset(),code:'QA-7',category:'QA',department:'QA',serial:'QA',specs:'QA',warranty:'QA',location:'QA',fields:asset(),history:[],history_has_more:false});
   if(act==='tickets')return json(route,{rows:ticket?[{...ticket,asset_code:'QA-7'}]:[],page:1,total:ticket?1:0,per_page:20});
   if(act==='ticket')return json(route,{ticket,asset:asset(),history:[{id:1,kind:'status',created_at:'2026-10-20 00:00:00',user_id:111,from_status:null,to_status:'pending',note:'QA <script>'},{id:2,kind:'ticket.assign',created_at:'2026-10-20 01:00:00',user_id:111,before_json:'{"technician_id":null}',after_json:'{"technician_id":222}'}],page:1,total:2,per_page:20});
   if(act==='ticket-technicians')return json(route,{rows:[{id:222,username:'qa-tech',fullname:'QA Tech'}],page:1,total:1,per_page:20});
   if(act==='ticket-statistics')return json(route,{year:2026,rows:[{type:'repair',status:'resolved',n:1,cost:'120.25'}]});
   if(act==='ticket-save'){
    const body=req.postDataJSON();posts.push({body,csrf:req.headers()['x-csrf-token'],method:req.method()});
    if(fail==='network'){fail='';return route.abort();}if(fail==='conflict'){fail='';return json(route,null,409,'Thiết bị đã có phiếu pending khác.');}if(fail==='server'){fail='';return json(route,null,503,'QA backend unavailable');}
    if(body.operation==='create')ticket={id:9,asset_id:7,ticket_no:'QA-TICKET-9',type:body.type,severity:body.severity,status:'pending',issue:body.note,technician_id:null,cost:'0.00',handled_date:null,resolution_note:null,version:'v1'};
    if(body.operation==='assign')ticket={...ticket,technician_id:Number(body.technician_id),version:'v2'};
    if(body.operation==='transition'){ticket={...ticket,status:body.status,cost:body.cost,handled_date:body.handled_date,resolution_note:body.note,version:'v3'};waiting=body.status==='resolved';}
    if(body.operation==='cost')ticket={...ticket,cost:body.cost,version:'v4'};
    if(body.operation==='ready')waiting=false;
    return json(route,{id:9,asset_id:7});
   }
   return json(route,null,404);
  });
  await p.goto('http://127.0.0.1:18766/#tickets');await p.locator('#ticket-live').waitFor({state:'visible'});
  await p.locator('#ticket-create-section summary').click();await p.locator('#ticket-asset').fill('7');await p.locator('#ticket-issue').fill('QA <img src=x> issue');await p.locator('#ticket-create button').click();await p.locator('#ticket-record').waitFor({state:'visible'});
  return {context,p,posts,fail:value=>fail=value};
 }
 for(const role of ['admin_it','system_manager','support_technician']){
  const s=await scenario(role),p=s.p;
  try{
   check(s.posts[0].body.operation==='create'&&s.posts[0].csrf==='qa-token',`Scoped create form ${role}`);
   check(await p.locator('#ticket-details img').count()===0&&await p.locator('#ticket-history script').count()===0,`Escaped text ${role}`);
   check((await p.locator('#ticket-history').textContent()).includes('ticket.assign'),`Merged assignment audit ${role}`);
   await p.locator('#ticket-filter button').click();await p.waitForFunction(()=>document.getElementById('ticket-message').textContent.includes('Đã tải danh sách:'));
   check((await p.locator('#ticket-message').textContent()).includes('1 phiếu'),`List reload completion feedback ${role}`);
   if(role==='admin_it'){
    for(const width of [1366,768,390]){await p.setViewportSize({width,height:1000});check(await p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),`No page overflow ${width}`);check(await p.locator('#ticket-cost').evaluate(n=>{const r=n.getBoundingClientRect();return r.left>=0&&r.right<=innerWidth;}),`Form bounds ${width}`);await p.screenshot({path:`.playwright-cli/phase07-${width}.png`,fullPage:true});}
    await p.locator('#ticket-technician').selectOption('222');await p.locator('#ticket-assign-note').fill('QA assign');await p.locator('#ticket-assign button[type=submit]').click();await p.waitForFunction(()=>document.getElementById('ticket-message').textContent.includes('đọc lại phiếu.'));
    await p.waitForFunction(()=>document.getElementById('ticket-details').textContent.includes('222')&&!document.getElementById('ticket-live').hasAttribute('aria-busy'));
    check(s.posts.at(-1).body.technician_id==='222','Technician assignment payload');
    await p.locator('#ticket-handled').fill('2026-10-20');await p.locator('#ticket-cost').fill('120.25');await p.locator('#ticket-resolution').fill('QA done');await p.locator('#ticket-transition button').click();await p.locator('#ticket-ready-badge').waitFor({state:'visible'});
    check(await p.locator('#ticket-ready').isVisible(),'Unassigned resolved awaits Admin ready');
    await p.locator('#ticket-correct-cost').fill('130.25');await p.locator('#ticket-cost-reason').fill('QA invoice correction');await p.locator('#ticket-cost-form button').click();await p.waitForFunction(()=>document.getElementById('ticket-correct-cost').value==='130.25');check(s.posts.at(-1).body.operation==='cost'&&s.posts.at(-1).body.note==='QA invoice correction','Closed cost reason payload');
    await p.locator('#ticket-ready-reason').fill('QA inspected');await p.locator('#ticket-ready button').click();await p.locator('#ticket-ready-badge').waitFor({state:'hidden'});check(s.posts.at(-1).body.asset_version==='asset-v','Ready uses asset version');
    await p.locator('#ticket-stat-form button').click();await p.waitForFunction(()=>document.getElementById('ticket-statistics').textContent.includes('120.25'));check((await p.locator('#ticket-statistics').textContent()).includes('1 phiếu'),'Year report distinct ticket output');
    await p.evaluate(()=>location.hash='assets');await p.locator('#asset-rows .detail-button').click();await p.locator('#asset-dialog').waitFor({state:'visible'});
    await p.locator('.asset-ticket-history summary').click();await p.locator('.asset-ticket-history button').filter({hasText:'QA-TICKET-9'}).click();
    await p.waitForFunction(()=>document.querySelector('.asset-ticket-history').textContent.includes('ticket.assign'));
    check((await p.locator('.asset-ticket-history').textContent()).includes('pending'),'Inline asset history merges state and assignment audit');
    await p.locator('#asset-dialog .dialog-close').count().then(async count=>{if(count)await p.locator('#asset-dialog .dialog-close').click();else await p.evaluate(()=>document.getElementById('asset-dialog').close());});
    await p.evaluate(()=>location.hash='tickets');s.fail('server');await p.locator('#ticket-create button').click();await p.waitForFunction(()=>document.getElementById('ticket-message').textContent.includes('HTTP 503'));
    check((await p.locator('#ticket-message').textContent()).includes('QA backend unavailable'),'Backend rejection shown with HTTP status');
   }else if(role==='system_manager'){check(await p.locator('#ticket-transition').isHidden()&&await p.locator('#ticket-assign').isVisible(),'Manager assign but no transition');}
   else{
    check(await p.locator('#ticket-assign').isHidden()&&await p.locator('#ticket-transition').isVisible(),'Support processes without reassign');
    s.fail('network');await p.locator('#ticket-handled').fill('2026-10-20');await p.locator('#ticket-cost').fill('1');await p.locator('#ticket-resolution').fill('QA done');await p.locator('#ticket-transition button').click();await p.waitForFunction(()=>document.getElementById('ticket-message').textContent.includes('Chưa xác nhận'));check(await p.locator('#ticket-transition button').isDisabled(),'Uncertain write blocks resubmit until reload');
    const feedback=await p.locator('#ticket-message').evaluate(n=>{const r=n.getBoundingClientRect();return {top:r.top,bottom:r.bottom,height:innerHeight,active:document.activeElement.id};});check(feedback.top>=0&&feedback.bottom<=feedback.height,'Write failure feedback visible in viewport '+JSON.stringify(feedback));
   }
  }finally{await s.context.close();}
 }
 check(errors.length===0,'No JavaScript runtime errors');return {checks:checks.length,results:checks,mode:'API mock, no DB'};
}
