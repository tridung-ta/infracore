# Hướng dẫn sử dụng InfraCore

## Bổ sung23/10 — Bảng dòng ngân sách (build FE local)

Bảng ngân sách chia20 dòng/trang; dùng Trước/Sau dưới bảng để đối chiếu các dòng khác. Tổng tiền phía trên luôn là toàn nguồn đang xem, không chỉ trang hiện tại. Khi tính lại hoặc mở snapshot khác, bảng trở về trang1. Xuất Excel/PDF vẫn xuất toàn nguồn theo bộ lọc/quyền và giới hạn server hiện có, không chỉ các dòng đang nhìn thấy. Chưa nghiệm thu DB/host.

## Bổ sung23/10 — So sánh nhóm vòng đời (build FE local)

Trong Vòng đời tài sản, phần So sánh theo nhóm có hai chế độ Loại thiết bị và Bộ phận. Mỗi trang tối đa20 nhóm; dùng Trước/Sau dưới bảng. Chuyển chế độ giữ trang vừa xem của từng bảng; nhận dữ liệu mới sẽ về trang1. Tổng quan phía trên và tỷ lệ lỗi của từng nhóm giữ nguyên khi chuyển trang nhóm. Chức năng này chỉ giúp xem bảng gọn hơn; chưa nghiệm thu DB/host.

## Bổ sung23/10 — Quản lý loại tài sản (Admin IT, build local)

Mở Quản lý loại tài sản, nhập mã/tên và bấm Tìm loại. Danh sách20 loại/trang có Trước/Sau; cả loại hoạt động và ngừng sử dụng đều xuất hiện. Chọn một loại để sửa; đổi trang không xóa nội dung đang sửa. Sau lưu thành công form trở về thêm mới, cần chọn lại dòng để sửa tiếp bằng version mới. Lỗi409 cần kiểm lại hồ sơ trước khi lưu; ứng dụng không tự ghi đè bằng version mới. Nếu đã lưu nhưng tải lại lỗi, không bấm lưu lại cùng thao tác; tìm/tải lại danh sách để kiểm tra. Dropdown trên form tài sản vẫn giữ giới hạn500 loại hiện tại. Chức năng chưa nghiệm thu DB/host.

## Bổ sung23/10 — Lịch sử tài sản (build local)

Mở hồ sơ tài sản, chọn tab Lịch sử. Mỗi trang hiển thị tối đa20 thao tác; Trước/Sau cho phép xem cả các thao tác cũ hơn50 bản ghi. Số trang/tổng thao tác nằm dưới danh sách, có thể cần cuộn trong hộp thoại. Mở từng dòng để xem dữ liệu trước/sau. Khi tải lỗi, nội dung lịch sử được ẩn và hiển thị thông báo; thử lại hướng chuyển trang khi phù hợp. Cả ba vai trò chỉ xem tài sản trong phạm vi server cho phép. Đây là audit thao tác tài sản; lịch sử bảo trì/phân bổ vẫn xem tại phần tương ứng. Chưa kiểm trên DB/host thật.

## Bổ sung23/10 — Thống kê phiếu theo năm (build local)

Tại Bảo trì & hỗ trợ, nhập ID thiết bị ở bộ lọc nếu cần, chọn năm và bấm Xem thống kê. Kết quả chia20 nhóm/trang theo thiết bị, loại và trạng thái; dùng Trước/Sau để chuyển trang. Tổng số phiếu/chi phí phía dưới là toàn bộ kết quả của bộ lọc đã áp dụng, không chỉ trang đang xem. Caption nêu năm và ID đang áp dụng; sửa input cần bấm Xem thống kê để áp dụng lại. Nếu tải lỗi, kết quả trước được giữ. Hỗ trợ chỉ có thống kê phiếu mình xử lý trong scope. Chức năng chưa được kiểm trên DB/host thật.

Cập nhật22/10/2026 theo source đã build local. **DB/host chưa nghiệm thu**; màn hình trên host phụ thuộc bộ source user triển khai. Trang có nhãn MOCK chỉ dùng minh họa, không lưu dữ liệu thật. Hướng dẫn này không xác nhận đã upload hoặc kiểm thử host.

## Đăng nhập và phạm vi

Mở địa chỉ HTTPS do quản trị viên cung cấp, nhập tên tài khoản hoặc email và mật khẩu hiện có. Nếu chưa được cấp quyền IT, liên hệ Admin IT. Không gửi mật khẩu/key qua ảnh hoặc báo cáo lỗi. Đăng xuất bằng nút trên thanh tài khoản.

| Vai trò | Công việc chính |
|---|---|
| Admin IT | Quản trị tài khoản/phạm vi, quản lý toàn hệ thống, key giấy phép, xác nhận vượt chỗ và chi phí phiếu đã đóng |
| Quản lý hệ thống | Tra cứu và thực hiện công việc được phép trong bộ phận phụ trách; xem vòng đời/ngân sách/báo cáo trong phạm vi |
| Hỗ trợ kỹ thuật | Công việc tài sản/phiếu được giao theo phạm vi; phần dashboard chỉ có bảng bảo trì, không ngân sách/vòng đời/xuất báo cáo |

Bộ phận phụ trách là phạm vi quản lý đã được cấp, không tự suy từ tài khoản cùng store. Đường dẫn trực tiếp vẫn phải qua kiểm quyền server; thấy một nút không đồng nghĩa thao tác sẽ được cho phép.

## Theo vai trò — Admin IT

1. Mở **Tài khoản & phân quyền**, chọn tài khoản hiện có, vai trò, bộ phận phụ trách và trạng thái cấp quyền. Lưu rồi đọc lại thông tin/nhật ký. Hệ thống chặn thu hồi Admin IT cuối cùng; không sửa SQL để vượt chặn.
2. Tại **Tài sản CNTT**, quản lý hồ sơ và danh mục; trước xóa mềm/thanh lý, đối chiếu người nhận, lượt giao và phiếu pending. Không có luồng tự động thanh lý từ điểm vòng đời.
3. Tại **Phân bổ & thu hồi**, chọn đối tượng nhận từ danh sách; người nhận tự xác nhận, Admin không ký thay. Chỉ tải PDF nếu nút khả dụng và đã đọc lại hồ sơ.
4. Tại **Phần mềm & giấy phép**, lưu thông tin/key, cấp hoặc thu hồi chỗ. Nếu cần cấp vượt, đọc số đã cấp/tổng chỗ, xác nhận và nhập lý do; không tự tăng tổng chỗ cho khớp. Xem key cần nhập lại mật khẩu và tự ẩn sau10 giây.
5. Tại **Bảo trì & hỗ trợ**, tạo/gán/xử lý phiếu; sửa chi phí phiếu đã đóng cần lý do. Với cảnh báo chờ sẵn sàng, kiểm tra thiết bị và nhập kết quả/lý do trước xác nhận.
6. Xem vòng đời, dự báo và báo cáo; quản lý đơn giá/định mức, lưu kế hoạch theo bộ phận. So đúng nguồn hiện tại/snapshot và dữ liệu thiếu. Không có nút chốt/mở lại kế hoạch trong phạm vi đã build.

Các bước trên mô tả code local hiện có. Lưu DB, phân quyền đồng thời, file tải và khóa trên PHP-FPM host **chưa nghiệm thu**; không hiểu hướng dẫn là xác nhận vận hành thành công.

## Theo vai trò — Quản lý hệ thống

1. Đăng nhập và kiểm bộ phận có thể chọn. Tìm/mở tài sản trong phạm vi; tạo/sửa hồ sơ, import và export theo quyền. Chọn đúng bộ phận đích khi điều chuyển; không có quyền xóa mềm/thanh lý hoặc sửa danh mục dùng chung.
2. Giao/thu hồi thiết bị trong phạm vi; người nhận vẫn tự xác nhận. Nếu dữ liệu đã đổi, tải lại trước khi thao tác.
3. Xem giấy phép che key; cấp/thu hồi cho máy hoặc bộ phận trong phạm vi. Không sửa catalog/key, không xem key đầy đủ, không xác nhận vượt chỗ; cấp theo user hiện chỉ Admin thực hiện.
4. Tạo phiếu và gán/gán lại kỹ thuật viên trong phạm vi, có lý do. Quản lý không trực tiếp chuyển kết quả xử lý phiếu; kỹ thuật viên Hỗ trợ hoặc Admin thực hiện theo quyền.
5. Xem vòng đời, dự báo, báo cáo theo bộ phận được cấp; lưu kế hoạch mở rộng của bộ phận. Không lưu đơn giá/định mức; liên hệ Admin khi thiếu cấu hình. Khi tính lại bản nháp, đối chiếu snapshot/version và xác nhận, không làm đổi bản đã chốt.

Nếu danh sách trống hoặc403, kiểm bộ lọc và nhờ Admin đối chiếu phạm vi; không thay URL/ID để mở dữ liệu bộ phận khác. Không có quyền quản trị tài khoản hoặc xem trang nhật ký toàn hệ thống.

## Theo vai trò — Hỗ trợ kỹ thuật

1. Tra cứu thiết bị trong bộ phận phụ trách, xem hồ sơ/lịch sử; ghi chú và đổi trạng thái theo chuyển đổi hợp lệ. Không tạo/sửa thông tin hồ sơ đầy đủ, xóa mềm/thanh lý hoặc import/export tài sản.
2. Thu hồi thiết bị trong phạm vi khi có lượt giao hiệu lực, nhập tình trạng thu hồi. Không giao mới; nếu là người nhận được chỉ định thì xác nhận biên bản bằng tài khoản của mình.
3. Tạo phiếu cho thiết bị trong phạm vi, nhập nội dung/mức độ/loại; không tự gán lại kỹ thuật viên. Xử lý phiếu theo quyền: nhập ngày xử lý, chi phí và kết quả rồi hoàn tất/thất bại. Không sửa chi phí phiếu đã đóng hoặc xác nhận sẵn sàng thay Admin.
4. Xem bảng bảo trì trong ứng dụng; thống kê ticket của Hỗ trợ giới hạn phiếu có người xử lý là mình trong scope. Không có quyền biểu đồ tổng quan, trung tâm cảnh báo riêng, giấy phép/key, vòng đời, ngân sách hoặc xuất báo cáo.

Code hiện giới hạn xử lý theo scope thiết bị; không suy rằng mọi endpoint chỉ cho người được gán nhìn thấy. Nếu công ty cần siết chỉ người được gán mới xử lý, phải thống nhất nghiệp vụ và gate riêng trước thay đổi.

## Ảnh minh họa và kiểm lại trên host

Tài liệu này dùng hướng dẫn chữ, **không nhúng ảnh**. Khi bổ sung ảnh từ trang tĩnh phải giữ badge **MOCK** trong ảnh và chú thích “MOCK — dữ liệu minh họa, không chứng minh đã lưu DB”; không lấy ảnh minh họa làm bằng chứng nghiệm thu. Không chụp key, password, token hoặc thông tin cá nhân thật.

Sau khi user deploy, cần kiểm lại layout PHP thật tại1366/768/390 bằng tài khoản thật từng vai trò, cả danh sách/form/lỗi và URL trái quyền. Phép đo static MOCK/API mock local hiện có chưa thay bước này. DB Phase7–10 vẫn chưa nghiệm thu; hướng dẫn không hứa phần BE11 chưa build.

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

Người nhận xác nhận bằng chính tài khoản được chỉ định; Admin không ký thay. Có thể tải biên bản PDF khi môi trường đủ thư viện/font. Thu hồi ghi tình trạng/nội dung theo form, giữ lại lịch sử thay vì tạo lại hồ sơ.

## Phần mềm và giấy phép

Admin quản lý catalog/key; Quản lý thấy thông tin che key, Hỗ trợ không được mở chức năng giấy phép. Cấp/thu hồi chỗ cho máy/bộ phận phải đúng phạm vi; cấp theo user hiện chỉ Admin thực hiện.

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

### Đọc đúng kết quả thao tác

| Trạng thái nhìn thấy | Ý nghĩa / bước tiếp |
|---|---|
| Danh sách tải xong nhưng không có dòng | Kiểm bộ lọc, trang hiện tại và phạm vi; không tự kết luận DB đã mất dữ liệu |
| Đã lưu và đã đọc lại hồ sơ | Đối chiếu mã hồ sơ, giá trị mới và lịch sử; đây là phản hồi của request hiện tại, không phải nghiệm thu cả chức năng |
| Đã lưu nhưng tải lại hồ sơ thất bại | Không bấm lưu lần nữa; tải lại danh sách/hồ sơ để đối chiếu trước thao tác mới |
| Chưa xác nhận đã lưu / mất phản hồi | Không đoán thành công hoặc thất bại; giữ nội dung cần đối chiếu, dùng tải lại/kiểm trạng thái thủ công. Import dùng kiểm trạng thái lô, không upload/xác nhận lại mù |
| Bản hiện tại / snapshot đã lưu | Dữ liệu hiện tại có thể đổi theo giá/phiếu; snapshot giữ số liệu lúc lưu. Kiểm nguồn, năm, bộ phận trước so hoặc xuất |
| Đang dùng giá trị mặc định | Một số cấu hình vòng đời chưa có, đang dùng số mặc định được ghi rõ; khác với dữ liệu tài sản thiếu và khác với cấu hình sai |
| Chưa đủ dữ liệu | Không thay bằng0 để quyết định; nhờ người có quyền bổ sung thông tin/cấu hình, rồi chủ động tính lại nếu cần |

Phản hồi trong bản MOCK chỉ minh họa; không tạo hồ sơ thật. Không dùng nút giao diện đang bị vô hiệu hóa làm bằng chứng server cho phép/từ chối. Khi đổi vai trò/phạm vi hoặc tài khoản bị khóa, mở lại phiên theo hướng dẫn; không cố gửi lại request cũ.

### Tự đối chiếu một vòng công việc theo vai trò

- **Admin:** kiểm quyền/bộ phận tài khoản → mở đúng thiết bị → tạo/gán/xử lý phiếu → đọc lại trạng thái/lịch bảo trì → nếu có chờ sẵn sàng thì kiểm tra và xác nhận → đối chiếu nhật ký. Quyền cuối cùng do server quyết định; chưa có chức năng tự phê duyệt mua sắm/chốt kế hoạch.
- **Quản lý:** chọn bộ phận phụ trách → tìm tài sản hoặc xem vòng đời → tạo phiếu/gán kỹ thuật viên nếu cần → xem ngân sách đúng nguồn hiện tại/snapshot. Không trực tiếp xử lý kết quả phiếu, sửa key hoặc đơn giá.
- **Hỗ trợ:** tìm thiết bị trong scope → đọc nội dung phiếu → nhập kết quả/ngày/chi phí khi xử lý → đọc lại kết quả. Nếu cần đổi kỹ thuật viên, sửa chi phí phiếu đóng hoặc xác nhận sẵn sàng, chuyển thông tin cho người có quyền; không sửa SQL để bỏ chặn.

Đây là hướng dẫn thao tác từ source đã build, không yêu cầu user chạy trên host lúc này. Các phép thử DB, rollback và hai phiên đồng thời vẫn theo checklist riêng; không suy đã đạt chỉ vì hoàn thành một vòng giao diện.

| Tình huống | Việc nên làm |
|---|---|
| Hết phiên / yêu cầu đăng nhập | Đăng nhập lại, mở đúng trang rồi kiểm dữ liệu trước khi lưu |
| Không có quyền / ngoài bộ phận | Liên hệ Admin IT, không đổi ID/URL để vượt phạm vi |
| Dữ liệu đã đổi /409 | Tải lại và đối chiếu, không ghi đè theo dữ liệu cũ |
| Dữ liệu chưa hợp lệ /422 | Sửa trường được báo; giới hạn dòng thì thu hẹp bộ lọc |
| Tần suất quá cao /429 | Chờ theo hướng dẫn, không bấm liên tục |
| Chưa sẵn sàng /503 hoặc mất kết nối | Không suy đã lưu; kiểm lại danh sách/nhật ký, thử GET thủ công, báo quản trị nếu lặp lại |

Khi báo lỗi, gửi màn hình thao tác, giờ Việt Nam, thông báo, loại tài khoản và mã hồ sơ không nhạy cảm. Không gửi mật khẩu, key, cookie, token CSRF hoặc dump DB. Không chạy SQL sửa trạng thái để bỏ chặn.
