async(page)=>{
 const checks=[],errors=[],check=(ok,label)=>{if(!ok)throw new Error(label);checks.push(label);};
 for(const role of ['admin_it','system_manager','support_technician']){
  const context=await page.context().browser().newContext({viewport:{width:1366,height:1000}}),p=await context.newPage(),reads=[],posts=[];
  let failRead=false,failSave=false,empty=false,clamp=false,revision=1,hold=false,release=null;
  const row=id=>({id,code:`QA-${id}`,name:`Category ${id} <img src=x>`,status:id===1?'inactive':'active',version:`version-${revision}-${id}`});
  const json=(route,data,status=200,message='')=>route.fulfill({status,contentType:'application/json',body:JSON.stringify({success:status===200,data,message})});
  p.on('pageerror',e=>errors.push(e.message));
  await context.route('**/*',async route=>{
   const req=route.request(),url=new URL(req.url());if(url.hostname!=='127.0.0.1')return route.abort();
   if(req.isNavigationRequest()){const response=await route.fetch();return route.fulfill({response,body:(await response.text()).replace('data-mode="mock"','data-mode="live"').replace('name="ita-csrf" content=""','name="ita-csrf" content="qa-token"')});}
   if(url.pathname!=='/admin.php')return route.continue();
   const act=url.searchParams.get('act');
   if(act==='data')return json(route,{assets:[],departments:[],pagination:{page:1,total:0,per_page:5},user:{id:111,role,display_name:'QA',role_label:role},summary:{total:0,by_status:{},by_department:[],alerts:[],alerts_total:0}});
   if(act==='categories'){
    if(!url.searchParams.has('paged'))return json(route,[row(1),row(2)]);
    reads.push({page:Number(url.searchParams.get('page')),q:url.searchParams.get('q'),method:req.method()});
    if(hold){hold=false;await new Promise(resolve=>release=resolve);}
    if(failRead)return json(route,null,503,'QA list unavailable');
    const number=empty||clamp?1:Math.min(Number(url.searchParams.get('page')),3);
    return json(route,{page:number,total:empty?0:41,per_page:20,rows:empty?[]:Array.from({length:number===3?1:20},(_,i)=>row((number-1)*20+i+1))});
   }
   if(act==='category-save'){
    posts.push({body:req.postDataJSON(),method:req.method(),csrf:req.headers()['x-csrf-token']});
    if(failSave)return json(route,null,409,'QA stale version');revision++;return json(route,{id:1});
   }
   return json(route,{rows:[],total:0,active_total:0,alerts_total:0,page:1,per_page:20});
  });
  try{
   await p.goto('http://127.0.0.1:18766/#assets');await p.waitForFunction(()=>window.itaLiveContext);
   const manage=p.getByRole('button',{name:'Quản lý loại tài sản',exact:true});
   if(role!=='admin_it'){check(await manage.isHidden(),`${role} management hidden`);check(reads.length===0,`${role} only legacy dropdown requests`);continue;}
   const ready=async text=>p.waitForFunction(value=>document.getElementById('category-page').textContent.includes(value)&&!document.getElementById('category-list').hasAttribute('aria-busy'),text);
   await manage.click();await ready('Trang 1/3');
   check(await p.locator('#category-list button').count()===20&&await p.locator('#category-prev').isDisabled(),'First page bounded');
   check(await p.locator('#category-list img').count()===0&&(await p.locator('#category-list').textContent()).includes('Ngừng sử dụng'),'Escaped and inactive rows included');
   await p.locator('#category-search').fill('%_!');await p.locator('#category-search').press('Enter');await ready('Trang 1/3');
   check(reads.at(-1).q==='%_!'&&reads.at(-1).page===1&&posts.length===0,'Search Enter sends GET only');
   await p.locator('#category-list button').first().click();await p.locator('#category-name').fill('Edited unsaved');
   await p.locator('#category-search').fill('not yet applied');await p.locator('#category-next').click();await ready('Trang 2/3');
   check(reads.at(-1).q==='%_!'&&reads.at(-1).page===2,'Page keeps applied search');
   check(await p.locator('#category-name').inputValue()==='Edited unsaved'&&await p.locator('#category-version').inputValue()==='version-1-1','Paging preserves selected edit and version');
   failSave=true;await p.locator('#category-save').click();await p.waitForFunction(()=>document.getElementById('category-message').textContent.includes('QA stale version'));
   check(posts.at(-1).body.version==='version-1-1'&&await p.locator('#category-name').inputValue()==='Edited unsaved','Conflict preserves edit and sends selected version');
   check(posts.at(-1).method==='POST'&&posts.at(-1).csrf==='qa-token','Save keeps POST and CSRF');
   failSave=false;await p.locator('#category-save').click();await p.waitForFunction(()=>document.getElementById('category-message').textContent.includes('Đã lưu'));
   check(await p.locator('#category-id').inputValue()===''&&await p.locator('#category-version').inputValue()==='','Successful save resets stale editor');
   await p.locator('#category-list button').first().click();check((await p.locator('#category-version').inputValue()).startsWith('version-2-'),'Reselect reads fresh version');
   failRead=true;const beforeSave=posts.length;await p.locator('#category-save').click();await p.waitForFunction(()=>document.getElementById('category-message').textContent.includes('Đã lưu danh mục, nhưng'));
   check(posts.length===beforeSave+1&&await p.locator('#category-id').inputValue()===''&&await p.locator('#category-version').inputValue()==='','Saved but reload failed clears editor and does not resend');
   failRead=false;await p.locator('#category-search-form button').click();await ready('Trang 1/3');
   failRead=true;await p.locator('#category-next').click();await p.waitForFunction(()=>document.getElementById('category-message').textContent.includes('QA list unavailable'));
   check(await p.locator('#category-list button').count()===0&&await p.locator('#category-search').isEnabled(),'Read failure shows error and allows explicit search');
   failRead=false;clamp=true;await p.locator('#category-search-form button').click();await ready('Trang 1/3');await p.locator('#category-next').click();await ready('Trang 1/3');
   check(await p.locator('#category-prev').isDisabled(),'Server clamp synchronized');clamp=false;
   for(const width of [1366,768,390]){await p.setViewportSize({width,height:1000});await p.locator('#category-next').scrollIntoViewIfNeeded();check(await p.locator('#category-next').evaluate(n=>{const r=n.getBoundingClientRect();return r.left>=0&&r.right<=innerWidth&&r.bottom<=innerHeight&&document.documentElement.scrollWidth<=innerWidth;}),`Pager reachable ${width}`);}
   await p.screenshot({path:'.playwright-cli/phase11-category-pagination-390.png',fullPage:true});
   hold=true;await p.locator('#category-next').click();await p.waitForFunction(()=>document.getElementById('category-list').hasAttribute('aria-busy'));
   check(await p.locator('#category-save').isDisabled(),'Read busy prevents editing/submitting');
   await p.locator('#category-close').click();await manage.click();await ready('Trang 1/3');release();await p.waitForTimeout(150);
   check((await p.locator('#category-page').textContent()).includes('Trang 1/3'),'Closed dialog response cannot overwrite reopened list');
   empty=true;await p.locator('#category-search').fill('none');await p.locator('#category-search-form button').click();await ready('Trang 1/1');
   check(await p.locator('#category-list button').count()===0&&await p.locator('#category-next').isDisabled(),'Empty search result');
   check(reads.every(r=>r.method==='GET'),'List and search never POST');
  }finally{if(release)release();await context.close();}
 }
 check(errors.length===0,'No JS runtime errors');return {checks:checks.length,results:checks,mode:'static/API mock, no DB'};
}
