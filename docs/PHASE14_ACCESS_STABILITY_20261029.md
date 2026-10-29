# Ổn định form và quyền xem tài khoản — 29/10/2026

Rà source và cải thiện phần phân quyền theo chức năng để hoàn thiện yêu cầu hiện có. Không thay PHP, SQL, dữ liệu, config hoặc quyền được server cấp. Không DB/host/deploy.

- Người chỉ có `access.read` và scope tương ứng có thể mở và đọc danh sách tài khoản theo contract server, nhưng không thấy nút Phân quyền/Thêm tài khoản nếu thiếu `access.manage`.
- Hủy rồi mở lại form tạo làm sạch lỗi validation của trường. Phản hồi quyền của tài khoản trước không ghi đè form tài khoản vừa chọn hoặc form tạo mới. Khi đang lưu không mở form tài khoản khác; thông báo kết quả dùng tên đã chọn lúc gửi.
- Review độc lập chỉ source phát hiện các tình huống stale validation/response và cập nhật sai tên thông báo; đã sửa. Không kết luận đây là bug đã tái hiện trên host.

## Kiểm thực tế

`tests/ita_access_race_browser.js`: **6 checks** chặn phản hồi trễ, không mở form khác lúc lưu, làm sạch validity; Node RAM/mock, không chứng minh transaction hay concurrency DB. `tests/ita_phase14_browser.js`: **22 checks** về quyền từng chức năng, gồm chỉ xem danh sách và không có nút sửa/tạo. `tests/ita_phase14_user_create_browser.js`: **28 checks**; API mock. `php tests/ita_phase14_unit.php`: **121 checks** fake transport. Smarty render, node syntax và `git diff --check` đạt. Không test HTTP/DB thật hoặc PHP 8.3.

Preview local: `start-local-review.cmd`/http://127.0.0.1:18767/, dữ liệu giả. Chưa đưa sửa mới vào gói deploy; giữ source, dữ liệu và config host. Các kiểm host/DB vẫn chờ user thực hiện trong phạm vi đã thống nhất.
