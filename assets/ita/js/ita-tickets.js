(() => {
 'use strict';
 const $=id=>document.getElementById(id),live=document.body.dataset.mode==='live';
 let current=null,page=1,total=0,busy=false,stale=false,started=false,techPage=1,techTotal=0,feedbackTimer,pickerPage=1,pickerTotal=0,pickerPerPage=5,pickerQuery='';
 let statPage=1,statTotal=0,statPerPage=20,statFilter=null;
 const role=()=>window.itaLiveContext?.user.role,admin=()=>role()==='admin_it';
 const el=(tag,text)=>{const n=document.createElement(tag);n.textContent=text;return n;};
 const labels={pending:'Đang chờ xử lý',resolved:'Đã hoàn tất',failed:'Chưa khắc phục',repair:'Sửa chữa',periodic:'Bảo trì định kỳ',low:'Thấp',medium:'Vừa',high:'Cao',critical:'Nghiêm trọng','ticket.assign':'Gán người xử lý','ticket.cost':'Điều chỉnh chi phí',in_use:'Đang sử dụng',maintenance:'Bảo trì',broken:'Hỏng',retired:'Đã thanh lý'};
 const displayLabel=value=>labels[value]||value;
 function money(value){
  const text=String(value??'0'),match=/^(\d+)(?:\.(\d{1,2}))?$/.exec(text);
  if(!match)return text;
  const integer=match[1].replace(/^0+(?=\d)/,'').replace(/\B(?=(\d{3})+(?!\d))/g,'.'),fraction=(match[2]||'').replace(/0+$/,'');
  return integer+(fraction?','+fraction:'');
 }
 function badge(value){const n=el('span',displayLabel(value));n.className='ticket-badge';n.dataset.state=value;return n;}
 function localTime(value){const date=new Date(String(value).replace(' ','T')+'Z');return Number.isNaN(date.getTime())?String(value):new Intl.DateTimeFormat('vi-VN',{year:'numeric',month:'2-digit',day:'2-digit',hour:'2-digit',minute:'2-digit',hourCycle:'h23',timeZone:'Asia/Ho_Chi_Minh'}).format(date);}
 function message(text,error=false,recovery=error){
  const n=$('ticket-message'),box=$('ticket-feedback'),pending=text==='Đang thực hiện yêu cầu…';
  n.textContent=text;box.hidden=false;box.dataset.state=error?'error':pending?'loading':'success';
  n.setAttribute('role',error?'alert':'status');n.setAttribute('aria-live',error?'assertive':'polite');
  $('ticket-feedback-icon').querySelector('use').setAttribute('href',error?'#ita-icon-warning':pending?'#ita-icon-clock':'#ita-icon-check');
  $('ticket-retry').hidden=!recovery;$('ticket-dismiss').disabled=pending;
  clearTimeout(feedbackTimer);
  if(!error&&!pending)feedbackTimer=setTimeout(()=>{if(!box.contains(document.activeElement))box.hidden=true;},6000);
 }
 function historyNote(h){
  if(h.note)return h.note;
  try{
   const before=h.before_json?JSON.parse(h.before_json):{},after=h.after_json?JSON.parse(h.after_json):{};
   const lines=[];
   if(h.kind==='ticket.assign')lines.push(`Người xử lý: ${before?.technician_id||'Chưa gán'} → ${after?.technician_id||'Chưa gán'}`);
   if(h.kind==='ticket.cost')lines.push(`Chi phí: ${money(before?.cost)} → ${money(after?.cost)} VND`);
   if(after?.note)lines.push(`Lý do: ${after.note}`);
   return lines.join('\n')||'Đã ghi nhận thao tác.';
  }catch(error){return 'Không đọc được nội dung lịch sử. Vui lòng tải lại dữ liệu.';}
 }
 function selectRecord(id,focus=false){
  document.querySelectorAll('#ticket-rows tr').forEach(row=>row.classList.toggle('is-selected',row.dataset.id===String(id)));
  if(focus){$('ticket-record').focus({preventScroll:true});$('ticket-record').scrollIntoView({behavior:'instant',block:'start'});}
 }
 async function api(action,query={},body){
  const controller=new AbortController(),timer=setTimeout(()=>controller.abort(),15000);
  try{
   const response=await fetch(`/admin.php?${new URLSearchParams({op:'ita',act:action,...query})}`,{method:body?'POST':'GET',cache:'no-store',credentials:'same-origin',signal:controller.signal,headers:body?{'Content-Type':'application/json','X-CSRF-Token':document.querySelector('meta[name=ita-csrf]').content}:{},body:body?JSON.stringify(body):undefined});
   let result;try{result=await response.json();}catch(error){throw new Error(`Máy chủ trả phản hồi không hợp lệ (HTTP ${response.status}). ${body?'Chưa xác nhận đã lưu; tải lại danh sách để kiểm tra.':'Vui lòng thử tải lại.'}`);}
   if(!response.ok||!result.success)throw new Error(`${result.message||'Yêu cầu bị từ chối.'} (HTTP ${response.status})`);return result.data;
  }catch(error){if(error instanceof TypeError||error.name==='AbortError')throw new Error(body?'Chưa xác nhận kết quả lưu. Tải lại danh sách và mở phiếu trước khi thao tác lại.':'Không tải được dữ liệu. Hãy thử lại.');throw error;}
  finally{clearTimeout(timer);}
 }
 function controls(){
  $('ticket-live').querySelectorAll('button').forEach(n=>n.disabled=busy);
  $('ticket-live').querySelectorAll('input,select,textarea').forEach(n=>n.disabled=busy);
  if(!busy&&current)transitionFields();
  $('ticket-prev').disabled=busy||page<=1;$('ticket-next').disabled=busy||page*20>=total;
  $('ticket-history-prev').disabled=busy||!current||current.page<=1;$('ticket-history-next').disabled=busy||!current||current.page*20>=current.total;
  $('ticket-tech-prev').disabled=busy||techPage<=1;$('ticket-tech-next').disabled=busy||techPage*20>=techTotal;
  $('ticket-picker-prev').disabled=busy||pickerPage<=1;
  $('ticket-picker-next').disabled=busy||pickerPage*pickerPerPage>=pickerTotal;
  $('ticket-stat-prev').disabled=busy||statPage<=1;
  $('ticket-stat-next').disabled=busy||statPage*statPerPage>=statTotal;
  $('ticket-picker-results').querySelectorAll('[data-unavailable]').forEach(n=>n.disabled=true);
  if(stale)$('ticket-record').querySelectorAll('button[type=submit]').forEach(n=>n.disabled=true);
 }
 async function task(fn){if(busy)return;busy=true;controls();$('ticket-live').setAttribute('aria-busy','true');message('Đang thực hiện yêu cầu…');try{await fn();if($('ticket-message').textContent==='Đang thực hiện yêu cầu…')message('Đã tải dữ liệu.');}catch(e){message(e.message,true);}finally{busy=false;controls();$('ticket-live').removeAttribute('aria-busy');}}
 async function listing(){
  const data=await api('tickets',{page,asset_id:$('ticket-filter-asset').value,status:$('ticket-filter-status').value});page=data.page;total=data.total;
  $('ticket-rows').replaceChildren(...data.rows.map(row=>{
   const tr=el('tr','');tr.dataset.id=row.id;
   const identity=el('td',''),code=el('strong',row.asset_code),number=el('span',row.ticket_no);identity.dataset.label='Phiếu / Thiết bị';number.className='ticket-reference';identity.append(code,number);
   const category=el('td','');category.dataset.label='Loại / Mức độ';category.append(el('span',displayLabel(row.type)),el('small',`Ưu tiên: ${displayLabel(row.severity)}`));
   const status=el('td','');status.dataset.label='Trạng thái';status.append(badge(row.status));
   const cost=el('td',money(row.cost));cost.dataset.label='Chi phí (VND)';cost.className='ticket-money';tr.append(identity,category,status,cost);
   const td=el('td',''),button=el('button','Mở phiếu');button.type='button';button.className='secondary-button';button.setAttribute('aria-label',`Mở phiếu ${row.ticket_no}`);button.addEventListener('click',()=>task(async()=>{await detail(row.id);selectRecord(row.id,true);}));td.append(button);tr.append(td);return tr;
  }));
  if(!data.rows.length){const tr=el('tr',''),td=el('td','Chưa có phiếu phù hợp. Thử đổi bộ lọc hoặc tạo phiếu cho thiết bị cần xử lý.');td.className='ticket-empty';td.colSpan=5;tr.append(td);$('ticket-rows').append(tr);}
  $('ticket-page').textContent=`Trang ${page} · ${total} phiếu`;selectRecord(current?.ticket.id);
  message(`Đã tải danh sách: ${total} phiếu. ${total?'Chọn Mở phiếu để xem chi tiết.':'Chưa có phiếu phù hợp với bộ lọc.'}`);
 }
 function chooseAsset(asset){
  $('ticket-asset').value=asset.id;
  const selected=$('ticket-selected-asset');selected.textContent=`Đã chọn: ${asset.code||asset.asset_code||'#'+asset.id} · ${asset.name||'Thiết bị'} · ID ${asset.id}`;selected.hidden=false;
  $('ticket-picker').open=false;$('ticket-type').focus();
 }
 async function findAssets(nextPage=1){
  $('ticket-picker-status').textContent='Đang tìm thiết bị…';$('ticket-picker-results').replaceChildren();$('ticket-picker-pagination').hidden=true;
  try{
   const data=await api('data',{q:pickerQuery,page:nextPage});
   pickerPage=data.pagination.page;pickerTotal=data.pagination.total;pickerPerPage=data.pagination.per_page;
   $('ticket-picker-results').replaceChildren(...data.assets.map(asset=>{
    const row=el('li',''),info=el('div','');info.append(el('strong',`${asset.code} · ${asset.name}`),el('small',`${asset.department} · ${displayLabel(asset.status)} · ID ${asset.id}`));
    const select=el('button',asset.status==='retired'?'Đã thanh lý':'Chọn thiết bị');select.type='button';select.className='secondary-button';select.setAttribute('aria-label',`Chọn ${asset.code} · ${asset.name}`);
    if(asset.status==='retired'){select.dataset.unavailable='true';select.disabled=true;}else select.addEventListener('click',()=>chooseAsset(asset));
    row.append(info,select);return row;
   }));
   $('ticket-picker-status').textContent=pickerTotal?`Tìm thấy ${pickerTotal} thiết bị. Chọn thiết bị để điền vào phiếu.`:'Không tìm thấy thiết bị. Thử mã, tên hoặc số seri khác.';
   $('ticket-picker-page').textContent=`Trang ${pickerPage} · ${pickerTotal} thiết bị`;$('ticket-picker-pagination').hidden=!pickerTotal;
  }catch(error){$('ticket-picker-status').textContent='Chưa tải được thiết bị. Bấm Tìm thiết bị để thử lại.';throw error;}
 }
 function startAssetSearch(){pickerQuery=$('ticket-asset-search').value.trim();return task(()=>findAssets(1));}
 $('ticket-asset-find').addEventListener('click',startAssetSearch);
 $('ticket-asset-search').addEventListener('keydown',event=>{if(event.key==='Enter'){event.preventDefault();startAssetSearch();}});
 $('ticket-picker-prev').addEventListener('click',()=>task(()=>findAssets(pickerPage-1)));
 $('ticket-picker-next').addEventListener('click',()=>task(()=>findAssets(pickerPage+1)));
 $('ticket-asset').addEventListener('input',()=>{$('ticket-selected-asset').hidden=true;$('ticket-selected-asset').textContent='';});
 async function technicians(nextPage=1){
  const data=await api('ticket-technicians',{asset_id:current.ticket.asset_id,page:nextPage});techPage=data.page;techTotal=data.total;
  $('ticket-technician').replaceChildren(new Option('Chọn kỹ thuật viên',''),...data.rows.map(r=>new Option(`${r.fullname||r.username} (#${r.id})`,r.id)));
  $('ticket-technician').value=String(current.ticket.technician_id||'');$('ticket-tech-page').textContent=`Trang ${techPage} · ${techTotal} người`;
 }
 async function detail(id,historyPage=1){
  current=await api('ticket',{id,page:historyPage});stale=false;const t=current.ticket,asset=current.asset;
  $('ticket-record').hidden=false;selectRecord(t.id);$('ticket-record-title').textContent=t.ticket_no;$('ticket-details').replaceChildren();
  for(const [label,value] of [['Thiết bị',`${asset.asset_code} · ${asset.name}`],['Trạng thái',displayLabel(t.status)],['Loại',displayLabel(t.type)],['Nội dung',t.issue],['Người xử lý',t.technician_id||'Chưa gán'],['Chi phí (VND)',money(t.cost)],['Kết quả',t.resolution_note||'Chưa ghi nhận']])$('ticket-details').append(el('dt',label),el('dd',String(value)));
  $('ticket-ready-badge').hidden=!asset.awaiting_ready;$('ticket-ready').hidden=!window.ItaPolicy.can('ticket.ready',admin())||!asset.awaiting_ready;
  $('ticket-assign').hidden=!window.ItaPolicy.can('ticket.assign',role()!=='support_technician')||t.status!=='pending';
  $('ticket-transition').hidden=!window.ItaPolicy.can('ticket.process',role()!=='system_manager')||t.status==='resolved';
  $('ticket-to-status').replaceChildren(...(t.status==='pending'?['resolved','failed']:['pending']).map(s=>new Option(displayLabel(s),s)));
  $('ticket-cost').value=t.cost;$('ticket-handled').value='';$('ticket-resolution').value='';
  $('ticket-cost-form').hidden=!window.ItaPolicy.can('ticket.cost',admin())||t.status==='pending';$('ticket-correct-cost').value=t.cost;$('ticket-cost-reason').value='';
  $('ticket-assign-note').value='';$('ticket-ready-reason').value='';transitionFields();
  $('ticket-history').replaceChildren(...current.history.map(h=>{const item=el('details','');item.append(el('summary',`${localTime(h.created_at)} · #${h.user_id} · ${h.kind==='status'?`${h.from_status?displayLabel(h.from_status):'Tạo phiếu'} → ${displayLabel(h.to_status)}`:displayLabel(h.kind)}`),el('pre',historyNote(h)));return item;}));
  if(!current.history.length)$('ticket-history').append(el('p','Chưa có lịch sử.'));
  $('ticket-history-page').textContent=`Trang ${current.page} · ${current.total} sự kiện`;
  if(!$('ticket-assign').hidden)try{await technicians();}catch(error){message('Đã tải phiếu, nhưng chưa tải được người xử lý. '+error.message,true);}
 }
 function transitionFields(){const closing=$('ticket-to-status').value!=='pending';$('ticket-handled').required=closing;$('ticket-handled').disabled=!closing;$('ticket-cost').readOnly=!closing||(!window.ItaPolicy.can('ticket.cost',admin())&&!!current?.ticket.handled_date);}
 async function save(body){
  stale=true;const result=await api('ticket-save',{}, {id:current?.ticket.id,version:current?.ticket.version,...body});message('Đã lưu, đang đọc lại…');
  try{await listing();if(result.id)await detail(result.id);else if(current)await detail(current.ticket.id);stale=false;if(body.operation==='create')$('ticket-create-section').open=false;message('Đã lưu và đọc lại phiếu.');dispatchEvent(new Event('ita:refresh'));return true;}
  catch(error){message('Đã lưu nhưng chưa tải lại được. Mở lại phiếu trước khi tiếp tục.',true);$('ticket-record').hidden=true;return false;}
 }
 $('ticket-dismiss').addEventListener('click',()=>{$('ticket-feedback').hidden=true;});
 $('ticket-retry').addEventListener('click',()=>task(async()=>{await listing();if(current)await detail(current.ticket.id);}));
 document.querySelector('.ticket-create-link').addEventListener('click',e=>{e.preventDefault();$('ticket-create-section').open=true;$('ticket-asset').focus({preventScroll:true});$('ticket-create-section').scrollIntoView({behavior:'instant',block:'start'});});
 $('ticket-close-record').addEventListener('click',()=>{$('ticket-record').hidden=true;selectRecord(null);$('ticket-filter-asset').focus();});
 $('ticket-live').addEventListener('invalid',e=>{const label=document.querySelector(`label[for="${e.target.id}"]`)?.textContent||'Thông tin nhập';message(`${label}: ${e.target.validationMessage}`,true,false);},true);
 $('ticket-filter').addEventListener('submit',e=>{e.preventDefault();task(async()=>{page=1;await listing();if(current)await detail(current.ticket.id);});});
 $('ticket-prev').addEventListener('click',()=>task(async()=>{page--;await listing();}));$('ticket-next').addEventListener('click',()=>task(async()=>{page++;await listing();}));
 for(const [direction,delta] of [['prev',-1],['next',1]]){
  $(`ticket-history-${direction}`).addEventListener('click',()=>task(()=>detail(current.ticket.id,current.page+delta)));
  $(`ticket-tech-${direction}`).addEventListener('click',()=>task(()=>technicians(techPage+delta)));
 }
 $('ticket-create').addEventListener('submit',e=>{e.preventDefault();task(()=>save({operation:'create',asset_id:$('ticket-asset').value,type:$('ticket-type').value,severity:$('ticket-severity').value,note:$('ticket-issue').value}));});
 $('ticket-assign').addEventListener('submit',e=>{e.preventDefault();task(()=>save({operation:'assign',technician_id:$('ticket-technician').value,note:$('ticket-assign-note').value}));});
 $('ticket-transition').addEventListener('submit',e=>{e.preventDefault();task(()=>save({operation:'transition',status:$('ticket-to-status').value,handled_date:$('ticket-handled').value,cost:$('ticket-cost').value,note:$('ticket-resolution').value}));});
 $('ticket-cost-form').addEventListener('submit',e=>{e.preventDefault();task(async()=>{const old=current.ticket.cost,next=$('ticket-correct-cost').value,note=$('ticket-cost-reason').value;const confirmed=await window.ItaUi.confirm({title:'Xác nhận tổng chi phí mới',message:money(old)+' VND → '+money(next)+' VND. Đây là tổng chi phí của phiếu, không phải số tiền cộng thêm. Lý do: '+note,confirmLabel:'Lưu chi phí mới'});if(!confirmed){message('Đã hủy điều chỉnh. Chi phí chưa thay đổi.');return;}if(await save({operation:'cost',cost:next,note}))message('Đã lưu tổng chi phí '+money(next)+' VND và đọc lại phiếu.');});});
 $('ticket-ready').addEventListener('submit',e=>{e.preventDefault();task(()=>save({operation:'ready',asset_id:current.asset.id,asset_version:current.asset.version,note:$('ticket-ready-reason').value}));});
 $('ticket-to-status').addEventListener('change',transitionFields);
 $('ticket-year').value=new Intl.DateTimeFormat('en',{year:'numeric',timeZone:'Asia/Ho_Chi_Minh'}).format(new Date());
 async function statistics(nextPage,filter=statFilter){
   const data=await api('ticket-statistics',{...filter,page:nextPage});
   if(Number(data.year)!==Number(filter.year))throw new Error('Năm thống kê trả về không khớp năm đã chọn. Hãy tải lại.');
   statFilter={...filter};statPage=data.page;statTotal=data.total;statPerPage=data.per_page;
   const table=el('table','');table.className='license-table ticket-stat-table';
   const caption=el('caption',`Kết quả năm ${data.year} · giờ Việt Nam · ${statFilter.asset_id?'ID thiết bị '+statFilter.asset_id:'mọi thiết bị trong phạm vi'}`);table.append(caption);
   const head=el('thead',''),headers=el('tr','');for(const text of ['Thiết bị','Loại phiếu','Trạng thái','Số phiếu','Chi phí (VND)']){const th=el('th',text);th.scope='col';headers.append(th);}head.append(headers);table.append(head);
   const body=el('tbody','');for(const r of data.rows){const row=el('tr','');for(const value of [r.asset_code||'Thiết bị',displayLabel(r.type),displayLabel(r.status),`${r.n} phiếu`,money(r.cost)])row.append(el('td',String(value)));body.append(row);}table.append(body);
   $('ticket-statistics').replaceChildren(data.rows.length?table:el('p','Chưa có phiếu trong năm này.'));
   $('ticket-stat-summary').hidden=false;$('ticket-stat-summary').textContent=`Toàn bộ bộ lọc: ${data.summary.ticket_count} phiếu · ${money(data.summary.cost)} VND. Mỗi dòng là một nhóm thiết bị / loại / trạng thái.`;
   $('ticket-stat-page').textContent=`Trang ${statPage}/${Math.max(1,Math.ceil(statTotal/statPerPage))} · ${statTotal} nhóm`;
   $('ticket-stat-pagination').hidden=statTotal===0;
   message(data.summary.ticket_count===0?`Không có phiếu mở trong năm ${data.year}. Đổi năm nếu bạn muốn xem kỳ khác.`:`Đã tải thống kê theo năm mở phiếu ${data.year}: ${data.summary.ticket_count} phiếu, trang ${statPage}.`);
 }
 const currentYear=$('ticket-year').value;const yearReset=el('button','Năm hiện tại');yearReset.type='button';yearReset.className='secondary-button';yearReset.id='ticket-year-current';yearReset.addEventListener('click',()=>{$('ticket-year').value=currentYear;$('ticket-year').focus();});$('ticket-stat-form').append(yearReset);
 $('ticket-stat-form').addEventListener('submit',e=>{e.preventDefault();task(()=>statistics(1,{year:$('ticket-year').value,asset_id:$('ticket-filter-asset').value}));});
 $('ticket-stat-prev').addEventListener('click',()=>task(()=>statistics(statPage-1)));
 $('ticket-stat-next').addEventListener('click',()=>task(()=>statistics(statPage+1)));
 addEventListener('ita:asset',event=>{
  if(!window.ItaPolicy.can('ticket.read',true))return;const dialog=$('asset-dialog');dialog.querySelector('.asset-ticket-link')?.remove();dialog.querySelector('.asset-ticket-history')?.remove();
  const link=el('button','Phiếu & lịch sử bảo trì');link.type='button';link.className='secondary-button asset-ticket-link';link.addEventListener('click',()=>{dialog.close();$('ticket-filter-asset').value=event.detail.id;$('ticket-asset').value=event.detail.id;$('ticket-selected-asset').hidden=true;location.hash='tickets';if(started)task(async()=>{page=1;await listing();});});dialog.append(link);
  const history=el('details','');history.className='asset-ticket-history';history.append(el('summary','Xem lịch sử bảo trì của thiết bị'));
  const content=el('div',''),feedback=el('p','');feedback.setAttribute('role','status');history.append(feedback,content);dialog.append(history);
  let loaded=false,loading=false;
  async function load(assetPage=1,ticketId=null,eventPage=1){
   if(loading)return;loading=true;feedback.textContent='Đang tải lịch sử…';content.querySelectorAll('button').forEach(n=>n.disabled=true);
   try{
    const data=await api(ticketId?'ticket':'tickets',ticketId?{id:ticketId,page:eventPage}:{asset_id:event.detail.id,page:assetPage});if(!history.isConnected)return;
    content.replaceChildren();
    const control=(label,callback)=>{const b=el('button',label);b.type='button';b.className='secondary-button';b.addEventListener('click',callback);content.append(b);};
    if(ticketId){
     content.append(el('h3',data.ticket.ticket_no));
     if(data.asset.awaiting_ready)content.append(el('p','Chờ Admin xác nhận sẵn sàng — thiết bị chưa phân bổ.'));
     for(const h of data.history)content.append(el('p',`${localTime(h.created_at)} · #${h.user_id} · ${h.kind==='status'?`${h.from_status?displayLabel(h.from_status):'Tạo phiếu'} → ${displayLabel(h.to_status)}`:displayLabel(h.kind)}`),el('pre',historyNote(h)));
     control('Về danh sách phiếu',()=>load(assetPage));if(data.page>1)control('Sự kiện trước',()=>load(assetPage,ticketId,data.page-1));if(data.page*20<data.total)control('Sự kiện sau',()=>load(assetPage,ticketId,data.page+1));
    }else{
     for(const t of data.rows)control(`${t.ticket_no} · ${t.type} · ${t.status}`,()=>load(data.page,t.id));
     if(data.page>1)control('Phiếu trước',()=>load(data.page-1));if(data.page*20<data.total)control('Phiếu sau',()=>load(data.page+1));
    }
    feedback.textContent=`Trang ${data.page} · ${data.total} ${ticketId?'sự kiện':'phiếu'}`;
   }catch(error){feedback.textContent=error.message;const retry=el('button','Thử lại lịch sử');retry.type='button';retry.className='secondary-button';retry.addEventListener('click',()=>load(assetPage,ticketId,eventPage));content.append(retry);}
   finally{loading=false;content.querySelectorAll('button').forEach(n=>n.disabled=false);}
  }
  history.addEventListener('toggle',()=>{if(history.open&&!loaded){loaded=true;load();}});
 });
 function activate(){if(location.hash!=='#tickets')return;if(!live){message('MOCK · Chỉ xem cấu trúc phiếu, chưa ghi dữ liệu.');return;}if(!window.itaLiveContext)return;$('ticket-live').hidden=!window.ItaPolicy.can('ticket.read',true);if($('ticket-live').hidden)return;$('ticket-create-section').hidden=!window.ItaPolicy.can('ticket.create',true);document.querySelector('.ticket-create-link').hidden=!window.ItaPolicy.can('ticket.create',true);$('ticket-stat-form').hidden=!(window.ItaPolicy.can('ticket.statistics',true)||window.ItaPolicy.can('ticket.statistics_self',true));if(!started){started=true;task(listing);}}
 addEventListener('ita:view',activate);addEventListener('ita:data',activate);activate();
})();
