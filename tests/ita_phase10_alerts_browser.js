async(page)=>{
 const context=await page.context().browser().newContext({viewport:{width:1366,height:1000}}),p=await context.newPage(),checks=[],requests=[],errors=[];
 const check=(ok,label)=>{if(!ok)throw new Error(label);checks.push(label);};
 await p.clock.setFixedTime(new Date('2026-10-21T10:00:00+07:00'));
 p.on('request',r=>requests.push({url:r.url(),method:r.method()}));p.on('pageerror',e=>errors.push(e.message));
 await context.route('**/*',r=>r.request().url().startsWith('http://127.0.0.1:18766/')?r.continue():r.abort());
 const apply=()=>p.locator('#preview-alert-filters button[type=submit]').click();
 const count=()=>p.locator('#preview-alert-table tbody tr').count();
 try{
  await p.goto('http://127.0.0.1:18766/assets/ita/mock/dashboard.html#alerts');await p.locator('#preview-alert-summary').filter({hasText:'4 / 4'}).waitFor();
  const asof=await p.locator('#preview-alerts').getAttribute('data-as-of');
  check(await count()===4,'Four active reasons, no inactive');check((await p.locator('#preview-alert-table tbody tr').first().textContent()).includes('Khẩn cấp'),'Critical sorted first');
  await p.locator('#preview-alert-size').selectOption('2');check(await count()===2,'Page size2');check((await p.locator('#preview-alert-page').textContent())==='Trang 1/2','Page1of2');
  await p.locator('#preview-alert-next').click();check((await p.locator('#preview-alert-page').textContent())==='Trang 2/2','Next page');check(await p.locator('#preview-alert-next').isDisabled(),'Last page disabled');
  check(await p.locator('#preview-alert-table tbody tr').filter({hasText:'MOCK-001'}).count()===2,'Two distinct reasons same device');
  await p.locator('#preview-alert-severity').selectOption('critical');await apply();check((await p.locator('#preview-alert-summary').textContent()).startsWith('2 / 4'),'Severity filtered count');check((await p.locator('#preview-alert-page').textContent())==='Trang 1/1','Filter resets page');
  check((await p.locator('#preview-alert-count').textContent())==='4','Bell not filtered rows');
  await p.locator('#preview-alert-reason').selectOption('maintenance_overdue');await apply();check(await count()===2,'Reason and severity intersection');
  await p.locator('#preview-alert-search').fill('MOCK-001');await p.locator('#preview-alert-search').press('Enter');check((await p.locator('#preview-alert-summary').textContent()).startsWith('0 / 4'),'Search intersection empty');check((await p.locator('#preview-alert-table').textContent()).includes('Không có dữ liệu phù hợp'),'Empty message');
  check(await p.locator('#preview-alert-next').isDisabled()&&await p.locator('#preview-alert-previous').isDisabled(),'Empty pagination disabled');
  await p.locator('#preview-alert-clear').click();check((await p.locator('#preview-alert-summary').textContent()).startsWith('4 / 4'),'Clear all list filters');check((await p.locator('#preview-alert-size').inputValue())==='2','Clear retains chosen page size');
  await p.locator('#preview-alert-search').fill('thiết bị lưu trữ');await apply();check((await p.locator('#preview-alert-summary').textContent()).startsWith('1 / 4'),'Vietnamese name search');
  check((await p.locator('#preview-alert-table').textContent()).includes('MOCK-003'),'Search right device');
  await p.locator('#preview-alert-clear').click();await p.locator('#preview-department').selectOption('ops');
  await p.locator('#preview-alert-next').click();check((await p.locator('#preview-alert-summary').textContent()).startsWith('4 / 4'),'List pagination uses applied global scope');
  await p.locator('#preview-filters button').click();check((await p.locator('#preview-alert-count').textContent())==='1','Ops effective bell1');check((await p.locator('#preview-alert-summary').textContent()).startsWith('1 / 1'),'Applied global scope matches list');
  await p.locator('#preview-role').selectOption('system_manager');check((await p.locator('#preview-alert-count').textContent())==='3','Manager effective bell3');check((await p.locator('#preview-alert-summary').textContent()).startsWith('3 / 3'),'Manager listIT3');
  await p.locator('#preview-alert-reason').selectOption('maintenance_overdue');await apply();check((await p.locator('#preview-alert-summary').textContent()).startsWith('1 / 3'),'Manager reason filter');check((await p.locator('#preview-alert-count').textContent())==='3','Manager bell not filtered');
  check(await p.locator('#preview-alerts').getAttribute('data-as-of')===asof,'List filters keep common asof');check(await p.evaluate(()=>itaDashboardPreview.chartCount()===0),'No heavy charts while filtering alerts');
  await p.locator('#preview-role').selectOption('support_technician');check(await p.locator('#preview-alerts').isHidden()&&await p.locator('#preview-maintenance').isVisible(),'Support no alert center');
  await p.locator('#preview-role').selectOption('admin_it');await p.locator('#preview-alerts-tab').click();await p.locator('#preview-alert-clear').click();
  for(const width of [1366,768,390]){await p.setViewportSize({width,height:1000});await p.evaluate(()=>{document.activeElement?.blur();scrollTo(0,0);});await p.waitForTimeout(60);check(await p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1),'No page overflow '+width);check(await p.locator('#preview-alert-filters').evaluate(n=>[...n.querySelectorAll('input,select,button')].every(n=>{const r=n.getBoundingClientRect();return r.left>=0&&r.right<=innerWidth+1;})),'List control bounds '+width);await p.screenshot({path:`.playwright-cli/phase10-alerts-${width}.png`,fullPage:true});}
  check(requests.filter(r=>r.url.endsWith('/dashboard.json')).length===1,'Only one static data read; no refresh on filters');check(requests.every(r=>r.method==='GET'&&!r.url.includes('admin.php')),'No writes/PHP requests');check(errors.length===0,'No runtime errors');return {checks:checks.length,results:checks,scope:'FE/mock alert list only; no BE/DB/read-state storage'};
 }finally{await context.close();}
}
