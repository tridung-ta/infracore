async(page)=>{
 const checks=[],check=(ok,label)=>{if(!ok)throw new Error(label);checks.push(label);};
 const c=await page.context().browser().newContext({viewport:{width:1366,height:1000}}),p=await c.newPage();let writes=0;
 p.on('request',r=>{if(r.method()==='POST')writes++;});
 try{
  await p.goto('http://127.0.0.1:18766/?devices=1#network');await p.locator('#network-show-3d').click();await p.locator('#network-3d canvas').waitFor();check(await p.locator('.network-3d-name').count()===6,'Manual six-device fixture usable');
  await p.goto('http://127.0.0.1:18766/assets/ita/mock/form-validation.html');
  await p.locator('#edit-asset_code').fill('TEST-LOCAL-01');await p.locator('#edit-name').fill('Máy in tầng 2');await p.locator('#edit-purchase_price').fill('123.45');await p.locator('form').first().locator('button').click();check((await p.locator('form').first().textContent()).includes('Qua kiểm tra FE'),'Standalone inert asset form works');
  await p.locator('#license-seats').fill('1');await p.locator('#license-start').fill('2026-10-27');await p.locator('#license-expire').fill('2025-10-27');await p.locator('form').nth(1).locator('button').click();check(await p.locator('#license-expire').evaluate(n=>n.validity.customError),'License date order rejected');
  await p.locator('#license-start').fill('2024-10-27');await p.locator('form').nth(1).locator('button').click();check((await p.locator('form').nth(1).textContent()).includes('Qua kiểm tra FE'),'Correcting license start alone clears paired error');
  await p.locator('#license-seats').fill('1.5');check(!await p.locator('#license-seats').evaluate(n=>n.checkValidity()),'Fractional seats rejected');
  for(const width of [1366,768,390]){await p.setViewportSize({width,height:1000});check(await p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1),'Demo form no overflow '+width);check(await p.locator('input').evaluateAll(nodes=>nodes.every(n=>{const r=n.getBoundingClientRect(),p=n.closest('form').getBoundingClientRect();return r.left>=p.left&&r.right<=p.right&&r.width>80;})),'Actual input bounds inside form '+width);}
  await p.screenshot({path:'.playwright-cli/refinements-form-390.png',fullPage:true});check(writes===0,'Manual preview never posts data');
 }finally{await c.close();}
 return {checks:checks.length,results:checks,scope:'Local static manual preview only; no DB/host'};
}
