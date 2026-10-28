/** Manual review controls only served by the loopback review server. */
(() => {
 'use strict';
 const el=(tag,text)=>{const n=document.createElement(tag);if(text)n.textContent=text;return n;},bar=el('aside');bar.id='local-review-bar';bar.setAttribute('aria-label','Điều khiển mô phỏng local');
 const details=el('details'),summary=el('summary','LOCAL MOCK · Chỉ kiểm giao diện · Không DB/host · Bấm mở hướng dẫn và kịch bản');details.open=innerWidth>=768;
 const note=el('p','Chỉ dùng dữ liệu giả. Nút Lưu/nhập/phân quyền chỉ đổi bộ nhớ của bản mô phỏng này. Import không đọc Excel bạn chọn; kết quả theo kịch bản. Không nhập tài khoản/mật khẩu thật.');
 const controls=el('div');controls.className='review-controls';
 function select(id,title,items){const label=el('label',title),input=el('select');input.id=id;input.className='form-select';for(const [value,text]of items){const o=el('option',text);o.value=value;input.append(o);}label.append(input);controls.append(label);return input;}
 const role=select('review-role','Quyền mô phỏng',[['admin_it','Admin — đầy đủ'],['system_manager','Quản lý'],['support_technician','Hỗ trợ'],['custom','C: xem/thêm tài sản + báo cáo']]);
 const mode=select('review-mode','Kịch bản',[['normal','Bình thường / import hợp lệ'],['import-errors','Import có lỗi mã loại/bộ phận'],['network-over-limit','Mạng vượt 200 — thử lọc'],['million-ui','1 triệu: chỉ giả số tổng, không sinh DB']]);
 const reset=el('button','Đặt lại tất cả dữ liệu giả');reset.type='button';reset.className='secondary-button';controls.append(reset);
 const login=el('a','Xem giao diện đăng nhập');login.href='/assets/ita/mock/login-preview.html';login.target='_blank';login.rel='noopener';controls.append(login);
 const hints=el('p','Thử theo menu: Nhật ký → lọc tên/ngày; Tài khoản → chọn C để tick quyền; Vòng đời → Xem N thiết bị; Import → tải mẫu rồi chọn file; Bàn giao → Tìm rồi chọn; Bảo trì → Mở phiếu, sửa chi phí; Giấy phép → thử năm111111; Cảnh báo → xem rồi reload; Mạng → 3D/chuột/toàn màn hình. Key demo chỉ dùng mật khẩu MOCK-ONLY.');
 const status=el('p');status.id='review-status';status.setAttribute('role','status');details.append(summary,note,controls,hints,status);bar.append(details);document.body.prepend(bar);
 const resize=()=>document.documentElement.style.setProperty('--review-height',Math.ceil(bar.getBoundingClientRect().height)+'px');new ResizeObserver(resize).observe(bar);resize();
 function labelMock(){document.querySelector('.environment').textContent='LOCAL MOCK';document.querySelector('.mock-notice').textContent='LOCAL MOCK · Không đăng nhập thật, không kết nối DB hoặc host. Dữ liệu và quyền chỉ là mô phỏng.';document.getElementById('connection-label').textContent='Dữ liệu giả trong RAM';}
 addEventListener('ita:data',labelMock);labelMock();
 async function change(input){reset.disabled=role.disabled=mode.disabled=true;try{const response=await fetch('/review/control',{method:'POST',headers:{'Content-Type':'application/json','X-Local-Review':'mock-only'},body:JSON.stringify(input)});if(!response.ok)throw Error('Không đổi được mô phỏng.');if(input.reset){try{for(const key of Object.keys(sessionStorage))if(key.startsWith('ita.alert-reminder.'))sessionStorage.removeItem(key);}catch{/* Optional presentation storage. */}}location.reload();}catch(error){status.textContent=error.message;reset.disabled=role.disabled=mode.disabled=false;}}
 role.addEventListener('change',()=>change({role:role.value}));mode.addEventListener('change',()=>change({mode:mode.value}));reset.addEventListener('click',()=>change({reset:true}));
 fetch('/review/status').then(r=>r.json()).then(r=>{role.value=r.data.role;mode.value=r.data.mode;status.textContent='Đang giữ '+r.data.allocated_assets+' tài sản giả trong RAM. Kịch bản1triệu chỉ thay số tổng, không tạo1triệu bản ghi. Tắt server thì mất các thay đổi.';}).catch(()=>{status.textContent='Chưa đọc được trạng thái mô phỏng.';});
})();
