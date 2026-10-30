async(page)=>{
 const checks=[],check=(ok,label)=>{if(!ok)throw new Error(label);checks.push(label);};
 for(const mode of ['normal','reduced','disabled']){
  const context=await page.context().browser().newContext({viewport:{width:1366,height:1000},reducedMotion:mode==='reduced'?'reduce':'no-preference'}),p=await context.newPage(),errors=[];
  await context.addInitScript(mode=>{if(mode==='disabled')window.UI_MOTION_ENABLED=false;window.motionCalls=[];const original=Element.prototype.animate;Element.prototype.animate=function(frames,options){window.motionCalls.push({keys:Object.keys(frames[0]),duration:options.duration,id:this.id});return original.call(this,frames,options);};},mode);
  p.on('pageerror',e=>errors.push(e.message));
  try{
   await p.goto('http://127.0.0.1:18766/');await p.waitForFunction(()=>window.ItaMotion);
   check(await p.evaluate(()=>ItaMotion.enabled())===(mode==='normal'),`${mode} enabled policy`);
   await p.evaluate(()=>{const n=document.createElement('button');n.id='motion-probe';n.textContent='Probe';document.getElementById('main').append(n);ItaMotion.register('#motion-probe');});
   if(mode==='normal'){
    check(await p.evaluate(()=>motionCalls.some(c=>c.id==='motion-probe'&&c.keys.join(',')==='opacity,transform'&&c.duration===180)),'Only transform/opacity short effect');
    await p.locator('[data-motion-toggle]').click();check(await p.evaluate(()=>!ItaMotion.enabled()&&document.getElementById('motion-probe').getAnimations().length===0),'Toggle cancels active animations');
    await p.reload();await p.waitForFunction(()=>window.ItaMotion);check(await p.evaluate(()=>!ItaMotion.enabled()),'Preference persists locally');
    await p.locator('[data-motion-toggle]').click();await p.emulateMedia({reducedMotion:'reduce'});await p.waitForFunction(()=>!ItaMotion.enabled());check(await p.locator('[data-motion-toggle]').isEnabled()&&await p.evaluate(()=>!ItaMotion.enabled()),'OS reduced motion wins; theme control remains usable');
    await p.emulateMedia({reducedMotion:'no-preference'});await p.waitForFunction(()=>ItaMotion.enabled());
   }else{check(await p.locator('[data-motion-toggle]').isEnabled()&&await p.evaluate(()=>motionCalls.length===0&&!ItaMotion.enabled()),`${mode} no animation; theme switch usable`);}
   for(const width of [1366,768,390]){await p.setViewportSize({width,height:1000});check(await p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),`${mode} bounds ${width}`);}
   check(errors.length===0,`${mode} no runtime errors`);
  }finally{await context.close();}
 }
 return {checks:checks.length,results:checks,scope:'motion infrastructure, static local, no DB'};
}
