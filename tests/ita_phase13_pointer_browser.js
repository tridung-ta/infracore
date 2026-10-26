async(page)=>{
 const browser=page.context().browser(),checks=[],check=(ok,label)=>{if(!ok)throw new Error(label);checks.push(label);};
 for(const reduce of [false,true]){
  const c=await browser.newContext({viewport:{width:1366,height:1000},reducedMotion:reduce?'reduce':'no-preference'}),p=await c.newPage(),errors=[];
  p.on('pageerror',e=>errors.push(e.message));
  try{
   await p.goto('http://127.0.0.1:18766/#network');
   await p.evaluate(async()=>{
    const T=await import('/assets/ita/vendor/three/0.180.0/three.module.min.js'),original=T.Scene.prototype.add;let group=null;
    Object.defineProperty(window,'__pose',{get:()=>group?{x:group.rotation.x,y:group.rotation.y}:null});
    T.Scene.prototype.add=function(...objects){group=objects.find(n=>n.isGroup)||group;window.__testGroup=group;return original.apply(this,objects);};
    const cast=T.Raycaster.prototype.setFromCamera;T.Raycaster.prototype.setFromCamera=function(pointer,camera){window.__testCamera=camera;return cast.call(this,pointer,camera);};
    window.__renders=0;for(const name of ['drawElements','drawArrays']){const draw=WebGL2RenderingContext.prototype[name];WebGL2RenderingContext.prototype[name]=function(...args){window.__renders++;return draw.apply(this,args);};}
   });
   await p.locator('#network-show-3d').click();const canvas=p.locator('#network-3d canvas');await canvas.waitFor({state:'visible'});await p.waitForFunction(()=>window.__pose);await canvas.scrollIntoViewIfNeeded();
   const pose=()=>p.evaluate(()=>window.__pose),box=await canvas.boundingBox(),move=async(x,y)=>{await p.mouse.move(box.x+box.width*x,box.y+box.height*y);await p.evaluate(()=>new Promise(requestAnimationFrame));};
   await p.mouse.move(1,1);await move(.5,.5);const base=await pose();
   await move(.1,.5);const left=await pose();await move(.9,.5);const right=await pose();
   await move(.5,.1);const top=await pose();await move(.5,.9);const bottom=await pose();
   if(reduce){check(Math.abs(left.y-base.y)<.001&&Math.abs(right.y-base.y)<.001&&Math.abs(top.x-base.x)<.001&&Math.abs(bottom.x-base.x)<.001,'Reduced motion disables hover rotation');}
   else{check(left.y<base.y&&right.y>base.y,'Pointer left/right controls yaw');check(top.x<base.x&&bottom.x>base.x,'Pointer up/down controls pitch');check(Math.abs(bottom.x)<=.4&&Math.abs(right.y)<=.65,'Hover angles bounded');}
   await p.mouse.move(1,1);check(Math.abs((await pose()).x)<.001&&Math.abs((await pose()).y)<.001,'Leaving canvas resets hover offset');
   await p.locator('#network-rotate-right').click();const button=await pose();check(button.y>.19,'Existing right button still rotates');
   await move(.8,.8);await p.mouse.move(1,1);check(Math.abs((await pose()).y-button.y)<.001,'Mouse leave preserves button base angle');
   await p.locator('#network-rotate-left').click();check(Math.abs((await pose()).y)<.001,'Existing left button still rotates');
   if(!reduce){
    await canvas.evaluate(n=>{for(let i=0;i<25;i++)n.dispatchEvent(new PointerEvent('pointermove',{pointerType:'mouse',clientX:100,clientY:100}));});
    await p.evaluate(()=>new Promise(requestAnimationFrame));const renders=await p.evaluate(()=>window.__renders);await p.waitForTimeout(120);
    check(await p.evaluate(()=>window.__renders)===renders,'No continuous rendering while pointer is still');
    await canvas.evaluate(n=>n.dispatchEvent(new PointerEvent('pointerleave')));const before=await pose();
    await canvas.evaluate(n=>n.dispatchEvent(new PointerEvent('pointermove',{pointerType:'touch',clientX:100,clientY:100})));await p.evaluate(()=>new Promise(requestAnimationFrame));
    check(Math.abs((await pose()).x-before.x)<.001,'Touch movement does not steal scroll for hover');
    const first=await canvas.screenshot();await move(.85,.2);const second=await canvas.screenshot();check(!first.equals(second),'Real WebGL frame changes with pointer');
    await p.screenshot({path:'.playwright-cli/phase13-pointer-1366.png',fullPage:true});
    await p.emulateMedia({reducedMotion:'reduce'});await p.waitForFunction(()=>matchMedia('(prefers-reduced-motion: reduce)').matches&&Math.abs(window.__pose.x)<.001&&Math.abs(window.__pose.y)<.001);const frozen=await pose();await move(.1,.9);check(Math.abs((await pose()).x-frozen.x)<.001&&Math.abs((await pose()).y-frozen.y)<.001,'Changing reduced-motion setting disables hover');
   }
   await p.locator('#network-node-list button').nth(2).click();check((await p.locator('#network-detail-title').textContent()).includes('Switch trung tâm'),'List selection preserved with pointer control');
   const selected=await canvas.evaluate(n=>{
    const r=n.getBoundingClientRect();n.dispatchEvent(new PointerEvent('pointerdown',{clientX:r.left+r.width/2,clientY:r.top+r.height/2}));
    const mesh=window.__testGroup.children.find(x=>x.isMesh),v=mesh.position.clone();mesh.localToWorld(v.set(0,0,0));v.project(window.__testCamera);
    n.dispatchEvent(new PointerEvent('pointerdown',{clientX:r.left+(v.x+1)*r.width/2,clientY:r.top+(1-v.y)*r.height/2}));return mesh.userData.id;
   });
   check(await p.locator('#network-node-list [aria-pressed="true"]').getAttribute('data-network-node')===String(selected),'Raycast click on 3D block still selects device');
   await canvas.evaluate(n=>{n.dispatchEvent(new PointerEvent('pointermove',{pointerType:'mouse',clientX:100,clientY:100}));document.getElementById('network-show-2d').click();});
   await p.waitForTimeout(80);check(await canvas.count()===0,'2D disposes scene with pending pointer frame');
   check(errors.length===0,'No uncaught pointer/disposal error');
  }finally{await c.close();}
 }
 return {checks:checks.length,results:checks,scope:'real WebGL, MOCK topology, no PHP/DB/host'};
}
