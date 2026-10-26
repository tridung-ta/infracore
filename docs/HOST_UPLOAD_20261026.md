# Gói cập nhật cộng dồn để kiểm host —26/10/2026

User cho phép **đóng gói để tự upload rồi kiểm trên host PHP8.3**. Agent không upload/kết nốiDB/đổi server. Đây là gói kiểm, không chứng nhận production/DB. PHP local user vừa cung cấp `D:\Hoc_PHP\php\php-8.5.10-nts-Win32-vs17-x64\php.exe` trả **8.5.10** thực; không phải runtime8.3. S26 unit686 và các kết quả local cũ không biến thành PASS8.3/FPM/DB. Auto_prepend/HSTS không được duyệt. Không merge/tag7–10/push/deploy bằngagent.

## Phạm vi và nền bắt buộc

Cập nhật cộng dồn ITA tới source_commit40 ký tự trong MANIFEST: PHP ITA, adapters PmDb/PmAuth, guard/mode/bootstrap/project-start, Smarty templates, CSS/JS/UIvendor, Excel/PDF/Inter có hash. **Không phải bộ cài nền Derasoft mới**: host phải có admin.php/ajax.php đã gọi project_gate trước bootstrap, Smarty4.5.5/native runtime cần thiết, configDB/auth private/khóa ITA_LICENSE_KEY đúng và marker .ita-enabled. Không có .ita-legacy-enabled. Bốn file host mẫu chỉ khớp Phase8, không chứng minh toàn bộ nền/dependency/schema hiện tại. Không dùng deltaS11 ba file thay gói cộng dồn.

Payload không có admin.php/ajax.php/nativebootstrap toàn bộ hoặc marker mới; **kiểm điều kiện nền trên trước upload**, nếu khác thì dừng lấy metadata/source đại diện, không ghi đè nền bằng bản không rõ. Giữ license/config/dữ liệu host. Nếu host thiếu class/route nền, không tự bật legacy hoặc dùng DDLcredential để chữa lỗi.

Schema cần001–005 đầy đủ +006 cho network live. Gate005/006 nháp kèm theo là kế hoạch đối chiếu, **không phải quyền chạy005 mới**. Không suy12 bảng lịch sử nghĩa là thiếu4. 006 đã được duyệt chuẩn bịSQL, nhưng chạy host vẫn theo PHASE13_SQL_HOST_GUIDE: user xác nhận schema/backup/preflight/bảng chưa có rồi tự thực hiện trong phạm vi đã duyệt; không chạy lại bảng tồn tại/IFNOTEXISTS. Gói không tự chạySQL/seed/migration và không chứa005 để import mù. Nếu metadata chưa đủ, chưa test network live; không ghi toànứngdụng đạt.

## Trước upload — user thực hiện

1. Xác định đúng vhost InfraCore riêng/documentroot, source đang chạy, PHP8.3 runtime/extensions mysqli/mysqlnd/OpenSSL/mbstring/zip/fileinfo/DOM/zlib/GD. Hostingpanel: DB khôngpublic, effectivegrants runtime, CDN nếu có, backup/logprivate theo20mục. Không gửi config/key/password/dump/HAR lênchat.
2. Backup **tất cả tệp sẽ thay** theo runtime manifest +.htaccess nếu sửa riêng, cấu hình vàkhóa private tách ngoàiwebroot; dumpDB bằng công cụ quản trị được phép, giữ bản có thể phục hồi. Ghi baselinehash/source/thời điểm; không tạo backup trongpublic_html.
3. Tải ZIP về máy, so SHA256 với checksum sidecar được bàn giao, giải nén **local**. Đối chiếu MANIFEST/SHA256SUMS. Chỉ chọn **nội dung runtime/** để upload vào đúng documentroot, giữ đường dẫn; không tạo runtime/ trên host.
4. **Không upload** ZIP/MANIFEST/SHA256SUMS/handoff/manual-review vàowebroot. Không upload docs/tests/scripts/tmp/artifacts/.git/.playwright-cli/MOCK/preview, dashboard.html/reports.html/login-preview.html, filelocalprivate/.env/dump/session. File dư host cầninventory/deny riêng, gói không xóa dư hoặc bảo đảm chúng vắng mặt.
5. `manual-review/.htaccess` là quy tắc source đã duyệt để user so với rulehost/backupriêng, **không auto-overwrite** .htaccesshost. Không có .user.ini/auto_prepend/HSTS mới. Khi cần áp rule ngoài phạm vi đã duyệt hoặc khácvhost, dừng để rà; nếu chưa áp rule phù hợp thì deny/legacy chưa được chứng minh.
6. Giữ ITA_LICENSE_KEY hiện tại tương ứng ciphertext; không dùng keytestlocal. Session có thể cần login lại theo thayđổiquyền/version, không sửa dc_users trực tiếp. Runtime không ALTER/DROP/CREATE/DELETE; grants thực theo checklist, không cấp rộng cho qua lỗi. Isolation của **kết nối ứng dụng** phảiRR/SERIALIZABLE, không lấy phpMyAdmin session thay.

## Kiểm host sau upload — chưa chạy

Chỉ tài khoản TEST/dữ liệu giả trong scope được phép, không SQLghi/seed/reset/nativeSQL. Login ITA có **UPDATE dc_users.last_login; password_hash khi MD5/needs_rehash và ≤72byte**, MD5 password giữ; tài khoản test không dùng chung legacy nếu chưa chấp nhận ảnh hưởng hash. Nhánh login thất bại sai/nonexistent401 cùngmessage, không503; dummy mismatch503 là lỗi cần báo, không hạcost để vượt. Không đo timing để kết luận constant-time.

- Truy cập **https://** đúngvhost; DevTools Set-Cookie ITA_SESSION HttpOnly/Secure/SameSite=Lax vàheaders cuối sauApache/proxy, CSP/no-store/nosniff/Referrer/Permissions, CORS khôngwildcardcredentials; display_errorsOff/log_errorsOn/logprivate. Không chụp giá trịcookie/token/key.
- Ba vai trò: login/logout đểcuối, menu/GETIDngoàiscope/CSRF/quyền, layoutPHPthật1366/768/390. Dashboard/alerts/reportpreview không503, mốc thời gian vàsố liệu theo cohort; Hỗtrợ chỉ maintenance report trongapp, khôngexport.
- Tài sản/ticket/assignment/license/lifecycle/budget/report theo PHASE09/10/11_HOST_TEST vàS26_AUTH_VERIFY; mộtXLSX/PDF dữliệugiả mởlại, tiếngViệt/font, audit theo thao tác. Nhánhquota/import/race/auditrollback chưađược kiểm chỉvì mộtfiletảiđược.
- Deny docs/tests/.git/artifacts/scripts/license/database/assets/ita/mock/config vàURLlegacy theo PHASE11_HOST_TEST; stagingimport ngoàiwebroot ACLriêng. Không executePHPfixture đểprobe. Nếu handlerlegacy cònhoạtđộng, dừngPOSTprobe vàbáo, không thửghi.
- Network live chỉkhi006metadata đãđúng; Adminwriter/Managerhaiđầucùngscope/Supportdeny. Không scanmạng.

Ghi Đạt/Không/Chưa kiểm từngmục, source_commit/góihash/PHPFPM/thờiđiểm/bằngchứngđãche. Báo **«kiểm thủ công trên host, phạm vi hạn chế»**, không tựghi nghiệmthuDB1–10 hoặcmerge/tag. PHPCLI8.3/S26counter/deprecationchưachạy; kiểmUIhost khôngthay đầyđủunit8.3.

## Hoàn tác source

Nếu lỗi, dừng thao tác ghi trong cửa sổ do user quản lý; khôi phục **đúng tệp đã thay** từ backup, .htaccess/marker chỉnếu đãthay; làm mớiopcache/cache bằngcơchếhostingđượcphép. Giữconfig/khóa/upload/audit/snapshot/last_login/hash/dữliệumới. **Không restore dump đè dữliệumới/DROP/resetuser** đểrollbacksource. Bảng006additive cóthểđểlại chưađượcsourcecũgọi; rollbackDDLphảigate riêng. Nếubảncũkhôngđọcdữliệumới, dừngđốichiếutươngthích. Kiểm smoke saurollback, ghi source/hash khôiphục vàphầnchưakiểm.

Hash/size/góithực ghi trong DEPLOY_LOG sau khi builder đọc lạiZIP thật; tài liệu này không điền hashgiả trước khi tạo. Dependency ngoàiGit làbaseline/hash quan sát, Excel/Inter upstream chưaxácminh; khôngtuyênbố đãbackupngoàimáydev hoặcrestoređãkiểm.
