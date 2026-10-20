(() => {
 'use strict';
 if(document.body.dataset.mode!=='live')return;
 const {api,el,pager}=window.ItaAdmin;
 const $=id=>document.getElementById(id);
 const state={audit:1};
 for(const [value,label] of [['license.create','Tạo giấy phép'],['license.update','Sửa giấy phép'],['license.allocate','Cấp phát giấy phép'],['license.release','Thu hồi giấy phép'],['license.overage','Xác nhận vượt mức'],['license.key_view','Xem key giấy phép']]){
  const option=el('option',label);option.value=value;$('audit-action').append(option);
 }
 for(const [value,label] of [['ticket.create','Tạo phiếu'],['ticket.assign','Gán kỹ thuật viên'],['ticket.transition','Xử lý phiếu'],['ticket.cost','Sửa chi phí phiếu']]){
  const option=el('option',label);option.value=value;$('audit-action').append(option);
 }
 let opened=false;
 async function audit(){
  $('audit-message').textContent='Đang tải nhật ký…';
  try{
   const data=await api('audit',{page:state.audit,action:$('audit-action').value,user_id:$('audit-user').value,from:$('audit-from').value,to:$('audit-to').value});state.audit=data.page;
   $('audit-rows').replaceChildren(...data.rows.map(item=>{
    const row=el('tr');const date=new Date(item.created_at.replace(' ','T')+'Z');
    [date.toLocaleString('vi-VN',{timeZone:'Asia/Ho_Chi_Minh'}),String(item.user_id),item.action,`${item.entity_type} #${item.entity_id}`].forEach(value=>row.append(el('td',value)));
    const cell=el('td'),details=el('details');details.append(el('summary','Xem thay đổi'),el('pre',JSON.stringify({before:item.before_json?JSON.parse(item.before_json):null,after:item.after_json?JSON.parse(item.after_json):null},null,2)));cell.append(details);row.append(cell);return row;
   }));pager('audit',data);$('audit-message').textContent=data.total?'':'Chưa có nhật ký phù hợp bộ lọc.';
  }catch(error){$('audit-message').textContent=error.message;}
 }
 function view(){
  if(!window.itaLiveContext)return;
  if(location.hash!=='#audit'){opened=false;return;}
  if(opened)return;opened=true;
  if(window.itaLiveContext.user.role!=='admin_it'){
   $('audit-message').textContent='Bạn không có quyền truy cập chức năng này.';return;
  }
  audit();
 }
 $('audit-filters').addEventListener('submit',event=>{event.preventDefault();state.audit=1;audit();});
 for(const direction of ['previous','next'])$('audit-'+direction).addEventListener('click',()=>{state.audit+=direction==='next'?1:-1;audit();});
 addEventListener('ita:view',view);addEventListener('ita:data',view);
})();
