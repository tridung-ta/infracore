async(page)=>{
 const context=await page.context().browser().newContext(),p=await context.newPage(),errors=[],requests=[],checks=[];
 p.on('pageerror',e=>errors.push(e.message));p.on('request',r=>{if(r.method()!=='GET')requests.push(r.url());});
 const check=(ok,label)=>{if(!ok)throw new Error(label);checks.push(label);};
 try{
  await p.goto('http://127.0.0.1:18766/#imports');await p.locator('#imports-panel').waitFor({state:'visible'});
  check(await p.locator('#import-guide').isVisible(),'Guide opens by hash');
  await p.getByRole('tab',{name:'2. Chọn file & xem trước'}).click();
  await p.locator('#import-file').setInputFiles({name:'qa.xlsx',mimeType:'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',buffer:Buffer.from('not-real-excel')});
  check((await p.locator('#import-file-status').textContent()).includes('Chưa upload'),'Selected file marked not uploaded');
  await p.getByRole('tab',{name:'1. Chuẩn bị file'}).click();await p.getByRole('tab',{name:'2. Chọn file & xem trước'}).click();
  check(await p.locator('#import-file').evaluate(n=>n.files[0]?.name)==='qa.xlsx','Selected file retained across tabs');
  for(const scenario of ['valid','errors','empty','failure']){
   await p.locator('#import-scenario').selectOption(scenario);await p.locator('#import-demo').click();
   const text=await p.locator('#import-summary').textContent();check(scenario==='valid'?text.includes('12 hợp lệ, 0 lỗi'):scenario==='errors'?text.includes('8 hợp lệ, 4 lỗi'):scenario==='empty'?text.includes('Không có dữ liệu'):text.includes('Không tải được'),`Scenario ${scenario}`);
  }
  await p.locator('#import-scenario').selectOption('valid');await p.locator('#import-demo').click();await p.locator('#import-next').click();check((await p.locator('#import-page-summary').textContent()).includes('6–10'),'Preview pagination');
  await p.locator('#import-file').setInputFiles({name:'bad.php',mimeType:'text/plain',buffer:Buffer.from('qa')});check(await p.locator('#import-file').evaluate(n=>n.files.length)===0,'Wrong extension cleared');
  for(const width of [1366,768,390]){await p.setViewportSize({width,height:900});check(await p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),`No page overflow ${width}`);}
  await p.getByRole('tab',{name:'3. Xuất dữ liệu'}).click();check(await p.getByRole('button',{name:'Xuất Excel — chưa kết nối',exact:true}).isDisabled(),'Real export disabled');
  await p.getByRole('tab',{name:'3. Xuất dữ liệu'}).press('Home');check(await p.locator('#import-guide').isVisible(),'Keyboard tab Home');
  check(requests.length===0,'No upload or write request');check(errors.length===0,'No JS runtime error');
  await p.screenshot({path:'.playwright-cli/phase04-guide-390.png',fullPage:true});return {result:'PASS',checks};
 }finally{await context.close();}
}
