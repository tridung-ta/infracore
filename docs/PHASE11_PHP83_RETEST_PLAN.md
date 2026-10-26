# S26-03 — Kế hoạch kiểm lại bằng PHP8.3, CHƯA CHẠY

Theo user26/10/2026: chỉ lập kế hoạch, **chưa sửa code/test/vendor, chưa chạy**. User sẽ cung cấp runtime PHP8.3 nếu có. Không tự tải/cài PHP, Composer/dependency hoặc đổi php.ini/host; không tạo/kết nối DB test. Kết quả8.5.10 trước đây không đổi thành PASS8.3.

## Điều kiện trước lượt chạy tương lai

1. User cung cấp đường dẫn php.exe8.3 và runtime/extensions tương ứng. Chốt source commit đủ40ký tự, working diff, phiên bản đầy đủ, ini đang dùng và danh sách extension qua runtime đó khi được giao chạy; không chỉ dựa tên folder. CLI8.3 vẫn không chứng minh PHP-FPM host8.3.33 có cùng cấu hình.
2. Kiểm mysqli/OpenSSL/mbstring/zip/fileinfo/DOM/zlib và GD theo chức năng; thiếu thì ghi chưa đủ điều kiện, không lấy DLL8.5 ghép vào8.3. GD chỉ bật per-process nếu runtime có đúng extension và chưa nạp; không sửa ini toàn cục hoặc nạp trùng.
3. Không dùng private DB config, session/login thật hoặc fixture reset DB. Chỉ bootstrap class + fake DB transport và dữ liệu XLSX/PDF giả. Giữ vendor/lock/font baseline, không nâng package để làm test đạt.

## Biến thể test đếm deprecation, mới là thiết kế

Chưa tạo các file biến thể. Sau khi được giao chạy cùng runtime user cung cấp, chuẩn bị biến thể **test-only** cho test có loại E_DEPRECATED (đặc biệt ita_phase04_unit.php); giữ nguyên fixtures/assertions và import giới hạn/scope/formula guard. Không include nguyên test còn gọi error_reporting(E_ALL & ~E_DEPRECATED) rồi giả định flag bên ngoài đã thắng.

- Dùng E_ALL trong biến thể, không loại E_DEPRECATED/E_USER_DEPRECATED khỏi mask. Không sửa PHP ứng dụng/vendor hoặc hạ error_reporting để giấu warning.
- Handler test-only tăng bộ đếm E_DEPRECATED/E_USER_DEPRECATED theo loại và vị trí, **trả false** để PHP vẫn phát diagnostic mặc định. Không nuốt warning/notice/fatal khác; giữ hành vi lỗi/assertion/exit code của test.
- Kết thúc mỗi test in bản tóm tắt `deprecations=<n>; user_deprecations=<n>` kèm exit code/assertion count. Lưu diagnostic synthetic local trong tmp riêng; không dump workbook/config/secret/stack dữ liệu thật. Khôi phục handler/mask sau lượt test nếu dùng chung process; ưu tiên mỗi test một process riêng.
- Với test export thật, giữ bytes XLSX/PDF thành file riêng từ renderer như hiện có; diagnostic CLI không được coi là bytes download HTTP. Không thêm output debug vào endpoint. Test cố ý phát một deprecation synthetic chỉ để kiểm counter phải tăng1 trước khi dùng biến thể, rồi tách nó khỏi số deprecation thư viện.
- Không đổi assertion bảo mật, số dòng, MIME/signature hoặc bỏ test lỗi chỉ để zero warning. “0” chỉ được ghi khi runtime đó chạy thật và handler/mask đã được xác nhận; không dự đoán PHP8.3 chắc chắn không có deprecation.

## Ma trận chạy dự kiến

Tên dưới đây là test hiện có để lập danh sách; **không phải lệnh đã chạy trên8.3**. Test nào cần biến thể phải chuẩn bị trước lượt chạy, ghi đúng tên/lệnh thực khi có.

| Nhóm | Test dự kiến | Kết quả cần thu | Trạng thái |
|---|---|---|---|
|Auth/session/limiter|ita_preupload_auth.php|Assertions, exit code, counters; không DB/native credential thật | CHƯA CHẠY |
|Unit tài sản/import/phân bổ/license/ticket/lifecycle|ita_phase03_unit.php đến ita_phase08_unit.php|Assertions như contract, counter của biến thể Phase4 và test có mask tương tự | CHƯA CHẠY |
|Ngân sách/dashboard/network|ita_phase09_unit.php, ita_phase10_unit.php, ita_phase13_unit.php|Số check/exit code/deprecation; fake transport | CHƯA CHẠY |
|Guard export|ita_phase11_forecast_export.php, ita_phase11_import_export.php, ita_write_routes.php|Actor/scope/revision/audit lỗi vẫn chặn theo contract | CHƯA CHẠY |
|File export thật|ita_phase09_exports.php, ita_phase10_exports.php|XLSX mở lại: formula-like text là STRING; PDF hợp lệ/tiếng Việt; counters đầy đủ, GD/runtime ghi rõ | CHƯA CHẠY |
|Syntax|PHP mới/sửa trong payload ứng viên qua php-lint8.3|Exit code và file lỗi; không chạy entrypoint DB | CHƯA CHẠY |

Sau export: xem lại PDF bằng renderer local nếu giữ artifact dữ liệu giả, kiểm tiếng Việt/cắt bảng; mở lại XLSX bằng thư viện và kiểm type ô/giới hạn. Host HTTP download/MIME/header/FPM display_errors là checklist host riêng, không tự đổi thành đạt từ test CLI.

Không chạy ita_phase02_checks/fixture/sql/browser cũ hoặc SQL/migration để kiểm runtime. Không tự khởi tạo DB hoặc deploy bản8.3 để có kết quả.

## Mẫu ghi kết quả sau này

| Source commit/runtime/ini/extensions | Lệnh/test/biến thể thật | Assertions/exit code | E_DEPRECATED | E_USER_DEPRECATED | XLSX/PDF | Mục chưa kiểm |
|---|---|---|---|---|---|---|
|Chưa cung cấp runtime | Chưa chạy | Chưa có | Chưa đếm trên8.3 | Chưa đếm trên8.3 | Chưa chạy8.3 | FPM/host/DB |

Đối chiếu số liệu8.3 với báo cáo8.5 bằng cùng nguồn/dependency và fixtures khi khả thi; ghi khác môi trường, không xóa kết quả cũ. Nếu assertion/file lỗi hoặc còn diagnostic: giữ bằng chứng và trình phương án riêng, không sửa vendor/che output. Không tuyên bố “hết deprecation” hoặc sẵn sàng deploy khi chưa có số thực. Chưa đóng gói/upload/push/merge/tag7–10.
