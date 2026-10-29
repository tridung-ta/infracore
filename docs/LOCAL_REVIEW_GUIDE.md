# Bản local để tự kiểm toàn bộ phần đã sửa — 28/10/2026

**Mở: http://127.0.0.1:18767/**. Nếu server đã dừng, nhấp đúp `D:\InfraCore\start-local-review.cmd`, giữ cửa sổ đó mở; Ctrl+C để dừng. Cần Node đã có trên máy, không cài package. Nếu cổng đang dùng, mở URL và xác nhận có thanh LOCAL MOCK; không tắt process lạ. Không truy cập bằng URL host.

Bản này dùng **template/CSS/JS ứng dụng hiện tại**, nhưng transport do Node mô phỏng, không chạy PHP, không kết nối DB hoặc đọc cấu hình private. Không đăng nhập thật. Nút Lưu/nhập/phân quyền chỉ đổi dữ liệu giả trong RAM. Khi khởi động, builder ghi lại2HTML preview sinh tự động; file sample test có thể được tải về máy. Điều này khác với lưu dữ liệu nghiệp vụ vào DB.

Thanh trên cùng có chọn quyền và kịch bản. Desktop mở sẵn; mobile bấm dòng LOCAL MOCK để mở/thu gọn. Sau mỗi nhóm kiểm, dùng **Đặt lại tất cả dữ liệu giả** nếu cần: trở lại6thiết bị/phiếu/giấy phép mẫu và bỏ trạng thái nhắc phiên của riêng origin local. Không xóa DB hoặc file. Thiết lập theme/hiệu ứng của trình duyệt được giữ.

## Bấm thử lần lượt

**Mới: Admin tạo tài khoản.** Chọn Admin ở thanh LOCAL MOCK →Tài khoản & phân quyền →Thêm tài khoản. Nhập `mock-new-user`, họ tên giả, email `mock-new-user@example.invalid`, mật khẩu giả `MOCK-password-123` hai lần; mở nhóm Tài sản chọn Xem +Thêm, chọn bộ phận Văn phòng. Bấm Tạo tài khoản & cấp quyền, thử Hủy rồi xác nhận. User mới chỉ nằm trong RAM; mở Phân quyền để đối chiếu, không đăng nhập native thật. Xem PHASE14_ACCOUNT_CREATE_VERIFY.md. Không dùng mật khẩu/tài khoản thật trong bản local.

| Phần | Thao tác cụ thể | Mong đợi |
|---|---|---|
|Đăng nhập/motion|Bấm **Xem giao diện đăng nhập** ở thanh LOCAL MOCK. Thử hiện/ẩn password và đổi hiệu ứng/theme. Quay lại tab app thử Bật/Tắt|Giao diện đã nâng cấp; form MOCK không gửi credential thật. Không nhập mật khẩu thật|
|Nhật ký|Chọn **Nhật ký thao tác**. Chọn người theo tên, nhóm thao tác, ngày hoặc ngày nhanh; bấm lọc/xóa lọc; mở chi tiết một dòng|Nhãn tiếng Việt, bảng thay đổi; null/null không hiện JSON rỗng. ID mẫu không có trong catalogue vẫn hiện#ID, không đoán tên|
|Quyền từng người|Chọn quyền **Admin** ở thanh trên. Mở **Tài khoản & phân quyền → Phân quyền MOCK-C**. Có sẵn xem/thêm tài sản +xem báo cáo. Thử thêm Sửa thông tin tài sản, Lưu quyền, xác nhận. Chọn **C** ở thanh trên|C dùng quyền giả vừa lưu; menu/thao tác phản ánh permission. Đây không bật BE14 trên DB; SQL007 vẫn chưa chạy. Trở lại Admin/Đặt lại để tiếp tục|
|Vòng đời|Mở **Vòng đời tài sản**, xem **So sánh theo nhóm**, đổi Loại/Bộ phận, bấm **Xem N thiết bị**|Cột Số thiết bị rõ; áp dụng bộ lọc tương ứng. Điểm/tuổi là fixture minh họa, không kiểm công thức DB|
|Import hợp lệ|Thanh kịch bản chọn **Bình thường / import hợp lệ**. Vào Import/Export → Hướng dẫn & file mẫu → **Tải mẫu Excel**. Chọn tab **Nhập tài sản**, chọn đúng file LOCAL-MOCK vừa tải; bấm **Kiểm tra file & xem kết quả**. Thử Bỏ file, chọn lại. Cuối cùng bấm xác nhận và thử Hủy trước, rồi xác nhận lại|Chọn file chưa upload; xem trước chưa thêm tài sản. Hủy không nhập. Chấp nhận thêm2tài sản giả vào RAM; chuyển sang Tài sản sẽ thấy mã TEST-LOCAL-IM01/IM02|
|Import lỗi|Chọn kịch bản **Import có lỗi mã loại/bộ phận**; vào tab Nhập tài sản, chọn file mẫu, Kiểm tra|Có1dòng lỗi, tổng/hợp lệ/cần sửa rõ; nút nhập khóa, có hướng sửa/tải Excel lỗi. Kịch bản quyết định kết quả, **không phân tích nội dung file**; không sửa workbook rồi coi demo đã kiểm XLSX thật|
|Bàn giao/thu hồi|Vào **Phân bổ & thu hồi**. Mới vào chưa mở hồ sơ. Bấm **Tìm thiết bị**, chọn TEST-LOCAL-000001. Xem lịch sử, thử nhập lý do thu hồi và xác nhận|Chỉ tải detail khi chọn; lịch sử/thông báo dễ đọc. Thao tác và PDF là giả. Có thể thử giao lại sau thu hồi, không chạm dữ liệu host|
|Chi phí bảo trì|Vào **Bảo trì & hỗ trợ → Mở phiếu MOCK-TICKET-9**. Nhập tổng22, lý do bất kỳ có chữ MOCK → Lưu chi phí. Thử Hủy; sau đó mở lại và xác nhận|Dialog100.000VND →22VND, nêu tổng thay thế; hủy giữ cũ, xác nhận đọc lại22 từ RAM|
|Năm2011|Cùng trang bảo trì, ở Thống kê nhập2011 và bấm Xem. Sau đó bấm **Năm hiện tại**, rồi Xem lại|2011 báo không có phiếu mở năm đó. Nút năm hiện tại chỉ điền ô, không tự gửi request; phải bấm Xem|
|Ngày giấy phép|Vào **Phần mềm & giấy phép → Mở hồ sơ**. Thử nhập năm111111, ngày hết hạn trước bắt đầu, sửa lại. Tên“1” và chi phí không âm vẫn được phép|Form chặn năm quá4chữ số/ngoài1000–9999 và ngày đảo. Không tự đặt policy cấm tên số. Key demo chỉ dùng mật khẩu giả **MOCK-ONLY**|
|Nhắc cảnh báo|Đặt lại dữ liệu nếu muốn badge mới. Bấm chuông hoặc Trung tâm cảnh báo, đợi tải thành công, rồi đổi trang/reload|Badge/chấm nhắc mất trong phiên; topbar vẫn ghi còn N cảnh báo cần xử lý. Không xóa nguyên nhân bảo trì thật; không lưu đã đọc trên DB|
|Mạng2D/3D|Kịch bản Bình thường → **Hạ tầng mạng**. Thử2D/3D, chuột/quay360, nút xoay, tên thiết bị, toàn màn hình và Esc|Máy in/máy tính/màn hình/laptop/server/switch có hình/màu phân biệt. Đây là liên kết khai báo giả, không quét mạng|
|Mạng vượt200|Chọn kịch bản **Mạng vượt200 — thử lọc**, vào Mạng. Gõ **TEST-LOCAL-000001** ở ô tìm rồi Áp dụng|Thông báo vượt cap vẫn giữ bộ lọc. Lọc mở lại được tập nhỏ; không nâng cap200/400 hoặc cắt ngầm graph|
|Một triệu|Chọn **1triệu: chỉ giả số tổng** → Tài sản/Mạng/Dashboard. Thử phân trang và lọc bộ phận|Chỉ giả số total1.000.000 và trả một trang nhỏ; trong RAM vẫn vài tài sản mẫu. Không có1triệu hồ sơ đầy đủ, không sinh DB hoặc benchmark. Một số ID ảo sâu không có detail|
|Ngân sách/báo cáo|Mở hai menu, thử tab/bộ lọc/nút và file tải|Chỉ dữ liệu/công thức fixture. File XLSX/PDF mẫu dùng thử nút tải, **không phải export PHP/mPDF thật hoặc kiểm công thức tài chính**|

## Giới hạn và phản hồi

Nút **Đặt lại tất cả dữ liệu giả** có hộp xác nhận: chọn **Hủy** để giữ các thay đổi đang thử; đồng ý mới trở về dữ liệu ban đầu. Không tác động DB/host. Lượt kiểm28/10 bổ sung xác nhận này đã chạy38browser checks và21contract checks, gồm cả hai nhánh Hủy/Đồng ý.

Không kiểm auth/password hash, CSRF/session thật, SQL/transaction/locking/concurrency, hiệu năng1triệu, PHP8.3/FPM hoặc header cuối host bằng bản này. Phân quyền mock giúp xem UI, không thay các test PHP thật trước đó hoặc duyệt migration. Mọi thao tác Node mock đều trong process; không có schema/migration/credential thật.

Nếu thấy lỗi, gửi **menu → quyền/kịch bản đang chọn → thao tác → mong đợi → kết quả/ảnh**. Chỉ gửi dữ liệu mẫu. Không upload launcher, assets/ita/preview/**, MOCK, tests, tmp hoặc file LOCAL-MOCK lên host. Đây không phải gói deploy. Sau user review mới chuẩn bị gói riêng khi được yêu cầu.
