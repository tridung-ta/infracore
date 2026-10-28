async(page)=>{
 const browser=page.context().browser(),checks=[],errors=[];
 const check=(ok,label)=>{if(!ok)throw new Error(label);checks.push(label);};
 async function scenario(role){
  const context=await browser.newContext({viewport:{width:1366,height:1000}}),p=await context.newPage(),posts=[];
  let failure='',used=2,name='QA <img src=x> Phần mềm';
  const license=()=>({id:7,software_name:name,vendor:'QA',license_type:'per_device',total_seats:2,used_seats:used,start_date:null,expire_date:'2026-10-20',cost:null,status:'active',has_key:true,key_mask:'••••••••',version:'v'+used,alerts:{days_remaining:0,expiry_level:'critical',overallocated:used>2}});
  p.on('pageerror',e=>errors.push(e.message));
  const json=(route,data,status=200,message='')=>route.fulfill({status,contentType:'application/json',body:JSON.stringify({success:status===200,data,message})});
  await context.route('**/*',async route=>{
   const request=route.request(),url=new URL(request.url());if(url.hostname!=='127.0.0.1')return route.abort();
   if(request.isNavigationRequest()){const response=await route.fetch();return route.fulfill({response,body:(await response.text()).replace('data-mode="mock"','data-mode="live"').replace('name="ita-csrf" content=""','name="ita-csrf" content="qa-token"')});}
   if(url.pathname!=='/admin.php')return route.continue();
   const action=url.searchParams.get('act');
   if(action==='data')return json(route,{assets:[],departments:[{value:'10',label:'QA'}],pagination:{page:1,per_page:5,total:0},user:{id:111,role,display_name:'QA',role_label:role},summary:{total:0,by_status:{},by_department:[],license_counts:{critical:1,warning:0},alerts:[{license_id:7,level:'critical',title:'Giấy phép QA',description:'QA alert'}],alerts_total:1}});
   if(action==='categories')return json(route,[]);
   if(action==='alerts')return json(route,{as_of:'2026-10-22T00:00:00Z',rows:[{level:'critical',code:'LIC-7',name:'Giấy phép QA',title:'Giấy phép hết hạn'}],total:1,active_total:1,page:1,per_page:20});
   if(action==='licenses')return json(route,{rows:[license()],total:1,page:1,per_page:20});
   if(action==='license')return json(route,{license:license(),allocations:[{id:9,asset_id:9,user_id:null,department_id:null,allocated_at:'2026-10-20 00:00:00',released_at:null}],total:1,page:1,per_page:20});
   if(['license-save','license-key'].includes(action)){
    const body=request.postDataJSON();posts.push({action,body,method:request.method(),csrf:request.headers()['x-csrf-token']});
    if(failure==='network'){failure='';return route.abort();}
    if(failure==='audit'){failure='';return json(route,null,503,'Không thể ghi nhật ký.');}
    if(action==='license-key')return json(route,{key:'QA-SECRET-ONLY-MEMORY',hide_after_seconds:10});
    if(body.operation==='save')name=body.software_name;
    if(body.operation==='allocate'){
     if(used>=2&&!(role==='admin_it'&&body.confirm_overage&&body.override_reason))return json(route,null,422,'Admin IT phải xác nhận vượt mức và nhập lý do.');used++;
    }
    return json(route,{id:7});
   }
   return json(route,null,404);
  });
  await p.goto('http://127.0.0.1:18766/#licenses');await p.locator('#licenses-panel').waitFor();
  if(role!=='support_technician')await p.locator('#license-rows button').waitFor();
  else await p.waitForFunction(()=>document.getElementById('license-message').textContent.includes('không có quyền'));
  return {p,context,posts,fail:value=>failure=value};
 }
 const admin=await scenario('admin_it');
 try{
  const p=admin.p;await p.locator('#license-rows button').click();await p.locator('#license-editor').waitFor({state:'visible'});
  check(await p.locator('#license-details img').count()===0,'HTML injection escaped');
  check((await p.locator('#license-details').textContent()).includes('Hết hạn trong 0 ngày'),'Expiry boundary displayed');
  await p.evaluate(()=>location.hash='alerts');await p.locator('#dash-output').getByText('Giấy phép hết hạn',{exact:true}).waitFor();
  check((await p.locator('#dash-output').textContent()).includes('Giấy phép QA'),'License alert shown in current notification center');
  await p.evaluate(()=>location.hash='licenses');await p.locator('#licenses-panel').waitFor({state:'visible'});
  const beforeInvalid=admin.posts.length;
  await p.locator('#license-name').fill('1');await p.locator('#license-vendor').fill('1');
  await p.locator('#license-start').fill('111111-11-11');await p.locator('#license-expire').fill('111111-11-11');await p.locator('#license-editor button[type=submit]').click();
  check(admin.posts.length===beforeInvalid&&!await p.locator('#license-start').evaluate(n=>n.checkValidity()),'Six-digit year blocked before any POST');
  await p.locator('#license-start').fill('2026-10-28');await p.locator('#license-expire').fill('2025-10-28');await p.locator('#license-editor button[type=submit]').click();
  check(admin.posts.length===beforeInvalid&&!await p.locator('#license-expire').evaluate(n=>n.checkValidity()),'Expiry before start blocked');
  await p.locator('#license-start').fill('2024-10-28');check(await p.locator('#license-expire').evaluate(n=>n.checkValidity()),'Changing start clears paired error');
  check(await p.locator('#license-name').evaluate(n=>n.checkValidity()),'Numeric-only software name remains allowed by current contract');
  await p.locator('#license-start').fill('');await p.locator('#license-expire').fill('2026-10-20');
  await p.locator('#license-name').fill('QA updated');await p.locator('#license-key-input').fill('QA-new-key');await p.locator('#license-editor button[type=submit]').click();
  await p.waitForFunction(()=>document.getElementById('license-message').textContent.includes('đọc lại hồ sơ.'));
  check(admin.posts.at(-1).body.software_name==='QA updated'&&admin.posts.at(-1).body.license_key==='QA-new-key','Metadata form sends name and new key');
  check(await p.locator('#license-name').inputValue()==='QA updated'&&await p.locator('#license-key-input').inputValue()==='','Metadata read back and key input cleared');
  for(const width of [1366,768,390]){
   await p.setViewportSize({width,height:1000});
   check(await p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),`No page overflow ${width}`);
   check(await p.locator('#license-name').evaluate(n=>{const r=n.getBoundingClientRect();return r.left>=0&&r.right<=innerWidth;}),`Form bounds ${width}`);
   check(await p.locator('#license-type').evaluate(n=>getComputedStyle(n).backgroundImage!=='none'),`Select arrow ${width}`);
   await p.screenshot({path:`.playwright-cli/phase06-${width}.png`,fullPage:true});
  }
  await p.locator('#license-target').fill('9');await p.locator('#license-allocate button[type=submit]').click();
  await p.waitForFunction(()=>document.getElementById('license-message').textContent.includes('nhập lý do'));
  check(admin.posts.at(-1).body.confirm_overage===false,'Overage not silently confirmed');
  await p.locator('#license-search-form button').click();await p.waitForFunction(()=>!document.getElementById('license-live').hasAttribute('aria-busy'));
  await p.locator('#license-target').fill('9');await p.locator('#license-allocate-over').check();await p.locator('#license-allocate-reason').fill('QA approved overage');
  await p.locator('#license-allocate button[type=submit]').click();await p.waitForFunction(()=>document.getElementById('license-message').textContent.includes('đọc lại hồ sơ.'));
  check(admin.posts.at(-1).body.override_reason==='QA approved overage'&&admin.posts.at(-1).csrf==='qa-token','Admin overage reason and CSRF sent');
  check((await p.locator('#license-details').textContent()).includes('Vượt số chỗ'),'Overage read back');
  await p.locator('#license-password').fill('qa-password');await p.locator('#license-reveal button[type=submit]').click();await p.locator('#license-key-output').waitFor({state:'visible'});
  const reveal=admin.posts.at(-1);check(reveal.action==='license-key'&&reveal.method==='POST'&&reveal.csrf==='qa-token'&&reveal.body.password==='qa-password','Reveal POST CSRF reauth');
  check(await p.locator('#license-password').inputValue()==='','Password cleared');
  check(await p.evaluate(()=>!Object.values(localStorage).join().includes('QA-SECRET')&&!Object.values(sessionStorage).join().includes('QA-SECRET')),'Key never placed in browser storage');
  await p.locator('#license-key-output').waitFor({state:'hidden',timeout:12000});check(await p.locator('#license-key-output').textContent()==='','Key auto-cleared after ten seconds');
  admin.fail('audit');await p.locator('#license-password').fill('qa-password');await p.locator('#license-reveal button[type=submit]').click();await p.waitForFunction(()=>document.getElementById('license-message').textContent.includes('nhật ký'));
  check(await p.locator('#license-key-output').isHidden(),'Audit error does not expose key');
  await p.locator('#license-password').fill('qa-password');await p.locator('#license-reveal button[type=submit]').click();await p.locator('#license-key-output').waitFor({state:'visible'});
  await p.evaluate(()=>location.hash='assets');await p.locator('#license-key-output').waitFor({state:'hidden'});check(await p.locator('#license-key-output').textContent()==='','Navigation clears key');
 }finally{await admin.context.close();}
 const manager=await scenario('system_manager');try{const p=manager.p;await p.locator('#license-rows button').click();await p.locator('#license-record').waitFor({state:'visible'});check(await p.locator('#license-editor').isHidden()&&await p.locator('#license-reveal').isHidden(),'Manager cannot edit shared metadata or reveal');check(await p.locator('#license-allocate-override').isHidden(),'Manager has no override control');manager.fail('network');await p.locator('#license-target').fill('9');await p.locator('#license-allocate button[type=submit]').click();await p.waitForFunction(()=>document.getElementById('license-message').textContent.includes('Chưa xác nhận'));check(await p.locator('#license-allocate button[type=submit]').isDisabled(),'Uncertain save blocks retry until reload');}finally{await manager.context.close();}
 const support=await scenario('support_technician');try{check(await support.p.locator('#license-live').isHidden(),'Support license controls denied');}finally{await support.context.close();}
 check(errors.length===0,'No JavaScript runtime errors');return {checks:checks.length,results:checks,mode:'browser API mock, no DB'};
}
