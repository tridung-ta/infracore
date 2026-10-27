# Hai phong cách Command / Focus — kiểm local 27/10/2026

Theo user: bật/tắt phải đổi diện mạo rõ ràng, không chỉ đổi nhãn nút. Chỉ FE/template/JavaScript/CSS; không PHP, DB, auth, quyền, cấu hình host hoặc package mới. Không đóng gói/upload/push/merge/tag7–10.

## Hành vi

- **Bật · Command:** nền tối Command Center hiện có, nhấn cyan; chuyển động tuân UI_MOTION_ENABLED và prefers-reduced-motion.
- **Tắt · Focus:** nền sáng, sidebar và bề mặt trắng, chữ xanh đậm, form/bảng/selection/dialog và thông báo theo bộ semantic tokens sáng; không chạy hiệu ứng tự động. Không thay nội dung/chức năng hoặc quyền.
- Giữ preference `ita.motion` hiện có, không thêm định danh hoặc dữ liệu tài khoản. `ita-appearance.js` external/self áp dụng preference trước CSS để giảm chớp sai theme khi mở lại. Khi storage không dùng được, fallback Command và lựa chọn hiện tại vẫn dùng trong trang.
- Theme dựa trên lựa chọn của user, **không** dựa vào document.hidden hoặc OS giảm chuyển động. Nút vẫn đổi phong cách khi reduced-motion hoặc cấu hình motion=false, nhưng không vượt chặn animation. Đây là thay đổi contract FE được chủ động áp dụng theo yêu cầu mới; các test cũ đổi assertion “nút bị khóa” thành “nút đổi theme được, actual animation vẫn tắt”. Không nới assertion bảo mật.
- Chuyển theme không reload trang, không reset form/bộ lọc, không gọi API hoặc POST. ECharts đổi màu trên instance hiện tại, giữ series/số liệu/bảng; Three.js dispose/dựng lại scene theo palette mới, giữ lựa chọn thiết bị. Góc xoay 3D trở lại góc dựng ban đầu khi đổi theme; không hứa giữ camera pose.

## Lệnh/kết quả thật

Static server workspace: `node assets/ita/preview/serve-preview.cjs`, 127.0.0.1:18766; process node đã đối chiếu command line. Lần thử mở server thứ hai gặp EADDRINUSE, dùng lại server đúng, không kill process khác. Browser session `ita-theme`.

| Lệnh | Kết quả | Giới hạn |
|---|---|---|
| playwright-cli -s=ita-theme run-code --filename=tests/ita_theme_contrast_browser.js |12 checks đạt|Computed contrast body/input/select/error/dialog >=4,5 ở hai theme, mũi tên select giữ; không đo mọi text trong repo|
| Cùng lệnh, ita_phase12_motion_browser.js |21 checks đạt|Persist/cancel, OS/config không animation, bounds1366/768/390|
| Cùng lệnh, ita_phase12_screens_browser.js |22 checks đạt|Feedback DOM trên các màn hiện có, disable/cancel|
| Cùng lệnh, ita_ui_refinements_browser.js |20 checks đạt|Hình thiết bị, form giả, responsive, reduced-motion theo contract mới|
| Cùng lệnh, ita_phase10_browser.js |70 checks đạt|API mock ba vai trò; thêm6 assertion theme đổi màu không request/không mất4series;64 assertion cũ giữ nguyên|
| node --check JS sửa/mới; git diff --check |Đạt|Cú pháp và whitespace|

Đã xem ảnh shell Focus1366 và đo scrollWidth/bounds trong browser. Palette khai báo trong ita-tokens.css; không đổi formula, cap, CSRF/version/audit hoặc logic role. Review độc lập không có finding cần sửa; review không tự chạy DB/host.

Chưa layout PHP thật/DB/host/PHP8.3. Kết quả static/API mock không là nghiệm thu host. User xem local tại http://127.0.0.1:18766/?devices=1#network; chuyển nút ở sidebar. Bản upload host hiện tại chưa có thay đổi này.
