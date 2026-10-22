# Dashboard, báo cáo và cảnh báo — kiểm chứng local 21/10, chốt hồ sơ 22/10/2026

User duyệt BE-GATE #10 và yêu cầu bắt đầu BUILD. Đã triển khai local; **chưa nghiệm thu DB/host**. Không DDL, không tạo/kết nối DB, không package mới, push/deploy hoặc merge/tag.

## Chức năng và commit

| Mã | Hành vi | Commit |
|---|---|---|
| F10.1 | KPI theo scope, một mốc giờ VN; Hỗ trợ chỉ bảng bảo trì | a017327 |
| F10.2 | Trạng thái theo loại/bộ phận/năm mua, biểu đồ xếp chồng và bảng | 86ba2bd |
| F10.3 | Tỷ lệ lỗi distinct asset theo repair opened_at, bảng tử số/mẫu số | 38adbbe |
| F10.4 | Đề xuất thay thế từ điểm DECIMAL gốc, hiển thị 3 lẻ, hỗ trợ >1 | 477d9f9 |
| F10.5 | Preview, Excel bốn báo cáo; snapshot ngân sách đọc nguyên dữ liệu đã lưu | a0b5b16 |
| F10.6 | PDF tiếng Việt, tối đa 500 dòng dữ liệu | d607518 |
| F10.7 | Cảnh báo có lọc/phân trang, chuông hiệu lực và nhiều lý do riêng | 0ca9a7e |
| Sửa điều hướng | Dựng lại chart khi quay về Tổng quan sau dispose | 0ce2166 |

## Hợp đồng thực hiện

- GET dashboard/dashboard-charts trả cùng loại response đầy đủ, ưu tiên một request cho KPI/biểu đồ/bảng và số chuông. GET alerts và report-preview độc lập khi user đổi trang. POST report-export đi qua cả outer allowlist và dispatcher, có CSRF.
- Một transaction đọc RR hoặc SERIALIZABLE cho mỗi response; isolation khác trả503, không tự đổi cấu hình server. Mốc as_of do server chọn một lần theo VN; hiển thị HH:mm/ngày. Không tuyên bố kiểm chứng tính nhất quán DB thật chỉ từ clock chung.
- Tập đọc giới hạn 5.000 tài sản/giấy phép; lấy tối đa5.001 để phát hiện vượt và từ chối, không cắt. Biểu đồ tối đa500 nhóm. Tính nhóm trong PHP từ tập đã lọc quyền; sửa chữa preaggregate trong truy vấn vòng đời sẵn có, không JOIN event để đếm tài sản.
- Hỗ trợ chỉ GET preview bảo trì trong scope; không dashboard/cảnh báo/ngân sách/vòng đời/export. Admin toàn store, Quản lý theo bộ phận. License của Quản lý chỉ đưa vào cảnh báo khi có allocation máy/bộ phận trong scope được xác minh; không suy membership từ user/store. Chỉ chọn metadata, không select ciphertext/key. Số chỗ dùng là tổng license như contract #06; không trả định danh allocation.
- Bảo trì gồm quá hạn và tối đa30 ngày, lịch effective của #07. Vòng đời ngắn tới hoặc còn tối đa12 tháng lịch tới tuổi thọ, clamp cuối tháng; thiếu ngày/tuổi thọ không tự đưa vào. Điểm/tier dùng #08; không suy từ điểm đã làm tròn.
- Excel tồn kho5.000, Excel khác1.000, PDF500 dòng dữ liệu; không gồm tiêu đề/tổng. Mọi ô viết STRING. PDF dùng renderer/font sẵn có, HTTP/local asset bị chặn. Metadata nguồn và mốc thời gian đi cùng bảng.
- HMAC preview gắn actor/revision/scope, bộ lọc, toàn bộ document và mốc as_of. Export tái đọc tại cùng ngày/mốc, so token; thay dữ liệu/bộ lọc hoặc quá15 phút trả409, yêu cầu preview lại. Đây là kiểm thay đổi có chữ ký, không lưu snapshot mới trong DB. Snapshot ngân sách đã lưu giữ timestamp/đầu vào/tiền; forecast hiện tại chuẩn hóa timestamp về mốc request trước ký.
- Sau render, kiểm native account active/store, khóa role/scope để kiểm revision và ghi audit; audit lỗi không trả file. Native có thể MyISAM, không tuyên bố locking/transaction xuyên engine. Không ghi dc_. Cache-Control no-store/private. FE kiểm MIME + chữ ký PDF/ZIP, không chấp nhận HTML200, không tự retry POST.
- Không polling, không tự refresh biểu đồ. Dispose và disconnect khi lọc/rời trang; abort và sequence guard ngăn response cũ ghi đè trang bị từ chối. Trang local đồng bộ với trang server đã clamp sau khi dữ liệu giảm.

## Lệnh và kết quả thật

| Lệnh | Loại và kết quả |
|---|---|
| php tests/ita_phase10_unit.php | 52 pure/scripted đạt; query binding/scope, ngày, đếm, overflow, token, audit failure và native revocation bằng double; không chứng minh InnoDB thật |
| php tests/ita_phase10_budget.php | 7 scripted đạt: current as_of ổn định, snapshot giữ timestamp/giá, đổi giá hiện tại làm đổi token, filter snapshot sai bị từ chối |
| playwright-cli -s=ita-phase06 run-code --filename=tests/ita_phase10_browser.js | 64 browser API mock đạt ngày22/10, layout live/controller thật; 3 vai trò, quay lại Tổng quan, chữ ký file/HTML200/audit lỗi, pagination clamp, delayed response, bounds1366/768/390, không runtime error |
| playwright-cli -s=ita-phase06 run-code --filename=tests/ita_phase10_dashboard_browser.js | 9 browser API mock đạt tại mỗi checkpoint dashboard; bản cuối3 biểu đồ, KPI rõ cấp bậc và bounds |
| playwright-cli -s=ita-phase06 run-code --filename=tests/ita_phase10_report_browser.js | 15 browser API mock đạt: preview, CSRF/token, HTML200, scope UI và bounds |
| php -d extension=gd tests/ita_phase10_exports.php | XLSX thật mở lại bằng reader, =HYPERLINK vẫn STRING; PDF thật tạo được; dữ liệu đổi bị409. GD bật per-process, không đổi php.ini |
| Poppler pdftoppm -scale-to 1600 -png -singlefile tmp/phase10-verify/inventory.pdf tmp/phase10-verify/inventory | Đã render/xem PDF tiếng Việt, bảng không chồng/cắt trong fixture một trang; không suy thành kiểm tải500 dòng |
| php tests/ita_phase07_unit.php; php tests/ita_phase08_unit.php; php tests/ita_phase09_unit.php | Hồi quy236 +72 +134 pure/scripted đạt; query plan report mới làm bộ #08 có72 check, số68 trong tài liệu cũ là lịch sử |
| php tests/ita_write_routes.php; php tests/ita_phase03_render.php | 13 route checks và Smarty thật/escaping đạt, không DB |
| Browser ita_phase08_browser.js; ita_phase09_browser.js | Hồi quy63 +42 browser API mock/static đạt |
| php -l các PHP mới/sửa; node --check dashboard/shell/test; git diff --check | Đạt |
| php tests/ita_phase10_explain.php | Sinh6 truy vấn đọc đúng source cho Admin/Quản lý; **không thực thi EXPLAIN** |

Đã xem ảnh dashboard/report desktop và mobile. Đã sửa KPI bị dính label/value, thêm legend trạng thái, giữ palette token hiện có. Rà độc lập chỉ đọc tìm ra timestamp ngân sách, native revocation, trang bị clamp, response muộn và cache view giữ trạng thái sau dispose; đã sửa và thêm test tương ứng. Không dùng nhận xét review thay DB test.

Lần chạy22/10 ban đầu không thực thi test vì browser/static server cũ đã đóng (ECONNREFUSED). Đã mở lại node assets/ita/preview/serve-preview.cjs trong workspace và chạy đủ64 check; không kết luận retry là nguyên nhân lỗi sản phẩm.

Vendor Excel/ZipStream có deprecation trên PHP local8.5 khi tạo/đọc XLSX. File bytes trong kiểm thử CLI vẫn mở được, nhưng chưa chứng minh response PHP-FPM host sạch. Không sửa vendor hoặc hạ error_reporting để giấu cảnh báo; checklist host yêu cầu display_errors Off và ghi lỗi vào log. Nâng dependency là phạm vi duyệt riêng.

## Chưa chạy

DB integration/UAT, EXPLAIN/index thật, rollback/audit lỗi trên InnoDB, đồng thời/revocation thật, native MyISAM race, PHP-FPM export, tải lớn và PDF500 dòng chưa chạy. Phase1–9 giữ **chưa nghiệm thu DB**; Phase10 cũng chưa nghiệm thu DB. Không merge/tag7–9 hoặc coi build local là đã deploy.

Các HTML dashboard.html/reports.html riêng vẫn là MOCK. Muốn kiểm chức năng đã nối dispatcher cần dùng layout live tại admin.php?op=ita sau khi user triển khai source; agent không tự kết nối host.
