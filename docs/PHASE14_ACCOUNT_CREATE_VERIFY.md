# Phase14 — Admin tạo tài khoản và gán quyền, kiểm local28/10/2026

User duyệt source theo BE_GATE_14_ACCOUNT_CREATE. Đã build PHP/Smarty/JS; không đọc private config, kết nối DB/host, tạo tài khoản thật, bật cờ hoặc đóng gói/upload. Sửa source không tự xác nhận quyền INSERT/UPDATE native trên hosting.

## Hành vi

Trong mode quyền chức năng, người có access.manage +scope.store mở Tài khoản & phân quyền →Thêm tài khoản. Nhập username/họ tên/email/mật khẩu/xác nhận; chọn từng chức năng và bộ phận. Không chọn sẵn quyền; không thêm role cho tổ hợp mới. Confirm Hủy không POST, đồng ý POST user-create với CSRF. Tài khoản mới mang metadata support_technician nhưng effective permissions đúng lựa chọn; native type staff1, không native Admin.

Quyền không được vượt người cấp, phải đủ dependencies và có phạm vi dữ liệu. API không lấy store/native type/status/ID từ client. Server kiểm actor lại dưới khóa và ngay trước native activation. Unknown native required column fail-closed trước INSERT, không sửa schema. Schema/default/grants thật chưa kiểm.

Native mới status0, hash hiện đại, placeholder ngẫu nhiên ở password (không MD5 mật khẩu người dùng); map/audit transaction ITA rồi activate và commit. Lỗi cố disable ID vừa tạo trước rollback; không DELETE hoặc sửa credential cũ. Compensation thất bại/commit không rõ vẫn có rủi ro native active; không nhận là rollback nguyên tử hoặc race MyISAM đã đạt. Writer legacy không giữ lock ITA vẫn có thể tranh identity. Xem gate về cửa sổ activation trước commit.

Mật khẩu không vào DTO/audit, không lưu browser storage, input được xóa sau request hoặc Hủy/đổi trang.422/409 cho chỉnh dữ liệu; transport không rõ/503 khóa nút tạo lại, hướng dẫn kiểm danh sách/ID trước. Không tự retry POST.401 chuyển về login hiện có. Credential chỉ xuất hiện trong body POST cần thiết qua HTTPS khi dùng thật; bản local chỉ dùng password giả.

## Lệnh/kết quả thật

| Loại | Lệnh | Kết quả / giới hạn |
|---|---|---|
|Service PHP thật/fake transport|php tests/ita_phase14_user_create.php|57checks đạt: hash/DTO/audit, bind, native type, validate/byte72, subset, trùng, scope/stale, schema lạ, audit/activate/commit faults, revoke sau audit, compensation và GET/CSRF thật. Không DB.|
|Browser local|playwright-cli -s=ita-local-review run-code --filename=tests/ita_phase14_user_create_browser.js|23checks đạt: form/không role4/không defaultgrants, mismatch/cancel, tạo/readback/quyền/scope, escape, sửa quyền cũ,409,503,403, bounds1366/768/390. Node RAM mock; riêng503 được route fixture cố ý mô phỏng, không phải native fault thật.|
|Hồi quy quyền|php tests/ita_phase14_unit.php|121checks đạt, fake transport.|
|S26 auth|php tests/ita_s26_auth.php|686checks đạt, counter thật qua fake transport; không DB/timing. |
|Hồi quy auth|php tests/ita_preupload_auth.php|29checks đạt, auth/session/limiter thật qua fake transport/session giả; không login host.|
|Gate/routing|php tests/ita_write_routes.php; python tests/ita_phase11_guard_http.py|13 +109 đạt; local PHP HTTP fixture, không Apache/host/DB.|
|Smarty|php tests/ita_phase03_render.php|Đạt. Ban đầu regex {2,49} trong attribute bị Smarty parse; đã sửa HTML entities rồi render thật đạt, không chỉ preview HTML.|
|Bản review cũ|node tests/ita_manual_review_model.cjs; playwright-cli -s=ita-local-review run-code --filename=tests/ita_manual_review_browser.js|21contract +38browser đạt; không DB.|
|UI QA|Impeccable detect access.tpl/admin.js/permissions.js|0finding; đã xem ảnh1366 và390; CSS/tokens hiện có giữ.|
|Review độc lập|Agent đọc service/input/route/audit|Đã sửa min ký tự HTML lệch contract byte Unicode, test PHP/browser mới đạt; giữ giới hạn MyISAM/legacy writer/compensation.|

PHP kiểm8.5.10, chưa8.3/FPM. Browser lần đầu phải mở nhóm quyền thu gọn trước tick; sửa test thao tác theo UI, không nới assertion. Không chạy SQL/fixture/reset DB. Không coi native schema giả trong test là cấu trúc host đã xác minh.

## User kiểm ngay trên local

1. Mở http://127.0.0.1:18767/, Ctrl+F5; thanh LOCAL MOCK chọn Admin và kịch bản bình thường.
2. Chọn Tài khoản & phân quyền →Thêm tài khoản.
3. Điền username `mock-new-user`, họ tên `MOCK Người mới`, email `mock-new-user@example.invalid`; mật khẩu giả `MOCK-password-123` hai lần.
4. Mở nhóm Tài sản, tick Xem tài sản +Thêm tài sản; tick bộ phận Văn phòng — MOCK.
5. Bấm Tạo tài khoản & cấp quyền →Hủy trước, rồi bấm lại →đồng ý. Danh sách có user mới; mở Phân quyền để đối chiếu2quyền/1bộphận.
6. Thử trùng username/email, password không khớp, bỏ scope, chọn quyền thiếu dependency (server PHP kiểm; mock không thay kiểm policy thật). Chuyển Quản lý/Hỗ trợ: không có nút tạo và mock POST bị403.

Thay đổi mô phỏng chỉ ở RAM và mất khi reset/tắt server; không có đăng nhập thật bằng tài khoản vừa tạo trong bản Node. Local review không thể chứng minh native login/persistence/SQL isolation. Preview/mock/launcher/tests phải loại deploy. Chưa tạo gói hoặc triển khai.
