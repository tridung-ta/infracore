# Tạo bảng ITA trên hosting

Đích: `dung_infracore`, MariaDB 10.6.28 theo file schema user cung cấp. User tự thực hiện bằng phpMyAdmin. Agent chỉ tạo file; chưa thực thi trên hosting.

## Cách chạy

1. Export bản sao lưu **cấu trúc và dữ liệu** của `dung_infracore`, lưu ngoài repo. File cấu trúc đã gửi không chứa bản ghi để khôi phục dữ liệu.
2. Chọn DB `dung_infracore` trong phpMyAdmin. Chạy `phase01_preflight.sql` ở tab SQL. Kết quả phải đúng DB và chưa có bảng `ita_`. Nếu có bảng `ita_`, dừng và báo lại.
3. Mở `phase01_create_ita_tables.sql`, copy toàn bộ vào tab SQL và chạy một lần; hoặc dùng tab Import để chọn chính file này. Không chạy thêm các file migration lẻ nếu đã chạy file tổng.
4. Nếu báo lỗi, dừng và gửi thông báo lỗi/tên bảng đã tạo; không xóa bảng, không bỏ qua lỗi, không chạy lại toàn bộ file.
5. Nếu thành công, chạy `phase01_verify.sql`. Gửi ảnh hoặc kết quả danh sách 12 bảng, bộ đếm `12 / 12 / 12` và danh sách 11 khóa ngoại.

## Phạm vi

- 12 bảng mới `ita_`, InnoDB, utf8mb4_unicode_ci. Không tạo user, không thêm tài sản mẫu, không sửa/xóa dữ liệu hoặc bảng `dc_`.
- Không có CREATE DATABASE, ALTER bảng cũ, DROP, DELETE, TRUNCATE, trigger, procedure hoặc thay cấu hình global.
- File dùng `CREATE TABLE` thường, chủ động báo lỗi nếu tên bảng đã tồn tại; không dùng IF NOT EXISTS để che schema không khớp.
- `store_id` bảo vệ quan hệ nội bộ tenant qua khóa ngoại ghép; tham chiếu user dùng INT có dấu và kiểm ở PHP vì `dc_users` là MyISAM.
- Mã tài sản unique theo `(store_id, asset_code)`, không phân biệt hoa/thường theo collation. Mã đã xóa mềm vẫn được giữ để bảo toàn lịch sử.
- Thời gian DATETIME mới lưu theo quy ước UTC; ứng dụng sẽ chuyển sang Asia/Ho_Chi_Minh khi hiển thị. `SET time_zone` ở đây chỉ tác động phiên import hiện tại.
- Unique generated key chặn hai bàn giao đang hiệu lực cho cùng thiết bị, hoặc cấp cùng giấy phép hai lần cho cùng đích. Loại đích giấy phép, quyền user, thiết bị retired, giới hạn số chỗ và chuyển trạng thái tài sản vẫn phải được backend kiểm tra bằng transaction/lock phù hợp.
- Bảng categories được tạo rỗng: 6 loại thiết bị, cấu hình mặc định và dữ liệu mẫu sẽ là bước riêng sau xác nhận tạo bảng thành công và xác định store_id.
- Hoàn tác: xem `../migrations/ROLLBACK.md`. CREATE TABLE không có rollback cả lô.

## Kiểm chứng trước bàn giao

Đã chạy tạo đủ 12 bảng trên MySQL 8.4.11 local cô lập, 30 kiểm tra tích hợp ràng buộc đạt; dữ liệu test được rollback. Đã đối chiếu cú pháp CHECK, foreign key và generated columns với tài liệu MariaDB. Chưa thực thi trên MariaDB 10.6.28 hosting và chưa coi kết quả local là xác nhận host.

Nguồn: https://mariadb.com/docs/server/reference/sql-statements/data-definition/constraint và https://mariadb.com/docs/server/reference/sql-statements/data-definition/create/generated-columns
