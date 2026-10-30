/* Page-load feedback only. No HTTP routing, auth changes or automatic retries. */
(() => {
 'use strict';
 const panel=document.getElementById('load-error');
 if(!panel)return;
 const states={
  0:['Không kết nối được hệ thống','Kiểm tra kết nối rồi thử tải lại.','Chưa có dữ liệu mới. Không tự gửi lại thao tác lưu.',true],
  400:['Yêu cầu chưa hợp lệ','Hãy mở lại trang và kiểm tra thông tin đã nhập.','Nếu lỗi lặp lại, gửi thời điểm và thao tác cho quản trị viên.',true],
  401:['Phiên đăng nhập đã hết hạn','Đăng nhập lại để tiếp tục.','Kiểm tra dữ liệu hiện tại trước khi thao tác lại.',false],
  403:['Bạn chưa có quyền truy cập','Tài khoản không được phép xem dữ liệu này.','Liên hệ Admin IT để kiểm tra vai trò và bộ phận phụ trách.',false],
  404:['Không tìm thấy nội dung','Hồ sơ hoặc trang này không còn khả dụng trong đường dẫn hiện tại.','Về Tổng quan để mở lại từ danh sách được phép.',false],
  409:['Dữ liệu đã thay đổi','Tải lại để đối chiếu thông tin mới nhất.','Không ghi đè hoặc tự gửi lại thao tác lưu theo dữ liệu cũ.',true],
  422:['Cần kiểm tra dữ liệu hoặc bộ lọc','Thông tin chưa hợp lệ hoặc phạm vi vượt giới hạn.','Mở lại trang, sửa thông tin hoặc thu hẹp bộ lọc trước khi thao tác.',false],
  429:['Thao tác quá nhanh','Hãy chờ trước khi thử lại.','Không bấm liên tục; ứng dụng không tự gửi lại yêu cầu.',false],
  500:['Chưa thể hoàn tất yêu cầu','Hệ thống gặp lỗi khi tải nội dung.','Thử tải lại thủ công. Nếu còn lỗi, liên hệ quản trị viên.',true],
  503:['Hệ thống chưa sẵn sàng','Chưa thể tải dữ liệu lúc này.','Thử lại sau; nếu lặp lại, quản trị viên cần kiểm cấu hình dịch vụ.',true]
 };
 function show({status=0,message='',focus=true}={}){
  const code=Number(status),state=states[code]||states[500];
  panel.dataset.status=String(Object.hasOwn(states,code)?code:500);
  document.getElementById('load-error-title').textContent=state[0];
  document.getElementById('load-error-message').textContent=typeof message==='string'&&message.trim()?message.slice(0,400):state[1];
  document.getElementById('load-error-help').textContent=state[2];
  document.getElementById('retry-button').hidden=!state[3];
  const home=document.getElementById('load-error-home'),live=document.body.dataset.mode==='live';
  home.textContent=code===401?'Đăng nhập lại':'Về Tổng quan';
  home.href=live?(code===401?'/admin.php?op=ita&act=login':'/admin.php?op=ita'):'/';
  panel.hidden=false;
  if(focus){panel.focus({preventScroll:true});panel.scrollIntoView({block:'nearest',behavior:'instant'});}
 }
 window.ItaFeedback=Object.freeze({show});
})();
