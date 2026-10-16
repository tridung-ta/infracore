async (page) => {
 const base='http://127.0.0.1:18771/admin.php?op=ita';
 const context=await page.context().browser().newContext({viewport:{width:390,height:844}});
 const p=await context.newPage(),errors=[],checks=[];
 p.on('pageerror',e=>errors.push(e.message));
 const check=(ok,label)=>{if(!ok)throw new Error(label);checks.push(label);};
 const visible=locator=>locator.evaluate(el=>{const b=el.getBoundingClientRect();return b.top>=0&&b.bottom<=innerHeight;});
 async function confirm(){await p.locator('#access-save').click();await p.locator('#confirm-accept').click();}
 async function edit(){await p.getByRole('button',{name:'Phân quyền unassigned',exact:true}).click();await p.locator('#access-form').waitFor({state:'visible'});}
 try{
  await p.goto(base+'&act=login');await p.locator('#username').fill('admin');await p.locator('#password').fill('Local-QA-only-2026!');await p.locator('.login-submit').click();
  await p.waitForFunction(()=>window.itaLiveContext,{}, {timeout:10000});await p.goto(base+'#users');await edit();
  await p.locator('#access-role').selectOption('support_technician');await p.locator('#access-enabled').check();
  for(const box of await p.locator('#scope-options input').all())await box.uncheck();
  await confirm();await p.locator('#access-message[role=alert]').waitFor();
  check(await visible(p.locator('#access-message'))&&!await p.locator('#access-form').isHidden(),'Validation error visible beside form; editor stays open');
  for(const width of [390,768,1366]){
   await p.setViewportSize({width,height:844});await p.locator('#scope-options input').first().check();
   const request=p.waitForRequest(r=>r.method()==='POST'&&new URL(r.url()).searchParams.get('act')==='access');
   await confirm();const payload=(await request).postDataJSON();
   await p.waitForFunction(()=>document.getElementById('access-form').hidden,{}, {timeout:10000});
   await p.waitForFunction(()=>document.getElementById('admin-message').textContent.includes('Đã cập nhật quyền truy cập cho unassigned'),{}, {timeout:10000});
   check(payload.department_ids.length===1&&Number(payload.department_ids[0])===1001,`UI submits selected department at ${width}`);
   check(await visible(p.locator('#admin-message')),`Success visible in viewport at ${width}`);
   if(width===390)await p.screenshot({path:'.playwright-cli/access-save-success-390.png'});
   await edit();check(await p.locator('#scope-options input:checked').count()===1,`Saved department read back at ${width}`);
  }
  await p.route('**/admin.php?*',route=>new URL(route.request().url()).searchParams.get('act')==='access'&&route.request().method()==='POST'?route.abort():route.continue());
  await confirm();await p.waitForFunction(()=>document.getElementById('access-message').textContent.includes('Chưa xác nhận được'),{}, {timeout:10000});
  check(await visible(p.locator('#access-message'))&&await p.locator('#access-save').isEnabled(),'Network ambiguity visible; save button restored');
  await p.unroute('**/admin.php?*');
  await p.route('**/admin.php?*',route=>new URL(route.request().url()).searchParams.get('act')==='users'?route.fulfill({status:503,contentType:'application/json',body:JSON.stringify({success:false,message:'QA list reload failed'})}):route.continue());
  await confirm();await p.waitForFunction(()=>document.getElementById('admin-message').textContent.includes('Chưa tải lại được danh sách'),{}, {timeout:10000});
  check(await visible(p.locator('#admin-message')),'Confirmed save distinguishes list refresh failure');
  await p.unroute('**/admin.php?*');
  check(errors.length===0,'No JavaScript runtime error');
  return {result:'PASS',checks};
 }catch(error){
  const diagnostic=await p.evaluate(()=>({url:location.href,message:document.getElementById('admin-message')?.textContent,feedback:document.getElementById('access-message')?.textContent,usersHidden:document.getElementById('users-panel')?.hidden}));
  throw new Error(JSON.stringify({error:error.message,checks,errors,diagnostic}));
 }finally{await context.close();}
}
