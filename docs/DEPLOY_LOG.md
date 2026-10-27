# Nhật ký thao tác hosting

**Chỉ đạo mới26/10/2026:** user cho phép đóng gói để tự upload và kiểm trên host8.3, thay yêu cầu dừng đóng gói trước. Agent chỉ tạo/kiểm ZIP local, chưa upload/kết nốiDB hoặc đổi cấu hìnhhost. Runtime user gửi thực8.5.10 (đã chạy đúng php.exe -v), **chưa kiểm8.3**; không tự nghiệm thuDB/host/merge/tag7–10/push/deploy. Các ghi chú “chưa đóng gói” bên dưới là lịch sử trước gói kiểm này, không xóa/sửa bằng chứng cũ.

**Chỉ đạo mới22/10/2026:** user sẽ kiểm DB thủ công trên host sau; agent **không tự tạo/kết nối DB test**, không chạy host/DB và không yêu cầu DB test để tiếp tục local trong phạm vi được duyệt. Các kế hoạch thử DB test trong tài liệu được giữ để truy vết, **không còn là bước bắt buộc user phải thực hiện**. Không ghi PASS/nghiệm thu DB khi chưa có xác nhận cụ thể. Quyền build BE/merge/tag7–10/push/deploy không được mở bởi chỉ đạo này.

Tái cấu trúc ngày **22/10/2026**: chuyển vị trí các mục, giữ nguyên nội dung lịch sử; chỉ hạ mức heading để gom bốn phần. Mục lịch sử có ngày xếp mới trước, cùng ngày giữ thứ tự cũ; mục không ghi ngày để cuối theo thứ tự cũ, không tự gán ngày. Các ghi chú cũ là bằng chứng tại thời điểm đó, không thay bảng trạng thái hiện tại.

## A. Trạng thái host hiện tại

27/10/2026 — Theo yêu cầu “tạo gói để tôi upload”, đã tạo **delta FE19file** `artifacts/ita-host-20261027/infracore-ui-update-704b818.zip`, source cố định **704b8189b7034d8d8b1e1576488437b7d6a0945c**,40.491byte; SHA256 **ed27ce03c8c4f07fe16e9356cf8920dbb2bab6e5a324894fdb607344e02026ff**.19runtime/100.432byte +2metadataentry. Đã đọc lại ZIP thật kiểm exactallowlist/exclusions/CRC/size/hash/manifest/checksums; Get-FileHash độc lập khớp. Gồm UI092afec/ed34749/704b818 và networkCSS/partial đồng bộ, khôngPHP/SQL/config/vendor/MOCK/test/tmp hoặc dữ liệu thật. **CHƯA UPLOAD**, chưa PHP8.3/FPM/DB; không suy host đang chạy source này. Cần nền InfraCore tương thích/Three.js0.180.0 có sẵn; không bộ cài độc lập.

Hướng dẫn19path/backup/FileZilla/smoke/rollback: **HOST_UPLOAD_UI_20261027.md**. User backupfile+dumpDB riêng, giải nénlocal rồi upload **nội dung runtime/** vào đúngpublic_html InfraCore, khôngZIP/metadata/docs hoặc thêm runtime/host. Khôngmigration. Rollback source khôi phục đúngbackup, giữ credential/audit/DB/dữliệumới. Headerkind chung củabuilder không đổi; package_type ghi rõ **UIFEDELTA—19RUNTIMEFILES**. Review/verify/checksumsidecar ởartifactignore, khôngcommitpayload. Validator66checks synthetic đạt, tách khỏi kết quảZIP thật; runtime kiểm riêng8.5.10, không8.3. Khôngagentupload/push/merge/tag7–10/deploy.

26/10/2026 — Gói **deltaFE3D** theo yêu cầu “gửi tôi file upload”: artifacts/ita-host-20261026/infracore-network-3d-46ef989.zip, source46ef9896446e39f25b8b6b55e3dd72818afdde00, **10.993byte**, SHA256 **3d94c3244a3e4e3945098fd0c5e4450f6681f1a9de1e5501e3a6fff6c83a160f**. Chỉ5file runtime/26.931byte +MANIFEST/SHA256SUMS metadata2entry; builder đã đọc lạiZIP thật kiểmallowlist/exclusions/CRC/hash/size/manifest đạt. **CHƯA UPLOAD**, không suy sourcehost hiệnđãcó delta. KhôngPHP/SQL/config/vendor/MOCK/test mới.

Năm path cầnbackup/upload đồngbộ: assets/ita/css/ita-network.css; assets/ita/js/ita-network-3d.js; assets/ita/js/ita-network-scene.js; templates/admin/ita/layout.tpl.html; templates/admin/ita/partials/network.tpl.html. Cần nền gói cộngdồn81f3c55 đangchạy hoặcsource tươngthích/Three.js0.180.0 cósẵn, khôngbộ càiđộc lập. User giảinénlocal rồi upload **nội dung runtime/** vào đúngpublic_html, khônguploadZIP/metadata hoặc tạo thêmruntime/host. Không cầnmigration/SQL/đổiDB; giữconfig/key/dữliệu. Backup5filetrước; rollback5fileđúngbackup, Ctrl+F5 sauupload/rollback. Kiểm 3Dnhãn/fullscreen/Esc/chuộttrái-kéo360°/clickkhối/tap vàresponsive; nếu lỗi gửistatus/consoleđãche, khôngthửsửaDB. Sidecar/report cùngbasename ởartifactđãignore, khôngcommitpayload. Headerkind chung củabuilder giữ“cumulative host test update”; trườngpackage_type trongmanifest ghi rõ **NETWORK3DFEDELTA—EXACTLYFIVERUNTIMEFILES**, tậpfiles5path làscope thực.

| Gói kiểm cộng dồn mới | Source cố định | Ngày tạo local / upload | Trạng thái |
|---|---|---|---|
|infracore-host-test-81f3c55821e7.zip|81f3c55821e760472a830ae523f1a662bd6dbe8b|26/10/2026 / CHƯA UPLOAD|ZIP local đã đọc lại; không biết source đang chạy host từ gói này|

PHP8.3/FPM/DB vẫn CHƯA KIỂM; user tự upload sau khi kiểm điều kiện nền/schema/backup trong HOST_UPLOAD_20261026.md. Đây là bản cập nhật nềnDerasoft hiện có, không bộ cài độc lập. Gói không có cấu hìnhhost/marker/admin.php/ajax.php toànnền; .htaccess chỉ tách manualreview. Không upload handoff/manual-review/metadata/ZIP vàowebroot.

26/10/2026 — Chỉ bổ sung hồ sơ sau S26-02: user ghi nhận686checks local, chưa8.3/FPM/DB. Ngoại lệ **“Giữ Argon2: cho phép ngoại lệ một verify hash thật, không thêm dummy”** có chi phí khác bcrypt, thời gian có thể khác; **«đếm verify không chứng minh hằng thời gian»**. Chấp nhận hai nơi giữ policy (PmAuth/ItaAuthService) cần rà đồng bộ và dummy theo PASSWORD_DEFAULT không khớp variant thì503. PHASE11_HOST_TEST thêm login sai/nonexistent401 cùng thông báo, không503, login đúng và khai báo native last_login/password_hash (MD5 giữ). S26-03 chờ runtime8.3, chạy S26 trước và xác nhận dummy/default cost thực; **CHƯA CHẠY**. Không code/test mới/gói/upload/merge/tag7–10/push/deploy.

26/10/2026 — S26-02 source đã duyệt/build local: ItaAuthService(+test) một verify/401, không native write401, không đổi PmAuth, ngoại lệ Argon2 một real verify.686counter checks+hồi quy tại S26_AUTH_VERIFY.md đạt; không timing hoặc DB/host proof. **Chưa upload/đóng gói**, source4file mẫu host vẫn Phase8, không suy có bản auth mới. Sau này backup/rollback đúng service source, giữ credential/last_login/audit mới; không reset user hoặc restore DB. Auto_prepend/HSTS KHÔNG duyệt; S26-03 chờ runtime8.3 riêng, chưa chạy. Không push/merge/tag7–10/deploy.

26/10/2026 — User ghi nhận local đạt, host chưa nghiệm thu, chưa gói; chỉ cập nhật hồ sơ. PHASE11_SECURITY_TEST_20.md đồng bộ báo cáo,17–20 **CHƯA KIỂM host**. S26-01 MD5 native là hạn chế user chấp nhận, không xóa/reset credential. S26-02 chỉ draft ItaAuthService/counter, chưa build; S26-03 chỉ PHASE11_PHP83_RETEST_PLAN.md, chưa runtime/chưa chạy. PHASE11_HOST_TEST.md bổ sung đúng HTTPS/Set-Cookie/header cuối/CORS/display_errors và hosting panel cho DB network/grants/Cloudflare/backup-log. Không thao tác panel hoặc cấu hình, không upload/gói/deploy/merge/tag7–10/push.

26/10/2026 — **Tạm dừng đóng gói/upload theo checklist Word mới của user.** Đã chạy các kiểm local trong PREUPLOAD_SECURITY_VERIFY.md, không chạy DB/host hoặc auth fixture reset. Các bộ local đạt, còn native MD5, nhánh timing chỉ thấy từ source, deprecation Excel trên PHP8.5 và các mục vận hành host chưa có bằng chứng. Không tạo ZIP ứng dụng; inventory JSON review không phải release/gói đã kiểm. Không đổi quyền deploy/DNS/grants, không push/merge/tag7–10.

26/10/2026 — Đã nhận4 file đại diện do user tải về: module ITA/mode/dispatcher/layout có byte SHA256 khớp checkpoint Phase8 `1691c43` (4/4). User báo ita-dashboard.js chưa có. Chi tiết HOST_SOURCE_IDENTIFICATION_RESULT.md. **Chỉ xác minh file mẫu, chưa xác nhận source/hash toàn host hoặc gói/ngày upload**; không suy Phase9–13 hoàn toàn vắng hoặc schema đã/chưa có. Cần cập nhật cộng dồn đồng bộ, không dùng delta S11 lẻ. Chưa tạo gói/upload/DB/host; full inventory chưa thu thập, DB chưa nghiệm thu. Các ghi chú trước “chưa có file host” là trạng thái trước đợt nhận mẫu này.

26/10/2026 — Bắt đầu nhận diện source: đã chuẩn bị HOST_SOURCE_FINGERPRINTS.csv (30 hash tham chiếu Git local) và HOST_SOURCE_IDENTIFICATION.md. User tải5file đại diện bằng FTP Binary vào tmp/host-inventory/files khi sẵn sàng; **hiện chưa có file host, chưa đối chiếu và chưa xác định source host**. Hash Git không thay manifest payload gói thực. Không tạo gói/upload/chạy host/DB hoặc cập nhật ngày deploy.

26/10/2026 — User “duyệt SQL #13 tiếp tục BUILD”: đã chuẩn bị database/migrations/006_ita_network_links.sql theo đúng DDL, database/host/phase13_preflight.sql +phase13_verify.sql chỉ đọc, hướng dẫn PHASE13_SQL_HOST_GUIDE.md.57 kiểm tĩnh +179 PHP hồi quy đạt; không SQL parser/DB proof. **Chưa áp dụng, chưa upload/gói, chưa xác nhận bảng mới/host source**, không ghi Đạt DB hoặc ngày migration giả. User backup source/DB, xác nhận schema/phiên bản và tự áp dụng khi sẵn sàng; runtime không cần DDL/DELETE. Giữ dữ liệu mới khi rollback source; không DROP/restore đè. Không merge/tag7–10/push/deploy. Ghi chú SQL chưa duyệt trước đây là lịch sử.

23/10/2026 — BE13: user duyệt source; API/DAO/service và FE live đã build/kiểm local. **Chưa upload, chưa tạo gói, schema mới chưa duyệt/chạy**, không coi host có bảng hoặc cùng source. Xem PHASE13_BE_VERIFY.md và BE_GATE_13_SQL.md. Runtime cần SELECT/INSERT/UPDATE đúng bảng mới, không DELETE/DDL; quyền/grants thật chưa kiểm. Khi được giao gói sau này phải review nền/schema/dependencies toàn bộ, không ghép vào delta S11 ba file. Backup source/DB trước triển khai do user làm; rollback source giữ link/audit/dữ liệu mới, không DROP/restore đè. Không merge/tag7–10/push/deploy.

**23/10 sau BUILD liên tục:** Phase12 motion FE và Phase13 network2D/3D MOCK đã build local tới89b0047, không tạo gói/upload. Phase13 chưa có BE, user chọn chỉ FE MOCK; không đưa topology mẫu hoặc cả nhánh source lên host. Three local0.180.0 có source/license/provenance, không chứng minh host đã có. Gate SQL/host/DB/EXPLAIN vẫn chưa nghiệm thu; các nguồn host trong bảng bên dưới không tự cập nhật theo commit local. CURRENT_BUILD_STATUS.md tổng hợp10 yêu cầu và giới hạn. Không merge/tag7–10/push/deploy.

**Source(g)23/10 local:** hai header Referrer/Permissions đã build ở module/error boundary,102 HTTP local +hồi quy đạt (PHASE11_PRIVACY_HEADERS_VERIFY.md). Chưa HSTS hoặc cấu hình host, chưa gói/upload; phải có nền(f). Không coi source local là header cuối trên hosting đã kiểm.

**Source(f)23/10 chỉ local:** error boundary/template/CSS mới và Application/bootstrap/module/project_gate đã sửa trong phạm vi user duyệt.56 HTTP/Smarty +41 browser đạt; chưa PHP-FPM/Apache/host/config log. Chưa gói/upload/deploy. Không dùng delta export3 file cũ để triển khai(f); dependency/file boundary phải bàn giao đồng bộ khi có yêu cầu tạo gói riêng.

**Cập nhật source local23/10:** user duyệt(c)/(d), đã build guard project-start +deny tests/artifacts/scripts/MOCK ở .htaccess/ita_mode; PHASE11_GUARD_VERIFY.md ghi109 PHPHTTP fixture/75 pure, chưa Apache/host. Chưa gói/upload/deploy. Delta S11 export dự kiến3 file từ561d335 không chứa4 file guard/rewrite mới; không coi host đã có chúng. Auto_prepend/.user.ini/header/trang lỗi HTTP vẫn chưa build, inventory CHƯA THU THẬP.

**Bổ sung22/10 sau duyệt riêng:** S11-01/S11-02 đã sửa và kiểm local không DB (PHASE11_EXPORT_VERIFY.md); chưa upload, chưa tạo gói hoặc kiểm host. Chỉ hai finding export được duyệt, các mục khác BE11 vẫn nháp. Những dòng lịch sử “S11-01/02 chưa sửa” bên dưới giữ trạng thái trước phê duyệt, không thay cập nhật này.

### Trạng thái host hiện tại — cập nhật22/10/2026

| Gói/source | Ngày upload | Đã upload được xác nhận | Chưa upload/chưa biết |
|---|---|---|---|
| Source hiện chạy trên host | Chưa được cung cấp | Chỉ có lời báo/ảnh đăng nhập và ticket trong lịch sử | Chưa xác định commit/hash; không suy từ UI |
| through-phase07 source80929beff9bb; picker2aa3c98179e5 | Chưa xác nhận | Chưa có xác nhận đúng gói/hash | Có gói local; upload thực chưa biết |
| Phase8 UI source1691c43bd37d | Chưa xác nhận | Chưa xác nhận upload đúng gói | Chỉ có gói local được ghi trong hồ sơ |
| Phase9 source2e95245; docs9b32bae | Chưa xác nhận | Chưa xác nhận | Build local, chưa có bằng chứng host |
| Phase10 source0ce2166; docs2f05ea3 | Chưa upload theo hồ sơ hiện có | Không | 7 chức năng +fix chỉ local; chưa tạo gói deploy #10 |
| Phase11 FE/tài liệu +S11-01/S11-02 | Chưa upload | Không | FE và hai sửa export local; phần BE11 còn lại chưa duyệt, host chưa kiểm |

**Bước kiểm đầu tiên trước nghiệm thu #10: transaction isolation của chính kết nối ứng dụng.** Dashboard, alerts và report-preview đều đi qua điều kiện RR/SERIALIZABLE, không riêng thao tác lưu ngân sách. Giá trị đọc trong phpMyAdmin là kết nối khác, không đủ chứng minh session PHP-FPM. Dùng công cụ/quan sát được phép của môi trường để kiểm kết nối ITA thực tế; khi chưa có cách quan sát, ghi **chưa xác nhận**, không thêm PHP endpoint chẩn đoán hoặc đổi config để vượt chặn trong bước này. Có thể dùng `@@transaction_isolation` (MariaDB cũ: `@@tx_isolation`) qua kết nối đó; nhánh fallback hiện có trong PmDb. READ-COMMITTED/READ-UNCOMMITTED chưa đạt điều kiện ứng dụng. Không tự đổi isolation toàn server.

**Gói runtime phải loại mọi MOCK**: `assets/ita/mock/**` (đặc biệt dashboard.html, reports.html, JSON/HTML khác), `assets/ita/preview/**`, test/fixture, ảnh kiểm thử và tmp. Dùng danh sách runtime cho phép và manifest/hash, không zip toàn workspace. Quét lại inventory ZIP để bảo đảm không có MOCK; chỉ grep chữ MOCK không đủ, vì mã production có thể chứa nhánh preview/label và không được tự xóa source đang dùng. Chưa tạo/kiểm ZIP #11 trong bước này.

PHP kiểm local8.5.10; host theo hồ sơ8.3.33; **chưa chạy test trên8.3**. Giữ DB/host chưa nghiệm thu, không merge/tag7–10/push/deploy. Kế hoạch tải chưa chạy tại PHASE11_LOAD_TEST_PLAN.md.

## B. Checklist host và kế hoạch bàn giao

### 26/10 — Công cụ chuẩn bị và validator, chưa đóng gói

Xem RELEASE_PREPARATION_VERIFY.md. Đã có prepare_ita_release.py (chỉ inventory JSON, không ZIP) và ita_release_validation.py với66checks ZIP synthetic trong memory. Inventory cũ dd03897 gồm1415file/105032697byte, chưa phải release mới; đóng gói/upload vẫn dừng theo PREUPLOAD_SECURITY_VERIFY.md. Tool mới loại preview/temp/MOCK, có Inter OFL, giữ project-start và dependency; .htaccess tách manual-review exacthash, SQL handoff exacthash, không config/key/marker runtime. Khi được giao tiếp phải chốt source/inventory cuối và kiểm ZIP thật; chưa có archive hash/manifest release hoặc bằng chứng host. Đặc tả lịch sử “chưa viết validator” bên dưới là thời điểm trước cập nhật này; không suy các script lịch sử đã được nâng cấp.

### F11.5 — Kiểm tự động ZIP sau đóng gói (đặc tả, chưa viết/chạy)

Bước bắt buộc sau khi tạo gói trong tương lai, trước bàn giao: **mở lại ZIP và kiểm chính entry của ZIP**, không chỉ tin danh sách nguồn hoặc excluded trong manifest. Hiện không tạo gói/viết validator; ba script lịch sử chưa được tuyên bố đã thực thi toàn bộ đặc tả này.

1. Chuẩn hóa đường dẫn để kiểm: dấu phân cách POSIX, so không phân biệt hoa thường; kiểm cả gốc ZIP và gốc payload sau prefix runtime/. Từ chối absolute/drive/UNC, backslash mơ hồ, NUL, thành phần . hoặc .., tên trùng sau chuẩn hóa, symlink/junction hoặc đường dẫn thoát staging. Không giải nén trước khi kiểm an toàn đường dẫn.
2. Từ chối mọi entry thuộc **tmp/** (kể cả tmp/host-inventory), **tests/**, **assets/ita/mock/**, **assets/ita/preview/**, **.git/**, **.playwright-cli/**, **artifacts/**; không cho qua vì thêm prefix runtime/ hoặc đổi hoa thường. Handoff cũng không được chứa listing/bản sao private. Không chỉ kiểm basename listing.txt.
3. Từ chối config/private secret (.env và biến thể .env.*, ita_database.local.php, config host thật, key/credential), dump SQL/backup/log/session và file dữ liệu thật. Inventory runtime phải là allowlist đã review, không chỉ blacklist tên; config mẫu được phép nếu có phải chỉ rõ từng path/nguồn không secret, không mặc định cho mọi file có tên config. Các tệp config cấu trúc bên trong dependency cũng phải xét allowlist, không tự cắt vendor làm hỏng runtime.
4. Đối chiếu ZIP ↔ manifest: cùng tập file được duyệt, không thiếu/thừa, size và SHA256 từng entry khớp; kiểm CRC, metadata manifest/checksum theo contract không self-hash. Vượt giới hạn hoặc không đọc được ZIP/entry → từ chối, không bỏ qua entry lỗi. Tệp bị loại xuất hiện trong manifest dù không có trong ZIP cũng là lỗi.
5. Chỉ ghi “đạt kiểm ZIP” sau khi validator thật chạy trên ZIP thật và lưu kết quả đã che private; hash ZIP riêng sau kiểm. Quét secret theo nội dung là lớp bổ sung, không dùng regex tên để hứa phát hiện mọi secret.

**Bổ sung deny/allowlist22/10:** luôn từ chối `.ita-legacy-enabled`, kể cả khác hoa thường/ở handoff. Từ chối basename `dashboard.html`, `reports.html`, `login-preview.html` **ở mọi đường dẫn**, không chỉ assets/ita/mock. `.htaccess`, `.user.ini` và mọi marker `.ita-*` khác chỉ được qua khi **đúng path và byte/hash** trong allowlist đã duyệt; không có ngoại lệ cho `.ita-legacy-enabled`. Mọi *.php trong thư mục upload/staging bị từ chối, không phân biệt hoa thường; dùng danh sách path upload/staging đã xác định cho release, không chỉ tìm một tên folder giả định. Chưa có inventory host thì không tuyên bố đã biết tất cả thư mục upload.

**Case bổ sung (chưa viết/chạy):** runtime/.ITA-LEGACY-ENABLED hoặc handoff/.ita-legacy-enabled→từ chối dù có allowlist; nested/Dashboard.HTML, handoff/reports.html, runtime/other/login-preview.html→từ chối; .htaccess/.user.ini/.ita-enabled không allowlist, sai path hoặc hash→từ chối; uploads/a.PHP và staging/sub/a.php→từ chối. Case đối chứng không được chặn nhầm: template dashboard.tpl.html, JS reports.js đúng allowlist→qua nếu các kiểm khác đạt; .htaccess/.user.ini/.ita-enabled đúng path+hash được duyệt→qua; file nằm ngoài upload/staging không tự bị từ chối chỉ vì là PHP, vẫn phải qua allowlist runtime. Đánh giá link/path chuẩn hóa trước các ngoại lệ để không bypass bằng alias.

**Ma trận test validator dự kiến, chưa viết/chạy:** gói tối thiểu allowlist hợp lệ→đạt; mỗi prefix cấm có file con và directory entry→từ chối; TMP/Host-Inventory, Runtime/Tests, ASSETS/ITA/MOCK, .GIT/HEAD→từ chối; config/.env/dump trong gốc/runtime/handoff→từ chối; absolute/../backslash/duplicate-case/link→từ chối; manifest thiếu/thừa/sai size/sai hash/CRC hỏng→từ chối. Case âm tính: tên gần giống như templates không bị coi là tmp; dependency hợp lệ chỉ qua khi được allowlist. Test bản sao listing đổi tên/private content phải bị từ chối qua review inventory/content, không coi blacklist path là đủ.

### Xác định source host bằng file đại diện — chưa thực hiện

User tải bằng FTP **binary/không chuyển newline** vài file đã biết không chứa secret, ví dụ classes/services/itaapplication.class.php, includes/ita_mode.inc.php, modules/admin/ita.module.php, templates/admin/ita/layout.tpl.html và assets/ita/js/ita-dashboard.js nếu có. Không tải private config/.env/dump/session/upload người dùng. Không chạy PHP/lệnh trên host. Giữ bản tải trong tmp/host-inventory/ đã ignore, không commit/đóng gói; nếu nội dung có dữ liệu nhạy cảm ngoài dự kiến thì không gửi lên chat.

Tính SHA256 **trên máy local**, ví dụ `Get-FileHash -Algorithm SHA256 -LiteralPath '<file local đã tải>'`; lấy bytes cùng path từ từng commit/gói ứng viên để tính hash local hoặc so manifest đã xác minh. Không mở/execute PHP tải về. Không chuẩn hóa newline/encoding khi so; khác do FTP text mode cần ghi sai lệch transport và tải lại binary, không tự sửa nội dung cho khớp.

| Path tương đối | Thời điểm user tải | Size/SHA256 local | Commit/gói ứng viên +hash cùng path | Kết quả | Giới hạn |
|---|---|---|---|---|---|
| [file đại diện] | Chưa có | Chưa tính | Chưa đối chiếu | CHƯA KIỂM | Không suy toàn bộ release từ một file |

Khớp vài file chỉ chứng minh **những file đó**; cùng file có thể không đổi qua nhiều commit nên không nhận diện duy nhất commit. File khớp các gói khác nhau/khác hash có thể là host trộn bản; ghi “chưa xác định source toàn bộ”. Xác nhận toàn release cần inventory/hash đủ runtime và dependency, marker/config riêng do user xác minh. Hiện chưa tải file/listing, inventory vẫn **CHƯA THU THẬP**.

### Thử tài khoản DB runtime mới trên DB test — kế hoạch chưa chạy

Chỉ sau user xác nhận **DB test cô lập**, dữ liệu giả/đã khử nhạy cảm và backup; không tạo DB test hay GRANT trong bước này. Không đổi credential host để thử. Quản trị tạo tài khoản runtime test với quyền dự kiến và tài khoản DDL riêng; không dùng tài khoản quản trị để chạy ứng dụng rồi coi quyền runtime đã đạt.

**Rà source chỉ đọc22/10:** tìm LOCK TABLES/UNLOCK TABLES, CREATE TEMPORARY TABLE/CREATE TABLE/ALTER TABLE/DROP TABLE trong ITA DAO/service/security/database/bootstrap và PmDb/PmAuth dùng chung không thấy câu lệnh khớp. Không chạy ứng dụng/DB; không suy kết quả này cho migration, fixture, legacy hoặc vendor ngoài luồng ITA đã rà. `SELECT … FOR UPDATE` và CTE `WITH` hiện có không phải LOCK TABLES hoặc tạo bảng tạm bằng DDL. Vì vậy **chưa đề xuất cấp LOCK TABLES hoặc CREATE TEMPORARY TABLES**. Nếu sau này runtime thực sự cần: xác định đúng query/tính năng và phạm vi bảng/schema rồi trình quyền riêng tối thiểu; không cấp CREATE/ALTER/DROP để thay CREATE TEMPORARY TABLES, không cấp LOCK TABLES toàn server để chữa lỗi không rõ nguồn.

**Lượt “app không phát sinh DDL” trên DB test — chưa chạy:** khởi động ứng dụng dưới credential runtime mới không có DDL, đăng nhập rồi mở shell/data/tài sản/danh mục/import/phân bổ/giấy phép/ticket/vòng đời/ngân sách/dashboard/cảnh báo/báo cáo theo3 vai trò. Quan sát DB audit/query log hiện có bằng tài khoản quản trị quan sát được phép, giới hạn đúng user/schema/thời điểm; không tự bật general log toàn server hoặc ghi dữ liệu nhạy cảm. Kiểm không có CREATE/ALTER (kể cả CREATE TEMPORARY TABLE) được thử rồi bị từ chối; cũng rà DROP/TRUNCATE/LOCK TABLES bất ngờ. Không có log phù hợp thì ghi **chưa xác nhận không DDL**, không suy từ màn hình200. Lỗi DDL bị code nuốt vẫn là không đạt; truy nguyên query, không tăng grant. Không đưa bước migration/fixture vào lượt runtime này.

- Ghi `SELECT CURRENT_USER()` và `SHOW GRANTS FOR CURRENT_USER()` qua đúng kết nối **tài khoản runtime test** bằng công cụ được phép; che tên user/host nhạy cảm trong hồ sơ. Kiểm effective grants/role được kích hoạt nếu DB hỗ trợ role, không chỉ grant trực tiếp. Không có CREATE/ALTER/DROP/GRANT OPTION/FILE/quyền rộng toàn server, không DELETE khi chưa có nhu cầu xác nhận. Chưa cấp/thu quyền hoặc chạy lệnh trên host.
- Chạy lại login username/email, hash upgrade/last_login và logout/limiter; ba vai trò, đọc/đổi quyền/phạm vi và audit. Kiểm SELECT/UPDATE dc_users đúng cột cần thiết; role mapping và scope không suy từ store.
- Chạy tài sản/danh mục/tìm kiếm/chi tiết/xóa mềm; import template/upload/preview/confirm/errors và export; giao/nhận/thu hồi/PDF; giấy phép cấp/thu hồi/vượt chỗ/reauth reveal; ticket tạo/gán/chuyển trạng thái/chi phí/ready/lịch; vòng đời/dashboard/alerts/báo cáo; cấu hình ngân sách/lưu/tính lại/snapshot/export. Mỗi thao tác đúng quyền được lưu/đọc lại, hành vi trái quyền vẫn bị chặn.
- Kiểm transaction isolation, SELECT FOR UPDATE và lock thực với bộ grant dự kiến; rollback khi audit lỗi và hai phiên đồng thời. Nếu thiếu quyền gây lỗi thì xác định đúng query/bảng/cột, không cấp DDL/DELETE toàn schema để làm test qua. Audit insert và các UPDATE thu hồi/xóa mềm không cần DELETE theo source đã rà.
- Xác minh tài khoản **không có quyền DDL** từ effective grants; chỉ test lệnh DDL bị từ chối khi có kế hoạch riêng được duyệt trong DB test, không thử phá bảng thật. Ghi lệnh/kết quả thực và mục chưa chạy; không suy SHOW GRANTS thay test luồng ứng dụng.
- Trước áp dụng host: user review kết quả, source tương ứng và bảng quyền tối thiểu; backup config private, chuẩn bị credential runtime cũ còn khả dụng để rollback qua kênh quản trị. Không đặt mật khẩu trong log/Git. Việc đổi host là thao tác riêng cần chỉ đạo, không được suy từ kế hoạch này.

### Xác nhận loại tmp khỏi đóng gói — rà source22/10, không tạo gói

Đã đọc ba script đóng gói hiện có, **không chạy script**:

| Script | Cách chọn payload hiện tại | tmp/host-inventory có được chọn? |
|---|---|---|
| scripts/package_ita_phase03.py | RUNTIME và DOCS là danh sách tệp cố định, manifest chỉ từ payload đó | Không; không lấy thư mục tmp/ |
| scripts/package_ita_pdf.py | RUNTIME cố định + cây classes/ita-pdf/vendor | Không; không lấy tmp/ ở root workspace |
| scripts/package_ita_host.py | Prefix/exact runtime cho phép + các cây dependency/font/handoff chỉ định, manifest từ payload | Không; tmp/ không thuộc prefix/exact hoặc cây nguồn được gom |

**Toàn bộ `tmp/`, bao gồm `tmp/host-inventory/`, phải bị loại khỏi mọi gói deploy và inventory file trong manifest release.** Không chỉ listing.txt: không đưa bất kỳ bản sao listing hay tệp private đã đổi tên vào payload/handoff. Git ignore chỉ bảo vệ stage mặc định, không phải cơ chế đóng gói.

Xác nhận trên là **đọc đường chọn tệp hiện tại**, không phải đã kiểm ZIP mới hoặc bảo đảm script tương lai không đổi. Ba script lịch sử không phải công cụ release Phase11 đã được nghiệm thu. Khi có yêu cầu tạo gói sau này: rà lại selection, kiểm inventory và ZIP thực tế không có path tmp/ hoặc runtime/tmp/ (kể cả khác hoa thường), không listing private ở path khác; từ chối gói nếu vi phạm. Không zip toàn workspace hoặc thêm tmp vào handoff. Chưa thêm/sửa script đóng gói, chưa tạo manifest/ZIP mới.

Inventory host vẫn **CHƯA THU THẬP**; chưa có listing và “Xác nhận cuối”, không đối chiếu host. Không merge/tag7–10/push/deploy.

### Khuyến nghị cô lập vhost và quyền DB runtime — chỉ tài liệu

Chỉ đề xuất triển khai vào **vhost InfraCore riêng** (`infracore.dung.derasoft.com` theo hồ sơ), document root/marker/PHP-FPM scope riêng được host xác minh; không áp .htaccess/.user.ini/auto_prepend lên domain cha hoặc web khác dùng chung. Inventory thật user điền HOST_INVENTORY_TEMPLATE.md; agent chưa thu thập/chạy gì trên host. Vhost riêng không tự thay thế DB grants, ACL và deny static/legacy.

DB dùng tài khoản runtime riêng cho ứng dụng, giới hạn đúng schema/host kết nối cần thiết. **Không có ALTER/DROP/CREATE**, không GRANT OPTION/FILE hoặc quyền quản trị; migration không chạy bằng tài khoản runtime. Tài khoản DDL riêng chỉ user dùng khi tự chạy migration đã duyệt, credential không đặt trong config ứng dụng/Git/gói. Không phát hành GRANT/REVOKE hoặc đổi user DB trong bước này.

| Nhóm | Quyền runtime dự kiến theo source ITA hiện có | Giới hạn |
|---|---|---|
| ita_ tables được runtime tham chiếu | SELECT/INSERT/UPDATE theo bảng thực sự đọc/ghi | Có thể tách read-only table/audit append-only sau đối chiếu schema/grants; không cấp schema wildcard cho DB khác |
| dc_users | SELECT và UPDATE cần cho password_hash/last_login của PmAuth/ITA auth | Không cần INSERT/DELETE trong luồng ITA; cân nhắc column-level UPDATE nếu hosting hỗ trợ, kiểm FOR UPDATE/grant thực tế trước siết |
| DELETE | **Chưa đề xuất cấp cho bất kỳ bảng nào** | Rà ITA DAO/services/security và PmAuth không có DELETE FROM/REPLACE/TRUNCATE; xóa asset là UPDATE is_deleted, scope/allocations thu hồi bằng UPDATE. Không suy cleanup/rate limit cần DELETE |

Danh sách bảng cần DELETE theo code runtime đã rà: **không có**. Nếu sau này thêm cleanup/clear trash hoặc phát hiện dependency thực sự yêu cầu DELETE, phải liệt kê chính xác bảng/câu lệnh/phạm vi và trình gate, không cấp toàn schema để chữa lỗi. Hạn chế trên áp dụng runtime ITA/legacy tắt, không cam kết chức năng legacy khi khôi phục sẽ chạy với cùng quyền. Không dùng credential DDL làm lối tắt khi runtime bị denied.

Quyền effective/SHOW GRANTS chưa kiểm trên host; DB/host chưa nghiệm thu. SELECT/INSERT/UPDATE mô tả nhu cầu hiện tại, không phải quyền đã cấp. Mọi thay đổi grants phải do user/quản trị thực hiện sau xác nhận môi trường và kế hoạch hoàn tác riêng.

### Checklist bảo mật host bổ sung22/10 — chưa chạy

Xem PHASE11_HOST_TEST.md: bước đầu vẫn isolation kết nối ứng dụng RR/SERIALIZABLE. Kiểm ITA_SESSION HttpOnly/Secure/SameSite=Lax, headers cuối sau proxy/Apache/PHP-FPM (kể cả lỗi/file), display_errors/log_errors và log private; .ita-enabled đúng document root, **không có .ita-legacy-enabled**. Mở trực tiếp docs/tests/.git/artifacts/assets/ita/mock và file cấu hình phải403/404, không chỉ không có listing; không in/tải body config để kiểm. Thử vài URL legacy GET/POST theo checklist an toàn; staging import ngoài public_html, không symlink về webroot, ACL riêng.

**Điểm source còn mở:** JSON MOCK hiện được allowlist static nếu file còn trên host; tests/artifacts GET có thể303, chưa đạt policy deny403/404. Không coi loại khỏi gói là đã bảo vệ file dư trên host. Inventory/guard tại PHASE11_LEGACY_SURFACE.md và PHASE11_PHP_SURFACE.csv. Chưa kiểm host, chưa sửa rule/PHP, chưa tạo gói hoặc deploy; S11-01/S11-02 vẫn chỉ gate nháp.

### F11.5 — Chuẩn bị bàn giao, chưa tạo gói (22/10/2026)

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

## C. Hồ sơ dependency

### Sao lưu dependency ngoài Git ra ngoài máy dev — kế hoạch chưa thực hiện

Mẫu lệnh Windows tạo ba bộ SHA256SUMS và bảng vị trí lưu ngoài máy dev tại **DEPENDENCY_CHECKSUM_WINDOWS.md**. Chỉ là tài liệu PowerShell/Get-FileHash; chưa chạy lệnh, tạo checksum hoặc copy/archive dependency. Mẫu giữ path tương đối root dự án, khác quy tắc path manifest Excel lịch sử nêu bên dưới; không so trực tiếp hash hai manifest khác convention.

Phạm vi: Excel legacy **566 file theo baseline đã ghi**, InterVariable.woff2 +OFL, vendor mPDF đã khóa cùng DejaVu/font/license và composer.json/lock. Số566 là quan sát baseline, phải đếm lại khi sao lưu thực tế; sai lệch cần đối chiếu, không tự bỏ file để khớp số. Không tạo archive/backup hoặc upload trong bước tài liệu này.

1. User chọn nơi lưu riêng **ngoài máy dev** (kho artifact của tổ chức/NAS hoặc thiết bị ngoại tuyến khác), kiểm quyền truy cập và khả năng khôi phục. Git clone không thay backup vendor/font. Không gom config/key/dump/session vào bộ dependency; khóa giấy phép backup riêng như hồ sơ hiện có.
2. Chốt bộ source/version/ref/baseline và license/provenance; Excel/Inter chưa xác minh upstream thì giữ nhãn unknown/baseline hash, không ghi phiên bản đoán. Lập inventory path/size/SHA256 byte thật cho mọi file, SHA256SUMS.txt UTF-8/LF sắp path, có archive hash riêng và số file. Giữ checksum sidecar ngoài archive; không self-hash. Không dùng ZIP hash thay file hash.
3. Khi được giao sao lưu, copy/đưa archive lên nơi lưu riêng rồi **đọc lại từ nơi đó** và kiểm archive/file hash với checksum nguồn. Ghi ngày, người thực hiện, storage reference không chứa credential và kết quả, hạn chế quyền sửa/xóa; giữ bản baseline cũ thay vì ghi đè không version.
4. Thử restore vào thư mục sạch/máy build tách biệt, không đè vendor dev/host: lấy từ nơi lưu ngoài máy dev, kiểm checksum trước giải nén, kiểm đường dẫn an toàn, file count/size/hash/license. PDF installed metadata khớp lock; Excel/font đúng tree/file hash. Không tải latest để bù thiếu file im lặng.
5. Trên runtime build tương thích host, chạy kiểm platform/extensions, mở lại XLSX STRING/formula-like text, render PDF tiếng Việt/DejaVu và kiểm ảnh/text; Inter tải đúng byte/font. Không DB/host cần thiết cho bộ test dữ liệu giả. Chỉ ghi “restore đã kiểm” khi thực sự chạy, kèm PHP version/lệnh/kết quả; hiện **chưa sao lưu ngoài máy dev, chưa thử restore**.

| Bộ | Storage reference/version | Số file +SHA256SUMS/archive hash | Copy đọc lại | Restore +runtime/test | Trạng thái |
|---|---|---|---|---|---|
| Excel baseline566 | [mã tham chiếu kho ngoài, không credential] — chưa xác nhận | excel [số file]; checksum mới chưa có bằng chứng | [khớp/khác/chưa làm] — chưa xác nhận đã so | [chưa làm]; không ghi đã kiểm | Chờ số liệu cụ thể |
| Inter +license | [mã tham chiếu kho ngoài, không credential] — chưa xác nhận | inter [số file]; checksum mới chưa có bằng chứng | [khớp/khác/chưa làm] — chưa xác nhận đã so | [chưa làm]; không ghi đã kiểm | Chờ số liệu cụ thể |
| mPDF vendor/DejaVu/lock/license | [mã tham chiếu kho ngoài, không credential] — chưa xác nhận | pdf [số file]; checksum mới chưa có bằng chứng | [khớp/khác/chưa làm] — chưa xác nhận đã so | [chưa làm]; không ghi đã kiểm | Chờ số liệu cụ thể |

**Phản hồi user ngày22/10/2026, nguyên văn:**

> Tôi đã chạy mẫu SHA256SUMS trên máy local (PowerShell [phiên bản]). Kết quả: excel [số file], inter [số file], pdf [số file]; checksum lưu tại [mã tham chiếu kho ngoài, không credential]. Đã copy dependency sang kho ngoài: [có/chưa]; đã đọc lại và so checksum bản copy: [khớp/khác/chưa làm]; restore thử: [chưa làm].

Các dấu ngoặc vuông còn nguyên trong thông tin nhận được, chưa phải phiên bản/số đếm/kho lưu hoặc xác nhận copy/read-back cụ thể. Ghi nhận lời báo, **không xác nhận kết quả chạy**, không ghi “đã đọc lại”, “khớp” hoặc “restore đã kiểm”. Phần mô tả “chưa thực hiện” phía trên là trạng thái trước phản hồi; trạng thái hiện tại chờ user điền giá trị thật. Agent không chạy mẫu, không đọc checksum/kho ngoài, không tạo gói hoặc thao tác host từ phản hồi này.

### Đề xuất dựng lại dependency Excel/PDF/font — chưa thực hiện

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

## D. Nhật ký theo ngày (mới nhất trước)

### 26/10/2026 — Tạo và kiểm ZIP cộng dồn theo quyền user mới, chưa upload

- Source: `81f3c55821e760472a830ae523f1a662bd6dbe8b`. Gói local `artifacts/ita-host-20261026/infracore-host-test-81f3c55821e7.zip`, **49.319.548 byte**, SHA256 **b8c8945c6c93233c4e9cb7049ec5e13e9d66bd884fc7654071e95e3ba8d4fd08**.
- Payload1419 file/105.076.353 byte:1401 runtime +17 handoff +1 manual-review/.htaccess; thêm MANIFEST.json và SHA256SUMS.txt =>1421 ZIPentries. Manifest ghi release_id/sourcecommit/buildUTC/môi trường/PHPCLI thực8.5.10. Metadatahash vàZIPhash nằm sidecar ngoài gói, không selfhash.
- Reviewinventory SHA256 `33ef20d3c70400d9d1118a2a233d235589cffc53b8dd50c8a918180acfe61b5e`. Builder hydrate lại đúng commit/dependencybytes, từ chối mismatch rồi mở lại **ZIP thật**, đối chiếu toàn bộ path allowlist/exclusions/case/link, CRC, size/SHA256 từngentry, manifest/checksums: **đạt kiểm cấu trúc và toàn vẹn ZIP**. Không suy thành PHP8.3/DB/downloadhostpass.66synthetic validatorchecks +Pythoncompile đạt trước build. Review độc lập đã bổ sung metadataF11.5 còn thiếu.
- Dependency: Excel566 file baseline tree88815432… đúng, chọn565 sau loại workbookTranslations.xlsx dùng pháttriển; PDFvendor697 nguồn/chọn696 sau loại tmpmarker, packageversion/refs khớp lock; Inter2file/hash baseline khớp. Không thayvendor hoặc khẳngđịnh Excel/Inter upstreamversion; chưa backup/restore ngoài máydev được xácnhận.
- Không có tmp/tests/scripts/artifacts/.git/.playwright-cli/MOCK/preview/configlocal/.env/dump/session hoặc markerlegacy trong runtime. FileSQL đãduyệt chỉ ởhandoff/sql, không autoexecute;005 không kèm để importmù. .htaccess tách manualreview cóhashngoạilệ để user rà riêng, không tự ghiđèhost; không .user.ini/auto_prepend/HSTS mới. Không xóa filedưhost.
- Hướng dẫn `docs/HOST_UPLOAD_20261026.md`: backupfile sẽthay +DB/keyprivate, xácminhnền/entrypoint/Smarty/config/schema/isolation/grants trước upload; user chỉ upload **nội dungruntime/** bằngFileZilla. Rollback đúngsourcebackup, giữdữliệumới/hash/last_login/audit, khôngDROP/restoreDBđè. Testhost tài khoảntest/dữliệugiả, ghi **«kiểm thủ công trên host, phạm vi hạn chế»**, không nghiệmthuDBtựđộng.
- Lệnh thật: `python scripts/prepare_ita_release.py --source 81f3c55 --output artifacts/ita-host-20261026/review-81f3c55.json`; `python scripts/build_ita_reviewed_release.py --review artifacts/ita-host-20261026/review-81f3c55.json --output artifacts/ita-host-20261026/infracore-host-test-81f3c55821e7.zip --php-version 8.5.10`. Report/sidecar cùngbasename trong thư mục artifactđãignore, khôngcommit payload. Agent **chưa upload/DB/push/merge/tag7–10**.

### 22/10/2026 — Dashboard/báo cáo #10 chỉ có trên local

Source a017327 → 0ca9a7e gồm7 commit F10.1–F10.7, kèm fix điều hướng0ce2166 đã build/tự kiểm; xem PHASE10_VERIFY.md. **Chưa upload, chưa tạo gói deploy #10, chưa xác nhận source host.** Không DDL/seed mới. Cần đủ PHP/bootstrap/ita_mode/audit/Smarty/JS/CSS và ECharts6.1.0, không chỉ giao diện.

Checklist host tại PHASE10_HOST_TEST.md: PHP/GD/mbstring, display_errors Off/log_errors On, RR/SERIALIZABLE, auth_key/ITA_LICENSE_KEY private; kiểm dependency/font theo hồ sơ nguồn bên dưới. Chưa chạy Composer hoặc đổi server config. Rollback source giữ dữ liệu/audit mới, không restore đè DB. DB Phase1–10 chưa nghiệm thu; không merge/tag7–9/push/deploy.

### Hồ sơ host đã rà21/10/2026 — đối chiếu lịch sử

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

### 21/10/2026 — Checklist host trước kiểm thử Phase 1–9 (chưa thực hiện)

Đây là checklist cho user/quản trị host, không phải kết quả kiểm tra hoặc quyền deploy. Phase 1–9 giữ trạng thái DB **chưa nghiệm thu**; không merge/tag Phase 7, 8, 9 trong lúc chờ xác nhận cụ thể. Không import lại migration hoặc tạo DB từ checklist này.

- [ ] Ghi môi trường đích, ngày kiểm, **phiên bản PHP thực của site/PHP-FPM** và phiên bản PHP CLI nếu dùng CLI. Đối chiếu khả năng chạy source và thư viện hiện có; CLI chạy được không chứng minh PHP-FPM có cùng cấu hình.
- [ ] **display_errors tắt khi tải Excel/PDF** trên đúng site/PHP-FPM; lỗi ghi vào log riêng được bảo vệ, không chen cảnh báo/HTML vào bytes tải. Kiểm MIME/chữ ký và mở lại file thật. Cảnh báo deprecation PhpSpreadsheet/ZipStream local còn được ghi nhận, chưa nâng vendor; không tắt toàn bộ error_reporting để che lỗi.
- [ ] **gd + mbstring** được nạp trong PHP-FPM phục vụ site để xuất PDF tiếng Việt; kiểm thêm dependency hiện có của Excel và OpenSSL/AES-256-GCM cho giấy phép. Bật GD per-process local trước đây không xác nhận extension trên host.
- [ ] Ghi phiên bản MySQL/MariaDB, engine các bảng liên quan và **transaction isolation của kết nối ứng dụng: REPEATABLE READ hoặc SERIALIZABLE**. Writer ngân sách từ chối isolation khác trước ghi. Không tự đổi cấu hình DB server. Kiểm rollback khi audit lỗi và hai phiên đồng thời trong môi trường/phạm vi user đã xác nhận phù hợp; ghi kết quả, không suy từ mock.
- [ ] **ITA_LICENSE_KEY** đã đặt cho đúng PHP-FPM pool/site, là base64 giải mã đúng32 byte và OpenSSL hỗ trợ AES-256-GCM. Dùng hướng dẫn PHASE06_HOST_TEST.md và script check_ita_license.php; kết quả CLI không thay kiểm môi trường PHP-FPM. Không in khóa vào chat, ảnh, Git, HTML hoặc log; không tạo khóa production tự động hay thay khóa đang dùng để chữa lỗi.
- [ ] Backup khóa giấy phép riêng, ngoài Git/document root và tách khỏi dump DB; backup source/DB trước triển khai. Kiểm khả năng khôi phục khóa tương ứng với ciphertext, không dùng khóa dev cho host.

User gửi xác nhận kiểm thử DB cụ thể Phase1–9 (môi trường, phiên bản, engine, CHECK ticket_events, rollback/audit, hai phiên, các mục chưa kiểm) rồi mới cập nhật nghiệm thu. Agent chưa chạy checklist, chưa đọc secret, chưa deploy. Phase10 chỉ có tài liệu BE-GATE, chưa bắt đầu BE.

### 21/10/2026 — Gói cải tiến giao diện ticket, chưa upload

- Gói: artifacts/ita-phase07/ita-ticket-ui-update.zip. Source cố định và SHA256 từng file trong MANIFEST.json của gói.
- Runtime7file FE (ba CSS, hai JS, layout/partial); không PHP/SQL/config/dump/vendor. Nền host cần routing60f4f49.
- Đã kiểm Smarty +43browser API mock checks, viewport1366/768/390 và reduced-motion; không đồng nhất với DB UAT.
- Agent chỉ chuẩn bị gói. User backup7file, upload nội dung runtime vào public_html, Ctrl+F5; không upload ZIP/manifest. Không migration.
- Rollback source khôi phục backup tương ứng, giữ DB/config/keys; chi tiết PHASE07_UI_VERIFY.md. Phase7 chưa merge.


### 21/10/2026 — Bổ sung bộ chọn thiết bị cho form ticket, chưa upload

Gói artifacts/ita-phase07/ita-ticket-picker-update.zip:7runtime FE cộng dồn, gồm cải tiến fcbef20; source commit và SHA256 từng file trong MANIFEST. Không PHP/SQL/config/dump/vendor, không cần gói UI trước. Cần routing60f4f49 đã kiểm host.64browser mock checks và Smarty/syntax/detector/review đạt; user kiểm picker bằng DB host sau upload. Backup7file, upload nội dung runtime, Ctrl+F5; không upload ZIP/manifest/handoff; rollback source giữ DB/config. Agent chưa deploy hoặc merge Phase7.


### 21/10/2026 — Vòng đời tài sản, chuẩn bị gói nhưng chưa upload

- Gói: artifacts/ita-phase08/ita-lifecycle-update.zip. Nền là bản Phase7 qua 2aa3c98, gồm giao diện ticket và bộ chọn thiết bị trước đó. Source cố định và hash từng file trong MANIFEST.json.
- Runtime 11 file: hai DAO lifecycle, rules, ItaApplication, bootstrap, JS shell/lifecycle, CSS lifecycle, layout/sidebar/partial lifecycle. Không config, SQL, seed, fixture MOCK, session, dump hoặc vendor mới.
- Không migration. User backup các file tương ứng và DB trước triển khai theo quy trình; upload nội dung runtime/ vào public_html, không upload ZIP/manifest/handoff. Ctrl+F5, mở #lifecycle.
- Handoff gồm hướng dẫn kiểm host, tính tay và EXPLAIN; chỉ EXPLAIN đọc, không ALTER. Không chạy trên DB nếu chưa xác định môi trường.
- Rollback source khôi phục file backup; giữ dữ liệu/config/keys. File mới có thể để lại không được layout/bootstrap cũ gọi; không DROP hoặc restore đè dữ liệu mới.
- Agent chưa upload/deploy, chưa merge/tag Phase7 hoặc8. Phần DB giữ CHƯA XÁC NHẬN đúng nguyên văn user trong PROGRESS.


### 21/10/2026 — Cải tiến giao diện vòng đời, chưa upload

Gói artifacts/ita-phase08/ita-lifecycle-ui-update.zip gồm12runtime files cộng dồn Phase8 (11file đã duyệt và CSS motion), source cố định/hash từng file trong manifest. Gói thay thế ita-lifecycle-update.zip; không cần upload cả hai. Nền vẫn là Phase7 qua2aa3c98. Không SQL/config/fixture/dump/vendor mới; backend giữ nguyên commit0c2e0a7.

User backup file tương ứng, upload nội dung runtime vào public_html rồi Ctrl+F5; không upload handoff/manifest/ZIP. Kiểm bộ lọc/xóa lọc, chuyển nhóm, xem/đóng lý do và số liệu gốc theo hướng dẫn host. Rollback khôi phục source backup, giữ DB/config/keys.63browser mock checks và review đạt; không có xác nhận DB mới, không merge/tag/deploy.

### 20/10/2026 — Gói upload cộng dồn đến Phase7 theo yêu cầu user

User xác nhận chỉ kiểm được trên host với DB hiện có, yêu cầu bắt đầu chuẩn bị gói. Agent chỉ đóng gói local, không upload/DB/merge/push. Source runtime cố định `80929beff9bb0675b19428df63d6f4316a706caf`, dùng cho host Phase2 đã đăng nhập được.

- ZIP: `artifacts/ita-phase07/infracore-host-through-phase07-80929beff9bb.zip`, 48.553.362 byte; 1.340 runtime files +6 handoff files và MANIFEST.json.
- SHA-256: `128748eb34c3c59beac605c19335642cac119df23c383d4f283d282b3e0d8f9b`. Đã đọc lại inventory, CRC và hash từng mục. Manifest ghi source commit, nguồn dependency và hash; PDF vendor khớp composer.lock. Excel dependency/font là snapshot bộ cục bộ hiện có, không tuyên bố chúng tracked trong source Git.
- Không chứa private config, auth/key secret, SQL/dump, session/log, dữ liệu MOCK, tests. Không mang workbook Translations.xlsx dùng duy trì dịch thuật của thư viện. Bao gồm dependency Excel/PDF/font, không cần Composer hoặc Node production.
- User giải nén và upload **nội dung runtime/** vào `/domains/infracore.dung.derasoft.com/public_html`, giữ cấu hình host, .ita-enabled và mã nền admin.php/Smarty/PmAuth/PmDb hiện có. Không upload handoff/manifest/ZIP. Hướng dẫn backup/rollback và điều kiện PHP/khóa: HOST_UPLOAD_PHASE07.md.
- Bằng chứng user mới: selected_database=dung_infracore, MariaDB10.6.28, check_constraint_checks=ON trong phpMyAdmin, 5 bảng chính đều InnoDB. Đây chưa phải DB UAT nghiệp vụ/rollback/concurrency hoặc CHECK enforcement của PHP session. Chưa merge Phase7; chưa sang Phase8.

### 20/10/2026 — Ticket local, chưa triển khai hoặc merge

User duyệt #07 kèm chỉnh sửa và #07-SCHEDULE. Đã build/kiểm local ticket, guard mọi writer, lịch ban đầu và periodic, lịch sử gộp/statistics. Không DDL, không SQL host, không tạo/kết nối DB hoặc upload. Commit lịch ban đầu ddc9c87; runtime ticket lưu trên nhánh feature/ita-phase07-tickets, không merge trước user UAT DB Phase1–7. Host/CHECK/EXPLAIN/concurrency chưa kiểm chứng. Checklist PHASE07_HOST_TEST.md; không dùng gói Phase5 cũ hoặc copy toàn repo để deploy. Chưa tạo gói phát hành mới.

### 20/10/2026 — PDF hoàn thiện local, chưa deploy host

BE-GATE #05-PDF được user duyệt 19/10, mPDF8.3.1 +7 dependency khóa riêng; GD local chạy per-process, không sửa php.ini hoặc server. F5.4 render/PDF text/font/pages/signatures, adapter scope/audit và browser mock đã kiểm; host/DB/download thực chưa UAT. Không migration/SQL mới.

User đã cho phép origin và push nhánh đang làm: checkpoint bfe3539 đã push feature/ita-phase05-pdf; bản PDF được commit/push riêng sau kiểm chứng. Đây là lưu mã, không deploy production hoặc merge main.

Builder scripts/package_ita_pdf.py tạo delta local từ source committed, gom vendor/font/license có manifest/hash và kiểm đọc lại; chỉ dùng khi user chủ động yêu cầu bàn giao. Delta phụ thuộc checkpoint, không phải bộ cài toàn app. Rollback code: khôi phục source/vendor cũ đã backup, giữ assignment/audit/DB; không DROP/restore dump đè dữ liệu hoặc sửa config host. Host cần GD/mbstring/zlib, temp ngoài webroot ghi được và runtime dependency đầy đủ; Composer chạy ở máy build, không production.

### 19/10/2026 — Phase 5 chỉ build local

BE-GATE #05 đã duyệt, code phân bổ/thu hồi/acknowledge/history/maintenance badge đã nối; không migration mới, không cài PDF dependency, không DB/host/deploy. Source còn working tree, chưa có release/manifest Phase5. Không upload toàn repo hoặc dùng gói Phase3 cũ. PHASE05_VERIFY.md ghi kết quả pure/scripted/browser mock; PHASE05_HOST_TEST.md ghi UAT còn lại. Không tạo PDF giả hoặc công bố phase-done.

### 19/10/2026 — Phase 4 chỉ build/verify local

User đã duyệt BE-GATE #04. Code F4.1–F4.5 có trong working tree; không tạo DB, không chạy SQL host, không upload hoặc tạo gói phát hành mới. Chưa có manifest/release cho Phase 4. Giữ private config host riêng, không copy toàn repo. `PHASE04_VERIFY.md` ghi bằng chứng local; `PHASE04_HOST_TEST.md` là checklist khi user sẵn sàng UAT. Không dùng gói Phase 3 cũ bên dưới cho thay đổi mới. DB transaction/collation/concurrency/performance chưa nghiệm thu.

### 19/10/2026 — Bàn giao Phase 3, chưa deploy

**Đính chính sau chỉ đạo user:** chưa upload production. Snapshot `b965bd30a9b4` dưới đây là bản cũ, không còn gồm các sửa local cuối (status required/category failure). Không dùng gói này để deploy; chờ user chủ động bắt đầu bàn giao sau. Phạm vi local đã kiểm 33 pure/scripted +30 browser API mock, không DB.

- User duyệt build và không tạo DB test. Agent không truy cập DB host hoặc thực hiện deploy.
- Snapshot delta: `artifacts/ita-phase03/infracore-phase03-b965bd30a9b4.zip`, 14 runtime files; archive SHA-256 `f00ab1f317b38671d8a686b4ceceacb43e3d8e0d90423cedfd36df1915d58248`. Bên trong có manifest SHA-256 từng file, đã đọc lại zip và đối chiếu hash.
- Snapshot từ working tree đã review, chưa phải release/tag phase-done; runtime còn chờ UAT SQL/read-back thực. Không thay cấu hình private hoặc auth_key; package không có config, dump, tests, session hay seed.
- Gói chỉ cập nhật host đang chạy Phase 2. User backup DB/source, giải nén trên máy và upload **nội dung runtime/** vào `/domains/infracore.dung.derasoft.com/public_html`, giữ đường dẫn, không tạo thêm thư mục runtime trên host. Không upload docs/manifest vào web root.
- Không cần migration mới hoặc import lại Phase 1/2. Hướng dẫn PHASE03_VERIFY.md và checklist PHASE03_HOST_TEST.md. Nếu lỗi, dừng ghi và khôi phục runtime từ backup, giữ dữ liệu mới; không DROP/DELETE/import dump làm lối tắt.

### Xác nhận từ user sau import SQL host

Ngày 16/10/2026: user báo «đã thành công» sau hướng dẫn chạy phase01_verify.sql. Ghi nhận user xác nhận tạo bảng trên dung_infracore/MariaDB hosting thành công; agent không kết nối host và chưa nhận ảnh bộ đếm 12/12/12 hoặc danh sách đủ 11 khóa ngoại sau lần import cuối. Không suy diễn xác nhận này thành quyền seed, sửa PHP/routing hoặc deploy. Migration F1.1–F1.4 đã bàn giao và được user xác nhận chạy; ứng dụng chưa tích hợp DB.

### Đính chính xác nhận hosting

Ảnh user gửi tiếp theo cho thấy selected_database=dung_infracore nhưng truy vấn metadata dùng DATABASE() trả 0/NULL/NULL, không thấy FK. Chưa xác nhận tạo đủ 12 bảng trên host; kết luận thành công trước đó chỉ dựa trên lời báo của user và được thay bằng «chờ chẩn đoán». Trước đó import có thông báo #1050 ita_departments already exists. Chưa rõ nguyên nhân hai quan sát khác nhau; không mặc định bảng bị mất hoặc migration thất bại. Đã thay bộ lọc metadata trong preflight/verify bằng tên schema cố định để giảm phụ thuộc ngữ cảnh truy vấn. Cần SHOW TABLES và SHOW CREATE TABLE với schema chỉ định rõ. Không chạy lại CREATE hoặc xóa bảng khi chưa đối chiếu.

### Bằng chứng SHOW TABLES trên host

User gửi ảnh SHOW TABLES FROM dung_infracore LIKE 'ita%': liệt kê đủ 12 tên bảng đúng migration. SHOW CREATE TABLE dung_infracore.ita_departments trả về cấu trúc nhưng ảnh bị rút gọn. Đã xác nhận tồn tại đủ bảng; không chạy lại CREATE. Chưa xác nhận toàn bộ cột/engine/collation/11 FK qua ảnh này. Nguyên nhân truy vấn information_schema trước đó dùng DATABASE() trả 0 chưa xác minh; không kết luận bảng chưa được tạo. File verify đã dùng tên schema cố định.

### Xác nhận số lượng khóa ngoại trên host

Ảnh mới từ user cho thấy truy vấn information_schema.TABLE_CONSTRAINTS với CONSTRAINT_SCHEMA='dung_infracore' và loại FOREIGN KEY trả tổng 11 dòng. Kết hợp SHOW TABLES trước đó: đã xác nhận đủ 12 tên bảng và 11 khóa ngoại trên MariaDB host. Chưa có ảnh bộ đếm engine/collation 12/12/12 hoặc toàn bộ định nghĩa cột. Không yêu cầu chạy lại CREATE. Quyền PHP/routing và seed vẫn chưa được user phê duyệt.

### File kết quả ita_settings.sql từ user

Đã đọc tests/ita_settings.sql, không thực thi: có 6 cặp setting_key/setting_value khớp danh mục khởi tạo (budget_contingency_percent=10, currency=VND, display_timezone=Asia/Ho_Chi_Minh, license_critical_days=7, license_warning_days=30, warranty_warning_days=30). INSERT chỉ xuất setting_key và setting_value, không có store_id trong khi CREATE định nghĩa store_id NOT NULL không default; file này không đủ để xác nhận scope store_id=1 hoặc dùng như bản backup phục hồi nguyên bảng. DDL xuất cũng không thể hiện đầy đủ engine/collation/CHECK. Không import lại file. Chưa nhận kết quả categories/departments sau chạy catalog; cần SELECT đếm ba nhóm theo store_id=1.

Gói local đã tạo và đọc lại toàn bộ 710 mục từ commit `5f1e6ea4f5fafef08e51be4b9a08641b5dbf34e0`: `artifacts/ita-phase05/ita-pdf-5f1e6ea4f5fa.zip`. SHA-256: `7d0149bcd1c89aad417a526522a3ab2d1bfcde84c9259ed5e09ba2da43443dec`. Gói chỉ lưu local, chưa upload/deploy.
