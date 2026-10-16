/* Read-only UI preview. No authentication, writes or production API calls. */
(() => {
 'use strict';
 const $ = (id) => document.getElementById(id);
 const statuses = {in_use:'Đang sử dụng',maintenance:'Bảo trì',broken:'Hỏng',retired:'Thanh lý'};
 const titles = {overview:['Tổng quan hạ tầng','Theo dõi tài sản, nắm bắt rủi ro và chủ động vận hành.'],assets:['Tài sản CNTT','Tra cứu thiết bị và thông tin phân bổ trong doanh nghiệp.'],alerts:['Trung tâm cảnh báo','Nhận diện rủi ro để ưu tiên công việc vận hành.']};
 const state = {data:null,page:1,pageSize:5,view:'overview'};
 let toastTimer;
 let returnFocus;
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
  state.view = Object.hasOwn(titles,candidate)?candidate:'overview';
  const [title,description] = titles[state.view];
  $('page-title').textContent = title;
  $('page-description').textContent = description;
  $('breadcrumb-current').textContent = state.view==='overview'?'Tổng quan':title;
  document.title = `InfraCore — ${title}`;
  $('overview-panel').hidden = state.view!=='overview';
  $('assets-panel').hidden = state.view==='alerts';
  $('alerts-panel').hidden = state.view!=='alerts';
  document.querySelectorAll('[data-view]').forEach((link) => {
   if (link.dataset.view===state.view) link.setAttribute('aria-current','page');
   else link.removeAttribute('aria-current');
  });
  setMobileMenu(false);
  if(menuWasOpen)$('main').focus();
 }
 function renderKpis() {
  const assets=state.data.assets;
  const count=(status)=>assets.filter((a)=>a.status===status).length;
  const cards=[['Tổng tài sản',assets.length,'Thiết bị trong bộ dữ liệu mẫu','accent','monitor'],['Đang sử dụng',count('in_use'),`${Math.round(count('in_use')/assets.length*100)}% tổng số tài sản`,'success','check'],['Đang bảo trì',count('maintenance'),'Cần theo dõi tiến độ xử lý','warning','wrench'],['Thiết bị hỏng',count('broken'),'Ưu tiên kiểm tra & khắc phục','danger','warning']];
  $('kpi-grid').replaceChildren(...cards.map(([label,value,detail,tone,glyph])=>{
   const card=el('article','kpi');
   const heading=el('div','kpi-label',label);
   heading.append(svgIcon(glyph,`kpi-glyph ${tone}-text`));
   card.append(heading,el('div','kpi-value',String(value).padStart(2,'0')),el('div','kpi-detail',detail));
   return card;
  }));
  $('nav-asset-count').textContent=assets.length;
  $('donut-total').textContent=assets.length;
  let offset=0;
  const segments=[];
  $('chart-legend').replaceChildren(...Object.entries(statuses).map(([status,label])=>{
   const value=count(status);
   const end=offset+value/assets.length*100;
   const token={in_use:'success',maintenance:'warning',broken:'danger',retired:'text-muted'}[status];
   segments.push(`var(--ita-${token}) ${offset}% ${end}%`);offset=end;
   const item=el('li',status);
   item.append(el('span','legend-dot'),el('span','',label),el('strong','',String(value)));
   return item;
  }));
  $('status-donut').style.background=`conic-gradient(${segments.join(',')})`;
  $('status-donut').setAttribute('aria-label',`Phân bố ${assets.length} tài sản mẫu: ${Object.entries(statuses).map(([s,l])=>`${count(s)} ${l}`).join(', ')}`);
 }
 function renderDepartments() {
  const counts=state.data.departments.map((department)=>({department,count:state.data.assets.filter((a)=>a.department===department).length}));
  const max=Math.max(1,...counts.map((item)=>item.count));
  $('department-bars').replaceChildren(...counts.map(({department,count})=>{
   const row=el('div','department-row');const track=el('div','bar-track');const fill=el('div','bar-fill');
   fill.style.width=`${count/max*100}%`;track.setAttribute('aria-hidden','true');track.append(fill);
   row.append(el('span','',department),track,el('strong','',String(count)));return row;
  }));
 }
 function renderAlerts() {
  const alerts=state.data.alerts;
  $('nav-alert-count').textContent=alerts.length;
  $('attention-summary').replaceChildren(...['critical','warning'].map((level)=>{
   const item=el('div','attention-item');
   item.append(el('strong',level==='critical'?'danger-text':'warning-text',String(alerts.filter((a)=>a.level===level).length).padStart(2,'0')),el('span','',level==='critical'?'Cảnh báo nghiêm trọng':'Cần theo dõi'));return item;
  }));
  $('alert-list').replaceChildren(...alerts.map((alert)=>{
   const row=el('article','alert-row');const text=el('div');text.append(el('h3','',alert.title),el('p','muted',alert.description));
   const button=el('button','secondary-button','Xem thiết bị');button.type='button';button.addEventListener('click',()=>showDetails(alert.asset_id,button));
   row.append(el('span',`alert-severity ${alert.level==='critical'?'danger-text':'warning-text'}`,alert.level==='critical'?'Nghiêm trọng':'Cảnh báo'),text,button);return row;
  }));
 }
 function filteredAssets() {
  const query=$('asset-search').value.trim().toLocaleLowerCase('vi');
  return state.data.assets.filter((a)=>(!query||`${a.code} ${a.name} ${a.serial}`.toLocaleLowerCase('vi').includes(query))&&(!$('department-filter').value||a.department===$('department-filter').value)&&(!$('status-filter').value||a.status===$('status-filter').value));
 }
 function showDetails(id,trigger) {
  const asset=state.data.assets.find((a)=>a.id===id);if(!asset)return;
  returnFocus=trigger;$('dialog-title').textContent=asset.name;
  const details=[['Mã tài sản',asset.code],['Số seri',asset.serial],['Loại thiết bị',asset.category],['Bộ phận',asset.department],['Trạng thái',statuses[asset.status]],['Bảo hành đến',asset.warranty],['Cấu hình',asset.specs],['Vị trí',asset.location]];
  $('asset-details').replaceChildren(...details.map(([label,value])=>{const group=el('div');group.append(el('dt','',label),el('dd','',value));return group;}));
  $('asset-dialog').showModal();
 }
 function renderTable() {
  if(!state.data)return;
  const assets=filteredAssets();const pages=Math.max(1,Math.ceil(assets.length/state.pageSize));state.page=Math.min(state.page,pages);
  const start=(state.page-1)*state.pageSize;const visible=assets.slice(start,start+state.pageSize);
  $('asset-rows').replaceChildren(...visible.map((asset)=>{
   const row=el('tr');const name=el('td');const identity=el('div','asset-name');const label=el('div');
   const glyph=el('span','asset-glyph');glyph.append(svgIcon(asset.category==='Switch / Router'?'network':'monitor'));
   label.append(el('strong','',asset.name),el('span','asset-code',asset.code));identity.append(glyph,label);name.append(identity);
   const status=el('td');status.append(el('span',`status-badge ${asset.status}`,statuses[asset.status]));
   const action=el('td');const button=el('button','detail-button');button.append(svgIcon('arrow'));button.type='button';button.setAttribute('aria-label',`Xem chi tiết ${asset.code}`);button.addEventListener('click',()=>showDetails(asset.id,button));action.append(button);
   row.append(name,el('td','muted',asset.category),el('td','',asset.department),status,el('td','muted',asset.warranty),action);return row;
  }));
  $('empty-state').hidden=assets.length!==0;
  $('results-status').textContent=`${assets.length} tài sản phù hợp · Lọc và phân trang trên dữ liệu MOCK tại trình duyệt`;
  $('page-summary').textContent=assets.length?`Hiển thị ${start+1}–${Math.min(start+state.pageSize,assets.length)} / ${assets.length} tài sản`:'0 tài sản';
  $('page-number').textContent=`${state.page} / ${pages}`;
  $('previous-page').disabled=state.page===1;$('next-page').disabled=state.page===pages;
 }
 function validateData(data) {
  if(!data||!Array.isArray(data.assets)||!data.assets.length||!Array.isArray(data.departments)||!Array.isArray(data.alerts))throw new Error('Invalid mock data');
  if(data.assets.some((a)=>!Object.hasOwn(statuses,a.status)||typeof a.id!=='number'||['code','name','serial','category','department','warranty','specs','location'].some((key)=>typeof a[key]!=='string')))throw new Error('Invalid mock asset');
  if(data.alerts.some((a)=>!['critical','warning'].includes(a.level)||typeof a.title!=='string'||typeof a.description!=='string'||!data.assets.some((asset)=>asset.id===a.asset_id)))throw new Error('Invalid mock alert');
 }
 async function loadData() {
  $('load-error').hidden=true;$('data-content').hidden=false;$('data-content').setAttribute('aria-busy','true');$('retry-button').disabled=true;
  try {
   const response=await fetch('/assets/ita/mock/overview.json',{cache:'no-store',signal:AbortSignal.timeout(10000)});
   if(!response.ok)throw new Error('Mock unavailable');const data=await response.json();validateData(data);state.data=data;
   $('department-filter').replaceChildren(new Option('Tất cả bộ phận',''),...data.departments.map((d)=>new Option(d,d)));
   renderKpis();renderDepartments();renderAlerts();renderTable();
  } catch {
   $('load-error').hidden=false;$('data-content').hidden=true;
  } finally {
   $('data-content').setAttribute('aria-busy','false');$('retry-button').disabled=false;
  }
 }
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
 $('notification-button').addEventListener('click',()=>{if(location.hash==='#alerts')notify('Bạn đang xem cảnh báo minh họa.');else location.hash='alerts';});
 $('close-dialog').addEventListener('click',()=>$('asset-dialog').close());
 $('asset-dialog').addEventListener('close',()=>{if(returnFocus?.isConnected)returnFocus.focus();});
 $('filter-form').addEventListener('submit',(event)=>event.preventDefault());
 ['asset-search','department-filter','status-filter'].forEach((id)=>$(id).addEventListener(id==='asset-search'?'input':'change',()=>{state.page=1;renderTable();}));
 // Reset's default action must finish before reading input/select values.
 $('filter-form').addEventListener('reset',()=>{setTimeout(()=>{state.page=1;renderTable();},0);});
 $('clear-filters').addEventListener('click',()=>{$('filter-form').reset();$('asset-search').focus();});
 $('previous-page').addEventListener('click',()=>{state.page--;renderTable();});
 $('next-page').addEventListener('click',()=>{state.page++;renderTable();});
 $('retry-button').addEventListener('click',loadData);
 addEventListener('hashchange',changeView);
 addEventListener('resize',()=>{if(innerWidth>=768)setMobileMenu(false);else $('sidebar').inert=!$('sidebar').classList.contains('is-open');});
 // Tablet navigation keeps compact icons accessible through explicit labels.
 document.querySelectorAll('[data-view]').forEach((link)=>{link.setAttribute('aria-label',titles[link.dataset.view][0]);link.title=titles[link.dataset.view][0];});
 changeView();loadData();
 if(new URLSearchParams(location.search).get('notice')==='legacy_hidden')notify('Chức năng cũ đang ẩn. Bạn đã được chuyển tới giao diện ITA.');
})();
