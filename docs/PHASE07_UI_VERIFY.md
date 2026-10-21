# Cải tiến giao diện Bảo trì & hỗ trợ — 21/10/2026

## Phạm vi

Chỉ template, CSS và JavaScript trình bày. Giữ endpoint/payload, CSRF, version và kiểm quyền phía server. Không DB, SQL, cấu hình host, PHP hoặc package mới. Không merge Phase7 hoặc mở Phase8 dựa vào lần kiểm tra UI này.

- Danh sách/bộ lọc rõ ràng; badge tiếng Việt, tiền VND giữ độ chính xác DECIMAL.
- Lối vào tạo phiếu dễ tìm, thu gọn sau tạo thành công; nút đóng chi tiết giữ danh sách.
- Thông tin và thao tác chia cột desktop; mobile chuyển cấu trúc, không ẩn dữ liệu phiếu.
- Thông báo cố định: loading/success/error có icon, role và nút đóng; success tự ẩn6s nếu không có focus, lỗi không tự ẩn. Không tự scroll/focus cho thông báo.
- Lỗi có tải lại chỉ đọc, không tự retry POST. Ghi chưa rõ kết quả vẫn chặn gửi lại thao tác phiếu đến khi đọc lại.
- Lịch sử đổi sang giờ Việt Nam; gán người xử lý/chi phí có nội dung dễ hiểu thay JSON thô.
- Thống kê bảng, có đơn vị và năm đối chiếu; không thêm số liệu giả vào runtime.
- Motion tách ita-motion.css và js/motion/ita-tickets.js: 150–180ms transform/opacity; `window.UI_MOTION_ENABLED=false` trước khi nạp script tắt motion. Tôn trọng reduced-motion.

## Kết quả thật

- `node assets/ita/preview/build-preview.cjs`: đạt.
- `node --check assets/ita/js/ita-tickets.js`: đạt.
- `node --check assets/ita/js/motion/ita-tickets.js`: đạt.
- `php tests/ita_phase03_render.php`: Smarty thực render/escape đạt, không private config/DB.
- `playwright-cli -s=ita-phase06 run-code --filename=tests/ita_phase07_browser.js`: 43 API mock checks đạt. Ba vai trò, create/assign/transition/cost/ready, history, list, stats, retry GET-only, CSRF payload, uncertain save, XSS text, max DECIMAL, reduced-motion.
- 1366/768/390px: đo document scrollWidth và form bounding box đạt, ảnh `.playwright-cli/phase07-{width}.png`. Đã nhìn desktop/mobile, detector không báo lỗi.
- Review độc lập: phát hiện Number làm tròn chi phí lớn; sửa định dạng chuỗi và kiểm giá trị 9999999999999999.99.

## Giới hạn và kiểm host

Đây là browser API mock; không chứng minh DB transaction/locking, EXPLAIN hoặc phân quyền server. Bằng chứng host trước cải tiến: tạo, gán, đóng phiếu periodic, lịch +6tháng, xác nhận sẵn sàng và thống kê đúng. Các ca DB còn lại giữ chưa nghiệm thu.

Gói FE gồm7file runtime: ita-tokens.css, ita-tickets.css, ita-motion.css, ita-tickets.js, js/motion/ita-tickets.js, layout.tpl.html và partial tickets.tpl.html. Cần host có bản sửa routing60f4f49 đã kiểm trước đó.

User sao lưu7file tương ứng (file mới ghi nhận chưa có), giải nén gói rồi upload **nội dung runtime/** vào public_html bằng FileZilla; không upload MANIFEST/ZIP. Ctrl+F5. Không SQL. Kiểm tải danh sách, mở/đóng phiếu, bộ lọc, thông báo, form theo vai trò và thống kê. Thao tác ghi test dùng phiếu UAT đã chọn, tránh tạo trùng phiếu đang pending.

Rollback: khôi phục7file tương ứng từ backup; file mới có thể để lại không được layout cũ gọi. Giữ nguyên DB và config/keys, không restore đè dữ liệu mới. Agent không upload/deploy.
