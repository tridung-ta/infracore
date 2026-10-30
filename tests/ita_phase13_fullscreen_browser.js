async(page)=>{
 const checks=[],check=(ok,label)=>{if(!ok)throw new Error(label);checks.push(label);};
 const c=await page.context().browser().newContext({viewport:{width:1366,height:1000}}),p=await c.newPage(),errors=[];let escapeExited=false;
 p.on('pageerror',e=>errors.push(e.message));
 await c.route('**/assets/ita/mock/network.json',async route=>{const response=await route.fetch(),data=await response.json();data.nodes[0].name='Máy 1 <img src=x onerror=alert(1)>';return route.fulfill({json:data});});
 try{
  await p.goto('http://127.0.0.1:18766/#network');check(await p.locator('#network-fullscreen').isDisabled(),'Fullscreen disabled in2D');
  await p.locator('#network-show-3d').click();const canvas=p.locator('#network-3d canvas');await canvas.waitFor({state:'visible'});
  check(await p.locator('.network-3d-name').count()===8,'One label per3D block');
  check((await p.locator('.network-3d-name').first().textContent()).includes('<img')&&await p.locator('.network-3d-labels img').count()===0,'Labels use escaped text, notHTML');
  check(await p.locator('.network-3d-labels').evaluate(n=>getComputedStyle(n).pointerEvents)==='none','Labels do not block picking/pointer');
  check(await p.locator('#network-fullscreen-controls').isHidden(),'Fullscreen toolbar hidden in normal3D');
  const pos=await p.locator('.network-3d-name').first().getAttribute('style');await p.locator('#network-rotate-right').click();check(await p.locator('.network-3d-name').first().getAttribute('style')!==pos,'Labels track model rotation');
  await p.locator('#network-fullscreen').click();await p.waitForFunction(()=>document.fullscreenElement===document.getElementById('network-3d'));
  await p.locator('#network-fullscreen-exit').waitFor({state:'visible'});check(true,'Exit button inside actual fullscreen');
  check(await p.locator('#network-fullscreen').getAttribute('aria-pressed')==='true','Fullscreen accessibility state');
  await p.emulateMedia({reducedMotion:'reduce'});await p.waitForTimeout(50);const beforeRotate=await canvas.screenshot();await p.locator('#network-fullscreen-right').focus();await p.keyboard.press('Enter');const afterRotate=await canvas.screenshot();check(!beforeRotate.equals(afterRotate),'Fullscreen keyboard button actually rotates with reduced motion');await p.locator('#network-fullscreen-left').focus();await p.keyboard.press('Enter');check(await p.locator('#network-fullscreen-left').isVisible(),'Keyboard rotation controls remain available with reduced motion in fullscreen');
  const selected=await p.locator('#network-fullscreen-device option').nth(2).getAttribute('value');await p.locator('#network-fullscreen-device').selectOption(selected);check(await p.locator('#network-node-list [aria-pressed=true]').getAttribute('data-network-node')===selected,'Fullscreen accessible device selection updates selected block');
  await p.waitForFunction(()=>{const c=document.querySelector('#network-3d canvas');return Math.abs(c.clientWidth-innerWidth)<3&&Math.abs(c.clientHeight-innerHeight)<3;});check(true,'Canvas resizes to fullscreen viewport');
  await p.screenshot({path:'.playwright-cli/phase13-fullscreen-1366.png'});
  await p.locator('#network-fullscreen-exit').click();await p.waitForFunction(()=>!document.fullscreenElement&&document.activeElement===document.getElementById('network-fullscreen'));check(await p.locator('#network-fullscreen').evaluate(n=>n===document.activeElement),'Exit restores button focus');
  await p.locator('#network-fullscreen').click();await p.waitForFunction(()=>document.fullscreenElement);await p.keyboard.press('Escape');
  await p.waitForFunction(()=>!document.fullscreenElement);escapeExited=true;
  await p.waitForFunction(()=>!document.fullscreenElement);check(await p.locator('#network-fullscreen').getAttribute('aria-pressed')==='false','Browser/API exit synchronizes controls');
  for(const width of [768,390]){await p.setViewportSize({width,height:1000});await p.evaluate(()=>new Promise(r=>requestAnimationFrame(()=>requestAnimationFrame(r))));check(await p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1),'Responsive bounds '+width);check(await p.locator('.network-3d-labels').evaluate(layer=>{const a=[...layer.children].filter(n=>!n.hidden).map(n=>n.getBoundingClientRect());return a.every((r,i)=>a.slice(i+1).every(s=>r.right<=s.left||s.right<=r.left||r.bottom<=s.top||s.bottom<=r.top));}),'Fixture labels do not overlap '+width);}
  await p.screenshot({path:'.playwright-cli/phase13-labels-390.png',fullPage:true});
  await p.locator('#network-department').selectOption('Văn phòng');await p.locator('#network-filters button[type=submit]').click();check(await p.locator('.network-3d-labels').count()===1,'Filter rebuild disposes old label layer');
  await p.locator('#network-fullscreen').click();await p.waitForFunction(()=>document.fullscreenElement);await p.evaluate(()=>{location.hash='assets';});await p.waitForFunction(()=>!document.fullscreenElement&&document.querySelectorAll('.network-3d-labels').length===0);check(true,'Leaving page exits fullscreen and removes labels');
  await p.evaluate(()=>{location.hash='network';});await p.locator('#network-show-3d').click();await canvas.waitFor({state:'visible'});
  await p.evaluate(()=>{document.getElementById('network-3d').requestFullscreen=()=>Promise.reject(new Error('QA denied'));});await p.locator('#network-fullscreen').click();await p.waitForFunction(()=>document.getElementById('network-3d-status').textContent.includes('chưa cho phép'));
  check(await canvas.isVisible()&&!await p.evaluate(()=>!!document.fullscreenElement),'Rejected fullscreen retains working3D');
  await p.locator('#network-show-2d').click();check(await p.locator('.network-3d-labels').count()===0,'2D removes all labels');
  check(errors.length===0,'No uncaught fullscreen/label error');
 }finally{await c.close();}
 return {checks:checks.length,results:checks,escapeExited,scope:'real browser Fullscreen API/WebGL, MOCK topology; no DB/host'};
}
