# Kiểm bảo mật trước upload theo tài liệu Word — 26/10/2026

Đã đọc20 mục trong **“kiểm thử web trước khi upload lên Host.docx”** user gửi; không sửa DOCX, thực thi macro/link hoặc coi đề xuất Cloudflare là quyền đổi DNS/server. User yêu cầu kiểm xong mới đóng gói/upload.

Bổ sung theo user26/10: S26-01 là **hạn chế chấp nhận được** vì tương thích hệ thống cũ, không xóa/reset credential. S26-02 đã được duyệt kèm điều kiện và ngoại lệ Argon2, đã build/kiểm local theo S26_AUTH_VERIFY.md; S26-03 chỉ kế hoạch PHP8.3, chưa có runtime/chưa chạy. Bản đồng bộ20 mục nằm tại PHASE11_SECURITY_TEST_20.md;17–20 giữ **CHƯA KIỂM host**, không phải điều kiện local đã đạt.

**Các bộ kiểm local dưới đây đạt, nhưng chưa đủ bằng chứng đánh dấu cả20 mục đạt trên host. Chưa đóng gói ứng dụng/upload.** Không tạo/kết nối DB, không đọc private config/credential hoặc gọi host. PHP CLI thực8.5.10, chưa8.3.33. Lượt kiểm ban đầu kế thừa dd03897 không sửa ứng dụng; cập nhật S26-02 sau duyệt chỉ sửa ItaAuthService(+test), không PmAuth/server config.

## Đối chiếu20 mục

“Đạt local” chỉ trong phạm vi fake transport/static/HTTP fixture, không PASS DB/host hoặc chứng nhận legacy toàn repo.

| # | Mục Word | Kết quả và giới hạn |
|---|---|---|
|1|Hash Argon2/bcrypt|**Một phần:** PmAuth thật dùng password_hash/PASSWORD_DEFAULT, test xác nhận hash mới bcrypt/Argon2 và verify đúng/sai. Native MD5 vẫn được hỗ trợ và password MD5 được giữ sau upgrade; chưa loại hash yếu. Contract mật khẩu legacy>72byte được giữ, không tự sửa credential. |
|2|Rate limit login|Đạt local: limiter thật với fake DB kiểm5lần/identity,30lần/IP,429, cửa sổ900giây và bucket HMAC. S26-02 bổ sung686checks một verify/401, zero native write401; Argon2 một real verify ngoại lệ. Chưa lock/shared state/concurrency hoặc constant-time HTTP. |
|3|Session hết hạn|Đạt local: actual ItaSession kiểm idle30phút/tuyệt đối8giờ, revision/store mismatch, ID+CSRF rotation và logout; session synthetic. Chưa storage/FPM/proxy host. |
|4|Xóa debug|Rà tập JS/PHP ITA không thấy console.log/debugger/var_dump/print_r. Không xóa legacy/vendor; không suy toàn repo sạch debug. Export thật còn deprecation8.5 (S26-03). |
|5|Secret không frontend|Rà JS/template ITA không thấy ITA_LICENSE_KEY/DB password/auth_key/private key. Browser giấy phép28checks kiểm masked listing, reauth, tự ẩn, audit lỗi. Reveal Admin cố ý trả key; CSRF meta không phải server secret. Không đọc file dư/config host. |
|6|Không show lỗi chi tiết|56checks HTTP lỗi HTML/JSON/Smarty/ref/fallback +61browser lỗi đạt. Module display_errors=0/catch không trả stacktrace. Fatal trước module và PHP-FPM log/display_errors thực chưa kiểm. |
|7|Giới hạn loại upload|Phase4:88checks có XLSX thật; source chỉ.xlsx, kiểm ZIP/XML/công thức/external relationships. MIME **có điều kiện fileinfo**; không gọi là vô điều kiện. Chưa upload PHP-FPM host. |
|8|Giới hạn dung lượng|Đạt local:5MiB/5000dòng và guard ZIP giải nén/ratio/entry. Limit Apache/nginx/PHP-FPM thật chưa kiểm. |
|9|Validate lại server|Actual DAO/service qua fake transport kiểm input/role/scope/version/CSRF ở tài sản/import/phân bổ/license/ticket/forecast/network. Không dùng FE validation thay server, không chứng minh persistence. |
|10|Đổi ID123→124|Guard DAO/service với fake actor kiểm403 ngoài scope, topology cả hai đầu, stale version. Browser API mock không chứng minh IDOR HTTP+DB/session thật; host chưa kiểm. |
|11|User thường vào admin|Server guard với fake actor và browser ba vai trò đạt. Không chỉ dựa ẩn menu. Access/login HTTP+DB thật chưa kiểm. |
|12|Parameterized SQL|Đọc PmDb prepare/bind và ITA SQL động từ fragment cố định/placeholders; unit kiểm bind/scope. Không thấy request chèn thành giá trị SQL trong ITA đã rà. Không kết luận SQL legacy an toàn; chưa EXPLAIN/DB. |
|13|Bắt buộc HTTPS|Actual ItaSession từ chối HTTP remote/loopback chưa cho phép; không tin X-Forwarded-Proto tự khai. Ngữ cảnh HTTPS trong CLI là giả lập, chưa TLS/chứng chỉ/redirect/proxy thật. |
|14|Security headers|102checks HTTP fixture module/error headers đạt: CSP/no-store/nosniff/Referrer/Permissions. HSTS chưa bật; CSP style-src unsafe-inline còn theo gate. Header cuối Apache/proxy/file/lỗi host chưa kiểm. |
|15|Cookie flags|Actual session_get_cookie_params: HttpOnly=true,Secure=true khi giả lập HTTPS,SameSite=Lax,path=/; strict mode/only cookies. Chưa quan sát Set-Cookie HTTPS host. |
|16|CORS chỉ domain cần|Không thấy Access-Control-Allow-Origin/Allow-Credentials trong tập source ITA quét; ứng dụng cùng origin. Không tự thêm wildcard/allowlist. CORS vhost/proxy/CDN/legacy khác chưa kiểm. |
|17|DB không public|**CHƯA KIỂM host:** không scan port hoặc kết nối DB. Quản trị xác minh bind/firewall/network đúng phạm vi, không gửi credential. |
|18|DB user tối thiểu|**CHƯA KIỂM host:** không có SHOW GRANTS của chính runtime. SQL-tool user có thể khác PHP. Source cần SELECT/INSERT/UPDATE theo bảng; login có UPDATE dc_users hash/last_login. Không sửa/cấp grants hoặc coi nhu cầu source là quyền effective. |
|19|Cloudflare|**CHƯA KIỂM host:** không có bằng chứng DNS/CDN/account, không đổi DNS/proxy/cache. Không chứng minh bằng unit test. |
|20|Backup/theo dõi lỗi|**CHƯA KIỂM host:** backup source/DB/key ngoài webroot, log ACL/rotation/alert/restore chưa xác nhận. Log/ref fixture chỉ kiểm local; không tạo dump hoặc đọc log thật. |

## Hạn chế chấp nhận được

- **S26-01 — MD5 native còn lưu:** user chấp nhận giữ vì tương thích hệ thống cũ. PmAuth chỉ ghi password_hash/last_login, giữ password MD5; **không xóa/reset/NULL credential**, không đổi PmAuth hoặc luồng legacy trong bước này. Rủi ro dò MD5 khi lộ DB vẫn còn, không coi đã loại hash yếu hoặc nghiệm thu host. Thay chính sách sau này cần gate riêng, đối chiếu consumer và backup.

## Rủi ro còn lại

- **S26-02 — Đã sửa và kiểm local theo duyệt:** một verify/401, dummy cho missing/MD5 và real cho bcrypt; Argon2 một real verify theo ngoại lệ user, không thêm dummy. Recheck trước mọi native write, không đổi PmAuth;686checks service/downstream counters và hồi quy tại S26_AUTH_VERIFY.md. **Đếm verify không chứng minh hằng thời gian**, chưa đo HTTP/DB/host/MyISAM race; không nhận là đã loại mọi enumeration. Không dùng benchmark để nghiệm thu.
- **S26-03 — Deprecation Excel/PHP8.5:** export thật tạo/mở lại XLSX STRING và render PDF tiếng Việt thành công nhưng PhpSpreadsheet/ZipStream có deprecation. Không sửa vendor/hạ error_reporting để che. Test Phase4 hiện có tự loại E_DEPRECATED, nên riêng output đó không chứng minh hết warning. Cần kiểm PHP8.3/FPM display_errors Off và bytes file thật; chưa chạy.
- **Host chưa kiểm:** TLS/cookie/header/CORS cuối, legacy rewrite/deny, DB grants/network/isolation, staging ACL, backup/log và source đầy đủ.4 file mẫu Phase8 không chứng minh bảo vệ Phase11/13 đã có trên host. Không ghi toàn bộ đã ổn.

## Lệnh và kết quả thật

| Nhóm | Lệnh/file | Kết quả |
|---|---|---|
|Auth/session/limiter mới|php tests/ita_preupload_auth.php|29checks đạt; PmAuth thật qua reflection+fake PmDb, limiter fake ItaDb, session synthetic local |
|S26-02 sau duyệt|php tests/ita_s26_auth.php|686checks service/downstream counters đạt;PmAuth source không đổi;Argon2 và bcrypt variants/long password/session/guard đã kiểm, không DB/timing |
|Unit/scripted|php tests/ita_phase03_unit.php /04_unit.php /05_unit.php /06_unit.php /07_unit.php /08_unit.php|39 /88 /203 /159 /237 /72 đạt; Phase4 có XLSX thật |
|Unit/scripted|php tests/ita_phase09_unit.php /10_unit.php /11_forecast_export.php /11_import_export.php /13_unit.php /ita_write_routes.php|134 /52 /30 /54 /179 /13 đạt; không DB |
|HTTP local|python tests/ita_phase11_privacy_http.py /ita_phase11_error_http.py /ita_phase11_guard_http.py|102 /56 /109 đạt; module/gate/Smarty có thật, transport fixture, không Apache/host |
|File thật|php -d extension=gd tests/ita_phase09_exports.php|XLSX mở lại, formula-like STRING, PDF tiếng Việt render được; còn deprecation8.5, không phải HTTP download host |
|Browser|playwright-cli -s=ita-phase06 run-code --filename=tests/ita_phase03_browser.js /04_browser.js /05_browser.js /06_browser.js /07_browser.js /08_browser.js /09_browser.js /10_browser.js /13_live_browser.js /11_errors_browser.js|30 /30 /29 /28 /64 /63 /42 /64 /31 /61 đạt; static/API mock, không auth/DB thật |
|SQL file|python tests/ita_phase13_sql_contract.py|57checks tĩnh đạt, không SQL server/DB |
|Release guard|python tests/ita_release_validation_test.py|66checks đạt với ZIP synthetic trong bộ nhớ, gồm NUL filename/Windows reparse bổ sung; không tạo ZIP ứng dụng |
|Syntax|php -l tests/ita_preupload_auth.php|Đạt |

Browser static127.0.0.1:18766, server node workspace/session ita-phase06; log synthetic ở tmp/security-*.log, không commit/gói. Không chạy ita_phase02_checks.php/fixture/sql/browser vì có mở/reset DB fixture ngoài phạm vi. Không đọc private config hoặc dùng kết quả mock thay DB.

## Dừng đóng gói theo yêu cầu mới

**Chưa đóng gói ứng dụng/upload.** Công việc trước chỉ tạo inventory JSON review1415file/105032697byte trong artifacts, không ZIP. Lỗi Excel được tái hiện: sort Path Windows cho hash80fc14a4…, sort relative POSIX ordinal khớp baseline88815432… cho566file. Đã sửa sort key, không thay dependency/baseline. Bản inventory sau sửa đạt kiểm đường dẫn/hash, không thay kiểm ZIP release thật hoặc xác nhận dependency backup ngoài máy dev.

Sau khi các mục mở được chốt trong phạm vi user chấp nhận mới đóng gói/kiểm ZIP thật theo F11.5. Upload vẫn do user qua FileZilla; không suy quyền đổi host/DNS/grants từ checklist. Không push/merge/tag7–10 hoặc đổi nghiệm thu DB bằng kết quả local.

Kế hoạch S26-03: PHASE11_PHP83_RETEST_PLAN.md; chưa có runtime user cung cấp, chưa sửa biến thể test hoặc chạy PHP8.3. Checklist host bổ sung HTTPS/Set-Cookie/header/CORS/display_errors và hosting panel17–20 tại PHASE11_HOST_TEST.md.
