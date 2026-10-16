# Phase 2 — Kết quả và cách bật trên host

Đã build và kiểm local qua entrypoint PHP/Smarty thật, DB MySQL 8.4 riêng port 18767, dữ liệu giả; không dùng dữ liệu hay credential host. Giao diện giữ hệ navy/cyan đã rà theo Impeccable, bổ sung đăng nhập, quản lý quyền và nhật ký.

## Kết quả kiểm chứng

- `php -l` toàn bộ PHP ITA mới và module/bootstrap: đạt. `node --check` shell/admin: đạt.
- `php tests/ita_phase01_routing.php`: 32 checks đạt, cập nhật contract anonymous chuyển đăng nhập. Chưa kiểm Apache/legacy restoration E2E.
- `playwright-cli -s=ita-phase02 run-code --filename tests/ita_phase02_browser.js`: lần kiểm đầu và sau tách module quyền/audit đạt 46 checks; lần cuối bổ sung kiểm JS runtime có 47 checks. Ba vai trò, scope store/bộ phận, URL trái quyền 403, cross-store 404, CSRF, last active Admin, đổi quyền vô hiệu session, audit, rate limit, đăng xuất, bố cục 1366/768/390.
- Sau browser, `php tests/ita_phase02_checks.php`: 8 integration checks đạt: MD5 nâng cấp hash, audit bỏ secret, scope, tắt quyền, mật khẩu legacy dài giữ nguyên và không chấp nhận suffix sai. Script sau đó reset **chỉ fixture giả local** để chạy lại. `--reset-only` chỉ chuẩn bị lại fixture, không tính là test pass.
- Đã xem ảnh mobile đăng nhập/dashboard; không tràn trang, form và bảng đọc được. Desktop/tablet được đo trong E2E. Review Agent độc lập: không thấy lỗi cần sửa; chưa chạy concurrency trực tiếp.
- Hồi quy `assets/ita/preview/verify-ui.js` trên static port 18766: lọc/tìm/phân trang/modal/alerts, 3 kích thước, lỗi mạng và retry, confirmation escape, không JS runtime error đều đạt. Console có lỗi mạng được test chủ động mô phỏng; không kết luận console sạch cho test này.
- `php tests/ita_phase02_sql.php`: 3 script preflight/bootstrap/verify thực thi local, giữ Admin có sẵn. EXPLAIN danh sách và aggregate có scope dùng access=ref, key=idx_ita_assets_department, rows=6 trên fixture nhỏ; chưa phải benchmark 500 tài sản hoặc optimizer MariaDB host.

## User chạy SQL trên phpMyAdmin

1. Backup toàn bộ DB `dung_infracore` ra ngoài repo; giữ bản source/config đang chạy.
2. Chạy `database/host/phase02_preflight.sql` (chỉ đọc). Cần user 111/store 1/status 1, đủ 8 cột native, chưa có ba bảng auth. Nếu khác, gửi kết quả trước khi tiếp tục.
3. Import **một lần** `database/migrations/005_ita_auth.sql` — tạo 3 bảng mới. Không chạy lại bộ CREATE Phase 1.
4. Chạy `database/host/phase02_bootstrap_admin.sql` — cấp Admin IT cho 111, không đổi username/mật khẩu. Nếu không có dòng kết quả đúng thì dừng, không sửa native status làm lối tắt.
5. Chạy `database/host/phase02_verify.sql`, gửi kết quả 3 bảng/engine/collation và dòng quyền 111.

DDL không có rollback transaction. Xem BE_GATE_02.md cho hoàn tác giữ dữ liệu. Các bảng cũ, 12 bảng Phase 1 và danh mục host 6/6/6 được giữ nguyên.

## Cấu hình riêng trước khi bật runtime

Copy `includes/ita_database.example.php` thành `includes/ita_database.local.php` **chỉ trên máy/host riêng**, điền tài khoản DB có quyền đọc/ghi cần thiết, DB dung_infracore, store_id=1. Không gửi password trong chat/Git. `auth_key` lấy từ `bin2hex(random_bytes(32))` bằng CLI riêng, giữ ổn định giữa các worker. File private bị gitignore và route gate chặn truy cập HTTP. DB user runtime không cần CREATE/DROP/ALTER.

Hosting phải dùng HTTPS, `allow_local_http=false`; proxy phải khai báo HTTPS đúng ở web server, ứng dụng không tin header forwarded tự do. PHP cần mysqli/mysqlnd, mbstring, JSON, session có storage ghi được. Cache Smarty nằm trong thư mục temp riêng. Bật đồng bộ source Phase 2 sau khi schema/config sẵn sàng; không đưa test/dump/config local vào gói runtime. Agent chưa deploy.

Đăng nhập `/admin.php?op=ita&act=login` bằng username/email và mật khẩu **hiện có** của tài khoản 111. Sau đó vào Tài khoản để gán một trong ba vai trò và bộ phận. Non-admin không có bộ phận sẽ không đọc được tài sản. API luôn kiểm quyền ở server; đổi quyền tăng revision và thu hồi session cũ ở lần yêu cầu kế tiếp. Admin cuối cùng còn active được bảo vệ.

## Giới hạn còn lại

Chưa kiểm tích hợp MariaDB 10.6/PHP 8.3/HTTPS trên host hoặc UAT tài khoản 111. Phiên local HTTP chỉ bật với loopback. Mật khẩu MD5 dài hơn 72 byte được xác minh đầy đủ nhưng hoãn nâng cấp bcrypt để tránh cắt suffix; bcrypt hiện có từ chối input dài hơn 72 byte. Credential native MyISAM không tham gia transaction InnoDB.

Audit writer có allowlist sự kiện tài sản nhưng chưa có CRUD Phase 3 để gắn hook; F2.5 chưa nghiệm thu. Các nút nghiệp vụ chưa build tiếp tục thông báo chưa hỗ trợ. Không tuyên bố Phase 1 hoặc toàn hệ thống hoàn tất.
