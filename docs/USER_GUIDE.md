# Hướng dẫn sử dụng InfraCore

Cập nhật22/10/2026 theo source đã build local. **DB/host chưa nghiệm thu**; màn hình trên host phụ thuộc bộ source user triển khai. Trang có nhãn MOCK chỉ dùng minh họa, không lưu dữ liệu thật. Hướng dẫn này không xác nhận đã upload hoặc kiểm thử host.

## Đăng nhập và phạm vi

Mở địa chỉ HTTPS do quản trị viên cung cấp, nhập tên tài khoản hoặc email và mật khẩu hiện có. Nếu chưa được cấp quyền IT, liên hệ Admin IT. Không gửi mật khẩu/key qua ảnh hoặc báo cáo lỗi. Đăng xuất bằng nút trên thanh tài khoản.

| Vai trò | Công việc chính |
|---|---|
| Admin IT | Quản trị tài khoản/phạm vi, quản lý toàn hệ thống, key giấy phép, xác nhận vượt chỗ và chi phí phiếu đã đóng |
| Quản lý hệ thống | Tra cứu và thực hiện công việc được phép trong bộ phận phụ trách; xem vòng đời/ngân sách/báo cáo trong phạm vi |
| Hỗ trợ kỹ thuật | Công việc tài sản/phiếu được giao theo phạm vi; phần dashboard chỉ có bảng bảo trì, không ngân sách/vòng đời/xuất báo cáo |

Bộ phận phụ trách là phạm vi quản lý đã được cấp, không tự suy từ tài khoản cùng store. Đường dẫn trực tiếp vẫn phải qua kiểm quyền server; thấy một nút không đồng nghĩa thao tác sẽ được cho phép.

## Tổng quan và cảnh báo

- Kiểm “Số liệu tại HH:mm” theo giờ Việt Nam. Bấm **Làm mới** khi muốn lấy số mới; biểu đồ không tự làm mới liên tục.
- “Sổ tài sản” gồm cả tài sản thanh lý chưa xóa; “Đang quản lý” loại thanh lý. Tỷ lệ lỗi dùng phần cứng đang phân tích và số thiết bị có ít nhất một phiếu sửa chữa trong12 tháng, không phải số phiếu/tổng tài sản.
- Chọn bộ phận và nhóm loại/bộ phận/năm mua, bấm **Áp dụng**. Mỗi biểu đồ có bảng đối chiếu; trên điện thoại cuộn ngang trong vùng bảng để xem đủ cột.
- Chuông là **số cảnh báo đang hiệu lực**, không phải chưa đọc hoặc số thiết bị. Một thiết bị có hai lý do có thể hiện hai dòng. Lọc mức độ/lý do/tên thay đổi số kết quả, không biến chuông thành số dòng sau lọc.
- Nếu tải lỗi nhưng số cũ còn hiện, xem mốc giờ cũ; không coi đó là số mới. Chỉ thử lại thủ công.

## Tài sản

Vào **Tài sản CNTT**, tìm theo mã/tên/seri hoặc dùng bộ lọc bộ phận, loại, trạng thái, năm mua/bảo hành. Dùng Trước/Sau cho các trang, xóa bộ lọc nếu không có kết quả. Mở chi tiết để xem thông tin và lịch sử.

Khi có quyền tạo/sửa, nhập các trường bắt buộc rồi lưu, chờ thông báo kết quả. Không bấm lưu nhiều lần khi phản hồi chưa rõ. Khi báo hồ sơ đã đổi, tải lại và đối chiếu trước khi sửa tiếp.

Lịch sử tài sản hiện chỉ có50 thao tác mới nhất và ghi chú nếu còn dữ liệu; chưa phải phân trang toàn bộ lịch sử tại chi tiết. Xóa thường là xóa mềm, không xóa lịch sử. Thiết bị còn phiếu pending bị chặn giao mới/thanh lý/xóa mềm/rời bảo trì; thu hồi vẫn được phép theo quyền.

## Import / Export tài sản

1. Tải mẫu từ ứng dụng, giữ đúng tiêu đề và định dạng. Chọn file XLSX theo giới hạn được hiển thị, không dùng tệp có macro hoặc công thức thay dữ liệu nhập.
2. Upload và xem Preview. Sửa các dòng có lỗi trong tệp nguồn rồi kiểm lại; không coi Preview là đã ghi tài sản.
3. Chỉ xác nhận áp dụng sau khi đã đối chiếu dữ liệu/phạm vi. Nếu nhận lỗi hoặc chưa rõ đã ghi, tải lại danh sách/kiểm lịch sử trước khi gửi lại, không tự chạy lại nhiều lần.
4. Export theo bộ lọc và quyền; vượt giới hạn cần thu hẹp, không chia bằng cách bỏ qua kiểm quyền.

## Phân bổ và thu hồi

Chọn thiết bị, người hoặc bộ phận nhận theo danh sách được phép; đối chiếu ngày giao/hạn trả và nội dung biên bản. Thiết bị phải thỏa điều kiện giao, không có lượt giao hiệu lực/phiếu pending khác gây chặn.

Người nhận xác nhận bằng chính tài khoản được chỉ định; Admin không ký thay. Có thể tải biên bản PDF khi môi trường đủ thư viện/font. Thu hồi ghi nội dung và ngày theo form, giữ lại lịch sử thay vì tạo lại hồ sơ.

## Phần mềm và giấy phép

Admin quản lý catalog/key; các vai trò khác chỉ thấy thông tin che key theo quyền. Cấp/thu hồi chỗ cho máy/bộ phận phải đúng phạm vi; cấp theo user chỉ khi có quyền và phạm vi được xác minh (hiện Admin thực hiện).

Vượt số chỗ chỉ Admin được xác nhận, bắt buộc lý do. Xem key đầy đủ cần nhập lại mật khẩu, có giới hạn tần suất, ghi nhật ký và tự ẩn sau10 giây. Không sao chép key vào ghi chú/báo lỗi; nếu thiếu cấu hình mã hóa, liên hệ quản trị, không tự tạo khóa mới để thay dữ liệu cũ.

Giấy phép ngừng sử dụng/hết hạn không nhận cấp mới. Cảnh báo hết hạn và vượt chỗ là hai lý do khác nhau; thu hồi không xóa lịch sử cấp.

## Bảo trì và hỗ trợ

1. Chọn thiết bị hoặc nhập ID, loại phiếu, mức độ và nội dung; bấm **Tạo phiếu**, chờ thông báo và đọc lại phiếu.
2. Admin/Quản lý trong scope chọn kỹ thuật viên và nhập lý do gán/gán lại. Hỗ trợ không tự gán lại người xử lý.
3. Khi xử lý, nhập ngày, ghi chú/kết quả và chi phí hợp lệ theo quyền; chọn hoàn tất hoặc thất bại. Phiếu failed có thể mở lại nếu thiết bị không có pending khác; resolved không mở lại.
4. Chỉ Admin sửa chi phí phiếu đã đóng, phải có lý do. Thống kê tính theo năm mở phiếu, mở lại không làm tăng số phiếu.

Chỉ bảo trì định kỳ hoàn tất mới cập nhật lịch bảo trì thành công. Thiết bị hoàn tất nhưng chưa phân bổ vẫn chờ Admin kiểm tra/xác nhận sẵn sàng; không tự coi là đã giao cho người dùng.

## Vòng đời tài sản

Admin/Quản lý xem tuổi, ba thành phần điểm thay thế và nhóm đánh giá. Điểm hiển thị3 số lẻ nhưng so/sắp xếp theo giá trị gốc; điểm0,7996 hiển thị0,800 vẫn có thể chưa đạt ngưỡng0,8. Điểm có thể lớn hơn1.

Mở phần giải thích để xem dữ liệu thiếu/cấu hình mặc định và chi tiết tính. “Chưa đủ dữ liệu” không phải điểm0. Danh sách đề xuất chỉ để xem xét, không tự thanh lý hoặc tạo mua sắm.

## Dự báo và ngân sách

Chọn năm/bộ phận/loại rồi **Tính dự báo hiện tại**. Đối chiếu ba thành phần thay thế, bảo trì, mua thêm và dự phòng cố định10%. Thiếu giá/định mức/lịch sử cần thiết được ghi rõ; tổng phần đã tính không phải ngân sách hoàn chỉnh.

Admin đặt đơn giá/định mức; để trống khác với nhập rõ0. Mua thêm = làm tròn lên(nhân sự tăng × định mức) × đơn giá, không trừ tồn kho. Bảo trì dựa chi phí của3 năm lịch hoàn chỉnh theo tập thiết bị hiện tại; thiết bị sắp thay vẫn có thể có cả bảo trì và thay thế.

Lưu kế hoạch cho một bộ phận sẽ giữ snapshot toàn bộ loại của bộ phận đó, không chỉ loại đang lọc. Đổi giá không tự đổi bản đã lưu. Muốn thay snapshot bản nháp, đọc lại dữ liệu và xác nhận tính lại; bản đã chốt chỉ đọc. Chọn **Xem snapshot đã lưu** để đối chiếu đúng nguồn.

## Báo cáo

Vào **Báo cáo**, chọn tồn kho/bảo trì/vòng đời ngắn/ngân sách và bộ lọc, bấm Áp dụng. Vòng đời ngắn là đã tới hoặc còn tối đa12 tháng lịch tới tuổi thọ, khác với danh sách điểm thay thế. Với snapshot ngân sách, ID kế hoạch phải khớp năm/bộ phận và quyền hiện tại.

Preview xong mới bấm Xuất Excel/PDF. Tại trang Báo cáo: Excel tồn kho tối đa5.000 dòng, Excel khác1.000, mọi PDF500; không gồm tiêu đề/tổng. Vượt giới hạn thì thu hẹp bộ lọc. Các nút xuất ở màn hình nghiệp vụ khác vẫn theo giới hạn được ghi tại màn hình đó.

Nếu dữ liệu/bộ lọc đã đổi hoặc preview quá15 phút, tải lại preview trước khi xuất. Lỗi audit/quyền/định dạng file không được coi là đã tải thành công. Không tự gửi lại POST khi phản hồi chưa rõ.

## Xử lý thông báo

| Tình huống | Việc nên làm |
|---|---|
| Hết phiên / yêu cầu đăng nhập | Đăng nhập lại, mở đúng trang rồi kiểm dữ liệu trước khi lưu |
| Không có quyền / ngoài bộ phận | Liên hệ Admin IT, không đổi ID/URL để vượt phạm vi |
| Dữ liệu đã đổi /409 | Tải lại và đối chiếu, không ghi đè theo dữ liệu cũ |
| Dữ liệu chưa hợp lệ /422 | Sửa trường được báo; giới hạn dòng thì thu hẹp bộ lọc |
| Tần suất quá cao /429 | Chờ theo hướng dẫn, không bấm liên tục |
| Chưa sẵn sàng /503 hoặc mất kết nối | Không suy đã lưu; kiểm lại danh sách/nhật ký, thử GET thủ công, báo quản trị nếu lặp lại |

Khi báo lỗi, gửi màn hình thao tác, giờ Việt Nam, thông báo, loại tài khoản và mã hồ sơ không nhạy cảm. Không gửi mật khẩu, key, cookie, token CSRF hoặc dump DB. Không chạy SQL sửa trạng thái để bỏ chặn.
