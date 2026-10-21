async(page)=>{
 const checks=[];const check=(ok,label)=>{if(!ok)throw Error(label);checks.push(label);};
 for(const role of ['admin_it','system_manager','support_technician']){
  const context=await page.context().browser().newContext({viewport:{width:1366,height:1000}}),p=await context.newPage(),posts=[],errors=[];let bad=false;
  p.on('pageerror',e=>errors.push(e.message));
  await context.route('**/*',async route=>{const req=route.request(),u=new URL(req.url());if(u.hostname!=='127.0.0.1')return route.abort();if(req.isNavigationRequest()){const response=await route.fetch();return route.fulfill({response,body:(await response.text()).replace('data-mode="mock"','data-mode="live"').replace('name="ita-csrf" content=""','name="ita-csrf" content="csrf-mock"')});}if(u.pathname!=='/admin.php')return route.continue();const json=data=>route.fulfill({contentType:'application/json',body:JSON.stringify({success:true,data,message:''})});const act=u.searchParams.get('act');if(act==='data')return json({assets:[],pagination:{page:1,per_page:5,total:0},departments:[],user:{id:1,role,display_name:'Mock'},summary:{total:0,by_status:{},by_department:[],alerts:[],alerts_total:0}});if(act==='report-preview')return json({report:'inventory',as_of:'2026-10-21T10:00:00+07:00',source:'Số liệu hiện tại',headers:['Mã','Tên'],rows:[['MOCK-1','=HYPERLINK giả']],data_count:1,missing:0,total:1,page:1,per_page:20,token:'mock-token'});if(act==='report-export'){posts.push({payload:req.postDataJSON(),csrf:req.headers()['x-csrf-token']});return bad?route.fulfill({contentType:'text/html',body:'Đăng nhập'}):route.fulfill({contentType:'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',body:'PK\u0003\u0004 MOCK'});}return json([]);});
  try{
   await p.goto('http://127.0.0.1:18766/#reports');if(role==='support_technician'){await p.locator('#dash-message').filter({hasText:'Hỗ trợ chỉ xem'}).waitFor();check(await p.locator('#dash-export').isHidden(),'Support direct report URL denied');continue;}
   await p.locator('#dash-export').waitFor({state:'visible'});check((await p.locator('#dash-output').textContent()).includes('=HYPERLINK giả'),'Preview text preserved '+role);
   bad=true;await p.locator('#dash-xlsx').click();await p.locator('#dash-message').filter({hasText:'Không tự gửi lại'}).waitFor();check(!(await p.locator('#dash-message').textContent()).includes('Đã nhận tệp'),'HTML200 rejected '+role);bad=false;
   const dl=p.waitForEvent('download');await p.locator('#dash-xlsx').click();await dl;check(posts.at(-1).csrf==='csrf-mock'&&posts.at(-1).payload.token==='mock-token','POST CSRF/token '+role);
   for(const width of [1366,768,390]){await p.setViewportSize({width,height:1000});check(await p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1),'Report bounds '+width+' '+role);}
   check(errors.length===0,'No report runtime errors '+role);
  }finally{await context.close();}
 }
 return {checks:checks.length,results:checks,scope:'Report live UI/API mock, no DB'};
}
