async(page)=>{
 const context=await page.context().browser().newContext(),p=await context.newPage(),checks=[],errors=[],writes=[];
 const check=(ok,label)=>{if(!ok)throw new Error(label);checks.push(label);};
 p.on('pageerror',error=>errors.push(error.message));p.on('request',request=>{if(request.method()!=='GET')writes.push(request.url());});
 try{
  await p.goto('http://127.0.0.1:18766/#assignments');await p.locator('#assignment-workspace').waitFor({state:'visible'});
  check((await p.locator('#assignment-message').textContent()).includes('MOCK'),'Mock clearly labeled');
  await p.locator('#assignment-preview').click();check(await p.locator('#assignment-draft').isVisible(),'Eligible device opens draft');
  await p.locator('#assignment-recipient').selectOption('department');check(await p.locator('#assignment-draft').isHidden(),'Recipient change invalidates draft');
  await p.locator('#assignment-preview').click();check((await p.locator('#assignment-draft-content').textContent()).includes('Bộ phận IT minh họa'),'Department draft text');
  await p.locator('#assignment-device').selectOption('demo-assigned');check(await p.locator('#assignment-preview').isDisabled(),'Already assigned blocked');
  check((await p.locator('#assignment-details').textContent()).includes('Quá hạn bảo trì'),'Overdue maintenance clearly displayed');
  check((await p.locator('#assignment-history').textContent()).includes('MOCK-BG-002'),'Assignment history rendered');
  await p.locator('#assignment-device').selectOption('demo-retired');check(await p.locator('#assignment-preview').isDisabled(),'Retired blocked');
  check((await p.locator('#assignment-history').textContent()).includes('Chưa có lịch sử'),'Empty history');
  await p.locator('#assignment-scenario').selectOption('empty');check(await p.locator('#assignment-workspace').isHidden(),'Empty state');
  await p.locator('#assignment-scenario').selectOption('failure');check(await p.locator('#assignment-retry').isVisible(),'Error retry visible');
  await p.locator('#assignment-retry').click();await p.locator('#assignment-workspace').waitFor({state:'visible'});check(await p.locator('#assignment-scenario').inputValue()==='normal','Retry resets mock');
  for(const width of [1366,768,390]){
   await p.setViewportSize({width,height:900});check(await p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),`No overflow ${width}`);
   check(await p.locator('#assignment-device').evaluate(n=>{const r=n.getBoundingClientRect();return r.left>=0&&r.right<=innerWidth;}),`Selector fits ${width}`);
   if(width!==768)await p.screenshot({path:`.playwright-cli/phase05-preview-${width}.png`,fullPage:true});
  }
  check(await p.getByRole('button',{name:'Thu hồi thiết bị — chờ kết nối'}).isDisabled(),'Real return disabled');
  check(await p.getByRole('button',{name:'Xác nhận đã nhận — chờ kết nối'}).isDisabled(),'Real acknowledge disabled');
  check(writes.length===0,'No write or upload request');check(errors.length===0,'No runtime JS error');
  return {result:'PASS: presentation only, no backend/DB',checks};
 }finally{await context.close();}
}
