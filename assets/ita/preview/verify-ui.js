async (page) => {
 const check = (condition, message) => { if (!condition) throw new Error(message); };
 const runtimeErrors = [];
 page.on('pageerror', error => runtimeErrors.push(error.message));
 const phpMode = new URL(page.url()).port === '18768';
 await page.setViewportSize({width:1366,height:1000});
 await page.goto(phpMode ? 'http://127.0.0.1:18768/admin.php?op=ita' : 'http://127.0.0.1:18766');
 if(phpMode)check(await page.locator('body').getAttribute('data-renderer')==='smarty','PHP renders Smarty');
 await page.locator('#asset-rows tr').first().waitFor();
 const checks = [];
 check(await page.locator('#asset-rows tr').count() === 5, 'Initial page size');
 check(await page.locator('#donut-total').textContent() === '18', 'Mock total');
 await page.locator('#next-page').click();
 check((await page.locator('#page-summary').textContent()).includes('6–10'), 'Next page');
 await page.locator('#asset-search').fill('IT-0008');
 check(await page.locator('#asset-rows tr').count() === 1, 'Search match');
 await page.getByRole('button', {name:'Xem chi tiết IT-0008', exact:true}).click();
 check(await page.locator('#asset-dialog').isVisible(), 'Detail modal visible');
 check((await page.locator('#dialog-title').textContent()).includes('EliteBook'), 'Correct detail');
 await page.keyboard.press('Escape');
 check(!(await page.locator('#asset-dialog').isVisible()), 'Escape closes modal');
 await page.locator('#asset-search').fill('<script>bad</script>');
 check(await page.locator('#empty-state').isVisible(), 'Empty state');
 await page.locator('#clear-filters').click();
 await page.locator('#status-filter').selectOption('broken');
 check(await page.locator('#asset-rows tr').count() === 2, 'Status filter');
 await page.locator('#department-filter').selectOption('CNTT');
 check(await page.locator('#empty-state').isVisible(), 'Combined filters');
 await page.locator('#filter-form button[type=reset]').click();
 await page.waitForFunction(() => document.querySelectorAll('#asset-rows tr').length === 5);
 check(await page.locator('#asset-rows tr').count() === 5, 'Reset filters');
 await page.locator('#notification-button').click();
 await page.locator('#alerts-panel').waitFor({state:'visible'});
 check(await page.locator('#alerts-panel').isVisible(), 'Notification navigation');
 check(await page.locator('.alert-row').count() === 4, 'Alert list');
 await page.locator('.alert-row button').first().click();
 check(await page.locator('#asset-dialog').isVisible(), 'Alert detail');
 await page.locator('#close-dialog').click();
 await page.locator('[data-view=overview]').click();
 await page.locator('#overview-panel').waitFor({state:'visible'});
 checks.push('Search, combined filters, pagination, empty/reset, modal Escape, alerts: PASS');
 for (const width of [1366,768,390]) {
  await page.setViewportSize({width,height:1000});
  const layout = await page.evaluate(() => {
   const box = (id) => {const r=document.getElementById(id).getBoundingClientRect();return {x:r.x,width:r.width,right:r.right};};
   return {viewport:innerWidth,scroll:document.documentElement.scrollWidth,main:box('main'),sidebar:box('sidebar'),selectImage:getComputedStyle(document.getElementById('status-filter')).backgroundImage};
  });
  check(layout.scroll <= width, `No horizontal overflow at ${width}: ${JSON.stringify(layout)}`);
  check(layout.main.right <= width+1, `Main fits ${width}`);
  check(layout.selectImage !== 'none', `Select arrow ${width}`);
  if(width===768)check(layout.sidebar.width===76,'Tablet compact sidebar');
  if(width===1366){
   await page.locator('#menu-toggle').click();
   check(await page.locator('body.sidebar-collapsed').count()===1,'Desktop collapse');
   await page.locator('#menu-toggle').click();
   check(await page.locator('body.sidebar-collapsed').count()===0,'Desktop expand');
  }
  if(width===390){
   check(layout.sidebar.right<=0,'Mobile menu initially hidden');
   await page.locator('#menu-toggle').click();
   check(await page.locator('#sidebar-backdrop').isVisible(),'Mobile backdrop');
   check((await page.locator('#menu-toggle').getAttribute('aria-expanded'))==='true','Menu expanded');
   await page.keyboard.press('Escape');
   check(!(await page.locator('#sidebar-backdrop').isVisible()),'Menu Escape');
  }
  await page.screenshot({path:`.playwright-cli/infracore-${width}.png`,fullPage:true});
  checks.push(`Viewport ${width}: PASS ${JSON.stringify(layout)}`);
 }
 await page.route('**/mock/overview.json', route => route.fulfill({status:503,body:'unavailable'}));
 await page.reload();
 await page.locator('#load-error').waitFor({state:'visible'});
 check(!(await page.locator('#data-content').isVisible()),'Error hides stale content');
 await page.unroute('**/mock/overview.json');
 await page.locator('#retry-button').click();
 await page.locator('#asset-rows tr').first().waitFor();
 check(!(await page.locator('#load-error').isVisible()),'Retry recovers');
 checks.push('Mock network failure and retry: PASS');
 await page.evaluate(()=>{ window.ItaUi.confirm({title:'Xác nhận thử nghiệm',message:'<img src=x onerror=alert(1)>'}).then(value=>{window.itaConfirmResult=value;}); });
 check(await page.locator('#confirm-message img').count()===0,'Confirmation escapes message');
 await page.locator('#confirm-cancel').click();
 await page.waitForFunction(()=>window.itaConfirmResult===false);
 check(await page.evaluate(()=>window.itaConfirmResult)===false,'Confirmation cancellation');
 await page.evaluate(()=>{ window.ItaUi.confirm().then(value=>{window.itaConfirmResult=value;}); });
 await page.locator('#confirm-accept').click();
 await page.waitForFunction(()=>window.itaConfirmResult===true);
 check(await page.evaluate(()=>window.itaConfirmResult)===true,'Confirmation acceptance');
 checks.push('Reusable confirmation, cancel/accept and text escaping: PASS');
 await page.emulateMedia({reducedMotion:'reduce'});
 check(await page.locator('#asset-rows tr').count()===5,'Reduced motion remains usable');
 await page.setViewportSize({width:1366,height:1000});
 check(runtimeErrors.length===0,`No JS runtime errors: ${runtimeErrors.join(';')}`);
 checks.push('No JavaScript runtime errors: PASS');
 return checks;
}
