(() => {
 'use strict';
 if(document.body.dataset.mode!=='live')return;
 const $=id=>document.getElementById(id),{api}=window.ItaAdmin;
 const labels={in_use:'Đang sử dụng',maintenance:'Bảo trì',broken:'Hỏng',retired:'Thanh lý'};
 const transitions={in_use:['maintenance','broken','retired'],maintenance:['in_use','broken','retired'],broken:['maintenance','retired'],retired:[]};
 const fields=[['asset_code','Mã tài sản','text',64],['name','Tên tài sản','text',200],['category_id','Loại tài sản','select'],['department_id','Bộ phận','select'],
  ['brand','Thương hiệu','text',100],['model','Model','text',150],['serial_no','Số seri','text',150],['os','Hệ điều hành','text',120],
  ['purchase_date','Ngày mua','date'],['purchase_price','Giá mua (VND)','text',19],['warranty_end','Bảo hành đến','date'],['vendor','Nhà cung cấp','text',200],
  ['location','Vị trí','text',255],['specs','Cấu hình / thông số','textarea',10000]];
 let categories=[],editing=null,detail=null,mode='',busy=false,loadingEditor=false,previousFocus=null,historyGeneration=0;
 let categoryPage=1,categoryTotal=0,categoryQuery='',categoryLoading=false,categorySaving=false,categorySequence=0;
 function node(tag,text,cls){const n=document.createElement(tag);if(text!==undefined)n.textContent=text;if(cls)n.className=cls;return n;}
 function message(id,text,error=false){const n=$(id);n.textContent=text;n.hidden=false;n.setAttribute('role',error?'alert':'status');n.classList.toggle('danger-text',error);window.ItaUi.autoDismiss(n,error);n.focus({preventScroll:true});n.scrollIntoView({block:'nearest'});}
 function button(text,callback){const n=node('button',text,'secondary-button');n.type='button';n.addEventListener('click',callback);return n;}
 const tools=node('div',undefined,'asset-tools');const add=button('Thêm tài sản',()=>openEditor('create'));
 const manage=button('Quản lý loại tài sản',openCategories);tools.append(add,manage);$('assets-panel').querySelector('.panel-heading').after(tools);
 const catalogNotice=node('p',undefined,'muted');catalogNotice.setAttribute('role','status');catalogNotice.hidden=true;tools.append(catalogNotice);
 const extra=node('div',undefined,'asset-extra-filters');
 for(const [key,label,type] of [['category_id','Loại tài sản','select'],['purchase_year','Năm mua','number'],['warranty_from','Bảo hành từ ngày','date'],['warranty_to','Bảo hành đến ngày','date']]){
  const group=node('div'),caption=node('label',label),input=node(type==='select'?'select':'input');input.id='filter-'+key;input.className=type==='select'?'form-select':'form-control';caption.htmlFor=input.id;
  if(type==='select')input.append(new Option('Tất cả loại',''));else input.type=type;
  if(type==='number'){input.min='1000';input.max='9998';}
  input.setAttribute('form','filter-form');input.addEventListener('change',()=>dispatchEvent(new Event('ita:refresh')));group.append(caption,input);extra.append(group);
 }
 $('filter-form').after(extra);
 const result=node('p',undefined,'admin-message');result.id='asset-result';result.tabIndex=-1;result.hidden=true;tools.after(result);
 async function loadCategories(){categories=await api('categories');return categories;}
 function refresh(){dispatchEvent(new Event('ita:refresh'));}
 function updateContext(){
  if(!window.itaLiveContext)return;
  add.hidden=!window.ItaPolicy.can('asset.create',window.itaLiveContext.user.role!=='support_technician');manage.hidden=!window.ItaPolicy.can('category.manage',window.itaLiveContext.user.role==='admin_it');
  loadCategories().then(rows=>{
   catalogNotice.hidden=true;
   const select=$('filter-category_id'),value=select.value;
   select.replaceChildren(new Option('Tất cả loại',''),...rows.map(c=>new Option(c.name+(c.status==='inactive'?' (ngừng sử dụng)':''),c.id)));select.value=value;
  }).catch(error=>{catalogNotice.textContent=error.message;catalogNotice.hidden=false;});
 }
 function field(key,label,{type='text',value='',options=[]}={}){
  const group=node('div'),caption=node('label',label),input=node(type==='select'?'select':type==='textarea'?'textarea':'input');
  input.name=key;input.id='edit-'+key;caption.htmlFor=input.id;input.className=type==='select'?'form-select':'form-control';
  if(type==='select')input.append(...options.map(([id,text])=>new Option(text,id)));else if(type!=='textarea')input.type=type;
  input.value=value??'';group.append(caption,input);if(type==='textarea')group.className='wide-field';$('asset-edit-fields').append(group);return input;
 }
 async function openEditor(operation){
  try{
   if(busy||loadingEditor)return;loadingEditor=true;mode=operation;editing=operation==='create'?null:detail;
   if(['create','update'].includes(mode))await loadCategories();
   previousFocus=document.activeElement;$('asset-edit-fields').replaceChildren();$('asset-edit-message').hidden=true;
   const titles={create:'Thêm tài sản',update:'Sửa thông tin tài sản',status:'Đổi trạng thái',note:'Ghi chú tài sản',delete:'Xóa mềm tài sản'};
   $('asset-editor-title').textContent=titles[mode];$('asset-editor-save').textContent=mode==='delete'?'Xóa mềm':'Lưu thay đổi';
   if(['create','update'].includes(mode)){
    for(const [key,label,type,max] of fields){
     let options=[];
     if(key==='category_id')options=[['','Chọn loại tài sản'],...categories.filter(c=>c.status==='active'||String(c.id)===String(editing?.fields.category_id)).map(c=>[String(c.id),c.name+(c.status==='inactive'?' (ngừng sử dụng)':'')])];
     if(key==='department_id')options=[['','Chưa phân bổ'],...window.itaLiveContext.departments.map(d=>[d.value,d.label])];
     const input=field(key,label,{type,value:editing?.fields[key]??'',options});if(max)input.maxLength=max;
     input.required=['asset_code','name','category_id'].includes(key)||(key==='department_id'&&!window.ItaPolicy.can('scope.store',window.itaLiveContext.user.role==='admin_it'));
     if(key==='purchase_price'){input.inputMode='decimal';input.pattern='[0-9]+(\\.[0-9]{1,2})?';}
    }
   }else{
    $('asset-edit-fields').append(node('p',`${editing.code} · ${editing.name}`,'wide-field'));
    if(mode==='status'){
     const status=field('status','Trạng thái mới',{type:'select',options:[['','Chọn trạng thái'],...(transitions[editing.status]||[]).filter(s=>s!=='retired'||window.ItaPolicy.can('asset.retire',window.itaLiveContext.user.role==='admin_it')).map(s=>[s,labels[s]])]});
     status.required=true;
    }
    const reason=field('reason',mode==='note'?'Ghi chú':'Lý do',{type:'textarea'});reason.required=true;reason.maxLength=4000;
   }
   if($('asset-dialog').open)$('asset-dialog').close();$('asset-editor').showModal();$('asset-edit-fields').querySelector('input,select,textarea')?.focus();
  }catch(error){message('asset-result',error.message,true);}finally{loadingEditor=false;}
 }
 $('asset-edit-form').addEventListener('submit',async event=>{
  event.preventDefault();if(busy)return;busy=true;const save=$('asset-editor-save');save.disabled=true;
  $('asset-editor-close').disabled=true;$('asset-editor-cancel').disabled=true;$('asset-edit-form').setAttribute('aria-busy','true');
  try{
   if(mode==='delete'&&!await window.ItaUi.confirm({title:'Xóa mềm tài sản?',message:'Tài sản sẽ được ẩn khỏi danh sách. Hồ sơ và lịch sử vẫn được giữ.',confirmLabel:'Xóa mềm'}))return;
   const body=Object.fromEntries(new FormData($('asset-edit-form')));body.operation=mode;
   if(editing){body.id=editing.id;body.version=editing.version;}
   save.textContent='Đang lưu…';await api('asset-save',{},body);$('asset-editor').close();refresh();message('asset-result','Đã lưu thay đổi tài sản. Mở lại hồ sơ để kiểm tra.');
  }catch(error){message('asset-edit-message',error.message,true);}finally{
   busy=false;save.disabled=false;save.textContent=mode==='delete'?'Xóa mềm':'Lưu thay đổi';$('asset-editor-close').disabled=false;$('asset-editor-cancel').disabled=false;$('asset-edit-form').removeAttribute('aria-busy');
  }
 });
 for(const id of ['asset-editor-close','asset-editor-cancel'])$(id).addEventListener('click',()=>{if(!busy)$('asset-editor').close();});
 $('asset-editor').addEventListener('cancel',event=>{if(busy)event.preventDefault();});
 $('asset-editor').addEventListener('close',()=>{if(previousFocus?.isConnected)previousFocus.focus();});
 function createHistory(initial,dialog,generation){
  const history=node('section',undefined,'asset-history');history.append(node('h3','Lịch sử thao tác'));
  const entries=node('div'),feedback=node('p',undefined,'muted'),paging=node('nav',undefined,'asset-tools');
  feedback.setAttribute('role','status');paging.setAttribute('aria-label','Phân trang lịch sử tài sản');
  const assetId=initial.id;let historyPage=1,historyTotal=0,historyPerPage=20,historyBusy=false;
  const previous=button('Trước',()=>loadHistory(historyPage-1)),next=button('Sau',()=>loadHistory(historyPage+1)),summary=node('span');
  previous.id='asset-history-prev';next.id='asset-history-next';summary.id='asset-history-page';summary.setAttribute('aria-live','polite');paging.append(previous,summary,next);
  function historyControls(){previous.disabled=historyBusy||historyPage<=1;next.disabled=historyBusy||historyPage*historyPerPage>=historyTotal;}
  function renderHistory(data){
   const pagination=data.history_pagination||{page:1,per_page:20,total:data.history?.length||0};
   historyPage=pagination.page;historyTotal=pagination.total;historyPerPage=pagination.per_page;
   entries.replaceChildren();if(!data.history?.length)entries.append(node('p','Chưa có lịch sử thao tác.','muted'));
   for(const item of data.history||[]){
    const entry=node('details'),date=new Date(item.created_at.replace(' ','T')+'Z');entry.append(node('summary',`${date.toLocaleString('vi-VN',{timeZone:'Asia/Ho_Chi_Minh'})} · #${item.user_id} · ${item.action}`));
    const parse=value=>{try{return value?JSON.parse(value):null;}catch(error){return 'Không đọc được dữ liệu lịch sử.';}};
    entry.append(node('pre',JSON.stringify({before:parse(item.before_json),after:parse(item.after_json)},null,2)));entries.append(entry);
   }
   summary.textContent=`Trang ${historyPage}/${Math.max(1,Math.ceil(historyTotal/historyPerPage))} · ${historyTotal} thao tác`;
   historyControls();
  }
  async function loadHistory(requested){
   if(historyBusy)return;historyBusy=true;historyControls();history.setAttribute('aria-busy','true');feedback.textContent='Đang tải lịch sử…';feedback.setAttribute('role','status');
   try{
    const data=await api('asset',{id:assetId,page:requested});
    if(generation!==historyGeneration||!dialog.open||!history.isConnected)return;
    renderHistory(data);feedback.textContent=`Đã tải lịch sử trang ${historyPage}.`;
   }catch(error){
    if(generation!==historyGeneration||!dialog.open||!history.isConnected)return;
    entries.replaceChildren();feedback.textContent=error.message;feedback.setAttribute('role','alert');
   }finally{
    if(generation===historyGeneration&&history.isConnected){historyBusy=false;historyControls();history.removeAttribute('aria-busy');}
   }
  }
  renderHistory(initial);history.append(feedback,entries,paging);
  return history;
 }
 $('asset-dialog').addEventListener('close',()=>{historyGeneration++;});
 addEventListener('ita:asset',event=>{
  const generation=++historyGeneration;
  detail=event.detail;const dialog=$('asset-dialog');dialog.querySelectorAll('.asset-tools,.asset-history').forEach(n=>n.remove());
  const controls=node('div',undefined,'asset-tools'),role=window.itaLiveContext.user.role;
  if(window.ItaPolicy.can('asset.update',role!=='support_technician'))controls.append(button('Sửa thông tin',()=>openEditor('update')));
  if(window.ItaPolicy.can('asset.status',true)&&(transitions[detail.status]||[]).some(s=>s!=='retired'||window.ItaPolicy.can('asset.retire',role==='admin_it')))controls.append(button('Đổi trạng thái',()=>openEditor('status')));
  if(window.ItaPolicy.can('asset.note',true))controls.append(button('Ghi chú',()=>openEditor('note')));if(window.ItaPolicy.can('asset.delete',role==='admin_it'))controls.append(button('Xóa mềm',()=>openEditor('delete')));dialog.append(controls);
  const history=createHistory(detail,dialog,generation);
  history.id='asset-history-panel';history.hidden=true;history.setAttribute('role','tabpanel');history.setAttribute('aria-labelledby','asset-history-tab');
  const tabs=node('div',undefined,'asset-tools asset-tabs');tabs.setAttribute('role','tablist');tabs.setAttribute('aria-label','Hồ sơ tài sản');
  const info=$('asset-details');info.setAttribute('role','tabpanel');info.setAttribute('aria-labelledby','asset-info-tab');info.hidden=false;
  function selectTab(index){
   info.hidden=index===1;history.hidden=index===0;
   [...tabs.children].forEach((tab,i)=>{tab.setAttribute('aria-selected',String(i===index));tab.tabIndex=i===index?0:-1;});
  }
  for(const [i,label] of ['Thông tin','Lịch sử'].entries()){
   const tab=button(label,()=>selectTab(i));tab.id=i===0?'asset-info-tab':'asset-history-tab';tab.setAttribute('role','tab');tab.setAttribute('aria-controls',i===0?'asset-details':history.id);
   tab.addEventListener('keydown',event=>{if(['ArrowLeft','ArrowRight','Home','End'].includes(event.key)){event.preventDefault();const index=event.key==='Home'?0:event.key==='End'?1:1-i;selectTab(index);tabs.children[index].focus();}});tabs.append(tab);
  }
  selectTab(0);info.before(tabs);dialog.append(history);dialog.querySelector(':scope>p').textContent='Thông tin và lịch sử trong phạm vi được cấp quyền.';
  const more=[['Thương hiệu',detail.fields.brand],['Model',detail.fields.model],['Hệ điều hành',detail.fields.os],['Ngày mua',detail.fields.purchase_date],['Giá mua (VND)',detail.fields.purchase_price],['Nhà cung cấp',detail.fields.vendor],['Ghi chú / lý do',detail.fields.status_reason]];
  for(const [label,value] of more){const group=node('div');group.append(node('dt',label),node('dd',value===null||value===undefined||value===''?'Chưa ghi nhận':String(value)));$('asset-details').append(group);}
 });
 async function openCategories(){
  if(categorySaving||$('category-editor').open)return;
  $('category-form').reset();$('category-message').textContent='';$('category-search').value='';categoryQuery='';categoryPage=1;categoryTotal=0;
  $('category-list').replaceChildren();$('category-page').textContent='';$('category-editor').showModal();
  try{await renderCategories(1,'');}catch(error){message('category-message',error.message,true);}
 }
 function categoryControls(){
  const locked=categoryLoading||categorySaving;
  $('category-editor').querySelectorAll('input,select,button').forEach(n=>n.disabled=locked);
  $('category-close').disabled=categorySaving;
  $('category-prev').disabled=locked||categoryPage<=1;$('category-next').disabled=locked||categoryPage*20>=categoryTotal;
 }
 async function renderCategories(nextPage=categoryPage,query=categoryQuery){
  const sequence=++categorySequence;categoryLoading=true;categoryControls();$('category-list').setAttribute('aria-busy','true');$('category-list-status').textContent='Đang tải danh mục…';
  try{
   const data=await api('categories',{paged:'1',page:nextPage,q:query});if(sequence!==categorySequence||!$('category-editor').open)return;
   categoryPage=data.page;categoryTotal=data.total;categoryQuery=query;
   $('category-list').replaceChildren(...data.rows.map(c=>button(`${c.code} · ${c.name} · ${c.status==='active'?'Hoạt động':'Ngừng sử dụng'}`,()=>{
   $('category-form').reset();$('category-id').value=c.id;$('category-version').value=c.version;$('category-code').value=c.code;$('category-name').value=c.name;$('category-status').value=c.status;$('category-code').focus();
   })));
   $('category-page').textContent=`Trang ${categoryPage}/${Math.max(1,Math.ceil(categoryTotal/20))} · ${categoryTotal} loại`;
   $('category-list-status').textContent=categoryTotal?`Kết quả tìm: ${categoryQuery||'Tất cả loại'}. Chọn loại để sửa hoặc thêm loại mới bên dưới.`:'Không có loại phù hợp. Thử mã hoặc tên khác.';
  }catch(error){
   if(sequence!==categorySequence||!$('category-editor').open)return;
   $('category-list').replaceChildren();$('category-list-status').textContent=error.message;
   throw error;
  }finally{if(sequence===categorySequence){categoryLoading=false;categoryControls();$('category-list').removeAttribute('aria-busy');}}
 }
 const categoryTask=fn=>fn().catch(error=>message('category-message',error.message,true));
 $('category-search-form').addEventListener('submit',event=>{event.preventDefault();if(!categoryLoading&&!categorySaving)categoryTask(()=>renderCategories(1,$('category-search').value.trim()));});
 $('category-prev').addEventListener('click',()=>categoryTask(()=>renderCategories(categoryPage-1)));
 $('category-next').addEventListener('click',()=>categoryTask(()=>renderCategories(categoryPage+1)));
 $('category-close').addEventListener('click',()=>$('category-editor').close());
 $('category-editor').addEventListener('cancel',event=>{if(categorySaving)event.preventDefault();});
 $('category-editor').addEventListener('close',()=>{categorySequence++;categoryLoading=false;});
 $('category-form').addEventListener('reset',()=>{$('category-id').value='';$('category-version').value='';});
 $('category-form').addEventListener('submit',async event=>{
  event.preventDefault();if(categorySaving||categoryLoading)return;categorySaving=true;categoryControls();let persisted=false;
  try{
   await api('category-save',{},Object.fromEntries(['id','version','code','name','status'].map(k=>[k,$('category-'+k).value])));persisted=true;
   $('category-form').reset();refresh();await renderCategories();message('category-message','Đã lưu loại tài sản. Chọn lại loại trong danh sách nếu cần sửa tiếp.');
  }catch(error){message('category-message',persisted?'Đã lưu danh mục, nhưng chưa tải lại được danh sách. Hãy tải lại danh sách trước khi sửa tiếp.':error.message,!persisted);}finally{categorySaving=false;categoryControls();}
 });
 addEventListener('ita:data',updateContext);
})();
