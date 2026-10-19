# Checklist user kiểm thử Phase 3 trên host

Chỉ làm sau backup và upload đồng bộ gói Phase 3. Không dùng tài sản thật để thử xóa/thanh lý. Agent không chạy các bước này. Kết quả hiện tại: **chưa chạy**.

## 1. Tạo hồ sơ thử bằng Admin 111

- Vào Tài sản CNTT → Thêm tài sản. Mã riêng `TEST-ITA-20261019-01`, tên `Tài sản kiểm thử Phase 3`; chọn loại và bộ phận đã có; giá `0.00`; ngày mua 19/10/2026 và bảo hành 19/10/2027; lưu.
- Cần thấy thông báo đã lưu. Tìm mã vừa tạo → mở chi tiết → tab Thông tin. Kiểm đủ giá trị, giá 0 vẫn hiện. F5 rồi tìm lại để xác nhận DB thực đã lưu.
- Tạo mã giống hệt: cần báo trùng, form giữ dữ liệu. Không được xuất hiện tài sản thứ hai. Thử bỏ tên/mã hoặc giá âm: bị chặn, không có dòng mới.

## 2. Sửa và chống ghi đè

- Mở tài sản thử → Sửa thông tin → đổi vị trí thành `Phòng thử A` → Lưu. F5 và mở lại: cần thấy vị trí mới.
- Mở cùng hồ sơ trong hai tab A/B. Ở A đổi vị trí thành `Phòng thử B`, lưu. B giữ hồ sơ cũ rồi sửa seri/lưu: cần báo tài sản đã thay đổi và yêu cầu mở lại; không ghi đè vị trí B.
- Trong tab Lịch sử cần thấy tạo/sửa, actor 111, thời gian Việt Nam, dữ liệu trước/sau phù hợp. Admin mở Nhật ký thao tác kiểm lại.

## 3. Trạng thái và ghi chú

- Đổi in_use → maintenance, bắt buộc lý do `TEST bảo trì`; lưu rồi F5 xác nhận. Đổi maintenance → broken → maintenance → in_use, mỗi lần đều có lý do và log.
- Ghi chú thử riêng; F5 xác nhận. Thử broken → in_use trực tiếp: không có lựa chọn/không được phép; cần qua maintenance.
- Dùng hồ sơ thử riêng nếu muốn kiểm thanh lý: Admin đổi retired có lý do. Không cho chuyển ngược retired → in_use.

## 4. Ba vai trò và phạm vi

- Giữ 111 làm Admin. Dùng tài khoản phụ đã được cấp Quản lý, chỉ một bộ phận. Đăng nhập cửa sổ ẩn danh: tạo/sửa trong bộ phận được cấp, không có nút quản lý danh mục/xóa/thanh lý trực tiếp.
- Dùng Hỗ trợ phụ: không có nút thêm/sửa thông tin chung/xóa/thanh lý. Vẫn được đổi trạng thái hợp lệ và ghi chú tài sản trong phạm vi.
- Admin lấy ID một hồ sơ thử ngoài bộ phận của tài khoản phụ. Trong phiên tài khoản phụ, mở `/admin.php?op=ita&act=asset&id=ID_THU`: cần 403. Tài khoản thuộc store khác không được thấy dữ liệu store 1 (nếu có tài khoản như vậy để thử).
- Không chỉ dựa vào menu ẩn. Kiểm trực tiếp request trái quyền/thiếu CSRF nếu biết dùng DevTools; nếu chưa thử thì ghi chưa chạy.

## 5. Bộ lọc và danh mục

- Lọc từ khóa mã thử + loại + bộ phận + năm mua 2026 + khoảng bảo hành có 19/10/2027: cần thấy hồ sơ; đổi năm khác thì không thấy. Đặt lại: các bộ lọc sạch.
- Admin → Quản lý loại tài sản → thêm mã `TEST-TYPE-20261019`, tên `Loại kiểm thử`. Chọn loại đó cho một tài sản thử mới. Ngừng dùng loại vừa tạo.
- Mở sửa tài sản cũ: phải giữ được loại inactive và cho sửa vị trí không buộc đổi loại. Form tạo mới không được chọn loại inactive. Không thay/đóng các loại nghiệp vụ đang dùng thật.

## 6. Xóa mềm và kiểm tra lỗi

- Hồ sơ TEST chưa phân bổ → Xóa mềm → nhập lý do → Hủy xác nhận: hồ sơ còn nguyên. Làm lại và xác nhận: hồ sơ biến mất khỏi danh sách sau F5, log asset.soft_delete còn.
- Tạo lại cùng mã đã xóa mềm: bị chặn trùng mã. Không hard delete để vượt kiểm tra này.
- Nếu có hồ sơ thử đang phân bổ, xóa/thanh lý/chuyển bộ phận phải bị chặn và yêu cầu thu hồi. Không dùng tài sản thật để test.
- Kiểm desktop/tablet/mobile: form cuộn được, nút lưu dùng được, thông báo lỗi thấy rõ, tab lịch sử dùng bàn phím được.

Gửi báo cáo dạng `1 đạt / 2 lỗi ...` cùng ảnh thông báo. Không gửi password, cookie, token, auth_key hoặc dump dữ liệu người dùng.
