# Upload bản cập nhật InfraCore đến Phase14 — 28/10/2026

Đây là **gói cập nhật cho nền InfraCore/Derasoft đang có để user kiểm host**, không bộ cài mới và chưa phải nghiệm thu PHP8.3/FPM/DB. Agent chỉ đóng gói local, không upload hoặc kết nối DB. Source runtime cố định `a7c2da18b7e0ff590859b6d5afbbdd7d1b4e146f`; các commit hồ sơ sau đó không đổi bytes gói.

- ZIP: `artifacts/ita-host-20261028/infracore-update-phase14-a7c2da1.zip`
- Kích thước: **49.310.447 byte** (khoảng49,3MB).
- Nội dung: **1.415 file runtime**,105.049.248byte trước nén; MANIFEST.json và SHA256SUMS.txt là2entry metadata, tổng1.417entry.
- SHA256: `4afc98d1c84f343c4cb112a21c8a098fd4cdee355238f63c50917faf05f973b9`.
- Đã đọc lại ZIP thật: allowlist/path/case/link/exclusions/CRC/size/hash/metadata; Get-FileHash độc lập và review độc lập khớp. Không mang cấu hình private, credential, dữ liệu người dùng, SQL, .htaccess/.user.ini/marker, MOCK/preview/launcher/tests/tmp/docs/scripts vào runtime/gói.

Gồm PHP/Smarty/CSS/JS mới nhất của ứng dụng ITA (nhật ký, bộ lọc, cảnh báo phiên, import, phân bổ, ràng buộc tài khoản, phân quyền từng người và mạng2D/3D), cùng Excel/PDF/font/vendor đồng bộ. Giữ native admin.php và runtime Derasoft/Smarty dùng chung đang có; không dùng ZIP này để dựng site trống. Dependency baseline/provenance có trong MANIFEST; Excel/Inter upstream ref chưa được xác minh đầy đủ, không gọi là vendor đã nâng cấp.

## 1. Trước upload

1. Chọn thời điểm không có người thao tác ghi. Backup **source/config riêng của vhost InfraCore**, dump DB và khóa giấy phép ra ngoài webroot; giữ backup/key private, không gửi lên chat. Kiểm rằng bản backup có thể lấy lại được. Agent không tạo dump hoặc đọc config.
2. Ghi source hiện chạy nếu biết, ngày/giờ upload và vị trí backup. Backup toàn bộ file sẽ bị thay; gói có nhiều PHP/vendor nên không chỉ backup CSS/JS.
3. Xác minh PHP-FPM8.3.x thực và extensions theo PHASE11_HOST_TEST; CLI8.5 local không thay host. HTTPS/certificate phải đúng, display_errors Off/log_errors On; giữ log private. Không Composer/Node install trên host.
4. Giữ cấu hình DB/auth/license/private key và .ita-enabled hiện có. Không tạo .ita-legacy-enabled. Rewrite/deny host là điều kiện riêng: ZIP không đổi .htaccess/.user.ini, không bật HSTS/auto_prepend. Kiểm trực tiếp docs/tests/artifacts/scripts/MOCK/config phải bị từ chối theo checklist host.
5. SQL007/mapping store1/Admin11147quyền/revision2 đã có ảnh user xác nhận; **không chạy lại CREATE/backfill** từ lịch sử. Các schema001–006 vẫn phải phù hợp chức năng đang dùng; không chạy migration/seed để chữa lỗi trong lượt upload này.

## 2. Kiểm gói trên máy

Mở PowerShell trong thư mục chứa ZIP:

```powershell
Get-FileHash -Algorithm SHA256 -LiteralPath '.\infracore-update-phase14-a7c2da1.zip'
```

Hash phải khớp chuỗi phía trên. Giải nén trên máy vào thư mục mới. Trong đó có runtime/, MANIFEST.json, SHA256SUMS.txt. Không giải nén ZIP trực tiếp vào public_html.

## 3. Upload bằng FileZilla

1. Bên phải mở đúng `domains → infracore.dung.derasoft.com → public_html` theo vhost của bạn; xác nhận có admin.php của InfraCore. Không chọn public_html của domain khác hoặc thư mục gốc tài khoản.
2. Bên trái mở **bên trong runtime/** vừa giải nén. Có các thư mục assets, classes, includes, modules, templates và file project-start.php.
3. Upload **các mục nằm trong runtime/** vào public_html, gộp vào thư mục đang có, giữ đúng đường dẫn. Chỉ ghi đè những file gói chứa; không chọn mirror/delete hoặc xóa file ngoài manifest.
4. Không upload nguyên thư mục runtime, ZIP, MANIFEST.json, SHA256SUMS.txt, checksum sidecar/report/hướng dẫn. Không copy config từ workspace, không đè ita_database.local.php hoặc upload toàn repo.
5. Đợi hàng đợi truyền hết, kiểm tab truyền lỗi không còn file. Nếu có lỗi, chưa nghiệm thu; upload lại đúng file lỗi, không sửa DB. Smarty/opcache cập nhật bằng cơ chế hosting được phép nếu cần; không xóa tùy tiện session/cache khác.
6. Mở site, Ctrl+F5 và Ctrl+0 (zoom100%). Gói không tự bật flag capabilities; giao diện cũ ba vai trò vẫn là mode tương thích nếu flag còn tắt.

## 4. Riêng chức năng tạo nhân viên/quyền từng người

Chỉ upload source **chưa làm nút tạo tài khoản hoạt động** khi mode quyền chức năng vẫn tắt. Trước kích hoạt cần:

- Bảng007/mapping hợp lệ và Admin còn47quyền; runtime thật có SELECT/INSERT/UPDATE trên ita_user_permissions.
- Tạo tài khoản cần thêm INSERT dc_users và UPDATE status của đúng native mới; login cần SELECT/UPDATE hash/last_login như trước. Không dùng tài khoản DDL hoặc tự cấp ALL PRIVILEGES để chữa lỗi. Grants của phpMyAdmin SSO không chứng minh grants runtime.
- Đối chiếu cấu trúc native/default bắt buộc; source từ chối cột bắt buộc lạ trước INSERT. Chưa có bằng chứng tạo native thật trên host. Không đổi schema dc_* để vượt kiểm.
- Giữ backup cấu hình và cách phục hồi qua FTP/panel ngay cả khi web lỗi.

**Hiện chưa xác minh grants runtime, nên chưa bật cờ.** Sau khi user/quản trị xác nhận đủ điều kiện, việc bật cờ là thao tác riêng: constant PHP `ITA_CAPABILITIES_ENABLED` phải là boolean true, được định nghĩa trước lúc app đọc quyền. Nếu đặt trong file private trả array thì phải ở trước return; không đưa credential/config vào Git/gói. Agent chưa sửa file hoặc bật trên host. Khi có503 sau cutover, phục hồi cờ đã backup và kiểm log/grants, không chạy lại mapping.

Quản trị tài khoản dùng access.manage +scope.store, không cần thêm role cho tổ hợp quyền. Khi tạo user thật chỉ dùng dữ liệu giả được phép trước; account native mới type staff1, không native Admin. Native MyISAM không cùng rollback, lỗi có thể để user khóa/chưa rõ trạng thái; theo ID trả về để đối chiếu, không bấm tạo lại hoặc xóa/reset mù quáng.

## 5. Smoke test sau upload do user thực hiện

Ghi **kiểm thủ công trên host, phạm vi hạn chế**, source/ZIP hash và từng Đạt/Không/Chưa kiểm. Không ghi nghiệm thu DB từ một lượt login.

1. HTTPS/certificate, PHP-FPM/extensions/font/key; cookie HttpOnly/Secure/SameSite=Lax, headers/no-store/CSP/CORS cuối; log/display_errors và deny paths theo PHASE11_HOST_TEST. Không công khai phpinfo/debug endpoint.
2. Login tài khoản được phép: biết trước last_login có thể ghi, password_hash có thể nâng khi MD5/needs_rehash theo contract, cột MD5 cũ giữ. Login đúng/sai/không tồn tại, sai phải401 cùng thông báo; không503. Không thử mật khẩu tài khoản vận hành của người khác.
3. Dashboard/alerts/report không503, số liệu cùng scope/filter. Dataset lớn có cap: dashboard5.000, forecast2.000, mạng200nodes/400links, không chứng minh1triệu; lọc hẹp khi vượt, không tăngcap để né.
4. Kiểm các sửa mới: nhật ký/filter/chi tiết, lifecycle Số thiết bị/drilldown, import preview/lỗi/cancel, phân bổ chỉ mở sau chọn, cảnh báo nhắc phiên, chi phí tổngVND/xác nhận, ngày giấy phép,3D/nhãn/xoay/fullscreen, responsive1366/768/390.
5. Tải một Excel/PDF dữ liệu giả hợp lệ, mở lại file/MIME/bytes/tiếng Việt; CLI file export không thay HTTP/FPM. PHP8.5 local còn deprecation vendor, không sửa vendor/tắt error_reporting để che;8.3/FPM vẫn phải kiểm thực.
6. Sau cutover được phép mới kiểm user giả mới: thêm/chọn quyền/bộ phận, login riêng, ngoài quyền/scope403, GET/CSRF sai không ghi, audit đọc lại. Không thu hồi Admin cuối. Không stress/revoke grants/race trên host.

## 6. Rollback

Ngừng ghi, khôi phục **đúng source/vendor/config/cờ đã thay** từ backup cùng lượt. Giữ DB, audit, tài khoản/allocations/ticket/snapshot và dữ liệu phát sinh; không DROP/DELETE hoặc restore dump đè dữ liệu mới. Account đã tạo không bị thu hồi chỉ bằng rollback source; đối chiếu trạng thái/quyền riêng. Role-mode không tương đương tổ hợp quyền tùy chỉnh, cần review trước đổi cờ. Ghi thời điểm/source khôi phục và kiểm login/đọc/file sau rollback.
