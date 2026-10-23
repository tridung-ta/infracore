async(page)=>{
 const source=await (await page.request.get('http://127.0.0.1:18766/assets/ita/mock/network.json')).json(),checks=[],errors=[];
 const check=(ok,label)=>{if(!ok)throw new Error(label);checks.push(label);};
 const context=await page.context().browser().newContext({viewport:{width:1366,height:1000}}),p=await context.newPage();let fail=true,reads=0;
 p.on('pageerror',e=>errors.push(e.message));
 await context.route('**/assets/ita/mock/network.json',route=>{reads++;return route.fulfill({status:fail?503:200,contentType:'application/json',body:JSON.stringify({...source,nodes:source.nodes.map((n,i)=>i? n:{...n,name:'QA <img src=x>'})})});});
 await context.route('**/admin.php?*',r=>r.abort());
 try{
  await p.goto('http://127.0.0.1:18766/#network');await p.locator('#network-retry').waitFor({state:'visible'});check(await p.locator('#network-content').isHidden(),'Read error hides graph and offers retry');
  fail=false;await p.locator('#network-retry').click();await p.locator('#network-content').waitFor({state:'visible'});
  check(await p.locator('#network-nodes g').count()===8&&await p.locator('#network-link-rows tr').count()===7,'2D nodes and table use same seven links');
  await p.locator('#network-nodes g').first().focus();await p.keyboard.press('Enter');check((await p.locator('#network-detail-title').textContent()).includes('QA <img src=x>')&&await p.locator('#network-panel img').count()===0,'SVG keyboard selection and safe text');
  const before=reads;await p.locator('#network-node-list button').nth(2).click();check((await p.locator('#network-detail-title').textContent()).includes('Switch trung tâm')&&reads===before,'List selection uses existing mock data');
  await p.locator('#network-department').selectOption('Văn phòng');await p.locator('#network-filters button[type=submit]').click();check(await p.locator('#network-nodes g').count()===3&&await p.locator('#network-link-rows tr').count()===2,'Filter keeps only edges with both visible ends');
  await p.locator('#network-search').fill('not-found');await p.locator('#network-filters button[type=submit]').click();check((await p.locator('#network-count').textContent()).includes('0/8')&&(await p.locator('#network-link-rows').textContent()).includes('Không có liên kết'),'Empty filter explained');
  await p.locator('#network-reset').click();check(await p.locator('#network-nodes g').count()===8,'Reset restores whole mock');
  for(const width of [1366,768,390]){await p.setViewportSize({width,height:1000});check(await p.locator('#network-panel').evaluate(n=>{const r=n.getBoundingClientRect();return r.left>=0&&r.right<=innerWidth&&document.documentElement.scrollWidth<=innerWidth;}),`Network bounds ${width}`);}
  await p.screenshot({path:'.playwright-cli/phase13-network-390.png',fullPage:true});
 }finally{await context.close();}
 const c=await page.context().browser().newContext(),live=await c.newPage();let mockReads=0;
 await c.route('**/*',async route=>{const req=route.request(),url=new URL(req.url());if(req.isNavigationRequest()){const r=await route.fetch();return route.fulfill({response:r,body:(await r.text()).replace('data-mode="mock"','data-mode="live"')});}if(url.pathname==='/assets/ita/mock/network.json'){mockReads++;return route.abort();}if(url.pathname==='/admin.php')return route.fulfill({contentType:'application/json',body:JSON.stringify({success:true,data:url.searchParams.get('act')==='data'?{assets:[],departments:[],pagination:{page:1,total:0,per_page:5},user:{id:111,role:'admin_it',display_name:'QA'},summary:{total:0,by_status:{},by_department:[],alerts:[],alerts_total:0}}:url.searchParams.get('act')==='categories'?[]:{rows:[],total:0,active_total:0,alerts_total:0}})});return route.continue();});
 try{await live.goto('http://127.0.0.1:18766/#network');await live.locator('#network-retry').waitFor({state:'visible'});check(mockReads===0&&await live.locator('#network-nav').isVisible()&&await live.locator('#network-content').isHidden(),'Live mode rejects invalid server topology and never falls back to MOCK');}finally{await c.close();}
 check(errors.length===0,'No JS runtime errors');return {checks:checks.length,results:checks,scope:'network MOCK only; no actual network/DB'};
}
