# Năm thống kê, chi phí và ngày giấy phép — 28/10/2026

Đối chiếu ảnh1–3: ảnh1 có ô năm2011 nên toast dùng2011 theo bộ lọc, không phải sai năm hệ thống. Ảnh2 lưu22 nghĩa là tổng22VND, không phải22nghìn hoặc phần tăng thêm. User chọn **“Giữ quy tắc hiện tại, làm rõ và xác nhận trước lưu”**: không cấm tên phần mềm chỉ gồm số, không thêm mức tiền tối thiểu hoặc đổi công thức/permission.

## Sửa FE

- Nhãn “Năm mở phiếu (giờ Việt Nam)”, giải thích năm lọc và nút “Năm hiện tại”. Nút này chỉ sửa ô, không fetch. Mặc định lấy năm hiện tại VN; vẫn cho chọn2000–2199 theo contract server. Năm2011 hợp lệ; không có phiếu thì nói rõ “Không có phiếu mở trong năm2011” thay toast tải thành công chung chung. Response sai năm đã gửi bị báo lỗi, không render như dữ liệu khớp. Lịch sử dùng năm4chữ số tránh21/10/26 khó hiểu.
- Điều chỉnh chi phí có dialog hiển thị tiền cũ → tổng mới bằng VND và lý do. Hủy không POST; xác nhận mới gửi đúng payload/version/CSRF hiện có. Không dùng float để định dạng DECIMAL. Toast sau readback ghi đúng tổng tiền mới. Nếu đã ghi nhưng đọc lại lỗi, giữ cảnh báo chưa xác nhận, không ghi đè thành toast thành công.
- Hai ô ngày giấy phép có min1000-01-01/max9999-12-31 và hướng dẫn năm4chữ số, ngày hết hạn không trước bắt đầu; sửa một đầu xóa đúng lỗi liên trường để kiểm lại. Tên“1” vẫn được phép theo contract user giữ. Chặn FE không thay server validation hoặc chứng minh DB persistence.

Đã tái hiện thiếu bounds ngày trên helper JS baseline1652264 bằng browser override với API giả: assertion “Six-digit year blocked before any POST” đỏ. Không sửa source cũ hoặc kết nối DB. 2011 và22 là dữ liệu/bộ lọc trong ảnh, không kết luận chúng tự phát sinh do hệ thống. Không tự sửa số tiền, năm hoặc dữ liệu đã có trên host.

| Kiểm thật | Kết quả | Giới hạn |
|---|---|---|
|playwright-cli -s=ita-admin-refine run-code --filename=tests/ita_phase07_browser.js|70 kiểm đạt|Dialog tiền/lý do/cancel/noPOST, payload/readback và hồi quy3vai trò, bounds1366/768/390; API mock|
|Cùng CLI tests/ita_phase11_ticket_statistics_browser.js|49 đạt|Paging giữ năm đã áp dụng, chọn2011 empty, reset năm không fetch, lỗi giữ trang; API mock|
|Cùng CLI tests/ita_phase06_browser.js|32 đạt|Năm111111 không POST, ngày đảo, sửa một đầu, tên số hợp lệ, crypto/reveal UI contract hồi quy; API mock, không key thật|
|Cùng CLI tests/ita_ui_refinements_browser.js|20 đạt|Whitespace/ngày liên trường/giá âm và UI/WebGL mock hồi quy|
|node --check JS sửa; php tests/ita_phase03_render.php; git diff --check|Đạt|Syntax/Smarty/whitespace, PHP local8.5.10, không PHP8.3/DB|

Test chi phí trước đó đợi input==130.25 có thể qua trước POST vì ô đã được người dùng nhập. Đã đổi wait sang toast đọc lại thành công và hết busy trước kiểm payload; không nới assertion. Đã xem ảnh dialog390px và đo bounds ba kích thước. Không mới PHP/package/SQL/config; không gói/upload/push/merge/tag7–10. Input FE vẫn có thể bị bypass qua client riêng; chưa kiểm HTTP/DB thật/host hoặc mở gate đổi quy tắc server.
