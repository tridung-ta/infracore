# Kiểm trên local, DB cô lập — kế hoạch 26/10/2026, CHƯA CHẠY

Chỉ thực hiện sau câu **«Tôi xác nhận cho chạy trên DB local [tên DB]»** cho đúng schema user tự tạo theo LOCAL_RUN.md. Không đọc config/credential host; không kết nối DB lúc soạn. Không chạy các fixture/reset DB cũ. Mọi kết quả ghi nhãn **«kiểm trên local, DB cô lập»**, không biến thành kiểm host hoặc tự merge/tag7–10.

## Thứ tự và điều kiện

1. Ghi source commit/diff, endpoint loopback không tunnel, DBname, PHP CLI/Apache8.3.x và MariaDB10.6.x thực, vhostHTTPS, engine/collation/CHECK/FK, CURRENT_USER/SHOW GRANTS của runtime thực. Chỉ lưu bằng chứng đã che credential.
2. Test S26 trước, dummy khớp PASSWORD_DEFAULT của runtime8.3; unit/scripted/export với đếm deprecation (không hạ mask). Sau đó migration0–6 và seed mới đúng môi trường đã xác nhận; ghi thực tế từng bước, không điền PASS từ source.
3. Dùng checklist chức năng A–L đã rà ở PHASE01_10_HOST_CHECKLIST_REVIEW.md, nhưng chạy local và sửa nhãn kết quả; C1=3A+3B. Account fake/MD5 upgrade chỉ trong local. I1 dùng fixture lịch sử2003/3006 tại LOCAL_RUN thay hạn chế host; không dùng SQL ghi trên host.
4. Kiểm các mục đặc thù dưới, rồi UI thật1366/768/390, ba vai trò/GETngoàiscope/CSRF/revision/export. Giữ nguyên các giới hạn sản phẩm và snapshot/version, không sửa code để né test.

## CHECK ticket_events: phải chứng minh enforcement, luôn ROLLBACK

Dùng runtime test/DDL phù hợp có INSERT sự kiện trên DB local đã xác nhận. Xác minh ENGINE=InnoDB và check_constraint_checks=ON của **chính kết nối kiểm**. Ghi count trước của ticket giả4001. BEGIN; INSERT event (store1,ticket4001,from_status='resolved',to_status='pending',user900001,note='TEST invalid CHECK'); phải nhận lỗi4025 MariaDB và tên **ck_ita_events_transition** (3819 nếu thực tế MySQL, ghi khác môi trường). Trong finally luôn ROLLBACK, kể cả INSERT được chấp nhận. Count sau bằng trước, không có event invalid. INSERT được nhận => KHÔNG ĐẠT dù rollback; FK/permission/syntax error khác => chưa chứng minh CHECK, không PASS. Gap AUTO_INCREMENT sau rollback chấp nhận được. Không chạy trong host.

## Rollback khi audit lỗi — InnoDB, không lấy login làm mẫu

- Đăng nhập phiên fake trước; ghi asset TEST mã mới chưa tồn tại và count/audit baseline. Backup local và giữ kết nối quản trị độc lập.
- Quản trị local tạm REVOKE INSERT **riêng bảng ita_audit_logs** của runtime. Kiểm SHOW GRANTS/effective role: nếu còn INSERT schema/global/role, không có fault thật và không thử để kết luận. Runtime phải grant từng bảng theo LOCAL_RUN.
- Qua UI thật tạo asset TEST mới bằng phiên có sẵn. Kỳ vọng boundary503, không asset mới/persistence, không audit thành công; quan sát DB từ kết nối đọc độc lập sau request. Không coi FEtoast là bằng chứng rollback.
- **finally** quản trị cấp lại đúng INSERT audit ban đầu, SHOW GRANTS xác nhận, tạo lại thao tác fake và đọc lại asset+audit thành công. Nếu không restore được, dừng test; không dùng DDL credential thay runtime trong app.
- Login/native dc_users MyISAM không cùng transaction: không tuyên bố audit rollback được last_login/password_hash. Không xóa rows/dump-restore để làm test đạt. Grant/revoke chỉ kế hoạch chưa thực thi; không test fault này trên host.

## Isolation READ-COMMITTED →503

Đầu tiên xác minh RR/SERIALIZABLE của **kết nối app** và dashboard/alert/report success. Trong một harness local tương lai với explicit runtime config/ack (chưa viết/chạy), SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED trên cùng connection rồi gọi service thật dashboard/report/forecast; yêu cầu503 trước đọc snapshot/ghi và callback nghiệp vụ không chạy; kiểm DB không có ghi. Restore session ban đầu trong finally. Không đổi GLOBAL hoặc dùng phpMyAdmin session khác để chứng minh app503.

Để xác nhận HTTP thật: chỉ trên instance MariaDB local dành riêng với kế hoạch đổi default/khởi tạo lại connection PHP có ghi giá trị trước/sau, user duyệt thao tác riêng. Sau test phục hồi RR, mở connection mới và kiểm dashboard lại. Không tự đổi server hiện tại, không sửa guard/runtime để giả RC. Tách kết quả harness service và HTTP, không gộp PASS.

## Hai phiên đồng thời thực

Hai browser profile/session riêng localHTTPS, không hai tab chung cookie. Dùng cùng hồ sơ TEST; dựng barrier thao tác bằng công cụ khi được giao, lưu thứ tự request/response và kết quả DB, không suy đồng thời từ hai thao tác tuần tự.

| Tình huống | Kỳ vọng |
|---|---|
|Hai tạo pending ticket cùng asset fake|Một thành công, một bị chặn; đúng1 pending, status asset nhất quán, audit tương ứng|
|Hai cấp chỗ cuối license7001|Vai trò không được override, hoặc Admin không bật override; không vượt2 seats, không allocation trùng|
|Hai sửa asset/cost/config/snapshot cùng version|Stale bị409; không mất cập nhật, audit không báo thành công cho ghi bị từ chối|
|Revoke mapping/native lúc export render|Chỉ local account fake. Cần harness/barrier có kiểm soát sau fetch/trước bytes; không đoán bằng click nhanh. Không bytes/audit thành công khi guard từ chối; nativeMyISAM race giới hạn ghi riêng|
|Dữ liệu đổi lúc report render/audit lỗi export|Kiểm transaction/snapshot/token đúng contract của từng export; không giả test tuần tự thành race|

Native status/MD5 write test chỉ trong DB local fake đã được xác nhận; không thay tài khoản host. Nếu chưa có harness concurrency thì ghi CHƯA KIỂM, không coi unitfake là DBpass.

## Dữ liệu lớn, PDF và EXPLAIN

- Chạy load trước UI thêm asset: tổng5000, loại2005=500, cohort budget2003 giữ nguyên. Ghi counts thực, cùng store/scope/filter/mốc thời gian; dữ liệu nhỏ/5k tách lượt. Warm-up và5 lượt đo có nguồn ghi rõ, báo median/max, không chọn chỉ lần nhanh.
- Dashboard: Network request tổng quan/chart/alerts thời gian server/end-to-end, response size, HTTPstatus; peak PHP memory đo bằng profiler/harness private local có memory_get_peak_usage quanh request/service thật, không coi browserheap là PHPmemory. Ghi memory_limit, max_execution_time, Apache/PHPversion và cache state. Chưa có ngưỡng SLA duyệt: báo số thực và lỗi/timeouts, không bịa PASS hiệu năng.
- PDF inventory filter category2005/A lấy đúng500, render tiếng Việt/font/bảng/byte/MIME/header, mở lại và xem trang đầu/giữa/cuối, đo thời gian/peakmemory. Mở file thật, không mock. Tạo thêm1 asset fake qua UI khi tới boundary:501 phải từ chối, không cắt ngầm; ghi thêm này làm tổng5001, dashboard5k đo trước. Excel inventory5000/5001 và báo cáo khác1000/1001 kiểm contract riêng, không đổi cap.
- EXPLAIN SELECT thật với bind được thay bằng literal fake an toàn: PHASE08/09/10_EXPLAIN và network/listing có source thực; ghi query/ref, filter, cardinality/key/rows/Extra cùng timings trên dataset lớn. ALL/filesort/temporary không tự là FAIL. Index đề xuất sau bằng chứng cần gateSQL riêng; không ALTER tự động.
- Tải test không chạy host. Không ép forecast đọc5000 vượtguard2000/network200; lọc đúng phạm vi hoặc kiểm422 theo contract.

## Mẫu kết quả và smoke host sau upload

| Mã | Môi trường/source/runtime | Kỳ vọng | Kết quả Đạt/Không/Chưa kiểm | Bằng chứng đã che | Giới hạn |
|---|---|---|---|---|---|
|LOCAL-CHECK|kiểm trên local, DB cô lập; chưa có|Lỗi đúng constraint, rollback sạch|Chưa kiểm|Chưa có|Không thay CHECKhost|
|LOCAL-AUDIT/RC/RACE/LOAD/PDF/EXPLAIN|Chưa có|Theo từng mục trên|Chưa kiểm|Chưa có|Không hostpass|

User xác nhận riêng kết quả nào mới ghi PROGRESS mục đó. Dù local đạt, sau đóng gói/upload được phép vẫn cần **smoke host ngắn** do user: source/hash đúng, PHPextensions/dependency/font/khóa, HTTPS thực, cookie HttpOnly/Secure/SameSite, headers/CORS/no-store/display_errors cuối; login3vai trò (biết trước hai cột native ghi), dashboard không503, GETngoàiscope403, một file export giả mở lại, audit đọc được. Isolation kết nối host vẫn ghi bằng chứng riêng. Không stress/revokegrants/race trên host hoặc tự coi local là nghiệm thu host. Không đóng gói/upload trong bước này.
