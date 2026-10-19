async(page)=>{
 const browser=page.context().browser(),checks=[],errors=[];
 const check=(ok,label)=>{if(!ok)throw new Error(label);checks.push(label);};
 async function scenario(role){
  const context=await browser.newContext({viewport:{width:1366,height:1000}}),p=await context.newPage();
  let invalid=false,completed=false,ambiguous=false,expired=false,uploaded=0,confirms=0;
  const requests=[];
  const json=(route,data,status=200)=>route.fulfill({status,contentType:'application/json',body:JSON.stringify({success:status===200,data,message:status===410?'Bản xem trước đã hết hạn.':''})});
  p.on('pageerror',e=>errors.push(e.message));
  await context.route('**/*',async route=>{
   const req=route.request(),url=new URL(req.url());
   if(url.hostname!=='127.0.0.1')return route.abort();
   if(req.isNavigationRequest()){
    const response=await route.fetch();return route.fulfill({response,body:(await response.text()).replace('data-mode="mock"','data-mode="live"').replace('name="ita-csrf" content=""','name="ita-csrf" content="qa-token"')});
   }
   if(url.pathname!=='/admin.php')return route.continue();
   const action=url.searchParams.get('act');requests.push({action,url,req});
   if(action==='data')return json(route,{assets:[],departments:[{value:'10',label:'QA'}],pagination:{page:1,total:0,per_page:5},user:{id:111,role,display_name:'QA',role_label:role},summary:{total:0,by_status:{},by_department:[],alerts:[],alerts_total:0}});
   if(action==='categories')return json(route,[]);
   if(['import-template','import-errors','asset-export'].includes(action))return route.fulfill({contentType:'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',body:Buffer.from('QA binary transport mock; real XLSX tested in PHP')});
   if(action==='import-confirm'){
    confirms++;completed=true;
    if(ambiguous){ambiguous=false;return route.abort();}
    return json(route,{batch_id:9,status:'completed',imported_rows:24,already_completed:false});
   }
   if(['import-upload','import-preview'].includes(action)){
    if(action==='import-upload'){uploaded++;completed=false;}
    if(expired)return json(route,null,410);
    const current=Number(url.searchParams.get('page')||1),start=(current-1)*20;
    return json(route,{batch_id:9,filename:'qa.xlsx',status:completed?'completed':'validated',total_rows:24,ok_rows:invalid?23:24,error_rows:invalid?1:0,confirm_token:'qa-confirm-token',page:current,per_page:20,expires_at:'2099-01-01',rows:Array.from({length:current===1?20:4},(_,i)=>({line:start+i+2,asset_code:`QA-${start+i}`,name:'QA <img src=x onerror=alert(1)>',error:invalid&&i===0?'Mã trùng':''}))});
   }
   return json(route,null,404);
  });
  await p.goto('http://127.0.0.1:18766/#imports');await p.waitForFunction(()=>!!window.itaLiveContext);
  return {context,p,requests,invalid:()=>invalid=true,ambiguous:()=>ambiguous=true,expire:()=>expired=true,counts:()=>({uploaded,confirms})};
 }
 const admin=await scenario('admin_it');
 try{
  const p=admin.p;
  check(!(await p.locator('#import-mode').textContent()).includes('MOCK'),'Live mode is clearly labeled');
  check(await p.locator('#import-nav .nav-count').count()===0,'Live navigation has no MOCK badge');
  check(await p.locator('.import-demo-controls').isHidden(),'Mock scenario controls hidden in live mode');
  const template=p.waitForEvent('download');await p.locator('#import-template').click();check((await template).suggestedFilename()==='ita-assets-template.xlsx','Template downloads as XLSX');
  await p.locator('#import-preview-tab').click();
  await p.locator('#import-file').setInputFiles({name:'bad.txt',mimeType:'text/plain',buffer:Buffer.from('qa')});
  check(await p.locator('#import-upload').isDisabled(),'Invalid extension cannot upload');
  await p.locator('#import-file').setInputFiles({name:'qa.xlsx',mimeType:'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',buffer:Buffer.from('transport mock')});
  await p.locator('#import-guide-tab').click();await p.locator('#import-preview-tab').click();
  check(await p.locator('#import-file').evaluate(n=>n.files[0].name)==='qa.xlsx','Live selected file retained across tabs');
  check(admin.counts().uploaded===0,'File selection does not silently upload');
  await p.locator('#import-upload').click();await p.locator('#import-confirm').waitFor({state:'visible'});await p.waitForFunction(()=>!document.getElementById('import-confirm').disabled);
  const upload=admin.requests.find(r=>r.action==='import-upload');
  check(upload.req.headers()['x-csrf-token']==='qa-token'&&upload.req.headers()['content-type'].includes('multipart/form-data'),'Upload sends multipart and CSRF token');
  check(await p.locator('#import-rows tr').count()===20&&await p.locator('#import-rows img').count()===0,'Preview pagination and text escaping');
  check(admin.counts().confirms===0,'Preview never automatically confirms');
  await p.locator('#import-next').click();await p.waitForFunction(()=>document.getElementById('import-page-summary').textContent.includes('Trang 2/2'));
  check(await p.locator('#import-rows tr').count()===4&&await p.locator('#import-next').isDisabled(),'Server preview next page');
  for(const width of [1366,768,390]){
   await p.setViewportSize({width,height:1000});check(await p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),`Live import fits ${width}`);
   check(await p.locator('#import-upload').evaluate(n=>{const r=n.getBoundingClientRect();return r.left>=0&&r.right<=innerWidth;}),`Upload control inside viewport ${width}`);
   if(width===1366)await p.screenshot({path:'.playwright-cli/phase04-live-1366.png',fullPage:true});
   if(width===390)await p.screenshot({path:'.playwright-cli/phase04-live-390.png',fullPage:true});
  }
  await p.setViewportSize({width:1366,height:1000});
  await p.locator('#import-confirm').click();await p.locator('#import-summary').filter({hasText:'Đã nhập thành công'}).waitFor();
  check(await p.locator('#import-confirm').isDisabled(),'Successful batch cannot be submitted again');
  const confirm=admin.requests.find(r=>r.action==='import-confirm').req.postDataJSON();
  check(confirm.batch_id===9&&confirm.confirm_token==='qa-confirm-token'&&Object.keys(confirm).length===2,'Confirm sends batch token, never trusted browser rows');
  await p.locator('#import-upload').click();await p.waitForFunction(()=>!document.getElementById('import-confirm').disabled);
  admin.ambiguous();await p.locator('#import-confirm').click();await p.locator('#import-summary').filter({hasText:'Kiểm tra trạng thái lô'}).waitFor();
  check(await p.locator('#import-confirm').isDisabled()&&await p.locator('#import-upload').isDisabled()&&await p.locator('#import-file').isDisabled(),'Lost confirm response blocks blind retry and re-upload');
  await p.locator('#import-check').click();await p.locator('#import-summary').filter({hasText:'đã hoàn tất'}).waitFor();
  check(await p.locator('#import-confirm').isDisabled()&&admin.counts().confirms===2,'Status check recovers completed batch without second write');
  admin.invalid();await p.locator('#import-upload').click();await p.locator('#import-summary').filter({hasText:'1 lỗi'}).waitFor();
  check(await p.locator('#import-confirm').isDisabled()&&await p.locator('#import-errors').isEnabled(),'Invalid batch blocks confirm and enables error workbook');
  const errorFile=p.waitForEvent('download');await p.locator('#import-errors').click();check((await errorFile).suggestedFilename()==='ita-import-errors.xlsx','Error workbook download');
  admin.expire();await p.locator('#import-check').click();await p.locator('#import-summary').filter({hasText:'hết hạn'}).waitFor();
  check(await p.locator('#import-confirm').isDisabled(),'Expired batch stays blocked');
  await p.locator('#import-export-tab').click();await p.locator('#import-export-status').selectOption('broken');
  const exported=p.waitForEvent('download');await p.locator('#import-export-download').click();check((await exported).suggestedFilename()==='ita-assets.xlsx','Asset export download');
  check(admin.requests.find(r=>r.action==='asset-export').url.searchParams.get('status')==='broken','Export sends selected status filter');
  check(await p.locator('#import-summary').isVisible(),'Download/status feedback visible on export tab');
 }finally{await admin.context.close();}
 for(const role of ['system_manager','support_technician']){
  const test=await scenario(role);
  try{check(await test.p.locator('#import-workspace').isVisible()===(role==='system_manager'),`Import role UI ${role}`);}finally{await test.context.close();}
 }
 check(errors.length===0,'No browser JavaScript runtime errors');
 return {result:'PASS (API mocked; real XLSX tested in PHP)',checks};
}
