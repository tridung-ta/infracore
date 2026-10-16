# Tích hợp khung PHP Phase 1

Ngày 16/10/2026. User yêu cầu «tiếp tục BUILD» sau phạm vi BE-GATE #1: tiếp tục PHP/routing local và soạn SQL để user tự chạy host. Không deploy hoặc thực thi SQL host.

## Đã triển khai

- modules/admin/ita.module.php dùng Smarty 4.5.5 sẵn có để render layout/partial ITA. Không bootstrap config DB cũ, không session/auth, không truy vấn dữ liệu thật; trang có nhãn MOCK.
- .ita-enabled bật chế độ ITA; .ita-legacy-enabled bật lại đường bootstrap cũ khi ITA đang bật. PHP và Apache dùng cùng marker làm nguồn cấu hình cho ITA_ENABLED / ITA_LEGACY_UI_ENABLED; query parameter không đổi được cờ.
- includes/project_gate.inc.php dispatch ITA trước legacy bootstrap. Route canonical /admin.php?op=ita; GET giao diện cũ chuyển 303 kèm thông báo cố định; POST cũ bị từ chối 403. Path config, SQL, cron, module trực tiếp và source dev bị chặn. Worker cũ trả script tự gỡ.
- Giữ nguyên router.php và admin.php: cả hai đã gọi project_gate trước bootstrap; không cần thêm nhánh dispatcher thứ hai hoặc sửa login khi auth Phase 2 chưa làm.
- .htaccess thêm allowlist asset ITA và đưa request còn lại qua gate. Chưa chạy Apache local/hosting; cần verify hosting trước deploy thật.
- Khung FE có sidebar/topbar/breadcrumb/toast; KPI, bảng, badge, modal chi tiết và API xác nhận dùng chung ItaUi.confirm. Component xác nhận không tự gọi backend hoặc báo ghi dữ liệu thành công.
- Không include menu cũ. Bản gốc gate/router/.htaccess lưu local tại .source-backup/ita-phase01-routing trước chỉnh; không đưa backup vào Git.

## Kiểm chứng đã chạy

- php -l includes/ita_mode.inc.php, includes/project_gate.inc.php, modules/admin/ita.module.php, tests/ita_phase01_routing.php: đạt.
- node --check ita-shell.js, ita-components.js: đạt.
- PHP development server qua router.php tại 127.0.0.1:18768.
- php tests/ita_phase01_routing.php: 32 kiểm tra đạt, gồm flags filesystem (không flag/ITA/restoration/legacy flag lẻ), route qua entrypoint thật, Smarty render không còn include literal, header, chuyển hướng, chặn POST và file riêng, CSS/JSON, worker.
- Playwright CLI phiên infracore-php: verify-ui.js đạt trên /admin.php?op=ita; assert data-renderer=smarty. Tìm/lọc, phân trang, empty/reset, modal, menu desktop/mobile, cảnh báo, lỗi HTTP 503/retry, reduced-motion, modal xác nhận hủy/đồng ý và escaping chuỗi HTML đạt. Không có JS runtime error; HTTP 503 là trường hợp lỗi chủ động mô phỏng.
- Bounding box không tràn ngang ở 1366, 768, 390 px; select giữ mũi tên. Ảnh ba kích thước nằm trong .playwright-cli/ (không commit).
- Modal close event là bất đồng bộ: kịch bản chờ Promise resolve rồi mới kiểm boolean, giữ nguyên assertion hủy=false và đồng ý=true.

## Danh mục ban đầu cho host

User gửi kết quả dc_users: chỉ store_id=1 với 20 user. phase01_initial_catalog.sql thêm tối đa 6 loại thiết bị, 6 bộ phận khởi tạo và 6 cài đặt cho phạm vi đó. Chỉ bổ sung mã/key chưa có; không sửa user hoặc ghi đè bản ghi đã chỉnh. Không phải bộ seed 300–500 tài sản, chưa tạo ticket/giấy phép giả.

Đã chạy hai lần trên MySQL 8.4.11 local cô lập với dc_users fixture MyISAM: kết quả 6/6/6, giữ nguyên tên danh mục đã đổi và số user vẫn 1. Server test được shutdown; không sửa service MySQL84. Host chưa chạy file danh mục.

## Giới hạn cần giữ rõ

- Đây là integration PHP/Smarty + MOCK, chưa phải đăng nhập hoặc live DB dashboard. Không mở dữ liệu thật trước khi có RBAC.
- Login/logout/upload/ajax dùng chung chưa được mở trong chế độ ITA; chúng sẽ được allowlist theo endpoint sau auth Phase 2. Hiện redirect GET hoặc từ chối POST để tránh vào config/DB nguồn cũ.
- Restoration đã kiểm cờ và quyết định bypass; chưa thực thi toàn ứng dụng legacy với DB thật hoặc Apache. Không công bố F1.10 hoàn tất toàn bộ nghiệm thu.
- Chưa kiểm MariaDB seed trên host, bộ seed đầy đủ, migration rollback dữ liệu, engine/collation toàn bộ host, auth theo vai trò, EXPLAIN hoặc UAT.
- Phase 0 chưa có baseline đầy đủ nguồn legacy, main/develop chưa được xác minh để merge; không tag phase done, không push.