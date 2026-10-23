async(page)=>{
 const checks=[],errors=[],check=(ok,label)=>{if(!ok)throw new Error(label);checks.push(label);};
 for(const role of ['admin_it','system_manager','support_technician']){
  const context=await page.context().browser().newContext({viewport:{width:1366,height:1000}}),p=await context.newPage(),reads=[],posts=[];let fail=false,saveFails=false,mockReads=0,hold=false,release;
  const row=id=>({id,source_asset_id:2,target_asset_id:3,source_name:'QA <img src=x>',target_name:'QA B',link_type:'fiber',source_port:'A',target_port:'B',capacity_label:'1G',note:'<b>text</b>',version:'version-'+id});
  const data=number=>({mock:false,can_manage:role==='admin_it',nodes:[{id:2,asset_code:'QA-A',name:'QA <img src=x>',kind:'Switch',department:'QA A',status:'in_use'},{id:3,asset_code:'QA-B',name:'QA B',kind:'Router',department:'QA A',status:'broken'}],links:[{id:1,source_asset_id:2,target_asset_id:3,link_type:'fiber',capacity_label:'1G'}],rows:[row(number===2?21:1)],page:number,total:21,per_page:20});
  const json=(route,data,status=200,message='')=>route.fulfill({status,contentType:'application/json',body:JSON.stringify({success:status===200,data,message})});
  p.on('pageerror',e=>errors.push(e.message));
  await context.route('**/*',async route=>{
   const req=route.request(),url=new URL(req.url());if(url.hostname!=='127.0.0.1')return route.abort();
   if(req.isNavigationRequest()){const r=await route.fetch();return route.fulfill({response:r,body:(await r.text()).replace('data-mode="mock"','data-mode="live"').replace('name="ita-csrf" content=""','name="ita-csrf" content="qa-token"')});}
   if(url.pathname==='/assets/ita/mock/network.json'){mockReads++;return route.abort();}
   if(url.pathname!=='/admin.php')return route.continue();
   const act=url.searchParams.get('act');
   if(act==='data')return json(route,{assets:[],departments:[{value:'10',label:'QA A'}],pagination:{page:1,total:0,per_page:5},user:{id:111,role,display_name:'QA',role_label:role},summary:{total:0,by_status:{},by_department:[],alerts:[],alerts_total:0}});
   if(act==='categories')return json(route,[]);
   if(act==='network'){
    reads.push(Object.fromEntries(url.searchParams));if(hold){hold=false;await new Promise(r=>release=r);}
    if(fail)return json(route,null,503,'QA unavailable');return json(route,data(Number(url.searchParams.get('page'))||1));
   }
   if(act==='network-save'){posts.push({payload:req.postDataJSON(),csrf:req.headers()['x-csrf-token'],method:req.method()});return json(route,saveFails?null:{id:7},saveFails?409:200,'QA stale version');}
   return json(route,{rows:[],total:0,active_total:0,alerts_total:0});
  });
  try{
   await p.goto('http://127.0.0.1:18766/#network');
   if(role==='support_technician'){
    await p.waitForFunction(()=>window.itaLiveContext?.user?.role==='support_technician');check(reads.length===0&&await p.locator('#network-content').isHidden()&&await p.locator('#network-nav').isHidden(),'Support no topology request or content');continue;
   }
   await p.locator('#network-content').waitFor({state:'visible'});
   check(mockReads===0&&await p.locator('#network-nodes g').count()===2,'Live loads server topology without MOCK');
   check(await p.locator('#network-editor').isHidden()===(role!=='admin_it'),'Admin-only editor');
   check(!(await p.locator('#network-heading').textContent()).includes('MOCK')&&!(await p.locator('#network-badge').textContent()).includes('MOCK'),'Live labels distinguish real declared data');
   await p.locator('#network-node-list button').first().click();check((await p.locator('#network-detail-title').textContent()).includes('<img src=x>')&&await p.locator('#network-panel img').count()===0,'Node and row text safely escaped');
   await p.locator('#network-next').click();await p.waitForFunction(()=>document.getElementById('network-page').textContent.includes('Trang 2/2'));
   check(reads.at(-1).page==='2'&&await p.locator('#network-next').isDisabled(),'Server table next page and boundary');
   await p.locator('#network-search').fill('QA');await p.locator('#network-department').selectOption('10');await p.locator('#network-filters button[type=submit]').click();await p.waitForFunction(()=>document.getElementById('network-page').textContent.includes('Trang 1/2'));
   check(reads.at(-1).q==='QA'&&reads.at(-1).department_id==='10'&&reads.at(-1).page==='1','Applied filters reset server page');
   for(const width of [1366,768,390]){await p.setViewportSize({width,height:1000});check(await p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1),'No document overflow '+role+' '+width);}
   if(role==='admin_it'){
    await p.getByRole('button',{name:'Sửa',exact:true}).click();check(await p.locator('#network-edit-version').inputValue()==='version-1','Edit retains server version');
    saveFails=true;await p.locator('#network-note').fill('QA retained');await p.locator('#network-save').click();await p.waitForFunction(()=>document.getElementById('network-save-message').textContent.includes('Chưa gửi lại'));
    check(posts.length===1&&posts[0].csrf==='qa-token'&&posts[0].method==='POST'&&posts[0].payload.version==='version-1','CSRF and version sent once');
    check(await p.locator('#network-note').inputValue()==='QA retained','409 keeps input without automatic POST retry');
    saveFails=false;await p.locator('#network-save').click();await p.waitForFunction(()=>document.getElementById('network-edit-id').value==='');check(posts.length===2,'Explicit retry saves and clears edit identity');
    p.once('dialog',d=>d.accept());await p.getByRole('button',{name:'Ngừng liên kết',exact:true}).click();await p.waitForFunction(()=>document.getElementById('network-save-message').textContent==='Đã lưu liên kết.');check(posts.at(-1).payload.action==='delete'&&posts.at(-1).payload.version==='version-1','Confirmed soft-delete carries version');
    await p.locator('#network-show-3d').click();await p.locator('#network-3d canvas').waitFor();check(!(await p.locator('#network-3d-status').textContent()).includes('MOCK'),'Live 3D lazy scene has correct label');await p.locator('#network-show-2d').click();check(await p.locator('#network-3d canvas').count()===0,'Live 3D disposed on 2D');
    await p.screenshot({path:'.playwright-cli/phase13-live-390.png',fullPage:true});
   }
   fail=true;await p.locator('#network-reset').click();await p.locator('#network-retry').waitFor({state:'visible'});check(await p.locator('#network-content').isHidden(),'Read failure hides stale topology');
   fail=false;await p.locator('#network-retry').click();await p.locator('#network-content').waitFor({state:'visible'});
   hold=true;await p.locator('#network-reset').click();await p.waitForTimeout(100);await p.evaluate(()=>{window.itaLiveContext.user.role='support_technician';dispatchEvent(new CustomEvent('ita:data'));});release?.();await p.waitForTimeout(100);
   check(await p.locator('#network-content').isHidden()&&await p.locator('#network-nav').isHidden(),'Late response cannot restore UI after role change');
  }finally{await context.close();}
 }
 check(errors.length===0,'No JS runtime errors');return {checks:checks.length,results:checks,scope:'API mock/static; no DB/host auth'};
}
