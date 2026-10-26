# F11.2 — Rà soát bảo mật chỉ đọc, 22/10/2026

**Bổ sung26/10 S26-02 sau duyệt riêng:** ItaAuthService đã sửa/test local một verify/401, không native write401; ngoại lệ Argon2 một real verify user chấp nhận, PmAuth không đổi. S26_AUTH_VERIFY.md ghi686counter/query checks và hồi quy, giới hạn không constant-time/HTTPDB/MyISAM/host. Không auto_prepend/HSTS/gói/upload. Phần rà22/10 dưới đây là snapshot lịch sử.

**Cập nhật(g)23/10 sau duyệt riêng:** module/error boundary đã có Referrer-Policy và Permissions-Policy giới hạn camera/microphone/geolocation;102 HTTP local checks đạt, không HSTS/host config. Những dòng “chưa thấy Referrer/Permissions” bên dưới là quan sát22/10 trước sửa. Host cuối chưa kiểm, CSP unsafe-inline/MD5/GET audit và các hạn chế còn lại không đổi.

**Cập nhật23/10:** sau user duyệt(c)/(d), project-start đã có gate; PHP/Apache source đã deny tests/artifacts/scripts/MOCK trước static, không còn allowlist MOCK. Kiểm109 PHPHTTP fixture +75 pure đạt, review độc lập không finding trong diff; **Apache/host chưa chạy**. Những mô tả “MOCK đang allowlist/project-start chưa guard” bên dưới là finding tại thời điểm review22/10, không phải source hiện tại. Vẫn phụ thuộc rewrite cho direct module/library/static khác; không auto_prepend hoặc tuyên bố legacy an toàn. Xem PHASE11_GUARD_VERIFY.md.

**Cập nhật sau review22/10:** user đã duyệt BUILD riêng S11-01/S11-02; hai bản sửa đã kiểm local không DB, xem PHASE11_EXPORT_VERIFY.md. S11-01 native recheck và S11-02 actor/scope/dataset/batch recheck sau render đã có trong code hiện tại. Nội dung finding/bảng source dưới đây giữ làm bằng chứng **trước sửa**, không mô tả trạng thái runtime mới. Không thêm audit GET, không đổi import-template/report-export; các rủi ro đó vẫn mở. Host/DB chưa kiểm/nghiệm thu, các phần khác gate11 chưa build.

## Kết luận và phát hiện

**S11-01 — Mức trung bình: forecast-export thiếu kiểm lại tài khoản native sau render.** `classes/services/itaapplication.class.php:101–109` lấy dữ liệu, render, gọi auditExport rồi trả bytes. `classes/services/itaforecastservice.class.php:7–21,97–110` kiểm lại mapping ITA/revision/scope nhưng không đọc lại dc_users.status/store như `itareportservice.class.php:100` và luồng handover hiện có. Nếu tài khoản native bị ngừng trong lúc render, mapping ITA còn bật, request đang chạy vẫn có thể nhận file ngân sách. Đầu request đã kiểm native qua current(), nên đây là khoảng thời gian thu hồi quyền, không phải truy cập vô danh. Đề xuất kiểm lại native active cùng store trước audit/bytes, lỗi fail-closed; cần test actor bị disable trong render, không bytes/audit thành công. **Chưa sửa, chưa tái hiện DB/concurrency**, đã đưa BE_GATE_11 nháp. Review Agent độc lập xác nhận đường gọi này.

Không phát hiện đầu vào request ghép trực tiếp thành giá trị SQL hoặc sink HTML động không escape trong **phạm vi ITA đã đọc**. Đây không phải chứng nhận toàn repo hoặc kiểm thử xâm nhập. Không đọc private config, dump, session hoặc dữ liệu thật; không chạy PHP/DB/host. Source rà tại HEAD28cefb5 (trước các commit tài liệu lần này); không thay code.

## Phạm vi và cách rà

Đọc dispatcher ItaApplication, module ITA, project_gate/ita_mode/bootstrap; mọi DAO/service/security mang tiền tố ita; adapter PmDb và PmAuth dùng chung; template templates/admin/ita, JavaScript assets/ita/js (không vendor), render Excel/PDF. Tìm `echo/json_encode/header`, biến Smarty, sink innerHTML/insertAdjacentHTML/document.write, mọi fetchOne/fetchAll/execute/query và nhánh SQL động; truy vết các guard theo từng action.

Legacy PHP/modules/templates còn được giữ theo yêu cầu dự án; **không kết luận mọi route/SQL/output legacy an toàn**. `includes/project_gate.inc.php` chạy trước bootstrap admin/ajax; khi ITA enabled và legacy disabled, POST ngoài allowlist bị chặn. Nếu bật `.ita-legacy-enabled` hoặc tắt ITA, đường legacy được khôi phục và ngoài ma trận dưới đây. Hiệu lực rewrite/chặn truy cập trực tiếp ở hosting chưa kiểm; không coi PHP guard thay cấu hình webserver.

## A. HTML/JSON và file đầu ra

| Sink | Bằng chứng source | Kết quả đọc / giới hạn |
|---|---|---|
| HTML/attribute Smarty | layout.tpl.html meta/body; sidebar user/token; login error/token; handoverpdf fields/note | Biến động dùng escape:'html' trong thuộc tính có quote hoặc text; nl2br đặt sau escape. Không nhúng JSON user trực tiếp trong script |
| DOM ứng dụng | shell/admin/asset/import/assignment/license/ticket/lifecycle/forecast/dashboard/errors JS | Dữ liệu dùng textContent/createTextNode hoặc value; tìm không có innerHTML/outerHTML/insertAdjacentHTML/document.write/eval trong JS ứng dụng. SVG/markup template tĩnh; không suy kiểm này sang ECharts/vendor |
| JSON HTTP | itaapplication:272–275, itaimportcontroller:38–39 | Content-Type application/json UTF-8, json_encode/JSON_THROW_ON_ERROR. JSON không cần HTML-escape khi được parse như JSON; không trả JSONP. Module có nosniff/no-store |
| PDF | itaforecastpdf:38–47; handoverpdf.tpl.html; itahandoverpdf:18–31 | htmlspecialchars ENT_QUOTES/ENT_SUBSTITUTE UTF-8 hoặc Smarty escape; chặn HTTP/local resource loader, không lấy URL tùy ý từ user. Font local được phép qua runtime riêng |
| Excel | itaexcel:write | setCellValueExplicit TYPE_STRING mọi ô, không biến chuỗi bắt đầu '=' thành công thức |
| Download headers | application:107–108,198–200,249–251; importcontroller:66–69 | MIME từ enum/hằng; tên tệp cố định hoặc ID/year đã validate, không lấy filename upload chèn thẳng header |
| Lỗi | application:34–38,278–289 | Lỗi nghiệp vụ trả message kiểm soát; Throwable chỉ log câu chung và503, không trả stacktrace từ catch. Chưa bao phủ parse/fatal trước bootstrap hoặc webserver404/500 |

Không dùng việc input đã validate thay cho escape đầu ra. JSON audit/note được parse rồi hiển thị text; key thô chỉ qua endpoint reveal Admin, không nằm trong DTO danh sách/template.

## B. POST và CSRF

Toàn bộ15 action POST được outer allowlist `ita_mode.inc.php:37` liệt kê dưới đây. Không có POST ghi mới trong lần rà này.

| POST action | Guard CSRF trước thao tác |
|---|---|
| login | application:47 requireCsrf($_POST['csrf_token']) trước authenticate |
| logout, access, asset-save, category-save, assignment-save | application requirePost() → itasession requireCsrf; rồi service kiểm quyền/scope |
| ticket-save, license-save, license-key | requirePost trước readJson/service; reveal cũng Admin+reauth+rate-limit+audit |
| forecast-config-save, forecast-plan-save, forecast-export, report-export | requirePost trước tính/lưu/export; export POST cũng kiểm token |
| import-upload, import-confirm | importcontroller:10–16 kiểm phương thức và X-CSRF-Token trước xử lý |

requirePost: application:255–260 kiểm POST rồi token header hoặc form. Token phiên32 byte random, so hash_equals; login xoay token/session. Các method khác bị405 hoặc không vào nhánh ghi. GET lẻ không có cơ chế đổi quyền bằng query.

GET tải file hiện có không qua CSRF; **assignment-pdf có audit**, asset-export/import-template/import-errors **không có audit export** trong đường gọi đã đọc. Không gộp các GET này thành đều có audit. Không phải POST bị thiếu token. Chưa đánh giá tải cưỡng bức/audit amplification qua navigation; SameSite=Lax không chặn top-level GET. Nếu đổi chính sách GET tải file phải trình gate, không tự đổi route trong lần rà này.

## C. SQL ghép chuỗi / prepared statement

`PmDb::fetchOne/fetchAll/execute` đều về prepareAndExecute (`classes/database/pmdb.class.php:62–80`), mysqli prepare/bind_param, kiểm số params/types. PmAuth tái sử dụng adapter này. Không tìm call query trực tiếp trong ITA DAO/service/security; `ItaDb::connect` chỉ query hai SET SESSION hằng (timezone/sql_mode), không có input chèn vào SQL.

| Nhóm SQL động | Nguồn chuỗi ghép và cách kiểm |
|---|---|
| Asset/category/user/audit/assignment/ticket scope/filter/paging | WHERE/FROM/JOIN/ORDER là đoạn source cố định; IN tạo dấu ? theo số ID; ID, tìm kiếm, ngày, trạng thái, offset bind params. Asset warranty operator lấy map cố định >=/<= |
| Asset/import INSERT/UPDATE columns | itaassetservice:75–83, itaimportservice:129–131: columns từ mảng fields do ItaAssetValidation và ItaMaintenanceSchedule tạo; không dùng raw array_keys request. Values đều bind |
| Import reference/UNION | itaimportrows:58–80: private reference chỉ được gọi với hai tên bảng hằng ita_asset_categories/ita_departments; ordinal là index nội bộ chunk. Mã Excel bind, IN chỉ sinh placeholder |
| License SQL tính level/subquery | italicenseobjects: các source/level/scope là expression cố định, input filter bind, LIMIT/OFFSET bind/hằng; không chèn key hoặc license_type raw vào expression |
| Lifecycle CTE/DECIMAL/tier | italifecycleobjects:queryPlans dùng SQL expression cố định, trọng số/ngưỡng đã validate đưa vào params, scope/filter bind; ORDER BY score_raw cố định |
| Forecast | itaforecastobjects:81–104 thay đuôi query bằng ORDER BY id/LIMIT từ hằng MAX_ASSETS, history WHERE bind; không lấy tên cột/bảng/sort từ request |
| Dashboard/report | itadashboardobjects:queryPlans/licensePlan chỉ ghép hai tên cột department_id/category_id từ foreach hằng, placeholders scope; data bind. Report reuse queryPlans, không execute SQL do client gửi |

Không tuyên bố “mọi SQL toàn repository đã prepared”; SQL legacy và vendor ngoài phạm vi. Không chạy EXPLAIN hoặc xác nhận index. Cần kiểm DB thật trước nghiệm thu, đặc biệt MyISAM dc_users không được bảo vệ rollback cùng InnoDB.

## D. Upload

- Boundary: importcontroller:42–49 nhận đúng $_FILES[file], UPLOAD_ERR_OK, is_uploaded_file; không có upload tùy ý khác trong dispatcher ITA.
- itaexcel:106–180: tên .xlsx (case-insensitive, cuối chuỗi), file tồn tại và1–5 MiB; finfo MIME chỉ cho ZIP/XLSX **khi finfo có sẵn**. Nếu extension không có thì MIME bỏ qua, nhưng vẫn kiểm ZIP/cấu trúc. Checklist host cần xác minh fileinfo/zip/DOM, không gọi MIME là vô điều kiện.
- ZIP tối đa200 entries,32 MiB giải nén tổng/24 MiB entry/ratio200 cho entry>1 MiB; từ chối path traversal, encryption, trùng tên, macro/OLE/ActiveX/externalLinks/DOCTYPE/ENTITY/external relationships. DOM parse LIBXML_NONET. Không extract ZIP vào webroot.
- Một sheet,16 cột,5.000 dòng dữ liệu, memory budget; công thức và ô lỗi bị từ chối. Row validate lại theo scope/catalog khi confirm.
- XLSX upload tạm của PHP được đọc, không giữ dưới tên user. Staging JSON tên random48hex, tạo exclusive, thư mục sys_temp/infracore-import-<root-hash> mode0700, kiểm ngoài ROOT_PATH/symlink (`itaimportstorage:8–42`); giới hạn32 MiB. Batch hết hạn2 giờ, store/user/session token/revision gắn batch. Unix mode/ACL và sys_temp có thật sự private trên host chưa kiểm; không có cron cleanup mới.

## E. Route × vai trò, kiểm server

A=Admin IT, M=Quản lý hệ thống, S=Hỗ trợ; ✓ là được qua guard vai trò, vẫn phải store/scope/validation, không phải PASS DB. Tất cả action tại `/admin.php?op=ita&act=…`; không đăng nhập trả401/redirect theo action. GET id ngoài scope bị service/DAO từ chối403, không chỉ ẩn menu; không tồn tại có thể404.

| Method/action | A | M | S | Guard/source chính |
|---|---|---|---|---|
| GET shell (act rỗng), data, asset | ✓ | scope | scope | Auth.current; AssetObjects scope/detail; requireDepartment |
| GET categories | ✓ | ✓ | ✓ | Catalog trong store, không có key |
| GET users, access, audit | ✓ | — | — | UserObjects listing/access, AuditService requireAdmin |
| GET assignments, assignment-pdf | ✓ | scope | scope | AssignmentObjects asset; HandoverService recheck trước bytes |
| GET assignment-recipients | ✓ | ✓ | — | AssignmentObjects recipients role guard; user active cùng store |
| GET licenses, license | ✓ | che key | — | LicenseObjects constructor/access; allocations theo scope. Catalog metadata chung store |
| GET tickets, ticket | ✓ | scope | scope | TicketObjects row→asset→requireDepartment, listing scope |
| GET ticket-technicians | ✓ | scope | — | TicketObjects:75–85 |
| GET ticket-statistics | ✓ | scope | scope + technician=self | TicketObjects:88–104; GET ID vẫn kiểm asset |
| GET lifecycle, lifecycle-detail | ✓ | scope | — | LifecycleObjects constructor + eligible/detail |
| GET forecast, forecast-config, forecast-plans, forecast-plan | ✓ | scope | — | ForecastRules access; ForecastObjects scope/saved. Config đọc catalog không đồng nghĩa được lưu |
| GET dashboard, dashboard-charts, alerts | ✓ | scope | — | DashboardRules access; Support dùng report-preview maintenance |
| GET report-preview | ✓ | scope | chỉ maintenance | ReportService document + DashboardRules access |
| GET import-template, import-preview, import-errors, asset-export | ✓ | scope | — | ImportService ctor; batch owner/session; AssetObjects export guard |
| POST asset-save create/update | ✓ | scope | — | AssetService:14; source/target scope |
| POST asset-save status/note | ✓ | scope | scope | AssetValidation transition; retired chỉ A, pending guard |
| POST asset-save delete; category-save; access | ✓ | — | — | requireAdmin trong service |
| POST import-upload, import-confirm | ✓ | scope | — | ImportService ctor + batch owner/token/revision/confirm |
| POST assignment-save | ✓ | scope | thu hồi/nhận của mình | Assign A/M; return A/M/S scope; acknowledge chỉ đúng to_user_id, Admin không ký thay |
| POST license-save | ✓ | cấp/thu hồi máy,bộ phận trong scope | — | Metadata/key/type/per-user và xác nhận vượt mức chỉ A; override lý do/audit |
| POST license-key | reauth | — | — | LicenseReveal requireAdmin/password/rate limit/audit fail-closed/no-store |
| POST ticket-save create | ✓ | scope | scope | TicketService/Rules; asset lock/pending guard |
| POST ticket-save assign | ✓ | scope | — | Technician membership kiểm server |
| POST ticket-save transition | ✓ | — | scope | Rules chuyển trạng thái; không suy M có quyền xử lý vì có quyền gán |
| POST ticket-save cost/ready | ✓ | — | — | Closed cost bắt lý do; ready kiểm asset version |
| POST forecast-config-save | ✓ | — | — | ForecastService configure requireAdmin |
| POST forecast-plan-save, forecast-export | ✓ | scope | — | ForecastRules/requireDepartment/snapshot/version; finding S11-01 |
| POST report-export | ✓ | scope | — | ReportService export deny Support, CSRF/token/audit/recheck |
| GET login / POST login | mọi người | mọi người | mọi người | Login POST CSRF+credential+limiter+ITA mapping |
| POST logout | ✓ | ✓ | ✓ | Phiên hợp lệ +CSRF, logout trong finally |

GET import-upload không thực hiện upload (trả405). Đối chiếu trực tiếp danh sách dispatch tại itaapplication:164–187 và importcontroller:31–36. Số liệu/chi phí ticket trong scope vẫn được S xem theo code, còn thống kê S giới hạn người xử lý=self.

## F. Cookie, headers, lỗi và logging hiện có

- `itasession:6–16`: ITA_SESSION, use_strict_mode, only_cookies, HttpOnly, SameSite=Lax, session cookie lifetime0/path/. Secure dựa HTTPS hoặc port443; chỉ cho HTTP loopback khi cấu hình riêng bật, không tin X-Forwarded-Proto. Login regenerate_id(true) và CSRF mới; idle30 phút/tối đa8 giờ, kiểm revision/native status mỗi request. Không đọc giá trị config thật.
- `modules/admin/ita.module.php:8–13` **đã** ini_set display_errors=0; no-store/max-age0, nosniff, X-Robots-Tag và CSP default-src none; script self; style self unsafe-inline; img self data; font/connect self; base-uri/frame-ancestors none, form-action self. Không nói headers/CSP chưa tồn tại. Không tìm HSTS/Referrer-Policy/Permissions-Policy/X-Frame-Options riêng trong đường ITA đã đọc; frame-ancestors đã chống nhúng trong CSP.
- Project gate lỗi sớm trả plain text +no-store, không cùng CSP trên mọi đường lỗi. .htaccess có cache rule; chưa đo header cuối cùng sau proxy/Apache/PHP-FPM. Không tự thêm/siết header hoặc đổi SameSite/HSTS.
- application catch Throwable log “ITA request unavailable.”, lỗi kiểm soát không có log vận hành chi tiết; audit nghiệp vụ riêng, không đồng nhất với error log. log_errors/đường log/ACL/rotation trên host chưa xác minh. display_errors tại module không bảo vệ fatal/parse trước khi vào module; cần cấu hình host Off/On theo checklist. Trang HTTP404/500 chuẩn và chính sách log/display_errors vẫn là phần BE F11.4 chưa duyệt.

## Việc còn mở

### Đối chiếu các đường xuất sau render — bổ sung22/10

| Action | Kiểm trước render | Kiểm lại sau render, trước bytes | Audit export |
|---|---|---|---|
| forecast-export | Native current, role/scope; document/snapshot | ForecastService:7–21,97–110 chỉ role/revision/scope; **thiếu native active/store** (S11-01) | Có, nhưng cần chặn trước audit nếu native đã disable |
| report-export | Auth, report quyền, preview token/version/document | ReportService:88–109 kiểm native active/store, role/revision, scope ID; **không đọc lại từng asset đổi bộ phận sau render** | Có, trước bytes, lỗi fail-closed |
| assignment-pdf | Auth/scoped document | HandoverService:31–38 currentActor/native active/role/revision/scope, đọc lại asset/document/version | Có, trước bytes |
| asset-export | Auth.current và AssetObjects::export role/filter/scope | ImportController:19–21,70 không có post-render recheck | Không |
| import-errors | ImportService:42–47 batch store/user/revision/session trước tạo workbook | Không có post-render recheck | Không |
| import-template | ImportService ctor A/M trước render; mẫu trống | Không có post-render recheck; ít dữ liệu nhạy cảm hơn hai đường trên | Không |

Đề xuất S11-02 cho asset-export/import-errors để chốt recheck/audit sau khi user duyệt gate; không coi chúng có mức bảo vệ giống report-export hiện tại. MyISAM native không tạo bảo đảm thu hồi quyền nguyên tử với transaction InnoDB. Luồng report snapshot cũng cần xác định ownership khi thiết bị đổi bộ phận; chỉ kiểm lại scope IDs của actor không đồng nghĩa đọc lại mọi đối tượng. Chưa chạy test thời điểm thu hồi quyền/DB thật.

**S11-02 — Thiếu post-render recheck trên asset-export/import-errors:** tên finding riêng cho hai đường GET có dữ liệu nêu trên; không phải S11-01 và không phải kết luận tất cả export đều thiếu CSRF. Chưa recheck native/role/scope hoặc batch owner/revision sau tạo workbook, hiện không có export audit. Phương án sửa/audit, rủi ro, host test và rollback ghi riêng tại BE_GATE_11 mục(b); chưa được duyệt/chưa sửa. Mẫu import trống tách khỏi finding này.

### Hạn chế chấp nhận được và rủi ro còn lại

“Chấp nhận được” ở đây là giới hạn tương thích đã được mô tả để chờ quyết định/kiểm host, **không phải phê duyệt nghiệm thu hoặc bỏ qua finding**.

Riêng **S26-01 đã được user chấp nhận rõ ngày26/10** để giữ tương thích native như bullet dưới; điều đó không mở việc xóa/reset credential, không chấp nhận thay cho các mục host chưa kiểm và không nghiệm thu DB.

- **S26-01 — Hash native MD5 cũ, user chấp nhận26/10:** giữ MD5 native vì tương thích hệ thống cũ, kể cả password MD5 còn sau khi ghi password_hash. Không xóa/reset/NULL credential hoặc đổi PmAuth/schema trong phạm vi này. Rủi ro dò MD5 khi lộ DB vẫn còn; không tuyên bố toàn bộ tài khoản đã bỏ hash yếu. Credential legacy dài>72 byte không nâng bcrypt gây cắt suffix. Sự chấp nhận giới hạn này không thay nghiệm thu DB/host hoặc mở quyền thay credential.
- **CSP style-src 'unsafe-inline':** giữ tương thích style hiện có/ECharts; script-src vẫn self trong ITA module. Cho phép inline style làm CSP yếu hơn policy nonce/hash chặt; không tự bỏ làm hỏng UI. Project-start có CSP khác, không lấy policy module suy cho mọi URL.
- **GET tải file không CSRF:** assignment-pdf có audit nên top-level navigation có thể phát sinh audit/tải cưỡng bức; các GET import/export nói trên chưa có audit export. SameSite=Lax không giải quyết top-level GET. Quyền server vẫn bắt buộc; đổi method/token là thay đổi BE cần duyệt.
- **Referrer-Policy/Permissions-Policy/HSTS:** chưa thấy header riêng trong đường ITA được rà; không kết luận host không thêm qua proxy. Chưa đặt HSTS khi domain chưa ổn định HTTPS/chứng chỉ; includeSubDomains chỉ cân nhắc khi mọi subdomain liên quan đều HTTPS ổn định, không tự preload. frame-ancestors none đã tồn tại, không gọi thiếu X-Frame-Options là không có chống iframe.

Trạng thái phải giữ rõ: **HSTS chưa bật trong source hiện có; host chưa kiểm**. CSP style-src unsafe-inline, MD5 nền cũ và GET tải file có audit không CSRF (assignment-pdf) vẫn là hạn chế/rủi ro đã nêu, không phải đã khắc phục hoặc đã được nghiệm thu.
- **Phụ thuộc host:** AllowOverride/rewrite, DOCUMENT_ROOT/marker, proxy TLS, PHP-FPM display_errors/log_errors/extensions, temp ACL và deny config/artifact. .user.ini không có auto_prepend bảo vệ toàn bộ PHP. Static MOCK JSON hiện được allowlist nếu file còn. Các điểm mở chi tiết tại PHASE11_LEGACY_SURFACE.md; không kết luận legacy an toàn.

**Trạng thái F11.2: rà soát source xong; kiểm thử trên host chưa chạy.** Inventory PHP/ops và checklist host bổ sung tại PHASE11_LEGACY_SURFACE.md, PHASE11_PHP_SURFACE.csv và PHASE11_HOST_TEST.md. Không thay kết quả source review thành PASS host.

Đưa S11-01 và phần HTTP/log/header vào BE_GATE_11 nháp, không tự sửa. Cần kiểm PHP8.3.33/HTTPS thật, tài khoản thật từng vai trò, CSRF sai/thiếu, GET ngoài scope, upload xấu, header/cookie cuối, thu hồi quyền lúc render, log không lộ secret. Lần này chỉ đọc source và rà độc lập; không gắn PASS cho các kiểm tra đó. DB/host chưa nghiệm thu; không merge/tag7–10/push/deploy.
