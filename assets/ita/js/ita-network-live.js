(() => {
 'use strict';
 if(document.body.dataset.mode!=='live')return;
 const $=id=>document.getElementById(id),ns='http://www.w3.org/2000/svg';
 const labels={in_use:'Đang dùng',maintenance:'Bảo trì',broken:'Hỏng',retired:'Thanh lý'};
 let visible={nodes:[],links:[]},documentData=null,sequence=0,controller=null,busy=false,posting=false,page=1,applied={q:'',department_id:''};
 const role=()=>window.itaLiveContext?.user?.role,allowed=()=>['admin_it','system_manager'].includes(role());
 const node=(tag,text)=>{const n=document.createElement(tag);n.textContent=text;return n;};
 function svg(tag,attrs={}){const n=document.createElementNS(ns,tag);for(const [k,v]of Object.entries(attrs))n.setAttribute(k,String(v));return n;}
 function message(text){$('network-message').textContent=text;}
 function controls(){
  $('network-prev').disabled=busy||posting||page<=1;$('network-next').disabled=busy||posting||!documentData||page*20>=documentData.total;
  $('network-editor').querySelectorAll('input,select,textarea,button').forEach(n=>{n.disabled=posting;});
  $('network-link-rows').querySelectorAll('button').forEach(n=>{n.disabled=busy||posting;});
 }
 async function request(action,data,signal){
  const post=action==='network-save',url='/admin.php?op=ita&act='+action+(post?'':'&'+new URLSearchParams(data));
  const response=await fetch(url,{method:post?'POST':'GET',cache:'no-store',signal,...(post?{headers:{'Content-Type':'application/json','X-CSRF-Token':document.querySelector('meta[name="ita-csrf"]')?.content||''},body:JSON.stringify(data)}:{})});
  let body;try{body=await response.json();}catch{throw new Error('Không đọc được phản hồi (HTTP '+response.status+').');}
  if(response.status===401){location.assign('/admin.php?op=ita&act=login');throw new Error('Phiên đã hết hạn.');}
  if(!response.ok||body.success!==true)throw new Error(typeof body.message==='string'?body.message.slice(0,400):'Yêu cầu bị từ chối.');
  return body.data;
 }
 function select(id){
  const item=visible.nodes.find(n=>n.id===id);if(!item)return;
  $('network-detail').hidden=false;$('network-detail-title').textContent=item.name;$('network-detail-values').replaceChildren();
  for(const [key,value]of [['Mã thiết bị',item.code],['Loại',item.kind],['Bộ phận',item.department],['Trạng thái tài sản',item.assetStatus],['Liên kết trong bộ lọc',visible.links.filter(l=>l.source===id||l.target===id).length]])$('network-detail-values').append(node('dt',key),node('dd',String(value)));
  document.querySelectorAll('[data-network-node]').forEach(n=>n.setAttribute('aria-pressed',String(n.dataset.networkNode===id)));
  dispatchEvent(new CustomEvent('ita:network-select',{detail:{id}}));
 }
 function edit(row){
  $('network-edit-id').value=row.id;$('network-edit-version').value=row.version;
  for(const [id,key]of [['source','source_asset_id'],['target','target_asset_id'],['type','link_type'],['source-port','source_port'],['target-port','target_port'],['capacity','capacity_label'],['note','note']])$('network-'+id).value=row[key];
  $('network-save-message').textContent='Đang sửa liên kết #'+row.id;$('network-source').focus();
 }
 function render(data){
  documentData=data;page=data.page;
  const status=s=>s==='broken'||s==='retired'?'offline':s==='maintenance'?'warning':'healthy';
  visible={nodes:data.nodes.map((n,i)=>({id:String(n.id),code:n.asset_code,name:n.name,kind:n.kind,department:n.department||'Chưa phân bổ',assetStatus:labels[n.status]||n.status,status:status(n.status),x:100+(i%5)*215,y:70+Math.floor(i/5)*90})),links:data.links.map(l=>({id:String(l.id),source:String(l.source_asset_id),target:String(l.target_asset_id),kind:l.link_type,capacity:l.capacity_label,status:'healthy'}))};
  $('network-svg').setAttribute('viewBox','0 0 1100 '+Math.max(500,Math.ceil(visible.nodes.length/5)*90+50));
  $('network-links').replaceChildren();$('network-nodes').replaceChildren();$('network-node-list').replaceChildren();$('network-detail').hidden=true;
  const byId=new Map(visible.nodes.map(n=>[n.id,n]));
  for(const link of visible.links){const a=byId.get(link.source),b=byId.get(link.target);if(!a||!b)throw new Error('Liên kết không khớp tập thiết bị.');$('network-links').append(svg('path',{d:`M ${a.x} ${a.y} L ${b.x} ${b.y}`,class:'network-edge'}));}
  for(const item of visible.nodes){
   const group=svg('g',{class:'network-node',transform:`translate(${item.x},${item.y})`,role:'button',tabindex:0,'aria-label':item.name,'aria-pressed':'false','data-network-node':item.id});
   const text=svg('text',{'text-anchor':'middle',y:5});text.textContent=item.code;group.append(svg('rect',{x:-66,y:-25,width:132,height:50,rx:8}),text);
   group.addEventListener('click',()=>select(item.id));group.addEventListener('keydown',event=>{if(['Enter',' '].includes(event.key)){event.preventDefault();select(item.id);}});$('network-nodes').append(group);
   const button=node('button',item.code+' · '+item.name);button.type='button';button.className='secondary-button';button.dataset.networkNode=item.id;button.addEventListener('click',()=>select(item.id));$('network-node-list').append(button);
  }
  $('network-count').textContent=visible.nodes.length+' thiết bị · '+data.total+' liên kết trong bộ lọc';
  $('network-link-rows').replaceChildren(...data.rows.map(row=>{
   const tr=node('tr','');for(const value of [row.source_name,row.target_name,row.link_type,row.capacity_label||'Chưa ghi',row.source_port+' ↔ '+row.target_port])tr.append(node('td',value));
   if(data.can_manage&&role()==='admin_it'){
    const td=tr.lastChild,editButton=node('button','Sửa'),remove=node('button','Ngừng liên kết');
    for(const button of [editButton,remove]){button.type='button';button.className='secondary-button';td.append(button);}
    editButton.addEventListener('click',()=>edit(row));remove.addEventListener('click',()=>{if(confirm('Ngừng liên kết #'+row.id+'? Hồ sơ được giữ bằng xóa mềm.'))save({action:'delete',id:row.id,version:row.version});});
   }return tr;
  }));
  if(!data.rows.length){const tr=node('tr',''),td=node('td','Không có liên kết trong bộ lọc.');td.colSpan=5;tr.append(td);$('network-link-rows').append(tr);}
  $('network-paging').hidden=false;$('network-page').textContent='Trang '+page+'/'+Math.max(1,Math.ceil(data.total/20))+' · '+data.total+' liên kết';
  $('network-editor').hidden=!(data.can_manage&&role()==='admin_it');
  for(const id of ['source','target']){
   const select=$('network-'+id),value=select.value;select.replaceChildren(new Option('Chọn thiết bị',''),...visible.nodes.map(n=>new Option(n.code+' · '+n.name,n.id)));select.value=value;
  }
  $('network-content').hidden=false;dispatchEvent(new CustomEvent('ita:network-render'));controls();
 }
 async function load(){
  if(!allowed()||location.hash!=='#network')return;
  controller?.abort();const pending=new AbortController();controller=pending;const current=++sequence;busy=true;controls();message('Đang tải liên kết…');$('network-retry').hidden=true;
  const timer=setTimeout(()=>pending.abort(),15000);
  try{const data=await request('network',{...applied,page},pending.signal);if(current!==sequence||location.hash!=='#network'||!allowed())return;
   if(data.mock!==false||!Array.isArray(data.nodes)||!Array.isArray(data.links)||!Array.isArray(data.rows)||data.nodes.length>200||data.links.length>400||data.rows.length>20)throw new Error('Phản hồi sơ đồ không hợp lệ.');
   render(data);message('Liên kết do Admin khai báo; không đo trạng thái kết nối hoặc lưu lượng mạng.');
  }catch(error){if(current!==sequence||location.hash!=='#network')return;$('network-content').hidden=true;visible={nodes:[],links:[]};dispatchEvent(new CustomEvent('ita:network-render'));message(error.name==='AbortError'?'Tải quá thời gian. Hãy thử lại.':error.message);$('network-retry').hidden=false;}
  finally{clearTimeout(timer);if(current===sequence){busy=false;controls();}}
 }
 async function save(payload){
  if(posting||role()!=='admin_it')return;posting=true;controls();$('network-save-message').textContent='Đang lưu…';
  try{await request('network-save',payload);$('network-editor').reset();$('network-edit-id').value='';$('network-edit-version').value='';$('network-save-message').textContent='Đã lưu liên kết.';await load();}
  catch(error){$('network-save-message').textContent=error.message+' Chưa gửi lại; dữ liệu nhập được giữ nguyên.';}
  finally{posting=false;controls();}
 }
 function activate(){
  $('network-nav').hidden=!allowed();if(location.hash!=='#network'){sequence++;controller?.abort();busy=false;return;}
  if(!allowed()){sequence++;controller?.abort();busy=false;$('network-content').hidden=true;visible={nodes:[],links:[]};dispatchEvent(new CustomEvent('ita:network-render'));message('Bạn không có quyền xem hạ tầng mạng.');return;}
  const departments=window.itaLiveContext.departments||[],selected=$('network-department').value;
  $('network-department').replaceChildren(new Option('Tất cả bộ phận',''),...departments.map(d=>new Option(d.label,d.value)));$('network-department').value=selected;
  load();
 }
 $('network-description').textContent='Liên kết khai báo giữa các tài sản phần cứng; không quét hoặc giám sát mạng.';$('network-badge').textContent='Dữ liệu hệ thống';
 $('network-nav').querySelector('.nav-label').textContent='Hạ tầng mạng';
 document.querySelector('label[for="network-search"]').textContent='Tìm mã hoặc tên thiết bị';document.querySelector('label[for="network-department"]').textContent='Bộ phận';
 $('network-show-3d').textContent='Xem 3D';$('network-svg-title').textContent='Kết nối thiết bị được khai báo';$('network-graph').setAttribute('aria-label','Sơ đồ liên kết, có thể cuộn');
 document.querySelector('#network-panel table caption').textContent='Liên kết trong bộ lọc · 20 dòng/trang';document.querySelectorAll('#network-panel th')[3].textContent='Dung lượng mô tả';document.querySelectorAll('#network-panel th')[4].textContent='Cổng / thao tác';
 $('network-flow-play').parentElement.hidden=true;
 $('network-node-list').previousElementSibling.textContent='Chọn thiết bị để xem trạng thái tài sản. Đường nối là khai báo, không chứng minh kết nối đang hoạt động.';
 $('network-retry').textContent='Tải lại sơ đồ';$('network-node-list').setAttribute('aria-label','Chọn thiết bị');
 $('network-filters').addEventListener('submit',event=>{event.preventDefault();applied={q:$('network-search').value,department_id:$('network-department').value};page=1;load();});
 $('network-reset').addEventListener('click',()=>{$('network-filters').reset();applied={q:'',department_id:''};page=1;load();});
 $('network-prev').addEventListener('click',()=>{page--;load();});$('network-next').addEventListener('click',()=>{page++;load();});$('network-retry').addEventListener('click',load);
 $('network-new').addEventListener('click',()=>{$('network-editor').reset();$('network-edit-id').value='';$('network-edit-version').value='';$('network-save-message').textContent='';});
 $('network-editor').addEventListener('submit',event=>{event.preventDefault();save({action:'save',id:$('network-edit-id').value,version:$('network-edit-version').value,source_asset_id:$('network-source').value,target_asset_id:$('network-target').value,link_type:$('network-type').value,source_port:$('network-source-port').value,target_port:$('network-target-port').value,capacity_label:$('network-capacity').value,note:$('network-note').value});});
 window.ItaNetwork={view:()=>visible,select};addEventListener('ita:view',activate);addEventListener('ita:data',activate);addEventListener('pagehide',()=>{sequence++;controller?.abort();});activate();
})();
