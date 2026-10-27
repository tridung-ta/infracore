async(page)=>{
 const checks=[],check=(ok,label)=>{if(!ok)throw new Error(label);checks.push(label);};
 const c=await page.context().browser().newContext({viewport:{width:1366,height:1000}}),p=await c.newPage(),errors=[];let posts=0,reads=0;
 p.on('pageerror',e=>errors.push(e.message));p.on('request',r=>{if(r.method()==='POST')posts++;if(r.url().includes('/mock/')&&r.url().includes('.json'))reads++;});
 await c.route('**/*',route=>new URL(route.request().url()).hostname==='127.0.0.1'?route.continue():route.abort());
 await c.addInitScript(()=>{window.__motionCount=0;const original=Element.prototype.animate;Element.prototype.animate=function(...args){window.__motionCount++;return original.apply(this,args);};});
 try{
  await p.goto('http://127.0.0.1:18766/assets/ita/mock/login-preview.html');await p.waitForFunction(()=>window.ItaMotion);
  await p.locator('#username').fill('TEST-LOCAL-ONLY');await p.locator('#password').fill('fake-preview-only');
  check(await p.locator('#login-form').getAttribute('method')==='post','Login method unchanged');
  check(await p.locator('#login-form').getAttribute('action')==='/admin.php?op=ita&act=login','Login action unchanged');
  check(await p.locator('[name=csrf_token]').inputValue()==='mock-csrf-no-session','CSRF input retained in generated preview');
  for(const theme of ['command','focus']){
   await p.evaluate(theme=>ItaMotion.setEnabled(theme==='command'),theme);
   check(await p.locator('body').getAttribute('data-ui-theme')===theme,theme+' selected');
   check(await p.locator('#username').inputValue()==='TEST-LOCAL-ONLY'&&await p.locator('#password').inputValue()==='fake-preview-only',theme+' preserves input');
   for(const width of [1366,768,390]){
    await p.setViewportSize({width,height:1000});
    check(await p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1),theme+' login no page overflow '+width);
    check(await p.locator('#login-form input:not([type=hidden])').evaluateAll(nodes=>nodes.every(n=>{const r=n.getBoundingClientRect();return r.width>100&&r.left>=0&&r.right<=innerWidth;})),theme+' login actual input bounds '+width);
    await p.screenshot({path:'.playwright-cli/login-'+theme+'-'+width+'.png',fullPage:true});
   }
   check(await p.locator('.login-topology').isVisible()===false,'Mobile hides decorative diagram '+theme);
  }
  await p.locator('#password-toggle').click();check(await p.locator('#password').getAttribute('type')==='text','Show password works');await p.locator('#password-toggle').click();check(await p.locator('#password').getAttribute('type')==='password','Hide password works');
  await p.locator('.login-submit').click();await p.locator('#mock-login-result').waitFor();check(posts===0,'Preview submit never sends credentials');check(!await p.locator('.login-submit').isDisabled(),'Preview submit does not strand disabled button');
  await p.setViewportSize({width:1366,height:1000});await p.goto('http://127.0.0.1:18766/?devices=1#network');await p.waitForFunction(()=>window.ItaMotion);
  check(await p.locator('body').getAttribute('data-ui-theme')==='focus','Theme persists across login and shell');
  await p.locator('#network-show-3d').click();await p.locator('#network-3d canvas').waitFor();await p.locator('#network-search').fill('UNSAVED FILTER');
  for(const theme of ['command','focus']){
   const before=reads;await p.evaluate(theme=>ItaMotion.setEnabled(theme==='command'),theme);await p.locator('#network-3d canvas').waitFor();
   check(await p.locator('#network-search').inputValue()==='UNSAVED FILTER',theme+' preserves unsent filter');check(reads===before,theme+' does not fetch data');check(await p.locator('#network-3d canvas').count()===1,theme+' one rebuilt WebGL canvas');
   for(const width of [1366,768,390]){await p.setViewportSize({width,height:1000});await p.waitForTimeout(70);check(await p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1),theme+' shell bounds '+width);}
   await p.setViewportSize({width:1366,height:1000});await p.screenshot({path:'.playwright-cli/shell-'+theme+'-1366.png',fullPage:true});
  }
  await p.emulateMedia({reducedMotion:'reduce'});await p.waitForFunction(()=>!ItaMotion.enabled());await p.evaluate(()=>window.__motionCount=0);await p.locator('[data-motion-toggle]').click();check(await p.evaluate(()=>!ItaMotion.enabled()&&window.__motionCount===0),'Reduced motion allows theme change without animation');check(await p.locator('[data-motion-toggle]').isEnabled(),'Theme switch remains available with reduced motion');
  check(posts===0,'No writes during theme tests');check(errors.length===0,'No page runtime errors');
 }finally{await c.close();}
 return {checks:checks.length,results:checks,scope:'Static MOCK/local WebGL, no authentication or DB/host'};
}
