async(page)=>{
 const context=await page.context().browser().newContext({viewport:{width:1366,height:1000}}),p=await context.newPage(),checks=[],errors=[],requests=[];
 const check=(ok,label)=>{if(!ok)throw Error(label);checks.push(label);};p.on('pageerror',e=>errors.push(e.message));
 await context.route('**/*',route=>{requests.push(route.request().url());if(!route.request().url().startsWith('http://127.0.0.1:18766/')||route.request().url().includes('/admin.php'))return route.abort();return route.continue();});
 try{
  await p.goto('http://127.0.0.1:18766/');await p.locator('#asset-rows tr').first().waitFor();
  const views=['overview','assets','imports','assignments','licenses','tickets','lifecycle','forecast','alerts'];
  for(const width of [1366,768,390]){
   await p.setViewportSize({width,height:1000});
   for(const view of views){await p.evaluate(v=>location.hash=v,view);await p.waitForTimeout(80);check(await p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1),'Document '+view+' '+width);
    const bad=await p.evaluate(()=>[...document.querySelectorAll('main input,main select,main button')].filter(n=>n.getClientRects().length&&!n.closest('[hidden],[inert],dialog:not([open])')).filter(n=>{const r=n.getBoundingClientRect();if(r.width===0||r.height===0||r.left>=0&&r.right<=innerWidth+1)return false;for(let parent=n.parentElement;parent&&parent!==document.body;parent=parent.parentElement){const s=getComputedStyle(parent),b=parent.getBoundingClientRect();if(['auto','scroll'].includes(s.overflowX)&&parent.scrollWidth>parent.clientWidth&&b.left>=0&&b.right<=innerWidth+1)return false;}return true;}).map(n=>n.id||n.tagName));check(bad.length===0,'Control bounds '+view+' '+width+(bad.length?' '+bad.join(','):''));
    await p.screenshot({path:`.playwright-cli/phase11-${view}-${width}.png`,fullPage:true});
   }
  }
  await p.evaluate(()=>location.hash='forecast');check((await p.locator('.forecast-scroll-hint').textContent()).includes('Bảng có thể cuộn ngang'),'Vietnamese scroll hint');check((await p.locator('.forecast-table-wrap').getAttribute('aria-label'))==='Bảng ngân sách có thể cuộn ngang','Vietnamese table accessible name');check(!requests.some(u=>u.includes('/admin.php')),'Static mock no PHP endpoint');check(errors.length===0,'No runtime errors');return {checks:checks.length,results:checks,scope:'Static presentation of nine views; live/API mock checked in existing suites, no DB'};
 }finally{await context.close();}
}
