# Kiểm bản review local tương tác — 28/10/2026

User yêu cầu bản local của toàn bộ lỗi trước upload. Đã thêm launcher start-local-review.cmd và server Node **127.0.0.1:18767**, cùng model/mock UI. Không thêm production endpoint/PHP/schema/package, không đọc config/credential host hoặc nối DB. GET/POST `/admin.php` trên cổng này do Node mô phỏng; không phải admin.php PHP. Header X-InfraCore-Review=LOCAL-MOCK-NO-DB và thanh LOCAL MOCK phân biệt rõ.

Khởi động chạy builder hiện có để sinh index.html và login-preview.html; builder chỉ đọc template trình bày. POST mô phỏng giữ trong RAM; reset/đóng process mất thay đổi. Source mới nằm trong preview/tests/launcher để loại khỏi deploy. Static server deny đường config/.git/docs/preview source, kiểm root/link và Host/Origin; CSP connect-src self. Không có công cụ gọi host/SQL, không credential thật. Chỉ đọc catalogue permission từ file class public để tránh catalogue demo cũ; không execute PHP.

| Lệnh/kiểm thật | Kết quả | Giới hạn |
|---|---|---|
|node assets/ita/preview/serve-review.cjs|Đã chạy18767 loopback|Không process PHP/DB; không daemon production|
|playwright-cli -s=ita-local-review run-code --filename=tests/ita_manual_review_browser.js|37 kiểm đạt|Mở12menu; asset detail/history, cost cancel/save22/readback,2011empty, date bound, C editor, bàn giao, reminder, importRAM2rows/lỗi, over200/recovery, virtual1m/reset, deny4URL,3D, bounds1366/768/390|
|node tests/ita_manual_review_model.cjs|21 đạt|Mock contract, history array/page, virtualtotal không allocate1m, import idempotentRAM, C đọc quyền vừa lưu, không echo key, ZIP/PDF cấu trúc/xref|
|Python zipfile/XML trên tmp/manual-review-sample.xlsx|ZIP CRC và5XMLpart hợp lệ|File mẫu độc lập, không phải PhpSpreadsheet export/import backend|
|PDF mẫu|Header/xref/offset/Page-count cấu trúc được kiểm bằng Node|Python pypdf hiện không có, chưa parse/render bằng thư viện đó; không cài thêm. Không thay kiểm PDF tiếng Việt/mPDF thật|
|node --check các script mới; git diff --check|Đạt|Không DB/host|
|Review agent độc lập|Sửa history object→array+history_pagination; không finding đường config/DB/host|Chỉ review source, không chứng nhận production security|

Lượt test gặp “execution context destroyed” vì đợi giá trị select đổi thay vì đợi reload; đã sửa test chờ load event rồi context/kịch bản mới. Không đổi sản phẩm hoặc hạ assertion. Server riêng do agent tạo được xác minh command line trước restart; Stop-Process gặp lỗi PowerShell, dùng Process.Kill đúng PID đã xác minh; không tắt process khác.

Đã xem ảnh manual-review390; thanh hướng dẫn thu gọn mặc định khi mở từ mobile, có thể mở/đóng. Dữ liệu test được reset về6tài sản ban đầu sau chạy, server để mở cho user. Hướng dẫn click cụ thể tại LOCAL_REVIEW_GUIDE.md. PHP/DB/host/one-million benchmark chưa kiểm; không upload/gói/push/merge/tag7–10. File bảo mật user-owned ngoài commit.
# Bổ sung28/10 — xác nhận đặt lại dữ liệu mô phỏng

Đã thêm xác nhận trước khi reset RAM; Hủy không gửi lệnh reset, đồng ý mới reset. Chỉ sửa review-client và test, không PHP/DB. Chạy lại `playwright-cli -s=ita-local-review run-code --filename=tests/ita_manual_review_browser.js`: **38checks đạt**, gồm12màn hình, cancel giữ8asset giả, accept về6, bounds1366/768/390 và Three.js. `node tests/ita_manual_review_model.cjs`: **21checks đạt**; node syntax và diff-check đạt. Đã xem ảnh390px. Browser session cũ đã đóng nên phải mở lại; lần chạy đầu chưa có session không phải lỗi sản phẩm. Không PHP8.3/DB/host hoặc ZIP release.
