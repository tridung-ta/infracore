async(page)=>{
 const browser=page.context().browser(),checks=[],errors=[];
 const check=(ok,label)=>{if(!ok)throw new Error(label);checks.push(label);};
 async function session(role,available=true){
  const context=await browser.newContext({viewport:{width:1366,height:1000}}),p=await context.newPage(),downloads=[];
  let failure='';p.on('pageerror',error=>errors.push(error.message));
  const json=(route,data,status=200,message='')=>route.fulfill({status,contentType:'application/json',body:JSON.stringify({success:status===200,data,message})});
  const asset={id:7,code:'QA-7',name:'Thiết bị PDF QA',status:'in_use',department_id:10,current_user_id:111,version:'v1',maintenance_overdue:false,next_maintenance_due:null};
  const active={id:9,to_user_id:111,to_department_id:null,assigned_at:'2026-10-20 03:00:00',due_return:null,handover_no:'QA-9',version:'a1',acknowledged_at:null};
  await context.route('**/*',async route=>{
   const request=route.request(),url=new URL(request.url());if(url.hostname!=='127.0.0.1')return route.abort();
   if(request.isNavigationRequest()){const response=await route.fetch();return route.fulfill({response,body:(await response.text()).replace('data-mode="mock"','data-mode="live"')});}
   if(url.pathname!=='/admin.php')return route.continue();
   const action=url.searchParams.get('act');
   if(action==='categories')return json(route,[]);
   if(action==='data')return json(route,{assets:[{...asset,serial:'QA',category:'PC',department:'QA',warranty:'QA',specs:'QA',location:'QA'}],departments:[{value:'10',label:'QA Dept'}],pagination:{page:1,per_page:5,total:1},user:{id:111,role,display_name:'QA',role_label:role},summary:{total:1,by_status:{in_use:1},by_department:[],alerts:[],alerts_total:0}});
   if(action==='assignments')return json(route,{asset,active,history:[active,{...active,id:8,handover_no:'QA-8',returned_at:'2026-10-19 03:00:00'}],page:1,per_page:20,total:2,pdf_available:available});
   if(action==='assignment-pdf'){
    downloads.push({id:url.searchParams.get('id'),method:request.method()});
    if(failure==='denied'){failure='';return json(route,null,403,'Bạn không có quyền tải biên bản này.');}
    if(failure==='invalid'){failure='';return route.fulfill({contentType:'application/pdf',body:'not-a-pdf'});}
    if(failure==='network'){failure='';return route.abort();}
    return route.fulfill({contentType:'application/pdf',body:Buffer.from('%PDF-1.4\nQA transport mock; real PDF tested by PHP and pypdf\n%%EOF')});
   }
   return json(route,null,404);
  });
  await p.goto('http://127.0.0.1:18766/#assignments');await p.locator('#handover-active').waitFor({state:'visible'});
  return {p,context,downloads,fail:kind=>failure=kind};
 }
 for(const role of ['admin_it','system_manager','support_technician']){
  const test=await session(role);
  try{
   const p=test.p;check(await p.locator('#handover-pdf').isEnabled(),`Scoped role PDF control ${role}`);
   const event=p.waitForEvent('download');await p.locator('#handover-pdf').click();check((await event).suggestedFilename()==='ita-handover-9.pdf',`Active PDF filename ${role}`);
   await p.locator('#handover-message').filter({hasText:'Đã tải biên bản PDF'}).waitFor();
   const history=p.waitForEvent('download');await p.getByRole('button',{name:'Tải PDF QA-8',exact:true}).click();await history;
   check(test.downloads.at(-1).id==='8'&&test.downloads.every(r=>r.method==='GET'),`Historical PDF selected by persisted ID ${role}`);
   if(role==='admin_it'){
    for(const kind of ['denied','invalid','network']){
     test.fail(kind);await p.locator('#handover-pdf').click();await p.waitForFunction(()=>document.getElementById('assignment-live').getAttribute('aria-busy')==='false');
     check(await p.locator('#handover-message').getAttribute('role')==='alert'&&await p.locator('#handover-pdf').isEnabled(),`PDF ${kind} error visible and retry enabled`);
    }
    for(const width of [1366,768,390]){await p.setViewportSize({width,height:1000});check(await p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),`PDF controls fit ${width}`);}
   }
  }finally{await test.context.close();}
 }
 const missing=await session('admin_it',false);
 try{check(await missing.p.locator('#handover-pdf').isDisabled()&&await missing.p.getByRole('button',{name:'Tải PDF QA-8',exact:true}).isDisabled(),'Missing renderer/GD disables all PDF controls');check((await missing.p.locator('#handover-pdf-help').textContent()).includes('GD/mbstring'),'Missing runtime requirements explained');}
 finally{await missing.context.close();}
 check(errors.length===0,'No JavaScript runtime errors');
 return {result:'PASS: browser transport/roles mocked; actual PDF verified separately',checks};
}
