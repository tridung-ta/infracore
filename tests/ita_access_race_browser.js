async(page)=>{
 const context=await page.context().browser().newContext(),p=await context.newPage(),errors=[];let checks=0,releaseRead,releaseWrite,readStarted,writeStarted;
 const assert=(value,message)=>{if(!value)throw Error(message);checks++;};p.on('pageerror',e=>errors.push(e.message));
 const headers={'X-Local-Review':'mock-only'};
 try{
  await context.request.post('http://127.0.0.1:18767/review/control',{headers,data:{reset:true}});
  await p.goto('http://127.0.0.1:18767/#users');await p.locator('#user-create-open').waitFor({state:'visible'});
  await p.locator('#user-create-open').click();
  await p.locator('#user-create-fullname').evaluate(n=>{n.setCustomValidity('Lỗi giả còn sót');n.setAttribute('aria-invalid','true');});
  await p.locator('#access-cancel').click();await p.locator('#user-create-open').click();
  assert(await p.locator('#user-create-fullname').evaluate(n=>!n.validity.customError&&!n.hasAttribute('aria-invalid')),'Reopening create clears stale validation');
  await p.locator('#access-cancel').click();
  const started=new Promise(resolve=>readStarted=resolve),gate=new Promise(resolve=>releaseRead=resolve);
  await p.route('**/admin.php?**',async route=>{
   const u=new URL(route.request().url());
   if(u.searchParams.get('act')==='access'&&u.searchParams.get('id')==='112'&&route.request().method()==='GET'){readStarted();await gate;}
   await route.continue();
  });
  await p.getByRole('button',{name:'Phân quyền MOCK-C',exact:true}).click();await started;
  await p.getByRole('button',{name:'Phân quyền MOCK-tech',exact:true}).click();
  await p.locator('#access-title').filter({hasText:'MOCK-tech'}).waitFor();releaseRead();await p.waitForTimeout(250);
  assert((await p.locator('#access-title').textContent()).includes('MOCK-tech'),'Slow response cannot replace later selection');
  await p.unroute('**/admin.php?**');
  let releaseCreateRead,createReadStarted;const createGate=new Promise(resolve=>releaseCreateRead=resolve),createReady=new Promise(resolve=>createReadStarted=resolve);
  await p.route('**/admin.php?**',async route=>{
   const u=new URL(route.request().url());if(u.searchParams.get('act')==='access'&&u.searchParams.get('id')==='112'&&route.request().method()==='GET'){createReadStarted();await createGate;}
   await route.continue();
  });
  await p.getByRole('button',{name:'Phân quyền MOCK-C',exact:true}).click();await createReady;
  await p.locator('#user-create-open').click();releaseCreateRead();await p.waitForTimeout(250);
  assert((await p.locator('#access-title').textContent()).includes('Thêm tài khoản')&&await p.locator('#user-create-fields').isVisible(),'Slow edit response cannot replace new-account form');
  await p.unroute('**/admin.php?**');await p.locator('#access-cancel').click();await p.getByRole('button',{name:'Phân quyền MOCK-tech',exact:true}).click();await p.locator('#access-title').filter({hasText:'MOCK-tech'}).waitFor();
  const writeGate=new Promise(resolve=>releaseWrite=resolve),writeReady=new Promise(resolve=>writeStarted=resolve);
  await p.route('**/admin.php?**',async route=>{
   const u=new URL(route.request().url());if(u.searchParams.get('act')==='access'&&route.request().method()==='POST'){writeStarted();await writeGate;}
   await route.continue();
  });
  await p.locator('#access-save').click();await p.locator('#confirm-accept').click();await writeReady;
  await p.getByRole('button',{name:'Phân quyền MOCK-C',exact:true}).click();
  assert((await p.locator('#access-title').textContent()).includes('MOCK-tech'),'Another editor cannot open during save');
  releaseWrite();await p.locator('#admin-message').filter({hasText:'MOCK-tech'}).waitFor();
  assert((await p.locator('#admin-message').textContent()).includes('MOCK-tech'),'Success identifies account actually saved');
  assert(errors.length===0,'No JS exceptions');return `${checks} access race checks passed; Node RAM mock, no DB.`;
 }finally{releaseRead?.();releaseWrite?.();await context.request.post('http://127.0.0.1:18767/review/control',{headers,data:{reset:true}});await context.close();}
}
