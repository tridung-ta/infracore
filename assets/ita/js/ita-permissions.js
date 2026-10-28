/** Effective capability presentation. All authorization remains on the server. */
(() => {
 'use strict';
 const $=id=>document.getElementById(id),enabled=()=>window.itaLiveContext?.user?.capabilities_enabled===true;
 const can=(key,legacy=false)=>enabled()?Array.isArray(window.itaLiveContext.user.permissions)&&window.itaLiveContext.user.permissions.includes(key):legacy;
 const labels={'asset.read':'Xem tài sản','asset.create':'Thêm tài sản','asset.update':'Sửa thông tin tài sản','asset.status':'Đổi trạng thái','asset.note':'Ghi chú tài sản','asset.retire':'Thanh lý tài sản','asset.delete':'Xóa mềm tài sản','category.manage':'Quản lý loại tài sản','import.read':'Xem nhập Excel','import.upload':'Tải Excel để kiểm tra','import.confirm':'Xác nhận nhập cả lô','asset.export':'Xuất tài sản','assignment.read':'Xem bàn giao','assignment.create':'Giao thiết bị','assignment.return':'Thu hồi thiết bị','assignment.acknowledge':'Xác nhận nhận của chính mình','assignment.pdf':'Tải biên bản PDF','license.read':'Xem giấy phép','license.manage':'Sửa danh mục giấy phép','license.allocate':'Cấp / thu hồi giấy phép','license.allocate_user':'Cấp giấy phép theo người','license.overage':'Xác nhận vượt số chỗ','license.reveal':'Xem key — xác thực lại','ticket.read':'Xem phiếu bảo trì','ticket.create':'Tạo phiếu','ticket.assign':'Gán người xử lý','ticket.process':'Xử lý phiếu','ticket.cost':'Điều chỉnh chi phí','ticket.ready':'Xác nhận thiết bị sẵn sàng','ticket.statistics':'Xem thống kê trong phạm vi','ticket.statistics_self':'Chỉ thống kê phiếu của mình','dashboard.read':'Xem tổng quan','alert.read':'Xem cảnh báo','lifecycle.read':'Xem vòng đời','forecast.read':'Xem ngân sách','forecast.configure':'Cấu hình ngân sách','forecast.plan':'Lưu kế hoạch ngân sách','forecast.export':'Xuất ngân sách','report.read':'Xem báo cáo','report.maintenance':'Chỉ xem báo cáo bảo trì','report.export':'Xuất báo cáo','network.read':'Xem hạ tầng mạng','network.manage':'Quản lý liên kết mạng','audit.read':'Xem nhật ký','access.read':'Đọc danh sách tài khoản','access.manage':'Quản lý tài khoản và cấp quyền','scope.store':'Phạm vi toàn hệ thống trong store'};
 const modules={asset:'Tài sản',category:'Loại tài sản',import:'Nhập Excel',assignment:'Bàn giao',license:'Giấy phép',ticket:'Bảo trì',dashboard:'Tổng quan',alert:'Cảnh báo',lifecycle:'Vòng đời',forecast:'Ngân sách',report:'Báo cáo',network:'Hạ tầng mạng',audit:'Nhật ký',access:'Quản trị quyền',scope:'Phạm vi dữ liệu'};
 const el=(tag,text)=>{const n=document.createElement(tag);if(text!==undefined)n.textContent=text;return n;};let version='';
 function open(user){
  $('permission-live-editor')?.remove();if(!enabled())return;
  const catalog=window.itaLiveContext.user.permission_catalog;
  if(user.capabilities_enabled!==true||!Array.isArray(user.permissions)||!Array.isArray(catalog))throw new Error('Chưa tải được quyền chức năng. Không dùng form role cũ để lưu thay.');
  version=user.version;const form=$('access-form'),fieldset=el('fieldset'),legend=el('legend','Chức năng được phép sử dụng'),help=el('p','Quyền ghi cần quyền xem tương ứng. Quản trị, danh mục chung và xem key cần chọn rõ phạm vi toàn hệ thống. Các ràng buộc vẫn được server kiểm tra.');
  fieldset.id='permission-live-editor';help.className='muted';const search=el('input');search.type='search';search.className='form-control';search.id='permission-function-search';search.placeholder='Tìm chức năng…';search.setAttribute('aria-label','Tìm chức năng');
  const grid=el('div');grid.className='permission-modules';const selected=new Set(user.permissions),grantable=new Set(window.itaLiveContext.user.permissions);
  for(const [prefix,title]of Object.entries(modules)){const keys=catalog.filter(k=>k.split('.')[0]===prefix);if(!keys.length)continue;const section=el('details');section.className='permission-module';section.open=keys.some(k=>selected.has(k));section.append(el('summary',title));for(const key of keys){const label=el('label'),box=el('input');box.type='checkbox';box.value=key;box.checked=selected.has(key);box.disabled=!grantable.has(key);label.append(box,el('span',Object.hasOwn(labels,key)?labels[key]:key));section.append(label);}grid.append(section);}
  search.addEventListener('input',()=>{const q=search.value.trim().toLocaleLowerCase('vi');grid.querySelectorAll('details').forEach(section=>{let found=false;section.querySelectorAll('label').forEach(label=>{label.hidden=q!==''&&!label.textContent.toLocaleLowerCase('vi').includes(q);found||=!label.hidden;});section.hidden=!found;if(q&&found)section.open=true;});});
  fieldset.append(legend,help,search,grid);$('scope-options').closest('fieldset').before(fieldset);
  $('access-role').hidden=true;document.querySelector('label[for=access-role]').hidden=true;
  const scopeHelp=$('scope-options').closest('fieldset').querySelector('p');scopeHelp.textContent='Nếu không chọn phạm vi toàn hệ thống, hãy chọn bộ phận được phụ trách. Không được vượt phạm vi ủy quyền của người cấp.';
  const outOfScope=user.permissions.some(key=>!grantable.has(key));$('access-save').disabled=outOfScope;if(outOfScope){const note=el('p','Tài khoản có quyền vượt phạm vi bạn được quản lý. Chỉ xem, không lưu thay đổi.');note.className='admin-message';fieldset.append(note);}
 }
 function payload(){
  if(!enabled())return {};
  const permissions=[...document.querySelectorAll('#permission-live-editor input[type=checkbox]:checked')].map(n=>n.value);
  return {permissions,version};
 }
 const maintenance=()=>enabled()?can('report.maintenance')&&!can('report.read')&&!can('dashboard.read'):window.itaLiveContext?.user?.role==='support_technician';
 function navigation(){
  if(!enabled())return;
  const routes={overview:can('dashboard.read')||maintenance(),assets:can('asset.read'),imports:can('import.read')||can('asset.export'),assignments:can('assignment.read'),alerts:can('alert.read'),licenses:can('license.read'),tickets:can('ticket.read'),lifecycle:can('lifecycle.read'),users:can('access.manage'),audit:can('audit.read'),forecast:can('forecast.read'),reports:can('report.read'),network:can('network.read')};
  document.querySelectorAll('[data-view]').forEach(link=>{if(Object.hasOwn(routes,link.dataset.view))link.hidden=!routes[link.dataset.view];});
  const view=location.hash.slice(1)||'overview';if(view==='overview'&&!routes.overview){const first=Object.keys(routes).find(key=>routes[key]);if(first){location.hash=first;return;}}
  const blocked=Object.hasOwn(routes,view)&&!routes[view];document.body.dataset.capabilityBlocked=String(blocked);let note=$('capability-view-denied');if(!note){note=el('p','Bạn chưa được cấp quyền dùng chức năng này. Chọn mục được phép trong menu.');note.id='capability-view-denied';note.className='admin-message';note.setAttribute('role','status');$('page-description').parentElement.after(note);}note.hidden=!blocked;
 }
 const previewGroups=Object.entries(modules).map(([prefix,title])=>[title,Object.entries(labels).filter(([key])=>key.split('.')[0]===prefix)]);
 window.ItaPolicy={enabled,can,maintenance,navigation,labels,previewGroups};window.ItaPermissionEditor={open,payload};addEventListener('ita:data',navigation);addEventListener('ita:view',navigation);
})();
/** Capability editor MOCK: no request, persistence, or real access changes. */
(() => {
 'use strict';if(document.body.dataset.mode==='live')return;
 const $=id=>document.getElementById(id),el=(tag,text)=>{const n=document.createElement(tag);if(text!==undefined)n.textContent=text;return n;};
 const groups=window.ItaPolicy.previewGroups;
 const presets={a:new Set(['asset.read','asset.create','asset.update']),b:new Set(['license.read','license.allocate','report.read']),c:new Set(['asset.read','asset.create','report.read'])};
 const names={a:'MOCK · Tài khoản A',b:'MOCK · Tài khoản B',c:'MOCK · Tài khoản C'};let account='c';
 function summary(){const selected=presets[account],list=$('permission-selection');list.replaceChildren(...groups.flatMap(([,items])=>items.filter(([key])=>selected.has(key)).map(([,label])=>el('li',label))));$('permission-count').textContent=selected.size+' quyền đang chọn cho '+names[account];$('permission-preview-message').textContent='MOCK · Chỉ thay đổi bản xem thử. Chưa lưu quyền thật hoặc tác động tài khoản nào.';}
 function render(){const root=$('permission-modules');root.replaceChildren();for(const [name,items]of groups){const details=el('details'),title=el('summary',name);details.className='permission-module';details.open=items.some(([key])=>presets[account].has(key));details.append(title);for(const [key,text]of items){const label=el('label'),input=el('input');input.type='checkbox';input.value=key;input.checked=presets[account].has(key);input.addEventListener('change',()=>{if(input.checked)presets[account].add(key);else presets[account].delete(key);summary();});label.append(input,el('span',text));details.append(label);}root.append(details);}summary();}
 $('permission-preview').hidden=false;$('permission-account').addEventListener('change',event=>{account=event.target.value;render();});$('permission-reset').addEventListener('click',()=>{presets[account]=new Set(account==='a'?['asset.read','asset.create','asset.update']:account==='b'?['license.read','license.allocate','report.read']:['asset.read','asset.create','report.read']);render();});
 document.querySelector('[data-view=users]').hidden=false;$('admin-message').textContent='MOCK · Minh họa quyền theo chức năng, chưa áp dụng vào phân quyền server.';
 $('user-rows').replaceChildren(...Object.entries(names).map(([key,name])=>{const tr=el('tr');for(const text of [name,'Tài khoản giả',key==='a'?'Chức năng 1, 2, 3':key==='b'?'Chức năng 4, 5, 6':'Chức năng 1, 2, 6','MOCK'])tr.append(el('td',text));const cell=el('td'),button=el('button','Chọn quyền');button.type='button';button.className='secondary-button';button.addEventListener('click',()=>{account=key;$('permission-account').value=key;render();$('permission-preview').scrollIntoView({block:'start'});$('permission-account').focus({preventScroll:true});});cell.append(button);tr.append(cell);return tr;}));
 $('users-panel').querySelectorAll('thead th')[2].textContent='Bộ quyền mẫu';$('user-page-summary').textContent='MOCK · 3 tài khoản minh họa';$('users-previous').disabled=$('users-next').disabled=true;render();
})();
