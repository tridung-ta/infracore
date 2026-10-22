# Phase11 — Checklist host, CHƯA CHẠY

Ngày soạn22/10/2026. User thực hiện sau khi triển khai được phép. Không phải phê duyệt deploy hoặc sửa server; DB/host Phase7–10 chưa nghiệm thu, không merge/tag/push/deploy trong bước này. F11.2 rà source xong; kiểm host dưới đây **chưa chạy**. Không chạy fixture/reset/cron hoặc SQL ghi để kiểm.

Ghi gói/commit/hash upload thật, ngày, vhost/document root, PHP-FPM version và vai trò test; không suy phiên bản source từ ảnh giao diện. Dùng tài khoản thật từng vai trò và hồ sơ kiểm thử được phép, không gửi password/cookie/key/token ra báo cáo.

| Bước | Kiểm tra và kết quả mong đợi | Kết quả thật |
|---|---|---|
|1|Isolation **chính kết nối ứng dụng** RR/SERIALIZABLE; phpMyAdmin là session khác, không đủ. Không thêm debug endpoint để đọc|Chưa chạy|
|2|PHP host8.3.33 theo hồ sơ; xác nhận runtime thực, GD/mbstring/zip/fileinfo/DOM, dependency/font; local8.5 không chứng minh host|Chưa chạy|
|3|ITA bật đúng DOCUMENT_ROOT; **không có .ita-legacy-enabled** trên host. Xác nhận bằng File Manager/FTP được phép, không chỉ HTTP marker403|Chưa chạy|
|4|DevTools Application/Network: ITA_SESSION HttpOnly=true, Secure=true trên HTTPS, SameSite=Lax, Path=/, session lifetime; login xoay ID, logout hết phiên. Không chụp giá trị cookie/token|Chưa chạy|
|5|Header **cuối cùng** sau proxy/Apache/PHP-FPM trên login/shell/JSON/file và lỗi: no-store, nosniff, CSP/noindex; không có header mâu thuẫn làm cache dữ liệu. So Referrer/Permissions/HSTS thực với policy được duyệt, không tự bật HSTS|Chưa chạy|
|6|display_errors Off, log_errors On ở PHP-FPM; không warning/HTML vào bytes tải file; log private, quyền/rotation được xác minh, không password/key/session. Không gây fatal trên production để kiểm|Chưa chạy|
|7|Deny URL nhạy cảm theo bảng dưới, cả thư mục và file con; không chỉ nhìn directory listing|Chưa chạy|
|8|URL legacy GET không mở chức năng cũ, POST không chạm handler; xem danh sách dưới|Chưa chạy|
|9|Staging import đường dẫn thực nằm **ngoài public_html/document root**, không symlink về webroot; owner/ACL riêng PHP worker, không world-readable. Không công khai đường temp trong báo cáo, không tải staging chứa dữ liệu thật|Chưa chạy|
|10|Từng vai trò đo layout PHP thật1366/768/390, form/lỗi/bảng, URL trái quyền; static MOCK local không thay bước này|Chưa chạy|

## Probe deny tài liệu, config và artifact

Thử `/docs/`, `/tests/`, `/.git/`, `/artifacts/`, `/assets/ita/mock/` **và** tệp con điển hình: `/docs/PROGRESS.md`, `/.git/HEAD`, `/assets/ita/mock/overview.json`, `/assets/ita/mock/dashboard.html`, `/assets/ita/mock/reports.html`; tệp test/artifact chỉ dùng tên đã có trong inventory được phép. Không thực thi PHP test/fixture để probe; yêu cầu deny thư mục bằng cấu hình host trước khi thử sâu.

Config: `/includes/ita_database.local.php`, `/.env`, `/.user.ini`, `/.htaccess` và dump path nếu tồn tại theo inventory host; **phải403/404, không trả nội dung**. Không đọc/tải body để chứng minh secret. Dùng Network status/headers, không lưu response body/HAR có credential. Đường không tồn tại404 không chứng minh tệp có thật cũng được chặn; đối chiếu inventory host riêng.

**Tiêu chí:**403/404 cho đường nhạy cảm; không200/file download/directory listing, không coi redirect/login200 cuối chuỗi là PASS deny. Kiểm status đầu tiên, không chỉ follow redirect. `/tests`/`artifacts` hiện có thể303 qua catch-all; MOCK JSON có thể200 theo allowlist source: ghi **không đạt policy deny**, không sửa/xóa/reroute ngoài gate. Exclusions deploy là bắt buộc nhưng không thay kiểm bảo vệ đường còn dư trên host.

## Probe URL legacy an toàn

GET `/admin.php?op=login`, `?op=pm`, `?op=editorial`, `/ajax.php?op=search_suggest`, `/pm_ajax.php?op=pmcosts`, `/pm-launch.php`, `/project-start.php`, `/mobile.php`: không chạy UI/handler legacy, thường303 về ITA theo source. Prefix `/modules/`, `/classes/`, `/includes/`, `/cron/` phải403/404 khi truy cập trực tiếp. Không dùng op có side effect để thử GET.

POST chỉ dùng payload rỗng ở URL legacy **sau khi đã xác minh rewrite/gate bằng GET**, không dùng upload/xóa/thanh toán; mong403 và không đến handler. Nếu GET cho thấy handler cũ đang hoạt động, dừng probe POST và báo cấu hình, không thử ghi dữ liệu. Ghi rõ URL/method/status/redirect đầu tiên và có/không nội dung legacy; không suy303 là kiểm quyền của chính legacy.

## Probe bổ sung scripts/license/database/project-start — chưa chạy

Không bật directory listing để kiểm. `Options -Indexes` có đang hiệu lực hay không phải xác minh bằng cấu hình/response; không thấy listing **không chứng minh** file con bị chặn. Không đưa test/fixture/scripts vào runtime để phục vụ probe.

| URL | GET và HEAD mong đợi | POST rỗng mong đợi | Tiêu chí không đạt |
|---|---|---|---|
| /scripts/ |403/404; không listing/file |403/404 |200, listing, download;303 chưa đạt deny tường minh |
| /license/ |403/404 theo deny hiện có |403/404 |Nội dung thư mục/file hoặc redirect thay deny |
| /database/ |403/404, không dump/SQL/test |403/404 |200/download/nội dung hoặc redirect thay deny |
| /project-start.php |Không thực thi landing legacy/bypass; theo contract gate có thể303→ITA, HEAD không có body |403/405, không landing200 |200 landing/worker trực tiếp, redirect loop, POST vào nội dung cũ |

Ghi status **đầu tiên**, Location, Content-Type và có/không body cho HEAD; không chỉ status sau follow redirect. Hỗ trợ HEAD không được làm lộ nội dung hoặc bỏ qua deny. Mỗi method có kết quả riêng, không suy GET đạt thì POST/HEAD đạt. Source hiện tại dự đoán /scripts GET/HEAD303 (chưa đạt deny tường minh); project-start có thể trả200 nếu rewrite không hoạt động, gate riêng chưa được bổ sung.

Trước POST rỗng, xác minh GET/HEAD và rewrite/deny trên URL thư mục an toàn. Nếu dấu hiệu handler cũ đang chạy, dừng, không POST vào upload/fixture. Không probe trực tiếp script PHP có tác vụ DB hoặc kích hoạt worker bằng query để chứng minh deny. Với file con, chọn tệp văn bản không nhạy cảm đã có trong inventory được phép, không tạo/upload file mới; nếu không có thì ghi chưa kiểm file con. Probe không thay xác nhận cấu hình và inventory host.

Tình trạng tất cả mục trên: **CHƯA CHẠY**. Sau khi gate được duyệt/triển khai mới đối chiếu contract cuối; không tự áp rule/auto_prepend trong lúc kiểm.

## Quyền, export và việc chưa được sửa

Đối chiếu route×vai trò trong PHASE11_SECURITY_REVIEW.md: GET ID ngoài scope, POST thiếu/sai CSRF và vai trò bị từ chối; file không có quyền không trả bytes. S11-01/S11-02 **đã sửa/kiểm local sau phê duyệt riêng**, chưa kiểm host; PHASE11_EXPORT_VERIFY.md. User kiểm DB thủ công trên host khi sẵn sàng, agent không tạo/kết nối DB test. Không tự disable tài khoản production giữa request; kiểm concurrent/revocation cần phạm vi kiểm thủ công an toàn do user xác nhận. S11-01 lỗi native/audit không bytes và không audit thành công; S11-02 giữ chính sách không audit export hiện có, không áp assertion audit mới cho hai GET đó.

Mỗi mục ghi PASS/FAIL/CHƯA CHẠY cùng bằng chứng đã che thông tin nhạy cảm; chỉ cập nhật PROGRESS khi có xác nhận cụ thể. Không coi toàn bộ checklist đã chạy vì đăng nhập hoặc một file tải được. Không thay đổi config/headers/DB trong bước kiểm nếu chưa được duyệt.
