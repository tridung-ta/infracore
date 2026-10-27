(() => {
 'use strict';
 if(document.body.dataset.mode!=='live')return;
 const $=id=>document.getElementById(id);
 const roles={admin_it:'Admin IT',system_manager:'Quản lý hệ thống',support_technician:'Hỗ trợ kỹ thuật'};
 const state={users:1};
 let openedView='';
 function el(tag,text){const node=document.createElement(tag);if(text!==undefined)node.textContent=text;return node;}
 const feedback=el('p');feedback.id='access-message';feedback.className='admin-message';feedback.hidden=true;
 $('access-form').insertBefore(feedback,$('access-form').lastElementChild);
 function announce(node,message,error=false){
  node.textContent=message;node.hidden=false;node.tabIndex=-1;
  node.setAttribute('role',error?'alert':'status');
  node.classList.toggle('danger-text',error);node.classList.toggle('success-text',!error);
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
   throw new Error(body?'Chưa xác nhận được kết quả lưu. Hãy mở lại quyền để kiểm tra trước khi lưu lại.':'Không tải được dữ liệu. Vui lòng thử lại.');
  }finally{clearTimeout(timeout);}
  if(response.status===401){location.assign('/admin.php?op=ita&act=login');throw new Error('Phiên đã hết hạn.');}
  if(!response.ok||!result.success)throw new Error(result.message||'Chưa thực hiện được yêu cầu.');return result.data;
 }
 function pager(name,data){
  $(`${name}-previous`).disabled=data.page<=1;$(`${name}-next`).disabled=data.page*data.per_page>=data.total;
  $(name==='users'?'user-page-summary':'audit-page-summary').textContent=`Trang ${data.page} · ${data.total} bản ghi`;
 }
 async function users(){
  $('users-panel').querySelector('thead th:nth-child(3)').textContent=window.ItaPolicy.enabled()?'Quyền sử dụng':'Vai trò IT';
  $('admin-message').textContent='Đang tải tài khoản…';
  try{
   const data=await api('users',{page:state.users});state.users=data.page;
   $('user-rows').replaceChildren(...data.rows.map(user=>{
    const row=el('tr');[user.username,user.fullname||'—',window.ItaPolicy.enabled()?'Quyền chức năng':roles[user.role]||'Chưa cấp quyền',Number(user.enabled)===1?'Đã cấp quyền':'Chưa cho phép'].forEach(value=>row.append(el('td',value)));
    const cell=el('td'),button=el('button','Phân quyền');button.type='button';button.className='secondary-button';button.setAttribute('aria-label',`Phân quyền ${user.username}`);button.addEventListener('click',()=>editAccess(user.id));cell.append(button);row.append(cell);return row;
   }));
   pager('users',data);$('admin-message').textContent=data.total?'':'Chưa có tài khoản.';return true;
  }catch(error){$('admin-message').textContent=error.message;return false;}
 }
 async function editAccess(id){
  $('admin-message').textContent='Đang tải quyền truy cập…';$('access-form').hidden=true;feedback.hidden=true;
  try{
   const user=await api('access',{id});$('access-user-id').value=user.id;$('access-title').textContent=`Phân quyền: ${user.username}`;
   $('access-role').value=user.role||'support_technician';$('access-enabled').checked=Number(user.enabled)===1;
   $('scope-options').replaceChildren(...window.itaLiveContext.departments.map(department=>{
    const label=el('label'),box=el('input');box.type='checkbox';box.value=department.value;box.checked=user.department_ids.includes(Number(department.value));label.className='checkbox-label';label.append(box,el('span',department.label));return label;
   }));
   $('access-form').dataset.accountName=user.username;
   window.ItaPermissionEditor.open(user);$('admin-message').textContent='';$('access-form').hidden=false;(window.ItaPolicy.enabled()?$('permission-function-search'):$('access-role')).focus();
  }catch(error){$('admin-message').textContent=error.message;}
 }
 function view(){
  if(!window.itaLiveContext)return;
  const next=location.hash.slice(1);
  if(next!=='users'){openedView='';return;}
  if(openedView===next)return;openedView=next;
  if(!window.ItaPolicy.can('access.manage',window.itaLiveContext.user.role==='admin_it')){
   $('admin-message').textContent='Bạn không có quyền truy cập chức năng này.';return;
  }
  users();
 }
 $('access-form').addEventListener('submit',async event=>{
  event.preventDefault();const button=$('access-save');button.disabled=true;feedback.hidden=true;
  try{
   const confirmed=await window.ItaUi.confirm({title:'Cập nhật quyền truy cập?',message:'Quyền mới có hiệu lực ngay. Tài khoản được chỉnh sẽ cần đăng nhập lại.',confirmLabel:'Cập nhật quyền'});
   if(!confirmed)return;
   button.textContent='Đang lưu…';$('access-form').setAttribute('aria-busy','true');$('access-cancel').disabled=true;
   await api('access',{}, {user_id:$('access-user-id').value,...(window.ItaPolicy.enabled()?window.ItaPermissionEditor.payload():{role:$('access-role').value}),enabled:$('access-enabled').checked,department_ids:[...document.querySelectorAll('#scope-options input:checked')].map(input=>input.value)});
   const message=`Đã cập nhật quyền truy cập cho ${$('access-form').dataset.accountName}.`;
   $('access-form').hidden=true;const refreshed=await users();
   announce($('admin-message'),message+(refreshed?'':' Chưa tải lại được danh sách; hãy tải lại trang để kiểm tra.'));
  }catch(error){announce(feedback,error.message,true);}finally{
   button.disabled=false;button.textContent='Lưu quyền truy cập';$('access-cancel').disabled=false;$('access-form').removeAttribute('aria-busy');
  }
 });
 $('access-cancel').addEventListener('click',()=>{$('access-form').hidden=true;});
 for(const name of ['users'])for(const direction of ['previous','next'])$(name+'-'+direction).addEventListener('click',()=>{state[name]+=direction==='next'?1:-1;users();});
 window.ItaAdmin={api,el,pager};
 addEventListener('ita:view',view);addEventListener('ita:data',view);
})();
