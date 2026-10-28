async(page)=>{
 const context=await page.context().browser().newContext({viewport:{width:1366,height:1000}}),p=await context.newPage(),checks=[],errors=[];let posts=0;
 const check=(ok,label)=>{if(!ok)throw Error(label);checks.push(label);};p.on('pageerror',e=>errors.push(e.message));p.on('request',r=>{if(r.method()==='POST'&&r.url().includes('act=user-create'))posts++;});
 async function grant(key){const box=p.locator('#permission-live-editor input[value="'+key+'"]'),group=box.locator('xpath=ancestor::details');if(await group.getAttribute('open')===null)await group.locator('summary').click();await box.check();}
 try{
  await context.request.post('http://127.0.0.1:18767/review/control',{headers:{'X-Local-Review':'mock-only'},data:{reset:true}});
  await p.goto('http://127.0.0.1:18767/#users');await p.locator('#user-create-open').waitFor({state:'visible'});await p.locator('#user-create-open').click();
  check(await p.locator('#user-create-fields').isVisible(),'New account fields open');
  check(await p.locator('#access-role').isHidden(),'No new role needed');
  check(await p.locator('#permission-live-editor input:checked').count()===0,'No default grants');
  await p.locator('#user-create-username').fill('mock-created');await p.locator('#user-create-fullname').fill('MOCK <người mới>');await p.locator('#user-create-email').fill('mock-created@example.invalid');
  await p.locator('#user-create-password').fill('MOCK-password-123');await p.locator('#user-create-confirmation').fill('mismatch-password');await p.locator('#access-save').click();
  check(posts===0&&(await p.locator('#access-message').textContent()).includes('trùng khớp'),'Password mismatch stops before POST');
  await p.locator('#user-create-password').fill('éééééééé');await p.locator('#user-create-confirmation').fill('éééééééé');check(await p.locator('#user-create-password').evaluate(n=>n.checkValidity()),'Unicode minimum uses bytes, no HTML character minimum');
  await grant('asset.read');await grant('asset.create');await p.locator('#scope-options input[value="10"]').check();
  await p.locator('#access-save').click();await p.locator('#confirm-dialog').waitFor({state:'visible'});await p.locator('#confirm-cancel').click();check(posts===0,'Cancel creation sends no POST');
  for(const width of [1366,768,390]){await p.setViewportSize({width,height:1000});check(await p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1),'Creation form bounds '+width);await p.screenshot({path:'.playwright-cli/account-create-'+width+'.png',fullPage:true});}
  await p.locator('#access-save').click();await p.locator('#confirm-accept').click();await p.locator('#admin-message').filter({hasText:'Đã tạo tài khoản mock-created'}).waitFor();
  check(posts===1,'Confirm creates once');check(await p.locator('#user-create-password').inputValue()===''&&await p.locator('#user-create-confirmation').inputValue()==='','Password fields cleared after POST');
  const data=await context.request.get('http://127.0.0.1:18767/admin.php?op=ita&act=users').then(r=>r.json()),user=data.data.rows.find(u=>u.username==='mock-created');
  check(user&&user.department_ids.length===1&&user.department_ids[0]===10,'Selected department stored in mock');
  check(user.permissions.length===2&&user.permissions.includes('asset.create'),'Only requested grants stored in mock');check(!JSON.stringify(user).includes('MOCK-password'),'No credential in listing DTO');
  check((await p.locator('#user-rows').textContent()).includes('MOCK <người mới>'),'Fullname markup displayed as text');
  await p.getByRole('button',{name:'Phân quyền mock-created',exact:true}).click();check(await p.locator('#user-create-fields').isHidden(),'Edit existing account hides credentials');check(await p.locator('#permission-live-editor input:checked').count()===2,'Existing permission editor still works');
  await p.locator('#access-cancel').click();await p.locator('#user-create-open').click();for(const [key,value]of Object.entries({username:'mock-created',fullname:'MOCK duplicate',email:'mock-created@example.invalid',password:'MOCK-password-123',confirmation:'MOCK-password-123'}))await p.locator('#user-create-'+key).fill(value);
  await grant('asset.read');await p.locator('#scope-options input[value="10"]').check();await p.locator('#access-save').click();await p.locator('#confirm-accept').click();await p.locator('#access-message').filter({hasText:'đã tồn tại'}).waitFor();
  check(await p.locator('#access-save').isEnabled(),'Duplicate409 permits correcting inputs');check(await p.locator('#user-create-password').inputValue()==='','Failed POST also clears password');
  await p.locator('#user-create-username').fill('mock-uncertain');await p.locator('#user-create-email').fill('mock-uncertain@example.invalid');await p.locator('#user-create-password').fill('MOCK-password-123');await p.locator('#user-create-confirmation').fill('MOCK-password-123');
  await p.route('**/admin.php?op=ita&act=user-create',route=>route.fulfill({status:503,contentType:'application/json',body:JSON.stringify({success:false,message:'MOCK provisioning unavailable'})}));await p.locator('#access-save').click();await p.locator('#confirm-accept').click();await p.locator('#access-message').filter({hasText:'MOCK provisioning unavailable'}).waitFor();check(await p.locator('#access-save').isDisabled(),'Uncertain503 prevents blind resubmission');await p.unroute('**/admin.php?op=ita&act=user-create');
  await context.request.post('http://127.0.0.1:18767/review/control',{headers:{'X-Local-Review':'mock-only'},data:{role:'system_manager'}});await p.reload();check(await p.locator('#user-create-open').isHidden(),'Nonmanager cannot open create');
  const denied=await context.request.post('http://127.0.0.1:18767/admin.php?op=ita&act=user-create',{headers:{'X-CSRF-Token':'local-review-only'},data:{}});check(denied.status()===403,'Direct mock POST forbidden without access.manage');
  check(errors.length===0,'No browser runtime errors');return {checks:checks.length,results:checks,scope:'Node RAM mock, not native account creation/DB/host'};
 }finally{await context.request.post('http://127.0.0.1:18767/review/control',{headers:{'X-Local-Review':'mock-only'},data:{reset:true}});await context.close();}
}
