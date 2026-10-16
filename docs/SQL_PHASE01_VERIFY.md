# Kiểm chứng SQL Phase 1

Ngày 16/10/2026. User yêu cầu agent gửi SQL để user tự chạy trên host rồi báo kết quả. Quyền này cho phép soạn migration tạo bảng F1.1–F1.4; không phê duyệt seed F1.5 hoặc routing/PHP F1.10.

## Kết quả đã chạy

- Server local cô lập: MySQL 8.4.11 tại 127.0.0.1:18767, datadir tạm riêng; không dùng hoặc thay đổi MySQL84 đang cài của user.
- 001–004 chạy xuôi tạo đủ 12 bảng, InnoDB/utf8mb4_unicode_ci; 11 foreign key đều nội bộ ita_.
- File tổng phase01_create_ita_tables.sql chạy thành công trong schema test độc lập thứ hai (chỉ thay tên DB đích trong bản test), tạo đủ 12 bảng.
- php -l database/tests/phase01_schema.php: đạt.
- php database/tests/phase01_schema.php: 30 kiểm tra tích hợp đạt. Kiểm mã tài sản trùng không phân biệt hoa thường, FK chặn chéo store, tiền âm, thanh lý thiếu lý do, bàn giao đang hiệu lực trùng, thu hồi/giao lại, cấp phát giấy phép trùng/chéo store/sai đích, lịch sử ticket sai chuyển trạng thái, JSON log không hợp lệ, snapshot chốt ngân sách thiếu và số dòng import sai.
- Dữ liệu test nằm trong transaction và rollback khi kết thúc. Server do test tạo đã được shutdown, không dừng service MySQL84 của user.
- File tổng có 12 CREATE TABLE mới, không có câu lệnh thay đổi bảng dc_, DML hoặc lệnh xóa. Lần kiểm tĩnh đầu báo nhầm ON DELETE RESTRICT là DELETE; đã sửa kiểm tra về đầu câu lệnh, không sửa schema để chiều kiểm tra.
- dung_infracore.sql vẫn bị Git ignore; chỉ whitelist bảy file SQL nguồn/host được tạo có chủ đích.

## Giới hạn và bàn giao

Host theo dump là MariaDB 10.6.28, PHP 8.3.33. Chưa thực thi trên MariaDB hosting; không gọi kết quả MySQL local là PASS trên host. Đã đối chiếu CHECK, generated columns và FK với tài liệu MariaDB chính thức. Không có DROP/rollback schema tự động; xem migrations/ROLLBACK.md. Chưa backup host, chưa seed, chưa PHP integration hoặc EXPLAIN trên dữ liệu thật.

Nguồn cú pháp: https://mariadb.com/docs/server/reference/sql-statements/data-definition/constraint ; https://mariadb.com/docs/server/reference/sql-statements/data-definition/create/generated-columns ; https://mariadb.com/docs/server/reference/sql-statements/transactions/sql-statements-that-cause-an-implicit-commit