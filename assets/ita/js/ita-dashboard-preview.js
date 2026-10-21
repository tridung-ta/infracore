/* Standalone synthetic preview. No authentication, API, database, writes or polling. */
(() => {
 'use strict';
 const $=id=>document.getElementById(id),statusNames={in_use:'Đang dùng',maintenance:'Bảo trì',broken:'Hỏng',retired:'Thanh lý'},departmentNames={it:'Công nghệ thông tin',ops:'Vận hành'};
 let source=null,asOf=null,instances=[],observer=null,loaded=false,sequence=0;
 const lifetime=new AbortController(),signal=lifetime.signal;
 const el=(tag,text)=>{const n=document.createElement(tag);n.textContent=text;return n;};
 const vnDate=date=>{const p=Object.fromEntries(new Intl.DateTimeFormat('en',{timeZone:'Asia/Ho_Chi_Minh',year:'numeric',month:'2-digit',day:'2-digit'}).formatToParts(date).map(p=>[p.type,p.value]));return `${p.year}-${p.month}-${p.day}`;};
 function windowStart(today){const [y,m,d]=today.split('-').map(Number),end=new Date(Date.UTC(y-1,m,0)).getUTCDate();return `${y-1}-${String(m).padStart(2,'0')}-${String(Math.min(d,end)).padStart(2,'0')}`;}
 function selection(){const role=$('preview-role').value,department=role==='admin_it'?$('preview-department').value:'it';return source.assets.filter(a=>!department||(department==='unassigned'?a.department===null:a.department===department));}
 function groupName(a){return $('preview-group').value==='category'?a.category:$('preview-group').value==='department'?(departmentNames[a.department]||'Chưa ghi nhận'):(a.purchase_date?.slice(0,4)||'Chưa ghi nhận');}
 function model(rows){
  const today=vnDate(asOf),start=windowStart(today),active=rows.filter(a=>a.status!=='retired');
  const failed=a=>a.repairs.some(d=>d>=start&&d<=today),groups=[...new Set(rows.map(groupName))].sort((a,b)=>a.localeCompare(b,'vi'));
  return {rows,active,groups,failed,total:rows.length,failedCount:active.filter(failed).length,
   replacement:active.filter(a=>a.score_raw!==null&&Number(a.score_raw)>=.8),
   alerts:source.alerts.filter(a=>a.active&&rows.some(r=>r.id===a.asset_id)),start,today};
 }
 function table(target,caption,headers,rows){
  const t=el('table',''),head=el('thead',''),body=el('tbody',''),hr=el('tr','');t.append(el('caption',caption));
  for(const label of headers){const th=el('th',label);th.scope='col';hr.append(th);}head.append(hr);
  for(const row of rows){const tr=el('tr','');for(const v of row)tr.append(el('td',v));body.append(tr);}
  if(!rows.length){const tr=el('tr',''),td=el('td','Không có dữ liệu phù hợp.');td.colSpan=headers.length;tr.append(td);body.append(tr);}
  t.append(head,body);$(target).replaceChildren(t);$(target).tabIndex=0;$(target).setAttribute('role','region');$(target).setAttribute('aria-label',caption+' — có thể cuộn ngang');
 }
 function dispose(){for(const c of instances)c.dispose();instances=[];observer?.disconnect();observer=null;}
 function colors(){const css=getComputedStyle(document.documentElement),get=k=>css.getPropertyValue('--ita-'+k).trim();return {text:get('text'),muted:get('text-muted'),border:get('border'),accent:get('accent'),status:[get('success'),get('warning'),get('danger'),get('text-muted')]};}
 function chart(id,groups,series,max){
  if(!window.echarts){$(id).textContent='Không tải được biểu đồ. Bảng đối chiếu vẫn có đầy đủ số liệu.';return;}
  const c=colors(),instance=echarts.init($(id),null,{renderer:'svg'});instances.push(instance);
  instance.setOption({animation:false,aria:{enabled:true},textStyle:{fontFamily:'Inter, system-ui, sans-serif',color:c.text},
   tooltip:{trigger:'axis',renderMode:'richText'},legend:{top:0,textStyle:{color:c.muted},type:'scroll'},grid:{left:12,right:18,top:48,bottom:12,containLabel:true},
   xAxis:{type:'value',...(max?{max}:{}),minInterval:max?undefined:1,axisLabel:{color:c.muted},splitLine:{lineStyle:{color:c.border}}},
   yAxis:{type:'category',inverse:true,data:groups,axisLabel:{color:c.muted,width:115,overflow:'truncate'},axisLine:{show:false},axisTick:{show:false}},
   series:series.map((s,i)=>({type:'bar',...s,itemStyle:{color:series.length===1?c.accent:c.status[i]},barMaxWidth:24}))});
  observer.observe($(id));
 }
 function renderCharts(m){
  observer=new ResizeObserver(()=>{for(const c of instances)if(!c.isDisposed())c.resize();});
  const statusRows=m.groups.map(g=>[g,...Object.keys(statusNames).map(s=>m.rows.filter(a=>groupName(a)===g&&a.status===s).length)]);
  table('preview-status-table','Đối chiếu tình trạng theo nhóm', ['Nhóm',...Object.values(statusNames)],statusRows);
  chart('preview-status-chart',m.groups,Object.entries(statusNames).map(([s,label],i)=>({name:label,stack:'status',data:statusRows.map(r=>r[i+1])})));
  const faults=m.groups.map(g=>{const rows=m.active.filter(a=>groupName(a)===g),n=rows.filter(m.failed).length;return [g,n,rows.length,rows.length?100*n/rows.length:null];});
  table('preview-fault-table',`Đối chiếu tỷ lệ lỗi · ${m.start} đến ${m.today} (VN)`,['Nhóm','Có sửa chữa','Mẫu số','Tỷ lệ'],faults.map(r=>[r[0],r[1],r[2],r[3]===null?'Chưa có mẫu số':r[3].toFixed(1)+'%']));
  chart('preview-fault-chart',m.groups,[{name:'Tỷ lệ lỗi (%)',data:faults.map(r=>r[3])}],100);
  const replacements=m.groups.map(g=>m.replacement.filter(a=>groupName(a)===g).length);
  chart('preview-replace-chart',m.groups,[{name:'Đề xuất thay thế',data:replacements}]);
  table('preview-replace-table','Thiết bị đề xuất thay thế · điểm gốc được dùng so ngưỡng', ['Mã','Thiết bị','Điểm (3 số lẻ)','Điểm gốc','Tier'],m.replacement.map(a=>[a.code,a.name,Number(a.score_raw).toFixed(3),a.score_raw,'replace']));
 }
 function render(){
  if(!source)return;dispose();
  if($('preview-role').value!=='admin_it')$('preview-department').value='it';
  const support=$('preview-role').value==='support_technician',view=support?'maintenance':(['alerts','maintenance'].includes(location.hash.slice(1))?location.hash.slice(1):'dashboard'),m=model(selection());
  for(const v of ['dashboard','alerts','maintenance'])$('preview-'+v).hidden=v!==view;
  for(const a of document.querySelectorAll('.preview-tabs a')){if(a.hash==='#'+view)a.setAttribute('aria-current','page');else a.removeAttribute('aria-current');}
  for(const id of ['preview-dashboard-tab','preview-alerts-tab','preview-bell','preview-department-field','preview-group-field'])$(id).hidden=support;
  $('preview-department').disabled=$('preview-role').value!=='admin_it';
  $('preview-missing-score').hidden=view!=='dashboard';$('preview-missing-score').textContent=m.active.filter(a=>a.score_raw===null).length+' hồ sơ thiếu điểm: chưa đủ dữ liệu để xếp hạng, không coi là điểm 0.';
  $('preview-asof').textContent='Số liệu tại '+asOf.toLocaleTimeString('vi-VN',{timeZone:'Asia/Ho_Chi_Minh',hour:'2-digit',minute:'2-digit'});
  $('preview-date').textContent=asOf.toLocaleDateString('vi-VN',{timeZone:'Asia/Ho_Chi_Minh'})+' · giờ Việt Nam · MOCK';
  for(const id of ['preview-dashboard','preview-alerts','preview-maintenance'])$(id).dataset.asOf=asOf.toISOString();
  $('preview-alert-count').textContent=String(m.alerts.length);$('preview-bell').setAttribute('aria-label',m.alerts.length+' cảnh báo đang hiệu lực, không phải chưa đọc');
  const metrics=[['Trong sổ tài sản',m.total,'Gồm thanh lý, chưa xóa'],['Đang quản lý',m.active.length,'Không gồm thanh lý'],['Thiết bị có sửa chữa',m.active.length?(100*m.failedCount/m.active.length).toFixed(1)+'%':'—',m.failedCount+'/'+m.active.length+' thiết bị trong cửa sổ12 tháng'],['Đề xuất thay thế',m.replacement.length,'Điểm gốc ≥0,8; phần cứng chưa thanh lý']];
  $('preview-kpis').replaceChildren(...metrics.map(([label,n,note])=>{const d=el('div','');d.append(el('dt',label),el('dd',n),el('small',note));return d;}));
  const maintenance=m.active.filter(a=>a.maintenance_due||a.status==='maintenance'||a.status==='broken');
  table('preview-maintenance-table','Bảng bảo trì minh họa trong phạm vi vai trò', ['Mã','Thiết bị','Trạng thái','Lịch bảo trì'],maintenance.map(a=>[a.code,a.name,statusNames[a.status],a.maintenance_due||'Chưa ghi nhận']));
  table('preview-alert-table','Cảnh báo đang hiệu lực trong phạm vi', ['Mã thiết bị','Thiết bị','Lý do'],m.alerts.map(a=>{const asset=m.rows.find(r=>r.id===a.asset_id);return [asset.code,asset.name,a.label];}));
  if(view==='dashboard')renderCharts(m);
  $('preview-message').textContent=support?'MOCK · Hỗ trợ chỉ xem bảng bảo trì CNTT trong bản xem thử. Không có export.':'MOCK · Đã áp dụng cùng một mốc thời gian cho toàn màn hình. Không tự làm mới.';
 }
 async function load(){
  const seq=++sequence;$('preview-refresh').disabled=true;$('preview-message').textContent='Đang tải dữ liệu giả…';
  try{const r=await fetch('/assets/ita/mock/dashboard.json',{cache:'no-store',signal});if(!r.ok)throw new Error();const data=await r.json();if(data.mock!==true||!Array.isArray(data.assets)||!Array.isArray(data.alerts))throw new Error();if(seq!==sequence)return;source=data;asOf=new Date();loaded=true;render();}
  catch(e){if(e.name==='AbortError')return;$('preview-message').textContent='Không tải được dữ liệu MOCK. Bấm làm mới để thử lại.';}
  finally{$('preview-refresh').disabled=false;}
 }
 $('preview-filters').addEventListener('submit',e=>{e.preventDefault();render();},{signal});
 $('preview-role').addEventListener('change',render,{signal});$('preview-refresh').addEventListener('click',load,{signal});
 addEventListener('hashchange',render,{signal});addEventListener('pagehide',()=>{dispose();lifetime.abort();},{once:true});
 addEventListener('pageshow',e=>{if(e.persisted)location.reload();});
 // Narrow diagnostic surface for local browser checks. Contains no live data or auth mechanism.
 window.itaDashboardPreview={chartCount:()=>instances.length,loaded:()=>loaded,windowStart};
 load();
})();
