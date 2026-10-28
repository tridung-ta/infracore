# Chọn file và xem trước dễ dùng hơn — 28/10/2026

Chỉ FE, không đổi PHP/API/SQL hoặc tăng giới hạn. Ba tab đổi thành Hướng dẫn & file mẫu / Nhập tài sản / Xuất tài sản, tránh hiểu Xuất là bước xác nhận nhập. Trong tab nhập có ba phần rõ: chọn file, xem kết quả, xác nhận cả lô.

File card giữ input native có label, hiển thị tên/dung lượng, nút kiểm tra nổi bật và Bỏ file. Chọn/bỏ file không upload; bấm Kiểm tra mới upload và tạo lô tạm trên server. Chưa thêm tài sản ở bước xem trước. Đổi file hoặc bắt đầu lượt kiểm mới ẩn kết quả cũ; không để bảng cũ trông như kết quả của file mới.

Kết quả có tổng số dòng/hợp lệ/cần sửa của **toàn lô**, bảng phân trang vẫn theo server. Dòng lỗi và hướng dẫn sửa dùng textContent, không HTML. Nếu còn lỗi, nút nhập ghi rõ chưa thể nhập; có hướng dẫn tải Excel lỗi, sửa file rồi kiểm lại, và giải thích category_code/department_code phải là mã đang hoạt động, không phải ID/tên. Không đoán mã thật từ ảnh hoặc thêm endpoint tra cứu chưa duyệt.

Xác nhận có dialog ghi tổng số tài sản/lô, nhấn Hủy không POST. Payload vẫn chỉ batch_id/confirm_token, không lấy dòng browser làm dữ liệu tin cậy. Thành công đổi trạng thái file/lô để tránh thông tin “chưa nhập” cũ. Khi mất phản hồi confirm, vẫn khóa chọn/bỏ/upload/confirm và chỉ cho kiểm trạng thái; không mở đường retry mù. Scope/CSRF/token/audit/idempotency và guard server giữ nguyên. UI custom capability không bị role Support cũ che mất import đã cấp.

Giữ .xlsx tối đa5MiB/5.000dòng, không hỗ trợ import1triệu dòng một lượt. Không đọc nội dung workbook trong JS; static preview là MOCK độc lập với file chọn. File lỗi/XLSX thật cần PHP server hiện có kiểm lại.

| Kiểm local thật | Kết quả | Giới hạn |
|---|---|---|
|playwright-cli -s=ita-admin-refine run-code --filename=tests/ita_phase04_browser.js|38 kiểm đạt|File/tab/clear/không tự upload, tổng24/20dòng trang, lỗi, hủy confirm/noPOST, CSRF/token, lost response khóa, expire/export, custom capability, bounds1366/768/390; API giả|
|php tests/ita_phase04_unit.php|88 đạt|Có XLSX thật/dữ liệu giả, không DB; mask deprecation cũ không chứng minh PHP8.3|
|php tests/ita_phase03_render.php; node --check; git diff --check|Đạt|Smarty/syntax/whitespace local8.5.10|
|Impeccable detector và xem ảnh mobile/desktop|Đã bỏ accent border một phía theo1warning; bố cục đã xem|Không claim detector chạy lại0; không host/layout FPM thật|
|Review agent|Không finding chặn trong source đã đọc|Không DB/host hoặc benchmark|

Ảnh phase04-live-1366/390 là API mock. Server Node127.0.0.1:18766 không nối DB. Tại local static #imports, chọn tab Nhập tài sản rồi Xem kịch bản MOCK để thử bố cục; không đọc Excel thật/ghi dữ liệu. Không gói/upload/push/merge/tag7–10. Rollback source đồng bộ partial/CSS/JS/layout, giữ DB/staging/dữ liệu đã nhập thật, không reset lô để sửa giao diện.
