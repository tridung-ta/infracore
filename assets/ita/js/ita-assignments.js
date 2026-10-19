/* Phase 5 presentation only. No native identities, write APIs or browser persistence. */
(() => {
 'use strict';
 if(document.body.dataset.mode==='live')return;
 const $=id=>document.getElementById(id),labels={in_use:'Đang sử dụng',retired:'Đã thanh lý'};
 let assets=[],loading=false;
 function text(tag,value){const node=document.createElement(tag);node.textContent=value;return node;}
 function selected(){return assets.find(asset=>asset.id===$('assignment-device').value);}
 function render(){
  const asset=selected();$('assignment-draft').hidden=true;
  if(!asset)return;
  const details=$('assignment-details');details.replaceChildren();
  for(const [label,value] of [['Mã thiết bị',asset.code],['Tên thiết bị',asset.name],['Trạng thái',labels[asset.status]],['Phân bổ',asset.assigned?'Đang giao, chờ thu hồi':'Chưa có lượt giao đang mở'],['Bảo trì',asset.overdue?'MOCK · Quá hạn bảo trì':'MOCK · Không có cảnh báo quá hạn']]){
   const group=document.createElement('div');group.append(text('dt',label),text('dd',value));details.append(group);
  }
  const blocked=asset.status==='retired'||asset.assigned;
  $('assignment-preview').disabled=blocked;
  $('assignment-eligibility').textContent=asset.status==='retired'?'Không thể giao thiết bị đã thanh lý.':asset.assigned?'Thiết bị đang được giao. Cần thu hồi trước khi tạo lượt giao mới.':'MOCK · Có thể xem trước bàn giao; chưa kiểm quyền, trạng thái hoặc nhân viên trong DB.';
  $('assignment-history').replaceChildren(...asset.history.map(item=>{const entry=document.createElement('p');entry.append(text('strong',`${item.date} · ${item.receipt}`),document.createElement('br'),text('span',item.event));return entry;}));
  if(!asset.history.length)$('assignment-history').append(text('p','MOCK · Chưa có lịch sử phân bổ.'));
  $('assignment-return-help').textContent=asset.assigned?'MOCK · Có lượt giao đang mở. Thu hồi và xác nhận nhận bàn giao cần backend kiểm đúng người, bộ phận và phiên bản bản ghi.':'MOCK · Không có lượt giao đang mở để thu hồi hoặc xác nhận.';
 }
 async function load(){
  if(loading)return;loading=true;$('assignment-workspace').hidden=true;$('assignment-retry').hidden=true;
  $('assignment-message').textContent='Đang tải dữ liệu minh họa…';
  const controller=new AbortController(),timeout=setTimeout(()=>controller.abort(),10000);
  try{
   const response=await fetch('/assets/ita/mock/assignments.json',{cache:'no-store',signal:controller.signal});
   if(!response.ok)throw new Error('Unavailable');
   const data=await response.json();
   if(data.mock!==true||!Array.isArray(data.assets)||!data.assets.length||data.assets.some(asset=>!['in_use','retired'].includes(asset.status)||!Array.isArray(asset.history)))throw new Error('Invalid mock');
   assets=data.assets;$('assignment-device').replaceChildren(...assets.map(asset=>new Option(`${asset.code} · ${asset.name}`,asset.id)));
   $('assignment-scenario').value='normal';$('assignment-message').textContent='MOCK · 3 thiết bị giả. Chọn thiết bị để xem điều kiện bàn giao, cảnh báo và lịch sử.';
   $('assignment-workspace').hidden=false;render();
  }catch(error){$('assignment-message').textContent='Không tải được dữ liệu minh họa. Bấm Thử lại; chưa có thao tác ghi nào.';$('assignment-retry').hidden=false;}
  finally{clearTimeout(timeout);loading=false;}
 }
 $('assignment-device').addEventListener('change',render);
 $('assignment-recipient').addEventListener('change',()=>{$('assignment-draft').hidden=true;});
 $('assignment-preview').addEventListener('click',()=>{
  const asset=selected();if(!asset||asset.assigned||asset.status==='retired')return;
  $('assignment-draft-content').textContent=`MOCK · ${asset.code} — ${asset.name}. Bên nhận: ${$('assignment-recipient').selectedOptions[0].textContent}.`;
  $('assignment-draft').hidden=false;
 });
 $('assignment-scenario').addEventListener('change',()=>{
  const scenario=$('assignment-scenario').value;
  $('assignment-workspace').hidden=scenario!=='normal';$('assignment-retry').hidden=scenario!=='failure';
  $('assignment-message').textContent=scenario==='failure'?'MOCK · Lỗi tải dữ liệu minh họa. Bấm Thử lại.':scenario==='empty'?'MOCK · Chưa có thiết bị để phân bổ. Không có thao tác ghi.':'MOCK · Dữ liệu giả, chưa kết nối phân bổ.';
  if(scenario==='normal')render();
 });
 $('assignment-retry').addEventListener('click',load);
 let started=false;function activate(){if(location.hash==='#assignments'&&!started){started=true;load();}}
 addEventListener('ita:view',activate);activate();
})();
