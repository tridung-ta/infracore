# Nhật ký thao tác hosting

## Khuyến nghị cô lập vhost và quyền DB runtime — chỉ tài liệu

Chỉ đề xuất triển khai vào **vhost InfraCore riêng** (`infracore.dung.derasoft.com` theo hồ sơ), document root/marker/PHP-FPM scope riêng được host xác minh; không áp .htaccess/.user.ini/auto_prepend lên domain cha hoặc web khác dùng chung. Inventory thật user điền HOST_INVENTORY_TEMPLATE.md; agent chưa thu thập/chạy gì trên host. Vhost riêng không tự thay thế DB grants, ACL và deny static/legacy.

DB dùng tài khoản runtime riêng cho ứng dụng, giới hạn đúng schema/host kết nối cần thiết. **Không có ALTER/DROP/CREATE**, không GRANT OPTION/FILE hoặc quyền quản trị; migration không chạy bằng tài khoản runtime. Tài khoản DDL riêng chỉ user dùng khi tự chạy migration đã duyệt, credential không đặt trong config ứng dụng/Git/gói. Không phát hành GRANT/REVOKE hoặc đổi user DB trong bước này.

| Nhóm | Quyền runtime dự kiến theo source ITA hiện có | Giới hạn |
|---|---|---|
| ita_ tables được runtime tham chiếu | SELECT/INSERT/UPDATE theo bảng thực sự đọc/ghi | Có thể tách read-only table/audit append-only sau đối chiếu schema/grants; không cấp schema wildcard cho DB khác |
| dc_users | SELECT và UPDATE cần cho password_hash/last_login của PmAuth/ITA auth | Không cần INSERT/DELETE trong luồng ITA; cân nhắc column-level UPDATE nếu hosting hỗ trợ, kiểm FOR UPDATE/grant thực tế trước siết |
| DELETE | **Chưa đề xuất cấp cho bất kỳ bảng nào** | Rà ITA DAO/services/security và PmAuth không có DELETE FROM/REPLACE/TRUNCATE; xóa asset là UPDATE is_deleted, scope/allocations thu hồi bằng UPDATE. Không suy cleanup/rate limit cần DELETE |

Danh sách bảng cần DELETE theo code runtime đã rà: **không có**. Nếu sau này thêm cleanup/clear trash hoặc phát hiện dependency thực sự yêu cầu DELETE, phải liệt kê chính xác bảng/câu lệnh/phạm vi và trình gate, không cấp toàn schema để chữa lỗi. Hạn chế trên áp dụng runtime ITA/legacy tắt, không cam kết chức năng legacy khi khôi phục sẽ chạy với cùng quyền. Không dùng credential DDL làm lối tắt khi runtime bị denied.

Quyền effective/SHOW GRANTS chưa kiểm trên host; DB/host chưa nghiệm thu. SELECT/INSERT/UPDATE mô tả nhu cầu hiện tại, không phải quyền đã cấp. Mọi thay đổi grants phải do user/quản trị thực hiện sau xác nhận môi trường và kế hoạch hoàn tác riêng.

## Checklist bảo mật host bổ sung22/10 — chưa chạy

Xem PHASE11_HOST_TEST.md: bước đầu vẫn isolation kết nối ứng dụng RR/SERIALIZABLE. Kiểm ITA_SESSION HttpOnly/Secure/SameSite=Lax, headers cuối sau proxy/Apache/PHP-FPM (kể cả lỗi/file), display_errors/log_errors và log private; .ita-enabled đúng document root, **không có .ita-legacy-enabled**. Mở trực tiếp docs/tests/.git/artifacts/assets/ita/mock và file cấu hình phải403/404, không chỉ không có listing; không in/tải body config để kiểm. Thử vài URL legacy GET/POST theo checklist an toàn; staging import ngoài public_html, không symlink về webroot, ACL riêng.

**Điểm source còn mở:** JSON MOCK hiện được allowlist static nếu file còn trên host; tests/artifacts GET có thể303, chưa đạt policy deny403/404. Không coi loại khỏi gói là đã bảo vệ file dư trên host. Inventory/guard tại PHASE11_LEGACY_SURFACE.md và PHASE11_PHP_SURFACE.csv. Chưa kiểm host, chưa sửa rule/PHP, chưa tạo gói hoặc deploy; S11-01/S11-02 vẫn chỉ gate nháp.

## F11.5 — Chuẩn bị bàn giao, chưa tạo gói (22/10/2026)

Đây là **đặc tả inventory/manifest/rollback**, không phải gói đã tạo hoặc lệnh cho phép deploy. Không có SHA256 ZIP #11 vì chưa tạo ZIP; không điền hash giả. Source bàn giao tương lai phải chốt một commit cụ thể sau review; các commit tài liệu không chứng minh host đang chạy chúng. Không merge/tag Phase7–10/push/deploy. S11-01 và phần BE11 chưa sửa, DB/host chưa nghiệm thu; không mô tả gói là đã sẵn sàng production.

### Danh sách bao gồm/loại trừ dự kiến

| Nhóm | Bao gồm khi có trong inventory nguồn cố định | Loại trừ / kiểm trước đóng gói |
|---|---|---|
| Entrypoint/guard | admin.php, project-start/service worker và các entrypoint hiện có thực sự cần; project_gate/ita_mode/bootstrap, module ITA; .ita-enabled/.htaccess phiên bản đã duyệt nếu gói cần | Không ghi đè config host hoặc tự bật .ita-legacy-enabled; thay config/rewrite mới phải gate. Không suy chỉ copy CSS/JS là đủ runtime |
| PHP ứng dụng | ITA DAO/service/security, adapter dùng chung và transitive dependencies theo bootstrap; Smarty/runtime cần thiết | Không zip toàn classes/modules theo mặc định. Giữ source legacy đang dùng trên host, không xóa để làm gọn. Dependency legacy dùng chung phải liệt kê cụ thể |
| Giao diện | templates/admin/ita; CSS/JS production assets/ita/css, assets/ita/js; SVG tĩnh; font và vendor UI ghim phiên bản/license | Không đưa assets/ita/mock/**, assets/ita/preview/**; kể cả login-preview.html, dashboard.html, reports.html, mọi fixture/JSON/HTML MOCK |
| Excel/PDF/font | Bộ runtime theo hồ sơ provenance phía dưới, composer.json/lock PDF, vendor/font/license cần thiết từ nguồn đã đối chiếu hash | Không giả định Git clone có vendor Excel/PDF/font. Không Composer install trên host; không đổi version hoặc dùng latest im lặng |
| Hồ sơ bàn giao | MANIFEST.json, SHA256SUMS.txt, hướng dẫn release/rollback đặt ngoài webroot hoặc kênh bàn giao riêng | Không đưa docs/database/tests vào webroot để người dùng tải trực tiếp |
| Tệp phát triển/sinh tự động | Không | tests/**, tmp/**, .playwright-cli/**, artifacts/**, cache/templates_c, node_modules, .git/.agents/.codex, log/session, screenshot, seed/dump, file kiểm thử Excel/PDF, backup/ZIP cũ |
| Riêng tư/dữ liệu sống | Không lấy từ workspace vào gói runtime | includes/ita_database.local.php, .env, khóa/credential/ITA_LICENSE_KEY, dump DB, uploads/dữ liệu người dùng, session và raw log. Host giữ riêng; không xóa khi rollback |

“Loại MOCK” áp dụng **cả đường dẫn lẫn nội dung runtime**: từ chối preview/fixture trong inventory, rà mọi tệp có nhãn MOCK và mọi basename dashboard.html/reports.html. Nếu production JS có nhánh hỗ trợ mock/nhãn, phải xác minh entrypoint live không bật nhánh đó; không xóa chuỗi hàng loạt hoặc sửa code trong bước này. Giữ cây legacy trên host theo yêu cầu chỉ ẩn; không dùng lệnh sync/delete loại tệp ngoài inventory.

### Contract manifest và hash cho bước đóng gói sau

1. Chốt source_commit đầy đủ40 ký tự, git status/diff, danh tính và danh sách runtime tường minh; không build từ working tree lẫn thay đổi chưa review. Chụp inventory dependency ngoài Git với nguồn/version hoặc baseline tree hash, license và tình trạng xác minh.
2. MANIFEST.json ghi release_id, source_commit, thời gian UTC, môi trường build/PHP, danh sách file `{path, size_bytes, sha256, origin, version_or_ref}`. Path tương đối POSIX, không absolute/.., không trùng tên kể cả khác hoa thường; không symlink/junction ra ngoài nguồn. Mọi hash là SHA256 byte thật,64 ký tự hex thường, không hash timestamp hoặc nội dung đã chuẩn hóa.
3. SHA256SUMS.txt sắp path, mỗi dòng `sha256  relative/path` và LF, UTF-8. Kiểm lại từng file sau giải nén staging ngoài webroot; số file/size/hash phải khớp và không có file ngoài manifest. Không self-hash MANIFEST/SHA256SUMS trong chính chúng; hash hai file metadata và ZIP ở checksum sidecar ngoài gói để tránh vòng lặp.
4. Soát inventory có0 đường dẫn loại trừ nêu trên và0 secret/dữ liệu thật; kiểm dependency/font bằng nguồn đã pin, không chỉ hash source Git. Hash ZIP riêng sau khi đóng gói; ZIP hash khác tree hash. Hai lần ZIP có thể khác byte dù cùng cây nên không so thay cho file hash.
5. Ghi kết quả thật và hash vào bảng host **chỉ khi có bằng chứng upload đúng gói**. Hiện chưa tạo inventory thực tế #11, chưa tính/verify hash của gói #11, chưa upload. Hash baseline dependency phía dưới là quan sát cũ, không thay manifest release mới.

Không cần dựng gói để chuẩn bị contract trên. Khi được giao đóng gói, nếu thiếu dependency/provenance thì báo thiếu, không tự tải bản khác hoặc coi “đã có Git” là đủ.

### Rollback source, user thực hiện khi triển khai sau

- Trước upload: xác nhận source/commit/gói **đang chạy** (hiện chưa biết), backup toàn bộ source/config riêng ngoài webroot; user backup DB và khóa giấy phép tách riêng. Ghi manifest/hash baseline và gói mới. Không đưa secret vào Git hoặc manifest nội dung.
- Kiểm đầu tiên trên kết nối ứng dụng: isolation RR/SERIALIZABLE. Sau đó PHP8.3.33/extensions, dependency/font, key/config, HTTPS/cookie/headers/display_errors Off/log_errors On; checklist host bên dưới. Không dùng phpMyAdmin session thay bằng chứng kết nối ứng dụng.
- Nếu lỗi sau upload: ngừng thao tác ghi trong thời gian rollback theo quy trình vận hành được user chọn; phục hồi **đúng tệp đã thay** từ backup và marker/config chỉ nếu đã thay, không ghi đè config/khóa/dữ liệu sống. Làm mới cache/opcache bằng cơ chế hosting được phép, không xóa tùy tiện cache/session đang dùng.
- Giữ DB, audit, tickets, allocations, snapshot và dữ liệu phát sinh; rollback source **không** DROP/restore dump đè hoặc đảo dữ liệu mới. Không DDL trong gói FE/tài liệu này; nếu code cũ không đọc được dữ liệu mới thì dừng để đối chiếu tương thích, không ép restore DB.
- User kiểm login/ba vai trò, đọc hồ sơ, file export và mốc số liệu sau rollback; ghi thời điểm, source/hash đã khôi phục và mục chưa kiểm. Chưa thực hành rollback host/DB trong bước này.

## Trạng thái host hiện tại — cập nhật22/10/2026

| Gói/source | Ngày upload | Đã upload được xác nhận | Chưa upload/chưa biết |
|---|---|---|---|
| Source hiện chạy trên host | Chưa được cung cấp | Chỉ có lời báo/ảnh đăng nhập và ticket trong lịch sử | Chưa xác định commit/hash; không suy từ UI |
| through-phase07 source80929beff9bb; picker2aa3c98179e5 | Chưa xác nhận | Chưa có xác nhận đúng gói/hash | Có gói local; upload thực chưa biết |
| Phase8 UI source1691c43bd37d | Chưa xác nhận | Chưa xác nhận upload đúng gói | Chỉ có gói local được ghi trong hồ sơ |
| Phase9 source2e95245; docs9b32bae | Chưa xác nhận | Chưa xác nhận | Build local, chưa có bằng chứng host |
| Phase10 source0ce2166; docs2f05ea3 | Chưa upload theo hồ sơ hiện có | Không | 7 chức năng +fix chỉ local; chưa tạo gói deploy #10 |
| Phase11 FE/tài liệu | Chưa upload | Không | Chỉ bắt đầu FE/tài liệu; BE chưa duyệt |

**Bước kiểm đầu tiên trước nghiệm thu #10: transaction isolation của chính kết nối ứng dụng.** Dashboard, alerts và report-preview đều đi qua điều kiện RR/SERIALIZABLE, không riêng thao tác lưu ngân sách. Giá trị đọc trong phpMyAdmin là kết nối khác, không đủ chứng minh session PHP-FPM. Dùng công cụ/quan sát được phép của môi trường để kiểm kết nối ITA thực tế; khi chưa có cách quan sát, ghi **chưa xác nhận**, không thêm PHP endpoint chẩn đoán hoặc đổi config để vượt chặn trong bước này. Có thể dùng `@@transaction_isolation` (MariaDB cũ: `@@tx_isolation`) qua kết nối đó; nhánh fallback hiện có trong PmDb. READ-COMMITTED/READ-UNCOMMITTED chưa đạt điều kiện ứng dụng. Không tự đổi isolation toàn server.

**Gói runtime phải loại mọi MOCK**: `assets/ita/mock/**` (đặc biệt dashboard.html, reports.html, JSON/HTML khác), `assets/ita/preview/**`, test/fixture, ảnh kiểm thử và tmp. Dùng danh sách runtime cho phép và manifest/hash, không zip toàn workspace. Quét lại inventory ZIP để bảo đảm không có MOCK; chỉ grep chữ MOCK không đủ, vì mã production có thể chứa nhánh preview/label và không được tự xóa source đang dùng. Chưa tạo/kiểm ZIP #11 trong bước này.

PHP kiểm local8.5.10; host theo hồ sơ8.3.33; **chưa chạy test trên8.3**. Giữ DB/host chưa nghiệm thu, không merge/tag7–10/push/deploy. Kế hoạch tải chưa chạy tại PHASE11_LOAD_TEST_PLAN.md.

## 22/10/2026 — Dashboard/báo cáo #10 chỉ có trên local

Source a017327 → 0ca9a7e gồm7 commit F10.1–F10.7, kèm fix điều hướng0ce2166 đã build/tự kiểm; xem PHASE10_VERIFY.md. **Chưa upload, chưa tạo gói deploy #10, chưa xác nhận source host.** Không DDL/seed mới. Cần đủ PHP/bootstrap/ita_mode/audit/Smarty/JS/CSS và ECharts6.1.0, không chỉ giao diện.

Checklist host tại PHASE10_HOST_TEST.md: PHP/GD/mbstring, display_errors Off/log_errors On, RR/SERIALIZABLE, auth_key/ITA_LICENSE_KEY private; kiểm dependency/font theo hồ sơ nguồn bên dưới. Chưa chạy Composer hoặc đổi server config. Rollback source giữ dữ liệu/audit mới, không restore đè DB. DB Phase1–10 chưa nghiệm thu; không merge/tag7–9/push/deploy.

## Hồ sơ host đã rà21/10/2026 — đối chiếu lịch sử

Không kết nối host trong lần rà này. “Ngày bằng chứng” là ngày lời báo/ảnh hoặc ngày tạo gói local, **không tự coi là ngày upload**. Chưa có inventory/hash source đang chạy trên host. Các ghi chú lịch sử bên dưới không thay thế bảng trạng thái này.

| Hạng mục / gói hoặc commit | Ngày bằng chứng / upload | Trạng thái xác nhận | Chưa upload hoặc chưa biết |
|---|---|---|---|
| Đăng nhập/đăng xuất ITA trên host, username và Gmail | User báo16/10; ngày upload chính xác chưa cung cấp | User xác nhận thao tác host thành công | Gói/commit đã upload chưa xác định; không coi là DB UAT1–9 |
| Ticket trên host: tạo/gán/resolved/xác nhận sẵn sàng/thống kê | Ảnh user21/10; ngày upload chưa xác định | Có bằng chứng thao tác và đọc lại một thiết bị/phiếu | Chưa xác nhận commit/gói hiện chạy; không suy toàn bộ source trùng commit local |
| infracore-host-through-phase07-80929beff9bb.zip; source80929beff9bb | Gói local ghi20/10 | Đã chuẩn bị/kiểm gói trong lịch sử | Chưa nhận xác nhận user upload đúng ZIP/hash này |
| ita-ticket-picker-update.zip; source2aa3c98179e5 | Gói local21/10, source đọc lại từ MANIFEST | Có gói local | Chưa có xác nhận upload đúng gói; agent không upload |
| ita-lifecycle-update.zip (gói cũ), thay bằng ita-lifecycle-ui-update.zip; source1691c43bd37d | Gói local21/10; manifest gói UI đã đối chiếu | Gói UI là bản bàn giao local mới hơn | Chưa có xác nhận upload/hash host của Phase8; không upload cả hai gói |
| Ngân sách source2e95245; docs checkpoint9b32bae | Commit local21/10 | Đã build/verify local | Chưa tạo gói Phase9 trong phiên này; chưa có xác nhận upload |
| Phase10 source0ce2166; docs2f05ea3 | Build local21/10, chốt22/10; chưa upload | BE đã duyệt/build local; **chưa nghiệm thu DB/host** | Chưa tạo gói deploy #10, chưa upload |

DB Phase1–10 **chưa nghiệm thu**; không merge/tag7–10. Để cập nhật bảng, user cung cấp gói/commit/hash và ngày upload thực; nếu không biết ghi chưa xác định, không dựng ngược từ ảnh UI hoặc kết quả mock. Việc đăng nhập hoặc một luồng ticket thành công không xác nhận CHECK/rollback/concurrency.

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
