async(page)=>{
 const context=await page.context().browser().newContext({viewport:{width:1366,height:900}}),p=await context.newPage(),errors=[];let checks=0,posts=0;
 const assert=(value,message)=>{if(!value)throw Error(message);checks++;};
 const headers={'X-Local-Review':'mock-only'};p.on('pageerror',e=>errors.push(e.message));p.on('request',r=>{if(r.method()==='POST'&&r.url().includes('act=access'))posts++;});
 try{
  await context.request.post('http://127.0.0.1:18767/review/control',{headers,data:{reset:true}});
  await p.goto('http://127.0.0.1:18767/#users');await p.getByRole('button',{name:'Xóa quyền ITA của MOCK-C',exact:true}).waitFor();
  await p.getByRole('button',{name:'Xóa quyền ITA của MOCK-C',exact:true}).click();await p.locator('#confirm-dialog').waitFor({state:'visible'});
  assert((await p.locator('#confirm-message').textContent()).includes('hệ thống cũ không bị khóa'),'Confirmation states ITA-only scope');
  await p.locator('#confirm-cancel').click();assert(posts===0,'Cancel sends no write');
  await p.getByRole('button',{name:'Xóa quyền ITA của MOCK-C',exact:true}).click();await p.locator('#confirm-accept').click();
  await p.locator('#admin-message').filter({hasText:'Đã xóa quyền truy cập InfraCore của MOCK-C'}).waitFor();
  const state=await context.request.get('http://127.0.0.1:18767/admin.php?op=ita&act=users').then(r=>r.json());const user=state.data.rows.find(u=>u.username==='MOCK-C');
  assert(posts===1&&Number(user.enabled)===0&&user.status===1,'Exactly one ITA revoke; native account remains active');
  assert(user.permissions.includes('asset.read')&&user.department_ids.includes(10),'Permissions and department retained for recovery');
  assert(await p.getByRole('button',{name:'Xóa quyền ITA của MOCK-C',exact:true}).count()===0,'Already revoked account not offered duplicate revoke');
  await p.getByRole('button',{name:'Xóa quyền ITA của MOCK-admin',exact:true}).click();await p.locator('#confirm-accept').click();
  await p.locator('#admin-message').filter({hasText:'quản lý quyền cuối'}).waitFor();assert(posts===2,'Last manager refusal returned');
  await context.request.post('http://127.0.0.1:18767/review/control',{headers,data:{role:'system_manager'}});await p.reload();
  assert(await p.getByRole('button',{name:/Xóa quyền ITA/}).count()===0,'Nonmanager cannot see revoke controls');
  const denied=await context.request.post('http://127.0.0.1:18767/admin.php?op=ita&act=access-revoke',{headers:{'X-CSRF-Token':'local-review-only'},data:{user_id:112,version:'forged'}});
  assert(denied.status()===403,'Direct unauthorized revoke denied by mock server');assert(errors.length===0,'No JS exceptions');
  return `${checks} ITA access revoke browser checks passed; Node RAM mock, no DB.`;
 }finally{await context.request.post('http://127.0.0.1:18767/review/control',{headers,data:{reset:true}});await context.close();}
}
