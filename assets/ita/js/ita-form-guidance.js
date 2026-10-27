/** FE mirrors of existing input contracts; server remains authoritative. */
(() => {
 'use strict';
 const help={
  'edit-asset_code':'Mã bắt buộc và duy nhất, tối đa 64 ký tự. Không chỉ nhập khoảng trắng.',
  'edit-name':'Tên bắt buộc, tối đa 200 ký tự. Có thể dùng tiếng Việt, số và dấu câu, ví dụ: Máy in tầng 2 — IT-01.',
  'edit-purchase_price':'Số không âm, tối đa 16 chữ số phần nguyên và 2 số thập phân. Dùng dấu chấm; không nhập dấu phân cách hàng nghìn.',
  'edit-purchase_date':'Ngày hợp lệ từ năm 1000. Ngày mua tương lai hiện được lưu; vòng đời sẽ đánh dấu tuổi chưa hợp lệ.',
  'edit-warranty_end':'Không được trước ngày mua nếu đã nhập cả hai ngày.',
  'handover-return-note':'Bắt buộc ghi lý do hoặc tình trạng thiết bị; tối đa 4.000 ký tự.',
  'handover-due':'Không bắt buộc; không trước hôm nay theo giờ Việt Nam. Chọn ngày trên lịch hoặc nhập theo định dạng trình duyệt.',
  'ticket-cost':'Tổng chi phí của phiếu, không phải phần tăng thêm. Nhập số không âm, dùng dấu chấm thập phân.',
  'ticket-correct-cost':'Nhập tổng chi phí sau điều chỉnh; bắt buộc có lý do riêng.',
  'license-seats':'Số nguyên từ 1 đến 1.000.000. Giảm dưới số đã cấp cần xác nhận và lý do theo quyền.',
  'license-name':'Tên bắt buộc, tối đa 200 ký tự; không chỉ nhập khoảng trắng.'
 };
 function today(){const parts=new Intl.DateTimeFormat('en-CA',{timeZone:'Asia/Ho_Chi_Minh',year:'numeric',month:'2-digit',day:'2-digit'}).formatToParts(new Date()),get=type=>parts.find(p=>p.type===type).value;return get('year')+'-'+get('month')+'-'+get('day');}
 function prepare(input){
  if(!input?.matches('input,textarea,select'))return;
  const text=help[input.id];
  if(text&&!document.getElementById(input.id+'-guidance')){const note=document.createElement('small');note.id=input.id+'-guidance';note.className='field-guidance';note.textContent=text;input.after(note);input.setAttribute('aria-describedby',((input.getAttribute('aria-describedby')||'')+' '+note.id).trim());}
  if(['edit-purchase_date','edit-warranty_end'].includes(input.id)){input.min='1000-01-01';input.max='9999-12-31';}
  if(input.id==='handover-due')input.min=today();
  if(input.id==='ticket-handled')input.max=today();
  if(['ticket-cost','ticket-correct-cost'].includes(input.id)){input.pattern='(?:0|[1-9][0-9]{0,15})(?:\\.[0-9]{1,2})?';input.maxLength=19;input.inputMode='decimal';}
 }
 document.querySelectorAll('input,textarea,select').forEach(prepare);
 document.addEventListener('focusin',event=>prepare(event.target));
 document.addEventListener('input',event=>{const input=event.target;if(typeof input.setCustomValidity==='function'){input.setCustomValidity('');input.removeAttribute('aria-invalid');}for(const pair of [['edit-purchase_date','edit-warranty_end'],['license-start','license-expire']])if(pair.includes(input.id)){const end=document.getElementById(pair[1]);end?.setCustomValidity('');end?.removeAttribute('aria-invalid');}});
 document.addEventListener('invalid',event=>{event.target.setAttribute('aria-invalid','true');},true);
 document.addEventListener('submit',event=>{
  const form=event.target;if(!(form instanceof HTMLFormElement))return;
  for(const input of form.querySelectorAll('input,textarea,select')){
   if(input.disabled||input.type==='password'||input.type==='hidden')continue;
   prepare(input);input.setCustomValidity('');
   if(input.required&&['text','search','textarea'].includes(input.type)&&!input.value.trim())input.setCustomValidity('Vui lòng nhập nội dung, không chỉ khoảng trắng.');
   if(input.id==='edit-purchase_price'&&input.value&&!/^(?:0|[1-9][0-9]{0,15})(?:\.[0-9]{1,2})?$/.test(input.value.trim()))input.setCustomValidity('Giá mua không âm, tối đa 16 chữ số và 2 số thập phân; dùng dấu chấm.');
  }
  const from=form.querySelector('#edit-purchase_date'),to=form.querySelector('#edit-warranty_end');
  if(from?.value&&to?.value&&to.value<from.value)to.setCustomValidity('Ngày hết bảo hành không được trước ngày mua.');
  const start=form.querySelector('#license-start'),expiry=form.querySelector('#license-expire');
  if(start?.value&&expiry?.value&&expiry.value<start.value)expiry.setCustomValidity('Ngày hết hạn phải từ ngày bắt đầu trở đi.');
  if(!form.reportValidity()){event.preventDefault();event.stopImmediatePropagation();}
 },true);
})();
