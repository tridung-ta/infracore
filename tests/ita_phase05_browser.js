async(page)=>{
 const browser=page.context().browser(),checks=[],errors=[];
 const check=(ok,label)=>{if(!ok)throw new Error(label);checks.push(label);};
 async function scenario(role){
  const context=await browser.newContext({viewport:{width:1366,height:1000}}),p=await context.newPage(),posts=[],reads=[];
  let failure='',getFailure=false;
  let asset={id:7,code:'QA-7',name:'QA <script>thiết bị',status:'in_use',department_id:10,current_user_id:role==='support_technician'?111:null,version:'v1',maintenance_overdue:true,next_maintenance_due:'2020-01-01'};
  let active=role==='support_technician'?{id:9,to_user_id:111,to_department_id:null,assigned_at:'2026-10-19 00:00:00',due_return:null,handover_no:'QA-BG',version:'a1',acknowledged_at:null}:null;
  let history=active?[active]:[];
  p.on('pageerror',e=>errors.push(e.message));
  const json=(route,data,status=200,message='')=>route.fulfill({status,contentType:'application/json',body:JSON.stringify({success:status===200,data,message})});
  await context.route('**/*',async route=>{
   const request=route.request(),url=new URL(request.url());
   if(url.hostname!=='127.0.0.1')return route.abort();
   if(request.isNavigationRequest()){const response=await route.fetch();return route.fulfill({response,body:(await response.text()).replace('data-mode="mock"','data-mode="live"').replace('name="ita-csrf" content=""','name="ita-csrf" content="qa-token"')});}
   if(url.pathname!=='/admin.php')return route.continue();
   const action=url.searchParams.get('act');reads.push({action,query:Object.fromEntries(url.searchParams)});
   if(action==='categories')return json(route,[]);
   if(action==='alerts')return json(route,{as_of:'2026-10-22T08:00:00+07:00',page:1,per_page:20,total:1,active_total:1,rows:[{entity_type:'asset',entity_id:7,level:'warning',code:'QA-7',name:'QA',title:'Quá hạn bảo trì: QA'}]});
   if(action==='data')return json(route,{assets:[{...asset,serial:'QA',category:'PC',department:'QA',warranty:'QA',specs:'QA',location:'QA'}],departments:[{value:'10',label:'QA Dept'}],pagination:{page:1,per_page:5,total:1},user:{id:111,role,display_name:'QA',role_label:role},summary:{total:1,by_status:{in_use:1},by_department:[],alerts:[{asset_id:7,level:'warning',title:'Quá hạn bảo trì: QA',description:'QA'}],alerts_total:1}});
   if(action==='assignments'){
    if(getFailure){getFailure=false;return json(route,null,503,'QA reload unavailable');}
    return json(route,{asset,active,history,page:1,per_page:20,total:history.length,pdf_available:false});
   }
   if(action==='assignment-recipients')return json(route,{rows:[{id:111,username:'qa',fullname:'QA <img src=x>'}],page:Number(url.searchParams.get('page')||1),per_page:20,total:21});
   if(action==='assignment-save'){
    const body=request.postDataJSON();posts.push({body,csrf:request.headers()['x-csrf-token']});
    if(failure==='conflict'){failure='';return json(route,null,409,'Bàn giao vừa thay đổi. Hãy tải lại.');}
    if(failure==='network'){failure='';return route.abort();}
    if(body.operation==='assign'){
     active={id:9,to_user_id:body.target_type==='user'?Number(body.target_id):null,to_department_id:body.target_type==='department'?Number(body.target_id):null,handover_no:'QA-BG-009',assigned_at:'2026-10-19 00:00:00',due_return:body.due_return,version:'a1',acknowledged_at:null};history=[active];asset={...asset,version:'v2',current_user_id:active.to_user_id};
    }
    if(body.operation==='acknowledge'){active={...active,version:'a2',acknowledged_at:'2026-10-19 01:00:00'};history=[active];}
    if(body.operation==='return'){history=[{...active,returned_at:'2026-10-19 02:00:00'}];active=null;asset={...asset,version:'v3',current_user_id:null};}
    return json(route,{id:9,asset_id:7,operation:body.operation});
   }
   return json(route,null,404);
  });
  await p.goto('http://127.0.0.1:18766/#assignments');await p.waitForFunction(()=>window.itaLiveContext);
  check(!reads.some(r=>r.action==='assignments'||r.action==='assignment-recipients'),'No handover/recipient fetch before interaction '+role);
  await p.locator('#handover-search-form button').click();await p.locator('#handover-asset option[value="7"]').waitFor({state:'attached'});check(!reads.some(r=>r.action==='assignments'),'Search waits for explicit asset selection '+role);await p.locator('#handover-asset').selectOption('7');await p.locator('#handover-record').waitFor({state:'visible'});
  return {p,context,posts,reads,fail:value=>failure=value,failGet:()=>getFailure=true};
 }
 const admin=await scenario('admin_it');
 try{
  const p=admin.p;
  check(await p.locator('#assignment-live').isVisible()&&await p.locator('#assignment-scenario').isHidden(),'Live data replaces mock controls');
  check(await p.locator('#handover-details script').count()===0,'Asset text escaped');
  check((await p.locator('#handover-details').textContent()).includes('Quá hạn từ 2020-01-01'),'Maintenance overdue shown');
  await p.evaluate(()=>location.hash='assets');await p.locator('#assets-panel').waitFor({state:'visible'});
  check((await p.locator('#asset-rows').textContent()).includes('Quá hạn bảo trì'),'Maintenance badge in asset list');
  await p.evaluate(()=>location.hash='alerts');await p.locator('#dashboard-live-panel').waitFor({state:'visible'});await p.locator('#dash-output').filter({hasText:'Quá hạn bảo trì: QA'}).waitFor();
  check((await p.locator('#dash-output').textContent()).includes('Quá hạn bảo trì: QA'),'Maintenance alert in notification center');
  await p.evaluate(()=>location.hash='assignments');await p.locator('#assignment-live').waitFor({state:'visible'});
  check(await p.locator('#handover-assign').isDisabled(),'Recipient must be selected');
  await p.locator('#handover-target').selectOption('111');
  for(const width of [1366,768,390]){
   await p.setViewportSize({width,height:1000});check(await p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),`Live handover fits ${width}`);
   check(await p.locator('#handover-target').evaluate(n=>{const r=n.getBoundingClientRect();return r.left>=0&&r.right<=innerWidth;}),`Recipient control fits ${width}`);
   if(width!==768)await p.screenshot({path:`.playwright-cli/phase05-live-${width}.png`,fullPage:true});
  }
  await p.locator('#handover-user-next').click();await p.waitForFunction(()=>document.getElementById('handover-user-page').textContent==='2/2');
  check(admin.reads.some(r=>r.action==='assignment-recipients'&&r.query.page==='2'),'Recipients paginated at server');
  await p.locator('#handover-target').selectOption('111');await p.locator('#handover-create-note').fill('QA note');await p.locator('#handover-assign').click();await p.locator('#handover-message').filter({hasText:'Đã giao thiết bị thành công'}).waitFor();
  check(admin.posts[0].body.asset_version==='v1'&&admin.posts[0].body.target_id==='111'&&admin.posts[0].csrf==='qa-token','Assign payload version/recipient/CSRF');
  check(await p.locator('#handover-create').isHidden()&&await p.locator('#handover-active').isVisible(),'Active handover blocks another assign');
  await p.locator('#handover-acknowledge').click();await p.locator('#handover-message').filter({hasText:'Đã xác nhận bạn nhận thiết bị'}).waitFor();
  check(admin.posts.at(-1).body.assignment_version==='a1'&&await p.locator('#handover-acknowledge').isHidden(),'Own receipt acknowledged once');
  await p.locator('#handover-return').click();check(admin.posts.length===2,'Blank return condition blocks submit');
  await p.locator('#handover-return-note').fill('QA returned');await p.locator('#handover-return').click();await p.locator('#handover-message').filter({hasText:'Đã thu hồi thiết bị thành công'}).waitFor();
  check(admin.posts.at(-1).body.operation==='return'&&admin.posts.at(-1).body.assignment_version==='a2','Return includes current assignment version');
  check((await p.locator('#handover-history').textContent()).includes('Đã thu hồi'),'Return retained in history');
  await p.locator('#handover-target-type').selectOption('department');await p.locator('#handover-target').selectOption('10');
  admin.fail('conflict');await p.locator('#handover-assign').click();await p.locator('#handover-message').filter({hasText:'Bàn giao vừa thay đổi'}).waitFor();
  check(await p.locator('#handover-assign').isDisabled(),'Conflict blocks stale resubmission');
  await p.locator('#handover-reload').click();await p.locator('#handover-target').selectOption('10');
  admin.fail('network');await p.locator('#handover-assign').click();await p.locator('#handover-message').filter({hasText:'Chưa xác nhận được'}).waitFor();
  check(await p.locator('#handover-assign').isDisabled(),'Unknown save result requires reload');
  await p.locator('#handover-reload').click();await p.locator('#handover-target').selectOption('10');
  admin.failGet();await p.locator('#handover-assign').click();await p.locator('#handover-message').filter({hasText:'Đã lưu thao tác, nhưng chưa tải lại'}).waitFor();
  check(await p.locator('#handover-record').isHidden(),'Saved-but-refresh-failed does not expose stale record');
  await p.locator('#handover-reload').click();await p.locator('#handover-active').waitFor({state:'visible'});
  check(await p.locator('#handover-acknowledge').isHidden(),'Department handover has no self acknowledgement');
  check(await p.locator('#handover-pdf').isDisabled(),'PDF disabled when server reports unavailable');
 }finally{await admin.context.close();}
 for(const role of ['system_manager','support_technician']){
  const test=await scenario(role);
  try{
   const p=test.p;
   check(await p.locator('#handover-create').isVisible()===(role==='system_manager'),`Assign UI role ${role}`);
   if(role==='support_technician'){
    check(!test.reads.some(r=>r.action==='assignment-recipients'),'Support does not load employee picker');
    await p.locator('#handover-return-note').fill('QA support received');await p.locator('#handover-return').click();await p.locator('#handover-message').filter({hasText:'Đã thu hồi thiết bị thành công'}).waitFor();
    check(test.posts.at(-1).body.operation==='return','Support return workflow');
   }
  }finally{await test.context.close();}
 }
 check(errors.length===0,'No JavaScript runtime errors');
 return {result:'PASS: browser API mock, not DB integration',checks};
}
