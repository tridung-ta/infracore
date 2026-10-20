# Phase 5 — Checklist UAT khi user sẵn sàng

Chưa deploy, không chạy SQL mới. Dùng schema assignments hiện có từ migration 003; không import lại CREATE. Trước bàn giao source phải có manifest/hash/package đã chốt; không upload toàn repo hoặc private config/dump/tests. User sao lưu source và DB ngoài webroot trước UAT. Không kiểm tranh chấp/gây lỗi trên production đang có người dùng.

| Ca | Thao tác | Mong đợi |
| --- | --- | --- |
| Giao nhân viên | Admin/Quản lý chọn thiết bị in_use chưa giao trong scope, chọn tài khoản active cùng store | Có số biên bản server, current_user_id đúng, một lượt mở, audit assignment.assign + asset.update; không ghi bảng dc_ |
| Giao bộ phận | Chọn bộ phận active được cấp phạm vi | Chỉ to_department_id có giá trị, current_user_id null; department_id asset theo bộ phận nhận; lịch sử giữ bộ phận đích |
| Không sẵn sàng | Thử thiết bị retired/broken/maintenance/đang giao hoặc current_user_id còn dữ liệu | 409, không có lượt giao thứ hai; không tự đổi status |
| Scope | Thử URL asset khác store/ngoài bộ phận, giả target department ngoài phạm vi | 404/403 theo tình huống; không ghi và không lộ history |
| Support | Đăng nhập Hỗ trợ: thử POST assign trực tiếp; sau đó thu hồi thiết bị trong scope | Assign 403, thu hồi được với tình trạng/lý do; ngoài scope 403 |
| Người nhận | Tài khoản người nhận có quyền ITA và scope vào xác nhận | Acknowledged_by là actor, UTC timestamp do server; lần hai bị chặn |
| Chống giả danh | Admin khác người nhận, tài khoản khác, hoặc bàn giao bộ phận thử acknowledge | 403, không có acknowledged_at; không suy Admin được ký thay |
| Thu hồi | Nhập tình trạng, xác nhận thu hồi | Returned_at có giá trị, current_user_id null, bộ phận giữ nguyên, lý do trong audit; lịch sử không mất; không thu hồi lặp |
| Target/status | Người nhận inactive/khác store, bộ phận inactive, ngày thu hồi trước ngày giao | 422; không tạo assignment |
| Version/CSRF | Mở hai tab, sửa ở một tab rồi gửi tab cũ; bỏ/sửa token | 409/403; UI yêu cầu tải lại, không tự thử ghi lại |
| Đồng thời | Khi có môi trường phù hợp, hai tài khoản giao cùng một thiết bị | Một lượt mở, không current_user_id sai hoặc audit thừa; đối chiếu unique key + transaction thật |
| Lỗi mạng | Ngắt mạng lúc ghi, nối lại và Tải lại hồ sơ | Không bấm ghi lại khi chưa biết kết quả; đối chiếu active/history trước khi tiếp tục |
| Lịch sử | Tạo đủ lượt nếu phù hợp dữ liệu QA, chuyển trang | 20 lượt/trang, không giới hạn ngầm toàn bộ lịch sử; không lộ private handover_file |
| Quá hạn | Đối chiếu thiết bị có next_maintenance_due từ dữ liệu thực đã có, trước hôm nay VN; không sửa DB chỉ để tạo test | Badge + trung tâm cảnh báo + bộ đếm khớp phạm vi; hôm nay/tương lai/retired không quá hạn; hai lý do có thể là hai cảnh báo |
| Responsive | 1366/768/390, tên người/thiết bị dài, bàn phím, lỗi server | Control thao tác được, phản hồi hiện rõ và không lộ exception/credential |
| PDF runtime | Kiểm host đủ vendor/font và GD/mbstring/zlib; tải từ lượt đang mở và lịch sử bằng ba vai trò đúng scope | PDF thật mở được, số/ngày/seri/người giao-nhận đúng, dấu tiếng Việt và khung ký đầy đủ; kiểm audit assignment.pdf_download. Không đủ runtime thì báo thiếu, không ảnh hưởng thao tác khác |
| PDF quyền/lỗi | Gọi ID lượt giao khác store/ngoài scope; mô phỏng audit không sẵn sàng chỉ khi được phép | 404/403 hoặc lỗi có kiểm soát; không trả bytes trái quyền hoặc PDF thiếu log. Không để lộ handover_file/path/private config |

Sau UAT chỉ xử lý dữ liệu QA bằng thao tác ứng dụng được phép. Không DELETE/DROP, xóa audit hoặc reset DB để làm sạch. Gửi kết quả ca/batch hoặc số biên bản/số dòng trước-sau; không gửi token, cookie, mật khẩu hoặc dump. Các phần chưa chạy giữ trạng thái chưa nghiệm thu.
