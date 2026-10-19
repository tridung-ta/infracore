# Audit nền Derasoft

## Bổ sung đối chiếu ngày 19/10/2026

Các đoạn 16/10 dưới đây là snapshot audit ban đầu, không phải trạng thái Git/runtime hiện hành. Hiện có lịch sử commit, branch feature/ita-phase03-assets, migrations 001–005 và auth ITA đã chạy trên host theo xác nhận user. Không suy từ đó rằng toàn bộ chức năng đã nghiệm thu.

Chênh lệch với Word được dùng trong triển khai đã duyệt: thêm store_id xuyên quan hệ, dùng role ITA riêng thay kế thừa PM, không FK user MyISAM; JSON ITA đi qua protected dispatcher admin.php?op=ita&act=... thay mở ajax legacy. Reuse PmAuth/PmDb, giữ session ITA riêng. Lý do và phạm vi ở BE_GATE_02/03 và DECISIONS.

ERD/PERF_NOTES đã bổ sung từ schema/source. Kiểm Phase 3 theo chỉ đạo user chỉ pure/scripted/browser mock/Smarty, không DB. Chưa nghiệm thu benchmark/EXPLAIN thực, phân trang tất cả lookup/history hoặc luồng duyệt thanh lý. Chưa build Phase 4. Không dùng production để phát triển hoặc tự chạy SQL.

Ngày khảo sát: 16/10/2026. Đây là bằng chứng từ source và runtime local, không phải nghiệm thu sản phẩm.

## Git và an toàn

- main chưa có commit, chưa có HEAD, chưa có nhánh develop hoặc tag backup.
- Remote origin có sẵn; không đổi remote, không push.
- Danh tính Git kế thừa của dự án thư viện; user yêu cầu đổi, chưa cung cấp tên/email thay thế. Không commit dưới danh tính đó.
- Tất cả source hiện chưa tracked. Không gom toàn bộ bằng git add . vì cần kiểm tra dữ liệu nhạy cảm trước baseline.
- .gitignore đã loại config, constant, mail config, license, dump SQL, backup và cache. Đây chưa phải chứng nhận toàn bộ source không chứa secret.
- Không sửa PHP, cấu hình, dữ liệu hoặc xóa source trong đợt audit này.

## Runtime và kiến trúc

- php -v: PHP 8.5.10 CLI NTS. Có mysqli, pdo_mysql, openssl, mbstring, zip, dom. Không thấy gd trong php -m.
- DeraCMS 4.0 theo header admin.php. Model ở classes/dao, controller ở modules, template ở templates.
- classes/template/smarty.class.php:110: Smarty 4.5.5.
- Bootstrap CSS 5.3.8 có sẵn tại css/vendor/bootstrap-5.3.8; InterVariable.woff2 tại assets/fonts/inter.
- classes/PhpSpreadSheet có source thư viện; vendor/autoload.php không tồn tại. Chưa chứng minh export Excel/PDF chạy được. Chưa xác định bộ PDF được cài đầy đủ.
- Chưa tìm thấy ECharts/GSAP/Three.js qua tên file trong assets/js/css. Không tải package trong Phase 0.

## Entry point và route

- admin.php nạp project_gate.inc.php trước Boot/config/DB; sau bootstrap đọc op/act/mod và dispatch modules/admin/<op>.module.php.
- index.php, ajax.php, pm_ajax.php và cron cũng có cơ chế cô lập nguồn. .legacy-hidden hiện tồn tại.
- includes/project_gate.inc.php phục vụ project-start.php rồi exit; CLI bị chặn exit 3. Smoke hiện tại không kiểm tra đăng nhập hay truy vấn DB.
- router.php phục vụ PHP local; cấu hình Apache .htaccess tồn tại nhưng chưa thử trên Apache/hosting.
- Hai tầng cờ cũ: .legacy-hidden và PM_HIDE_LEGACY. F1.10 phải xác định thứ tự ưu tiên trước khi thêm ITA_LEGACY_UI_ENABLED.

## Auth và dữ liệu

- classes/dao/users.class.php dùng DB_PREFIX + users; một số hàm cũ vẫn MD5 và SQL chuỗi.
- classes/security/pmauth.class.php có password_verify, kiểm MD5 tương thích và cập nhật password_hash sau xác thực.
- classes/database/pmdb.class.php là ứng viên adapter prepared statement; cần review trước tái sử dụng.
- classes/database/mysql.class.php dùng mysqli và SET NAMES utf8, chưa phải utf8mb4. Không tự đổi connection chung.
- dc_users, dc_pm_roles, dc_pm_user_roles được tham chiếu trong code PM; không suy ra bảng đã tồn tại hay có cấu trúc tương ứng trên DB đích.
- database/migrations/ tồn tại nhưng không có file. Chưa có schema/snapshot để kiểm engine, FK, index hoặc 3NF.
- User cho biết đã có DB. Chưa xác định DB đó là dev/test, tên schema và backup; chưa kết nối bằng config cũ.
- Không chạy cron/editorial smoke vì có thể bootstrap DB và gây side effect.

## Kiểm chứng

- php -v, php -m: đã chạy.
- php -l toàn source: 1.456 file, 1.451 đạt và 5 lỗi thư viện mail cũ; chi tiết BASELINE.md.
- HTTP local bằng php -S 127.0.0.1:18765 router.php: /, /admin.php?op=login, /ajax.php, /cron/pm-reminders.php, /includes/config.inc.php đều HTTP 200, cùng trang cô lập 974 byte. Server do audit tạo đã dừng.
- Đây là HTTP smoke chế độ cô lập, không phải browser E2E, auth test, bảo mật tổng thể hoặc DB integration.
- DB engine/schema, EXPLAIN, migration rollback, Apache, responsive, UAT: chưa chạy.

## Điều kiện chuyển tiếp

Xác định danh tính Git mới; xác nhận DB dev/test và backup; kiểm tra schema chỉ đọc; hoàn thiện baseline/tag. Sau đó đề xuất BE-GATE #1 cụ thể. FE độc lập có thể làm trước gate nhưng không nối runtime bằng sửa PHP khi chưa duyệt.
## Xác nhận DB từ user và ảnh phpMyAdmin

Ngày 16/10/2026: user xác nhận dung_infracore trên hosting dành riêng cho dự án. Ảnh cho thấy schema có sẵn dữ liệu và engine hỗn hợp: dc_articles, dc_article_categories, dc_cart_items là MyISAM; dc_article_groups, dc_cart, dc_contacts là InnoDB. Có cả utf8mb4_unicode_ci và latin1_swedish_ci. Đây là quan sát một phần qua ảnh, chưa phải inventory đầy đủ hoặc truy vấn trực tiếp. Engine của dc_users và các bảng role chưa xác định.

Không đổi engine/collation bảng cũ. Không dùng transaction hoặc FK với bảng cũ khi chưa kiểm chứng. Chưa có thông tin truy cập DB được xác minh; chưa kết nối hoặc ghi DB. Xem DB_SCHEMA_AUDIT.md để lấy metadata, không lấy dữ liệu người dùng.

## Schema SQL được cung cấp

Đã đọc dung_infracore.sql: 96 bảng (68 MyISAM, 28 InnoDB), không có bảng ita_, không thấy INSERT/REPLACE. dc_users và dc_trackings là MyISAM; dc_users.id là int có dấu. Bảng PM role/permission là InnoDB và có store_id. Chi tiết SCHEMA_AUDIT.md. File cấu trúc không thay thế backup dữ liệu. Chưa import, chưa kết nối hosting.
