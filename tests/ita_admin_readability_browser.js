async(page)=>{
 const checks=[],check=(ok,label)=>{if(!ok)throw new Error(label);checks.push(label);};
 const c=await page.context().browser().newContext({viewport:{width:1366,height:1000}}),p=await c.newPage(),errors=[];let posts=0;
 p.on('pageerror',e=>errors.push(e.message));p.on('request',r=>{if(r.method()==='POST')posts++;});
 try{
  await p.goto('http://127.0.0.1:18766/#audit');await p.locator('#audit-rows > tr').first().waitFor();
  check((await p.locator('#audit-user').textContent()).includes('Lan — IT'),'Choose actor by name instead of numeric input');
  check((await p.locator('#audit-rows > tr').first().textContent()).includes('Đăng nhập'),'Human-readable action');
  check(await p.locator('#audit-rows > tr').first().locator('details').count()===0,'Null before/after has no empty JSON disclosure');
  await p.locator('#audit-rows > tr').nth(1).locator('summary').click();check(await p.locator('#audit-rows > tr').nth(1).locator('.audit-change-table tbody tr').count()===2,'Only changed fields rendered, unchanged price omitted');
  check((await p.locator('#audit-rows > tr').nth(1).textContent()).includes('Tên'),'Changed field translated');
  await p.locator('#audit-action').selectOption('asset.update');await p.locator('#audit-user').selectOption('111');await p.locator('#audit-filters button[type=submit]').click();await p.waitForFunction(()=>document.querySelectorAll('#audit-rows > tr').length===1);check(true,'Action and actor combine');
  await p.locator('#audit-reset').click();await p.waitForFunction(()=>document.querySelectorAll('#audit-rows > tr').length===7);
  await p.locator('#audit-from').fill('2026-10-28');await p.locator('#audit-to').fill('2026-10-20');await p.locator('#audit-filters button[type=submit]').click();check(await p.locator('#audit-to').evaluate(n=>n.validity.customError),'Reversed dates rejected');
  await p.locator('#audit-from').fill('2026-10-20');await p.locator('#audit-filters button[type=submit]').click();await p.waitForFunction(()=>document.querySelectorAll('#audit-rows > tr').length===1);check(true,'Editing start alone clears range error');
  await p.locator('[data-audit-days="30"]').click();await p.waitForFunction(()=>document.querySelectorAll('#audit-rows > tr').length===7);check(true,'Quick range applies whole fixture');
  for(const width of [1366,768,390]){await p.setViewportSize({width,height:1000});check(await p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1),'Audit no page overflow '+width);check(await p.locator('#audit-user').evaluate(n=>{const r=n.getBoundingClientRect();return r.left>=0&&r.right<=innerWidth;}),'Actor control bounds '+width);if(width!==768)await p.screenshot({path:'.playwright-cli/audit-new-'+width+'.png',fullPage:true});}
  await p.goto('http://127.0.0.1:18766/#users');await p.locator('#permission-preview').waitFor({state:'visible'});
  check(await p.locator('#permission-modules input:checked').evaluateAll(nodes=>nodes.map(n=>n.value).sort().join(','))==='asset.create,asset.read,report.read','C combines functions1/2/6 without new role');
  await p.locator('#permission-account').selectOption('a');check(await p.locator('#permission-modules input:checked').count()===3,'A preset has three functions');
  await p.locator('#permission-modules input[value="asset.update"]').uncheck();await p.locator('#permission-account').selectOption('c');await p.locator('#permission-account').selectOption('a');check(!await p.locator('#permission-modules input[value="asset.update"]').isChecked(),'Per-user mock choice retained in memory');
  await p.locator('#permission-reset').click();check(await p.locator('#permission-modules input[value="asset.update"]').isChecked(),'Mock reset restores example');check(await p.locator('#access-form').isHidden(),'Mock does not expose real role saving');
  await p.goto('http://127.0.0.1:18766/#lifecycle');await p.locator('[data-group=category] tbody tr').first().waitFor();check((await p.locator('[data-group=category] th').nth(1).textContent())==='Số thiết bị','Count column explicitly named');
  const first=p.locator('[data-group=category] tbody tr').first();check(await first.locator('td').count()===7,'Age known/missing separate from repair fraction');await first.locator('button').click();await p.waitForFunction(()=>document.getElementById('lifecycle-category').value!=='');check(await p.locator('#lifecycle-more-filters').evaluate(n=>n.open),'Group selection reveals applied category filter');
  await p.locator('[data-lifecycle-group=department]').click();await p.locator('[data-group=department] tbody tr button').first().click();await p.waitForFunction(()=>document.getElementById('lifecycle-department').value!=='');check(true,'Department drilldown uses existing filter');
  check(posts===0,'No POST/auth/access writes');check(errors.length===0,'No runtime errors');
 }catch(error){throw new Error('After '+checks.at(-1)+': '+error.message);}finally{await c.close();}
 return {checks:checks.length,results:checks,scope:'Static MOCK local only; no DB/server authorization proof'};
}
