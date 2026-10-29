# Đối chiếu yêu cầu Phase 0–14 — 28/10/2026

Nguồn rà: yêu cầu 10 nhóm chức năng user gửi lại, PLAN_FINAL, CURRENT_BUILD_STATUS, các báo cáo verify và source tại HEAD `65465bf`. Đây là rà source/hồ sơ; không chạy lại toàn bộ test, không PHP/DB/host, không tạo ZIP hoặc upload. Working tree có thay đổi riêng của user ở PHASE11_SECURITY_TEST_20.md; không sửa hoặc đưa thay đổi đó vào kết luận kiểm thử mới.

**Kết luận: có implementation cho cả 10 nhóm chính, nhưng chưa đủ căn cứ đánh dấu hoàn tất Phase 0–14 hoặc phát hành một gói được mô tả là đã kiểm đầy đủ.** Đặc biệt Phase14 mới hoàn tất source: SQL007/mapping/kích hoạt còn chưa duyệt. Không lấy bản local Node/RAM làm bản ứng dụng để upload.

## Đối chiếu 10 yêu cầu

| # | Yêu cầu | Bằng chứng implementation hiện có | Phần còn phải xác nhận |
|---|---|---|---|
|1|Đăng nhập, ba vai trò, phạm vi bộ phận, audit|ItaAuthService, ItaUserObjects/ObjectInfo, ItaPermissions; ItaAssetService/ItaAuditService; dispatcher ItaApplication; S26_AUTH_VERIFY, PHASE14_CAPABILITIES_VERIFY|S26 có 686 checks local theo báo cáo, không phải login HTTP/DB thật. Quyền theo chức năng Phase14 cần SQL007 và rollout riêng; chế độ ba vai trò vẫn là compatibility khi cờ tắt.|
|2|Loại, hồ sơ, trạng thái, tìm kiếm tài sản|ItaAssetObjects filteredScope/listing/detail; ItaAssetService; ItaCategoryService; trường serial/specification/OS và bộ lọc năm/bảo hành theo source|Đọc/ghi DB thật, mã trùng đồng thời, đủ bộ danh mục trên schema đích; EXPLAIN và thời gian truy vấn chưa xác nhận. Không tự thêm quy tắc cấm tên chỉ gồm số/ngày mua tương lai.|
|3|Phần mềm/giấy phép, key, hết hạn/vượt chỗ|ItaLicenseObjects/Service/Reveal; mã hóa, reauth, audit; read-model cảnh báo|Khóa test/production, FPM/OpenSSL, persistence và tranh chỗ cuối trên DB thật chưa nghiệm thu.|
|4|Ticket, kỹ thuật viên, tiến độ, thống kê năm|ItaTicketObjects/Service/Rules, MaintenanceSchedule; ticket-statistics, lịch sử sự kiện|CHECK thực thi, rollback audit và hai request đồng thời chưa có bằng chứng DB. Năm thống kê là năm mở phiếu, không mặc định năm hiện tại cho mọi lần xem.|
|5|Tuổi, tuổi trung bình, tỷ lệ lỗi, đề xuất thay thế|ItaLifecycleObjects và rules; chi tiết điểm, bảng nhóm và drilldown|Đối chiếu cohort/query thật, dữ liệu thiếu và EXPLAIN; phân trang nhóm FE không giảm aggregate SQL.|
|6|Giao/thu hồi, lịch sử, ký nhận/PDF, quá hạn bảo trì|ItaAssignmentObjects/Service, handover PDF, acknowledge, MaintenanceSchedule|Có xác nhận nhận và biên bản có vị trí ký; không phải chữ ký số/chữ ký vẽ điện tử. Persistence, cạnh tranh giao cùng thiết bị, PDF qua HTTP host chưa nghiệm thu đầy đủ.|
|7|Ngân sách bảo trì/thay thế/mở rộng|ItaForecastObjects/Service/Rules/Export; cấu hình, kế hoạch, snapshot và export|Công thức với dữ liệu thật, isolation của chính kết nối ứng dụng và concurrency snapshot chưa xác nhận. Fixture lịch sử không được tự ghi vào host.|
|8|Dashboard, biểu đồ, Excel/PDF bốn nhóm báo cáo|ItaDashboardObjects, ItaReportService, renderers; dashboard/charts/alerts/report-preview/export|Số liệu so query cùng scope/filter và file HTTP thật chưa đầy đủ. Có guard dataset; không bảo đảm dashboard toàn bộ dữ liệu lớn tùy ý.|
|9|Import Excel, preview, trùng mã, export|ItaImportController/Service, Excel reader/writer, template/lỗi/confirm; giao diện mới commit 02d3d5c|Có test XLSX/scripted/browser theo báo cáo; transaction cả lô và trùng mã trên DB thật còn phải kiểm. Import tối đa 5MiB/5.000 dòng.|
|10|Responsive, sidebar, phân trang/tối ưu MySQL|Smarty/Bootstrap5, semantic tokens; pagination SQL danh sách/lịch sử/stats/category; báo cáo responsive local|Không có kết quả EXPLAIN/đo tải DB thật để kết luận tối ưu toàn bộ. Lookup còn cap; dashboard/alerts có phân trang mảng sau dựng tập, không phải mọi nơi đều SQL pagination.|

Dispatcher source hiện có các route cho từng nhóm trên tại `classes/services/itaapplication.class.php`. Chỉ thấy route không chứng minh mọi nhánh đã chạy qua DB; các báo cáo test phải giữ đúng loại fake transport/API mock/HTTP fixture/file renderer.

## Theo từng phase

| Phase | Trạng thái source/hồ sơ | Trạng thái nghiệm thu còn thiếu |
|---|---|---|
|0|Có audit, inventory legacy, kế hoạch và baseline lịch sử|Không lặp lại cleanup/reset để làm working tree sạch; tree hiện có thay đổi riêng của user.|
|1|Có migration001–004, ERD, layout và guard legacy|Chưa có bằng chứng chạy xuôi/ngược toàn bộ migration trên DB cô lập; dữ liệu/schema host cần inventory thật.|
|2|Có auth, ba vai trò, scope, access và audit|Đăng nhập từng vai trò, native writes, session/limiter trên DB/FPM thật.|
|3|Có quản lý tài sản/danh mục/tìm kiếm/lịch sử|CRUD/persistence, dữ liệu500 và EXPLAIN theo tiêu chí plan.|
|4|Có import/preview/confirm/errors/export|Import500 dòng thật, rollback cả lô và trùng mã DB.|
|5|Có phân bổ/thu hồi/nhận/PDF|Ghi/đọc lại, xung đột phân bổ và file host.|
|6|Có giấy phép, mã hóa/reveal/allocations/cảnh báo|Khóa, grants, rollback và concurrency thật.|
|7|Có ticket/lịch/stats/events|Enforcement CHECK, chuyển trạng thái/lock/rollback thật.|
|8|Có lifecycle/điểm/nhóm/drilldown|Cohort, query/index và aggregate DB.|
|9|Có forecast/snapshot/config/export|Isolation và tính đúng với dữ liệu/schema thật.|
|10|Có KPI/charts/reports/alerts|Query đối chiếu, giới hạn/tải, export HTTP.|
|11|Có sửa export S11, HTTP errors, privacy headers, hướng dẫn và contract release|PHP8.3, security checklist host17–20, final headers/TLS/grants/backup; F11.1 EXPLAIN/tải chưa chạy. Auto_prepend/HSTS không được duyệt.|
|12|Có motion, reduced-motion, Command/Focus/login UI theo báo cáo local|User review bản local; smoke môi trường thật, không coi effect MOCK là backendpass.|
|13|Có API/2D/3D, xoay chuột/toàn màn hình/nhãn và hình phân loại; migration006 đã soạn/duyệt SQL|Chưa có xác nhận áp dụng006 đúng schema/source hiện chạy hoặc kiểm DB mạng đầy đủ. Mạng là liên kết khai báo, không quét/giám sát thực.|
|14|Có source quyền từng chức năng và các sửa UI gần đây|SQL007/mapping/cờ chưa duyệt/chạy; bản review dùng RAM mô phỏng quyền không chứng minh rollout thật.|

## Các điểm chặn kết luận “đã đủ”

1. **Phase14 chưa thể sử dụng đầy đủ bằng upload source đơn thuần.** `ItaPermissions::enabled()` chỉ bật khi constant bằng true; BE_GATE_14_SQL.md còn NHÁP. Cần duyệt riêng migration/mapping, kiểm schema/quyền và kế hoạch kích hoạt trước khi nhận là hoàn tất. Không tự tạo bảng/bật cờ từ yêu cầu kiểm tra này.
2. **Phiên bản PHP cần nói chính xác.** Source có `readonly` tại ItaHttpError/ItaUserObjectInfo nên không chạy trên PHP8.0. Mốc tối thiểu cú pháp này là8.1; chưa khẳng định toàn bộ dependency chạy từ8.1. Môi trường đích dự kiến8.3 cần test thật theo PHASE11_PHP83_RETEST_PLAN; đường user đã đưa là8.5.10, không phải8.3.
3. **Chưa nghiệm thu dữ liệu/hiệu năng.** Không có bằng chứng đầy đủ cho CHECK/rollback/locks/isolation/concurrency/EXPLAIN/tải trên DB thật. Có source pagination/index và test query contract không thay tiêu chí này. Một triệu dòng chưa được sinh hoặc benchmark; không tuyên bố hỗ trợ từ tổng UI giả.
4. **Baseline host/release chưa xác định đầy đủ.** Bốn file FTP mẫu chỉ nhận diện những file đó; cần xác nhận schema005/006 và backup source/config/DB/key trước upload. Inventory host và backup/restore dependency ngoài máy dev chưa có xác nhận đầy đủ.

Các giới hạn cố ý giữ: MD5 native tương thích; policy auth ở PmAuth/ItaAuthService phải rà đồng bộ; dummy không khớp PASSWORD_DEFAULT thì503; đếm verify không chứng minh hằng thời gian; CSP style unsafe-inline; HSTS/auto_prepend chưa bật. Đây không phải lỗi mới đã được tái hiện trong lượt rà này, cũng không tự coi chúng là đồng ý bỏ qua mọi kiểm host.

## Công nghệ và bàn giao

Kiến trúc vẫn PHP MVC tự xây Derasoft + **Smarty**, Bootstrap5; không thay bằng framework frontend khác. ITA dùng schema InnoDB/utf8mb4 và quan hệ theo migration/ERD; bảng native MyISAM là ranh giới tương thích, không chứng nhận toàn DB legacy đạt3NF. MariaDB10.6 là môi trường đang được nhắc tới; không suy test MySQL mọi phiên bản từ source.

Chưa tạo gói vì điều kiện user đặt là “nếu đã đủ”. Gói UI cũ không có toàn bộ sửa mới, không dùng thay bản tổng hợp hiện tại. Nếu user chủ động chọn gói **kiểm thử có giới hạn** trước nghiệm thu, phải ghi rõ trạng thái Phase14 chưa kích hoạt và kiểm F11.5 trên ZIP thật; đó là phạm vi khác với phát hành đã hoàn tất.

Bản để kiểm giao diện hiện tại: `start-local-review.cmd`, hướng dẫn LOCAL_REVIEW_GUIDE.md. Toàn bộ preview/mock/launcher/tests/tmp và private config phải loại khỏi runtime host. Sau khi đủ điều kiện: chốt commit, inventory/runtime/dependency, manifest/SHA256SUMS, đọc lại ZIP/CRC/hash/exclusions, checklist backup/rollback; user upload FileZilla. Rollback source giữ dữ liệu mới, không restore dump đè hoặc DROP bảng.
