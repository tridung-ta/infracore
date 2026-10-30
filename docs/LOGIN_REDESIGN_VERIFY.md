# Nâng cấp trang đăng nhập — 27/10/2026

User yêu cầu trang đăng nhập bớt đơn giản và đồng bộ hai phong cách mới. Chỉ sửa template/CSS và bộ tạo preview/test; không đổi ita-login.js, PHP, PmAuth/ItaAuthService, session, cookie, CSRF, limiter hoặc trường/method/action của form thật. Không DB/host/đóng gói/upload/push/merge/tag7–10.

## Giao diện

Desktop chia hai vùng: thương hiệu, giới thiệu và sơ đồ SVG minh họa bên trái; form bên phải. Command giữ nền tối/cyan và sơ đồ các nhóm công việc; Focus sáng, ít trang trí, thay sơ đồ bằng nội dung tập trung. Đây là minh họa chức năng, không phải số liệu hoặc kết nối thật. Không ảnh tải ngoài, font/dependency mới, nút tạo tài khoản/reset password hoặc cơ chế đăng nhập mới.

Mobile giữ thương hiệu/form/nút chuyển chế độ, ẩn phần minh họa để ưu tiên thao tác. Nút chọn mode vẫn có ít nhất44px; form/password/hiện-ẩn giữ label, autocomplete và thứ tự thao tác. Lỗi từ server vẫn qua Smarty escape; token hidden được escape như trước.

Preview sinh từ template thật tại **http://127.0.0.1:18766/assets/ita/mock/login-preview.html**. Có nhãn MOCK và submit capture chặn trước handler login UI, không gửi tài khoản và không để nút bị kẹt disabled. Kết quả preview không chứng minh đăng nhập thật. Không nhập mật khẩu thật vào trang này.

## Kiểm thật local

| Lệnh/test | Kết quả | Phạm vi |
|---|---|---|
| playwright-cli -s=ita-theme run-code --filename=tests/ita_theme_login_browser.js |42checks đạt|Hai theme; login và shell1366/768/390, bounds input, không POST, giữ nội dung, show/hide password, persist, WebGL rebuild và giảm chuyển động|
| Cùng lệnh, ita_phase12_login_motion_browser.js |9checks đạt|Motion login bật/giảm/cancel, show password, không auth request|
| php tests/ita_login_render.php |14checks đạt|Smarty thật với lỗi/token giả; escaping, fields/action/method, không config/DB|
| php tests/ita_phase03_render.php |Đạt|Hồi quy template shell/assets/category, dữ liệu giả|
| php -l tests/ita_login_render.php; node --check JS sửa/mới; git diff --check |Đạt|Syntax/whitespace|

PHP local8.5.10; không8.3/FPM/DB. Đã xem ảnh login Command/Focus1366 và390, đo cả768. Không biến mock thành UAT xác thực.

Impeccable detector có6warning:2về Inter và2về palette chữ/nền là hệ thiết kế đã ghim, giữ để đồng bộ dự án;1về chữ phụ thương hiệu9px và1về tracking đã sửa scope login thành11px/.05em. Không nói detector0finding. Kiểm contrast trên shell/form/select/error/dialog của hai theme có12checks riêng tại UI_THEME_MODES_VERIFY.md; không tuyên bố mọi điểm ảnh đã kiểm.

Review độc lập không finding về form contract, escaping, preview gửi credential hoặc theme. Review không tự chạy DB/host. Chưa triển khai; trước bàn giao tương lai phải đồng bộ template/CSS/core/theme/appearance từ source đã chốt, backup tệp thay; rollback source giữ dữ liệu và credential mới. MOCK/preview/tests không vào gói deploy.
