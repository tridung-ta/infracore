async(page)=>{
 const checks=[],check=(ok,label)=>{if(!ok)throw new Error(label);checks.push(label);};
 for(const mode of ['normal','reduced','disabled','unsupported']){
  const c=await page.context().browser().newContext({reducedMotion:mode==='reduced'?'reduce':'no-preference'}),p=await c.newPage();
  if(mode==='disabled')await c.addInitScript(()=>window.UI_MOTION_ENABLED=false);
  if(mode==='unsupported')await c.addInitScript(()=>Element.prototype.animate=undefined);
  try{
   await p.goto('http://127.0.0.1:18766/#network');await p.locator('#network-content').waitFor({state:'visible'});
   if(mode==='normal'){
    await p.locator('#network-flow-play').click();check(await p.locator('#network-flow-layer circle').count()===6,'Only non-offline edges get mock flow');
    check(await p.locator('#network-flow-play').isDisabled(),'No concurrent replay');
    await p.locator('#network-flow-stop').click();check(await p.locator('#network-flow-layer circle').count()===0,'Stop removes transient dots');
    await p.locator('#network-flow-play').click();await p.evaluate(()=>ItaMotion.setEnabled(false));check(await p.locator('#network-flow-layer circle').count()===0&&await p.locator('#network-flow-play').isDisabled(),'Motion toggle cancels network flow');
    await p.evaluate(()=>ItaMotion.setEnabled(true));await p.locator('#network-flow-play').click();await p.waitForFunction(()=>document.querySelectorAll('#network-flow-layer circle').length===0);check(await p.locator('#network-flow-stop').isDisabled(),'One-shot completes without loop');
    await p.locator('#network-flow-play').click();await p.evaluate(()=>location.hash='assets');await p.waitForFunction(()=>document.querySelectorAll('#network-flow-layer circle').length===0);check(true,'Leaving view stops flow');
   }else check(await p.locator('#network-flow-play').isDisabled(),`${mode} policy prevents flow`);
  }finally{await c.close();}
 }
 return {checks:checks.length,results:checks,scope:'finite MOCK animation only; no network traffic/DB'};
}
