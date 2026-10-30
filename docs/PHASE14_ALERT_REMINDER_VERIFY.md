# Nhắc cảnh báo trong phiên — 28/10/2026

User chọn **“Bỏ nhắc mới trong phiên, giữ cảnh báo cần xử lý”**. Chỉ FE, không thêm read receipt/bảng/endpoint hoặc SQL ghi. Cảnh báo vẫn còn hiệu lực đến khi nguyên nhân được xử lý; mở trang không giải quyết bảo trì/giấy phép.

Trước đây badge8 chính là active_total, không phải unread. Đã tái hiện trên JS baseline1652264 bằng override trong browser context/API giả: kiểm “Successful view hides numeric reminder” đỏ, nhãn vẫn8cảnh báo hiệu lực. Không sửa/reset source cũ để tái hiện.

Module ita-notifications.js lưu fingerprint trình bày trong sessionStorage theo tài khoản và tập quyền/role. Sau khi danh sách cảnh báo và số tổng chuẩn được tải thành công, ẩn badge số/chấm nhắc; topbar ghi “Đã xem · N cảnh báo cần xử lý”, danh sách giữ nguyên. Lỗi tải danh sách không đánh dấu đã xem. Có fallback bộ nhớ nếu storage bị chặn. Chỉ một phiên/tab trình duyệt; không đồng bộ trạng thái đã đọc giữa thiết bị hoặc lưu DB.

Chuông dùng GET alerts không filter để lấy số chuẩn; không lấy số đang lọc bộ phận/severity thay số toàn phạm vi. Khi mở/làm mới trung tâm, đọc danh sách và snapshot chuông song song, chỉ đánh dấu sau cả hai thành công. Generation/identity ngăn response cũ đè snapshot mới. Không polling, không POST từ xem/đánh dấu. Snapshot thay đổi sẽ hiện nhắc lại. Nhấn chuông khi đã ở trung tâm sẽ tải lại danh sách thay chỉ hiện toast.

Fingerprint chỉ dùng tổng và các định danh/nội dung trình bày của trang canonical đầu; **không phải read receipt từng cảnh báo**, không phát hiện mọi thay đổi ở các trang sau khi tổng/trang đầu không đổi. Không lưu raw alert/key/cookie vào storage. Hash ngắn chỉ phục vụ trình bày, không dùng làm bảo mật. Tải lại cùng tab giữ trạng thái; đóng phiên/xóa storage có thể hiện nhắc lại.

Đã sửa race script dashboard nạp sau boot event: khởi tạo chuông cả khi context đã có sẵn. Chưa có snapshot hợp lệ thì không hiển thị0 như kết quả thật. Đã đổi cache version cả5 JS đang sửa trong đợt ảnh (shell/dashboard/tickets/assignments-live/form-guidance) để tránh host trộn JS cũ/mới; module mới có version riêng. Chưa upload các file này.

| Kiểm local thật | Kết quả | Giới hạn |
|---|---|---|
|playwright-cli -s=ita-admin-refine run-code --filename=tests/ita_alert_session_browser.js|17 kiểm đạt|8→xem→ẩn nhắc, lỗi không mark, filter giữ tổng, reload, đổi account, snapshot đổi, storage fallback, cache URLs, không POST, bounds1366/768/390|
|tests/ita_phase10_browser.js qua cùng CLI|70 đạt|Dashboard/report/bell/theme hồi quy API mock|
|tests/ita_phase14_browser.js qua cùng CLI|19 đạt|Effective permissions/không chuông nếu thiếu alert.read, API mock|
|Impeccable detect các target sửa; node --check; Smarty render|0 finding; đạt|Source/static/local, không đo header cuối host|
|Review agent|Đã sửa finding cache URL|Chỉ đọc source, không DB/host|

Đã xem ảnh mobile phase15-alert-390.png, đo bounds ba kích thước. Server Node static127.0.0.1:18766; API bị intercept bằng dữ liệu giả, không gọi host/DB. Không PHP/config/credential/DDL, không gói/upload/push/merge/tag7–10. Rollback source phải đồng bộ layout/module/dashboard/shell, giữ dữ liệu thật; session presentation state không phải audit nghiệp vụ.
