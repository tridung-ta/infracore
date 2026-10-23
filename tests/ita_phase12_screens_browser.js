async(page)=>{
 const context=await page.context().browser().newContext({viewport:{width:1366,height:1000},reducedMotion:'no-preference'}),p=await context.newPage(),checks=[],errors=[];
 const check=(ok,label)=>{if(!ok)throw new Error(label);checks.push(label);};
 const cases=[['alerts','#alert-list'],['tickets','#ticket-feedback'],['overview','#kpi-grid']];
 await context.addInitScript(()=>{window.motionCalls=[];const original=Element.prototype.animate;Element.prototype.animate=function(frames,options){motionCalls.push({id:this.id,keys:Object.keys(frames[0]),duration:options.duration});return original.call(this,frames,options);};});
 p.on('pageerror',e=>errors.push(e.message));
 try{
  await p.goto('http://127.0.0.1:18766/');await p.waitForFunction(()=>window.ItaMotion);
  for(const [view,selector] of cases){
   await p.evaluate(view=>{location.hash=view;ItaMotion.setEnabled(true);},view);
   await p.waitForFunction(()=>location.hash.length>1);
   const id=selector.slice(1);
   await p.evaluate(selector=>{const n=document.querySelector(selector);let a=n;while(a){if(a.hidden)a.hidden=false;a=a.parentElement;}if(n instanceof HTMLDialogElement&&!n.open)n.showModal();motionCalls.length=0;n.append(document.createComment('motion probe'));},selector);
   await p.waitForFunction(id=>motionCalls.some(c=>c.id===id),id);
   check(await p.evaluate(id=>motionCalls.some(c=>c.id===id&&c.duration===180&&c.keys.join(',')==='opacity,transform'),id),`${view} real target short opacity/transform feedback`);
   await p.evaluate(selector=>{ItaMotion.setEnabled(false);motionCalls.length=0;document.querySelector(selector).append(document.createComment('disabled probe'));},selector);
   await p.evaluate(()=>new Promise(resolve=>requestAnimationFrame(()=>requestAnimationFrame(resolve))));
   check(await p.evaluate(()=>motionCalls.length===0),`${view} disabled prevents effect`);
   check(await p.locator(selector).evaluate(n=>n.getAnimations().length===0),`${view} disabling cancels animation`);
   await p.evaluate(()=>document.querySelectorAll('dialog[open]').forEach(n=>n.close()));
  }
  check(errors.length===0,'No runtime errors');return {checks:checks.length,results:checks,scope:'DOM feedback probes on static templates, no DB'};
 }finally{await context.close();}
}
