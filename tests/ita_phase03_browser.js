async (page) => {
 const browser=page.context().browser(),results=[],errors=[];
 const check=(value,label)=>{if(!value)throw new Error(label);results.push(label);};
 async function scenario(role){
  const context=await browser.newContext({viewport:{width:1366,height:1000}}),p=await context.newPage();
  const posts=[],queries=[];let rejectSave=false,categoryFailure=false;
  let categories=[{id:2,code:'PC',name:'Máy tính',status:'active',version:'c1'}];
  let fields={id:7,asset_code:'QA-7',name:'Thiết bị <script> QA',category_id:2,department_id:10,brand:'QA',model:'',serial_no:'',specs:'QA',os:'',purchase_date:'2026-10-01',purchase_price:'0.00',warranty_end:'2027-10-01',vendor:'',location:'QA',status_reason:''};
  let asset={id:7,code:fields.asset_code,name:fields.name,serial:'QA',category:'Máy tính',department:'QA Department',status:'in_use',warranty:'01/10/2027',specs:'QA',location:'QA',fields,version:'v1',history:[],history_has_more:false};
  const json=(route,data,status=200)=>route.fulfill({status,contentType:'application/json',body:JSON.stringify(data)});
  await context.route('**/*',async route=>{
   const request=route.request(),url=new URL(request.url());
   if(url.hostname!=='127.0.0.1')return route.abort();
   if(request.isNavigationRequest()){
    const response=await route.fetch();const html=(await response.text()).replace('data-mode="mock"','data-mode="live"');
    return route.fulfill({response,body:html});
   }
   if(url.pathname!=='/admin.php')return route.continue();
   const action=url.searchParams.get('act');
   if(action==='categories')return categoryFailure?json(route,{success:false,message:'QA category unavailable'},503):json(route,{success:true,data:categories});
   if(action==='asset')return json(route,{success:true,data:asset});
   if(action==='data'){
    queries.push(Object.fromEntries(url.searchParams));
    return json(route,{success:true,data:{assets:[asset],departments:[{value:'10',label:'QA Department'}],pagination:{page:1,per_page:5,total:1},user:{id:111,role,display_name:'QA',role_label:role},summary:{total:1,by_status:{in_use:1,maintenance:0,broken:0,retired:0},by_department:[{department:'QA Department',count:1}],alerts:[],alerts_total:0}}});
   }
   if(action==='asset-save'){
    const body=request.postDataJSON();posts.push(body);
    if(rejectSave)return json(route,{success:false,message:'Mã tài sản đã tồn tại.'},409);
    asset={...asset,version:'v'+(posts.length+1),history:[{id:1,user_id:111,action:'asset.'+body.operation,before_json:null,after_json:JSON.stringify({name:body.name||asset.name}),created_at:'2026-10-19 01:00:00'}]};
    if(['create','update'].includes(body.operation)){
     fields={...fields,...body,category_id:Number(body.category_id),department_id:Number(body.department_id)};
     asset={...asset,name:fields.name,code:fields.asset_code,fields};
    }
    if(body.operation==='status')asset.status=body.status;
    return json(route,{success:true,data:{id:7}});
   }
   if(action==='category-save'){
    const body=request.postDataJSON();categories=[{...categories[0],...body,id:2,version:'c2'}];
    return json(route,{success:true,data:{id:2}});
   }
   return json(route,{success:false,message:'Unexpected mock API'},404);
  });
  p.on('pageerror',e=>errors.push(e.message));await p.goto('http://127.0.0.1:18766/#assets');
  await p.locator('#asset-rows tr').first().waitFor();await p.waitForFunction(()=>!!window.itaLiveContext);
  return {p,context,posts,queries,reject:value=>rejectSave=value,inactive:()=>categories[0].status='inactive',failCategories:()=>categoryFailure=true};
 }
 async function details(p){await p.locator('#asset-rows button').first().click();await p.locator('#asset-dialog').waitFor({state:'visible'});}
 async function saved(p){await p.waitForFunction(()=>!document.getElementById('asset-editor').open);await p.locator('#asset-result').filter({hasText:'Đã lưu'}).waitFor();}
 const admin=await scenario('admin_it');
 try{
  const p=admin.p;
  for(const width of [1366,768,390]){
   await p.setViewportSize({width,height:1000});await p.getByRole('button',{name:'Thêm tài sản',exact:true}).click();
   check(await p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),`Page no overflow ${width}`);
   check(await p.locator('#asset-editor').evaluate(n=>n.getBoundingClientRect().width<=innerWidth),`Editor fits ${width}`);
   await p.locator('#edit-asset_code').fill('QA-NEW');await p.locator('#edit-name').fill('QA <img src=x onerror=alert(1)>');
   await p.locator('#edit-category_id').selectOption('2');await p.locator('#edit-department_id').selectOption('10');await p.locator('#edit-purchase_price').fill('0.00');
   if(width===390)await p.screenshot({path:'.playwright-cli/phase03-editor-390.png'});
   await p.locator('#asset-editor-save').click();await saved(p);
   const body=admin.posts.at(-1);check(body.operation==='create'&&body.department_id==='10'&&body.purchase_price==='0.00',`Create form payload ${width}`);
  }
  await p.setViewportSize({width:1366,height:1000});await details(p);
  check(await p.locator('#asset-details img').count()===0,'Detail renders untrusted name as text');
  await p.getByRole('tab',{name:'Lịch sử',exact:true}).click();check(await p.locator('.asset-history details').count()===1&&await p.locator('.asset-history').isVisible(),'Asset history tab rendered');await p.getByRole('tab',{name:'Thông tin',exact:true}).click();
  await p.getByRole('button',{name:'Sửa thông tin',exact:true}).click();
  await p.locator('#edit-location').fill('New location');await p.locator('#asset-editor-save').click();await saved(p);
  check(admin.posts.at(-1).operation==='update'&&admin.posts.at(-1).version.startsWith('v'),'Edit includes record version');
  admin.inactive();await details(p);await p.getByRole('button',{name:'Sửa thông tin',exact:true}).click();
  check(await p.locator('#edit-category_id').inputValue()==='2','Current inactive category preserved on edit');await p.locator('#asset-editor-cancel').click();
  await p.getByRole('button',{name:'Thêm tài sản',exact:true}).click();
  await p.locator('#asset-editor').waitFor({state:'visible'});
  check(await p.locator('#edit-category_id option[value="2"]').count()===0,'Inactive category excluded from create');await p.locator('#asset-editor-cancel').click();
  await details(p);await p.getByRole('button',{name:'Đổi trạng thái',exact:true}).click();await p.locator('#edit-reason').fill('QA maintenance');
  const beforeStatus=admin.posts.length;await p.locator('#asset-editor-save').click();
  check(admin.posts.length===beforeStatus&&await p.locator('#edit-status').evaluate(n=>!n.checkValidity()),'Empty status blocked before API request');
  await p.locator('#edit-status').selectOption('maintenance');await p.locator('#asset-editor-save').click();await saved(p);
  check(admin.posts.at(-1).operation==='status'&&admin.posts.at(-1).reason==='QA maintenance','Status form submits reason');
  admin.reject(true);await details(p);await p.getByRole('button',{name:'Ghi chú',exact:true}).click();await p.locator('#edit-reason').fill('QA note');await p.locator('#asset-editor-save').click();
  await p.locator('#asset-edit-message').filter({hasText:'Mã tài sản đã tồn tại'}).waitFor();check(await p.locator('#asset-editor').isVisible()&&await p.locator('#asset-editor-save').isEnabled(),'Server error stays visible with retry enabled');
  await p.locator('#asset-editor-cancel').click();admin.reject(false);
  await details(p);await p.getByRole('button',{name:'Xóa mềm',exact:true}).click();await p.locator('#edit-reason').fill('QA delete');await p.locator('#asset-editor-save').click();await p.locator('#confirm-cancel').click();check(await p.locator('#asset-editor').isVisible(),'Delete cancel preserves editor');
  await p.locator('#asset-editor-save').click();await p.locator('#confirm-accept').click();await saved(p);check(admin.posts.at(-1).operation==='delete','Confirmed soft delete submits');
  await p.locator('#filter-category_id').selectOption('2');await p.locator('#filter-purchase_year').fill('2026');await p.locator('#filter-purchase_year').dispatchEvent('change');await p.waitForTimeout(300);
  check(admin.queries.some(q=>q.category_id==='2'&&q.purchase_year==='2026'),'Advanced filters sent to API');
  await p.getByRole('button',{name:'Quản lý loại tài sản',exact:true}).click();await p.locator('#category-list button').first().click();await p.locator('#category-name').fill('QA Category');await p.locator('#category-save').click();await p.locator('#category-message').filter({hasText:'Đã lưu'}).waitFor();
  check(await p.locator('#category-list').textContent().then(t=>t.includes('QA Category')),'Category submit refreshes list');
 }finally{await admin.context.close();}
 for(const role of ['system_manager','support_technician']){
  const session=await scenario(role);try{
   const p=session.p;check(await p.getByRole('button',{name:'Quản lý loại tài sản',exact:true}).isHidden(),`${role} category manager hidden`);
   await details(p);check(await p.getByRole('button',{name:'Xóa mềm',exact:true}).count()===0,`${role} delete action absent`);
   if(role==='support_technician'){
    check(await p.getByRole('button',{name:'Sửa thông tin',exact:true}).count()===0,'Support general edit absent');
    session.failCategories();
    await p.getByRole('button',{name:'Đổi trạng thái',exact:true}).click();await p.locator('#asset-editor').waitFor({state:'visible'});check(await p.locator('#edit-status option[value=retired]').count()===0,'Support retired action absent');await p.locator('#asset-editor-cancel').click();
    await details(p);await p.getByRole('button',{name:'Ghi chú',exact:true}).click();await p.locator('#asset-editor').waitFor({state:'visible'});
    await p.locator('#edit-reason').fill('QA note without categories');await p.locator('#asset-editor-save').click();await saved(p);
    check(session.posts.at(-1).operation==='note','Support note works without loading category API');
   }else{
    await p.getByRole('button',{name:'Sửa thông tin',exact:true}).click();await p.locator('#asset-editor').waitFor({state:'visible'});check(await p.locator('#edit-department_id option[value="10"]').count()===1,'Manager edit department comes from scoped API');await p.locator('#asset-editor-cancel').click();
   }
  }finally{await session.context.close();}
 }
 check(errors.length===0,'No JS runtime errors');return {result:'PASS',checks:results.length,results,scope:'Browser with mocked APIs only; no DB/host'};
}
