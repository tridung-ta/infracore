# Cải tiến giao diện và rà nhập liệu — 27/10/2026

Chỉ FE/template/CSS/JavaScript và test/tài liệu. Không sửa PHP, SQL, config, quyền, API hoặc công thức. Không kết nối DB/host, chưa đóng gói/upload. Giữ thay đổi có sẵn của user trong PHASE11_SECURITY_TEST_20.md.

## Xem trực tiếp local

Preview chỉ phục vụ static/MOCK tại **http://127.0.0.1:18766/**, không chạy PHP:

1. Mở **http://127.0.0.1:18766/?devices=1#network**, bấm **Xem 3D (MOCK)**. Có máy in, màn hình, máy bàn, laptop, máy chủ, switch. Thử kéo ngang/dọc 360°, chọn khối, chọn tên, nút xoay và toàn màn hình/Esc.
2. Bấm **Hiệu ứng: Bật/Tắt** bên trái: trạng thái màu/viền/nhãn thay đổi; bật có chuyển động mở nhẹ, tắt có phản hồi ngắn rồi dừng. Tắt hiệu ứng ngăn nghiêng 3D tự động theo hover; kéo xoay và nút vẫn dùng được. Reduced-motion của hệ điều hành có ưu tiên, không ép bật animation.
3. Mở **http://127.0.0.1:18766/#assignments**: xem lịch sử trình bày mới, chọn các thiết bị giả và tình huống lỗi/rỗng. Chế độ MOCK không ghi phân bổ/thu hồi. Luồng live đã kiểm bằng API giả riêng, không nối DB.
4. Mở **http://127.0.0.1:18766/assets/ita/mock/form-validation.html**: nhập tên tiếng Việt, mã TEST, giá âm/giá hợp lệ, bảo hành trước ngày mua; sửa riêng ngày mua rồi kiểm lại. Thử ngày giấy phép đảo ngược, số chỗ 1,5 và số nguyên. Nút chỉ kiểm FE, không tạo tài sản/giấy phép.

Khởi động lại khi cần: `node assets/ita/preview/build-preview.cjs`, sau đó `node assets/ita/preview/serve-preview.cjs`. Giữ terminal server mở; nếu cổng đã có preview đúng thì không khởi động bản thứ hai. Ctrl+0 để xem ở zoom 100%.

## Phạm vi đã thay đổi

- Lịch sử bàn giao thành từng dòng có số biên bản, ngày/người hoặc bộ phận nhận, trạng thái và hành động PDF. Bàn giao bộ phận không còn nhãn chờ xác nhận cá nhân. Nội dung vẫn dùng textContent; nút PDF giữ guard/busy/version/phân quyền cũ.
- Thông báo trạng thái/lỗi có khoảng cách, nền và độ tương phản rõ; lỗi nhận focus. Không tự retry POST.
- Biểu tượng ngân sách và báo cáo khác nhau: ví/ngân sách và tài liệu báo cáo, dùng SVG cùng hệ nét.
- 3D dùng khối ghép theo loại/tên: printer/monitor/desktop/laptop/server/storage/network; loại chưa nhận diện dùng khối trung tính. Màu phân biệt thiết bị; chấm cạnh tên biểu thị trạng thái dữ liệu, không phải giám sát kết nối. Đây là mô hình minh họa từ metadata, không nhận dạng phần cứng tự động. Các thiết bị cùng loại vẫn có thể cùng dáng; nhiều thiết bị có thể có màu gần nhau nên tên/danh sách luôn giữ.
- Sáu hình demo là fixture MOCK riêng, không tạo thiết bị trên host. Hình dựng bằng Three.js hiện có, không thêm ảnh/dependency hoặc tải tài nguyên ngoài.

## Đối chiếu ràng buộc hiện hành

| Nhóm | Contract source đã đọc | Cách thể hiện/giới hạn |
|---|---|---|
| Mã/tên tài sản | ItaAssetValidation: mã 64, tên 200 ký tự, trim và bắt buộc; không có regex chỉ-chữ-cái | Cho tiếng Việt/số/dấu câu. FE chặn nội dung bắt buộc chỉ khoảng trắng; mã trùng phải kiểm server/DB |
| Thông tin khác | brand100, model150, serial150, os120, vendor200, location255, specs10000 | Giữ maxlength hiện có, không cấm thêm ký tự ngoài contract |
| Ngày tài sản | ItaInput.date kiểm ngày thực Y-m-d, năm từ1000; bảo hành không trước ngày mua | Thêm hướng dẫn/min/max và kiểm liên trường; sửa một ngày xóa lỗi liên trường cũ. Ngày mua tương lai không tự bị cấm |
| Giá mua | Không âm, tối đa16 chữ số phần nguyên/2 thập phân; để trống là chưa biết | Hướng dẫn dùng dấu chấm, chặn âm/sai định dạng; không đổi chuỗi tiền thành Number gây mất chính xác |
| Danh mục/bộ phận | ID nguyên dương; danh mục active/tham chiếu cũ/scope do service kiểm | Không thay kiểm server bằng dropdown. Import phải dùng code tồn tại/active, lỗi cả lô vẫn khóa xác nhận |
| Phân bổ | Chỉ Admin/Quản lý giao; thiết bị in_use/chưa giao; hạn thu hồi không trước hôm nay VN; note tối đa4000; thu hồi cần lý do | FE hướng dẫn/min hạn thu hồi; giữ guard, version và quyền xác nhận chỉ người nhận |
| Giấy phép | Tên200, vendor200; số chỗ nguyên1–1000000; ngày hết hạn>=bắt đầu; tiền16+2; override lý do1000 | Hướng dẫn và kiểm hai ngày; giữ reauth, key tự ẩn, scope/audit/override server |
| Ticket | ID dương; note4000 bắt buộc; tiền không âm16+2; ngày xử lý từ ngày mở đến hôm nay VN; chuyển trạng thái theo rules | Thêm pattern tiền/max ngày hôm nay; giới hạn ngày mở và trạng thái vẫn kiểm server. Không hứa tạo lịch sử quá khứ qua UI |
| Ngân sách | ItaForecastRules: năm hiện tại–2200; tham số có giới hạn và độ chính xác theo từng trường | Không đổi công thức, snapshot/version hoặc các ngưỡng hiện có |
| Liên kết mạng | Hai ID khác nhau; ethernet/fiber/wireless; cổng/capacity64, note1000 | Giữ validation và quyền hai đầu ở server; không thêm quét mạng |
| Upload/login/quyền | Giữ guard XLSX/5MiB/5000 dòng, CSRF, limiter, reauth và session hiện có | Không thay PHP hoặc nới assertion bảo mật; không kiểm lại DB/persistence trong lần này |

Đây là rà các form/chức năng ITA đã nêu và kiểm local có giới hạn, không chứng nhận mọi input legacy/host. FE chỉ giúp nhập đúng; server vẫn có thẩm quyền quyết định.

## Kết quả và lưu ý test

Kết quả thật được chốt ở PROGRESS cùng lệnh. Test pointer đổi điểm lấy mesh từ khối đơn sang mesh con của nhóm thiết bị; giữ nguyên assertion click/touch/pen chọn đúng node. Test fullscreen chờ cả trạng thái thoát và focus sau sự kiện, giữ assertion trả focus; không sửa quyền/CSRF/version/reauth/audit assertion.

| Lệnh/test thật | Kết quả | Phạm vi |
|---|---|---|
| playwright-cli -s=ita-refine run-code --filename=tests/ita_ui_refinements_browser.js |20 checks đạt|WebGL sáu dáng/màu, motion, bounds và form giả|
| Cùng lệnh, ita_ui_manual_preview_browser.js |12 checks đạt|Trang demo người dùng, ngày/số chỗ/bounds input, không POST|
| Cùng lệnh, ita_phase13_pointer_browser.js / fullscreen_browser.js / orbit_browser.js |32 /23 /28 đạt|WebGL/Fullscreen/mouse/touch/pen thật, dữ liệu MOCK|
| Cùng lệnh, ita_phase03_browser.js / ita_phase05_browser.js |30 /29 đạt|API mock; quyền/CSRF/version, lỗi và ba vai trò theo fixture|
| php tests/ita_phase03_unit.php /05_unit.php /06_unit.php /07_unit.php /09_unit.php /13_unit.php |39 /203 /159 /237 /134 /179 đạt|Pure/fake transport; không DB|
| php tests/ita_phase03_render.php |Đạt|Smarty thật, escape, không DB|
| node --check JS mới/sửa; git diff --check |Đạt|Cú pháp/whitespace; không thay test cũ để bỏ assertion|

Review độc lập phát hiện lỗi customValidity liên trường còn giữ và nhãn chờ xác nhận bộ phận; đã sửa, có hồi quy. Bản đầu test reduced-motion đọc trước sự kiện media được xử lý; đổi sang đợi trạng thái disabled, không đổi hành vi sản phẩm.

Chưa PHP8.3/FPM/DB/layout PHP thật hoặc host; không nghiệm thu DB/merge/tag7–10/push/deploy. File MOCK, preview và ảnh không đưa vào gói deploy. Chỉ sau user xem local và yêu cầu riêng mới chuẩn bị gói; backup các file thay, rollback source giữ dữ liệu mới.
