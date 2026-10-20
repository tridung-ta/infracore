/* Read-only UI preview. No authentication, writes or production API calls. */
(() => {
 'use strict';
 const $ = (id) => document.getElementById(id);
 const statuses = {in_use:'Đang sử dụng',maintenance:'Bảo trì',broken:'Hỏng',retired:'Thanh lý'};
 const liveMode=document.body.dataset.mode==='live';
 const titles = {overview:['Tổng quan hạ tầng','Theo dõi tài sản, nắm bắt rủi ro và chủ động vận hành.'],assets:['Tài sản CNTT','Tra cứu thiết bị và thông tin phân bổ trong doanh nghiệp.'],alerts:['Trung tâm cảnh báo','Nhận diện rủi ro để ưu tiên công việc vận hành.'],users:['Tài khoản & phân quyền','Quản lý quyền IT và phạm vi bộ phận cho tài khoản hiện có.'],audit:['Nhật ký thao tác','Tra cứu lịch sử truy cập và thay đổi trong hệ thống.']};
 const state = {data:null,page:1,pageSize:5,view:'overview'};
 titles.licenses=['Phần mềm & giấy phép','Theo dõi số chỗ, thời hạn và cấp phát trong phạm vi quản lý.'];
 titles.imports=['Import / Export tài sản',liveMode?'Kiểm tra file, nhập theo lô và xuất tài sản theo quyền.':'Chuẩn bị dữ liệu Excel và xem luồng nhập minh họa.'];
 titles.assignments=['Phân bổ & thu hồi',liveMode?'Giao thiết bị, xác nhận nhận và theo dõi thu hồi trong phạm vi của bạn.':'MOCK · Xem trước bàn giao và lịch sử với dữ liệu giả.'];
 let toastTimer;
 let returnFocus;
 let currentRequest;
 let loadSequence=0;
 let queryTimer;
 function el(tag, className, text) {
  const node = document.createElement(tag);
  if (className) node.className = className;
  if (text !== undefined) node.textContent = text;
  return node;
 }
 function svgIcon(name,className='') {
  const node=document.createElementNS('http://www.w3.org/2000/svg','svg');
  node.setAttribute('class',`ita-icon ${className}`);
  node.setAttribute('aria-hidden','true');
  const use=document.createElementNS('http://www.w3.org/2000/svg','use');
  use.setAttribute('href',`#ita-icon-${name}`);
  node.append(use);
  return node;
 }
 function notify(message) {
  $('toast').textContent = message;
  $('toast').hidden = false;
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => { $('toast').hidden = true; }, 4000);
 }
 function setMobileMenu(open) {
  $('sidebar').classList.toggle('is-open',open);
  $('sidebar-backdrop').hidden = !open;
  document.body.classList.toggle('menu-open',open);
  const expanded = innerWidth>=1200 ? !document.body.classList.contains('sidebar-collapsed') : open;
  $('menu-toggle').setAttribute('aria-expanded',String(expanded));
  $('menu-toggle').setAttribute('aria-label',innerWidth>=1200?(expanded?'Thu gọn menu điều hướng':'Mở rộng menu điều hướng'):(open?'Đóng menu điều hướng':'Mở menu điều hướng'));
  $('sidebar').inert = innerWidth<768 && !open;
  if (open) $('sidebar').querySelector('a').focus();
 }
 function changeView() {
  const menuWasOpen=$('sidebar').classList.contains('is-open');
  const candidate = location.hash.slice(1);
  state.view = Object.hasOwn(titles,candidate)&&(liveMode||!['users','audit'].includes(candidate))?candidate:'overview';
  const [title,description] = titles[state.view];
  $('page-title').textContent = title;
  $('page-description').textContent = description;
  $('breadcrumb-current').textContent = state.view==='overview'?'Tổng quan':title;
  document.title = `InfraCore — ${title}`;
  $('overview-panel').hidden = state.view!=='overview';
  $('assets-panel').hidden = !['overview','assets'].includes(state.view);
  $('alerts-panel').hidden = state.view!=='alerts';
  $('users-panel').hidden = state.view!=='users';
  $('audit-panel').hidden = state.view!=='audit';
  $('imports-panel').hidden = state.view!=='imports';
  $('assignments-panel').hidden = state.view!=='assignments';
  $('licenses-panel').hidden = state.view!=='licenses';
  document.querySelectorAll('[data-view]').forEach((link) => {
   if (link.dataset.view===state.view) link.setAttribute('aria-current','page');
   else link.removeAttribute('aria-current');
  });
  setMobileMenu(false);
  if(menuWasOpen)$('main').focus();
  dispatchEvent(new CustomEvent('ita:view',{detail:{view:state.view}}));
 }
 function renderKpis() {
  const assets=state.data.assets;
  const count=(status)=>liveMode?state.data.summary.by_status[status]:assets.filter((a)=>a.status===status).length;
  const total=liveMode?state.data.summary.total:assets.length;
  const cards=[['Tổng tài sản',total,liveMode?'Trong phạm vi được cấp quyền':'Thiết bị trong bộ dữ liệu mẫu','accent','monitor'],['Đang sử dụng',count('in_use'),`${total?Math.round(count('in_use')/total*100):0}% tổng số tài sản`,'success','check'],['Đang bảo trì',count('maintenance'),'Cần theo dõi tiến độ xử lý','warning','wrench'],['Thiết bị hỏng',count('broken'),'Ưu tiên kiểm tra & khắc phục','danger','warning']];
  $('kpi-grid').replaceChildren(...cards.map(([label,value,detail,tone,glyph])=>{
   const card=el('article','kpi');
   const heading=el('div','kpi-label',label);
   heading.append(svgIcon(glyph,`kpi-glyph ${tone}-text`));
   card.append(heading,el('div','kpi-value',String(value).padStart(2,'0')),el('div','kpi-detail',detail));
   return card;
  }));
  $('nav-asset-count').textContent=total;
  $('donut-total').textContent=total;
  let offset=0;
  const segments=[];
  $('chart-legend').replaceChildren(...Object.entries(statuses).map(([status,label])=>{
   const value=count(status);
   const end=total?offset+value/total*100:0;
   const token={in_use:'success',maintenance:'warning',broken:'danger',retired:'text-muted'}[status];
   segments.push(`var(--ita-${token}) ${offset}% ${end}%`);offset=end;
   const item=el('li',status);
   item.append(el('span','legend-dot'),el('span','',label),el('strong','',String(value)));
   return item;
  }));
  $('status-donut').style.background=total?`conic-gradient(${segments.join(',')})`:'var(--ita-surface-2)';
  $('status-donut').setAttribute('aria-label',`Phân bố ${total} tài sản: ${Object.entries(statuses).map(([s,l])=>`${count(s)} ${l}`).join(', ')}`);
 }
 function renderDepartments() {
  const counts=liveMode?state.data.summary.by_department.map(item=>({...item,count:Number(item.count)})):state.data.departments.map((department)=>({department,count:state.data.assets.filter((a)=>a.department===department).length}));
  const max=Math.max(1,...counts.map((item)=>item.count));
  $('department-bars').replaceChildren(...counts.map(({department,count})=>{
   const row=el('div','department-row');const track=el('div','bar-track');const fill=el('div','bar-fill');
   fill.style.width=`${count/max*100}%`;track.setAttribute('aria-hidden','true');track.append(fill);
   row.append(el('span','',department),track,el('strong','',String(count)));return row;
  }));
  if(!counts.length)$('department-bars').append(el('p','muted','Chưa có tài sản trong phạm vi của bạn.'));
 }
 function renderAlerts() {
  const alerts=state.data.alerts;
  $('nav-alert-count').textContent=liveMode?state.data.summary.alerts_total:alerts.length;
  $('notification-button').querySelector('.notification-dot').hidden=liveMode?state.data.summary.alerts_total===0:alerts.length===0;
  $('attention-summary').replaceChildren(...['critical','warning'].map((level)=>{
   const item=el('div','attention-item');
   const count=liveMode?(state.data.summary.by_status[level==='critical'?'broken':'maintenance']||0)+(state.data.summary.license_counts?.[level]||0)+(level==='warning'?(state.data.summary.maintenance_overdue_total||0):0):alerts.filter((a)=>a.level===level).length;
   item.append(el('strong',level==='critical'?'danger-text':'warning-text',String(count).padStart(2,'0')),el('span','',level==='critical'?'Cảnh báo nghiêm trọng':'Cần theo dõi'));return item;
  }));
  $('alert-list').replaceChildren(...alerts.map((alert)=>{
   const row=el('article','alert-row');const text=el('div');text.append(el('h3','',alert.title),el('p','muted',alert.description));
   const button=el('button','secondary-button',alert.license_id?'Xem giấy phép':'Xem thiết bị');button.type='button';button.addEventListener('click',()=>{if(alert.license_id)location.hash='licenses';else showDetails(alert.asset_id,button);});
   row.append(el('span',`alert-severity ${alert.level==='critical'?'danger-text':'warning-text'}`,alert.level==='critical'?'Nghiêm trọng':'Cảnh báo'),text,button);return row;
  }));
  if(!alerts.length)$('alert-list').append(el('p','muted','Chưa có cảnh báo trong phạm vi của bạn.'));
 }
 function filteredAssets() {
  const query=$('asset-search').value.trim().toLocaleLowerCase('vi');
  return state.data.assets.filter((a)=>(!query||`${a.code} ${a.name} ${a.serial}`.toLocaleLowerCase('vi').includes(query))&&(!$('department-filter').value||a.department===$('department-filter').value)&&(!$('status-filter').value||a.status===$('status-filter').value));
 }
 async function showDetails(id,trigger) {
  let asset=state.data.assets.find((a)=>a.id===id);
  if(liveMode){
   trigger.disabled=true;
   try{const response=await fetch(`/admin.php?op=ita&act=asset&id=${id}`);const result=await response.json();if(response.status===401){location.assign('/admin.php?op=ita&act=login');return;}if(!result.success)throw new Error(result.message);asset=result.data;}
   catch(error){notify(error.message||'Chưa tải được chi tiết thiết bị.');return;}
   finally{trigger.disabled=false;}
  }
  if(!asset)return;
  returnFocus=trigger;$('dialog-title').textContent=asset.name;
  const details=[['Mã tài sản',asset.code],['Số seri',asset.serial],['Loại thiết bị',asset.category],['Bộ phận',asset.department],['Trạng thái',statuses[asset.status]],['Bảo hành đến',asset.warranty],['Cấu hình',asset.specs],['Vị trí',asset.location]];
  $('asset-details').replaceChildren(...details.map(([label,value])=>{const group=el('div');group.append(el('dt','',label),el('dd','',value));return group;}));
  $('asset-dialog').showModal();
  if(liveMode)dispatchEvent(new CustomEvent('ita:asset',{detail:asset}));
 }
 function renderTable() {
  if(!state.data)return;
  const assets=liveMode?state.data.assets:filteredAssets();
  const total=liveMode?state.data.pagination.total:assets.length;
  if(liveMode)state.page=state.data.pagination.page;
  const pages=Math.max(1,Math.ceil(total/state.pageSize));state.page=Math.min(state.page,pages);
  const start=(state.page-1)*state.pageSize;const visible=liveMode?assets:assets.slice(start,start+state.pageSize);
  $('asset-rows').replaceChildren(...visible.map((asset)=>{
   const row=el('tr');const name=el('td');const identity=el('div','asset-name');const label=el('div');
   const glyph=el('span','asset-glyph');glyph.append(svgIcon(asset.category==='Switch / Router'?'network':'monitor'));
   label.append(el('strong','',asset.name),el('span','asset-code',asset.code));identity.append(glyph,label);name.append(identity);
   if(asset.maintenance_overdue)label.append(el('span','warning-text','Quá hạn bảo trì'));
   const status=el('td');status.append(el('span',`status-badge ${asset.status}`,statuses[asset.status]));
   const action=el('td');const button=el('button','detail-button');button.append(svgIcon('arrow'));button.type='button';button.setAttribute('aria-label',`Xem chi tiết ${asset.code}`);button.addEventListener('click',()=>showDetails(asset.id,button));action.append(button);
   row.append(name,el('td','muted',asset.category),el('td','',asset.department),status,el('td','muted',asset.warranty),action);return row;
  }));
  $('empty-state').hidden=assets.length!==0;
  $('results-status').textContent=liveMode?`${total} tài sản phù hợp trong phạm vi của bạn`:`${assets.length} tài sản phù hợp · Lọc và phân trang trên dữ liệu MOCK tại trình duyệt`;
  $('page-summary').textContent=total?`Hiển thị ${start+1}–${Math.min(start+state.pageSize,total)} / ${total} tài sản`:'0 tài sản';
  $('page-number').textContent=`${state.page} / ${pages}`;
  $('previous-page').disabled=state.page===1;$('next-page').disabled=state.page===pages;
 }
 function validateData(data) {
  if(!data||!Array.isArray(data.assets)||(!liveMode&&!data.assets.length)||!Array.isArray(data.departments)||!Array.isArray(data.alerts))throw new Error('Invalid data');
  if(data.assets.some((a)=>!Object.hasOwn(statuses,a.status)||typeof a.id!=='number'||['code','name','serial','category','department','warranty','specs','location'].some((key)=>typeof a[key]!=='string')))throw new Error('Invalid mock asset');
  if(data.alerts.some((a)=>!['critical','warning'].includes(a.level)||typeof a.title!=='string'||typeof a.description!=='string'||(!liveMode&&!data.assets.some((asset)=>asset.id===a.asset_id))))throw new Error('Invalid alert');
 }
 async function loadData() {
  const sequence=++loadSequence;currentRequest?.abort();const controller=new AbortController();currentRequest=controller;
  const timeout=setTimeout(()=>controller.abort(),10000);
  $('load-error').hidden=true;$('data-content').hidden=false;$('data-content').setAttribute('aria-busy','true');$('retry-button').disabled=true;
  $('next-page').disabled=true;$('previous-page').disabled=true;
  try {
   const query=new URLSearchParams({op:'ita',act:'data',page:state.page,q:$('asset-search').value,department_id:$('department-filter').value,status:$('status-filter').value});
   if(liveMode)for(const id of ['category_id','purchase_year','warranty_from','warranty_to']){
    const input=document.getElementById('filter-'+id);if(input?.value)query.set(id,input.value);
   }
   const response=await fetch(liveMode?`/admin.php?${query}`:'/assets/ita/mock/overview.json',{cache:'no-store',signal:controller.signal});
   if(response.status===401&&liveMode){location.assign('/admin.php?op=ita&act=login');return;}
   const result=await response.json();if(!response.ok)throw new Error(result.message||'Chưa tải được dữ liệu.');
   const data=liveMode?result.data:result;if(liveMode)data.alerts=data.summary.alerts;
   if(sequence!==loadSequence)return;validateData(data);state.data=data;
   const department=$('department-filter').value;
   $('department-filter').replaceChildren(new Option('Tất cả bộ phận',''),...data.departments.map((d)=>liveMode?new Option(d.label,d.value):new Option(d,d)));
   $('department-filter').value=department;
   renderKpis();renderDepartments();renderAlerts();renderTable();
   if(liveMode){window.itaLiveContext={user:data.user,departments:data.departments};document.querySelectorAll('.admin-only').forEach(link=>{link.hidden=data.user.role!=='admin_it';});dispatchEvent(new CustomEvent('ita:data'));}
  } catch(error) {
   if(sequence!==loadSequence)return;
   $('load-error').hidden=false;$('data-content').hidden=true;
   if(liveMode)$('load-error').querySelector('p').textContent=error.message||'Hãy kiểm tra kết nối rồi thử lại.';
  } finally {
   clearTimeout(timeout);if(sequence===loadSequence){$('data-content').setAttribute('aria-busy','false');$('retry-button').disabled=false;}
  }
 }
 function updateFilters(){state.page=1;if(liveMode){clearTimeout(queryTimer);queryTimer=setTimeout(loadData,200);}else renderTable();}
 $('menu-toggle').addEventListener('click',()=>{
  if(innerWidth>=1200){const collapsed=document.body.classList.toggle('sidebar-collapsed');$('menu-toggle').setAttribute('aria-expanded',String(!collapsed));$('menu-toggle').setAttribute('aria-label',collapsed?'Mở rộng menu điều hướng':'Thu gọn menu điều hướng');}
  else setMobileMenu(!$('sidebar').classList.contains('is-open'));
 });
 $('sidebar-backdrop').addEventListener('click',()=>{setMobileMenu(false);$('menu-toggle').focus();});
 document.addEventListener('keydown',(event)=>{
  if(event.key==='Escape'&&$('sidebar').classList.contains('is-open')){setMobileMenu(false);$('menu-toggle').focus();}
  if(event.key==='Tab'&&$('sidebar').classList.contains('is-open')){
   const items=[...$('sidebar').querySelectorAll('a')];const first=items[0];const last=items.at(-1);
   if(event.shiftKey&&document.activeElement===first){event.preventDefault();last.focus();}
   else if(!event.shiftKey&&document.activeElement===last){event.preventDefault();first.focus();}
  }
 });
 $('notification-button').addEventListener('click',()=>{if(location.hash==='#alerts')notify(liveMode?'Bạn đang xem trung tâm cảnh báo.':'Bạn đang xem cảnh báo minh họa.');else location.hash='alerts';});
 $('close-dialog').addEventListener('click',()=>$('asset-dialog').close());
 $('asset-dialog').addEventListener('close',()=>{if(returnFocus?.isConnected)returnFocus.focus();});
 $('filter-form').addEventListener('submit',(event)=>event.preventDefault());
 ['asset-search','department-filter','status-filter'].forEach((id)=>$(id).addEventListener(id==='asset-search'?'input':'change',updateFilters));
 // Reset's default action must finish before reading input/select values.
 $('filter-form').addEventListener('reset',()=>{setTimeout(updateFilters,0);});
 $('clear-filters').addEventListener('click',()=>{$('filter-form').reset();$('asset-search').focus();});
 $('previous-page').addEventListener('click',()=>{state.page--;if(liveMode)loadData();else renderTable();});
 $('next-page').addEventListener('click',()=>{state.page++;if(liveMode)loadData();else renderTable();});
 $('retry-button').addEventListener('click',loadData);
 addEventListener('ita:refresh',()=>{state.page=1;loadData();});
 addEventListener('hashchange',changeView);
 addEventListener('resize',()=>{if(innerWidth>=768)setMobileMenu(false);else $('sidebar').inert=!$('sidebar').classList.contains('is-open');});
 // Tablet navigation keeps compact icons accessible through explicit labels.
 document.querySelectorAll('[data-view]').forEach((link)=>{link.setAttribute('aria-label',titles[link.dataset.view][0]);link.title=titles[link.dataset.view][0];});
 if(liveMode){
  document.querySelector('.mock-notice').textContent='Đã xác thực · Dữ liệu được giới hạn theo quyền và bộ phận của bạn.';
  document.querySelector('.environment').textContent='ĐÃ XÁC THỰC';
  document.querySelector('.heading-meta').hidden=true;
  document.querySelector('#connection-label').textContent='Dữ liệu hệ thống';
  document.querySelectorAll('.subtle-tag').forEach(tag=>{tag.textContent=tag.textContent.includes('Chỉ đọc')?'Chỉ đọc':'Dữ liệu hệ thống';});
  document.querySelector('.panel-footnote').textContent='Thống kê trong phạm vi tài sản được cấp quyền.';
  $('load-error').querySelector('h2').textContent='Chưa tải được dữ liệu hệ thống';
  document.querySelector('#alerts-panel .panel-heading p').textContent='Thiết bị trong phạm vi và giấy phép dùng chung theo quyền. Danh sách hiển thị có giới hạn; số đếm là tổng cảnh báo.';
  document.querySelector('#assets-panel .panel-heading p').textContent='Tra cứu thiết bị trong phạm vi được cấp quyền';
  document.querySelector('#asset-dialog .dialog-meta').textContent='Hồ sơ tài sản';
  document.querySelector('#asset-dialog>p').textContent='Thông tin thiết bị chỉ đọc. Chức năng cập nhật sẽ được triển khai ở bước tiếp theo.';
 }
 changeView();loadData();
 if(new URLSearchParams(location.search).get('notice')==='legacy_hidden')notify('Chức năng cũ đang ẩn. Bạn đã được chuyển tới giao diện ITA.');
})();
