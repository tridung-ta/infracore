async(page)=>{
 const context=await page.context().browser().newContext({viewport:{width:1366,height:900}}),p=await context.newPage();let checks=0;const errors=[];
 const check=(ok,message)=>{if(!ok)throw Error(message);checks++;};p.on('pageerror',e=>errors.push(e.message));
 try{
  await p.goto('http://127.0.0.1:18767/#users');await p.locator('#user-create-open').waitFor({state:'visible'});
  await p.locator('#user-search').fill('MOCK-C');await p.locator('#user-filters button[type=submit]').click();await p.waitForFunction(()=>document.querySelector('#user-page-summary').textContent.includes('1 bản ghi'));
  check(await p.locator('#user-rows tr').count()===1,'Search filters complete mock set');
  check((await p.locator('#user-rows').textContent()).includes('MOCK-C'),'Correct account');
  await p.locator('#user-status').selectOption('locked');await p.locator('#user-filters button[type=submit]').click();await p.waitForFunction(()=>document.querySelector('#user-page-summary').textContent.includes('0 bản ghi'));
  check((await p.locator('#user-rows').textContent()).includes('Không có tài khoản phù hợp'),'Empty state offers recovery');
  await p.locator('#user-filter-reset').click();await p.waitForFunction(()=>document.querySelector('#user-page-summary').textContent.includes('3 bản ghi'));
  check(await p.locator('#user-search').inputValue()==='','Reset clears search');
  await p.locator('#user-create-open').click();await p.locator('#user-create-email').fill('bad');await p.locator('#access-save').click();
  check((await p.locator('#user-create-username-error').textContent()).includes('Tên đăng nhập'),'Required error names field');
  for(const width of [1366,768,390]){await p.setViewportSize({width,height:900});check(await p.locator('#user-filters').evaluate(n=>n.getBoundingClientRect().right<=innerWidth+1),'Filter fits viewport '+width);}
  await p.evaluate(()=>{const n=document.getElementById('admin-message');n.textContent='Thông báo thử';window.ItaUi.autoDismiss(n);});
  check(await p.locator('#admin-message').isVisible(),'Success visible before timeout');
  await p.waitForTimeout(4200);check(await p.locator('#admin-message').isHidden(),'Success closes after four seconds');
  await p.evaluate(()=>{const n=document.getElementById('admin-message');n.textContent='Lỗi thử';window.ItaUi.autoDismiss(n,true);});
  await p.waitForTimeout(4200);check(await p.locator('#admin-message').isVisible(),'Error persists for correction');
  check(errors.length===0,'No JS exceptions');return checks+' account filter browser checks passed; Node RAM, no DB.';
 }finally{await context.close();}
}
