/* Presentation-only import workflow. Never reads file contents or calls write APIs. */
(() => {
 'use strict';
 const $=id=>document.getElementById(id);
 const steps=['guide','preview','export'];let rows=[],page=1;
 function review(data){
  $('import-review').hidden=!data;if(!data)return;
  for(const [id,key]of [['total','total_rows'],['valid','ok_rows'],['invalid','error_rows']])$('import-'+id).textContent=Number(data[key]).toLocaleString('vi-VN');
  const errors=Number(data.error_rows)>0;
  $('import-review-help').dataset.state=errors?'error':'ready';
  $('import-review-help').textContent=data.status==='completed'?'Lô này đã nhập xong. Không xác nhận lại.':errors?'Còn '+data.error_rows+' dòng cần sửa. Tải Excel lỗi, sửa trên máy rồi chọn lại file để kiểm tra. Chưa nhập tài sản nào.':'Tất cả dòng đã qua kiểm tra. Hãy xem lại nội dung trước khi xác nhận nhập cả lô.';
  if(document.body.dataset.mode!=='live')$('import-review-help').textContent='MOCK · '+$('import-review-help').textContent;
  $('import-code-help').hidden=!errors;
 }
 window.ItaImportUi={review};
 $('import-clear').addEventListener('click',()=>{if(document.body.dataset.mode==='live')return;$('import-file').value='';$('import-file-status').textContent='Đã bỏ file. Chưa đọc hoặc tải lên.';$('import-clear').disabled=true;});

 function tab(index){
  steps.forEach((step,i)=>{const button=$(`import-${step}-tab`);button.setAttribute('aria-selected',String(i===index));button.tabIndex=i===index?0:-1;$(`import-${step}`).hidden=i!==index;});
 }
 steps.forEach((step,index)=>{
  const button=$(`import-${step}-tab`);button.addEventListener('click',()=>tab(index));
  button.addEventListener('keydown',event=>{
   if(!['ArrowLeft','ArrowRight','Home','End'].includes(event.key))return;event.preventDefault();
   const next=event.key==='Home'?0:event.key==='End'?2:(index+(event.key==='ArrowRight'?1:2))%3;tab(next);$(`import-${steps[next]}-tab`).focus();
  });
 });
 $('import-file').addEventListener('change',()=>{
  if(document.body.dataset.mode==='live')return;
  const file=$('import-file').files[0];
  if(!file){$('import-file-status').textContent='Chưa chọn file.';return;}
  const valid=/\.xlsx$/i.test(file.name)&&file.size>0&&file.size<=5*1024*1024;
  $('import-file-status').textContent=valid?`Đã chọn ${file.name} · Chưa upload hoặc đọc nội dung. Kết quả MOCK bên dưới độc lập với file này.`:'Chọn file .xlsx có dữ liệu, tối đa 5 MB. Đây chỉ là kiểm tra tên/dung lượng, chưa xác minh nội dung Excel.';
  if(!valid)$('import-file').value='';$('import-clear').disabled=!valid;
 });
 function render(){
  const start=(page-1)*5;$('import-rows').replaceChildren(...rows.slice(start,start+5).map(row=>{
   const tr=document.createElement('tr');for(const value of row){const td=document.createElement('td');td.textContent=String(value);tr.append(td);}return tr;
  }));
  $('import-page-summary').textContent=rows.length?`MOCK · Dòng ${start+1}–${Math.min(start+5,rows.length)}/${rows.length}`:'Không có dòng minh họa';
  $('import-previous').disabled=page<=1;$('import-next').disabled=page*5>=rows.length;
 }
 $('import-demo').addEventListener('click',()=>{
  const scenario=$('import-scenario').value;page=1;rows=[];
  if(['valid','errors'].includes(scenario))rows=Array.from({length:12},(_,i)=>[i+2,`MOCK-${String(i+1).padStart(3,'0')}`,`Tài sản minh họa ${i+1}`,scenario==='errors'&&i%3===0?'Lỗi minh họa: mã trùng hoặc bộ phận ngoài phạm vi':'Hợp lệ trong kịch bản MOCK']);
  const errorCount=rows.filter(row=>row[3].startsWith('Lỗi')).length;
  $('import-summary').textContent=scenario==='failure'?'Không tải được bản xem trước minh họa. Chọn kịch bản khác rồi thử lại.':rows.length?`MOCK · ${rows.length} dòng minh họa: ${rows.length-errorCount} hợp lệ, ${errorCount} lỗi. Chưa kiểm tra file hoặc DB.`:'MOCK · Không có dữ liệu minh họa. Chưa đọc file đã chọn.';
  review(rows.length?{total_rows:rows.length,ok_rows:rows.length-errorCount,error_rows:errorCount,status:'validated'}:null);render();$('import-summary').focus({preventScroll:true});
 });
 $('import-previous').addEventListener('click',()=>{if(document.body.dataset.mode!=='live'){page--;render();}});$('import-next').addEventListener('click',()=>{if(document.body.dataset.mode!=='live'){page++;render();}});
 function permission(){
  const denied=document.body.dataset.mode==='live'&&(window.ItaPolicy.enabled()?!(window.ItaPolicy.can('import.read')||window.ItaPolicy.can('asset.export')):window.itaLiveContext?.user.role==='support_technician');
  $('import-nav').hidden=!!denied;$('import-workspace').hidden=!!denied;$('import-denied').hidden=!denied;
 }
 addEventListener('ita:data',permission);addEventListener('ita:view',permission);permission();
})();
