async(page)=>{
 const context=await page.context().browser().newContext({viewport:{width:1366,height:1000}}),p=await context.newPage();let checks=0,posts=0;const errors=[];
 const ck=(ok,label)=>{if(!ok)throw Error(label);checks++;};
 p.on('pageerror',e=>errors.push(e.message));p.on('request',r=>{if(r.method()==='POST'&&r.url().includes('admin.php'))posts++;});
 try{
  await context.request.post('http://127.0.0.1:18767/review/control',{headers:{'X-Local-Review':'mock-only'},data:{reset:true}});
  await p.goto('http://127.0.0.1:18767/#users');await p.locator('#user-create-open').click();await p.locator('#access-save').click();
  ck((await p.locator('#access-message').textContent()).includes('Tên đăng nhập: chưa nhập'),'Required field names missing input');ck(posts===0,'Invalid form never submits');
  await p.locator('#user-create-username').fill('bad name');await p.locator('#access-save').click();ck((await p.locator('#access-message').textContent()).includes('bắt đầu bằng chữ/số'),'Username format explains repair');
  await p.locator('#access-cancel').click();ck((await p.locator('#admin-message').textContent()).includes('Chưa tạo'),'Cancel create explains no write');
  await p.getByRole('button',{name:'Sửa thông tin MOCK-C',exact:true}).click();await p.locator('#user-profile-email').fill('wrong');await p.locator('#user-profile-save').click();ck((await p.locator('#user-profile-message').textContent()).includes('Email: sai định dạng'),'Profile email explains format');
  await p.locator('#user-profile-cancel').click();ck((await p.locator('#admin-message').textContent()).includes('Chưa gửi'),'Cancel profile explains no write');
  await p.getByRole('button',{name:'Đặt lại mật khẩu MOCK-C',exact:true}).click();await p.locator('#user-password-save').click();ck((await p.locator('#user-profile-message').textContent()).includes('Mật khẩu mới: chưa nhập'),'Reset required password named');
  await p.locator('#user-password-new').fill('abcdef');await p.locator('#user-password-confirm').fill('different');await p.locator('#user-password-actor').fill('MOCK-ONLY');await p.locator('#user-password-save').click();ck((await p.locator('#user-profile-message').textContent()).includes('chưa khớp'),'Mismatch explains repair');ck(posts===0,'All validation failures prevent writes');
  await p.locator('#user-password-confirm').fill('abcdef');await p.locator('#user-password-save').click();await p.locator('#confirm-cancel').click();ck((await p.locator('#user-profile-message').textContent()).includes('Đã hủy xác nhận'),'Reset confirmation cancellation announced');
  for(const width of [1366,768,390]){await p.setViewportSize({width,height:1000});ck(await p.locator('#user-profile-message').evaluate(n=>n.getBoundingClientRect().right<=innerWidth+1),'Feedback fits '+width);}
  ck(errors.length===0,'No browser exceptions');return `${checks} account feedback checks passed; mock only, no DB.`;
 }finally{await context.close();}
}
