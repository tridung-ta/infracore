(() => {
 'use strict';
 if(document.body.dataset.mode!=='live')return;
 const $=id=>document.getElementById(id);
 const roles={admin_it:'Admin IT',system_manager:'Quản lý hệ thống',support_technician:'Hỗ trợ kỹ thuật'};
 const state={users:1,filters:{}};let userRequest=0;
 let openedView='';
 let creating=false,createUncertain=false,accessSaving=false,accessRequest=0,profileState=null;const accessUncertain=new Set(),profileUncertain=new Set();
 const credentials=['username','fullname','email','password','confirmation'];
 function createMode(active){
  creating=active;$('user-create-fields').hidden=!active;
  for(const key of credentials){const n=$('user-create-'+key);n.disabled=!active;n.required=active;n.value='';n.dispatchEvent(new Event('input',{bubbles:true}));}
  $('access-enabled').disabled=active;
  $('access-save').textContent=active?'Tạo tài khoản & cấp quyền':'Lưu quyền truy cập';
 }
 function clearPasswords(){for(const key of ['password','confirmation'])$('user-create-'+key).value='';}
 function adminStatus(message,error=false){const node=$('admin-message');renderFeedback(node,message,error);window.ItaUi.autoDismiss(node,error||message.startsWith('Đang'));if(!message)node.hidden=true;}
 function el(tag,text){const node=document.createElement(tag);if(text!==undefined)node.textContent=text;return node;}
 function renderFeedback(node,message,error=false){
  const pending=message.startsWith('Đang'),success=message.startsWith('Đã')&&!message.startsWith('Đã hủy')&&!message.startsWith('Đã đóng');
  node.dataset.state=error?'error':pending?'pending':success?'success':'info';node.setAttribute('role',error?'alert':'status');node.setAttribute('aria-live',error?'assertive':'polite');
  node.classList.toggle('danger-text',error);node.classList.toggle('success-text',!error&&success);
  node.replaceChildren();if(!message)return;
  const mark=el('span',error?'!':pending?'…':success?'✓':'i');mark.className='account-feedback-mark';mark.setAttribute('aria-hidden','true');
  const content=el('div');content.className='account-feedback-content';content.append(el('strong',error?'Cần kiểm tra':pending?'Đang xử lý':success?'Hoàn tất':'Thông tin'),el('p',message));
  const close=el('button','×');close.type='button';close.className='account-feedback-close';close.setAttribute('aria-label','Đóng thông báo');close.disabled=pending;close.addEventListener('click',()=>node.hidden=true);
  node.append(mark,content,close);
 }
 const feedback=el('p');feedback.id='access-message';feedback.className='admin-message';feedback.hidden=true;
 $('access-form').insertBefore(feedback,$('access-form').lastElementChild);
 const accountForms=['access-form','user-profile-form','user-password-form'];
 function formFeedback(form){return form.id==='access-form'?feedback:$('user-profile-message');}
 function invalidMessage(field){
  const label=document.querySelector(`label[for="${field.id}"]`)?.textContent.trim()||'Thông tin';
  if(field.validity.customError)return `${label}: ${field.validationMessage}`;
  if(field.validity.valueMissing)return `${label}: chưa nhập. Hãy điền trường này trước khi lưu.`;
  if(field.validity.typeMismatch)return `${label}: sai định dạng. Hãy nhập địa chỉ email như ten@example.com.`;
  if(field.validity.patternMismatch)return `${label}: dùng 3–50 ký tự chữ/số không dấu, dấu chấm, gạch ngang hoặc gạch dưới; bắt đầu bằng chữ/số.`;
  if(field.validity.tooShort)return `${label}: quá ngắn. Hãy nhập ít nhất ${field.minLength} ký tự.`;
  return `${label}: dữ liệu chưa hợp lệ. Hãy kiểm tra định dạng và độ dài trước khi lưu.`;
 }
 for(const id of accountForms){const form=$(id);form.addEventListener('invalid',event=>{
  const field=event.target;field.setAttribute('aria-invalid','true');
  if([...form.elements].find(n=>n.willValidate&&!n.validity.valid)===field)announce(formFeedback(form),invalidMessage(field),true);
 },true);form.addEventListener('input',event=>event.target.removeAttribute('aria-invalid'));}
 function announce(node,message,error=false){
  renderFeedback(node,message,error);node.hidden=false;node.tabIndex=-1;
  window.ItaUi.autoDismiss(node,error||message.startsWith('Đang'));
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
  if(response.status===401&&!(action==='user-password-reset'&&result.message==='Mật khẩu quản trị xác nhận không đúng.')){location.assign('/admin.php?op=ita&act=login');throw new Error('Phiên đã hết hạn.');}
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
    const cell=el('td');
    if(window.ItaPolicy.can('access.manage',window.itaLiveContext.user.role==='admin_it')){
     const button=el('button','Phân quyền');button.type='button';button.className='secondary-button';button.setAttribute('aria-label',`Phân quyền ${user.username}`);button.addEventListener('click',()=>editAccess(user.id));cell.append(button);
     if(window.ItaPolicy.enabled()&&window.ItaPolicy.can('scope.store')){
      for(const [mode,label] of [['profile','Sửa thông tin'],['password','Đặt lại mật khẩu']]){const action=el('button',label);action.type='button';action.className='secondary-button';action.setAttribute('aria-label',`${label} ${user.username}`);action.disabled=profileUncertain.has(user.id);action.addEventListener('click',()=>openProfile(user.id,user.username,mode));cell.append(' ',action);}
     }
     if(window.ItaPolicy.enabled()&&Number(user.enabled)===1){const remove=el('button','Xóa quyền ITA');remove.type='button';remove.className='secondary-button';remove.setAttribute('aria-label',`Xóa quyền ITA của ${user.username}`);remove.disabled=accessUncertain.has(user.id);remove.addEventListener('click',()=>removeAccess(user.id,user.username));cell.append(' ',remove);}
    }
    else cell.textContent='Chỉ xem';row.append(cell);return row;
   }));
   if(!data.rows.length){const row=el('tr'),cell=el('td','Không có tài khoản phù hợp. Hãy đổi từ khóa, trạng thái hoặc xóa bộ lọc.');cell.colSpan=5;row.append(cell);$('user-rows').append(row);}
   pager('users',data);
   if(profileUncertain.size||accessUncertain.size||createUncertain)adminStatus('Có thao tác tài khoản chưa xác nhận được kết quả. Không gửi lại; hãy kiểm tra tài khoản và nhật ký trước khi tiếp tục. Các nút liên quan đang được khóa trong phiên này.',true);
   else adminStatus(`Tìm thấy ${data.total} tài khoản theo bộ lọc hiện tại.`);return true;
  }catch(error){if(request!==userRequest)return false;$('user-rows').replaceChildren();$('users-previous').disabled=true;$('users-next').disabled=true;$('user-page-summary').textContent='Chưa tải được danh sách';adminStatus(error.message,true);return false;}
 }
 async function removeAccess(id,name){
  if(accessSaving||accessUncertain.has(id)){adminStatus('Đang xử lý hoặc kết quả trước chưa rõ. Hãy kiểm tra lại tài khoản trước khi tiếp tục.');return;}
  if(!window.ItaPolicy.enabled()||!window.ItaPolicy.can('access.manage'))return;
  accessSaving=true;const request=++accessRequest;closeProfile();$('access-form').hidden=true;let posted=false;
  try{
   adminStatus('Đang kiểm tra quyền truy cập của tài khoản…');const target=await api('access',{id});
   if(request!==accessRequest||location.hash!=='#users')return;
   const accepted=await window.ItaUi.confirm({title:`Xóa quyền ITA của ${name}?`,message:'Tài khoản sẽ không đăng nhập InfraCore được. Tài khoản nền, dữ liệu và lịch sử vẫn được giữ; hệ thống cũ không bị khóa. Có thể cấp lại quyền sau.',confirmLabel:'Xóa quyền ITA'});
   if(!accepted){adminStatus('Đã hủy. Quyền truy cập chưa thay đổi.');return;}
   if(request!==accessRequest||location.hash!=='#users')return;
   posted=true;await api('access-revoke',{}, {user_id:id,version:target.version});const refreshed=await users();
   announce($('admin-message'),`Đã xóa quyền truy cập InfraCore của ${name}. Tài khoản nền và lịch sử vẫn được giữ.`+(refreshed?'':' Chưa tải lại được danh sách; hãy kiểm tra trước khi thao tác tiếp.'));
  }catch(error){
   if(posted&&(!error.status||error.status>=500))accessUncertain.add(id);
   adminStatus(error.message+(accessUncertain.has(id)?' Kết quả chưa rõ; kiểm tra tài khoản trước khi thao tác lại.':''),true);
  }finally{accessSaving=false;}
 }
 async function editAccess(id){
  if(accessSaving){adminStatus('Đang lưu quyền. Hãy đợi thao tác hiện tại hoàn tất trước khi chọn tài khoản khác.');return;}
  const request=++accessRequest;
  closeProfile();createMode(false);
  adminStatus('Đang tải quyền truy cập…');$('access-form').hidden=true;feedback.hidden=true;
  try{
   const user=await api('access',{id});if(request!==accessRequest||location.hash!=='#users')return;$('access-user-id').value=user.id;$('access-title').textContent=`Phân quyền: ${user.username}`;
   $('access-role').value=user.role||'support_technician';$('access-enabled').checked=Number(user.enabled)===1;
   $('scope-options').replaceChildren(...window.itaLiveContext.departments.map(department=>{
    const label=el('label'),box=el('input');box.type='checkbox';box.value=department.value;box.checked=user.department_ids.includes(Number(department.value));label.className='checkbox-label';label.append(box,el('span',department.label));return label;
   }));
   $('access-form').dataset.accountName=user.username;
   window.ItaPermissionEditor.open(user);adminStatus('');$('access-form').hidden=false;(window.ItaPolicy.enabled()?$('permission-function-search'):$('access-role')).focus();
  }catch(error){if(request===accessRequest&&location.hash==='#users')adminStatus(error.message,true);}
 }
 function clearProfilePasswords(){for(const id of ['user-password-new','user-password-confirm','user-password-actor'])$(id).value='';}
 function closeProfile(){clearProfilePasswords();$('user-profile-panel').hidden=true;profileState=null;}
 async function openProfile(id,name,mode){
  if(accessSaving||profileUncertain.has(id)){adminStatus('Đang lưu hoặc kết quả trước chưa rõ. Hãy kiểm tra lại trước khi tiếp tục.');return;}
  const request=++accessRequest;closeProfile();$('access-form').hidden=true;adminStatus('Đang tải thông tin tài khoản…');
  try{
   const data=await api('user-profile',{id});if(request!==accessRequest||location.hash!=='#users')return;
   $('user-profile-form').reset();$('user-password-form').reset();profileState={id,name,mode,version:data.version};$('user-profile-title').textContent=(mode==='profile'?'Sửa thông tin: ':'Đặt lại mật khẩu: ')+name;
   $('user-profile-form').hidden=mode!=='profile';$('user-password-form').hidden=mode!=='password';$('user-profile-message').hidden=true;
   for(const key of ['username','fullname','email'])$('user-profile-'+key).value=data[key];
   $('user-profile-panel').hidden=false;adminStatus('');$(mode==='profile'?'user-profile-username':'user-password-new').focus();
  }catch(error){if(request===accessRequest&&location.hash==='#users')adminStatus(error.message,true);}
 }
 async function saveProfile(event,mode){
  event.preventDefault();if(accessSaving||!profileState||profileState.mode!==mode)return;
  const request=accessRequest,selected={...profileState},button=$(mode==='profile'?'user-profile-save':'user-password-save'),message=$('user-profile-message');
  const originalLabel=button.textContent;accessSaving=true;button.disabled=true;message.hidden=true;let posted=false;
  try{
   let payload={user_id:selected.id,version:selected.version};
   if(mode==='profile'){for(const key of ['username','fullname','email'])payload[key]=$('user-profile-'+key).value;}
   else{
    const password=$('user-password-new').value;
    if(Array.from(password).length<6||new TextEncoder().encode(password).length>72||password.includes('\0')||/^[\s\p{Z}\p{Cf}]*$/u.test(password))throw Error('Mật khẩu mới cần ít nhất 6 ký tự, tối đa 72 byte và không chỉ gồm khoảng trắng.');
    if(password!==$('user-password-confirm').value)throw Error('Mật khẩu nhập lại chưa khớp. Hãy nhập giống hệt mật khẩu mới.');
    if(!$('user-password-actor').value)throw Error('Mật khẩu của bạn để xác nhận: chưa nhập. Hãy nhập mật khẩu quản trị đang dùng.');
    payload.password=password;payload.password_confirmation=$('user-password-confirm').value;payload.actor_password=$('user-password-actor').value;
   }
   const confirmed=await window.ItaUi.confirm({title:mode==='profile'?'Lưu thông tin tài khoản nền?':'Đặt lại mật khẩu tài khoản?',message:mode==='profile'?'Tên đăng nhập, họ tên và email sẽ thay đổi trong tài khoản dùng chung với hệ thống cũ.':'Mật khẩu cũ bị thay thế. Đăng nhập cũ chỉ hỗ trợ MD5 có thể ngừng hoạt động; phiên hệ thống cũ không tự đóng.',confirmLabel:mode==='profile'?'Lưu thông tin':'Đặt lại mật khẩu'});
   if(!confirmed||request!==accessRequest||location.hash!=='#users'||profileState?.id!==selected.id){clearProfilePasswords();delete payload.password;delete payload.password_confirmation;delete payload.actor_password;if(request===accessRequest&&location.hash==='#users')announce(message,'Đã hủy xác nhận. Thông tin và mật khẩu chưa thay đổi.');return;}
   button.textContent='Đang lưu…';$(mode==='profile'?'user-profile-form':'user-password-form').setAttribute('aria-busy','true');announce(message,'Đang lưu. Vui lòng đợi kết quả trước khi thao tác tiếp.');
   posted=true;try{await api(mode==='profile'?'user-profile-save':'user-password-reset',{},payload);}finally{clearProfilePasswords();delete payload.password;delete payload.password_confirmation;delete payload.actor_password;}
   closeProfile();const refreshed=await users();announce($('admin-message'),(mode==='profile'?'Đã cập nhật thông tin của ':'Đã đặt lại mật khẩu của ')+selected.name+'.'+(refreshed?'':' Chưa tải lại danh sách; hãy kiểm tra trước khi tiếp tục.'));
  }catch(error){
   if(posted&&(!error.status||error.status>=500)){profileUncertain.add(selected.id);button.disabled=true;}
   if(profileUncertain.has(selected.id))adminStatus(error.message+' Kết quả chưa rõ; không gửi lại. Kiểm tra tài khoản và nhật ký trước khi tiếp tục.',true);
   announce(message,error.message+(profileUncertain.has(selected.id)?' Kết quả chưa rõ; kiểm tra tài khoản trước khi thao tác lại.':''),true);
  }finally{accessSaving=false;button.disabled=profileUncertain.has(selected.id);button.textContent=originalLabel;$(mode==='profile'?'user-profile-form':'user-password-form').removeAttribute('aria-busy');}
 }
 $('user-profile-form').addEventListener('submit',event=>saveProfile(event,'profile'));
 $('user-password-form').addEventListener('submit',event=>saveProfile(event,'password'));
 for(const id of ['user-profile-cancel','user-password-cancel'])$(id).addEventListener('click',()=>{if(!accessSaving){accessRequest++;closeProfile();announce($('admin-message'),'Đã đóng biểu mẫu. Chưa gửi thay đổi tài khoản.');}});
 function view(){
  if(!window.itaLiveContext)return;
  const next=location.hash.slice(1);
  if(next!=='users'){openedView='';return;}
  if(openedView===next)return;openedView=next;
  if(!window.ItaPolicy.can('access.read',window.itaLiveContext.user.role==='admin_it')){
   adminStatus('Bạn không có quyền truy cập chức năng này.');return;
  }
  $('user-department').replaceChildren(new Option('Tất cả bộ phận',''),...window.itaLiveContext.departments.map(d=>new Option(d.label,d.value)));
  $('user-department').value=state.filters.department_id||'';users();
 }
 $('access-form').addEventListener('submit',async event=>{
  event.preventDefault();if(accessSaving)return;accessSaving=true;const button=$('access-save');button.disabled=true;feedback.hidden=true;
  const request=accessRequest,isCreate=creating,accountName=$('access-form').dataset.accountName;let posted=false;
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
   if(!confirmed){announce(feedback,'Đã hủy xác nhận. Chưa tạo tài khoản hoặc thay đổi quyền.');return;}
   if(request!==accessRequest||location.hash!=='#users')return;
   button.textContent='Đang lưu…';$('access-form').setAttribute('aria-busy','true');$('access-cancel').disabled=true;
   const payload={user_id:$('access-user-id').value,...(window.ItaPolicy.enabled()?window.ItaPermissionEditor.payload():{role:$('access-role').value}),enabled:$('access-enabled').checked,department_ids:[...document.querySelectorAll('#scope-options input:checked')].map(input=>input.value)};
   if(isCreate){for(const key of ['username','fullname','email','password'])payload[key]=$('user-create-'+key).value;payload.password_confirmation=$('user-create-confirmation').value;}
   posted=true;let result;try{result=await api(isCreate?'user-create':'access',{},payload);}finally{if(isCreate){clearPasswords();delete payload.password;delete payload.password_confirmation;}}
   const message=isCreate?`Đã tạo tài khoản ${result.username} (#${result.id}) và cấp quyền.`:`Đã cập nhật quyền truy cập cho ${accountName}.`;
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
   accessSaving=false;button.disabled=isCreate&&createUncertain;button.textContent=isCreate?'Tạo tài khoản & cấp quyền':'Lưu quyền truy cập';$('access-cancel').disabled=false;$('access-form').removeAttribute('aria-busy');
  }
 });
 $('access-cancel').addEventListener('click',()=>{if(accessSaving)return;accessRequest++;clearPasswords();$('access-form').hidden=true;announce($('admin-message'),'Đã đóng biểu mẫu. Chưa tạo tài khoản hoặc thay đổi quyền.');});
 $('user-create-open').addEventListener('click',()=>{
  if(accessSaving||!window.ItaPolicy.enabled()||!window.ItaPolicy.can('access.manage')||!window.ItaPolicy.can('scope.store')||createUncertain)return;
  accessRequest++;
  closeProfile();createMode(true);feedback.hidden=true;$('access-title').textContent='Thêm tài khoản & chọn quyền';$('access-user-id').value='';$('access-enabled').checked=true;
  $('scope-options').replaceChildren(...window.itaLiveContext.departments.map(d=>{const label=el('label'),box=el('input');box.type='checkbox';box.value=d.value;label.className='checkbox-label';label.append(box,el('span',d.label));return label;}));
  window.ItaPermissionEditor.open({capabilities_enabled:true,permissions:[],version:''});$('access-form').hidden=false;$('user-create-username').focus();
 });
 addEventListener('ita:view',()=>{if(location.hash!=='#users'){accessRequest++;clearPasswords();closeProfile();$('access-form').hidden=true;}});
 for(const name of ['users'])for(const direction of ['previous','next'])$(name+'-'+direction).addEventListener('click',()=>{state[name]+=direction==='next'?1:-1;users();});
 window.ItaAdmin={api,el,pager};
 $('user-filters').addEventListener('submit',event=>{event.preventDefault();state.filters={q:$('user-search').value.trim(),access_status:$('user-status').value,department_id:$('user-department').value};state.users=1;users();});
 $('user-filter-reset').addEventListener('click',()=>{$('user-filters').reset();state.filters={};state.users=1;users();});
 const userPanel=$('users-panel'),filterForm=$('user-filters');userPanel.insertBefore(filterForm,$('admin-message'));
 addEventListener('ita:view',view);addEventListener('ita:data',view);
})();
