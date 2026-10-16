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
## Xác nhận từ user sau import SQL host

Ngày 16/10/2026: user báo «đã thành công» sau hướng dẫn chạy phase01_verify.sql. Ghi nhận user xác nhận tạo bảng trên dung_infracore/MariaDB hosting thành công; agent không kết nối host và chưa nhận ảnh bộ đếm 12/12/12 hoặc danh sách đủ 11 khóa ngoại sau lần import cuối. Không suy diễn xác nhận này thành quyền seed, sửa PHP/routing hoặc deploy. Migration F1.1–F1.4 đã bàn giao và được user xác nhận chạy; ứng dụng chưa tích hợp DB.
## Đính chính xác nhận hosting

Ảnh user gửi tiếp theo cho thấy selected_database=dung_infracore nhưng truy vấn metadata dùng DATABASE() trả 0/NULL/NULL, không thấy FK. Chưa xác nhận tạo đủ 12 bảng trên host; kết luận thành công trước đó chỉ dựa trên lời báo của user và được thay bằng «chờ chẩn đoán». Trước đó import có thông báo #1050 ita_departments already exists. Chưa rõ nguyên nhân hai quan sát khác nhau; không mặc định bảng bị mất hoặc migration thất bại. Đã thay bộ lọc metadata trong preflight/verify bằng tên schema cố định để giảm phụ thuộc ngữ cảnh truy vấn. Cần SHOW TABLES và SHOW CREATE TABLE với schema chỉ định rõ. Không chạy lại CREATE hoặc xóa bảng khi chưa đối chiếu.
## Bằng chứng SHOW TABLES trên host

User gửi ảnh SHOW TABLES FROM dung_infracore LIKE 'ita%': liệt kê đủ 12 tên bảng đúng migration. SHOW CREATE TABLE dung_infracore.ita_departments trả về cấu trúc nhưng ảnh bị rút gọn. Đã xác nhận tồn tại đủ bảng; không chạy lại CREATE. Chưa xác nhận toàn bộ cột/engine/collation/11 FK qua ảnh này. Nguyên nhân truy vấn information_schema trước đó dùng DATABASE() trả 0 chưa xác minh; không kết luận bảng chưa được tạo. File verify đã dùng tên schema cố định.
## Xác nhận số lượng khóa ngoại trên host

Ảnh mới từ user cho thấy truy vấn information_schema.TABLE_CONSTRAINTS với CONSTRAINT_SCHEMA='dung_infracore' và loại FOREIGN KEY trả tổng 11 dòng. Kết hợp SHOW TABLES trước đó: đã xác nhận đủ 12 tên bảng và 11 khóa ngoại trên MariaDB host. Chưa có ảnh bộ đếm engine/collation 12/12/12 hoặc toàn bộ định nghĩa cột. Không yêu cầu chạy lại CREATE. Quyền PHP/routing và seed vẫn chưa được user phê duyệt.
## File kết quả ita_settings.sql từ user

Đã đọc tests/ita_settings.sql, không thực thi: có 6 cặp setting_key/setting_value khớp danh mục khởi tạo (budget_contingency_percent=10, currency=VND, display_timezone=Asia/Ho_Chi_Minh, license_critical_days=7, license_warning_days=30, warranty_warning_days=30). INSERT chỉ xuất setting_key và setting_value, không có store_id trong khi CREATE định nghĩa store_id NOT NULL không default; file này không đủ để xác nhận scope store_id=1 hoặc dùng như bản backup phục hồi nguyên bảng. DDL xuất cũng không thể hiện đầy đủ engine/collation/CHECK. Không import lại file. Chưa nhận kết quả categories/departments sau chạy catalog; cần SELECT đếm ba nhóm theo store_id=1.

Xác nhận tiếp theo: ảnh SELECT có scope store_id=1 trả categories=6, departments=6, settings=6. Đã ghi nhận host hoàn tất danh mục khởi tạo; không cần chạy lại INSERT.
