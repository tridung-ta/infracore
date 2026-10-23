(() => {
 'use strict';
 const $=id=>document.getElementById(id),live=document.body.dataset.mode==='live',now=Number(new Intl.DateTimeFormat('en',{timeZone:'Asia/Ho_Chi_Minh',year:'numeric'}).format(new Date()));
 let config=null,plans=[],documentData=null,busy=false,loaded=false,displayFilters={},rowPage=1;
 $('forecast-year').value=now;$('forecast-year').min=now;
 const node=(tag,text)=>{const n=document.createElement(tag);n.textContent=text;return n;};
 const money=v=>v===null?'Chưa đủ dữ liệu':String(v).replace(/\B(?=(\d{3})+(?!\d))/g,' ').replace('.',',');
 const allowed=()=>!live||['admin_it','system_manager'].includes(window.itaLiveContext?.user.role);
 function message(text,error=false){$('forecast-message').textContent=text;$('forecast-message').dataset.error=String(error);$('forecast-message').setAttribute('role',error?'alert':'status');$('forecast-retry').hidden=!error;}
 function filters(){return {year:$('forecast-year').value,department_id:$('forecast-department').value,category_id:$('forecast-category').value};}
 function selected(){return plans.find(p=>String(p.department_id)===$('forecast-plan-department').value);}
 async function request(action,data={},post=false,file=false){
  const controller=new AbortController(),timer=setTimeout(()=>controller.abort(),30000);
  try{
   const url='/admin.php?'+new URLSearchParams({op:'ita',act:action,...(!post?data:{})});
   const response=await fetch(url,{method:post?'POST':'GET',credentials:'same-origin',cache:'no-store',signal:controller.signal,
    ...(post?{headers:{'Content-Type':'application/json','X-CSRF-Token':document.querySelector('meta[name="ita-csrf"]')?.content||''},body:JSON.stringify(data)}:{})});
   if(file&&response.ok){
    const mime=(response.headers.get('content-type')||'').split(';')[0].trim().toLowerCase(),expected=data.format==='pdf'?'application/pdf':'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    if(mime!==expected)throw new Error('Máy chủ chưa trả đúng file báo cáo. Không tải tệp; hãy kiểm tra phiên đăng nhập.');
    const blob=await response.blob(),bytes=new Uint8Array(await blob.slice(0,5).arrayBuffer());
    const valid=data.format==='pdf'?new TextDecoder().decode(bytes)==='%PDF-':bytes[0]===80&&bytes[1]===75&&bytes[2]===3&&bytes[3]===4;
    if(!valid)throw new Error('File báo cáo không hợp lệ. Chưa tải tệp.');
    return blob;
   }
   let body;try{body=await response.json();}catch(e){throw new Error('Phản hồi không hợp lệ (HTTP '+response.status+').');}
   if(!response.ok||!body.success)throw new Error(body.message||'Không thể thực hiện yêu cầu.');
   return body.data;
  }catch(e){if(e.name==='AbortError'||e instanceof TypeError)throw new Error(post?'Chưa xác nhận đã lưu. Tải lại kế hoạch trước khi thử tiếp; không gửi lại tự động.':'Không tải được dữ liệu. Hãy kiểm tra kết nối.');throw e;}
  finally{clearTimeout(timer);}
 }
 function options(id,rows,blank){const n=$(id),old=n.value;n.replaceChildren(...(blank?[new Option(blank,'')]:[]),...rows.map(r=>new Option(r.name,r.id)));if([...n.options].some(o=>o.value===old))n.value=old;}
 function configure(data){config=data;options('forecast-department',data.departments,'Tất cả trong phạm vi');options('forecast-plan-department',data.departments,'Chọn bộ phận');options('forecast-category',data.categories,'Tất cả loại');options('forecast-config-category',data.categories,null);$('forecast-config').hidden=!data.can_configure;configFields();planFields();}
 function configFields(){const c=config?.categories.find(c=>String(c.id)===$('forecast-config-category').value);if(!c)return;for(const [id,key]of [['forecast-price','price'],['forecast-units','units_per_person'],['forecast-percent','maintenance_percent']])$(id).value=c[key]??'';}
 function planFields(){const p=selected();$('forecast-headcount').value=p?.planned_headcount_increase??'';$('forecast-note').value=p?.note??'';$('forecast-confirm').checked=false;$('forecast-view-plan').disabled=!p?.has_snapshot||busy;$('forecast-save-plan').disabled=busy||!live||p?.status==='finalized';$('forecast-headcount').disabled=p?.status==='finalized'||busy;$('forecast-note').disabled=p?.status==='finalized'||busy;$('forecast-plan-state').textContent=p?(p.status==='finalized'?'Đã chốt — chỉ đọc snapshot.':'Đã lưu bản nháp. Tính lại cần xác nhận thay snapshot.'):'Chưa có kế hoạch năm/bộ phận này.';}
 function render(data){
  documentData=data;displayFilters={...filters()};$('forecast-content').hidden=false;
  $('forecast-source').textContent=(data.saved?'Snapshot đã lưu — ':'Dự báo hiện tại, chưa lưu — ')+data.year+' · '+new Date(data.as_of).toLocaleString('vi-VN',{timeZone:'Asia/Ho_Chi_Minh'})+(data.lifecycle_config?.using_defaults?' · Ngưỡng vòng đời đang dùng giá trị mặc định.':'');
  $('forecast-incomplete').textContent=data.summary.complete?'':'Chưa đủ dữ liệu. Các tổng dưới đây chỉ gồm phần đã tính.'+(data.unassigned_assets?' Có '+data.unassigned_assets+' thiết bị chưa có bộ phận, chưa đưa vào các dòng dự toán.':'');
  const labels={replacement:'Thay thế',maintenance:'Bảo trì',growth:'Mua thêm',contingency:'Dự phòng 10%',total:data.summary.complete?'Tổng ngân sách':'Tổng phần đã tính'};
  $('forecast-summary').replaceChildren(...Object.entries(labels).map(([k,label])=>{const d=node('div','');d.append(node('dt',label+' · VND'),node('dd',money(data.summary[k])));return d;}));
  rowPage=1;renderRows();
 }
 function rowControls(){const total=documentData?.rows.length||0;$('forecast-row-prev').disabled=busy||rowPage<=1;$('forecast-row-next').disabled=busy||rowPage*20>=total;}
 function renderRows(){
  $('forecast-rows').replaceChildren(...documentData.rows.slice((rowPage-1)*20,rowPage*20).map(r=>{const tr=node('tr',''),first=node('td',r.department_name);first.append(node('small',r.category_name));tr.append(first,...['replacement','maintenance','growth'].map(k=>node('td',money(r[k]))),node('td',[...r.issues,`Thiết bị: ${r.asset_count}; thay thế: ${r.replacement_count}; mua thêm: ${r.growth_quantity??'chưa xác định'}`,`Giá: ${r.inputs.price??'chưa có'}; thiết bị/người: ${r.inputs.units_per_person??'chưa có'}; bảo trì dự phòng: ${r.inputs.maintenance_percent??'chưa có'}%`].join(' · ')));return tr;}));
  const total=documentData.rows.length;
  if(!total){const tr=node('tr',''),td=node('td','');td.textContent='Chưa có dòng ngân sách trong nguồn đang xem.';td.colSpan=5;tr.append(td);$('forecast-rows').append(tr);}
  $('forecast-row-page').textContent=`Trang ${rowPage}/${Math.max(1,Math.ceil(total/20))} · ${total} dòng`;
  rowControls();
 }
 for(const [direction,delta] of [['prev',-1],['next',1]])$('forecast-row-'+direction).addEventListener('click',()=>{if(busy||!documentData)return;rowPage=Math.max(1,Math.min(rowPage+delta,Math.max(1,Math.ceil(documentData.rows.length/20))));renderRows();});
 async function load(){
  let data;
  if(live){data=await request('forecast',filters());plans=data.plans;configure(data.config);}
  else{data=await (await fetch('/assets/ita/mock/forecast.json',{cache:'no-store'})).json();plans=data.plans;configure(data.config);data={...data,year:Number($('forecast-year').value),rows:data.rows.filter(r=>(!filters().department_id||String(r.department_id)===filters().department_id)&&(!filters().category_id||String(r.category_id)===filters().category_id))};
   const cents=v=>BigInt(v.replace('.','')),fmt=v=>(v/100n)+'.'+String(v%100n).padStart(2,'0');const s={};for(const k of ['replacement','maintenance','growth'])s[k]=fmt(data.rows.reduce((n,r)=>n+(r[k]===null?0n:cents(r[k])),0n));const sum=cents(s.replacement)+cents(s.maintenance)+cents(s.growth),reserve=(sum+5n)/10n;data.summary={...s,subtotal:fmt(sum),contingency:fmt(reserve),total:fmt(sum+reserve),complete:data.rows.every(r=>!r.issues.length)};
  }
  render(data);loaded=true;message(live?'Đã tính dự báo hiện tại.':'MOCK · Chỉ minh họa; lưu và xuất được kiểm bằng luồng API riêng.');
 }
 async function run(work){if(busy)return;busy=true;$('forecast-panel').setAttribute('aria-busy','true');$('forecast-panel').querySelectorAll('button,input,select').forEach(n=>n.disabled=true);message('Đang xử lý…');
  try{await work();}catch(e){message(e.message,true);}finally{busy=false;$('forecast-panel').setAttribute('aria-busy','false');$('forecast-panel').querySelectorAll('button,input,select').forEach(n=>n.disabled=false);const p=selected();$('forecast-save-plan').disabled=!live||p?.status==='finalized';$('forecast-view-plan').disabled=!p?.has_snapshot;$('forecast-headcount').disabled=p?.status==='finalized';$('forecast-note').disabled=p?.status==='finalized';if(!live)$('forecast-panel').querySelectorAll('#forecast-config-form button,#forecast-xlsx,#forecast-pdf').forEach(n=>n.disabled=true);rowControls();}}
 $('forecast-filters').addEventListener('submit',e=>{e.preventDefault();run(load);});$('forecast-retry').addEventListener('click',()=>run(load));
 $('forecast-year').addEventListener('change',()=>{if($('forecast-year').checkValidity())run(load);});
 $('forecast-plan-department').addEventListener('change',planFields);$('forecast-config-category').addEventListener('change',configFields);
 $('forecast-plan-form').addEventListener('submit',e=>{e.preventDefault();if(!live)return;const p=selected(),confirmed=$('forecast-confirm').checked;
  const input={year:$('forecast-year').value,department_id:$('forecast-plan-department').value,planned_headcount_increase:$('forecast-headcount').value,note:$('forecast-note').value,version:p?.version??'',confirm_recalculate:confirmed};
  if(p&&!confirmed){message('Hãy xác nhận tính lại trước khi thay snapshot bản nháp.',true);return;}
  run(async()=>{const data=await request('forecast-plan-save',input,true);render(data);plans=await request('forecast-plans',{year:input.year});planFields();message('Đã lưu kế hoạch và snapshot. Thay đổi giá sau này không đổi số liệu này.');});
 });
 $('forecast-view-plan').addEventListener('click',()=>run(async()=>{const p=selected();if(!p)return;render(await request('forecast-plan',{id:p.id,category_id:$('forecast-category').value}));message('Đang xem số liệu snapshot đã lưu.');}));
 $('forecast-config-form').addEventListener('submit',e=>{e.preventDefault();if(!live)return;const input={category_id:$('forecast-config-category').value,version:config.version,price:$('forecast-price').value,units_per_person:$('forecast-units').value,maintenance_percent:$('forecast-percent').value};run(async()=>{configure(await request('forecast-config-save',input,true));message('Đã lưu cấu hình. Snapshot đã lưu giữ nguyên; bấm Tính dự báo hiện tại để xem giá mới.');});});
 for(const format of ['xlsx','pdf'])$('forecast-'+format).addEventListener('click',()=>run(async()=>{const input=documentData.saved?{plan_id:documentData.plan_id,category_id:documentData.category_id??'',format}:{...displayFilters,format};const blob=await request('forecast-export',input,true,true),url=URL.createObjectURL(blob),a=node('a','');a.href=url;a.download=`ita-budget-${documentData.year}.${format}`;document.body.append(a);a.click();a.remove();setTimeout(()=>URL.revokeObjectURL(url),1000);message('Đã tải báo cáo ngân sách.');}));
 function activate(){const ok=allowed();$('forecast-nav').hidden=live&&!ok;if(location.hash!=='#forecast')return;if(live&&!window.itaLiveContext)return;if(!ok){$('forecast-content').hidden=true;$('forecast-plans').hidden=true;$('forecast-config').hidden=true;message('Bạn không có quyền xem ngân sách.',true);return;}$('forecast-mode').textContent=live?'Dữ liệu hệ thống':'MOCK · Dữ liệu minh họa';if(!loaded)run(load);}
 addEventListener('ita:view',activate);addEventListener('ita:data',activate);activate();
})();
