/* Synthetic presentation only. No file generation, persistence or production API. */
(() => {
 'use strict';
 const $=id=>document.getElementById(id),departments={it:'Công nghệ thông tin',ops:'Vận hành'},statuses={in_use:'Đang dùng',maintenance:'Bảo trì',broken:'Hỏng',retired:'Thanh lý'},titles={inventory:'Tồn kho',maintenance:'Sắp bảo trì',lifecycle:'Vòng đời ngắn',budget:'Ngân sách'};
 const abort=new AbortController();let source=null,page=1,price='20000000.00';const size=5;
 const el=(tag,text)=>{const n=document.createElement(tag);n.textContent=text;return n;};
 const support=()=>$('report-role').value==='support_technician';
 function dateParts(value){if(typeof value!=='string'||!/^\d{4}-\d{2}-\d{2}$/.test(value))return null;const [y,m,d]=value.split('-').map(Number),date=new Date(Date.UTC(y,m-1,d));return date.toISOString().slice(0,10)===value?[y,m,d]:null;}
 function addMonths(value,n){const p=dateParts(value);if(!p)return null;const month=p[0]*12+p[1]-1+n,y=Math.floor(month/12),m=month%12+1,d=Math.min(p[2],new Date(Date.UTC(y,m,0)).getUTCDate());return `${y}-${String(m).padStart(2,'0')}-${String(d).padStart(2,'0')}`;}
 function addDays(value,n){const p=dateParts(value);return new Date(Date.UTC(p[0],p[1]-1,p[2]+n)).toISOString().slice(0,10);}
 function limit(type,format){return format==='pdf'?500:type==='inventory'?5000:1000;}
 function money(v){return String(v).replace(/\B(?=(\d{3})+(?!\d))/g,' ').replace('.',',');}
 function cents(v){const [whole,fraction='']=v.split('.');return BigInt(whole)*100n+BigInt(fraction.padEnd(2,'0'));}
 function decimal(v){return (v/100n)+'.'+String(v%100n).padStart(2,'0');}
 function currentBudget(){const b=source.budget,p=cents(price),replacement=p*BigInt(b.replacement_count),maintenance=cents(b.maintenance),growth=p*BigInt(b.growth_quantity),subtotal=replacement+maintenance+growth,reserve=(subtotal+5n)/10n;return {price,replacement:decimal(replacement),maintenance:decimal(maintenance),growth:decimal(growth),subtotal:decimal(subtotal),contingency:decimal(reserve),total:decimal(subtotal+reserve)};}
 function selectedAssets(){const department=$('report-department').value;return source.assets.filter(a=>!department||(department==='unassigned'?a.department===null:a.department===department));}
 function table(headers,rows){const t=el('table',''),head=el('thead',''),body=el('tbody',''),hr=el('tr','');t.append(el('caption','Bảng xem trước · MOCK · không phải file xuất'));for(const h of headers){const th=el('th',h);th.scope='col';hr.append(th);}head.append(hr);for(const r of rows){const tr=el('tr','');for(const v of r)tr.append(el('td',v));body.append(tr);}if(!rows.length){const tr=el('tr',''),td=el('td','Không có dữ liệu phù hợp.');td.colSpan=headers.length;tr.append(td);body.append(tr);}t.append(head,body);$('report-table').replaceChildren(t);$('report-table').tabIndex=0;$('report-table').setAttribute('role','region');$('report-table').setAttribute('aria-label','Bảng xem trước có thể cuộn ngang');}
 function data(){
  const type=$('report-type').value,assets=selectedAssets(),today=source.as_of.slice(0,10),active=assets.filter(a=>a.status!=='retired');let rows=[],headers=[],missing=0,note='',budget=null;
  if(type==='inventory'){
   headers=['Mã','Thiết bị','Bộ phận','Loại','Trạng thái','Ngày mua'];rows=assets.map(a=>[a.code,a.name,departments[a.department]||'Chưa ghi nhận',a.category,statuses[a.status],a.purchase_date||'Chưa ghi nhận']);note='Sổ tài sản minh họa có cả trạng thái thanh lý, không gồm hồ sơ xóa mềm.';
  }else if(type==='maintenance'){
   const end=addDays(today,30);headers=['Mã','Thiết bị','Bộ phận','Lịch bảo trì','Tình trạng'];
   rows=active.filter(a=>dateParts(a.maintenance_due)&&a.maintenance_due<=end).map(a=>[a.code,a.name,departments[a.department]||'Chưa ghi nhận',a.maintenance_due,a.maintenance_due<today?'Quá hạn':'Trong 30 ngày']);missing=active.filter(a=>!dateParts(a.maintenance_due)).length;note=`Minh họa cửa sổ 30 ngày đến ${end}, gồm cả quá hạn. Đây là ngưỡng đề xuất trong gate10, chưa duyệt BE.`;
  }else if(type==='lifecycle'){
   const end=addMonths(today,12);headers=['Mã','Thiết bị','Đến tuổi thọ','Điểm (3 số lẻ)','Tier','Đối chiếu'];
   for(const a of active){const valid=dateParts(a.purchase_date)&&a.purchase_date<=today&&Number.isInteger(a.lifespan_years)&&a.lifespan_years>0;const due=valid?addMonths(a.purchase_date,a.lifespan_years*12):null;
    if(!due){missing++;continue;}if(due>end)continue;const score=a.score_raw===null?null:Number(a.score_raw),tier=score===null?'missing':score>=.8?'replace':score>=.6?'watch':'normal';rows.push([a.code,a.name,due,score===null?'Chưa đủ dữ liệu':score.toFixed(3),tier,due<=today?'Đã tới tuổi thọ':'Còn tối đa 12 tháng lịch']);}
   note=`Đã tới hoặc còn tối đa12 tháng lịch đến tuổi thọ (mốc ${end}); không dùng tier replace thay cho điều kiện tuổi. Ngày cuối tháng được chặn ở ngày hợp lệ.`;
  }else{
   const snapshot=$('report-budget-source').value==='snapshot',b=source.budget,department=$('report-department').value;headers=['Thành phần','Số lượng / căn cứ','Đơn giá VND','Thành tiền VND'];
   if(!department||department===b.department){budget=snapshot?b.snapshot:currentBudget();rows=[['Thay thế',String(b.replacement_count),money(budget.price),money(budget.replacement)],['Bảo trì','9.000.000 / 3 năm','Theo chi phí lịch sử',money(budget.maintenance)],['Mua thêm',`Làm tròn lên (${b.headcount} × ${b.units_per_person.replace('.',',')}) = ${b.growth_quantity}`,money(budget.price),money(budget.growth)]];}
   note='Đây là kế hoạch ngân sách giả riêng để đối chiếu công thức, không tính từ bảng thiết bị phía trên. Ba thành phần cộng dự phòng 10%. Chỉ có kế hoạch CNTT; bộ phận khác không được coi là ngân sách 0. Giá giả của dự báo hiện tại không thay đổi snapshot.';
  }
  return {headers,rows,missing,note,budget};
 }
 function render(){
  if(!source)return;const role=$('report-role').value;
  if(role!=='admin_it')$('report-department').value='it';if(support())$('report-type').value='maintenance';
  $('report-type').disabled=support();$('report-department').disabled=role!=='admin_it';$('report-department-field').hidden=support();
  const type=$('report-type').value,budget=type==='budget',snapshot=$('report-budget-source').value==='snapshot',view=data();
  $('report-content').hidden=false;$('report-budget-controls').hidden=!budget||support();$('report-limit-panel').hidden=support();$('report-price').disabled=snapshot;$('report-price-apply').disabled=snapshot;
  $('report-price').value=snapshot?source.budget.snapshot.price:price;
  $('report-heading').textContent=titles[type]+' · MOCK';$('report-definition').textContent=view.note;$('report-count').textContent=view.rows.length+' dòng dữ liệu';$('report-missing').textContent=view.missing?view.missing+' hồ sơ chưa đủ dữ liệu ngày mua/tuổi thọ hoặc lịch bảo trì, không tự điền0.':'';
  $('report-source').textContent=budget&&!view.budget?'Không có kế hoạch trong bộ phận đang chọn':budget&&snapshot?'Snapshot giả '+source.budget.snapshot.id+' · lưu '+new Date(source.budget.snapshot.saved_at).toLocaleString('vi-VN',{timeZone:'Asia/Ho_Chi_Minh'}):budget?'Dự báo hiện tại (giả), chưa lưu · Giá đã áp dụng: '+money(price)+' VND':'Nguồn: tệp dữ liệu giả, không phải host';
  const pages=Math.max(1,Math.ceil(view.rows.length/size));page=Math.max(1,Math.min(page,pages));table(view.headers,view.rows.slice((page-1)*size,page*size));
  $('report-page').textContent=`Trang ${page}/${pages} · ${view.rows.length} dòng`;$('report-previous').disabled=page<=1;$('report-next').disabled=page>=pages;
  $('report-totals').hidden=!view.budget;if(view.budget){const labels={replacement:'Thay thế',maintenance:'Bảo trì',growth:'Mua thêm',subtotal:'Cộng ba thành phần',contingency:'Dự phòng10%',total:'Tổng ngân sách'};$('report-totals').replaceChildren(...Object.entries(labels).map(([k,label])=>{const d=el('div','');d.append(el('dt',label+' · VND'),el('dd',money(view.budget[k])));return d;}));}else $('report-totals').replaceChildren();
  $('report-limit-count').value=view.rows.length;$('report-limit-result').textContent='';$('report-limit-result').removeAttribute('data-state');
  $('report-message').textContent=support()?'MOCK · Hỗ trợ chỉ xem bảng bảo trì CNTT, không có export hoặc bộ kiểm giới hạn.':'MOCK · Chỉ xem trước và kiểm quy tắc, chưa tạo tệp.';$('report-message').dataset.error='false';
 }
 async function load(){
  $('report-retry').disabled=true;$('report-message').textContent='Đang tải dữ liệu giả…';
  try{const r=await fetch('/assets/ita/mock/reports.json',{cache:'no-store',signal:abort.signal});if(!r.ok)throw new Error();const value=await r.json();if(value.mock!==true||!Array.isArray(value.assets)||!value.budget||!Number.isFinite(new Date(value.as_of).getTime()))throw new Error();source=value;const date=new Date(source.as_of);$('report-asof').textContent='Số liệu tại '+date.toLocaleTimeString('vi-VN',{timeZone:'Asia/Ho_Chi_Minh',hour:'2-digit',minute:'2-digit'});$('report-date').textContent=date.toLocaleDateString('vi-VN',{timeZone:'Asia/Ho_Chi_Minh'})+' · giờ VN · mốc fixture';$('report-retry').hidden=true;render();}
  catch(e){if(e.name==='AbortError')return;$('report-message').textContent='Không tải được dữ liệu MOCK. Tải lại để thử tiếp.';$('report-message').dataset.error='true';$('report-retry').hidden=false;}
  finally{$('report-retry').disabled=false;}
 }
 $('report-filters').addEventListener('submit',e=>{e.preventDefault();page=1;render();});$('report-role').addEventListener('change',()=>{page=1;render();});
 for(const id of ['report-type','report-department'])$(id).addEventListener('change',()=>{page=1;render();});
 $('report-budget-source').addEventListener('change',()=>{page=1;render();});$('report-previous').addEventListener('click',()=>{page--;render();});$('report-next').addEventListener('click',()=>{page++;render();});$('report-retry').addEventListener('click',load);
 $('report-price-apply').addEventListener('click',()=>{const value=$('report-price').value;if(!/^\d{1,12}(?:\.\d{1,2})?$/.test(value)){$('report-message').textContent='Giá giả phải không âm, tối đa12 chữ số nguyên và2 số lẻ.';$('report-message').dataset.error='true';return;}price=decimal(cents(value));render();});
 $('report-limit-form').addEventListener('submit',e=>{e.preventDefault();if(support())return;const value=$('report-limit-count').value,n=Number(value),max=limit($('report-type').value,$('report-format').value),out=$('report-limit-result');
  if(!/^\d+$/.test(value)||!Number.isSafeInteger(n)||n<0||n>1000000){out.textContent='Số dòng giả phải là số nguyên từ0 đến1.000.000.';out.dataset.state='reject';return;}
  out.dataset.state=n>0&&n<=max?'accept':'reject';out.textContent=n===0?'Không có dữ liệu, chưa có gì để tạo tệp.':n>max?`Từ chối: ${n.toLocaleString('vi-VN')} dòng vượt giới hạn ${max.toLocaleString('vi-VN')}. Không cắt ngầm; hãy thu hẹp bộ lọc.`:`Trong giới hạn ${max.toLocaleString('vi-VN')} dòng. Đây chỉ là kiểm tra MOCK, chưa tạo hoặc tải tệp.`;
 });
 $('report-format').addEventListener('change',()=>{$('report-limit-result').textContent='';});$('report-limit-count').addEventListener('input',()=>{$('report-limit-result').textContent='';});
 addEventListener('pagehide',()=>abort.abort(),{once:true});addEventListener('pageshow',e=>{if(e.persisted)location.reload();});
 window.itaReportsPreview={addMonths,limit};load();
})();
