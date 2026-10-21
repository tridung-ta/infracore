# Bài học tái sử dụng — phải kiểm chứng tại dự án đích

## ITA-20261020-07 Ticket thiếu phản hồi nhìn thấy khi UAT host

User báo tạo phiếu/tải danh sách không có thông báo; chưa có response/log để xác định nguyên nhân ghi DB host. Đã tái hiện local: lỗi mạng đặt text nhưng thông báo có bbox top=-365px dù đang focus; scroll-behavior smooth chưa đưa nó vào viewport ở thời điểm phản hồi. Tải danh sách thành công cũng không có thông báo hoàn tất khi kết quả không đổi.

Sửa FE: focus/cuộn instant vào giữa, thông báo pending/completion, số phiếu sau tải, lỗi HTTP/JSON và invalid form. Review bổ sung completion mặc định cho detail/statistics/paging. Browser API mock kiểm viewport sau lỗi và HTTP503; không coi việc sửa phản hồi là đã sửa nguyên nhân DB host. Cần UAT lại và đọc thông báo thực tế, không chạy lại CREATE.

Các nguyên tắc dưới đây được rút ra từ tình huống đã gặp ở dự án trước. Chúng không chứng minh dự án mới có cùng lỗi hoặc mọi giải pháp cũ đều áp dụng được.

| Khi gặp | Cách tiếp cận nên dùng | Điều cần kiểm tra |
| --- | --- | --- |
| Bấm nút nhưng không có phản hồi | Kiểm native validity, handler, pending/busy, confirm và request/response | Không báo thành công trước backend; lỗi phải nhìn thấy và có cách sửa |
| Frontend và backend nhận dữ liệu khác nhau | So đơn vị đo, normalize, tên field, submitter, serialization và validation | Byte Unicode khác độ dài ký tự; không echo credential |
| UPDATE không tác động dòng nào | Phân biệt no-op với target không tồn tại/ngoài quyền theo API/DAO thực tế | Không coi affected_rows=0 tự động là thành công hoặc lỗi |
| Có nhiều định danh đăng nhập | Xem namespace và collation, unique constraints và concurrency | Va chạm chéo có thể không được phát hiện khi kiểm từng field riêng |
| HTTP trả 200 dù thao tác bị từ chối | Trace loại lỗi và contract của endpoint | Phân biệt validation, scope, auth; không đoán từ chuỗi thông báo |
| Tổng tiền/giờ bị nhân | Xác định grain trước khi JOIN/group; giữ kiểu decimal phù hợp | Tránh many-to-many nhân SUM; không cộng thành phần đã nằm trong tổng lần nữa |
| Giá trị lịch sử khác cấu hình mới | Kiểm ngày hiệu lực, snapshot và chính sách chỉnh sửa | Không tính lại lịch sử chỉ để làm số liệu khớp kỳ vọng mới |
| Đổi theme làm chữ biến mất | Đọc computed style, specificity và semantic tokens | Kiểm chữ/nền ở form, menu, dialog, hover/disabled/error; không chỉ body |
| Select mất mũi tên | Kiểm background-image và padding sau override | Dùng background-color khi chỉ đổi màu |
| Responsive không xếp lại | Đo bounding box và rule thắng ở các breakpoint | Kiểm menu trong viewport, DOM order và phóng chữ/zoom phù hợp |
| Animation cản thao tác | Giới hạn hiệu ứng theo tương tác; overlay không nhận pointer | Reduced motion, hủy nhanh và không lặp vô ích |
| Test fail sau thay UI | Tái tạo fixture từ code hiện tại, thao tác tab/details như người dùng | Giữ assertion quyền/CSRF/payload; đừng sửa ứng dụng để chiều test lỗi thời |
| Chart không còn tồn tại | Kiểm nhánh destroy/fallback và điều kiện dữ liệu | Không vẽ số liệu sai chỉ để test lấy được instance |
| Test pass nhưng ứng dụng lỗi bootstrap | Chạy tiến trình sạch qua entrypoint thật | Harness preload/mock có thể che dependency thiếu |
| Test gọi nhầm server | Kiểm port, process owner và định danh fixture | Từ chối port đã chiếm; chỉ dừng process do test tạo |
| Rollback có vẻ an toàn | Trace mọi writer, engine, external side effect và request đồng thời | Dùng isolation phù hợp; xác nhận môi trường/backup; không đổi engine làm lối tắt |
| File upload mất sau Preview | Xem reload trang và giới hạn file input của browser | Giữ DOM nếu phù hợp, revalidate ở server; không lưu file/secret tùy tiện |
| Download làm nút bị khóa | Tách tải file khỏi busy guard cho thao tác lưu | Giữ named submitter/action và token; thử tải nhiều lần |
| Unicode bị hỏng khi dùng shell | Đọc/ghi UTF-8 rõ ràng; xác minh replacement thực sự khớp | Console/pipe/BOM có thể khác nhau; không sửa global locale chỉ để chữa một lệnh |
| Xóa thư mục để dọn dự án | Kiểm path nằm trong workspace, tracked state, refs và junction/symlink | Link có thể trỏ vào dependency chung; không recurse theo link ra ngoài |
| Đóng gói bàn giao | Dùng source cố định, gom dependency, kiểm inventory/CRC/hash và rollback | Không mang config, dump, session, log hoặc test data vào runtime |
| Nhiều test PASS | Ghi chính xác loại test, version, môi trường và giới hạn | Mock không thay DB/persistence E2E; UAT không tự suy từ số suite |
| Lỗi gián đoạn chưa rõ nguồn | Giữ output/timing và điều tra có giới hạn | Không gọi retry PASS là root cause; không che FAIL bằng vòng lặp |

## Điểm cần canh ở dự án 5 (chưa phải bài học đã xảy ra)

Đây là các chỗ dễ vấp, suy ra từ bảng trên và phạm vi dự án 5. Đây chỉ là danh sách cần kiểm tra, **không phải chẩn đoán**; chỉ chuyển thành bài học khi đã tái hiện và có bằng chứng.

| Chỗ dễ vấp | Phase | Cần kiểm tra |
| --- | --- | --- |
| Bảng cũ của nền Derasoft có thể dùng engine không hỗ trợ transaction/FK | 0, 1, 4 | Đọc engine thật; bảng `ita_` dùng InnoDB; không đổi engine bảng cũ; import «rollback cả lô» chỉ đúng khi mọi bảng ghi đều hỗ trợ transaction |
| Middleware phạm vi bộ phận làm UPDATE/DELETE trả 0 dòng | 2, 3, 7 | Phân biệt no-op, không tồn tại, ngoài phạm vi (403 hay 404 theo contract); test truy cập chéo bộ phận bằng URL trực tiếp |
| Endpoint JSON trả 200 dù bị từ chối | 2–10 | Contract `{success, data, message}` và mã trạng thái nhất quán; phân biệt validation, quyền, xác thực |
| JOIN làm nhân tổng | 7–10 | Số ticket, chi phí bảo trì, số chỗ giấy phép, ngân sách: xác định grain trước khi JOIN/GROUP; dùng decimal cho tiền |
| Đổi tham số làm số liệu cũ đổi theo | 8, 9 | Đổi tuổi thọ chuẩn, định mức, giá thay thế không được tự tính lại ngân sách hoặc báo cáo đã chốt: dùng snapshot theo năm hoặc ngày hiệu lực |
| Mã tài sản và định danh đăng nhập | 2, 3, 4 | Collation và unique của `asset_code`, username, email; va chạm giữa trong file import và trong DB; hoa/thường, dấu cách đầu cuối |
| Mất file upload sau bước xem trước import | 4 | Lưu tạm phía server theo batch, kiểm tra lại khi xác nhận; không giả định file input còn giữ file |
| Tải file làm khóa nút lưu | 4, 10 | Tách export khỏi busy guard của thao tác lưu; thử tải nhiều lần |
| Tiếng Việt: độ dài byte và ký tự, PDF | 3, 5, 10 | utf8mb4, validate theo ký tự; font PDF hiển thị đủ dấu; đọc/ghi file UTF-8 |
| Theme tối làm mất chữ hoặc mũi tên select | 1, 12 | Computed style ở form, menu, dialog, hover/disabled/error; dùng semantic tokens |
| Responsive không xếp lại | 1, 11 | Đo bounding box ở 1366/768/390 px; sidebar và bảng chuyển đúng |
| Animation cản thao tác | 12, 13 | Overlay không nhận pointer; reduced-motion; canvas dừng khi tab ẩn; hủy nhanh |
| Biểu đồ ECharts bị hủy hoặc không còn | 8, 9, 10, 12 | Nhánh dispose và fallback khi đổi bộ lọc; không vẽ số sai để test lấy được instance |
| Test chạy nhầm server hoặc harness che lỗi bootstrap | Mọi phase | Kiểm port, process owner; chạy qua entrypoint thật; Playwright dùng đúng server |
| Dọn thư mục đi theo symlink/junction | 0 | Kiểm đường dẫn tuyệt đối trong workspace; chỉ xóa theo danh sách trắng; `git clean -n` trước |
| Gói deploy mang nhầm config, dump, log, seed | 11 | Manifest có hash; loại trừ config, dump, session, log, dữ liệu test và seed; có rollback |

## Mẫu ghi bài học mới

- ID/ngày:
- Triệu chứng và cách tái hiện:
- Phân loại: sản phẩm / harness / đúng nghiệp vụ / yêu cầu thiết kế / đang điều tra.
- Nguyên nhân đã xác minh, hoặc giả thuyết còn mở:
- Cách xử lý và phạm vi áp dụng:
- Test/lệnh đã chạy và kết quả thật:
- Bằng chứng an toàn để lưu:
- Giới hạn hoặc thông tin có thể khiến bài học không còn đúng:

Không nhập transcript/raw log, mật khẩu, token, cookie hoặc dữ liệu người thật vào tài liệu này.

## ITA-20261020-06 Lịch ban đầu và mốc trạng thái ticket

- Review Phase7 phát hiện source bỏ qua tính lại lịch khi đã có due, dù chưa bảo trì và ngày mua được sửa. Đã phân biệt last_maintenance_at với lịch ban đầu; scripted test ngày mua 31/01 → 28/02 xác minh due đổi tương ứng, còn lịch sau bảo trì được giữ. Chưa chạy DB.
- Review cũng thấy dùng updated_at chọn phiếu cuối sẽ để sửa cost phiếu cũ tác động cờ chờ Admin. Đây là rủi ro suy ra từ writer và query, chưa tái hiện trên DB thật. Đã dùng mốc/id event lifecycle và loại trừ pending ở cả helper và query cảnh báo; test contract không còn updated_at, UAT phải thử old failed/new resolved rồi sửa cost cũ.
- Browser form ticket cho phép gõ khi save còn refresh; dữ liệu gõ có thể bị render đọc lại ghi đè. Đã khóa input/select/textarea trong busy state cùng nút, test chờ đúng read-back trước bước tiếp theo. Browser API mock đạt, không suy thành persistence E2E.

## ITA-20261016-01 PHP lint thư viện mail cũ

- Triệu chứng: php -l trên 1.456 file thất bại ở 5 file liệt kê trong BASELINE.md.
- Phân loại: tương thích runtime legacy; chưa xác định ảnh hưởng luồng sản phẩm.
- Bằng chứng: PHP 8.5.10 báo GetMXRR trùng hàm, cú pháp ngoặc nhọn cũ, __autoload không hỗ trợ và lỗi cú pháp trong test script.
- Xử lý: giữ nguyên source, ghi baseline FAIL; trace include thực tế trước khi đề xuất sửa BE.
- Giới hạn: lint không chứng minh runtime, DB hoặc gửi mail chạy được.

## ITA-20261016-02 Lưu quyền từ form chưa được kiểm E2E

- Triệu chứng: user không thấy kết quả khi lưu phân quyền trên host.
- Phân loại: lỗi JavaScript selector và phản hồi UI; chưa có bằng chứng ghi DB từ thao tác lỗi.
- Tái hiện local: helper `$` dùng getElementById nhưng nhận selector `scope-options input:checked`; spread null gây `$ is not a function or its return value is not iterable` trước POST. Thông báo nằm tại y=-247px trên mobile. Ở tablet 768px, rule bảng tài sản còn ẩn cột thao tác tài khoản thứ 5.
- Sửa: querySelectorAll đúng; lỗi cạnh form, success được focus/scroll vào vùng nhìn; tách kết quả lưu và lỗi tải lại danh sách, timeout mạng có thông báo chưa xác nhận; giữ cột thao tác admin. Cache version cho JS/CSS.
- Verify: tests/ita_access_feedback.js đạt 13 checks qua PHP/MySQL fixture: submit UI thật và read-back DB ở 390/768/1366, validation, lỗi mạng, lỗi refresh, không JS runtime error.
- Bài học: 47 checks trước đã thử ghi bằng API trực tiếp và mở editor, chưa bấm Lưu từ UI; không xem coverage API là coverage submit form. Host chờ upload bản sửa và UAT lại.

## ITA-20261019-03 Giữ danh mục inactive ở hồ sơ cũ

- Triệu chứng: review Phase 3 phát hiện edit tài sản đang dùng loại inactive mất option và service buộc chọn loại mới dù chỉ sửa vị trí.
- Nguyên nhân: dùng cùng điều kiện active cho create và update, không phân biệt giữ nguyên tham chiếu cũ.
- Sửa: edit giữ option inactive hiện tại; server cho giữ nguyên category_id inactive, tạo/chuyển sang inactive vẫn trả 422.
- Kiểm tra: pure/scripted checks unchanged/create/change và browser API mock edit/create đạt. Không chạy SQL/DB integration theo yêu cầu user.
- Giới hạn: đây là evidence test mô phỏng và review source, chưa phải UAT host.

## ITA-20261019-04 Giới hạn staging phải khớp từ lúc ghi

- Triệu chứng/phân loại: Review Agent thấy XLSX hợp lệ có thể tạo batch rồi preview báo không khả dụng; lỗi thiết kế giới hạn file, chưa tái hiện trên host.
- Bằng chứng: validate trả cả values và fields, code staging serialize cả hai; put không giới hạn nhưng get từ chối trên 32 MiB. Trường dài bị lưu trùng, và JSON có thể phình hơn XLSX/XML.
- Sửa: bỏ fields khỏi staging, confirm dựng lại từ nguồn; kiểm JSON tối đa 32 MiB trước khi tạo file/batch. Không đổi dữ liệu/schema cũ.
- Kiểm tra: `php tests/ita_phase04_unit.php` có case staging vượt giới hạn trả 422, roundtrip XLSX và revalidation dùng adapter; không DB.
- Phạm vi: giới hạn payload phải được áp dụng trước persistence; không suy từ dung lượng nén ra dung lượng JSON/bộ nhớ. DB/concurrency thật vẫn cần UAT.

## ITA-20261020-05 CSS inline của template PDF phải tách khỏi cú pháp Smarty

- Triệu chứng/phân loại: lần render local đầu của F5.4 báo Smarty Compiler Unexpected ':' tại dòng CSS, chưa sinh PDF; đây là lỗi template, không phải GD/font hoặc DB.
- Bằng chứng: stylesheet inline chứa dấu ngoặc nhọn không có khoảng trắng, Smarty diễn giải thành tag. Lệnh `php -d extension=gd tests/ita_phase05_pdf.php` tái hiện được.
- Sửa: chỉ bọc stylesheet tĩnh bằng literal, giữ toàn bộ dữ liệu biên bản dùng escape html và nl2br sau escape. Không bọc literal quanh dữ liệu để bỏ escape.
- Kiểm tra: render PDF thật 1/2 trang, kiểm injection template, pypdf/pdfplumber và nhìn ảnh Poppler đạt. Múi giờ VN, seri 0 đầu, tiếng Việt và chữ ký đọc được; không DB.


### 21/10/2026 — POST allowlist thiếu endpoint mới
- Triệu chứng: tạo phiếu nhận HTTP403 không phải JSON dù trang đăng nhập và giao diện mở được.
- Phân loại: routing trước application.
- Nguyên nhân có bằng chứng: itaRequestDecision từ chối ticket-save; dispatcher có endpoint nhưng allowlist chưa cập nhật. Tương tự license-save/license-key.
- Sửa: chỉ thêm ba endpoint đã duyệt, giữ deny cho action lạ và kiểm quyền/CSRF ở application.
- Kiểm tra: tests/ita_write_routes.php 12 checks đạt; ticket pure/scripted 236 đạt, không DB.
- Phạm vi: kiểm routing gate ngoài cùng khi thêm endpoint; mock fetch không chứng minh request qua gate. GET danh sách host vẫn cần response để chẩn đoán riêng.


### 21/10/2026 — Hiển thị DECIMAL khi Việt hóa chi phí
- Triệu chứng: giá trị lớn có nguy cơ bị làm tròn khi định dạng giao diện.
- Phân loại: FE numeric precision.
- Bằng chứng: review chỉ ra Number('9999999999999999.99') làm mất phần lẻ và đổi giá trị nguyên.
- Sửa: nhóm chữ số và dấu thập phân trực tiếp trên chuỗi, không Number/float; đầu ra dùng textContent.
- Kiểm tra: browser mock thống kê xác nhận 9.999.999.999.999.999,99; bộ43checks đạt, không DB.
- Phạm vi: formatter hiển thị tiền ticket/list/detail/history/stats; không sửa payload hoặc logic tính chi phí backend.


### 21/10/2026 — Nhãn ẩn trong bảng cuộn vẫn có thể làm tràn trang

- Triệu chứng: vòng đời ở 768px có document scrollWidth 816px; ở 390px là 730px.
- Phân loại: FE responsive, không phải dữ liệu DB.
- Bằng chứng: nhãn visually-hidden nằm trong ô tiêu đề của bảng min-width 790px; bounding box vượt viewport, vùng bảng chưa có overflow riêng.
- Sửa: overflow-x:auto cho vùng bảng, position:relative cho ô tiêu đề để chứa nhãn ẩn; bảng thiết bị trên mobile dùng khối thông tin, bảng nhóm giữ scroll cục bộ.
- Kiểm tra: browser mock đo document width và form bounds ở 1366/768/390 đạt; điểm mobile nằm trong viewport. Bộ 37 checks đạt; đã xem ảnh desktop/mobile.
- Phạm vi: bảng có nội dung ẩn truy cập được và độ rộng tối thiểu lớn; không chữa bằng cách ẩn tràn toàn body.
