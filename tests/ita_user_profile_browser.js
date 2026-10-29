async(page)=>{
 const context=await page.context().browser().newContext({viewport:{width:1366,height:1000}}),p=await context.newPage(),errors=[];let checks=0,profilePosts=0,passwordPosts=0;
 const assert=(ok,label)=>{if(!ok)throw Error(label);checks++;},headers={'X-Local-Review':'mock-only'};
 p.on('pageerror',e=>errors.push(e.message));p.on('request',r=>{if(r.method()==='POST'){if(r.url().includes('act=user-profile-save'))profilePosts++;if(r.url().includes('act=user-password-reset'))passwordPosts++;}});
 async function newPassword(actor='MOCK-ONLY'){await p.locator('#user-password-new').fill('mock12');await p.locator('#user-password-confirm').fill('mock12');await p.locator('#user-password-actor').fill(actor);}
 try{
  await context.request.post('http://127.0.0.1:18767/review/control',{headers,data:{reset:true}});
  const legacy=await context.request.get('http://127.0.0.1:18767/admin.php?op=ita&act=user-profile&id=222');assert(legacy.status()===403,'Mapped account without creation provenance is protected');
  await p.goto('http://127.0.0.1:18767/#users');await p.getByRole('button',{name:'Sửa thông tin MOCK-C',exact:true}).click();
  await p.locator('#user-profile-form').waitFor({state:'visible'});assert((await p.locator('#user-profile-panel').textContent()).includes('dùng chung'),'Shared-account effect explained');
  await p.locator('#user-profile-fullname').fill('MOCK Changed <img src=x>');await p.locator('#user-profile-save').click();await p.locator('#confirm-cancel').click();assert(profilePosts===0,'Cancel profile edit never writes');
  await p.locator('#user-profile-save').click();await p.locator('#confirm-accept').click();await p.locator('#admin-message').filter({hasText:'Đã cập nhật thông tin'}).waitFor();
  assert(profilePosts===1&&(await p.locator('#user-rows').textContent()).includes('MOCK Changed <img src=x>'),'Profile change read back as text');assert(await p.locator('#user-rows img').count()===0,'Untrusted name not executed');
  await p.getByRole('button',{name:'Đặt lại mật khẩu MOCK-C',exact:true}).click();await p.locator('#user-password-form').waitFor({state:'visible'});
  assert((await p.locator('#user-password-form').textContent()).includes('MD5'),'Legacy sign-in impact explained');
  for(const width of [1366,768,390]){await p.setViewportSize({width,height:1000});assert(await p.locator('#user-profile-panel').evaluate(n=>n.getBoundingClientRect().right<=innerWidth+1),'Password form fits '+width);}
  await newPassword();await p.locator('#user-password-save').click();await p.locator('#confirm-cancel').click();assert(passwordPosts===0,'Cancel reset never writes');
  assert(await p.locator('#user-password-new').inputValue()===''&&await p.locator('#user-password-actor').inputValue()==='','Cancel clears password fields');
  await newPassword('wrong');await p.locator('#user-password-save').click();await p.locator('#confirm-accept').click();await p.locator('#user-profile-message').filter({hasText:'không đúng'}).waitFor();
  assert(new URL(p.url()).hash==='#users','Wrong admin confirmation does not redirect to login');assert(await p.locator('#user-password-new').inputValue()===''&&await p.locator('#user-password-actor').inputValue()==='','Failed reset clears passwords');
  await newPassword();await p.locator('#user-password-save').click();await p.locator('#confirm-accept').click();await p.locator('#admin-message').filter({hasText:'Đã đặt lại mật khẩu'}).waitFor();
  assert(passwordPosts===2,'Only explicit confirmations submit reset');
  const users=await context.request.get('http://127.0.0.1:18767/admin.php?op=ita&act=users').then(r=>r.json());assert(!JSON.stringify(users).includes('mock12')&&!JSON.stringify(users).includes('MOCK-ONLY'),'Mock listing retains no plaintext passwords');
  await p.getByRole('button',{name:'Đặt lại mật khẩu MOCK-C',exact:true}).click();await newPassword();
  await p.locator('#user-password-save').click();await p.evaluate(()=>{location.hash='#assets';});await p.waitForFunction(()=>location.hash==='#assets');await p.locator('#confirm-accept').click();
  assert(passwordPosts===2,'Leaving users while confirmation waits prevents reset POST');assert(await p.locator('#user-password-new').inputValue()==='','Navigation clears secret fields');
  await p.goto('http://127.0.0.1:18767/#users');await p.getByRole('button',{name:'Đặt lại mật khẩu MOCK-C',exact:true}).click();await newPassword();
  await p.route('**/admin.php?op=ita&act=user-password-reset',route=>route.fulfill({status:503,contentType:'application/json',body:JSON.stringify({success:false,message:'MOCK uncertain reset'})}));
  await p.locator('#user-password-save').click();await p.locator('#confirm-accept').click();await p.locator('#user-profile-message').filter({hasText:'Kết quả chưa rõ'}).waitFor();assert(await p.locator('#user-password-save').isDisabled(),'Unknown reset result prevents blind retry');
  await context.request.post('http://127.0.0.1:18767/review/control',{headers,data:{role:'system_manager'}});await p.reload();assert(await p.getByRole('button',{name:/Đặt lại mật khẩu MOCK-C/}).count()===0,'No reset control without management permission');
  assert(errors.length===0,'No JS exceptions');return `${checks} profile/reset browser checks passed; Node RAM mock, no native credentials or DB.`;
 }finally{await context.request.post('http://127.0.0.1:18767/review/control',{headers,data:{reset:true}});await context.close();}
}
