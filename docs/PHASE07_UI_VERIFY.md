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


## Bổ sung cùng ngày — Tìm và chọn thiết bị

- Trong Tạo phiếu mới, tìm theo mã/tên/seri, chọn kết quả để tự điền ID. Native details tự thu gọn sau chọn; ô ID vẫn dùng được nếu biết sẵn.
- Tái sử dụng GET `/admin.php?op=ita&act=data&q=...&page=...`; trả assets + pagination5dòng đã có scope tại server. Không PHP/SQL/API mới. Endpoint này còn tải summary; chưa benchmark DB, không phát sinh endpoint lookup riêng.
- Thiết bị retired có nút disabled và giữ disabled sau loading; guard/pending vẫn kiểm lại ở service, FE không cam kết thiết bị chưa có phiếu chờ.
- Empty và403 có hướng dẫn tìm lại; kết quả cũ bị xóa khi tìm/tải lỗi. Enter tại search chỉ tìm, không submit ticket. Nhập ID thủ công xóa nhãn lựa chọn cũ, không dùng localStorage.
- Browser API mock mở rộng:64checks đạt, bao gồm21checks mới so với43trước; ba vai trò, pager giữ query, escaping, retired, Enter, empty,403, chọnID, xóa nhãn và bounds1366/768/390. Ảnh `.playwright-cli/phase07-picker-{width}.png` đã xem desktop/mobile. Node syntax + Smarty thực + detector[] đạt; review độc lập không phát hiện lỗi.
- Host UAT bổ sung: mở Tạo phiếu mới → tìm UAT-IT-001 → chọn → kiểm ID1 và tên đúng; thử tìm không khớp; đối chiếu scope từng vai trò. Chưa cần tạo phiếu mới chỉ để xác nhận picker; test ghi theo checklist ticket còn mở.
- Gói `artifacts/ita-phase07/ita-ticket-picker-update.zip` gồm cả cải tiến giao diện trước đó (7file), không cần cài gói UI cũ trước. Cần routing60f4f49 trên host. Backup/upload/rollback giống hướng dẫn trên; không SQL, chưa deploy.
