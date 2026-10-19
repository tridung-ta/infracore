# Nhật ký thao tác hosting

## 19/10/2026 — Phase 5 chỉ build local

BE-GATE #05 đã duyệt, code phân bổ/thu hồi/acknowledge/history/maintenance badge đã nối; không migration mới, không cài PDF dependency, không DB/host/deploy. Source còn working tree, chưa có release/manifest Phase5. Không upload toàn repo hoặc dùng gói Phase3 cũ. PHASE05_VERIFY.md ghi kết quả pure/scripted/browser mock; PHASE05_HOST_TEST.md ghi UAT còn lại. Không tạo PDF giả hoặc công bố phase-done.

## 19/10/2026 — Phase 4 chỉ build/verify local

User đã duyệt BE-GATE #04. Code F4.1–F4.5 có trong working tree; không tạo DB, không chạy SQL host, không upload hoặc tạo gói phát hành mới. Chưa có manifest/release cho Phase 4. Giữ private config host riêng, không copy toàn repo. `PHASE04_VERIFY.md` ghi bằng chứng local; `PHASE04_HOST_TEST.md` là checklist khi user sẵn sàng UAT. Không dùng gói Phase 3 cũ bên dưới cho thay đổi mới. DB transaction/collation/concurrency/performance chưa nghiệm thu.

## 19/10/2026 — Bàn giao Phase 3, chưa deploy

**Đính chính sau chỉ đạo user:** chưa upload production. Snapshot `b965bd30a9b4` dưới đây là bản cũ, không còn gồm các sửa local cuối (status required/category failure). Không dùng gói này để deploy; chờ user chủ động bắt đầu bàn giao sau. Phạm vi local đã kiểm 33 pure/scripted +30 browser API mock, không DB.

- User duyệt build và không tạo DB test. Agent không truy cập DB host hoặc thực hiện deploy.
- Snapshot delta: `artifacts/ita-phase03/infracore-phase03-b965bd30a9b4.zip`, 14 runtime files; archive SHA-256 `f00ab1f317b38671d8a686b4ceceacb43e3d8e0d90423cedfd36df1915d58248`. Bên trong có manifest SHA-256 từng file, đã đọc lại zip và đối chiếu hash.
- Snapshot từ working tree đã review, chưa phải release/tag phase-done; runtime còn chờ UAT SQL/read-back thực. Không thay cấu hình private hoặc auth_key; package không có config, dump, tests, session hay seed.
- Gói chỉ cập nhật host đang chạy Phase 2. User backup DB/source, giải nén trên máy và upload **nội dung runtime/** vào `/domains/infracore.dung.derasoft.com/public_html`, giữ đường dẫn, không tạo thêm thư mục runtime trên host. Không upload docs/manifest vào web root.
- Không cần migration mới hoặc import lại Phase 1/2. Hướng dẫn PHASE03_VERIFY.md và checklist PHASE03_HOST_TEST.md. Nếu lỗi, dừng ghi và khôi phục runtime từ backup, giữ dữ liệu mới; không DROP/DELETE/import dump làm lối tắt.

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
