# Kiểm thử giao diện mẫu dashboard và báo cáo

Ngày chuẩn bị: 21/10/2026. Chỉ kiểm FE/MOCK độc lập, không cần DB. BE-GATE #10 chưa duyệt; DB Phase 1–9 chưa nghiệm thu. Theo chỉ đạo mới của user, agent tự kiểm local; user kiểm thủ công sau khi tự upload host. Các ô dưới đây là kịch bản tham chiếu, không phải yêu cầu user phải kiểm local hoặc bằng chứng host đã đạt.

## Lần chạy local ngày 21/10/2026

Agent đã chạy lại trên source sau commit 0c64089, preview localhost:18766, tiến trình node.exe chạy assets/ita/preview/serve-preview.cjs. Ba lệnh thực tế:

```powershell
playwright-cli -s=ita-phase06 run-code --filename=tests/ita_phase10_preview_browser.js
playwright-cli -s=ita-phase06 run-code --filename=tests/ita_phase10_reports_browser.js
playwright-cli -s=ita-phase06 run-code --filename=tests/ita_phase10_alerts_browser.js
```

Kết quả: **46 + 50 + 37 = 133 kiểm tra browser static/mock đạt**, không lỗi JavaScript runtime. Có kiểm luồng lọc, phân trang, ba vai trò mô phỏng, trạng thái lỗi/rỗng, ngày biên, ngân sách giả/snapshot giả, giới hạn dòng giả và bounds 1366/768/390. Node syntax của hai controller đạt. Không phát hiện lỗi mới trong các kịch bản này, không sửa mã sản phẩm trong lượt kiểm này.

Chưa chạy DB integration, quyền server, xuất Excel/PDF thật của #10 hoặc host UAT. Mô phỏng vai trò không chứng minh bảo mật server; giới hạn dòng giả không chứng minh exporter thật. Không dùng kết quả local để cập nhật nghiệm thu DB.

Nếu chưa có preview server, chạy tại thư mục dự án:

```powershell
node assets/ita/preview/serve-preview.cjs
```

Nếu cổng 18766 đang dùng, kiểm đúng server của workspace trước khi mở; không dừng tiến trình khác để chiếm cổng.

## Dashboard

Mở http://127.0.0.1:18766/assets/ita/mock/dashboard.html

- [ ] Nhãn MOCK rõ; Admin toàn phạm vi có 9 tài sản, 8 đang quản lý, tỷ lệ lỗi 50%, 3 đề xuất thay thế.
- [ ] Mỗi biểu đồ có bảng đối chiếu; đổi nhóm loại/bộ phận/năm mua cho kết quả cùng định nghĩa.
- [ ] Điểm 1,250 hiển thị được; điểm gốc 0,7996 không vào đề xuất thay thế dù làm tròn thành 0,800.
- [ ] Quản lý CNTT có 5 tài sản, 4 đang quản lý, tỷ lệ lỗi 75%, 2 đề xuất; bộ chọn bộ phận hiển thị CNTT.
- [ ] Hỗ trợ chỉ xem bảng bảo trì; không thấy dashboard, chuông hoặc nút xuất.
- [ ] Mốc số liệu chung không đổi khi lọc; chỉ Làm mới dữ liệu giả mới tải lại. Không tự làm mới.

## Cảnh báo

Mở http://127.0.0.1:18766/assets/ita/mock/dashboard.html#alerts

- [ ] Admin toàn phạm vi thấy chuông 4; chọn 2 dòng/trang có 2 trang, khẩn cấp trước.
- [ ] Lọc Khẩn cấp còn 2/4 dòng, chuông vẫn 4; tìm mã không tồn tại có thông báo rỗng và nút phân trang bị vô hiệu hóa.
- [ ] Xóa bộ lọc trả lại danh sách; hai lý do trên MOCK-001 giữ hai dòng.
- [ ] Áp dụng bộ phận Vận hành: chuông 1; Quản lý CNTT: chuông 3. Đây là mô phỏng UI, không chứng minh quyền server.

## Báo cáo

Mở http://127.0.0.1:18766/assets/ita/mock/reports.html

- [ ] Tồn kho 9 dòng, phân trang 5 dòng; vòng đời ngắn 5 dòng, có điểm và tier, không đồng nghĩa danh sách thay thế.
- [ ] Bảo trì phân biệt sắp đến hạn/quá hạn; cửa sổ 30 ngày vẫn ghi là đề xuất chưa duyệt BE.
- [ ] Ngân sách mẫu riêng có tổng 135.300.000 VND; đổi giá giả thành 30.000.000 cho tổng hiện tại 201.300.000, snapshot giả vẫn 135.300.000.
- [ ] Xóa giá không biến thành 0; nhập rõ 0 là giá hợp lệ. Bộ phận chưa có kế hoạch hiện thiếu dữ liệu.
- [ ] Bộ thử giới hạn chấp nhận Excel tồn kho 5.000, từ chối 5.001; Excel khác 1.000/1.001; PDF 500/501. Không sinh hoặc tải file thật.

## Khả năng sử dụng

- [ ] Kiểm ở 1366, 768, 390 px: không tràn toàn trang; bảng rộng cuộn trong vùng bảng.
- [ ] Dùng Tab/Enter tới bộ lọc, nút và phân trang; biết vị trí đang focus, đọc được trạng thái kết quả.
- [ ] Khi tải fixture lỗi, có thông báo và thử lại thủ công; không giả báo lưu/xuất thành công.

## Bằng chứng local đã có

Dashboard 46, báo cáo 50, cảnh báo 37 kiểm tra browser static/mock đạt ở commit 11f6adc và các commit tiền nhiệm. Chi tiết lệnh/giới hạn tại PHASE10_FE_PREVIEW.md, PHASE10_REPORT_PREVIEW.md, PHASE10_ALERT_PREVIEW.md. Không dùng các con số này thay kết quả DB, UAT hoặc kiểm quyền server.

Backend, xuất file thật, SQL aggregate/EXPLAIN và dữ liệu host còn chờ gate #10 được duyệt riêng. Không merge/tag Phase 7–9, push hoặc deploy trong bước chuẩn bị này.
