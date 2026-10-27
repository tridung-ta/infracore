async(page)=>{
 const checks=[],check=(ok,label)=>{if(!ok)throw new Error(label);checks.push(label);};
 const context=await page.context().browser().newContext({viewport:{width:1366,height:1000}}),p=await context.newPage(),errors=[];
 p.on('pageerror',e=>errors.push(e.message));
 const kinds=['Printer','Monitor','Desktop','Laptop','Server','Switch'];
 await context.route('**/assets/ita/mock/network.json',route=>route.fulfill({contentType:'application/json',body:JSON.stringify({mock:true,nodes:kinds.map((kind,i)=>({id:'qa-'+i,name:kind+' QA '+(i+1),kind,department:'QA',status:'healthy',x:150+(i%3)*350,y:130+Math.floor(i/3)*250})),links:[]})}));
 try{
  await p.goto('http://127.0.0.1:18766/#network');
  check(await p.locator('#forecast-nav use').getAttribute('href')!==await p.locator('[data-view=reports] use').getAttribute('href'),'Budget/report icons distinct');
  await p.evaluate(async()=>{const T=await import('/assets/ita/vendor/three/0.180.0/three.module.min.js'),add=T.Scene.prototype.add;T.Scene.prototype.add=function(...objects){window.__qaGroup=objects.find(n=>n.isGroup)||window.__qaGroup;return add.apply(this,objects);};});
  await p.locator('#network-show-3d').click();await p.locator('#network-3d canvas').waitFor();
  const models=await p.evaluate(()=>window.__qaGroup.children.filter(n=>n.isGroup).map(n=>({id:n.userData.id,signature:n.children.map(p=>JSON.stringify(p.geometry.parameters)+JSON.stringify(p.position)).join('|'),color:n.children[0].material.color.getHexString()})));
  check(models.length===6,'Six actual device groups rendered');check(new Set(models.map(n=>n.signature)).size===6,'Printer/monitor/desktop/laptop/server/switch silhouettes distinct');check(new Set(models.map(n=>n.color)).size===6,'Six fixture device colors distinct');
  check(await p.locator('.network-3d-name').count()===6,'Each device retains a name');
  const toggle=p.locator('[data-motion-toggle]');await toggle.click();check(await toggle.getAttribute('aria-pressed')==='false','Motion visibly off');await toggle.click();check(await toggle.getAttribute('aria-pressed')==='true','Motion visibly on');
  await p.screenshot({path:'.playwright-cli/refinements-3d-1366.png',fullPage:true});
  for(const width of [1366,768,390]){await p.setViewportSize({width,height:1000});await p.waitForTimeout(80);check(await p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1),'No page overflow at '+width);const box=await p.locator('#network-3d canvas').boundingBox();check(box.width>100&&box.width<=width,'Actual canvas bounds at '+width);}
  await p.screenshot({path:'.playwright-cli/refinements-3d-390.png',fullPage:true});
  await p.setViewportSize({width:1366,height:1000});
  // Synthetic form isolates FE contracts without starting any PHP/DB request.
  await p.evaluate(()=>{const f=document.createElement('form');f.id='qa-form';for(const [id,type]of [['edit-name','text'],['edit-purchase_date','date'],['edit-warranty_end','date'],['edit-purchase_price','text']]){const n=document.createElement('input');n.id=id;n.type=type;n.className='form-control';n.required=id==='edit-name';f.append(n);}const b=document.createElement('button');b.textContent='QA submit';f.append(b);document.querySelector('main').prepend(f);window.__qaPosts=0;f.addEventListener('submit',e=>{e.preventDefault();window.__qaPosts++;});});
  await p.locator('#edit-name').fill('   ');await p.locator('#qa-form button').click();check(await p.evaluate(()=>window.__qaPosts)===0,'Whitespace required text blocks before submit');
  await p.locator('#edit-name').fill('Máy in 1 — Phòng IT');await p.locator('#edit-purchase_date').fill('2026-10-27');await p.locator('#edit-warranty_end').fill('2025-10-27');await p.locator('#qa-form button').click();check(await p.evaluate(()=>window.__qaPosts)===0,'Warranty before purchase blocks');
  await p.locator('#edit-purchase_date').fill('2024-10-27');await p.locator('#qa-form button').click();check(await p.evaluate(()=>window.__qaPosts)===1,'Correcting only purchase date clears paired error');
  await p.locator('#edit-purchase_price').fill('-1');await p.locator('#qa-form button').click();check(await p.evaluate(()=>window.__qaPosts)===1,'Negative purchase price blocks');
  await p.locator('#edit-purchase_price').fill('20000000.25');await p.locator('#qa-form button').click();check(await p.evaluate(()=>window.__qaPosts)===2,'Vietnamese name and valid decimal accepted');
  await p.emulateMedia({reducedMotion:'reduce'});await p.waitForFunction(()=>!ItaMotion.enabled());check(await toggle.isEnabled()&&await p.evaluate(()=>!ItaMotion.enabled()),'Reduced motion blocks animation while theme switch stays usable');check(errors.length===0,'No page JS errors');
 }finally{await context.close();}
 return {checks:checks.length,results:checks,scope:'Static MOCK/WebGL and synthetic FE form; no PHP/DB/host'};
}
