# BE-GATE #11 — Duyệt S11-01/S11-02,(c)/(d),(f)/(g) app và phân trang thống kê; phần còn lại NHÁP

## 26/10/2026 — Bổ sung auth từ checklist trước upload, CHƯA DUYỆT BUILD

PREUPLOAD_SECURITY_VERIFY.md đã đối chiếu20 mục user gửi. Không sửa PHP/auth/credential trong lượt kiểm này. Các đề xuất dưới đây chưa được mở chỉ vì user yêu cầu chạy kiểm thử:

- **S26-01, MD5 giữ sau upgrade:** PmAuth ghi password_hash/last_login nhưng giữ cột password MD5 cho tương thích. Chưa đạt tiêu chí loại bỏ hash yếu. Nếu muốn thay cần khảo sát mọi consumer legacy, quyết định tương thích/cột nào được ghi, backup và kế hoạch chuyển đổi riêng; không tự NULL/reset/xóa credential hoặc đổi dc_*.
- **S26-02, nhánh thất bại khác chi phí:** ItaAuthService thiếu user chạy password_hash+verify, còn user legacy sai mật khẩu có đường MD5 nhanh. Mới là finding từ source, **chưa chứng minh enumeration HTTP thực**. Đề xuất sửa trong ItaAuthService: dummy hash cố định hợp lệ, cân bằng phép verify chậm ở đường thất bại legacy/không có user, giữ message/limiter/long-password/auth contract, không thêm native write. Rủi ro: tăng CPU/latency hoặc bỏ sót nhánh disabled/ambiguous; test actual code với fake transport cho missing/disabled/MD5/modern/sai/đúng/long, đo có giới hạn không tuyên bố constant-time HTTP. Host timing/concurrency do user kiểm riêng. Rollback đúng service source backup, giữ limiter/audit/native credential.

S26-03 deprecation dependency cần kiểm PHP8.3/HTTP bytes, không tự nâng vendor/Composer hoặc che warning. Host-only TLS/grants/Cloudflare/backup/log chưa kiểm; không tự thay server. Chưa tạo gói/upload.

## 23/10/2026 — Duyệt danh sách Quản lý loại Admin

User «Duyệt BUILD danh sách Quản lý loại». GET categories paged=1 Admin-only, COUNT20/search literal code/name, sort name,id; UI Trước/Sau giữ bản sửa/version, reset sau save và guard phản hồi muộn. Dropdown giữ contract mảng và guard500/inactive như trước.69 scripted +24 browser mới +30 browser hồi quy,39/13/174/190 hồi quy/Smarty/lint đạt; review không finding. PHASE11_CATEGORY_PAGINATION_VERIFY.md ghi query/giới hạn. Không DB/DDL/index/config/gói/merge/tag7–10/push/deploy; lookup phân trang khác vẫn chưa mở.

## 23/10/2026 — Duyệt phân trang lịch sử tài sản

User «Duyệt BUILD phân trang lịch sử tài sản». Đã bổ sung COUNT/LIMIT20/OFFSET trên GET asset, sort id DESC, Trước/Sau, guard quyền trước count/list và sequence/generation chặn phản hồi muộn khi đóng/đổi hồ sơ.174 query-contract +43 browser mới/30 browser hồi quy +39 asset/237 ticket/13 routes/Smarty/lint đạt; review độc lập không finding. PHASE11_ASSET_HISTORY_VERIFY.md ghi query và giới hạn hai lần đọc không chung snapshot. Không DDL/index/DB/config/gói/merge/tag7–10/push/deploy; các phần phân trang khác vẫn chưa duyệt.

## 23/10/2026 — Duyệt phân trang thống kê ticket

User «Duyệt BUILD phân trang thống kê ticket». Phạm vi COUNT nhóm và LIMIT/OFFSET cùng scope/bộ lọc, sort ổn định, FE Trước/Sau, giữ tổng toàn tập. Đã build trong ItaTicketObjects + JS/Smarty hiện có; không endpoint mới, index/DDL/config/DB/gói.190 query-contract +237 ticket scripted +43 browser mới +64 browser hồi quy +13 routes/Smarty/lint đạt; review độc lập không finding. PHASE11_TICKET_STATISTICS_VERIFY.md ghi contract, query và giới hạn hai lần đọc không chung snapshot. Chưa EXPLAIN/host/DB; không merge/tag7–10/push/deploy. Không mở phần phân trang khác hoặc auto_prepend/HSTS.

## 23/10/2026 — Duyệt(g) giới hạn trong ứng dụng

User «Duyệt BUILD (g) giới hạn trên». Đã thêm Referrer-Policy strict-origin-when-cross-origin và Permissions-Policy camera/microphone/geolocation=() sau rà source ITA không thấy feature cần dùng.102 HTTP header +56 error +109 guard checks/lint/review đạt, PHASE11_PRIVACY_HEADERS_VERIFY.md. Không HSTS/.user.ini/auto_prepend/server config/DB/deploy. Host cuối/proxy vẫn chưa kiểm; phần(g) HSTS không mở để build.

## 23/10/2026 — Duyệt(f) trong ứng dụng

User «Duyệt BUILD (f) trong ứng dụng». Error boundary HTML/JSON/log/ref/fallback đã build/test local, PHASE11_ERROR_HTTP_VERIFY.md.56 HTTP/Smarty +41 browser, hồi quy/lint/review đạt, không DB. Chỉ sửa trong ứng dụng/gate/module; cấu hình PHP-FPM/log_errors/display_errors host, .user.ini/auto_prepend, HSTS/headers mới và Apache ErrorDocument **không được mở**. F11.4 còn kiểm host, không ghi hoàn tất toàn bộ Phase11 hoặc nghiệm thu DB.

## 23/10/2026 — Duyệt và build riêng(c)/(d)

User «Duyệt BUILD (c) và (d)», yêu cầu kiểm kỹ bảo mật. (c) guard project-start và internal landing/worker đã build (7dba665); (d) deny prefix nhạy cảm trước static trong .htaccess/ita_mode đã build local. Verify75 pure +109 realPHPHTTP fixture +13 routes, lint/review đạt; PHASE11_GUARD_VERIFY.md. Apache/PHP-FPM8.3/host/DB chưa kiểm. Không auto_prepend/.user.ini/header/trang lỗi HTTP, không gói/upload/deploy hoặc merge/tag7–10. Các mục đề xuất bên dưới giữ làm hồ sơ trước phê duyệt, không mở các phần khác.

## Phê duyệt giới hạn22/10/2026 — chỉ S11-01 và S11-02

User nguyên văn: «Duyệt BUILD S11-01 và S11-02 trước». Chỉ hai bản sửa kiểm quyền sau render được mở để build/test local không DB; các mục khác trong draft **chưa duyệt/chưa build**. Không sửa auto_prepend/.htaccess/.user.ini/header hoặc tạo/kết nối DB test, không merge/tag7–10/push/deploy. Các mô tả “chưa sửa/chưa duyệt” bên dưới là trạng thái trước lần duyệt này đối với hai finding; không suy thành duyệt toàn gate.

Kết quả local: S11-01 commit b879282; S11-02 kiểm48 scripted controller,21 forecast,88 import/XLSX,134 forecast hồi quy và13 routing đạt. PHASE11_EXPORT_VERIFY.md ghi phạm vi và giới hạn. **Host/DB chưa kiểm**, không đánh dấu gate11 hoàn tất.

Lựa chọn S11-02 trong phạm vi này: giữ GET và không thêm export audit mới; chặn khi actor/scope đổi, đọc lại tập dòng export sau render và từ chối409 nếu khác; import-errors đối chiếu batch/source/version/expiry. Vì chưa chốt chính sách audit GET riêng, không tự thêm side effect. report-export/template trống không đổi trong bước này; thiếu audit/semantics report ownership được giữ là rủi ro riêng chưa giải quyết.

**Cập nhật chỉ đạo22/10:** không tự tạo/kết nối DB test. User kiểm DB thủ công trên host sau; local chỉ pure/scripted/mock/render trong phạm vi đã duyệt. Các tham chiếu “cần DB test” bên dưới là phương án kiểm đã soạn trước, không buộc user tạo DB test hoặc cho phép agent chạy DB/host. Kết quả DB thật vẫn chưa nghiệm thu; cách kiểm thủ công phù hợp sẽ bàn giao khi user sẵn sàng. Gate vẫn chưa được duyệt/build, không merge/tag7–10/push/deploy.

Theo chỉ đạo22/10: **chỉ soạn tài liệu, chưa build BE**. Không coi việc làm FE/tài liệu hoặc user duyệt #10 là duyệt gate này. Không tự chạy DB/EXPLAIN, cài package, sửa PHP/session/server config. Không merge/tag Phase7–10/push/deploy. DB/host Phase1–10 chưa nghiệm thu.

## Phạm vi đề xuất

- F11.1: đo và đối chiếu query/index theo EXPLAIN trên DB test được xác nhận; kế hoạch PHASE11_LOAD_TEST_PLAN.md. Chưa có index cụ thể được đề xuất khi chưa có bằng chứng.
- F11.2: rà auth/scope/CSRF/upload/export, cookie phiên và security headers theo runtime thật. Source ItaSession hiện đã có strict_mode/only_cookies/HttpOnly, Secure theo HTTPS, SameSite=Lax; **không mô tả chúng là chưa có hoặc tự đổi chính sách**. Cần kiểm headers/cookie thực tế trên8.3.33/HTTPS/proxy phù hợp.
- Bổ sung phân trang theo PHASE11_PAGINATION_AUDIT.md, ưu tiên thống kê ticket và lịch sử tài sản; thiết kế count/scope/sort trước khi code. Bounded-list/array pagination được ghi đúng hiện trạng, không thay guard bằng cắt ngầm.
- F11.4 BE nếu được duyệt sau: nối HTTP status/error envelope/trang lỗi với dispatcher khi cần; FE template hiện có không chứng minh status/routing server.

## Thay đổi dự kiến (chưa thực hiện)

### Phát hiện từ rà chỉ đọc F11.2 ngày22/10 — chưa sửa

S11-01 (PHASE11_SECURITY_REVIEW.md): forecast-export render xong chỉ kiểm lại mapping ITA/revision/scope qua ItaForecastService::lock/auditExport, chưa kiểm lại dc_users active cùng store trước audit/bytes. Đề xuất bổ sung recheck native tại ranh giới export tương tự report-export/handover, từ chối nếu tài khoản bị disable trong lúc render; test scripted giả lập thay đổi trong render và DB thật sau khi được duyệt. Không coi rollback InnoDB bảo vệ bảng native MyISAM. Chưa đổi PHP, chưa thực hiện gate này.

**Bản sửa dự kiến S11-01:** đọc lại native đúng actor.id +store_id và status=active sau render, trước ghi audit thành công và trước trả bất kỳ bytes file; không tồn tại/disable/store khác hoặc lỗi kiểm tra thì fail-closed, bỏ file đã render. Giữ kiểm role/enabled/revision/scope hiện có, không dùng audit thay kiểm native. Test bắt buộc (chưa viết/chạy): render bình thường→file/audit đúng một lần; native bị disable trong render→không bytes file, không audit export thành công; native mất/store đổi→từ chối; query/audit lỗi→không bytes; mapping/scope đổi→vẫn từ chối. Response lỗi kiểm soát không được mang MIME/chữ ký file. Sau khi duyệt mới bổ sung scripted test, DB concurrency thật vẫn cần môi trường được xác nhận.

**Đối chiếu đường xuất khác (chỉ đọc):** report-export có recheck native/role/revision/scope ID sau render, assignment-pdf còn đọc lại asset/document. asset-export và import-errors không có recheck sau render hoặc audit export; import-template cũng không nhưng chỉ là mẫu trống. Đề xuất xét S11-02 cho hai đường có dữ liệu (recheck actor/scope/batch owner-revision trước bytes, xác định audit phù hợp); mẫu trống tách mức rủi ro thấp, chưa mặc định mở rộng audit. report-export chưa đối chiếu lại từng asset chuyển bộ phận trong lúc render; cần chốt semantics snapshot/phạm vi và test riêng, không tuyên bố đã kiểm ownership đầy đủ. Tất cả đề xuất này **chưa duyệt/chưa sửa**, bảng bằng chứng tại PHASE11_SECURITY_REVIEW.

**Bề mặt legacy:** PHASE11_LEGACY_SURFACE.md + CSV2.092 path ghi direct guard khác với bảo vệ có điều kiện qua .htaccess. Điểm mở: MOCK JSON đang allowlist; tests/artifacts không deny prefix tường minh; project-start.php không có gate riêng; auto_prepend rỗng, direct module/upload/library phụ thuộc rewrite. Cần chốt hardening deny trên host và ngoại lệ runtime trước duyệt; không sửa PHP/rule hoặc tự xóa source legacy trong bước này.

F11.4 chỉ **một phần FE**. Phần BE còn lại cần thiết kế trang lỗi HTTP404/500/JSON theo loại request, mã HTTP thật, correlation/log vận hành không chứa secret và chính sách display_errors/log_errors (kể cả lỗi trước bootstrap). ITA module đã có display_errors=0, CSP/no-store/nosniff/X-Robots-Tag; không thêm lại như thể đang thiếu. Header cuối và cấu hình log host chưa xác minh. Không tự thay .htaccess/PHP/server config trong bước tài liệu.

- PHP DAO/services/controllers liên quan ticket statistics, asset history, lookup/aggregate/report pagination; ItaApplication/outer allowlist chỉ nếu thực sự cần endpoint mới và đã liệt kê action cụ thể trước build.
- ItaSession/auth/config middleware và entrypoints nếu kiểm chứng cần sửa cookie/security headers. CSP phải kiểm asset local/ECharts/PDF/download và form; không tự bật một policy gây chặn UI. HSTS chỉ sau xác nhận HTTPS/chứng chỉ/subdomain; không tin X-Forwarded-Proto từ nguồn chưa được xác minh.
- Không đổi compiler/vendor, không Composer mới trong gate dự kiến này. Instrumentation đo PHP memory phải private/tắt mặc định, được duyệt rõ; không tạo endpoint debug công khai.

## Danh mục duyệt riêng — vẫn NHÁP, chưa build

Các mục dưới đây là phương án để review, **không phải patch/config đã áp dụng**. Host test chỉ thực hiện sau triển khai được phép; các tình huống đổi quyền trong render cần DB test được xác nhận. Không thêm debug endpoint hoặc gây lỗi trên production để đo.

### (a) S11-01 — forecast-export thiếu native recheck

- Thay đổi đề xuất: sau render, đọc lại native theo actor ID +store_id +status active trước audit thành công/bytes; giữ kiểm role/revision/scope, lỗi fail-closed. Không trả MIME/chữ ký file khi bị từ chối.
- Rủi ro: native MyISAM không bảo đảm thu hồi quyền nguyên tử cùng InnoDB; thêm query có thể lỗi/tăng thời gian. Không dùng rollback làm bằng chứng native chưa đổi.
- Kiểm host/DB test sau duyệt: baseline xuất được; disable/mất native/store đổi trong render → **không bytes file, không audit export thành công**; query/audit lỗi cũng không bytes; mapping/scope đổi vẫn chặn. Kiểm cả Excel/PDF, không chỉ nút FE.
- Hoàn tác: phục hồi đúng file service/dispatcher/test đã thay từ source backup, giữ DB/audit/snapshot. Việc quay lại bản cũ làm S11-01 mở lại; ghi rõ rủi ro, không restore DB để che lỗi.

### (b) S11-02 — asset-export/import-errors thiếu kiểm lại sau render

- Định nghĩa: hai GET có dữ liệu `asset-export` và `import-errors` chỉ kiểm quyền/scope hoặc batch owner/revision **trước** tạo workbook, không kiểm lại actor/native/scope/batch sau render; hiện cũng không có export audit. Đây là finding riêng, không phải tên khác của S11-01. `import-template` chỉ là mẫu trống, mức rủi ro thấp hơn, tách khỏi finding dữ liệu này.
- Thay đổi đề xuất: recheck native active cùng store, ITA role/revision/scope trước bytes; import-errors kiểm lại owner/session/revision/expiry của batch. Chốt audit export phù hợp trước build (không ghi row dữ liệu/secret vào log); nếu audit được duyệt thì fail-closed khi ghi lỗi. Chốt semantics tài sản đổi bộ phận trong render cho asset-export/report-export; scope ID của actor không tự chứng minh ownership đối tượng hiện tại. Không tự đổi GET→POST trong đề xuất này.
- Rủi ro: thay contract tải file/expiry có thể từ chối workbook đang render; thêm audit sẽ làm GET có side effect audit, cần đánh giá CSRF navigation/rate limit. Không mở rộng quyền hoặc làm snapshot ngân sách thay đổi theo hiện tại.
- Kiểm host/DB test sau duyệt: native/role/scope bị thu hồi trong render, batch hết hạn/revision đổi/khác owner →không bytes; baseline A/M đúng phạm vi và batch của mình tải được, S bị chặn. Audit nếu được duyệt phải đúng một lần và lỗi không trả file. Đối chiếu danh sách dòng khi asset chuyển bộ phận theo contract đã chốt.
- Hoàn tác: phục hồi controller/service từ backup, không xóa audit đã phát sinh/batch/source staging để quay lui. Ghi việc bảo vệ sau render chưa còn hiệu lực nếu rollback.

### (c) project-start.php — thêm project_gate an toàn

- Thay đổi đề xuất: gate trước mọi output khi truy cập trực tiếp, kèm cơ chế include một lần/reentrancy rõ ràng. **Không chỉ chèn require mù**: project_gate hiện gọi project-start.php cho service-worker và chế độ .legacy-hidden; phải giữ hai đường này không đệ quy/không dispatch hai lần.
- Rủi ro: loop redirect/include, worker không unregister/cache cleanup đúng, trang landing legacy khôi phục sai; lỗi trước output làm trắng trang.
- Kiểm host sau duyệt: GET/HEAD project-start trực tiếp không bypass; POST rỗng bị chặn; service-worker.js trả JS hợp lệ đúng một lần, không vòng lặp, login/shell vẫn hoạt động. Test tổ hợp marker trong môi trường cô lập, không bật legacy trên production để thử.
- Hoàn tác: phục hồi project-start và guard liên quan đồng bộ từ backup qua FTP/File Manager; không cần PHP app còn chạy để khôi phục. Giữ nguyên marker đã xác minh.

### (d) Deny tường minh tests/artifacts/scripts/assets/ita/mock

- Thay đổi đề xuất: rule403/404 đặt **trước** static allowlist/catch-all, chặn cả đường thư mục và file con trên vhost InfraCore. Apache có mod_rewrite: rule dự kiến ở đầu .htaccess `RewriteRule ^(?:tests|artifacts|scripts|assets/ita/mock)(?:/|$) - [F,END,NC]`; `Options -Indexes` chỉ khi host cho phép directive đó. Đây là cú pháp đề xuất, **chưa ghi vào .htaccess**. Nếu hosting dùng Nginx/LiteSpeed khác hoặc cấm AllowOverride/directive, quản trị phải dùng rule tương đương được xác minh, không coi mẫu Apache tự có hiệu lực.
- Rủi ro: sai thứ tự vẫn lộ JSON MOCK; directive không hỗ trợ gây500; chặn scripts HTTP là chủ ý, script CLI không được đưa vào runtime. Không block assets production/vendor hoặc dựa directory listing Off để bảo vệ file con.
- Kiểm host sau duyệt: GET/HEAD/POST rỗng thư mục +file an toàn phải403/404, không303/200; CSS/JS/font production vẫn200. Không gọi PHP fixture để probe khi deny chưa chứng minh có hiệu lực. Kiểm không listing và thử URL mixed case theo webserver thực.
- Hoàn tác: khôi phục đúng backup config qua kênh ngoài app; reload/cache bằng cơ chế host được phép, kiểm lại login/static. Nếu deny chưa có hiệu lực, không đưa thư mục nhạy cảm vào runtime; không xóa source legacy để chữa rule.

### (e) auto_prepend_file qua .user.ini — RỦI RO CAO, lớp chiều sâu tùy chọn

- Thay đổi đề xuất: prepend guard riêng bằng đường dẫn tuyệt đối, ngoài webroot nếu host cho phép; guard idempotent, xác định đúng vhost/root/method/marker và không include vòng. Không trỏ mù prepend vào project_gate hiện tại trước khi kiểm reentrancy/worker/legacy compatibility. Cần xác nhận PHP-FPM có đọc .user.ini, phạm vi kế thừa tới subdirectory và open_basedir; không tạo prepend trong bước này. Lớp PHP không bảo vệ static files, vẫn cần deny webserver.
- Rủi ro **cao**: sai path/parse/permission có thể làm mọi request PHP lỗi, mất cả trang login; ảnh hưởng vhost khác nếu scope chung, chạy guard lặp, header/session đã gửi. .user.ini hiện auto_prepend_file rỗng; không thay toàn server/account dùng chung.
- Kiểm host sau duyệt: trước tiên vhost cô lập/staging, xác minh chỉ InfraCore chịu tác động; direct PHP legacy bị chặn dù bỏ qua đường dispatcher, ITA/login/download/worker không lặp. Xác minh giá trị hiệu lực qua phương tiện quản trị private; không phpinfo/debug công khai. Không tắt rewrite trên production để thử.
- **Hoàn tác nhanh:** chuẩn bị sẵn bản .user.ini cũ và kênh FTP/File Manager không phụ thuộc PHP. Khôi phục giá trị cũ (hiện rỗng) trước, **giữ tệp guard tồn tại** đến khi config cache hết hiệu lực; không xóa guard trước làm cached prepend trỏ file mất. `.user.ini` có cache theo `user_ini.cache_ttl` thực tế: xác minh TTL, chờ hoặc nhờ host recycle đúng PHP-FPM pool khi được phép; đổi tên/xóa .user.ini không bảo đảm tác dụng ngay. Sau rollback probe nhiều request/worker. Không tuyên bố “khôi phục tức thì” nếu chưa hết cache/reload.

### (f) HTTP404/500, log_errors/display_errors

- Thay đổi đề xuất: trang lỗi HTTP đúng status cho HTML, JSON envelope cho API, không HTML200 thay lỗi/file; error log private có mã tương quan không secret. display_errors Off/log_errors On ở cấu hình PHP-FPM phù hợp cả lỗi trước bootstrap. Module đã display_errors=0, không mô tả là chưa có. Handler fail phải fallback tối giản, không render lỗi lặp.
- Rủi ro: ErrorDocument/internal redirect làm mất status hoặc đệ quy vào auth; warning phá bytes export; log lộ secret, đầy disk, ACL sai. PHP catch không xử lý mọi parse/startup error.
- Kiểm host sau duyệt: URL không tồn tại/deny, API error/file error đúng MIME/status; log private/rotation, không stacktrace/secret trong response. Lỗi500 có kiểm soát chỉ tạo ở môi trường test/qua observer được phép, không làm hỏng file production.
- Hoàn tác: phục hồi handler/config theo backup; giữ log điều tra private, không xóa audit; lưu ý cache .user.ini nếu dùng và reload pool chỉ khi được phép.

### (g) Referrer-Policy, Permissions-Policy và HSTS có điều kiện

- Thay đổi đề xuất: chọn Referrer-Policy (đề xuất strict-origin-when-cross-origin) và Permissions-Policy tối thiểu cho tính năng không dùng (camera/microphone/geolocation), kiểm cấu hình header cuối tại vhost. **HSTS chưa bật trong source được rà, host chưa xác nhận**; chỉ trình bật khi cả domain phục vụ ổn định HTTPS/chứng chỉ/redirect. includeSubDomains chỉ khi mọi subdomain liên quan cũng đáp ứng; không preload tự động. Không đổi CSP style-src unsafe-inline trong hạng mục này.
- Rủi ro: policy chặn tính năng dùng chung; header trùng qua proxy; HSTS có thể khóa truy cập khi TLS lỗi và trình duyệt giữ cache sau rollback.
- Kiểm host sau duyệt: header cuối của HTML/API/file/lỗi, chức năng hiện có không bị chặn; HTTPS mọi đường, certificate/renewal và domain/subdomain scope được xác nhận trước HSTS. Không coi một request HTTPS thành công là ổn định cả domain.
- Hoàn tác: khôi phục header config cũ; với HSTS phải còn HTTPS hợp lệ để phát max-age=0, client cần nhận phản hồi đó và cache có thể còn. Xóa header server không xóa HSTS đã lưu trên client; preload càng không hoàn tác nhanh. Đây là lý do chưa bật khi chưa đủ điều kiện.

## Migration, rủi ro và hoàn tác chung

**Chưa có DDL/seed/migration.** Index mới sẽ có gate SQL riêng với bảng/cột/thứ tự, index hiện tại, EXPLAIN, ảnh hưởng ghi, backup và hoàn tác. Không ALTER engine/schema cũ, không DROP/reset/restore đè dữ liệu.

Rủi ro: aggregate/count lệch scope, pagination làm đổi tổng/snapshot, lỗi cookie gây mất phiên, headers chặn library/download, đo tải ảnh hưởng hệ thống dùng chung, PHP8.5 PASS không bảo đảm8.3.33. Chỉ DB test cô lập sau xác nhận môi trường; user backup source/DB/khóa riêng. Rollback code/config về bản backup, giữ audit/dữ liệu mới; thao tác DB cụ thể không suy từ rollback source.

## Điểm cần xác nhận trước khi trình duyệt chính thức

### Điều kiện sẵn sàng cho duyệt — không phải phê duyệt

| Hạng mục | Đã có trong draft | Còn phải chốt trước build |
|---|---|---|
| S11-01 | Ranh giới native active/store sau render, không bytes/audit thành công khi lỗi, test và rollback | User duyệt sửa; DB test/thời điểm thu hồi quyền thật chưa có bằng chứng |
| S11-02 | Định danh rõ asset-export/import-errors, actor/batch recheck và giới hạn mẫu trống | Có thêm audit hay không; semantics asset chuyển bộ phận trong render, scope snapshot; không tự đổi GET thành POST |
| project-start guard | Chống lặp, worker/legacy-hidden compatibility, hoàn tác file đồng bộ | Cơ chế reentrancy cụ thể phải được review trước triển khai |
| Deny nhạy cảm | Prefix/method/status, mẫu Apache trước allowlist, không listing | Webserver/AllowOverride/DOCUMENT_ROOT thực tế, inventory host **CHƯA THU THẬP** |
| auto_prepend | Rủi ro cao, phạm vi vhost riêng, TTL/cache/khôi phục ngoài app | Tùy chọn riêng, không mặc định bật cùng deny; cần xác nhận PHP-FPM/user_ini/cache và kênh rollback |
| HTTP/log/header | Phân biệt HTML/API/file/error, log private, HSTS có điều kiện | ErrorDocument/proxy/log ACL và policy cuối chưa kiểm host |
| Phân trang/index/hiệu năng | Inventory và kế hoạch tải có giới hạn | Chọn danh sách/contract cụ thể, EXPLAIN và gate SQL riêng nếu có index; không tự bổ sung tất cả vì duyệt security |

User có thể duyệt từng hạng mục hoặc chỉnh scope; việc hoàn thiện tài liệu không mở quyền build toàn bộ draft. Không yêu cầu deploy/thu listing sớm để hoàn thành phần tài liệu. Không đóng mục chưa có bằng chứng bằng kết quả static/mock. Giữ F11.4 một phần, F11.2 source review xong/host chưa chạy.

1. Môi trường DB test và nguồn xác nhận PHP8.3.33; có sẵn runtime tương thích hoặc cách test CI/build, không tự cài runtime.
2. Thứ tự/lượng danh sách cần bổ sung theo inventory; số dòng/trang/search và total thống nhất. Không mở rộng scope hoặc quyền của3 vai trò.
3. Nguồn bằng chứng isolation của **kết nối ứng dụng**, EXPLAIN/query timings/memory; chưa đo thì ghi chưa đo.
4. Chính sách cookie/header thực tế mong muốn và cấu hình HTTPS/proxy hiện có; không tự siết HSTS/SameSite/CSP khi chưa biết tác động.
5. Giới hạn tải và SLA; có cho instrumentation private hay dùng observer có sẵn; dataset/backup/cleanup do ai thực hiện trên DB test.

Khi các contract trên đã cụ thể, cập nhật draft và trình user **OK / OK với chỉnh sửa / Không**. Hiện **không yêu cầu user duyệt ngay, chưa build BE**. FE responsive/trang lỗi và hướng dẫn sử dụng được làm độc lập, không thay PHP/DB.
