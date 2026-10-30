async(page)=>{
 const documents=__ERROR_DOCUMENTS__,context=await page.context().browser().newContext(),p=await context.newPage(),errors=[],checks=[];
 const check=(ok,label)=>{if(!ok)throw Error(label);checks.push(label);};p.on('pageerror',e=>errors.push(e.message));
 await context.route('**/*',r=>{const u=new URL(r.request().url());if(u.origin!=='http://127.0.0.1:18766')return r.abort();if(u.pathname==='/preview-error')return r.fulfill({status:Number(u.searchParams.get('status')),contentType:'text/html; charset=utf-8',body:documents[u.searchParams.get('status')]});return r.continue();});
 try{
  for(const status of [403,404,500,503]){
   await p.goto('http://127.0.0.1:18766/preview-error?status='+status);
   check(await p.locator('h1').count()===1,'Single error heading '+status);check(await p.locator('main img').count()===0,'Escaped server text '+status);
   check(/^[a-f0-9]{16}$/.test(await p.locator('code').textContent()),'Reference '+status);
   for(const width of [1366,768,390]){
    await p.setViewportSize({width,height:900});check(await p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'No overflow '+status+'/'+width);
    check(await p.locator('nav a').evaluateAll(ns=>ns.every(n=>{const r=n.getBoundingClientRect();return r.left>=0&&r.right<=innerWidth&&r.height>=44;})),'Action bounds/targets '+status+'/'+width);
    if(status===404)await p.screenshot({path:'.playwright-cli/phase11-http-error-'+width+'.png',fullPage:true});
   }
   await p.locator('nav a').first().focus();check(await p.locator('nav a').first().evaluate(n=>document.activeElement===n&&getComputedStyle(n).outlineStyle!=='none'),'Keyboard focus '+status);
  }
  check(errors.length===0,'No runtime errors');return {checks:checks.length,results:checks,scope:'Actual Smarty HTML from local PHP fixture, browser transport mocked, no DB/host'};
 }finally{await context.close();}
}
