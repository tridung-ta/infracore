# Nhật ký thao tác hosting

## Trạng thái host hiện tại — rà hồ sơ 21/10/2026

Không kết nối host trong lần rà này. “Ngày bằng chứng” là ngày lời báo/ảnh hoặc ngày tạo gói local, **không tự coi là ngày upload**. Chưa có inventory/hash source đang chạy trên host. Các ghi chú lịch sử bên dưới không thay thế bảng trạng thái này.

| Hạng mục / gói hoặc commit | Ngày bằng chứng / upload | Trạng thái xác nhận | Chưa upload hoặc chưa biết |
|---|---|---|---|
| Đăng nhập/đăng xuất ITA trên host, username và Gmail | User báo16/10; ngày upload chính xác chưa cung cấp | User xác nhận thao tác host thành công | Gói/commit đã upload chưa xác định; không coi là DB UAT1–9 |
| Ticket trên host: tạo/gán/resolved/xác nhận sẵn sàng/thống kê | Ảnh user21/10; ngày upload chưa xác định | Có bằng chứng thao tác và đọc lại một thiết bị/phiếu | Chưa xác nhận commit/gói hiện chạy; không suy toàn bộ source trùng commit local |
| infracore-host-through-phase07-80929beff9bb.zip; source80929beff9bb | Gói local ghi20/10 | Đã chuẩn bị/kiểm gói trong lịch sử | Chưa nhận xác nhận user upload đúng ZIP/hash này |
| ita-ticket-picker-update.zip; source2aa3c98179e5 | Gói local21/10, source đọc lại từ MANIFEST | Có gói local | Chưa có xác nhận upload đúng gói; agent không upload |
| ita-lifecycle-update.zip (gói cũ), thay bằng ita-lifecycle-ui-update.zip; source1691c43bd37d | Gói local21/10; manifest gói UI đã đối chiếu | Gói UI là bản bàn giao local mới hơn | Chưa có xác nhận upload/hash host của Phase8; không upload cả hai gói |
| Ngân sách source2e95245; docs checkpoint9b32bae | Commit local21/10 | Đã build/verify local | Chưa tạo gói Phase9 trong phiên này; chưa có xác nhận upload |
| Phase10 | Chỉ tài liệu gate, cập nhật21/10 | **BE chưa duyệt, chưa build** | Không có runtime Phase10 để upload |

DB Phase1–9 **chưa nghiệm thu**; không merge/tag7/8/9. Để cập nhật bảng, user cung cấp gói/commit/hash và ngày upload thực; nếu không biết ghi chưa xác định, không dựng ngược từ ảnh UI hoặc kết quả mock. Việc đăng nhập hoặc một luồng ticket thành công không xác nhận CHECK/rollback/concurrency.

## Đề xuất dựng lại dependency Excel/PDF/font — chưa thực hiện

Đã kiểm tra local và `git ls-files` ngày21/10/2026. Đây là hồ sơ đề xuất, không cài/cập nhật dependency, không đổi loader/Composer hoặc tạo gói mới trong bước tài liệu này.

| Thành phần | Bằng chứng hiện có | Git thực tế | Cách dựng lại đề xuất / phần còn thiếu |
|---|---|---|---|
| PDF | classes/ita-pdf/composer.json ghim mpdf/mpdf8.3.1, composer.lock khóa8 package; README ghi platform host8.3.33 | composer.json, lock, README được tracked; vendor hiện có local nhưng **không tracked** | Dùng đúng json/lock tại source commit, Composer install trên máy build với đúng PHP/extensions; so package name/version/source-ref/dist-ref với lock, kiểm platform/audit, kiểm file thật; đóng vendor/font/license vào gói offline có hash |
| Excel legacy | classes/PhpSpreadSheet có566 file ở bốn namespace PhpOffice/MyCLabs/Psr/ZipStream; không tìm thấy composer.json/lock/installed metadata trong cây này | **Chưa tracked** qua git ls-files; không gán phiên bản upstream từ phỏng đoán | Trước mắt đề xuất lưu bản baseline legacy đã kiểm trong kho artifact riêng với inventory SHA256/file, archive hash và nguồn lấy. Bản baseline định danh bằng tree hash, không phải phiên bản upstream. Cần truy ra bản phát hành/commit và license tương ứng; nếu không truy được thì ghi unknown. Chuyển sang Composer hoặc nâng bản là gate riêng, không làm ở đây |
| Font Inter | assets/fonts/inter/InterVariable.woff2 + OFL.txt có local; OFL nêu The Inter Project Authors và github.com/rsms/inter | Hai file **chưa tracked**; phiên bản/tag/commit nguồn chưa xác minh | Đề xuất hồ sơ nguồn gồm tên font, tag/commit thực đã kiểm, URL artifact, SHA256 font/archive và license. Có thể phục hồi baseline từ artifact được hash hiện tại; chưa đủ dữ kiện để hứa tải lại đúng byte từ upstream. Không dùng latest hoặc thay font im lặng |
| Font PDF DejaVu | DejaVuSans.ttf đang trong vendor mPDF; font đi cùng runtime đã khóa | Vendor/font này **không tracked** | Phục hồi theo package mPDF đã khóa và đối chiếu hash font/license trong manifest, không lấy bản font hệ thống khác thay thế |

Các hash **quan sát local**, không chứng minh host đang có cùng file hoặc nguồn upstream đã được xác minh:

| File / bộ | SHA256 |
|---|---|
| classes/ita-pdf/composer.json | d3691a3f94f2d38193f814b26c2649b7cd63385acbd942f0cfcb956dd04e0cd1 |
| classes/ita-pdf/composer.lock | 2e8a16cf22e5d0e54efb8109a6404db97058679aea87b9668fcc9d466d4581aa |
| assets/fonts/inter/InterVariable.woff2 | 693b77d4f32ee9b8bfc995589b5fad5e99adf2832738661f5402f9978429a8e3 |
| assets/fonts/inter/OFL.txt | b3195af0fb14368d1b3b10fb9d3fe503b7163ea083859d2ee553bc74da07c320 |
| vendor mPDF ttfonts/DejaVuSans.ttf | 08ca98e69d9d8fa1065584b4f9ab7d49b6205abea6572b90e171b254845bb990 |
| Manifest cây Excel566 file, quy tắc bên dưới | 888154326da0fc145d3e6d0d6810818c63881882fa0819ad7f77f93ef2dbe797 |

Quy tắc tree hash Excel: lấy mọi file trong classes/PhpSpreadSheet, sắp xếp đường dẫn tương đối POSIX theo thứ tự chuỗi; mỗi dòng là `sha256_hex_thường` + hai dấu cách + `relative_path` + LF. SHA256 của chuỗi manifest UTF-8 đó là hash bảng trên. Đây không phải hash ZIP. Bản source/dependency mới cần manifest riêng, không sửa baseline cũ.

Quy trình đề xuất trên máy build tách biệt: lấy source commit và lock cố định → khôi phục baseline Excel/font đúng hash → chạy Composer trong classes/ita-pdf với `install --no-dev --prefer-dist --no-scripts --no-plugins`, `check-platform-reqs --no-dev`, `audit --locked --no-dev --no-plugins` → so lock với installed → chạy render/reopen Excel/PDF tiếng Việt và kiểm formula-like STRING → gom license/font/runtime, tạo manifest phiên bản/nguồn/kích thước/SHA256 từng file và hash ZIP → đọc lại gói kiểm hash → user giữ gói/manifest/backup ở kho riêng trước upload. Không Composer trên production, không ignore-platform-reqs, không mang config/key/dump/session vào gói.

File ghi nguồn đề xuất (chưa tạo/cài runtime): dependency name, version hoặc unknown, source URL/commit hoặc nguồn baseline legacy, lock SHA256, artifact SHA256, inventory path/hash, license path/hash, ngày thu thập, source commit, lệnh build/verify và giới hạn. Có thể track hồ sơ và lock trong Git, còn payload binary/vendor giữ artifact riêng có checksum; chỉ công bố phục hồi đầy đủ khi đã thực sự dựng lại và kiểm. Script package_ita_pdf.py hiện có chỉ là delta PDF, không thay quy trình phục hồi toàn app hoặc tự chứa Excel/Inter.

## 21/10/2026 — Checklist host trước kiểm thử Phase 1–9 (chưa thực hiện)

Đây là checklist cho user/quản trị host, không phải kết quả kiểm tra hoặc quyền deploy. Phase 1–9 giữ trạng thái DB **chưa nghiệm thu**; không merge/tag Phase 7, 8, 9 trong lúc chờ xác nhận cụ thể. Không import lại migration hoặc tạo DB từ checklist này.

- [ ] Ghi môi trường đích, ngày kiểm, **phiên bản PHP thực của site/PHP-FPM** và phiên bản PHP CLI nếu dùng CLI. Đối chiếu khả năng chạy source và thư viện hiện có; CLI chạy được không chứng minh PHP-FPM có cùng cấu hình.
- [ ] **display_errors tắt khi tải Excel/PDF** trên đúng site/PHP-FPM; lỗi ghi vào log riêng được bảo vệ, không chen cảnh báo/HTML vào bytes tải. Kiểm MIME/chữ ký và mở lại file thật. Cảnh báo deprecation PhpSpreadsheet/ZipStream local còn được ghi nhận, chưa nâng vendor; không tắt toàn bộ error_reporting để che lỗi.
- [ ] **gd + mbstring** được nạp trong PHP-FPM phục vụ site để xuất PDF tiếng Việt; kiểm thêm dependency hiện có của Excel và OpenSSL/AES-256-GCM cho giấy phép. Bật GD per-process local trước đây không xác nhận extension trên host.
- [ ] Ghi phiên bản MySQL/MariaDB, engine các bảng liên quan và **transaction isolation của kết nối ứng dụng: REPEATABLE READ hoặc SERIALIZABLE**. Writer ngân sách từ chối isolation khác trước ghi. Không tự đổi cấu hình DB server. Kiểm rollback khi audit lỗi và hai phiên đồng thời trong môi trường/phạm vi user đã xác nhận phù hợp; ghi kết quả, không suy từ mock.
- [ ] **ITA_LICENSE_KEY** đã đặt cho đúng PHP-FPM pool/site, là base64 giải mã đúng32 byte và OpenSSL hỗ trợ AES-256-GCM. Dùng hướng dẫn PHASE06_HOST_TEST.md và script check_ita_license.php; kết quả CLI không thay kiểm môi trường PHP-FPM. Không in khóa vào chat, ảnh, Git, HTML hoặc log; không tạo khóa production tự động hay thay khóa đang dùng để chữa lỗi.
- [ ] Backup khóa giấy phép riêng, ngoài Git/document root và tách khỏi dump DB; backup source/DB trước triển khai. Kiểm khả năng khôi phục khóa tương ứng với ciphertext, không dùng khóa dev cho host.

User gửi xác nhận kiểm thử DB cụ thể Phase1–9 (môi trường, phiên bản, engine, CHECK ticket_events, rollback/audit, hai phiên, các mục chưa kiểm) rồi mới cập nhật nghiệm thu. Agent chưa chạy checklist, chưa đọc secret, chưa deploy. Phase10 chỉ có tài liệu BE-GATE, chưa bắt đầu BE.

## 20/10/2026 — Gói upload cộng dồn đến Phase7 theo yêu cầu user

User xác nhận chỉ kiểm được trên host với DB hiện có, yêu cầu bắt đầu chuẩn bị gói. Agent chỉ đóng gói local, không upload/DB/merge/push. Source runtime cố định `80929beff9bb0675b19428df63d6f4316a706caf`, dùng cho host Phase2 đã đăng nhập được.

- ZIP: `artifacts/ita-phase07/infracore-host-through-phase07-80929beff9bb.zip`, 48.553.362 byte; 1.340 runtime files +6 handoff files và MANIFEST.json.
- SHA-256: `128748eb34c3c59beac605c19335642cac119df23c383d4f283d282b3e0d8f9b`. Đã đọc lại inventory, CRC và hash từng mục. Manifest ghi source commit, nguồn dependency và hash; PDF vendor khớp composer.lock. Excel dependency/font là snapshot bộ cục bộ hiện có, không tuyên bố chúng tracked trong source Git.
- Không chứa private config, auth/key secret, SQL/dump, session/log, dữ liệu MOCK, tests. Không mang workbook Translations.xlsx dùng duy trì dịch thuật của thư viện. Bao gồm dependency Excel/PDF/font, không cần Composer hoặc Node production.
- User giải nén và upload **nội dung runtime/** vào `/domains/infracore.dung.derasoft.com/public_html`, giữ cấu hình host, .ita-enabled và mã nền admin.php/Smarty/PmAuth/PmDb hiện có. Không upload handoff/manifest/ZIP. Hướng dẫn backup/rollback và điều kiện PHP/khóa: HOST_UPLOAD_PHASE07.md.
- Bằng chứng user mới: selected_database=dung_infracore, MariaDB10.6.28, check_constraint_checks=ON trong phpMyAdmin, 5 bảng chính đều InnoDB. Đây chưa phải DB UAT nghiệp vụ/rollback/concurrency hoặc CHECK enforcement của PHP session. Chưa merge Phase7; chưa sang Phase8.

## 20/10/2026 — Ticket local, chưa triển khai hoặc merge

User duyệt #07 kèm chỉnh sửa và #07-SCHEDULE. Đã build/kiểm local ticket, guard mọi writer, lịch ban đầu và periodic, lịch sử gộp/statistics. Không DDL, không SQL host, không tạo/kết nối DB hoặc upload. Commit lịch ban đầu ddc9c87; runtime ticket lưu trên nhánh feature/ita-phase07-tickets, không merge trước user UAT DB Phase1–7. Host/CHECK/EXPLAIN/concurrency chưa kiểm chứng. Checklist PHASE07_HOST_TEST.md; không dùng gói Phase5 cũ hoặc copy toàn repo để deploy. Chưa tạo gói phát hành mới.

## 20/10/2026 — PDF hoàn thiện local, chưa deploy host

BE-GATE #05-PDF được user duyệt 19/10, mPDF8.3.1 +7 dependency khóa riêng; GD local chạy per-process, không sửa php.ini hoặc server. F5.4 render/PDF text/font/pages/signatures, adapter scope/audit và browser mock đã kiểm; host/DB/download thực chưa UAT. Không migration/SQL mới.

User đã cho phép origin và push nhánh đang làm: checkpoint bfe3539 đã push feature/ita-phase05-pdf; bản PDF được commit/push riêng sau kiểm chứng. Đây là lưu mã, không deploy production hoặc merge main.

Builder scripts/package_ita_pdf.py tạo delta local từ source committed, gom vendor/font/license có manifest/hash và kiểm đọc lại; chỉ dùng khi user chủ động yêu cầu bàn giao. Delta phụ thuộc checkpoint, không phải bộ cài toàn app. Rollback code: khôi phục source/vendor cũ đã backup, giữ assignment/audit/DB; không DROP/restore dump đè dữ liệu hoặc sửa config host. Host cần GD/mbstring/zlib, temp ngoài webroot ghi được và runtime dependency đầy đủ; Composer chạy ở máy build, không production.

## 19/10/2026 — Phase 5 chỉ build local

BE-GATE #05 đã duyệt, code phân bổ/thu hồi/acknowledge/history/maintenance badge đã nối; không migration mới, không cài PDF dependency, không DB/host/deploy. Source còn working tree, chưa có release/manifest Phase5. Không upload toàn repo hoặc dùng gói Phase3 cũ. PHASE05_VERIFY.md ghi kết quả pure/scripted/browser mock; PHASE05_HOST_TEST.md ghi UAT còn lại. Không tạo PDF giả hoặc công bố phase-done.

## 19/10/2026 — Phase 4 chỉ build/verify local

User đã duyệt BE-GATE #04. Code F4.1–F4.5 có trong working tree; không tạo DB, không chạy SQL host, không upload hoặc tạo gói phát hành mới. Chưa có manifest/release cho Phase 4. Giữ private config host riêng, không copy toàn repo. `PHASE04_VERIFY.md` ghi bằng chứng local; `PHASE04_HOST_TEST.md` là checklist khi user sẵn sàng UAT. Không dùng gói Phase 3 cũ bên dưới cho thay đổi mới. DB transaction/collation/concurrency/performance chưa nghiệm thu.

## 19/10/2026 — Bàn giao Phase 3, chưa deploy

**Đính chính sau chỉ đạo user:** chưa upload production. Snapshot `b965bd30a9b4` dưới đây là bản cũ, không còn gồm các sửa local cuối (status required/category failure). Không dùng gói này để deploy; chờ user chủ động bắt đầu bàn giao sau. Phạm vi local đã kiểm 33 pure/scripted +30 browser API mock, không DB.

- User duyệt build và không tạo DB test. Agent không truy cập DB host hoặc thực hiện deploy.
- Snapshot delta: `artifacts/ita-phase03/infracore-phase03-b965bd30a9b4.zip`, 14 runtime files; archive SHA-256 `f00ab1f317b38671d8a686b4ceceacb43e3d8e0d90423cedfd36df1915d58248`. Bên trong có manifest SHA-256 từng file, đã đọc lại zip và đối chiếu hash.
- Snapshot từ working tree đã review, chưa phải release/tag phase-done; runtime còn chờ UAT SQL/read-back thực. Không thay cấu hình private hoặc auth_key; package không có config, dump, tests, session hay seed.
- Gói chỉ cập nhật host đang chạy Phase 2. User backup DB/source, giải nén trên máy và upload **nội dung runtime/** vào `/domains/infracore.dung.derasoft.com/public_html`, giữ đường dẫn, không tạo thêm thư mục runtime trên host. Không upload docs/manifest vào web root.
- Không cần migration mới hoặc import lại Phase 1/2. Hướng dẫn PHASE03_VERIFY.md và checklist PHASE03_HOST_TEST.md. Nếu lỗi, dừng ghi và khôi phục runtime từ backup, giữ dữ liệu mới; không DROP/DELETE/import dump làm lối tắt.

## Xác nhận từ user sau import SQL host

Ngày 16/10/2026: user báo «đã thành công» sau hướng dẫn chạy phase01_verify.sql. Ghi nhận user xác nhận tạo bảng trên dung_infracore/MariaDB hosting thành công; agent không kết nối host và chưa nhận ảnh bộ đếm 12/12/12 hoặc danh sách đủ 11 khóa ngoại sau lần import cuối. Không suy diễn xác nhận này thành quyền seed, sửa PHP/routing hoặc deploy. Migration F1.1–F1.4 đã bàn giao và được user xác nhận chạy; ứng dụng chưa tích hợp DB.
## Đính chính xác nhận hosting

Ảnh user gửi tiếp theo cho thấy selected_database=dung_infracore nhưng truy vấn metadata dùng DATABASE() trả 0/NULL/NULL, không thấy FK. Chưa xác nhận tạo đủ 12 bảng trên host; kết luận thành công trước đó chỉ dựa trên lời báo của user và được thay bằng «chờ chẩn đoán». Trước đó import có thông báo #1050 ita_departments already exists. Chưa rõ nguyên nhân hai quan sát khác nhau; không mặc định bảng bị mất hoặc migration thất bại. Đã thay bộ lọc metadata trong preflight/verify bằng tên schema cố định để giảm phụ thuộc ngữ cảnh truy vấn. Cần SHOW TABLES và SHOW CREATE TABLE với schema chỉ định rõ. Không chạy lại CREATE hoặc xóa bảng khi chưa đối chiếu.
## Bằng chứng SHOW TABLES trên host

User gửi ảnh SHOW TABLES FROM dung_infracore LIKE 'ita%': liệt kê đủ 12 tên bảng đúng migration. SHOW CREATE TABLE dung_infracore.ita_departments trả về cấu trúc nhưng ảnh bị rút gọn. Đã xác nhận tồn tại đủ bảng; không chạy lại CREATE. Chưa xác nhận toàn bộ cột/engine/collation/11 FK qua ảnh này. Nguyên nhân truy vấn information_schema trước đó dùng DATABASE() trả 0 chưa xác minh; không kết luận bảng chưa được tạo. File verify đã dùng tên schema cố định.
## Xác nhận số lượng khóa ngoại trên host

Ảnh mới từ user cho thấy truy vấn information_schema.TABLE_CONSTRAINTS với CONSTRAINT_SCHEMA='dung_infracore' và loại FOREIGN KEY trả tổng 11 dòng. Kết hợp SHOW TABLES trước đó: đã xác nhận đủ 12 tên bảng và 11 khóa ngoại trên MariaDB host. Chưa có ảnh bộ đếm engine/collation 12/12/12 hoặc toàn bộ định nghĩa cột. Không yêu cầu chạy lại CREATE. Quyền PHP/routing và seed vẫn chưa được user phê duyệt.
## File kết quả ita_settings.sql từ user

Đã đọc tests/ita_settings.sql, không thực thi: có 6 cặp setting_key/setting_value khớp danh mục khởi tạo (budget_contingency_percent=10, currency=VND, display_timezone=Asia/Ho_Chi_Minh, license_critical_days=7, license_warning_days=30, warranty_warning_days=30). INSERT chỉ xuất setting_key và setting_value, không có store_id trong khi CREATE định nghĩa store_id NOT NULL không default; file này không đủ để xác nhận scope store_id=1 hoặc dùng như bản backup phục hồi nguyên bảng. DDL xuất cũng không thể hiện đầy đủ engine/collation/CHECK. Không import lại file. Chưa nhận kết quả categories/departments sau chạy catalog; cần SELECT đếm ba nhóm theo store_id=1.

Gói local đã tạo và đọc lại toàn bộ 710 mục từ commit `5f1e6ea4f5fafef08e51be4b9a08641b5dbf34e0`: `artifacts/ita-phase05/ita-pdf-5f1e6ea4f5fa.zip`. SHA-256: `7d0149bcd1c89aad417a526522a3ab2d1bfcde84c9259ed5e09ba2da43443dec`. Gói chỉ lưu local, chưa upload/deploy.


## 21/10/2026 — Gói cải tiến giao diện ticket, chưa upload

- Gói: artifacts/ita-phase07/ita-ticket-ui-update.zip. Source cố định và SHA256 từng file trong MANIFEST.json của gói.
- Runtime7file FE (ba CSS, hai JS, layout/partial); không PHP/SQL/config/dump/vendor. Nền host cần routing60f4f49.
- Đã kiểm Smarty +43browser API mock checks, viewport1366/768/390 và reduced-motion; không đồng nhất với DB UAT.
- Agent chỉ chuẩn bị gói. User backup7file, upload nội dung runtime vào public_html, Ctrl+F5; không upload ZIP/manifest. Không migration.
- Rollback source khôi phục backup tương ứng, giữ DB/config/keys; chi tiết PHASE07_UI_VERIFY.md. Phase7 chưa merge.


## 21/10/2026 — Bổ sung bộ chọn thiết bị cho form ticket, chưa upload

Gói artifacts/ita-phase07/ita-ticket-picker-update.zip:7runtime FE cộng dồn, gồm cải tiến fcbef20; source commit và SHA256 từng file trong MANIFEST. Không PHP/SQL/config/dump/vendor, không cần gói UI trước. Cần routing60f4f49 đã kiểm host.64browser mock checks và Smarty/syntax/detector/review đạt; user kiểm picker bằng DB host sau upload. Backup7file, upload nội dung runtime, Ctrl+F5; không upload ZIP/manifest/handoff; rollback source giữ DB/config. Agent chưa deploy hoặc merge Phase7.


## 21/10/2026 — Vòng đời tài sản, chuẩn bị gói nhưng chưa upload

- Gói: artifacts/ita-phase08/ita-lifecycle-update.zip. Nền là bản Phase7 qua 2aa3c98, gồm giao diện ticket và bộ chọn thiết bị trước đó. Source cố định và hash từng file trong MANIFEST.json.
- Runtime 11 file: hai DAO lifecycle, rules, ItaApplication, bootstrap, JS shell/lifecycle, CSS lifecycle, layout/sidebar/partial lifecycle. Không config, SQL, seed, fixture MOCK, session, dump hoặc vendor mới.
- Không migration. User backup các file tương ứng và DB trước triển khai theo quy trình; upload nội dung runtime/ vào public_html, không upload ZIP/manifest/handoff. Ctrl+F5, mở #lifecycle.
- Handoff gồm hướng dẫn kiểm host, tính tay và EXPLAIN; chỉ EXPLAIN đọc, không ALTER. Không chạy trên DB nếu chưa xác định môi trường.
- Rollback source khôi phục file backup; giữ dữ liệu/config/keys. File mới có thể để lại không được layout/bootstrap cũ gọi; không DROP hoặc restore đè dữ liệu mới.
- Agent chưa upload/deploy, chưa merge/tag Phase7 hoặc8. Phần DB giữ CHƯA XÁC NHẬN đúng nguyên văn user trong PROGRESS.


## 21/10/2026 — Cải tiến giao diện vòng đời, chưa upload

Gói artifacts/ita-phase08/ita-lifecycle-ui-update.zip gồm12runtime files cộng dồn Phase8 (11file đã duyệt và CSS motion), source cố định/hash từng file trong manifest. Gói thay thế ita-lifecycle-update.zip; không cần upload cả hai. Nền vẫn là Phase7 qua2aa3c98. Không SQL/config/fixture/dump/vendor mới; backend giữ nguyên commit0c2e0a7.

User backup file tương ứng, upload nội dung runtime vào public_html rồi Ctrl+F5; không upload handoff/manifest/ZIP. Kiểm bộ lọc/xóa lọc, chuyển nhóm, xem/đóng lý do và số liệu gốc theo hướng dẫn host. Rollback khôi phục source backup, giữ DB/config/keys.63browser mock checks và review đạt; không có xác nhận DB mới, không merge/tag/deploy.
