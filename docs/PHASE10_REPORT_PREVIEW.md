# Xem trước báo cáo — FE/MOCK, 21/10/2026

**BE10 chưa duyệt/chưa build. DB Phase1–9 chưa nghiệm thu.** Không merge/tag7/8/9, không push/deploy. Đây là bước tiếp FE/mock độc lập đã được user cho phép, không sửa exporter hoặc layout live.

Mở http://127.0.0.1:18766/assets/ita/mock/reports.html khi static server `node assets/ita/preview/serve-preview.cjs` đang chạy đúng workspace. Không khởi động đè tiến trình khác ở cổng18766. Trang đọc duy nhất reports.json, không PHP/API/DB/ghi/polling. Vai trò là minh họa UI, không xác thực hoặc kiểm quyền server.

## Phần đã làm

- Bốn bảng xem trước: tồn kho, sắp bảo trì, vòng đời ngắn, ngân sách. Phân trang5 dòng, lọc bộ phận; Quản lý CNTT có nhãn scope khớp số liệu. Hỗ trợ chỉ bảng bảo trì CNTT, không chọn báo cáo khác, không bộ kiểm giới hạn hoặc ngân sách.
- Fixture có9 thiết bị; tồn kho9 dòng, bảo trì4, vòng đời ngắn5. Bảo trì30 ngày là ngưỡng đề xuất chưa duyệt, UI ghi rõ. Mốc minh họa cố định10:00 ngày21/10/2026 giờ VN từ tệp JSON; không giả đó là thời điểm truy vấn host hiện tại.
- Vòng đời ngắn: đã tới hoặc còn tối đa12 tháng lịch đến tuổi thọ, không dùng tier replace thay điều kiện tuổi. Có điểm3 số lẻ và tier theo giá trị gốc:0.7996 hiển thị0.800 nhưng vẫn watch. Ngày cuối tháng chặn hợp lệ; tương lai/thiếu ngày mua hoặc tuổi thọ báo chưa đủ dữ liệu, không tự điền0. Không tính retired.
- Ngân sách là **mẫu kế hoạch riêng** để cộng tay, không phải tính từ bảng9 thiết bị hoặc service backend. Giá20 triệu, thay thế1, bảo trì3 triệu, nhân sự tăng3 × định mức1,5 làm tròn lên5 chỗ mua thêm: tổng135,3 triệu gồm dự phòng10%. Đổi giá giả30 triệu cho dự báo hiện tại tạo tổng201,3 triệu; snapshot giả vẫn135,3 triệu. Giá0 đặt rõ tạo3,3 triệu gồm bảo trì và dự phòng; giá rỗng không biến0. Bộ phận không có kế hoạch không hiện tổng ngân sách0 đầy đủ.
- Snapshot chỉ là JSON fixture bất biến. Giá giả nằm trong biến JS, không lưu cấu hình/plan/localStorage. Nguồn và đơn giá hiển thị đúng bản đang xem; quay lại dự báo giữ giá trong bộ nhớ trang. Không chứng minh lưu/freeze snapshot trên DB thật.
- Bộ kiểm giới hạn **tách khỏi số dòng bảng**: user nhập số dòng giả để thử. Excel tồn kho5.000, Excel khác1.000, mọi PDF500; vượt từ chối, không cắt ngầm. Số0 là không có gì để tạo tệp. Trong giới hạn chỉ báo quy tắc MOCK, không có Blob, download hoặc báo “đã xuất”. Chưa có kiểm giới hạn export server hay file thật.

## Kiểm chứng thật

`playwright-cli -s=ita-phase06 run-code --filename=tests/ita_phase10_reports_browser.js`: **50 kiểm tra browser static/mock đạt**. Bao gồm lỗi tải/retry, phân trang9→5+4, ba vai trò UI, biên500/501,1.000/1.001,5.000/5.001,0 dòng, mốc12 tháng đúng ngày/qua1 ngày, năm nhuận/cuối tháng, ngày mua tương lai, raw tier, thiếu dữ liệu, tính tay ngân sách/current/snapshot/0/rỗng, bàn phím skip-link, escaping và bounds1366/768/390. Request chỉ GET local, không PHP/API hoặc JS runtime error.

`node --check` controller/test đạt. Đã xem ảnh desktop/mobile; bảng cuộn cục bộ có hướng dẫn, giữ mã một dòng. Ảnh .playwright-cli/phase10-reports-{1366,768,390}.png. Rà độc lập không phát hiện lỗi cần sửa; nhắc giữ rõ ngân sách là mẫu riêng, đã ghi cả UI và tài liệu.

Chưa tạo/mở lại Excel/PDF, chưa nghiệm thu F10.5/F10.6 export, chưa DB/EXPLAIN/host UAT. Quyền và giới hạn server phải được build/test riêng sau duyệt gate. Không dùng50 checks này thay bằng chứng DB hoặc an toàn file xuất.
