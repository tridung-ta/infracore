/* Live Excel workflow. Static preview remains isolated in ita-import.js. */
(() => {
 'use strict';
 if(document.body.dataset.mode!=='live')return;
 const $=id=>document.getElementById(id);
 let batch=null,busy=false,uncertain=false,unknown=false;
 const controls=['import-upload','import-confirm','import-errors','import-check','import-template','import-export-download','import-previous','import-next'];
 const allowed=()=>['admin_it','system_manager'].includes(window.itaLiveContext?.user.role);
 function message(text,error=false){$('import-summary').textContent=text;$('import-summary').setAttribute('role',error?'alert':'status');}
 function state(){
  const disabled=busy||!allowed();
  $('import-file').disabled=disabled||unknown;
  controls.forEach(id=>$(id).disabled=disabled);
  $('import-upload').disabled=disabled||unknown||!$('import-file').files.length;
  $('import-confirm').disabled=disabled||uncertain||!batch||batch.error_rows>0||batch.status!=='validated';
  $('import-errors').disabled=disabled||!batch||!batch.error_rows;
  $('import-check').disabled=disabled||!batch;
  $('import-previous').disabled=disabled||!batch||batch.page<=1;
  $('import-next').disabled=disabled||!batch||batch.page*batch.per_page>=batch.total_rows;
  $('import-workspace').setAttribute('aria-busy',String(busy));
 }
 async function request(action,options={}){
  const controller=new AbortController(),timeout=setTimeout(()=>controller.abort(),60000);
  try{
   const response=await fetch(`/admin.php?op=ita&act=${action}`,{cache:'no-store',credentials:'same-origin',...options,signal:controller.signal});
   if(response.status===401)throw new Error('Phiên đăng nhập đã hết hạn. Hãy đăng nhập lại.');
   if(!response.ok){const data=await response.json(),error=new Error(data.message||'Thao tác chưa hoàn tất.');error.status=response.status;throw error;}
   return response;
  }catch(error){if(error.name==='AbortError')throw new Error('Chưa nhận được phản hồi từ máy chủ.');throw error;}
  finally{clearTimeout(timeout);}
 }
 async function task(callback){
  if(busy||!allowed())return;
  busy=true;state();
  try{await callback();}catch(error){message(error.message||'Không kết nối được. Hãy thử lại.',true);}finally{busy=false;state();}
 }
 function render(data){
  batch=data;uncertain=false;unknown=false;
  $('import-rows').replaceChildren(...data.rows.map(row=>{
   const tr=document.createElement('tr');for(const value of [row.line,row.asset_code,row.name,row.error||'Hợp lệ']){const td=document.createElement('td');td.textContent=String(value);tr.append(td);}return tr;
  }));
  $('import-page-summary').textContent=`Trang ${data.page}/${Math.ceil(data.total_rows/data.per_page)} · ${data.total_rows} dòng`;
  message(data.status==='completed'?`Lô #${data.batch_id} đã hoàn tất: ${data.ok_rows} tài sản. Không nhập lại file này.`:
   `Lô #${data.batch_id} · ${data.total_rows} dòng: ${data.ok_rows} hợp lệ, ${data.error_rows} lỗi. Bản xem trước có hiệu lực 2 giờ. Chưa ghi tài sản.`);
  state();
 }
 async function preview(page){
  try{render((await (await request(`import-preview&batch_id=${batch.batch_id}&page=${page}`)).json()).data);}
  catch(error){uncertain=true;if(error.status)unknown=false;throw error;}
 }
 async function download(action,name){
  const response=await request(action),type=response.headers.get('content-type')||'';
  if(!type.includes('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'))throw new Error('Máy chủ chưa trả về file Excel hợp lệ.');
  const url=URL.createObjectURL(await response.blob()),link=document.createElement('a');
  link.href=url;link.download=name;document.body.append(link);link.click();link.remove();setTimeout(()=>URL.revokeObjectURL(url),1000);
  message('Đã tải file Excel.');
 }
 $('import-file').addEventListener('change',()=>{
  batch=null;uncertain=false;unknown=false;$('import-rows').replaceChildren();$('import-page-summary').textContent='Chưa có bản xem trước';
  const file=$('import-file').files[0],valid=file&&/\.xlsx$/i.test(file.name)&&file.size>0&&file.size<=5*1024*1024;
  if(file&&!valid)$('import-file').value='';
  $('import-file-status').textContent=valid?`Đã chọn ${file.name}. Bấm kiểm tra để tải lên; chưa ghi tài sản.`:'Chọn file .xlsx có dữ liệu, tối đa 5 MB.';
  message('Chưa có bản xem trước của file này.');state();
 });
 $('import-upload').addEventListener('click',()=>task(async()=>{
  batch=null;uncertain=false;const body=new FormData();body.append('file',$('import-file').files[0]);
  message('Đang tải và kiểm tra file…');
  const response=await request('import-upload',{method:'POST',headers:{'X-CSRF-Token':document.querySelector('meta[name=ita-csrf]').content},body});
  render((await response.json()).data);
 }));
 $('import-confirm').addEventListener('click',()=>task(async()=>{
  if(!batch||batch.status!=='validated'||batch.error_rows)return;
  uncertain=true;unknown=true;message('Đang ghi toàn bộ lô. Vui lòng chờ…');
  try{
   const response=await request('import-confirm',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':document.querySelector('meta[name=ita-csrf]').content},body:JSON.stringify({batch_id:batch.batch_id,confirm_token:batch.confirm_token})});
   const result=(await response.json()).data;batch.status='completed';uncertain=false;unknown=false;
   message(`Đã nhập thành công ${result.imported_rows} tài sản${result.already_completed?' (lô đã hoàn tất trước đó, không ghi lại)':''}.`);dispatchEvent(new Event('ita:refresh'));
  }catch(error){if(error.status)unknown=false;throw new Error(`${error.message} Kiểm tra trạng thái lô trước khi thử lại. Không tải lại file khi chưa biết kết quả.`);}
 }));
 $('import-check').addEventListener('click',()=>task(()=>preview(batch.page)));
 $('import-previous').addEventListener('click',()=>task(()=>preview(batch.page-1)));
 $('import-next').addEventListener('click',()=>task(()=>preview(batch.page+1)));
 $('import-errors').addEventListener('click',()=>task(()=>download(`import-errors&batch_id=${batch.batch_id}`,'ita-import-errors.xlsx')));
 $('import-template').addEventListener('click',()=>task(()=>download('import-template','ita-assets-template.xlsx')));
 $('import-export-download').addEventListener('click',()=>task(async()=>{
  const query=new URLSearchParams({status:$('import-export-status').value,q:$('asset-search').value,department_id:$('department-filter').value});
  for(const id of ['category_id','purchase_year','warranty_from','warranty_to'])if($('filter-'+id)?.value)query.set(id,$('filter-'+id).value);
  await download(`asset-export&${query}`,'ita-assets.xlsx');
 }));
 $('import-mode').textContent='Nhập tài sản từ Excel: kiểm tra trước, xác nhận sau. Không tự sửa hoặc ghi đè tài sản đã có.';
 $('import-file-status').textContent='Chưa chọn file. Sau khi xem trước, file được giữ để xác nhận trong 2 giờ.';
 $('import-confirm-help').textContent='Chỉ ghi khi toàn bộ lô hợp lệ. Lỗi bất kỳ dòng nào sẽ hủy toàn bộ lần ghi. Chọn lại file nếu cần sửa dữ liệu.';
 $('import-export-help').textContent='Xuất các tài sản trong phạm vi quyền của bạn. File giữ nguyên mã và không chạy công thức.';
 $('imports-panel').querySelector('.import-demo-controls').hidden=true;
 $('imports-panel').querySelector('caption').textContent='Kết quả kiểm tra các dòng trong file Excel';
 $('imports-panel').querySelector('thead th:last-child').textContent='Kết quả kiểm tra';
 $('import-nav').querySelector('.nav-count')?.remove();
 $('import-summary').textContent='Chọn file để kiểm tra trước khi nhập tài sản.';
 $('import-summary').classList.add('admin-message');
 $('import-workspace').querySelector('.import-tabs').after($('import-summary'));
 for(const [id,label] of [['import-template','Tải mẫu Excel'],['import-confirm','Xác nhận nhập toàn bộ lô'],['import-errors','Tải Excel lỗi'],['import-export-download','Xuất Excel']])$(id).textContent=label;
 $('import-upload').hidden=false;$('import-check').hidden=false;
 addEventListener('ita:data',state);state();
})();
