(() => {
 'use strict';
 const $=id=>document.getElementById(id),live=document.body.dataset.mode==='live';
 let current=null,page=1,total=0,busy=false,stale=false,started=false,techPage=1,techTotal=0;
 const role=()=>window.itaLiveContext?.user.role,admin=()=>role()==='admin_it';
 const el=(tag,text)=>{const n=document.createElement(tag);n.textContent=text;return n;};
 function message(text,error=false){const n=$('ticket-message');n.textContent=text;n.setAttribute('role',error?'alert':'status');n.classList.toggle('danger-text',error);}
 async function api(action,query={},body){
  const controller=new AbortController(),timer=setTimeout(()=>controller.abort(),15000);
  try{
   const response=await fetch(`/admin.php?${new URLSearchParams({op:'ita',act:action,...query})}`,{method:body?'POST':'GET',cache:'no-store',credentials:'same-origin',signal:controller.signal,headers:body?{'Content-Type':'application/json','X-CSRF-Token':document.querySelector('meta[name=ita-csrf]').content}:{},body:body?JSON.stringify(body):undefined});
   const result=await response.json();if(!response.ok||!result.success)throw new Error(result.message||'Yêu cầu bị từ chối.');return result.data;
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
  if(stale)$('ticket-record').querySelectorAll('button[type=submit]').forEach(n=>n.disabled=true);
 }
 async function task(fn){if(busy)return;busy=true;controls();$('ticket-live').setAttribute('aria-busy','true');try{await fn();}catch(e){message(e.message,true);}finally{busy=false;controls();$('ticket-live').removeAttribute('aria-busy');}}
 async function listing(){
  const data=await api('tickets',{page,asset_id:$('ticket-filter-asset').value,status:$('ticket-filter-status').value});page=data.page;total=data.total;
  $('ticket-rows').replaceChildren(...data.rows.map(row=>{
   const tr=el('tr','');for(const text of [`${row.ticket_no} · ${row.asset_code}`,`${row.type} · ${row.severity}`,row.status,row.cost])tr.append(el('td',text));
   const td=el('td',''),button=el('button','Mở phiếu');button.type='button';button.className='secondary-button';button.addEventListener('click',()=>task(()=>detail(row.id)));td.append(button);tr.append(td);return tr;
  }));
  if(!data.rows.length){const tr=el('tr',''),td=el('td','Không có phiếu phù hợp.');td.colSpan=5;tr.append(td);$('ticket-rows').append(tr);}
  $('ticket-page').textContent=`Trang ${page} · ${total} phiếu`;
 }
 async function technicians(nextPage=1){
  const data=await api('ticket-technicians',{asset_id:current.ticket.asset_id,page:nextPage});techPage=data.page;techTotal=data.total;
  $('ticket-technician').replaceChildren(new Option('Chọn kỹ thuật viên',''),...data.rows.map(r=>new Option(`${r.fullname||r.username} (#${r.id})`,r.id)));
  $('ticket-technician').value=String(current.ticket.technician_id||'');$('ticket-tech-page').textContent=`Trang ${techPage} · ${techTotal} người`;
 }
 async function detail(id,historyPage=1){
  current=await api('ticket',{id,page:historyPage});stale=false;const t=current.ticket,asset=current.asset;
  $('ticket-record').hidden=false;$('ticket-record-title').textContent=t.ticket_no;$('ticket-details').replaceChildren();
  for(const [label,value] of [['Thiết bị',`${asset.asset_code} · ${asset.name}`],['Trạng thái',t.status],['Loại',t.type],['Nội dung',t.issue],['Người xử lý',t.technician_id||'Chưa gán'],['Chi phí (VND)',t.cost],['Kết quả',t.resolution_note||'Chưa ghi nhận']])$('ticket-details').append(el('dt',label),el('dd',String(value)));
  $('ticket-ready-badge').hidden=!asset.awaiting_ready;$('ticket-ready').hidden=!admin()||!asset.awaiting_ready;
  $('ticket-assign').hidden=role()==='support_technician'||t.status!=='pending';
  $('ticket-transition').hidden=role()==='system_manager'||t.status==='resolved';
  $('ticket-to-status').replaceChildren(...(t.status==='pending'?['resolved','failed']:['pending']).map(s=>new Option(s,s)));
  $('ticket-cost').value=t.cost;$('ticket-handled').value='';$('ticket-resolution').value='';
  $('ticket-cost-form').hidden=!admin()||t.status==='pending';$('ticket-correct-cost').value=t.cost;$('ticket-cost-reason').value='';
  $('ticket-assign-note').value='';$('ticket-ready-reason').value='';transitionFields();
  $('ticket-history').replaceChildren(...current.history.map(h=>{const item=el('details','');item.append(el('summary',`${h.created_at} UTC · #${h.user_id} · ${h.kind==='status'?`${h.from_status||'Tạo'} → ${h.to_status}`:h.kind}`),el('pre',h.note||JSON.stringify({before:h.before_json?JSON.parse(h.before_json):null,after:h.after_json?JSON.parse(h.after_json):null},null,2)));return item;}));
  if(!current.history.length)$('ticket-history').append(el('p','Chưa có lịch sử.'));
  $('ticket-history-page').textContent=`Trang ${current.page} · ${current.total} sự kiện`;
  if(!$('ticket-assign').hidden)try{await technicians();}catch(error){message('Đã tải phiếu, nhưng chưa tải được người xử lý. '+error.message,true);}
 }
 function transitionFields(){const closing=$('ticket-to-status').value!=='pending';$('ticket-handled').required=closing;$('ticket-handled').disabled=!closing;$('ticket-cost').readOnly=!closing||(!admin()&&!!current?.ticket.handled_date);}
 async function save(body){
  stale=true;const result=await api('ticket-save',{}, {id:current?.ticket.id,version:current?.ticket.version,...body});message('Đã lưu, đang đọc lại…');
  try{await listing();if(result.id)await detail(result.id);else if(current)await detail(current.ticket.id);stale=false;message('Đã lưu và đọc lại phiếu.');dispatchEvent(new Event('ita:refresh'));}
  catch(error){message('Đã lưu nhưng chưa tải lại được. Mở lại phiếu trước khi tiếp tục.',true);$('ticket-record').hidden=true;}
 }
 $('ticket-filter').addEventListener('submit',e=>{e.preventDefault();task(async()=>{page=1;await listing();if(current)await detail(current.ticket.id);});});
 $('ticket-prev').addEventListener('click',()=>task(async()=>{page--;await listing();}));$('ticket-next').addEventListener('click',()=>task(async()=>{page++;await listing();}));
 for(const [direction,delta] of [['prev',-1],['next',1]]){
  $(`ticket-history-${direction}`).addEventListener('click',()=>task(()=>detail(current.ticket.id,current.page+delta)));
  $(`ticket-tech-${direction}`).addEventListener('click',()=>task(()=>technicians(techPage+delta)));
 }
 $('ticket-create').addEventListener('submit',e=>{e.preventDefault();task(()=>save({operation:'create',asset_id:$('ticket-asset').value,type:$('ticket-type').value,severity:$('ticket-severity').value,note:$('ticket-issue').value}));});
 $('ticket-assign').addEventListener('submit',e=>{e.preventDefault();task(()=>save({operation:'assign',technician_id:$('ticket-technician').value,note:$('ticket-assign-note').value}));});
 $('ticket-transition').addEventListener('submit',e=>{e.preventDefault();task(()=>save({operation:'transition',status:$('ticket-to-status').value,handled_date:$('ticket-handled').value,cost:$('ticket-cost').value,note:$('ticket-resolution').value}));});
 $('ticket-cost-form').addEventListener('submit',e=>{e.preventDefault();task(()=>save({operation:'cost',cost:$('ticket-correct-cost').value,note:$('ticket-cost-reason').value}));});
 $('ticket-ready').addEventListener('submit',e=>{e.preventDefault();task(()=>save({operation:'ready',asset_id:current.asset.id,asset_version:current.asset.version,note:$('ticket-ready-reason').value}));});
 $('ticket-to-status').addEventListener('change',transitionFields);
 $('ticket-year').value=new Intl.DateTimeFormat('en',{year:'numeric',timeZone:'Asia/Ho_Chi_Minh'}).format(new Date());
 $('ticket-stat-form').addEventListener('submit',e=>{e.preventDefault();task(async()=>{const data=await api('ticket-statistics',{year:$('ticket-year').value,asset_id:$('ticket-filter-asset').value});$('ticket-statistics').replaceChildren(...data.rows.map(r=>el('p',`${r.asset_code||'Thiết bị'} · ${r.type} · ${r.status}: ${r.n} phiếu · ${r.cost} VND`)));if(!data.rows.length)$('ticket-statistics').append(el('p','Chưa có phiếu trong năm này.'));});});
 addEventListener('ita:asset',event=>{
  const dialog=$('asset-dialog');dialog.querySelector('.asset-ticket-link')?.remove();dialog.querySelector('.asset-ticket-history')?.remove();
  const link=el('button','Phiếu & lịch sử bảo trì');link.type='button';link.className='secondary-button asset-ticket-link';link.addEventListener('click',()=>{dialog.close();$('ticket-filter-asset').value=event.detail.id;$('ticket-asset').value=event.detail.id;location.hash='tickets';if(started)task(async()=>{page=1;await listing();});});dialog.append(link);
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
     for(const h of data.history)content.append(el('p',`${h.created_at} UTC · #${h.user_id} · ${h.kind==='status'?`${h.from_status||'Tạo'} → ${h.to_status}`:h.kind}`),el('pre',h.note||JSON.stringify({before:h.before_json?JSON.parse(h.before_json):null,after:h.after_json?JSON.parse(h.after_json):null},null,2)));
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
 function activate(){if(location.hash!=='#tickets')return;if(!live){message('MOCK · Chỉ xem cấu trúc phiếu, chưa ghi dữ liệu.');return;}if(!window.itaLiveContext)return;$('ticket-live').hidden=false;if(!started){started=true;task(listing);}}
 addEventListener('ita:view',activate);addEventListener('ita:data',activate);activate();
})();
