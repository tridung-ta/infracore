(() => {
 'use strict';
 if(document.body.dataset.mode!=='live')return;
 const $=id=>document.getElementById(id);
 const roles={admin_it:'Admin IT',system_manager:'Quản lý hệ thống',support_technician:'Hỗ trợ kỹ thuật'};
 const state={users:1,filters:{}};let userRequest=0;
 let openedView='';
 let creating=false,createUncertain=false;
 const credentials=['username','fullname','email','password','confirmation'];
 function createMode(active){
  creating=active;$('user-create-fields').hidden=!active;
  for(const key of credentials){const n=$('user-create-'+key);n.disabled=!active;n.required=active;n.value='';}
  $('access-enabled').disabled=active;
  $('access-save').textContent=active?'Tạo tài khoản & cấp quyền':'Lưu quyền truy cập';
 }
 function clearPasswords(){for(const key of ['password','confirmation'])$('user-create-'+key).value='';}
 function adminStatus(message){const node=$('admin-message');node.textContent=message;window.ItaUi.autoDismiss(node,true);}
 function el(tag,text){const node=document.createElement(tag);if(text!==undefined)node.textContent=text;return node;}
 const feedback=el('p');feedback.id='access-message';feedback.className='admin-message';feedback.hidden=true;
 $('access-form').insertBefore(feedback,$('access-form').lastElementChild);
 function announce(node,message,error=false){
  node.textContent=message;node.hidden=false;node.tabIndex=-1;
  node.setAttribute('role',error?'alert':'status');
  node.classList.toggle('danger-text',error);node.classList.toggle('success-text',!error);
  window.ItaUi.autoDismiss(node,error);
  node.focus({preventScroll:true});node.scrollIntoView({block:'nearest'});
 }
 async function api(action,query={},body){
  const options=body?{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':document.querySelector('meta[name=ita-csrf]').content},body:JSON.stringify(body)}:{};
  const controller=new AbortController();const timeout=setTimeout(()=>controller.abort(),15000);
  let response,result;
  try{
   response=await fetch(`/admin.php?${new URLSearchParams({op:'ita',act:action,...query})}`,{...options,signal:controller.signal});
   result=await response.json();
  }catch(error){
   throw new Error(body?'Chưa xác nhận được kết quả lưu. Hãy kiểm tra danh sách trước khi gửi lại.':'Không tải được dữ liệu. Vui lòng thử lại.');
  }finally{clearTimeout(timeout);}
  if(response.status===401){location.assign('/admin.php?op=ita&act=login');throw new Error('Phiên đã hết hạn.');}
  if(!response.ok||!result.success){const error=new Error(result.message||'Chưa thực hiện được yêu cầu.');error.status=response.status;throw error;}return result.data;
 }
 function pager(name,data){
  $(`${name}-previous`).disabled=data.page<=1;$(`${name}-next`).disabled=data.page*data.per_page>=data.total;
  $(name==='users'?'user-page-summary':'audit-page-summary').textContent=`Trang ${data.page} · ${data.total} bản ghi`;
 }
 async function users(){
  const request=++userRequest;
  $('users-panel').querySelector('thead th:nth-child(3)').textContent=window.ItaPolicy.enabled()?'Quyền sử dụng':'Vai trò IT';
  $('user-create-open').hidden=!window.ItaPolicy.enabled()||!window.ItaPolicy.can('access.manage')||!window.ItaPolicy.can('scope.store')||createUncertain;
  adminStatus('Đang tải tài khoản…');
  try{
   const data=await api('users',{page:state.users,...state.filters});if(request!==userRequest)return false;state.users=data.page;
   $('user-rows').replaceChildren(...data.rows.map(user=>{
    const row=el('tr');[user.username,user.fullname||'—',window.ItaPolicy.enabled()?'Quyền chức năng':roles[user.role]||'Chưa cấp quyền',Number(user.status)!==1?'Tài khoản bị khóa':Number(user.enabled)===1?'Đã cấp quyền':'Chưa cho phép'].forEach(value=>row.append(el('td',value)));
    const cell=el('td'),button=el('button','Phân quyền');button.type='button';button.className='secondary-button';button.setAttribute('aria-label',`Phân quyền ${user.username}`);button.addEventListener('click',()=>editAccess(user.id));cell.append(button);row.append(cell);return row;
   }));
   if(!data.rows.length){const row=el('tr'),cell=el('td','Không có tài khoản phù hợp. Hãy đổi từ khóa, trạng thái hoặc xóa bộ lọc.');cell.colSpan=5;row.append(cell);$('user-rows').append(row);}
   pager('users',data);adminStatus(`Tìm thấy ${data.total} tài khoản theo bộ lọc hiện tại.`);return true;
  }catch(error){if(request!==userRequest)return false;$('user-rows').replaceChildren();$('users-previous').disabled=true;$('users-next').disabled=true;$('user-page-summary').textContent='Chưa tải được danh sách';adminStatus(error.message);return false;}
 }
 async function editAccess(id){
  createMode(false);
  adminStatus('Đang tải quyền truy cập…');$('access-form').hidden=true;feedback.hidden=true;
  try{
   const user=await api('access',{id});$('access-user-id').value=user.id;$('access-title').textContent=`Phân quyền: ${user.username}`;
   $('access-role').value=user.role||'support_technician';$('access-enabled').checked=Number(user.enabled)===1;
   $('scope-options').replaceChildren(...window.itaLiveContext.departments.map(department=>{
    const label=el('label'),box=el('input');box.type='checkbox';box.value=department.value;box.checked=user.department_ids.includes(Number(department.value));label.className='checkbox-label';label.append(box,el('span',department.label));return label;
   }));
   $('access-form').dataset.accountName=user.username;
   window.ItaPermissionEditor.open(user);adminStatus('');$('access-form').hidden=false;(window.ItaPolicy.enabled()?$('permission-function-search'):$('access-role')).focus();
  }catch(error){adminStatus(error.message);}
 }
 function view(){
  if(!window.itaLiveContext)return;
  const next=location.hash.slice(1);
  if(next!=='users'){openedView='';return;}
  if(openedView===next)return;openedView=next;
  if(!window.ItaPolicy.can('access.manage',window.itaLiveContext.user.role==='admin_it')){
   adminStatus('Bạn không có quyền truy cập chức năng này.');return;
  }
  $('user-department').replaceChildren(new Option('Tất cả bộ phận',''),...window.itaLiveContext.departments.map(d=>new Option(d.label,d.value)));
  $('user-department').value=state.filters.department_id||'';users();
 }
 $('access-form').addEventListener('submit',async event=>{
  event.preventDefault();const button=$('access-save');button.disabled=true;feedback.hidden=true;
  const isCreate=creating;let posted=false;
  try{
   if(isCreate){
    if(createUncertain)throw Error('Chưa xác nhận lượt tạo trước. Kiểm danh sách tài khoản trước khi tạo lại.');
    if(/^[\s\p{Z}\p{Cf}]*$/u.test($('user-create-fullname').value)||/\p{Cc}/u.test($('user-create-fullname').value))throw Error('Họ tên không được chứa ký tự điều khiển hoặc chỉ gồm khoảng trắng/ký tự vô hình.');
    const password=$('user-create-password').value;
    if(Array.from(password).length<6)throw Error('Mật khẩu quá ngắn. Hãy nhập ít nhất 6 ký tự.');
    if(new TextEncoder().encode(password).length>72)throw Error('Mật khẩu quá dài: tối đa 72 byte. Ký tự có dấu có thể chiếm nhiều byte; hãy rút ngắn mật khẩu.');
    if(password.includes('\0')||/^[\s\p{Z}\p{Cf}]*$/u.test(password))throw Error('Mật khẩu không được chứa ký tự NUL hoặc chỉ gồm khoảng trắng/ký tự vô hình. Hãy nhập mật khẩu có nội dung.');
    if(password!==$('user-create-confirmation').value)throw Error('Nhập lại mật khẩu chưa khớp. Hãy nhập hai ô mật khẩu giống hệt nhau.');
   }
   const confirmed=await window.ItaUi.confirm({title:isCreate?'Tạo tài khoản và cấp quyền?':'Cập nhật quyền truy cập?',message:isCreate?'Tài khoản mới có thể đăng nhập với đúng quyền và bộ phận bạn đang chọn.':'Quyền mới có hiệu lực ngay. Tài khoản được chỉnh sẽ cần đăng nhập lại.',confirmLabel:isCreate?'Tạo tài khoản':'Cập nhật quyền'});
   if(!confirmed)return;
   button.textContent='Đang lưu…';$('access-form').setAttribute('aria-busy','true');$('access-cancel').disabled=true;
   const payload={user_id:$('access-user-id').value,...(window.ItaPolicy.enabled()?window.ItaPermissionEditor.payload():{role:$('access-role').value}),enabled:$('access-enabled').checked,department_ids:[...document.querySelectorAll('#scope-options input:checked')].map(input=>input.value)};
   if(isCreate){for(const key of ['username','fullname','email','password'])payload[key]=$('user-create-'+key).value;payload.password_confirmation=$('user-create-confirmation').value;}
   posted=true;let result;try{result=await api(isCreate?'user-create':'access',{},payload);}finally{if(isCreate){clearPasswords();delete payload.password;delete payload.password_confirmation;}}
   const message=isCreate?`Đã tạo tài khoản ${result.username} (#${result.id}) và cấp quyền.`:`Đã cập nhật quyền truy cập cho ${$('access-form').dataset.accountName}.`;
   $('access-form').hidden=true;const refreshed=await users();
   announce($('admin-message'),message+(refreshed?'':' Chưa tải lại được danh sách; hãy tải lại trang để kiểm tra.'));
  }catch(error){if(isCreate&&posted&&(!error.status||error.status>=500)){createUncertain=true;$('user-create-open').hidden=true;}
   const dependency=/^Quyền ([a-z_.]+) cần thêm ([a-z_.]+)\.$/.exec(error.message);
   let message=error.message;
   if(dependency){const labels=window.ItaPolicy.labels,key=dependency[1],required=dependency[2];
    message=key==='category.manage'&&required==='scope.store'
     ?'Quản lý loại tài sản là chức năng dùng chung toàn hệ thống. Hãy chọn thêm quyền “Phạm vi toàn hệ thống”, hoặc bỏ chọn “Quản lý loại tài sản” nếu tài khoản chỉ phụ trách một số bộ phận.'
     :`Để dùng “${labels[key]||key}”, hãy chọn thêm quyền “${labels[required]||required}”, hoặc bỏ chọn chức năng đang phụ thuộc quyền này.`;
   }
   announce(feedback,message+(isCreate&&createUncertain?' Hãy kiểm danh sách trước khi thử tạo lại.':''),true);}finally{
   button.disabled=isCreate&&createUncertain;button.textContent=isCreate?'Tạo tài khoản & cấp quyền':'Lưu quyền truy cập';$('access-cancel').disabled=false;$('access-form').removeAttribute('aria-busy');
  }
 });
 $('access-cancel').addEventListener('click',()=>{clearPasswords();$('access-form').hidden=true;});
 $('user-create-open').addEventListener('click',()=>{
  if(!window.ItaPolicy.enabled()||!window.ItaPolicy.can('access.manage')||!window.ItaPolicy.can('scope.store')||createUncertain)return;
  createMode(true);feedback.hidden=true;$('access-title').textContent='Thêm tài khoản & chọn quyền';$('access-user-id').value='';$('access-enabled').checked=true;
  $('scope-options').replaceChildren(...window.itaLiveContext.departments.map(d=>{const label=el('label'),box=el('input');box.type='checkbox';box.value=d.value;label.className='checkbox-label';label.append(box,el('span',d.label));return label;}));
  window.ItaPermissionEditor.open({capabilities_enabled:true,permissions:[],version:''});$('access-form').hidden=false;$('user-create-username').focus();
 });
 addEventListener('ita:view',()=>{if(location.hash!=='#users'){clearPasswords();$('access-form').hidden=true;}});
 for(const name of ['users'])for(const direction of ['previous','next'])$(name+'-'+direction).addEventListener('click',()=>{state[name]+=direction==='next'?1:-1;users();});
 window.ItaAdmin={api,el,pager};
 $('user-filters').addEventListener('submit',event=>{event.preventDefault();state.filters={q:$('user-search').value.trim(),access_status:$('user-status').value,department_id:$('user-department').value};state.users=1;users();});
 $('user-filter-reset').addEventListener('click',()=>{$('user-filters').reset();state.filters={};state.users=1;users();});
 const userPanel=$('users-panel'),filterForm=$('user-filters');userPanel.insertBefore(filterForm,$('admin-message'));
 addEventListener('ita:view',view);addEventListener('ita:data',view);
})();
