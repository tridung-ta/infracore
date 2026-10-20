(() => {
 'use strict';
 const $=id=>document.getElementById(id),live=document.body.dataset.mode==='live';
 let current=null,page=1,total=0,busy=false,started=false,timer,sequence=0,stale=false;
 const admin=()=>window.itaLiveContext?.user.role==='admin_it';
 const types={per_device:'Theo máy',per_user:'Theo người',per_department:'Theo bộ phận'};
 function el(tag,text){const node=document.createElement(tag);node.textContent=text;return node;}
 function message(text,error=false){const n=$('license-message');n.textContent=text;n.setAttribute('role',error?'alert':'status');n.classList.toggle('danger-text',error);}
 function hideKey(){clearTimeout(timer);$('license-key-output').textContent='';$('license-key-output').hidden=true;$('license-password').value='';$('license-key-input').value='';sequence++;}
 async function api(action,query={},body){
  const controller=new AbortController(),timeout=setTimeout(()=>controller.abort(),15000);
  try{
   const response=await fetch(`/admin.php?${new URLSearchParams({op:'ita',act:action,...query})}`,{method:body?'POST':'GET',cache:'no-store',credentials:'same-origin',signal:controller.signal,
    headers:body?{'Content-Type':'application/json','X-CSRF-Token':document.querySelector('meta[name=ita-csrf]').content}:{},body:body?JSON.stringify(body):undefined});
   const result=await response.json();
   if(!response.ok||!result.success)throw new Error(result.message||'Yêu cầu bị từ chối.');
   return result.data;
  }catch(error){if(error instanceof TypeError||error.name==='AbortError')throw new Error(body?'Chưa xác nhận kết quả. Tải lại hồ sơ trước khi thao tác lại.':'Không tải được dữ liệu. Hãy thử lại.');throw error;}
  finally{clearTimeout(timeout);}
 }
 function controls(){
  $('license-new').disabled=busy;
  $('license-live').querySelectorAll('button').forEach(n=>n.disabled=busy);
  $('license-prev').disabled=busy||page<=1;$('license-next').disabled=busy||page*20>=total;
  $('license-history-prev').disabled=busy||!current||current.page<=1;
  $('license-history-next').disabled=busy||!current||current.page*20>=current.total;
  if(stale)$('license-record').querySelectorAll('button[type=submit],button[data-release]').forEach(n=>n.disabled=true);
 }
 async function task(callback){if(busy)return;busy=true;controls();$('license-live').setAttribute('aria-busy','true');try{await callback();}catch(error){message(error.message,true);}finally{busy=false;controls();$('license-live').removeAttribute('aria-busy');}}
 function warnings(row){const a=row.alerts;return [a.overallocated?'Vượt số chỗ':'',a.expiry_level==='critical'?(a.days_remaining<0?'Đã hết hạn':'Hết hạn trong '+a.days_remaining+' ngày'):a.expiry_level==='warning'?'Còn '+a.days_remaining+' ngày':'',row.status==='inactive'?'Ngừng sử dụng':''].filter(Boolean).join(' · ')||'Trong giới hạn';}
 async function listing(){
  const data=await api('licenses',{q:$('license-search').value,page});page=data.page;total=data.total;
  $('license-rows').replaceChildren(...data.rows.map(row=>{
   const tr=el('tr','');for(const text of [row.software_name,`${row.used_seats} / ${row.total_seats}`,row.expire_date||'Không thời hạn',warnings(row)])tr.append(el('td',text));
   const td=el('td',''),button=el('button','Mở hồ sơ');button.type='button';button.className='secondary-button';button.addEventListener('click',()=>task(()=>detail(row.id)));td.append(button);tr.append(td);return tr;
  }));
  if(!data.rows.length){const tr=el('tr',''),td=el('td','Không có giấy phép phù hợp.');td.colSpan=5;tr.append(td);$('license-rows').append(tr);}
  $('license-page').textContent=`Trang ${page} · ${total} giấy phép`;
 }
 function render(){
  const row=current.license;$('license-record').hidden=false;$('license-record-title').textContent=row.software_name;
  $('license-details').replaceChildren();
  for(const [label,value] of [['Hình thức',types[row.license_type]],['Số chỗ',`${row.used_seats} / ${row.total_seats}`],['Key',row.key_mask],['Cảnh báo',warnings(row)]])$('license-details').append(el('dt',label),el('dd',value));
  $('license-editor').hidden=!admin();$('license-reveal').hidden=!admin()||!row.has_key;
  for(const [id,key] of [['name','software_name'],['vendor','vendor'],['type','license_type'],['seats','total_seats'],['start','start_date'],['expire','expire_date'],['cost','cost'],['status','status']])$(`license-${id}`).value=row[key]??'';
  $('license-key-input').value='';$('license-save-over').checked=false;$('license-save-reason').value='';
  $('license-allocate-over').checked=false;$('license-allocate-reason').value='';$('license-target').value='';
  $('license-allocate').hidden=row.status!=='active'||(row.alerts.days_remaining!==null&&row.alerts.days_remaining<0)||(!admin()&&row.license_type==='per_user');
  $('license-allocate-override').hidden=!admin();
  $('license-target-help').textContent=`${types[row.license_type]}: nhập đúng ID đích. Máy và bộ phận phải thuộc phạm vi của bạn. Máy/bộ phận tra ở trang Tài sản; tài khoản do Admin IT xác minh.`;
  $('license-allocations').replaceChildren(...current.allocations.map(a=>{
   const tr=el('tr',''),target=a.asset_id?`Máy #${a.asset_id}`:a.user_id?`Tài khoản #${a.user_id}`:`Bộ phận #${a.department_id}`;
   for(const text of [target,a.allocated_at,a.released_at||'Đang cấp'])tr.append(el('td',text));
   const td=el('td','');if(!a.released_at){const button=el('button','Thu hồi');button.type='button';button.className='secondary-button';button.dataset.release=a.id;button.addEventListener('click',()=>task(()=>save({operation:'release',allocation_id:a.id})));td.append(button);}tr.append(td);return tr;
  }));
  if(!current.allocations.length){const tr=el('tr',''),td=el('td','Chưa có lượt cấp phát trong phạm vi.');td.colSpan=4;tr.append(td);$('license-allocations').append(tr);}
  $('license-history-page').textContent=`Trang ${current.page} · ${current.total} lượt`;
 }
 async function detail(id,historyPage=1){hideKey();current=await api('license',{id,page:historyPage});stale=false;render();}
 async function save(input){
  hideKey();stale=true;
  const data=await api('license-save',{}, {id:current?.license.id||0,version:current?.license.version||'',...input});
  message('Đã lưu. Đang đọc lại hồ sơ…');
  try{await listing();await detail(data.id);message('Đã cập nhật giấy phép và đọc lại hồ sơ.');dispatchEvent(new Event('ita:refresh'));}
  catch(error){message('Đã lưu, nhưng chưa tải lại được hồ sơ. Hãy tìm và mở lại trước khi tiếp tục.',true);}
 }
 $('license-search-form').addEventListener('submit',e=>{e.preventDefault();task(async()=>{page=1;hideKey();await listing();if(current?.license.id)await detail(current.license.id);});});
 $('license-prev').addEventListener('click',()=>task(async()=>{page--;await listing();}));
 $('license-next').addEventListener('click',()=>task(async()=>{page++;await listing();}));
 $('license-history-prev').addEventListener('click',()=>task(()=>detail(current.license.id,current.page-1)));
 $('license-history-next').addEventListener('click',()=>task(()=>detail(current.license.id,current.page+1)));
 $('license-new').addEventListener('click',()=>{
  if(busy)return;hideKey();current=null;stale=false;$('license-record').hidden=false;$('license-record-title').textContent='Thêm giấy phép';$('license-details').replaceChildren();
  $('license-editor').reset();$('license-editor').hidden=false;$('license-allocate').hidden=true;$('license-reveal').hidden=true;$('license-allocations').replaceChildren();$('license-history-page').textContent='';controls();$('license-name').focus();
 });
 $('license-editor').addEventListener('submit',e=>{e.preventDefault();task(async()=>{
  const fields={operation:'save'};for(const [id,key] of [['name','software_name'],['vendor','vendor'],['type','license_type'],['seats','total_seats'],['start','start_date'],['expire','expire_date'],['cost','cost'],['status','status'],['key-input','license_key']])fields[key]=$(`license-${id}`).value;
  fields.confirm_overage=$('license-save-over').checked;fields.override_reason=$('license-save-reason').value;
  try{await save(fields);}finally{fields.license_key='';$('license-key-input').value='';}
 });});
 $('license-allocate').addEventListener('submit',e=>{e.preventDefault();task(()=>save({operation:'allocate',target_id:$('license-target').value,confirm_overage:$('license-allocate-over').checked,override_reason:$('license-allocate-reason').value}));});
 $('license-reveal').addEventListener('submit',e=>{e.preventDefault();task(async()=>{
  let password=$('license-password').value;hideKey();const token=sequence,id=current.license.id;
  let data;try{data=await api('license-key',{}, {id,password});}finally{password='';$('license-password').value='';}
  if(token!==sequence||location.hash!=='#licenses'||document.hidden)return;
  $('license-key-output').textContent=data.key;data.key='';$('license-key-output').hidden=false;timer=setTimeout(hideKey,10000);message('Key tự ẩn sau 10 giây.');
 });});
 $('license-hide-key').addEventListener('click',hideKey);
 addEventListener('hashchange',hideKey);addEventListener('pagehide',hideKey);addEventListener('blur',hideKey);
 document.addEventListener('visibilitychange',()=>{if(document.hidden)hideKey();});
 function activate(){
  if(location.hash!=='#licenses')return;
  if(!live){message('MOCK · Giấy phép chỉ hoạt động khi đăng nhập hệ thống. Không có key hoặc dữ liệu thật trong bản xem thử.');return;}
  if(!window.itaLiveContext)return;
  const allowed=['admin_it','system_manager'].includes(window.itaLiveContext.user.role);
  $('license-live').hidden=!allowed;$('license-new').hidden=!allowed||!admin();
  if(!allowed){message('Bạn không có quyền quản lý giấy phép.',true);return;}
  if(!started){started=true;task(listing);}
 }
 addEventListener('ita:view',activate);addEventListener('ita:data',activate);activate();
})();
