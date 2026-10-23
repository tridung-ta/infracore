(() => {
 'use strict';
 const $=id=>document.getElementById(id),live=document.body.dataset.mode==='live';
 const tiers={replace:'Đề xuất thay thế',watch:'Theo dõi',normal:'Bình thường',missing:'Chưa đủ dữ liệu'};
 let page=1,total=0,busy=false,fixture=null,loaded=false,perPage=20,groupMode='category',returnFocus=null;
 let groupData={category:[],department:[]},groupPages={category:1,department:1};
 const el=(tag,text)=>{const node=document.createElement(tag);node.textContent=text;return node;};
 const decimal=(value,digits=2)=>value===null||value===undefined?'Chưa có dữ liệu':new Intl.NumberFormat('vi-VN',{maximumFractionDigits:digits}).format(Number(value));
 function feedback(text,error=false){$('lifecycle-message').textContent=text;$('lifecycle-message').dataset.state=error?'error':text.startsWith('Đang')?'loading':'success';$('lifecycle-message').classList.toggle('is-error',error);$('lifecycle-message').setAttribute('role',error?'alert':'status');$('lifecycle-retry').hidden=!error;}
 function allowed(){return !live||['admin_it','system_manager'].includes(window.itaLiveContext?.user.role);}
 function controls(){
  $('lifecycle-panel').querySelectorAll('button,input,select').forEach(n=>n.disabled=busy);
  $('lifecycle-prev').disabled=busy||page<=1;$('lifecycle-next').disabled=busy||page*perPage>=total;
  $('lifecycle-group-prev').disabled=busy||groupPages[groupMode]<=1;
  $('lifecycle-group-next').disabled=busy||groupPages[groupMode]*20>=groupData[groupMode].length;
 }
 async function request(action,query={}){
  const controller=new AbortController(),timer=setTimeout(()=>controller.abort(),15000);
  try{
   const url=live?'/admin.php?'+new URLSearchParams({op:'ita',act:action,...query}):'/assets/ita/mock/lifecycle.json';
   const response=await fetch(url,{credentials:'same-origin',cache:'no-store',signal:controller.signal});
   let body;try{body=await response.json();}catch(error){throw new Error(`Phản hồi không hợp lệ (HTTP ${response.status}). Hãy tải lại dữ liệu.`);}
   if(!response.ok||(live&&!body.success))throw new Error(`${body.message||'Không tải được vòng đời tài sản.'} (HTTP ${response.status})`);
   return live?body.data:body;
  }catch(error){if(error.name==='AbortError'||error instanceof TypeError)throw new Error('Không tải được dữ liệu. Kiểm tra kết nối rồi thử lại.');throw error;}
  finally{clearTimeout(timer);}
 }
 function filters(){return {q:$('lifecycle-search').value,department_id:$('lifecycle-department').value,category_id:$('lifecycle-category').value,tier:$('lifecycle-tier').value};}
 function mockData(source){
  const f=filters(),rows=source.rows.filter(r=>(!f.q||(r.asset_code+' '+r.name).toLocaleLowerCase('vi').includes(f.q.toLocaleLowerCase('vi')))&&(!f.department_id||String(r.department_id)===f.department_id)&&(!f.category_id||String(r.category_id)===f.category_id)&&(!f.tier||r.tier===f.tier));
  const summary=items=>{const known=items.filter(r=>r.age_years!==null);return {total:items.length,failed_assets:items.filter(r=>r.repair_count>0).length,age_known:known.length,average_age_years:known.length?known.reduce((sum,r)=>sum+Number(r.age_years),0)/known.length:null,missing_scores:items.filter(r=>r.tier==='missing').length};};
  const group=field=>[...new Set(rows.map(r=>r[field+'_id']))].map(id=>{const items=rows.filter(r=>r[field+'_id']===id);return {group_id:id,group_name:items[0][field+'_name'],...summary(items)};});
  const last=Math.max(1,Math.ceil(rows.length/20));page=Math.min(page,last);
  return {...source,rows:rows.slice((page-1)*20,page*20),page,total:rows.length,summary:summary(rows),by_category:group('category'),by_department:group('department')};
 }
 function options(id,rows,idKey,nameKey){
  const node=$(id),current=node.value,first=node.options[0].cloneNode(true);node.replaceChildren(first,...rows.map(r=>new Option(r[nameKey],r[idKey])));node.value=current;
 }
 function scoreCell(row,scale){
  const cell=el('td',row.score_display===null?'—':row.score_display.replace('.',','));cell.className='lifecycle-score';
  cell.dataset.label='Điểm thay thế';cell.dataset.tier=row.tier;
  if(row.score_raw!==null){const raw=Number(row.score_raw);const bar=document.createElement('meter');bar.min=0;bar.max=scale;bar.value=raw;bar.setAttribute('aria-label',`Điểm ${row.score_display}, thang 0 đến ${scale}`);bar.textContent=row.score_display;cell.append(bar);cell.title=`Điểm gốc: ${row.score_raw}`;}
  return cell;
 }
 function renderRows(rows){
  const scale=Math.max(1,...rows.map(r=>Number(r.score_raw)||0));
  $('lifecycle-rows').replaceChildren(...rows.map(row=>{
   const tr=el('tr',''),asset=el('td','');asset.append(el('strong',row.name),el('small',row.asset_code+' · '+(row.department_name||'Chưa phân bổ')),el('small',row.category_name));
   const age=el('td',`${decimal(row.age_years)} / ${decimal(row.lifespan_years)} năm`);if(row.age_ratio!==null)age.append(el('small',`${decimal(Number(row.age_ratio)*100)}% tuổi thọ chuẩn`));
   age.dataset.label='Tuổi / Tuổi thọ';
   const count=el('td',String(row.repair_count)),tier=el('td',''),badge=el('span',tiers[row.tier]);count.dataset.label='Sửa chữa 12 tháng';tier.dataset.label='Đánh giá';badge.className='lifecycle-tier';badge.dataset.tier=row.tier;tier.append(badge);
   const action=el('td',''),button=el('button','Xem lý do');button.type='button';button.className='secondary-button';button.setAttribute('aria-label',`Giải thích điểm ${row.asset_code}`);button.addEventListener('click',()=>{returnFocus=button;run(()=>detail(row.id));});action.append(button);tr.dataset.assetId=row.id;tr.append(asset,scoreCell(row,scale),tier,age,count,action);return tr;
  }));
  $('lifecycle-scale').textContent=`Thang điểm trên trang: 0–${decimal(scale,3)} · Điểm có thể lớn hơn 1; hồ sơ chưa đủ dữ liệu xếp cuối.`;
  if(!rows.length){const tr=el('tr',''),td=el('td','Không có thiết bị phù hợp. Thử đổi bộ lọc.');td.className='lifecycle-empty';td.colSpan=6;tr.append(td);$('lifecycle-rows').append(tr);}
 }
 function renderGroups(data){
  if(data){groupData={category:data.by_category,department:data.by_department};groupPages={category:1,department:1};}
  $('lifecycle-group-tables').replaceChildren();
  for(const [key,name,allRows] of [['category','Theo loại thiết bị',groupData.category],['department','Theo bộ phận',groupData.department]]){
   groupPages[key]=Math.max(1,Math.min(groupPages[key],Math.max(1,Math.ceil(allRows.length/20))));
   const rows=allRows.slice((groupPages[key]-1)*20,groupPages[key]*20);
   const wrap=el('div','');wrap.className='lifecycle-group-wrap';wrap.dataset.group=key;wrap.hidden=key!==groupMode;const table=el('table','');table.className='lifecycle-table';table.append(el('caption',name));
   const head=el('thead',''),tr=el('tr','');for(const label of ['Nhóm','Thiết bị','Tuổi TB (năm)','Có tuổi / Thiếu tuổi','Tỷ lệ lỗi']){const th=el('th',label);th.scope='col';tr.append(th);}head.append(tr);table.append(head);
   const body=el('tbody','');for(const row of rows){const line=el('tr',''),count=Number(row.total);for(const value of [row.group_name||'Chưa phân bổ',row.total,decimal(row.average_age_years),`${row.age_known} / ${count-Number(row.age_known)}`,`${row.failed_assets}/${count} · ${count?decimal(Number(row.failed_assets)*100/count):'0'}%`])line.append(el('td',String(value)));body.append(line);}if(!rows.length){const empty=el('tr',''),cell=el('td','Chưa có dữ liệu nhóm trong bộ lọc này.');cell.colSpan=5;empty.append(cell);body.append(empty);}table.append(body);wrap.append(table);$('lifecycle-group-tables').append(wrap);
  }
  const count=groupData[groupMode].length;
  $('lifecycle-group-page').textContent=`${groupMode==='category'?'Loại thiết bị':'Bộ phận'} · Trang ${groupPages[groupMode]}/${Math.max(1,Math.ceil(count/20))} · ${count} nhóm`;
  controls();
 }
 function render(data){
  page=data.page;total=data.total;perPage=data.per_page;const s=data.summary,v=data.config.values;
  $('lifecycle-content').hidden=false;$('lifecycle-mode').textContent=live?'Dữ liệu hệ thống · Chỉ đọc':'MOCK · Dữ liệu minh họa';
  $('lifecycle-window').textContent=`Đánh giá ${data.window.as_of.split('-').reverse().join('/')} · Lịch sử sửa chữa 12 tháng`;
  $('lifecycle-cohort').textContent=`Phiếu sửa chữa từ ${data.window.start_date} đến hết ngày ${data.window.as_of}, theo giờ Việt Nam.`;
  $('lifecycle-defaults').hidden=!data.config.using_defaults;
  $('lifecycle-config-values').textContent=`Trọng số tuổi / sửa chữa / chi phí: ${v.lifecycle_weight_age} / ${v.lifecycle_weight_repairs} / ${v.lifecycle_weight_cost}. Ngưỡng theo dõi: ${v.lifecycle_watch_threshold}; thay thế: ${v.lifecycle_replace_threshold}.`;
  const summary=$('lifecycle-summary');summary.replaceChildren();
  for(const [label,value,note] of [['Trong bộ lọc',`${s.total} thiết bị`,''],['Tuổi trung bình',s.average_age_years===null?'—':decimal(s.average_age_years)+' năm',`${s.age_known} hồ sơ có tuổi`],['Chưa có tuổi hợp lệ',String(Number(s.total)-Number(s.age_known)),'Thiếu ngày mua hoặc ngày ở tương lai'],['Chưa đủ điểm',String(s.missing_scores||0),'Cần bổ sung dữ liệu để đánh giá']]){
   const group=el('div',''),definition=el('dd',value);group.append(el('dt',label),definition);if(note)definition.append(el('small',note));summary.append(group);
  }
  const active=[filters().department_id,filters().category_id].filter(Boolean).length;$('lifecycle-filter-count').textContent=active?` · ${active} đang lọc`:'';
  options('lifecycle-department',data.departments,'value','label');options('lifecycle-category',data.categories,'id','name');renderRows(data.rows);renderGroups(data);
  $('lifecycle-page').textContent=`Trang ${page} · ${total} thiết bị`;
 }
 async function load(){
  if(!allowed())throw new Error('Bạn không có quyền xem phân tích vòng đời tài sản.');
  $('lifecycle-detail').hidden=true;
  const source=await request('lifecycle',{...filters(),page});if(!live)fixture=source;
  render(live?source:mockData(source));loaded=true;feedback(`Đã tải ${total} thiết bị. ${live?'':'MOCK · Chưa kết nối dữ liệu thật.'}`);
 }
 async function detail(id){
  const data=live?await request('lifecycle-detail',{id}):{asset:fixture.rows.find(r=>r.id===id)},r=data.asset;
  $('lifecycle-detail-title').textContent='Đánh giá · '+r.asset_code;$('lifecycle-detail-name').textContent=r.name;
  $('lifecycle-detail-score').textContent=r.score_display===null?'Chưa đủ điểm':r.score_display.replace('.',',')+' điểm';$('lifecycle-detail-score').dataset.tier=r.tier;$('lifecycle-detail-status').textContent=tiers[r.tier]+(r.missing_reasons.length?' · '+r.missing_reasons.join('; '):'');
  const parts=$('lifecycle-detail-parts');parts.replaceChildren();
  for(const [name,value,note] of [['Tuổi sử dụng',r.age_part,`${decimal(r.age_years)} / ${decimal(r.lifespan_years)} năm`],['Tần suất sửa chữa',r.repairs_part,`${r.repair_count} phiếu trong 12 tháng`],['Chi phí / tình trạng hỏng',r.cost_part,r.status==='broken'?'Thiết bị đang hỏng':'So chi phí sửa với giá mua']]){
   const item=el('dl',''),valueNode=el('dd',value===null?'Chưa đủ dữ liệu':Number(value).toFixed(3).replace('.',',')+' điểm');valueNode.append(el('small',note));item.append(el('dt',name),valueNode);parts.append(item);
  }
  $('lifecycle-detail-values').replaceChildren();
  for(const [name,value] of [['Thiết bị',r.name],['Tuổi / Tuổi thọ',`${decimal(r.age_years)} / ${decimal(r.lifespan_years)} năm`],['Thành phần tuổi',r.age_part],['Thành phần sửa chữa',r.repairs_part],['Thành phần chi phí / hỏng',r.cost_part],['Điểm gốc (chưa làm tròn)',r.score_raw],['Phiếu sửa chữa 12 tháng',r.repair_count],['Chi phí sửa / Giá mua (VND)',`${r.repair_cost} / ${r.purchase_price??'Chưa ghi nhận'}`]])$('lifecycle-detail-values').append(el('dt',name),el('dd',value===null?'Chưa đủ dữ liệu':String(value)));
  document.querySelector('.lifecycle-detail-source').open=false;
  document.querySelectorAll('#lifecycle-rows tr').forEach(row=>row.classList.toggle('is-selected',row.dataset.assetId===String(r.id)));
  $('lifecycle-detail').hidden=false;$('lifecycle-detail').focus({preventScroll:true});$('lifecycle-detail').scrollIntoView({behavior:'instant',block:'start'});feedback('Đã tải giải thích điểm.');
 }
 async function run(fn){if(busy)return;busy=true;controls();$('lifecycle-panel').setAttribute('aria-busy','true');feedback('Đang tải dữ liệu vòng đời…');try{await fn();}catch(error){$('lifecycle-content').hidden=true;feedback(error.message,true);}finally{busy=false;controls();$('lifecycle-panel').removeAttribute('aria-busy');}}
 $('lifecycle-filters').addEventListener('submit',event=>{event.preventDefault();page=1;run(load);});
 $('lifecycle-prev').addEventListener('click',()=>{page--;run(load);});$('lifecycle-next').addEventListener('click',()=>{page++;run(load);});
 $('lifecycle-retry').addEventListener('click',()=>run(load));$('lifecycle-detail-close').addEventListener('click',()=>{$('lifecycle-detail').hidden=true;document.querySelectorAll('#lifecycle-rows tr').forEach(row=>row.classList.remove('is-selected'));(returnFocus?.isConnected?returnFocus:$('lifecycle-search')).focus();});
 $('lifecycle-clear').addEventListener('click',()=>{$('lifecycle-filters').reset();page=1;run(load);});
 document.querySelectorAll('[data-lifecycle-group]').forEach(button=>button.addEventListener('click',()=>{
  groupMode=button.dataset.lifecycleGroup;document.querySelectorAll('[data-lifecycle-group]').forEach(n=>n.setAttribute('aria-pressed',String(n===button)));renderGroups();
 }));
 for(const [direction,delta] of [['prev',-1],['next',1]])$('lifecycle-group-'+direction).addEventListener('click',()=>{
  if(busy)return;groupPages[groupMode]+=delta;renderGroups();
 });
 $('lifecycle-show-method').addEventListener('click',()=>{const method=$('lifecycle-method');method.open=true;method.querySelector('summary').focus({preventScroll:true});method.scrollIntoView({behavior:'instant',block:'start'});});
 function activate(){
  const nav=$('lifecycle-nav');nav.hidden=live&&!allowed();
  if(location.hash!=='#lifecycle')return;
  if(live&&!window.itaLiveContext){feedback('Đang xác minh quyền truy cập…');return;}
  if(!allowed()){$('lifecycle-content').hidden=true;feedback('Bạn không có quyền xem phân tích vòng đời tài sản.');return;}
  if(!loaded)run(load);
 }
 addEventListener('ita:view',activate);addEventListener('ita:data',activate);activate();
})();
