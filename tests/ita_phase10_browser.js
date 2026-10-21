async(page)=>{
 const checks=[],errors=[];const check=(ok,label)=>{if(!ok)throw Error(label);checks.push(label);};
 for(const role of ['admin_it','system_manager','support_technician']){
  const context=await page.context().browser().newContext({viewport:{width:1366,height:1000}}),p=await context.newPage(),requests=[];let bad='',failure=false,paginating=false,shrunk=false,delaySupport=false,releaseSupport;
  const asof='2026-10-21T10:00:00+07:00';
  const group={id:'1',name:'Máy tính',total:3,by_status:{in_use:1,maintenance:1,broken:0,retired:1},analyzed:2,failed_assets:1,fault_rate:.5,replace:1,missing_scores:0};
  p.on('pageerror',e=>errors.push(e.message));
  await context.route('**/*',async route=>{
   const req=route.request(),u=new URL(req.url());if(u.hostname!=='127.0.0.1')return route.abort();
   if(req.isNavigationRequest()){const r=await route.fetch();return route.fulfill({response:r,body:(await r.text()).replace('data-mode="mock"','data-mode="live"').replace('name="ita-csrf" content=""','name="ita-csrf" content="csrf-mock"')});}
   if(u.pathname!=='/admin.php')return route.continue();
   const act=u.searchParams.get('act');requests.push({act,page:u.searchParams.get('page'),method:req.method(),payload:req.postDataJSON?.(),csrf:req.headers()['x-csrf-token']});
   const json=(data,status=200,message='')=>route.fulfill({status,contentType:'application/json',body:JSON.stringify({success:status===200,data,message})});
   if(act==='data')return json({assets:[],pagination:{page:1,per_page:5,total:0},departments:[{value:'10',label:'CNTT'}],user:{id:1,role,display_name:'Mock',role_label:role},summary:{total:0,by_status:{},by_department:[],alerts:[],alerts_total:0}});
   if(act==='categories')return json([]);
   if(failure)return route.fulfill({status:503,contentType:'application/json',body:JSON.stringify({success:false,message:'Lỗi thử nghiệm'})});
   if(act==='dashboard'||act==='dashboard-charts'){
    if(role==='support_technician')return json(null,403,'Không có quyền');
    return json({as_of:asof,config:{using_defaults:true},group:'category',groups:[group],alerts_total:4,summary:{total:3,managed:2,analyzed:2,failed_assets:1,fault_rate:.5,replace:1},replacements:[{asset_code:'MOCK-1',name:'<img src=x onerror=alert(1)>',score_display:'1.250',tier:'replace'}]});
   }
   if(act==='alerts')return json({as_of:asof,page:1,total:1,per_page:20,active_total:4,rows:[{entity_type:'asset',entity_id:1,level:'critical',code:'MOCK-1',name:'Thiết bị giả',title:'Quá hạn bảo trì'}]});
   if(act==='report-preview'){const type=u.searchParams.get('report'),total=paginating?(shrunk?21:41):1,data={report:type,as_of:asof,source:'Số liệu hiện tại',headers:['Mã','Tên'],rows:[['MOCK-1','=HYPERLINK giả']],data_count:total,missing:0,total,page:Math.min(Number(u.searchParams.get('page')||1),Math.ceil(total/20)),per_page:20,token:'mock-token'};if(delaySupport&&role==='support_technician')return new Promise(resolve=>{releaseSupport=()=>json(data).then(resolve);});return json(data);}
   if(act==='report-export'){
    if(bad==='audit')return json(null,503,'Không ghi được audit');
    if(bad==='html')return route.fulfill({contentType:'text/html',body:'<html>Đăng nhập</html>'});
    if(bad==='signature')return route.fulfill({contentType:'application/pdf',body:'not pdf'});
    const format=req.postDataJSON().format;return route.fulfill({contentType:format==='pdf'?'application/pdf':'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',body:format==='pdf'?'%PDF-1.4 MOCK':'PK\u0003\u0004 MOCK'});
   }
   return json([]);
  });
  try{
   await p.goto('http://127.0.0.1:18766/');await p.locator('#dash-message').filter({hasText:'Đã tải số liệu'}).waitFor();
   if(role==='support_technician'){
    check((await p.locator('#dash-heading').textContent())==='Bảng bảo trì','Support maintenance only');check(await p.locator('#dash-export').isHidden(),'Support no export');check(await p.locator('[data-view="reports"]').isHidden()&&await p.locator('#notification-button').isHidden(),'Support no reports/bell');check(requests.some(r=>r.act==='report-preview')&&!requests.some(r=>r.act==='dashboard'),'Support requests safe maintenance endpoint');
    await p.evaluate(()=>location.hash='reports');await p.locator('#dash-message').filter({hasText:'Hỗ trợ chỉ xem'}).waitFor();check(await p.locator('#dash-export').isHidden(),'Support direct URL no export');
    delaySupport=true;await p.evaluate(()=>location.hash='overview');for(let i=0;i<50&&!releaseSupport;i++)await p.waitForTimeout(20);check(!!releaseSupport,'Delayed maintenance request captured');await p.evaluate(()=>location.hash='reports');await p.locator('#dash-message').filter({hasText:'Hỗ trợ chỉ xem'}).waitFor();await releaseSupport();await p.waitForTimeout(100);check((await p.locator('#dash-message').textContent()).includes('Hỗ trợ chỉ xem'),'Late response cannot overwrite denied view');continue;
   }
   check(await p.locator('#dash-output .dash-chart svg').count()===3,'Three real chart SVG '+role);
   check((await p.locator('#dash-output').textContent()).includes('1.250'),'Score >1 displayed '+role);
   check((await p.locator('#dash-output').textContent()).includes('Đang dùng giá trị mặc định'),'Default note '+role);
   check(await p.locator('#dash-output img').count()===0,'Server text escaped '+role);
   check((await p.locator('#dash-time').textContent()).includes('10:00'),'Shared VN timestamp '+role);
   const statusChart=await p.locator('.dash-chart').first().evaluate(el=>echarts.getInstanceByDom(el).getOption().series.length);check(statusChart===4,'Four stacked status series '+role);
   for(const width of [1366,768,390]){await p.setViewportSize({width,height:1000});await p.waitForTimeout(80);check(await p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1),'Dashboard bounds '+width+' '+role);await p.screenshot({path:`.playwright-cli/phase10-dashboard-live-${role}-${width}.png`,fullPage:true});}await p.setViewportSize({width:1366,height:1000});
   await p.locator('#dash-group').selectOption('year');await p.locator('#dash-filters button').click();await p.locator('#dash-message').filter({hasText:'Đã tải số liệu'}).waitFor();check(await p.locator('.dash-chart svg').count()===3,'No accumulated charts after filter '+role);
   await p.locator('[data-view="alerts"]').click();await p.locator('#dash-message').filter({hasText:'Đã tải số liệu'}).waitFor();check(await p.locator('.dash-chart svg').count()===0,'Dispose on leaving dashboard '+role);check((await p.locator('#dash-output').textContent()).includes('1 kết quả khớp / 4'),'Filtered count distinct bell '+role);
   await p.locator('#dash-level').selectOption('critical');await p.locator('#dash-alert-filters button').click();await p.locator('#dash-message').filter({hasText:'Đã tải số liệu'}).waitFor();check((await p.locator('#nav-alert-count').textContent())==='4','Bell unchanged by severity '+role);
   await p.locator('[data-view="reports"]').click();await p.locator('#dash-message').filter({hasText:'Đã tải số liệu'}).waitFor();await p.locator('#dash-export').waitFor({state:'visible'});check(await p.locator('#dash-export').isVisible(),'Report preview enables export '+role);
   paginating=true;await p.locator('#dash-refresh').click();await p.locator('#dash-page').filter({hasText:'Trang 1 / 3'}).waitFor();await p.locator('#dash-next').click();await p.locator('#dash-page').filter({hasText:'Trang 2 / 3'}).waitFor();shrunk=true;await p.locator('#dash-next').click();await p.locator('#dash-page').filter({hasText:'Trang 2 / 2'}).waitFor();check(await p.locator('#dash-next').isDisabled(),'Server clamped page disables Next '+role);await p.locator('#dash-previous').click();await p.locator('#dash-page').filter({hasText:'Trang 1 / 2'}).waitFor();check(requests.filter(r=>r.act==='report-preview').at(-1).page==='1','Previous uses returned page after clamp '+role);paginating=false;await p.locator('#dash-refresh').click();await p.locator('#dash-page').filter({hasText:'Trang 1 / 1'}).waitFor();
   for(const mode of ['html','signature','audit']){bad=mode;await p.locator('#dash-pdf').click();await p.locator('#dash-message').filter({hasText:'Không tự gửi lại'}).waitFor();check(!(await p.locator('#dash-message').textContent()).includes('Đã nhận tệp'),'Reject '+mode+' '+role);}
   bad='';const dl=p.waitForEvent('download');await p.locator('#dash-xlsx').click();await dl;check((await p.locator('#dash-message').textContent()).includes('Đã nhận tệp'),'Valid Excel signature accepted '+role);
   const post=requests.filter(r=>r.act==='report-export').at(-1);check(post.method==='POST'&&post.csrf==='csrf-mock'&&post.payload.token==='mock-token'&&post.payload.as_of===asof,'POST CSRF timestamp token '+role);
   failure=true;await p.locator('#dash-refresh').click();await p.locator('#dash-message').filter({hasText:'Lỗi thử nghiệm'}).waitFor();check((await p.locator('#dash-time').textContent()).includes('10:00')&&await p.locator('#dash-export').isHidden(),'Failure keeps old timestamp, disables export '+role);failure=false;
   for(const width of [1366,768,390]){await p.setViewportSize({width,height:1000});check(await p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1),'Bounds '+width+' '+role);await p.screenshot({path:`.playwright-cli/phase10-live-${role}-${width}.png`,fullPage:true});}
   check(!requests.some(r=>r.method==='POST'&&r.act!=='report-export'),'Only explicit export writes '+role);
  }finally{await context.close();}
 }
 check(errors.length===0,'No runtime errors');return {checks:checks.length,results:checks,scope:'Live layout/JS with API mock; no DB'};
}
