async(page)=>{
 const context=await page.context().browser().newContext({viewport:{width:1366,height:1000}}),p=await context.newPage(),checks=[],requests=[],errors=[];let fail=false;
 const check=(ok,label)=>{if(!ok)throw new Error(label);checks.push(label);};
 p.on('pageerror',e=>errors.push(e.message));p.on('request',r=>requests.push({url:r.url(),method:r.method()}));
 await context.route('**/*',r=>{if(!r.request().url().startsWith('http://127.0.0.1:18766/'))return r.abort();if(fail&&r.request().url().endsWith('/reports.json'))return r.fulfill({status:503,body:'fail'});return r.continue();});
 const count=()=>p.locator('#report-count').textContent();
 const limit=async(format,n,accepted)=>{await p.locator('#report-format').selectOption(format);await p.locator('#report-limit-count').fill(String(n));await p.locator('#report-limit-form button').click();check(await p.locator('#report-limit-result').getAttribute('data-state')===(accepted?'accept':'reject'),`${format}/${n} ${accepted?'accepted mock limit':'refused'}`);};
 try{
  fail=true;await p.goto('http://127.0.0.1:18766/assets/ita/mock/reports.html');await p.locator('#report-message').filter({hasText:'Không tải được'}).waitFor();check(await p.locator('#report-retry').isVisible(),'Initial error has retry');fail=false;
  await p.locator('#report-retry').click();await p.locator('#report-content').waitFor({state:'visible'});
  check((await p.locator('.mock-note').textContent()).includes('Không có API'),'Explicit mock and no API');
  check((await count()).startsWith('9 '),'Inventory9');check(await p.locator('#report-table tbody tr').count()===5,'Paginated5 per page');
  await p.locator('#report-next').click();check(await p.locator('#report-table tbody tr').count()===4,'Page2 remaining4');check(await p.locator('#report-next').isDisabled(),'Last page disabled');
  await limit('xlsx',5000,true);await limit('xlsx',5001,false);await limit('pdf',500,true);await limit('pdf',501,false);await limit('pdf',0,false);
  await p.locator('#report-type').selectOption('maintenance');check((await count()).startsWith('4 '),'Maintenance includes due and overdue');
  check((await p.locator('#report-table').textContent()).includes('Quá hạn'),'Overdue distinguished');check((await p.locator('#report-definition').textContent()).includes('chưa duyệt BE'),'30-day proposal marked not approved');
  await limit('xlsx',1000,true);await limit('xlsx',1001,false);await limit('pdf',501,false);
  await p.locator('#report-type').selectOption('lifecycle');check((await count()).startsWith('5 '),'Shortlife five, not same as replace');
  const row=p.locator('#report-table tbody tr').filter({hasText:'MOCK-004'});check((await row.textContent()).includes('0.800')&&(await row.textContent()).includes('watch'),'Rounded0.800 keeps raw watch tier');
  check((await p.locator('#report-missing').textContent()).startsWith('1 hồ sơ'),'Missing lifespan inputs reported');
  check(!(await p.locator('#report-table').textContent()).includes('MOCK-005'),'Retired excluded from shortlife');
  const dates=await p.evaluate(()=>[itaReportsPreview.addMonths('2024-02-29',12),itaReportsPreview.addMonths('2026-01-31',1)]);check(dates[0]==='2025-02-28'&&dates[1]==='2026-02-28','Calendar months clamp leap/endmonth');
  await p.locator('#report-type').selectOption('budget');check((await p.locator('#report-totals').textContent()).includes('135 300 000,00'),'Budget hand total135.3m');
  await p.locator('#report-price').fill('30000000.00');await p.locator('#report-price-apply').click();check((await p.locator('#report-totals').textContent()).includes('201 300 000,00'),'Current forecast reacts to mock price');
  await p.locator('#report-budget-source').selectOption('snapshot');check((await p.locator('#report-totals').textContent()).includes('135 300 000,00'),'Frozen snapshot unchanged');
  check(await p.locator('#report-price').isDisabled()&&await p.locator('#report-price').inputValue()==='20000000.00','Snapshot price read-only and matches frozen data');
  check((await p.locator('#report-source').textContent()).includes('MOCK-PLAN-2026-IT'),'Source snapshot ID shown');
  await p.locator('#report-budget-source').selectOption('current');check((await p.locator('#report-totals').textContent()).includes('201 300 000,00'),'Current price survives snapshot view');
  await p.locator('#report-price').fill('');await p.locator('#report-price-apply').click();check(await p.locator('#report-message').getAttribute('data-error')==='true','Blank price not coerced0');
  await p.locator('#report-price').fill('0');await p.locator('#report-price-apply').click();check((await p.locator('#report-totals').textContent()).includes('3 300 000,00'),'Explicit0 price known');
  await p.locator('#report-department').selectOption('ops');check((await count()).startsWith('0 '),'Missing Ops plan has no rows');check(await p.locator('#report-totals').isHidden(),'Missing plan not zero full budget');
  await p.locator('#report-role').selectOption('system_manager');check(await p.locator('#report-department').inputValue()==='it'&&await p.locator('#report-department').isDisabled(),'Manager visible scope forcedIT');
  await p.locator('#report-role').selectOption('support_technician');check(await p.locator('#report-type').inputValue()==='maintenance'&&await p.locator('#report-type').isDisabled(),'Support only maintenance');
  check(await p.locator('#report-limit-panel').isHidden()&&await p.locator('#report-budget-controls').isHidden(),'Support no export/limit/budget controls');check((await count()).startsWith('3 '),'Support maintenanceIT3');
  check(await p.locator('a[download]').count()===0,'No download link');
  await p.locator('#report-role').selectOption('admin_it');await p.locator('#report-type').selectOption('inventory');
  for(const width of [1366,768,390]){await p.setViewportSize({width,height:1000});await p.evaluate(()=>{document.activeElement?.blur();scrollTo(0,0);});await p.waitForTimeout(80);check(await p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1),'No page overflow '+width);check(await p.locator('#report-filters').evaluate(n=>[...n.querySelectorAll('select,button')].every(n=>{const b=n.getBoundingClientRect();return b.left>=0&&b.right<=innerWidth+1;})),'Filter bounds '+width);await p.screenshot({path:`.playwright-cli/phase10-reports-${width}.png`,fullPage:true});}
  await p.locator('.skip-link').focus();await p.keyboard.press('Enter');check(await p.locator('#reports-main').evaluate(n=>n===document.activeElement),'Skip link keyboard focus');
  check(requests.every(r=>r.method==='GET'&&!r.url.includes('admin.php')),'No writes/PHP/API requests');check(requests.every(r=>r.url.startsWith('http://127.0.0.1:18766/')),'All local requests');
  await context.route('**/reports.json',async r=>{const response=await r.fetch(),data=await response.json();data.assets.find(a=>a.id===2).purchase_date='2022-10-21';data.assets.find(a=>a.id===2).name='<img src=x onerror=alert(1)>';data.assets.find(a=>a.id===9).purchase_date='2022-10-22';data.assets.find(a=>a.id===8).purchase_date='2199-01-01';return r.fulfill({json:data});});
  const edge=await context.newPage();edge.on('pageerror',e=>errors.push(e.message));await edge.goto('http://127.0.0.1:18766/assets/ita/mock/reports.html');await edge.locator('#report-content').waitFor({state:'visible'});await edge.locator('#report-type').selectOption('lifecycle');
  check((await edge.locator('#report-count').textContent()).startsWith('6 '),'Exact12month horizon included, nextday excluded');
  check((await edge.locator('#report-missing').textContent()).startsWith('1 hồ sơ'),'Future purchase flagged missing');
  check((await edge.locator('#report-table').textContent()).includes('<img src=x')&&await edge.locator('#report-table img').count()===0,'Report text escaped');await edge.close();
  check(errors.length===0,'No runtime errors');return {checks:checks.length,results:checks,scope:'standalone mock report preview and limit simulator; no file/BE/DB'};
 }finally{await context.close();}
}
