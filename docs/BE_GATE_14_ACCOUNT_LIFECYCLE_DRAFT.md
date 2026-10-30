# Phase 14 — Hoàn thiện quản lý tài khoản: phạm vi đã duyệt

## Cập nhật 29/10/2026 — Quyết định thay thế bản phân tích bên dưới

Đã duyệt build source: xóa quyền ITA (không xóa native); sửa thông tin và reset mật khẩu có xác nhận tác động dùng chung. Chỉ tài khoản nhân viên được tạo từ InfraCore, có audit user.create cùng store/entity được sửa/reset. User duyệt CAS trong PmAuth để request login cũ không ghi đè credential vừa reset. Reset không giữ MD5 mật khẩu cũ/không tạo MD5 mới; legacy MD5-only có thể mất đăng nhập. Không tự đóng phiên legacy hoặc hứa atomicity MyISAM. Phần dưới giữ làm lịch sử phân tích trước duyệt; không còn là câu hỏi đang chờ.

User yêu cầu hoàn thiện tạo/xem/sửa/xóa và kiểm lại. Source hiện có tạo mới, xem, sửa quyền/phạm vi và ngừng truy cập ITA bằng `ita_user_roles.enabled`; chưa sửa thông tin native, reset mật khẩu cũ hoặc xóa native. Đây là bản phân tích, chưa build hoặc chạy SQL/DB/host.

## Quyết định phạm vi đang hỏi user

1. Nút xóa/ngừng: chỉ thu hồi ITA và giữ `dc_users` cùng dữ liệu lịch sử, hay khóa `dc_users.status` ảnh hưởng hệ thống cũ? Dự án không xóa vật lý dữ liệu cũ; thay đổi status native có phạm vi rộng và không có rollback transaction MyISAM.
2. Có cho Admin ITA sửa họ tên/email và reset mật khẩu native dùng chung không? Cần nêu tác động tới hệ thống cũ trong giao diện trước khi xác nhận.

## Bằng chứng cần xử lý trước code

- `dc_users` trong cấu trúc user cung cấp là MyISAM; native update không rollback cùng audit/permission InnoDB. Không hứa ghi audit/native luôn atomic và không tự giả định bù đắp thành công.
- `ItaAuthService`/`PmAuth` ưu tiên `password_hash` hiện đại, nhưng code legacy `classes/dao/users.class.php` còn nhánh xác thực MD5 và `modules/admin/system/staffedit.module.php` còn ghi `password` MD5. Nếu chỉ reset `password_hash` mà giữ MD5, mật khẩu cũ có thể vẫn dùng được trong consumer legacy; không tuyên bố reset thu hồi mọi mật khẩu cũ. Nếu ghi MD5 mật khẩu mới thì tăng rủi ro hash yếu và thay chính sách credential; nếu xóa MD5 có thể phá đăng nhập cũ. Phải xác minh consumer và chọn chính sách trước khi build reset.
- Cấu trúc native user cung cấp: username30, fullname50, email50; cần kiểm thực tế theo schema, trùng namespace username/email, collation, store, version và writer legacy đồng thời. Không lấy/trả password/hash trong JSON, audit hoặc log.
- Thu hồi ITA hiện kiểm quyền `access.manage`, revision, scope, CSRF và giữ người quản trị quyền cuối. Cần xác minh hành vi disable các tài khoản chưa có mapping, tài khoản chính mình, hồi phục và trạng thái UI; không coi hành vi này khóa hệ thống cũ.

## Kiểm dự kiến

Fake transport/browser local: vai trò/ủy quyền, ID ngoài store, CSRF, validate input, trùng, stale version, last manager, xác nhận trước thao tác, audit không bí mật, lỗi network/không retry ghi không rõ, và phân biệt ITA/native. Không mô phỏng MyISAM thành rollback nguyên tử.

Nghiệm thu DB/host chỉ khi user thực hiện hoặc cho phép riêng môi trường; source/fake/browser không thay xác nhận đó. Giữ backup, không dùng DELETE/DROP hoặc restore đè dữ liệu mới; không tạo DB test, kết nối DB, upload hay chạy SQL.
