# Phase11 — Checklist host, CHƯA CHẠY

## Bổ sung26/10 — HTTPS, cookie/header và hosting panel17–20

Chỉ checklist cho user khi được phép kiểm host, **chưa chạy**. Không tự upload/cấu hình/DNS/grants. Mục17–20 của PHASE11_SECURITY_TEST_20.md giữ CHƯA KIỂM host và được xác minh qua hosting panel/quản trị có quyền; không thay bằng unit local hoặc phpMyAdmin user khác.

| Mã | Cách quan sát / tiêu chí | Kết quả thật |
|---|---|---|
|H-HTTPS|Gõ đúng `https://infracore.dung.derasoft.com/` và mở đường ITA cần kiểm; quan sát URL cuối vẫn HTTPS, certificate hợp lệ, không cảnh báo TLS/mixed-content. Không nhập credential trên HTTP hoặc bỏ qua cảnh báo chứng chỉ. TLS/redirect/proxy do hosting panel quản trị xác nhận, không tin chỉ X-Forwarded-Proto client tự khai | CHƯA KIỂM host |
|H-COOKIE|DevTools Network quan sát **Set-Cookie** của ITA_SESSION ở phản hồi phù hợp: HttpOnly; Secure trên HTTPS; SameSite=Lax; Path=/, đồng thời kiểm Application/Cookies. Response không phát Set-Cookie không đủ kết luận; dùng phiên test được phép. Không chụp/copy giá trị cookie, không xuất HAR. Login có native last_login/hash side effect nên không tự mở ngoại lệ không ghi dc_* | CHƯA KIỂM host |
|H-HEADERS|Network kiểm header cuối sau proxy/Apache/PHP-FPM trên login/shell/JSON/file/error: no-store/nosniff/CSP/Referrer/Permissions như source đã duyệt; không policy trùng/mâu thuẫn. HSTS chỉ quan sát trạng thái, không tự bật. Không xem HTTP fixture local là header host | CHƯA KIỂM host |
|H-CORS|Kiểm response cuối và policy trong hosting panel/proxy/CDN: không ACAO wildcard hoặc phản chiếu Origin tùy ý; ACAC chỉ khi policy thực cần. Ứng dụng ITA cùng origin không cần tự mở CORS; thiếu header không phải lỗi tự động. Không thử CORS để ghi dữ liệu hoặc đổi cấu hình | CHƯA KIỂM host |
|H-ERRORS|Hosting panel xác nhận PHP-FPM display_errors=Off, log_errors=On, log private/rotation/ACL; không tạo phpinfo/debug endpoint hoặc gây fatal trên production. Export test được phép phải đúng MIME/signature và không có warning/HTML trong bytes. Không hạ error_reporting để che deprecation | CHƯA KIỂM host |
|17 — DB network|Hosting panel/quản trị xác nhận database bind/network/firewall/remote-access giới hạn phạm vi cần; không scan port, mở public DB hoặc gửi credential | CHƯA KIỂM host |
|18 — Runtime grants|Hosting panel đối chiếu đúng DB user ứng dụng và quyền hiệu lực tối thiểu từng bảng; SHOW GRANTS chỉ có giá trị cho user/role đang kiểm, không mặc định phpMyAdmin=runtime. Không tự cấp/thu quyền hoặc đưa credential DDL vào config app | CHƯA KIỂM host |
|19 — Cloudflare|Hosting/DNS panel và Cloudflare dashboard nếu có: xác nhận trạng thái/proxy/TLS/CORS/cache trong phạm vi user quản trị. Nếu không dùng ghi rõ chưa cấu hình; không đổi DNS/proxy/cache để cho checklist đạt | CHƯA KIỂM host |
|20 — Backup/log|Hosting panel xác nhận backup source/DB/key ngoài webroot, lịch/thời điểm/phạm vi, quyền truy cập và theo dõi lỗi/rotation/cảnh báo; khả năng restore có bằng chứng riêng, không restore đè dữ liệu thật hoặc lấy dump vào chat | CHƯA KIỂM host |

Ghi source/gói/giờ kiểm và kết quả Đạt/Không/Chưa kiểm với bằng chứng đã che; không đánh dấu tất cả đã đạt từ việc đăng nhập hoặc tải một file. Checklist này không mở quyền gói/upload và không nghiệm thu DB. Các bước lịch sử dưới đây vẫn giữ để đối chiếu.

**Source(g)23/10:** app/module/error boundary đã thêm Referrer-Policy=strict-origin-when-cross-origin, Permissions-Policy=camera=(), microphone=(), geolocation=(); PHASE11_PRIVACY_HEADERS_VERIFY.md. Trên host kiểm header cuối đúng một policy mỗi tên, shell/login/API/file/error và chức năng hiện có không bị ảnh hưởng. HSTS chưa thêm trong code; proxy/server có thể có policy riêng chưa xác minh. Không tự bật HSTS hoặc sửa config để làm checklist qua.

**Source(f)23/10:** lỗi HTML/JSON và log ứng dụng đã build local (PHASE11_ERROR_HTTP_VERIFY.md). Cần kiểm host mã HTTP/MIME/ref và log private thực tế; PHP-FPM display_errors/log_errors/ACL/rotation, startup/fatal trước boundary và Apache ErrorDocument chưa được sửa/xác minh. Không coi lỗi ứng dụng đã đẹp là cấu hình server đã đạt. Delta3 file export cũ không chứa error boundary/template/CSS/bootstrap/module/guard mới; chưa có gói mới.

**Source mới23/10:** (c)/(d) đã build local, có guard project-start và deny tests/artifacts/scripts/MOCK trong PHP/.htaccess. Dự đoán source cũ303/MOCK200 ở lịch sử dưới không thay tiêu chí deny403/404 khi triển khai source mới.109 PHP HTTP fixture không chứng minh Apache/host; tất cả host vẫn Chưa kiểm, không tự upload hoặc đổi marker/config.

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
