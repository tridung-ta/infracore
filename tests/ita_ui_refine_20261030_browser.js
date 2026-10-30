async(page)=>{
 const c=await page.context().browser().newContext({viewport:{width:1366,height:1000}}),p=await c.newPage();let checks=0,posts=0,records=0;const errors=[];
 const ck=(ok,label)=>{if(!ok)throw Error(label);checks++;};
 p.on('pageerror',e=>errors.push(e.message));p.on('request',r=>{if(r.method()==='POST'&&r.url().includes('admin.php'))posts++;if(new URL(r.url()).searchParams.get('act')==='assignments')records++;});
 try{
  await c.request.post('http://127.0.0.1:18767/review/control',{headers:{'X-Local-Review':'mock-only'},data:{reset:true}});
  await p.goto('http://127.0.0.1:18767/#assignments');await p.locator('#handover-browse').waitFor();ck(records===0,'No record automatically opened');
  await p.locator('#handover-browse').click();await p.waitForFunction(()=>document.getElementById('handover-asset').options.length>1);
  ck(await p.locator('#handover-search').inputValue()==='','Can browse without entering text');ck((await p.locator('#handover-asset option').allTextContents()).some(n=>n.includes('TEST-LOCAL')&&n.includes(' · ')),'Picker shows code and name');ck(records===0,'Browsing does not open first record');
  await p.locator('#handover-assets-next').click();await p.waitForFunction(()=>document.getElementById('handover-assets-page').textContent.includes('2/'));ck(records===0,'Paging list never opens a record');await p.locator('#handover-assets-previous').click();await p.waitForFunction(()=>document.getElementById('handover-assets-page').textContent.includes('1/'));ck(await p.locator('#handover-asset').inputValue()==='','Paging preserves explicit selection requirement');
  await p.locator('#handover-asset').selectOption({index:1});await p.locator('#handover-record').waitFor();ck(records===1,'Selecting machine opens exactly its record');ck(posts===0,'Read-only device selection');
  const icons=await p.locator('.main-nav a use').evaluateAll(nodes=>nodes.map(n=>n.getAttribute('href')));ck(new Set(icons).size===icons.length,'All main navigation icons distinct');
  ck(await p.locator('.main-nav a use').evaluateAll(nodes=>nodes.every(n=>document.querySelector(n.getAttribute('href')))),'All icon symbols exist');
  await p.evaluate(()=>location.hash='#users');await p.locator('#user-create-open').click();await p.locator('#access-save').click();
  ck(await p.locator('#access-message .account-feedback-content strong').textContent()==='Cần kiểm tra','Error has clear heading');ck(await p.locator('#access-message').getAttribute('role')==='alert','Error is accessible');
  await p.locator('#access-message .account-feedback-close').click();ck(await p.locator('#access-message').isHidden(),'Notification can be closed');await p.locator('#access-save').click();ck(await p.locator('#access-message').isVisible(),'New error reappears after dismissal');
  for(const width of [1366,768,390]){await p.setViewportSize({width,height:1000});ck(await p.locator('#access-message').evaluate(n=>n.getBoundingClientRect().right<=innerWidth+1),'Feedback fits '+width);}
  await p.setViewportSize({width:1366,height:1000});await p.locator('[data-motion-toggle]').click();await p.waitForFunction(()=>document.body.dataset.uiTheme==='focus');
  ck(await p.locator('#access-message').evaluate(n=>getComputedStyle(n).backgroundColor!==getComputedStyle(document.body).backgroundColor),'Focus feedback separates from background');
  ck(await p.locator('#users-panel').evaluate(n=>getComputedStyle(n).boxShadow!=='none'),'Focus panel has subtle depth');ck(await p.locator('#user-search').evaluate(n=>getComputedStyle(n).backgroundImage!=='none'||getComputedStyle(n).backgroundColor==='rgb(255, 255, 255)'),'Focus input readable surface');
  await p.locator('#access-message').screenshot({path:'.playwright-cli/ui-20261030-feedback-1366.png'});await p.setViewportSize({width:390,height:1000});await p.locator('#access-message').screenshot({path:'.playwright-cli/ui-20261030-feedback-390.png'});
  ck(posts===0,'Presentation controls never write');ck(errors.length===0,'No browser runtime errors');return `${checks} UI refinement checks passed; mock transport, no DB/host.`;
 }finally{await c.close();}
}
