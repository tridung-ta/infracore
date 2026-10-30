async(page)=>{
 const checks=[],check=(ok,label)=>{if(!ok)throw new Error(label);checks.push(label);};
 for(const reduce of [false,true]){
  const c=await page.context().browser().newContext({viewport:{width:1366,height:1600},reducedMotion:reduce?'reduce':'no-preference'}),p=await c.newPage(),errors=[];p.on('pageerror',e=>errors.push(e.message));
  try{
   await p.goto('http://127.0.0.1:18766/#network');await p.evaluate(async()=>{
    const T=await import('/assets/ita/vendor/three/0.180.0/three.module.min.js'),add=T.Scene.prototype.add;let group;
    T.Scene.prototype.add=function(...objects){group=objects.find(n=>n.isGroup)||group;return add.apply(this,objects);};
    Object.defineProperty(window,'__pose',{get:()=>group?{x:group.rotation.x,y:group.rotation.y}:null});window.__selections=0;addEventListener('ita:network-select',()=>window.__selections++);
   });
   await p.locator('#network-show-3d').click();const canvas=p.locator('#network-3d canvas');await canvas.waitFor();await p.waitForFunction(()=>window.__pose);await canvas.scrollIntoViewIfNeeded();
   await p.locator('#network-node-list button').nth(2).click();const selections=await p.evaluate(()=>window.__selections);
   async function drag(dx,dy){const b=await canvas.boundingBox(),x=b.x+Math.min(80,b.width/4),y=b.y+20;await p.mouse.move(x,y);await p.mouse.down();check(await canvas.evaluate(n=>n.hasPointerCapture(1)),'Mouse drag captures pointer');const before=await p.evaluate(()=>window.__pose);if(dy===600&&!reduce){await p.mouse.move(x,y+300,{steps:8});await p.screenshot({path:'.playwright-cli/phase13-orbit-underside.png',fullPage:true});}await p.mouse.move(x+dx,y+dy,{steps:15});await p.mouse.up();await p.evaluate(()=>new Promise(requestAnimationFrame));return {before,after:await p.evaluate(()=>window.__pose)};}
   let r=await drag(600,0);check(r.after.y-r.before.y>Math.PI*2,'Horizontal drag exceeds full360 degrees');
   r=await drag(0,600);check(r.after.x-r.before.x>Math.PI*2,'Vertical drag exceeds full360 degrees beyond canvas edge');
   r=await drag(0,-600);check(r.after.x-r.before.x< -Math.PI*2,'Vertical reverse rotation unrestricted');
   check(await p.evaluate(()=>window.__selections)===selections,'Drag does not accidentally select a block');
   await p.mouse.move(1,1);const stable=await p.evaluate(()=>window.__pose);await p.waitForTimeout(80);check(Math.abs((await p.evaluate(()=>window.__pose)).x-stable.x)<.001,'Released drag keeps its pitch without inertia');
   await p.locator('#network-rotate-right').click();check((await p.evaluate(()=>window.__pose)).y>stable.y,'Existing button adds rotation after orbit');
   const b=await canvas.boundingBox();await p.mouse.move(b.x+50,b.y+50);await p.mouse.down();await canvas.evaluate(n=>n.dispatchEvent(new PointerEvent('pointercancel',{pointerId:1,pointerType:'mouse'})));await p.mouse.up();const cancelPose=await p.evaluate(()=>window.__pose);await p.mouse.move(1,1);check(Math.abs((await p.evaluate(()=>window.__pose)).x-cancelPose.x)<.5,'Cancelled drag releases state');
   await p.locator('#network-fullscreen').click();await p.locator('#network-fullscreen-exit').waitFor({state:'visible'});r=await drag(500,0);check(r.after.y-r.before.y>5,'Free rotation also works in fullscreen');
   await p.locator('#network-fullscreen-exit').click();const b2=await canvas.boundingBox();await p.mouse.move(b2.x+50,b2.y+50);await p.mouse.down();await p.mouse.move(b2.x+150,b2.y+80);await p.evaluate(()=>document.getElementById('network-show-2d').click());await p.mouse.up();check(await canvas.count()===0,'Disposing during captured drag removes scene');check(errors.length===0,'No orbit/capture errors');
  }finally{await c.close();}
 }
 return {checks:checks.length,results:checks,scope:'real mouse/WebGL/fullscreen on MOCK; no PHP/DB/host'};
}
