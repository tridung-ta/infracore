async(page)=>{
 const checks=[],check=(ok,label)=>{if(!ok)throw new Error(label);checks.push(label);};
 for(const reduced of ['no-preference','reduce']){
  const context=await page.context().browser().newContext({viewport:{width:390,height:900},reducedMotion:reduced}),p=await context.newPage();let posts=0;
  await context.addInitScript(()=>{window.motionCalls=[];const original=Element.prototype.animate;Element.prototype.animate=function(frames,options){motionCalls.push({target:this.className,keys:Object.keys(frames[0])});return original.call(this,frames,options);};});
  await context.route('**/admin.php?*',route=>{posts++;return route.abort();});
  try{
   await p.goto('http://127.0.0.1:18766/assets/ita/mock/login-preview.html');await p.waitForFunction(()=>window.ItaMotion);
   check(await p.evaluate(()=>motionCalls.length>0)===(reduced==='no-preference'),`${reduced} login motion policy`);
   await p.locator('#username').fill('QA');await p.locator('#password').fill('mock-only');await p.locator('#password-toggle').click();
   check(await p.locator('#password').getAttribute('type')==='text',`${reduced} password toggle still works`);
   check(await p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),`${reduced} mobile bounds`);
   if(reduced==='no-preference'){await p.locator('[data-motion-toggle]').click();check(await p.evaluate(()=>document.querySelector('.login-card').getAnimations().length===0),'Login effect cancellable');}
   check(posts===0,'No authentication request from mock');
  }finally{await context.close();}
 }
 return {checks:checks.length,results:checks,scope:'login MOCK presentation only; no authentication/DB'};
}
