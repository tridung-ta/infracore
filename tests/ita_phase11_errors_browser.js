async(page)=>{
 const context=await page.context().browser().newContext({viewport:{width:1366,height:1000}}),p=await context.newPage(),checks=[],requests=[],errors=[];let failed=true,reads=0;
 const check=(ok,label)=>{if(!ok)throw Error(label);checks.push(label);};p.on('pageerror',e=>errors.push(e.message));
 await context.route('**/*',route=>{const req=route.request();requests.push({url:req.url(),method:req.method()});if(!req.url().startsWith('http://127.0.0.1:18766/'))return route.abort();if(req.url().endsWith('/overview.json')){reads++;if(failed)return route.abort();}return route.continue();});
 try{
  await p.goto('http://127.0.0.1:18766/');await p.locator('#load-error').waitFor({state:'visible'});
  check((await p.locator('#load-error-title').textContent()).includes('Không kết nối'),'Network title');check(await p.locator('#load-error').evaluate(n=>n===document.activeElement),'Error focused');check(await p.locator('#retry-button').isVisible(),'Manual retry available');
  const before=reads;failed=false;await p.locator('#retry-button').click();await p.locator('#load-error').waitFor({state:'hidden'});await p.locator('#asset-rows tr').first().waitFor();check(reads===before+1,'Only explicit retry reads fixture');
  for(const status of [400,401,403,404,409,422,429,500,503]){
   await p.evaluate(code=>{document.getElementById('data-content').hidden=true;ItaFeedback.show({status:code});},status);
   check(await p.locator('#load-error').getAttribute('data-status')===String(status),'State '+status);check((await p.locator('#load-error-title').textContent()).length>8,'Useful heading '+status);check((await p.locator('#load-error-help').textContent()).length>20,'Next action '+status);
   check((await p.locator('#retry-button').isVisible())===[400,409,500,503].includes(status),'Retry policy '+status);
  }
  await p.evaluate(()=>ItaFeedback.show({status:503,message:'<img src=x onerror=alert(1)> '+'x'.repeat(800)}));check(await p.locator('#load-error img').count()===0,'Message escaped');check((await p.locator('#load-error-message').textContent()).length===400,'Message bounded');
  for(const width of [1366,768,390]){
   await p.setViewportSize({width,height:1000});check(await p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1),'Error page bounds '+width);check(await p.locator('.app-error-actions').evaluate(n=>[...n.children].filter(n=>!n.hidden).every(n=>{const r=n.getBoundingClientRect();return r.width>=44&&r.height>=44&&r.left>=0&&r.right<=innerWidth+1;})),'Error action targets '+width);await p.screenshot({path:`.playwright-cli/phase11-error-${width}.png`,fullPage:true});
  }
  await p.evaluate(()=>{document.body.dataset.mode='live';ItaFeedback.show({status:401});});check((await p.locator('#load-error-home').getAttribute('href')).includes('act=login'),'Session state uses existing login URL');await p.evaluate(()=>ItaFeedback.show({status:403}));check(await p.locator('#retry-button').isHidden(),'Forbidden no repeat button');check((await p.locator('#load-error-home').getAttribute('href'))==='/admin.php?op=ita','Existing overview URL');
  check(requests.every(r=>r.method==='GET'&&!r.url.includes('/admin.php')),'Presentation test no PHP/POST');check(reads===2,'No automatic retry');
  let liveStatus=503,nonJson=false;const live=await context.newPage();live.on('pageerror',e=>errors.push(e.message));await context.route('**/admin.php?*',route=>route.fulfill({status:liveStatus,contentType:nonJson?'text/html':'application/json',body:nonJson?'<html>Proxy refused</html>':JSON.stringify({success:false,data:null,message:'Dịch vụ tạm thời chưa sẵn sàng'})}));await context.route('http://127.0.0.1:18766/',async route=>{const response=await route.fetch();return route.fulfill({response,body:(await response.text()).replace('data-mode="mock"','data-mode="live"')});});await live.goto('http://127.0.0.1:18766/');await live.locator('#load-error[data-status="503"]').waitFor({state:'visible'});check((await live.locator('#load-error-message').textContent()).includes('Dịch vụ tạm thời'),'Actual shell HTTP503 mapped');
  nonJson=true;for(const status of [403,404,503]){liveStatus=status;await live.reload();await live.locator(`#load-error[data-status="${status}"]`).waitFor({state:'visible'});check((await live.locator('#retry-button').isVisible())===(status===503),'NonJSON HTTP retry policy '+status);check((await live.locator('#load-error-message').textContent()).includes('phản hồi không hợp lệ'),'NonJSON keeps real HTTP status '+status);}await live.close();
  check(errors.length===0,'No runtime errors');return {checks:checks.length,results:checks,scope:'FE error states + mocked HTTP, no PHP/DB/status routing changes'};
 }finally{await context.close();}
}
