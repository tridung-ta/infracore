# Bài học tái sử dụng — phải kiểm chứng tại dự án đích

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
