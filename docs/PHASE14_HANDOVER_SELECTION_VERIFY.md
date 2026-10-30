# Bàn giao chỉ tải hồ sơ khi chọn thiết bị — 28/10/2026

Ảnh5 là các GET data/categories/assignments/assignment-recipients: source cũ mở trang rồi lấy danh sách, tự chọn thiết bị đầu tiên và tải hồ sơ/người nhận. GET không phải xác nhận bàn giao hoặc INSERT, nhưng hiển thị “Đã tải hồ sơ” trước thao tác gây hiểu nhầm. Đã tái hiện bằng JS baseline1652264 trong browser override/API giả: assertion “No handover/recipient fetch before interaction admin_it” đỏ.

Đã sửa FE: khi mới vào trang chỉ hướng dẫn tìm thiết bị; chưa request assignments hoặc assignment-recipients. Bấm Tìm thiết bị lấy danh sách có placeholder, vẫn chưa mở hồ sơ đầu tiên. Chọn rõ thiết bị mới tải detail và, nếu được phép/giao được, danh sách người nhận. Tải lại hồ sơ giữ đúng ID đã chọn, không tự quay về thiết bị đầu. Đổi trang tìm kiếm lại chờ chọn; trạng thái record cũ được ẩn trước đọc.

**Vẫn có các GET khởi tạo chung** data/categories và cảnh báo theo quyền để hiển thị app/menu. Không hứa Network trống trước tương tác. Tìm/xem/đánh dấu nhắc phiên không gửi POST xác nhận bàn giao. Sau khi user chủ động lưu một thao tác, hệ thống vẫn đọc lại hồ sơ để xác nhận; không bỏ readback vì yêu cầu này. Guard server/scope/version/CSRF/ownership/PDF/audit giữ nguyên, không sửa PHP/DB.

| Kiểm local | Kết quả | Giới hạn |
|---|---|---|
|playwright-cli -s=ita-admin-refine run-code --filename=tests/ita_phase05_browser.js|35 kiểm đạt|Ba vai trò, chưa tương tác không request detail/recipient; tìm vẫn chờ chọn; assign/return/ack/version/CSRF/error/bounds1366/768/390; API mock|
|Cùng CLI tests/ita_phase05_pdf_browser.js|18 đạt|PDF theo ID lịch sử/current, denied/lỗi/type/role/unavailable, API bytes giả|
|node --check; php tests/ita_phase03_render.php; git diff --check|Đạt|Syntax/Smarty; không DB/host|

Test cũ chờ auto-select đã đổi sang bấm Tìm rồi chọn ID7, thêm assertion không request trước hai bước đó; không bỏ assertion quyền/version/CSRF. Không nối DB, không đọc private config hoặc gọi host. PHP local8.5.10 chỉ render; chưa8.3/FPM/HTTP+DB thật.

Tổng hợp5ảnh: nhắc phiên theo PHASE14_ALERT_REMINDER_VERIFY.md (commit6147733); năm/chi phí/ngày theo PHASE14_INPUT_FEEDBACK_VERIFY.md (commit53630ee); chọn hồ sơ theo commit chứa tài liệu này. Chưa gói/upload/push/merge/tag7–10. Host không tự nhận thay đổi local; giữ backup source và dữ liệu khi bàn giao sau được yêu cầu.
