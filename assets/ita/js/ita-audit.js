(() => {
 'use strict';
 const live=document.body.dataset.mode==='live';
 const {el,actions,labels,entities,changes}=window.ItaAuditView;
 const $=id=>document.getElementById(id);
 const state={audit:1};let generation=0,opened=false,busy=false,fixture=null,peoplePage=0,peopleTotal=0;const people=new Map();
 const allowed=()=>!live||window.itaLiveContext?.user.role==='admin_it';
 async function mock(){if(!fixture){const r=await fetch('/assets/ita/mock/audit.json',{cache:'no-store'});if(!r.ok)throw new Error('Chưa tải được nhật ký MOCK.');fixture=await r.json();if(fixture.mock!==true||!Array.isArray(fixture.rows)||!Array.isArray(fixture.users))throw new Error('Dữ liệu MOCK không hợp lệ.');}return fixture;}
 async function read(action,query){if(live)return window.ItaAdmin.api(action,query);const f=await mock();if(action==='users')return {rows:f.users,page:1,total:f.users.length,per_page:20};const rows=f.rows.filter(r=>(!query.action||r.action===query.action)&&(!query.user_id||String(r.user_id)===query.user_id)&&(!query.from||new Date(r.created_at.replace(' ','T')+'Z')>=new Date(query.from+'T00:00:00+07:00'))&&(!query.to||new Date(r.created_at.replace(' ','T')+'Z')<new Date(query.to+'T00:00:00+07:00').getTime()+86400000));const page=Math.min(query.page,Math.max(1,Math.ceil(rows.length/20)));return {rows:rows.slice((page-1)*20,page*20),page,total:rows.length,per_page:20};}
 function actionOptions(){const selected=$('audit-action').value;$('audit-action').replaceChildren(new Option('Tất cả thao tác',''));for(const [name,items]of Object.values(actions)){const group=document.createElement('optgroup');group.label=name;for(const [key,label]of Object.entries(items))group.append(new Option(label,key));$('audit-action').append(group);}$('audit-action').value=selected;}
 function peopleOptions(){const select=$('audit-user'),selected=select.value;select.replaceChildren(new Option('Tất cả mọi người',''),...Array.from(people.values(),u=>new Option((u.fullname||u.username||'Tài khoản')+' · '+(u.username||'#'+u.id),String(u.id))));select.value=selected;$('audit-people-more').hidden=people.size>=peopleTotal;$('audit-people-help').textContent=peopleTotal?`Đã tải ${people.size}/${peopleTotal} tài khoản. Chọn tên; không cần nhớ mã số.`:'Chọn tên người thao tác để lọc.';}
 async function loadPeople(){if(!allowed())return;const button=$('audit-people-more');button.disabled=true;try{const data=await read('users',{page:peoplePage+1});peoplePage=data.page;peopleTotal=data.total;data.rows.forEach(u=>people.set(Number(u.id),u));peopleOptions();}catch(error){$('audit-people-help').textContent='Chưa tải được tên tài khoản. Có thể thử tải lại danh sách.';button.hidden=false;}finally{button.disabled=false;}}
 function controls(){for(const id of ['audit-previous','audit-next'])$(id).disabled=busy||Number($(id).dataset.blocked)===1;$('audit-filters').setAttribute('aria-busy',String(busy));}
 for(const [value,label] of [['license.create','Tạo giấy phép'],['license.update','Sửa giấy phép'],['license.allocate','Cấp phát giấy phép'],['license.release','Thu hồi giấy phép'],['license.overage','Xác nhận vượt mức'],['license.key_view','Xem key giấy phép']]){
  const option=el('option',label);option.value=value;$('audit-action').append(option);
 }
 for(const [value,label] of [['ticket.create','Tạo phiếu'],['ticket.assign','Gán kỹ thuật viên'],['ticket.transition','Xử lý phiếu'],['ticket.cost','Sửa chi phí phiếu']]){
  const option=el('option',label);option.value=value;$('audit-action').append(option);
 }
 async function audit(){
  if(!allowed()||location.hash!=='#audit')return;const current=++generation;busy=true;controls();$('audit-message').setAttribute('role','status');
  $('audit-message').textContent='Đang tải nhật ký…';
  try{
   const query={page:state.audit,action:$('audit-action').value,user_id:$('audit-user').value,from:$('audit-from').value,to:$('audit-to').value};
   const data=await read('audit',query);if(current!==generation||location.hash!=='#audit'||!allowed())return;state.audit=data.page;
   $('audit-rows').replaceChildren(...data.rows.map(item=>{
    const row=el('tr');const date=new Date(item.created_at.replace(' ','T')+'Z');
    const person=people.get(Number(item.user_id));[date.toLocaleString('vi-VN',{timeZone:'Asia/Ho_Chi_Minh'}),person?(person.fullname||person.username):'Tài khoản #'+item.user_id,labels[item.action]||item.action,`${entities[item.entity_type]||item.entity_type} #${item.entity_id}`].forEach(value=>row.append(el('td',value)));
    const cell=el('td');cell.append(changes(item));row.append(cell);return row;
   }));$('audit-page-summary').textContent=`Trang ${data.page}/${Math.max(1,Math.ceil(data.total/data.per_page))} · ${data.total} sự kiện`;$('audit-previous').dataset.blocked=Number(data.page<=1);$('audit-next').dataset.blocked=Number(data.page*data.per_page>=data.total);$('audit-message').textContent=(live?'':'MOCK · ')+(data.total?`Tìm thấy ${data.total} sự kiện. Thời gian theo giờ Việt Nam.`:'Không có sự kiện phù hợp. Thử bỏ bớt bộ lọc.');
  }catch(error){if(current===generation){$('audit-message').textContent=error.message;$('audit-message').setAttribute('role','alert');}}
  finally{if(current===generation){busy=false;controls();}}
 }
 function view(){
  if(live&&!window.itaLiveContext)return;
  if(location.hash!=='#audit'){opened=false;generation++;busy=false;return;}
  if(opened)return;opened=true;
  if(!allowed()){
   $('audit-message').textContent='Bạn không có quyền truy cập chức năng này.';return;
  }
  actionOptions();loadPeople().then(audit);
 }
 $('audit-filters').addEventListener('submit',event=>{event.preventDefault();const from=$('audit-from'),to=$('audit-to');to.setCustomValidity(from.value&&to.value&&from.value>to.value?'Ngày kết thúc không được trước ngày bắt đầu.':'');if(!$('audit-filters').reportValidity())return;state.audit=1;audit();});
 for(const id of ['audit-from','audit-to'])$(id).addEventListener('input',()=>$('audit-to').setCustomValidity(''));
 $('audit-reset').addEventListener('click',()=>{$('audit-filters').reset();$('audit-to').setCustomValidity('');state.audit=1;audit();});
 $('audit-people-more').addEventListener('click',()=>loadPeople().then(audit));
 document.querySelectorAll('[data-audit-days]').forEach(button=>button.addEventListener('click',()=>{const days=Number(button.dataset.auditDays),today=new Intl.DateTimeFormat('en-CA',{timeZone:'Asia/Ho_Chi_Minh',year:'numeric',month:'2-digit',day:'2-digit'}).format(new Date()),start=new Date(today+'T00:00:00Z');start.setUTCDate(start.getUTCDate()-Math.max(0,days-1));$('audit-from').value=days?start.toISOString().slice(0,10):'';$('audit-to').value=days?today:'';$('audit-to').setCustomValidity('');state.audit=1;audit();}));
 for(const direction of ['previous','next'])$('audit-'+direction).addEventListener('click',()=>{state.audit+=direction==='next'?1:-1;audit();});
 addEventListener('ita:view',view);addEventListener('ita:data',view);
 if(!live){document.querySelector('[data-view=audit]').hidden=false;view();}
})();
