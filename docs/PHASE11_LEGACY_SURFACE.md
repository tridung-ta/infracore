# Bề mặt PHP/legacy — chỉ đọc, 22/10/2026

Điều kiện phân tích: `.ita-enabled` có, `.ita-legacy-enabled` không có. **Chưa kiểm host**, không kết luận legacy an toàn. Không chạy các entrypoint hoặc đọc config riêng/dump. Rule được đọc trong .htaccess:7–19, ita_mode và project_gate. Inventory từng đường dẫn tại PHASE11_PHP_SURFACE.csv: **2.092 tệp PHP** quan sát trong cây local (7 root,1.678 classes,350 modules,20 includes,8 cron,5 scripts,22 tests,1 database,1 license). File CSV là danh sách bề mặt có thể yêu cầu trực tiếp, không khẳng định mọi class là endpoint độc lập.

Loại khỏi inventory: .git, .source-backup, .cache, .playwright-cli, tmp, artifacts, templates_c, node_modules (bản sao/generated/development). Các thư mục đó vẫn phải bị cấm truy cập trên host, không phải được phép vì không có trong inventory. Không liệt kê nội dung hoặc giá trị secret. Inventory gồm cả dependency/classes, không chứng nhận thư viện không có script thực thi trực tiếp. `local_guard_evidence` phân biệt guard đã đọc với guard chưa truy vết từng file; không suy mọi module tự có project_gate.

## Các điểm vào chính

| Điểm vào / URL | Gate riêng | GET khi ITA bật/legacy tắt | POST khi ITA bật/legacy tắt | Nếu rewrite không hoạt động |
|---|---|---|---|---|
| admin.php?op=ita | project_gate ngay đầu | Shell/action ITA theo auth/quyền, không vào admin legacy | Chỉ15 action allowlist, rồi method/CSRF/quyền | Gate vẫn chạy khi file thực thi |
| admin.php?op=login/logout/editorial/pm/pmusers/pmprojects/pmtimesheets/pmaudit/pmcosts/pmallocations/pmreports/pmimports/pmnotifications | project_gate trước bootstrap |303 về ITA, legacy op không chạy |403 | Gate vẫn chặn; admin.php:30–42,111–131 là dispatch cũ sau gate |
| ajax.php?op=… (mọi op) | project_gate đầu file |303 về ITA |403 | Gate vẫn chạy, không tới include modules/ajax/<op>.module.php |
| index.php?op=… / các URL public rewrite cũ | project_gate đầu file |303 về ITA |403 | index.php vẫn có gate, nhưng URL static/direct module khác không được bảo đảm |
| pm_ajax.php?op=pmcosts/pmallocations/pmtaskstatus | project_gate đầu file |303 |403 | Không tới PM endpoint khi gate có hiệu lực |
| pm-launch.php | project_gate đầu file |303 |403 | Gate trước nội dung PM |
| router.php (PHP built-in server) | project_gate đầu file |303 theo path gốc; admin.php?op=ita được ITA dispatch |403 ngoài ITA allowlist | Chỉ dành local; production không tự chạy router.php |
| project-start.php | **Không có project_gate riêng** | Qua rewrite:303 về ITA. Ngoại lệ worker: service-worker.js được gate chuyển sang landing worker | Qua rewrite:403 | Truy cập trực tiếp có thể trả landing/worker tĩnh200 cả POST, không method guard; không ghi DB nhưng không đạt yêu cầu chặn mọi legacy entry |
| /mobile, /mobile.php, ?op=mobile | Không thấy mobile.php/thư mục mobile hoặc modules/mobile trong inventory hiện tại | Qua rewrite/gate:303 |403 | Không có file local để xác nhận; không suy host không còn bản cũ. Cần thử URL trên host |

`.htaccess` dùng marker từ DOCUMENT_ROOT và catch-all rewrite sang admin.php, **kể cả file có thật**, trước rule legacy phía dưới. `itaRequestDecision` xét URI gốc: /includes/classes/modules/database/docs/templates/templates_c/cron và file ẩn →404; POST path khác →403; GET path khác →303. `admin.php?op=ita` mới vào shell; chỉ allowlist POST mới đi tiếp. .git/.source-backup/license có rule403 riêng ở đầu .htaccess.

## Module, upload handler và bề mặt phụ

| Nhóm | Từng đường nằm ở | GET | POST | Guard còn thiếu/giới hạn |
|---|---|---|---|---|
|350 module PHP, gồm admin/ajax/public | Mỗi path trong CSV, group=modules |404 qua catch-all→gate vì prefix/modules |404 qua cùng gate | Không coi include guard admin/ajax là guard riêng cho URL module. Phụ thuộc Apache rewrite khi truy cập trực tiếp |
| Upload cũ | modules/admin/manage/uploadadd.module.php, uploadedit.module.php, uploadcleantrash.module.php, uploadsymboliclink*.module.php, uploadimgsymboliclinkedit.module.php; applyrecruitment.module.php và các module có $_FILES/move_uploaded_file | URL /modules/...:404; qua admin/ajax cũ:303 | URL /modules/...:404; qua admin/ajax cũ:403 | Không gọi upload cũ để thử. Không xác nhận MIME/path/CSRF của legacy upload an toàn |
| Các handler upload nhúng khác | articleadd/edit, csauadd/edit, imgvaria, importdata/importdata1, pdfadd/edit, productadd/editaccessory, productadd/editoption, recruitmentadd/edit, specificationsadd/edit, staticadd/edit (modules/admin/manage) |404 trực tiếp nếu rewrite đúng |404 trực tiếp nếu rewrite đúng | File không cần tên “upload” vẫn là bề mặt; danh sách filename đầy đủ trong CSV |
| classes/includes/library PHP | group classes/includes trong CSV |404 qua gate |404 qua gate | Khi webserver bỏ rewrite, không bảo đảm từng file có guard; config không được đưa ra web |
|8 cron | group cron; đều require project_gate đầu file theo scan |404 |404 | CLI và HTTP khác nhau. Không chạy smoke/cron để rà; không coi toàn bộ cron có cùng CLI guard |
|5 scripts | check_ita_license, ita_phase08/09_fixture, ita_phase08/09_explain |303 theo catch-all vì /scripts không nằm regex cấm |403 theo gate | Có guard PHP_SAPI CLI-only riêng trả404 nếu thực thi HTTP trực tiếp; không deploy scripts/fixture |
|22 tests /1 database test /1 license PHP | CSV | /database:404; /license:403; /tests hiện303 | /database:404; /license:403; /tests403 | Tests không có bảo đảm guard riêng; không deploy. Không thử reset/fixture trên host |

Mọi đường file đã quan sát được liệt kê trong CSV; bảng nhóm áp dụng chung theo prefix, **không** thay audit logic riêng bên trong từng module/dependency. Ops được sinh thêm bởi nền legacy không được coi là kiểm quyền ITA; khi op khác/không hợp lệ, ITA gate chặn trước khi dispatcher legacy xử lý.

## Rủi ro còn mở

1. **Phụ thuộc rewrite/AllowOverride/marker đúng DOCUMENT_ROOT.** .user.ini hiện auto_prepend_file rỗng; không có lớp prepend toàn cục buộc mọi PHP qua gate. Sai document root, subdirectory, Apache rule bị bỏ qua, alias/vhost khác có thể làm direct module/library/upload cũ chạy ngoài gate. Không thay config trong lần rà này.
2. **MOCK được allowlist static hiện tại:** assets/ita/mock/*.json (và css/js/woff2 phù hợp regex) đi thẳng qua .htaccess/ita_mode. Thư mục có nhãn MOCK không đồng nghĩa bị chặn. Vì vậy phải loại khỏi deploy; nếu vẫn có file, bài test deny /assets/ita/mock/overview.json sẽ không đạt theo source hiện tại. HTML dashboard.html/reports.html không nằm allowlist extension nhưng vẫn không được đưa lên host. Không tự xóa tệp hoặc sửa rule.
3. **/tests, /artifacts không có deny prefix tường minh trong ita_mode.** GET thường303, POST403 qua catch-all; chưa phải policy deny403/404 đồng nhất. Nếu rewrite không chạy, artifact tĩnh/test PHP có thể bị lộ/thực thi. Cần loại khỏi runtime và kiểm host; hardening deny cần BE gate.
4. project-start.php không có gate riêng, phụ thuộc rewrite; nội dung tĩnh không làm nó thành endpoint được duyệt cho mọi method. Mobile/old handler có thể còn trên host ngoài cây local; cần inventory host do user cung cấp, không suy từ repo.
5. Dotfile/config/dump: rule chặn trong source không chứng minh proxy/Apache cuối đang áp dụng. Không mở file secret để lấy bằng chứng; cần probe status mà không in/lưu body nhạy cảm.

Những mục này nằm trong BE_GATE_11 nháp để xác định giải pháp sau phê duyệt. Hiện F11.2 **rà source xong, kiểm host chưa chạy**, không kết luận legacy an toàn, không sửa PHP/.htaccess/.user.ini/DB, không merge/tag7–10/push/deploy.
