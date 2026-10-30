async(page)=>{
 const checks=[],check=(ok,label)=>{if(!ok)throw new Error(label);checks.push(label);};
 for(const mode of ['normal','importfail','renderfail','webglfail','latefail']){
  const fail=mode==='importfail';
  const c=await page.context().browser().newContext({viewport:{width:1366,height:1000}}),p=await c.newPage(),errors=[];let loads=0;
  p.on('pageerror',e=>errors.push(e.message));
  if(mode==='renderfail')await c.addInitScript(()=>{WebGL2RenderingContext.prototype.clear=function(){throw new Error('QA render failure');};});
  if(mode==='webglfail')await c.addInitScript(()=>{const original=HTMLCanvasElement.prototype.getContext;HTMLCanvasElement.prototype.getContext=function(type,...args){return type.startsWith('webgl')?null:original.call(this,type,...args);};});
  await c.route('**/assets/ita/vendor/three/**',route=>{loads++;return fail?route.abort():route.continue();});
  try{
   await p.goto('http://127.0.0.1:18766/#network');await p.locator('#network-content').waitFor({state:'visible'});check(loads===0,'Three not loaded with 2D');
   await p.locator('#network-show-3d').click();
   if(!['normal','latefail'].includes(mode)){await p.waitForFunction(()=>document.getElementById('network-3d-status').textContent.includes('chưa khả dụng'));check(await p.locator('#network-graph').isVisible()&&await p.locator('#network-3d canvas').count()===0,`${mode} falls back with zero stale canvas`);check(errors.length===0,`${mode} caught without runtime error`);continue;}
   if(mode==='latefail'){await p.locator('#network-3d canvas').waitFor({state:'visible'});await p.evaluate(()=>{WebGL2RenderingContext.prototype.clear=function(){throw new Error('QA late render failure');};});await p.locator('#network-rotate-right').click();check(await p.locator('#network-graph').isVisible()&&await p.locator('#network-3d canvas').count()===0,'Late render failure disposes to 2D');check(errors.length===0,'Late render failure has no uncaught error');continue;}
   await p.locator('#network-3d canvas').waitFor({state:'visible'});check(loads>=2&&await p.locator('#network-graph').isHidden(),'Local Three lazy modules create scene');
   await p.locator('#network-rotate-right').click();await p.locator('#network-node-list button').nth(2).click();check((await p.locator('#network-detail-title').textContent()).includes('Switch trung tâm'),'Accessible list selects while 3D');
   for(const width of [1366,768,390]){await p.setViewportSize({width,height:1000});check(await p.locator('#network-3d canvas').evaluate(n=>{const r=n.getBoundingClientRect();return r.width>0&&r.right<=innerWidth&&document.documentElement.scrollWidth<=innerWidth;}),`3D canvas bounds ${width}`);}
   await p.screenshot({path:'.playwright-cli/phase13-3d-390.png',fullPage:true});
   await p.locator('#network-department').selectOption('Văn phòng');await p.locator('#network-filters button[type=submit]').click();check(await p.locator('#network-3d canvas').count()===1,'Filter rebuild replaces prior scene');
   await p.locator('#network-show-2d').click();check(await p.locator('#network-3d canvas').count()===0&&await p.locator('#network-graph').isVisible(),'2D disposes canvas');
   await p.locator('#network-show-3d').click();await p.locator('#network-3d canvas').waitFor({state:'visible'});
   await p.locator('#network-3d canvas').evaluate(n=>n.dispatchEvent(new Event('webglcontextlost',{cancelable:true})));check(await p.locator('#network-graph').isVisible()&&await p.locator('#network-3d canvas').count()===0,'Context loss falls back without stale canvas');
   await p.locator('#network-show-3d').click();await p.locator('#network-3d canvas').waitFor({state:'visible'});await p.evaluate(()=>location.hash='assets');await p.waitForFunction(()=>document.querySelectorAll('#network-3d canvas').length===0);check(true,'Leaving page disposes scene');
   check(errors.length===0,'No uncaught 3D errors');
  }finally{await c.close();}
 }
 return {checks:checks.length,results:checks,scope:'real Three/WebGL on fake topology; no network/DB'};
}
