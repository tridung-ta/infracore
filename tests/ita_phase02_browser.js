async (page) => {
 const base='http://127.0.0.1:18769';
 const url=act=>`${base}/admin.php?op=ita${act?'&act='+act:''}`;
 const browser=page.context().browser();const contexts=[];const results=[];const runtimeErrors=[];
 const check=(condition,label)=>{if(!condition)throw new Error(label);results.push(label);};
 async function account(identity){
  const context=await browser.newContext({viewport:{width:1366,height:1000}});contexts.push(context);
  const p=await context.newPage();p.on('pageerror',error=>runtimeErrors.push(error.message));await p.goto(url('login'));await p.locator('#username').fill(identity);await p.locator('#password').fill('Local-QA-only-2026!');
  await p.locator('.login-submit').click();await p.locator('body[data-mode=live]').waitFor();await p.locator('#results-status').filter({hasText:'phạm vi'}).waitFor();
  return {context,p,csrf:await p.locator('meta[name=ita-csrf]').getAttribute('content')};
 }
 async function get(context,act,query=''){return context.request.get(url(act)+query);}
 try{
  const anonymous=await browser.newContext();contexts.push(anonymous);
  check((await get(anonymous,'data')).status()===401,'Anonymous data denied');
  check((await anonymous.request.get(url(''))).url().includes('act=login'),'Anonymous shell redirects to login');
  const admin=await account('admin');
  const adminData=await (await get(admin.context,'data')).json();
  check(adminData.data.summary.total===13&&adminData.data.assets.length===5,'Admin real DB aggregate and server pagination');
  check((await admin.p.locator('[data-view=users]').isVisible()),'Admin user navigation');
  const cookie=(await admin.context.cookies()).find(c=>c.name==='ITA_SESSION');
  check(cookie.httpOnly&&cookie.sameSite==='Lax','Session HttpOnly and SameSite');
  const manager=await account('manager');const support=await account('support');
  for(const actor of [manager,support]){
   const data=await (await get(actor.context,'data','&store_id=2')).json();
   check(data.data.summary.total===6&&data.data.departments.length===1,'Role data is scoped and ignores forged store');
   check((await get(actor.context,'users')).status()===403,'Non-admin user listing denied');
   check((await get(actor.context,'audit')).status()===403,'Non-admin audit listing denied');
   check(!(await actor.p.locator('[data-view=users]').isVisible()),'Non-admin admin nav hidden');
  }
  check((await get(manager.context,'asset','&id=1002')).status()===403,'Manager cross-department URL denied 403');
  check((await get(support.context,'asset','&id=1001')).status()===403,'Support cross-department URL denied 403');
  check((await get(manager.context,'asset','&id=1099')).status()===403,'Unassigned inventory denied to scoped role');
  check((await get(admin.context,'asset','&id=2001')).status()===404,'Cross-store asset hidden even from store admin');
  check((await get(manager.context,'asset','&id=1001')).status()===200,'In-scope direct asset allowed');
  check((await get(admin.context,'data','&department_id[]=1')).status()===422,'Array filter rejected');
  check((await get(admin.context,'data','&q=%25')).status()===200,'Literal wildcard search accepted safely');
  const mutation={user_id:9004,role:'system_manager',enabled:true,department_ids:[1002]};
  const save=(actor,body,csrf=actor.csrf)=>actor.context.request.post(url('access'),{headers:{'X-CSRF-Token':csrf},data:body});
  check((await save(admin,mutation,'invalid')).status()===403,'Role mutation requires CSRF');
  check((await save(support,mutation)).status()===403,'Support cannot grant roles');
  check((await save(admin,{...mutation,department_ids:[2001]})).status()===422,'Cross-store scope assignment rejected');
  check((await save(admin,{...mutation,user_id:9001,role:'support_technician'})).status()===409,'Last active admin protected despite inactive admin mapping');
  check((await save(admin,mutation)).status()===200,'Grant role to existing account succeeds');
  const granted=await account('unassigned');
  check((await (await get(granted.context,'data')).json()).data.summary.total===6,'New role and scopes are effective');
  check((await save(admin,{...mutation,user_id:9002})).status()===200,'Change existing scope succeeds');
  check((await get(manager.context,'data')).status()===401,'Old session invalidated after permission change');
  const audit=await (await get(admin.context,'audit','&action=user.access_update')).json();
  check(audit.data.total===2&&audit.data.rows.every(r=>r.user_id===9001||Number(r.user_id)===9001),'Role updates have filtered actor audit');
  check(!JSON.stringify(audit).includes('Local-QA-only'),'No password in audit response');
  await admin.p.locator('[data-view=users]').click();await admin.p.locator('#user-rows tr').first().waitFor();
  await admin.p.getByRole('button',{name:'Phân quyền unassigned',exact:true}).click();await admin.p.locator('#access-form').waitFor({state:'visible'});
  check(await admin.p.locator('#scope-options input:checked').count()===1,'User access editor shows persisted scope');
  await admin.p.locator('[data-view=audit]').click();await admin.p.locator('#audit-rows tr').first().waitFor();
  check(await admin.p.locator('#audit-rows tr').count()>0,'Audit screen renders real records');
  for(const width of [1366,768,390]){
   await support.p.setViewportSize({width,height:1000});
   check(await support.p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),`Live responsive ${width}`);
   await support.p.screenshot({path:`.playwright-cli/phase02-live-${width}.png`,fullPage:true});
  }
  check((await admin.context.request.get(url('logout'))).status()===405,'Logout cannot use GET');
  check((await admin.context.request.post(url('logout'),{form:{csrf_token:'invalid'}})).status()===403,'Logout CSRF required');
  await admin.context.request.post(url('logout'),{form:{csrf_token:admin.csrf}});
  check((await get(admin.context,'data')).status()===401,'Logout revokes session');
  const denied=await browser.newContext();contexts.push(denied);const p=await denied.newPage();await p.goto(url('login'));
  const token=await p.locator('input[name=csrf_token]').inputValue();
  check((await denied.request.post(url('login'),{form:{username:'admin',password:'wrong',csrf_token:'bad'}})).status()===403,'Login CSRF required');
  check((await denied.request.post(url('login'),{form:{username:'inactive',password:'Local-QA-only-2026!',csrf_token:token}})).status()===401,'Inactive account denied');
  for(let i=0;i<5;i++)check((await denied.request.post(url('login'),{form:{username:'unknown-qa',password:'wrong',csrf_token:token}})).status()===401,`Invalid attempt ${i+1} denied`);
  check((await denied.request.post(url('login'),{form:{username:'unknown-qa',password:'wrong',csrf_token:token}})).status()===429,'Shared login rate limit enforced');
  await page.goto(url('login'));await page.setViewportSize({width:390,height:900});await page.screenshot({path:'.playwright-cli/phase02-login.png'});
  check(runtimeErrors.length===0,'No JavaScript runtime errors on live role pages');
  return {result:'PASS',checks:results.length,results};
 }finally{for(const context of contexts)await context.close();}
}
