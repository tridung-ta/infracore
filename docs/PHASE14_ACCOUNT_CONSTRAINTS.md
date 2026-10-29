# Ràng buộc thêm tài khoản nhân viên — 28/10/2026

Rà theo yêu cầu user “nhớ cần phải có đầy đủ các ràng buộc cần thiết cho phần thêm nhân viên”. Mở rộng kiểm từ57 lên135checks; phát hiện và sửa trường hợp họ tên/mật khẩu chỉ chứa khoảng trắng Unicode hoặc ký tự định dạng vô hình. Giữ các giới hạn độ dài/quyền hiện tại, không tự thêm trường HR. Source thực thi là ItaUserCreationInput/Service, không dùng FE thay bảo vệ server.

| Trường / thao tác | Ràng buộc hiện có | Kết quả khi vi phạm |
|---|---|---|
|Tên đăng nhập|Bắt buộc,3–50 ký tự ASCII chữ/số, chấm/gạch ngang/gạch dưới; bắt đầu chữ/số; không @ hoặc dấu cách bên trong; trim ngoài|422, không ghi DB|
|Họ tên|Bắt buộc sau trim, tối đa100 ký tự UTF-8 hợp lệ, không NUL/ký tự điều khiển hoặc chỉ khoảng trắng/format vô hình|422, không ghi DB; không tự cấm tên quốc tế hoặc tên có số|
|Email|Bắt buộc, định dạng hợp lệ, tối đa100 ký tự; trim ngoài|422, không ghi DB|
|Trùng định danh|Kiểm username/email cả hai chiều trong store, kể cả native đã khóa; serialized theo role locks của các writer ITA|409; tên/email case/collation/race writer ngoài ITA còn cần DB thật, không bảo đảm uniqueness toàn hệ thống từ fake test|
|Mật khẩu ban đầu|Ít nhất 6 ký tự Unicode, tối đa 72 byte; không NUL/không toàn khoảng trắng; giữ nguyên byte, không trim; xác nhận trùng chính xác|422; kiểm số ký tự và byte UTF-8 ở JS và PHP|
|Lưu mật khẩu|PASSWORD_DEFAULT hash; native password chỉ placeholder ngẫu nhiên, không MD5 mật khẩu thật; không trả/lưu credential trong DTO/audit/browser storage của app|Input xóa sau request/Hủy/đổi trang; không có chức năng đổi mật khẩu user cũ trong luồng này|
|Quyền chức năng|Ít nhất1 key thuộc catalogue, đủ dependency, không vượt quyền hiện tại của người cấp; keys trùng chuẩn hóa|422/403; role gửi giả không cấp Admin|
|Bộ phận|Array tối đa500, mỗi ID nguyên dương theo ItaInput; chuẩn hóa trùng; phải active/cùng store; nếu không có scope.store phải chọn ít nhất1|422; kiểm references lại dưới khóa|
|Người tạo|Phiên hợp lệ, mode capabilities bật, access.manage +scope.store; current/revision/quyền còn hiệu lực dưới khóa và trước native activation|403; mode chưa bật503, không tạo tài khoản|
|Request|POST, JSON giới hạn16KiB, CSRF đúng; GET không tạo|405/403/422 hoặc lỗi boundary kiểm soát; không SQL/write từ GET|
|Store/ID/type/status|Server quyết định; không dùng giá trị client giả mạo. Native ID auto_increment; type staff='1', status0 khi provision; role metadata support_technician|Không tự tạo native Admin hoặc role4|
|Schema native|Đủ cột chuẩn, id auto_increment, độ dài char/varchar thực; extra NOT NULL không default chỉ allowlist đã rà|Cột bắt buộc lạ503 trước INSERT, không tự ALTER/default đoán|
|Lưu một lần|Dialog Hủy không POST; đang lưu khóa nút;409/422 cho sửa;503/transport không rõ chặn tạo lại mù quáng|Kiểm danh sách/ID trước thao tác lại; không auto retry|
|Audit/lỗi|Audit user.create không credential; transaction ITA; native lỗi cố khóa đúng ID mới, không DELETE/reset user cũ|Không bảo đảm rollback MyISAM hoặc compensation khi kết nối chết; cần quản trị kiểm trạng thái chưa rõ|

## Kiểm đã chạy

`php tests/ita_phase14_user_create.php`: **135checks đạt** bằng PHP8.5.10, service/input thật +fake transport, không DB/host. Các ca sai shape/boundary bổ sung xác nhận chưa beginTransaction/execute. Có test giả store/type/role, chuẩn hóa trùng và schema thiếu/ID không auto/width ngắn. Tổng check không phải135 request tấn công thật.

Test mới đã đỏ với “No denial: Unicode whitespace fullname denied” trước khi sửa; PHP trim không loại NBSP. Test C1 control U+0085 trong tên cũng đã đỏ trước khi đổi kiểm control sang Unicode Cc. Sau sửa, kiểm PHP đạt; không loại ký tự Unicode định dạng nằm trong tên có nội dung thật. Mật khẩu giữ nguyên byte nếu có nội dung, không trim.

`playwright-cli -s=ita-local-review run-code --filename=tests/ita_phase14_user_create_browser.js`: **27checks đạt**, có4case Unicode blank/format-only trước POST, các luồng tạo/sửa/409/503 và bounds1366/768/390 giữ. Một case tên NBSP được form-guidance hiện có chặn bằng validity; case zero-width dùng guard mới. Không yêu cầu mọi lỗi phải hiện cùng một nhãn khi đã có feedback field hợp lệ. Node/RAM, không DB; riêng503 là fixture có chủ đích. PHP lint/JS syntax/Smarty/diff-check kiểm trong lượt này; không chạy lại PHP8.3 hoặc coi hồi quy cũ là chạy mới.

Chưa xác nhận native schema/collation/unique indexes, effective runtime grants, native login user mới, persistence/rollback/concurrency thật hoặc hostHTTPS. Không đọc private config, kết nối DB hoặc thêm dữ liệu thật. Không đóng gói/upload/push/merge/tag. User vẫn kiểm form Node/RAM tại127.0.0.1:18767, không dùng tài khoản/mật khẩu thật.
