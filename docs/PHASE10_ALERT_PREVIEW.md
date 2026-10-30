# Trung tâm cảnh báo — FE/MOCK, 21/10/2026

Mở http://127.0.0.1:18766/assets/ita/mock/dashboard.html#alerts trên preview server local. Trang độc lập, không nối dashboard live, PHP, endpoint hoặc DB. BE-GATE #10 chưa được duyệt.

- Lọc mức độ, lý do, mã/tên thiết bị; sắp xếp khẩn cấp trước; phân trang 2/5/10 dòng và xóa bộ lọc.
- Chuông đếm mọi cảnh báo đang hiệu lực trong phạm vi đã áp dụng, không đếm chưa đọc hoặc số dòng sau lọc. Một thiết bị có nhiều lý do vẫn giữ nhiều dòng.
- Hiển thị số kết quả khớp/tổng trong phạm vi, trạng thái rỗng và nút phân trang phù hợp. Bộ lọc danh sách dùng phạm vi đã áp dụng, không dùng lựa chọn bộ phận chưa bấm Áp dụng.
- Lọc và phân trang không tải lại fixture, đổi mốc số liệu hoặc dựng lại biểu đồ. Hỗ trợ chỉ thấy bảng bảo trì trong mô phỏng UI. Đây không phải xác thực/quyền server.

Kiểm chứng: `playwright-cli -s=ita-phase06 run-code --filename=tests/ita_phase10_alerts_browser.js` đạt **37 kiểm tra browser static/mock**. Hồi quy `ita_phase10_preview_browser.js` đạt **46**, `ita_phase10_reports_browser.js` đạt **50**. Kiểm cả scope UI, tổng chuông, lọc giao nhau, phân trang/rỗng, thời điểm chung, request GET local và bounds 1366/768/390. Đã xem ảnh desktop/mobile; không lỗi runtime. `node --check` controller/test và `git diff --check` đạt. Rà diff độc lập không phát hiện lỗi cần sửa; reviewer chỉ đọc source.

Không kiểm DB, quyền server, EXPLAIN hoặc UAT. Dữ liệu và mức độ là fixture giả; không lưu trạng thái đã đọc. DB Phase 1–9 **chưa nghiệm thu**; không merge/tag Phase 7–9, push hoặc deploy.
