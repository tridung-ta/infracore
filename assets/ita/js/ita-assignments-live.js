(() => {
 'use strict';
 if(document.body.dataset.mode!=='live')return;
 const $=id=>document.getElementById(id),{api}=window.ItaAdmin;
 let record=null,assetPage=1,assetTotal=0,userPage=1,userTotal=0,busy=false,stale=false,started=false;
 const status={in_use:'Đang sử dụng',maintenance:'Bảo trì',broken:'Hỏng',retired:'Thanh lý'};
 function node(tag,text){const n=document.createElement(tag);n.textContent=text;return n;}
 function message(text,error=false){const n=$('handover-message');n.textContent=text;n.setAttribute('role',error?'alert':'status');}
 function buttons(){
  const canAssign=window.itaLiveContext?.user.role!=='support_technician';
  $('handover-assign').disabled=busy||stale||!record||!!record.active||record.asset.status!=='in_use'||!!record.asset.current_user_id||!canAssign||!$('handover-target').value;
  $('handover-return').disabled=busy||stale||!record?.active;
  $('handover-acknowledge').disabled=busy||stale||!record?.active;
  for(const [prefix,page,total,size] of [['assets',assetPage,assetTotal,5],['user',userPage,userTotal,20],['history',record?.page||1,record?.total||0,20]]){
   $(`handover-${prefix}-previous`).disabled=busy||page<=1;$(`handover-${prefix}-next`).disabled=busy||page*size>=total;
  }
 }
 async function task(callback){
  if(busy)return;busy=true;$('assignment-live').setAttribute('aria-busy','true');
  const controls=[...$('assignment-live').querySelectorAll('input,select,textarea,button')],disabled=controls.map(n=>n.disabled);
  controls.forEach(n=>n.disabled=true);
  try{await callback();}catch(error){message(error.message||'Không tải được dữ liệu. Hãy thử lại.',true);}
  finally{busy=false;controls.forEach((n,i)=>n.disabled=disabled[i]);$('assignment-live').setAttribute('aria-busy','false');buttons();}
 }
 async function loadAssets(page=1){
  record=null;$('handover-record').hidden=true;stale=true;
  const data=await api('data',{page,q:$('handover-search').value});assetPage=data.pagination.page;assetTotal=data.pagination.total;
  $('handover-asset').replaceChildren(...data.assets.map(asset=>new Option(`${asset.code} · ${asset.name}`,asset.id)));
  $('handover-assets-page').textContent=`Trang ${assetPage}/${Math.max(1,Math.ceil(assetTotal/5))} · ${assetTotal} thiết bị`;
  if(data.assets.length)await loadRecord();else message('Không có thiết bị phù hợp trong phạm vi của bạn.');
 }
 async function loadRecord(page=1){
  stale=true;$('handover-record').hidden=true;record=null;
  const id=$('handover-asset').value;if(!id)return;
  const data=await api('assignments',{asset_id:id,page});record=data;stale=false;render();message('Đã tải hồ sơ bàn giao mới nhất.');
  if(!data.active&&data.asset.status==='in_use'&&window.itaLiveContext.user.role!=='support_technician')await loadRecipients(1);
 }
 function render(){
  const {asset,active,history}=record,canAssign=window.itaLiveContext.user.role!=='support_technician';
  $('handover-record').hidden=false;const details=$('handover-details');details.replaceChildren();
  for(const [label,value] of [['Mã thiết bị',asset.code],['Tên thiết bị',asset.name],['Trạng thái',status[asset.status]],['Bảo trì',asset.maintenance_overdue?`Quá hạn từ ${asset.next_maintenance_due}`:'Chưa có cảnh báo quá hạn']]){const d=node('div','');d.append(node('dt',label),node('dd',value));details.append(d);}
  const eligible=!active&&asset.status==='in_use'&&!asset.current_user_id;
  $('handover-create').hidden=!canAssign||!eligible;$('handover-active').hidden=!active;
  $('handover-eligible').textContent=active?'Thiết bị đang có lượt bàn giao, cần thu hồi trước khi giao lại.':asset.current_user_id?'Thiết bị còn người đang giữ nhưng không có lượt giao tương ứng. Cần đối chiếu dữ liệu trước khi giao mới.':eligible?(canAssign?'Có thể bàn giao cho nhân viên hoặc bộ phận trong phạm vi.':'Vai trò Hỗ trợ được thu hồi trong phạm vi, không tạo bàn giao mới.'):'Thiết bị hỏng, bảo trì hoặc thanh lý không được giao mới.';
  if(active){
   $('handover-active-text').textContent=`${active.handover_no} · Người nhận: ${active.to_user_id?'tài khoản #'+active.to_user_id:'bộ phận #'+active.to_department_id} · Giao lúc ${active.assigned_at} UTC · Hạn thu hồi ${active.due_return||'chưa đặt'}`;
   $('handover-acknowledge').hidden=!active.to_user_id||Number(active.to_user_id)!==window.itaLiveContext.user.id||!!active.acknowledged_at;
   $('handover-ack-help').textContent=active.acknowledged_at?`Đã xác nhận nhận lúc ${active.acknowledged_at} UTC.`:active.to_user_id?'Chỉ chính tài khoản người nhận có quyền ITA và phạm vi thiết bị được xác nhận.':'Bàn giao cho bộ phận dùng biên bản ký nhận; chưa có xác nhận điện tử.';
  }
  $('handover-history').replaceChildren(...history.map(item=>{
   const p=node('p','');p.append(node('strong',item.handover_no),document.createElement('br'),node('span',`${item.assigned_at} UTC · ${item.returned_at?'Đã thu hồi '+item.returned_at+' UTC':item.acknowledged_at?'Đã xác nhận nhận':'Đang giao, chưa xác nhận'} · ${item.to_user_id?'Tài khoản #'+item.to_user_id:'Bộ phận #'+item.to_department_id}`));return p;
  }));
  if(!history.length)$('handover-history').append(node('p','Chưa có lịch sử phân bổ.'));
  $('handover-history-page').textContent=`Trang ${record.page}/${Math.max(1,Math.ceil(record.total/20))} · ${record.total} lượt`;
 }
 async function loadRecipients(page=1){
  $('handover-target').replaceChildren(new Option('Chọn người/bộ phận nhận',''));
  const department=$('handover-target-type').value==='department';$('handover-user-search-group').hidden=department;$('handover-user-pages').hidden=department;
  if(department){$('handover-target').append(...window.itaLiveContext.departments.map(d=>new Option(d.label,d.value)));return;}
  const data=await api('assignment-recipients',{page,q:$('handover-user-search').value});userPage=data.page;userTotal=data.total;
  $('handover-target').append(...data.rows.map(user=>new Option(`${user.fullname||user.username} · ${user.username}`,user.id)));
  $('handover-user-page').textContent=`${userPage}/${Math.max(1,Math.ceil(userTotal/20))}`;
 }
 async function save(operation){
  if(!record||stale)return;const current=record;stale=true;
  const body={operation,asset_id:current.asset.id,asset_version:current.asset.version};
  if(operation==='assign')Object.assign(body,{target_type:$('handover-target-type').value,target_id:$('handover-target').value,due_return:$('handover-due').value,note:$('handover-create-note').value});
  else Object.assign(body,{assignment_id:current.active.id,assignment_version:current.active.version,note:$('handover-return-note').value});
  try{await api('assignment-save',{},body);}
  catch(error){throw new Error(error.message.replace('Hãy mở lại quyền để kiểm tra trước khi lưu lại.','Hãy tải lại hồ sơ bàn giao để kiểm tra trước khi thử lại.')+' Tải lại hồ sơ trước thao tác tiếp theo.');}
  $('handover-return-note').value='';$('handover-create-note').value='';$('handover-due').value='';
  try{await loadRecord();message(operation==='assign'?'Đã giao thiết bị thành công.':operation==='return'?'Đã thu hồi thiết bị thành công.':'Đã xác nhận bạn nhận thiết bị.');}
  catch(error){message(`Đã lưu thao tác, nhưng chưa tải lại được hồ sơ. ${error.message} Bấm Tải lại hồ sơ trước thao tác tiếp theo.`,true);}
  dispatchEvent(new Event('ita:refresh'));
 }
 $('handover-search-form').addEventListener('submit',e=>{e.preventDefault();task(()=>loadAssets(1));});
 $('handover-asset').addEventListener('change',()=>task(()=>loadRecord()));$('handover-reload').addEventListener('click',()=>task(()=>loadAssets(assetPage)));
 $('handover-create').addEventListener('submit',e=>{e.preventDefault();task(()=>save('assign'));});
 $('handover-return-form').addEventListener('submit',e=>{e.preventDefault();task(()=>save('return'));});
 $('handover-acknowledge').addEventListener('click',()=>task(()=>save('acknowledge')));
 $('handover-target-type').addEventListener('change',()=>task(()=>loadRecipients(1)));$('handover-target').addEventListener('change',buttons);
 $('handover-user-find').addEventListener('click',()=>task(()=>loadRecipients(1)));
 for(const [prefix,read,current] of [['assets',loadAssets,()=>assetPage],['user',loadRecipients,()=>userPage],['history',loadRecord,()=>record.page]])for(const [direction,delta] of [['previous',-1],['next',1]])$(`handover-${prefix}-${direction}`).addEventListener('click',()=>task(()=>read(current()+delta)));
 $('assignment-mode').textContent='Giao và thu hồi thiết bị theo quyền; lịch sử lưu riêng từng lượt bàn giao.';
 $('assignment-live').nextElementSibling.hidden=true;$('assignment-live').hidden=false;
 document.querySelector('[data-view=assignments] .nav-count')?.remove();
 for(const [value,label] of [['assignment.assign','Giao thiết bị'],['assignment.return','Thu hồi thiết bị'],['assignment.acknowledge','Xác nhận nhận thiết bị']])$('audit-action').append(new Option(label,value));
 function activate(){if(!started&&location.hash==='#assignments'&&window.itaLiveContext){started=true;task(()=>loadAssets());}}
 addEventListener('ita:view',activate);addEventListener('ita:data',activate);activate();
})();
