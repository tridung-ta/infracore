# Tiếp tục BUILD và kiểm thử — 29/10/2026

User yêu cầu ưu tiên hoàn thành 10 nhóm chức năng đã thống nhất, hoãn cron/email và cải tiến ngoài phạm vi. Không tạo/kết nối DB, không đọc private config hoặc thao tác host trong lượt này. Working tree có thay đổi đã làm ở lượt trước và các thay đổi riêng có sẵn; không xóa hoặc ghi đè chúng.

## Trạng thái chức năng

Có source cho 10 nhóm tại PHASE00_14_REQUIREMENTS_REVIEW.md; báo cáo đó là checkpoint 28/10, các dòng SQL007/flag chưa chạy là thông tin cũ. User đã cung cấp ảnh SQL007 có 47 quyền Admin sau COMMIT, ảnh `ita_ticket_events` InnoDB + CHECK, thông báo lưu quyền và xác nhận nút Thêm tài khoản đã hiện sau khi tự bật flag. Đây là bằng chứng host hạn chế do user cung cấp; chưa chứng minh runtime grants, tạo tài khoản thành công, enforcement CHECK hoặc các luồng/concurrency toàn DB.

Các thay đổi đang kiểm: mật khẩu mới tối thiểu 6 ký tự/72 byte tối đa, tìm tài khoản theo tên/trạng thái/bộ phận, phản hồi lỗi theo trường và 4 giây cho thông báo thành công; chi tiết UX_REVIEW_20261029. Không thay các công thức, vai trò hoặc dữ liệu host.

## Kết quả thực tế

Chạy qua PHP CLI 8.5.10, fake transport/pure trừ khi ghi khác:

| Nhóm | Kết quả |
|---|---|
|Phase03–08 unit|39 / 88 / 203 / 159 / 237 / 72 checks; Phase04 có XLSX thật, test cũ lọc E_DEPRECATED |
|Phase09–10 unit|134 / 52 checks |
|S11 export guards|30 / 54 checks, renderer/storage giả |
|Phase13 / Phase14 permissions / create account|179 / 121 / 139 checks |
|Lọc tài khoản|13 query contract checks; không chạy SQL server hoặc EXPLAIN |
|Auth/limiter, S26, write routes|29 / 716 / 13 checks; S26 bổ sung đăng nhập đúng/sai với password 6 ký tự, không đo hằng thời gian |
|Smarty|ita_phase03_render.php đạt, dữ liệu giả |
|HTTP fixture|privacy 102, error 56, guard 109; không Apache/FPM/host |
|SQL static|Phase13 57 checks; Phase14 88 role/key pairs; không thực thi SQL |
|ZIP validator|66 checks ZIP tổng hợp trong bộ nhớ; không tạo gói ứng dụng |
|Excel/PDF|ita_phase09_exports.php và ita_phase10_exports.php exit 0; log lần lượt 33/37 dòng khớp Deprecated trên PHP8.5, là số dòng chứ không phải số sự kiện duy nhất |
|Browser local|28 checks tạo tài khoản, gồm 5 ký tự Unicode bị từ chối, 6 ký tự được form chấp nhận và tạo một lần trong RAM; không tạo tài khoản thật |

Các lệnh và log synthetic `tmp/build-*.log`; S26 được chạy lại sau bổ sung ca 6 ký tự (716 checks), log ban đầu vẫn ghi 686. Lượt browser/UI rộng trước đó ghi tại UX_REVIEW_20261029. Chưa chạy PHP 8.3/FPM, SQL/MariaDB, transaction/isolation/concurrency/EXPLAIN, tải lớn hay lưu thực trên host. Không dùng kết quả mock để đánh dấu các phase đã nghiệm thu.

## Bước tiếp theo

User kiểm bản local `start-local-review.cmd`. Các thay đổi source hiện chưa có trong gói cũ; gói mới chỉ làm khi user yêu cầu. Khi user thực hiện host test, ghi kết quả theo từng mục và nhãn kiểm thủ công phạm vi hạn chế; lưu ý native `dc_users` là MyISAM, host có username 30/email 50/fullname 50 ký tự theo cấu trúc đã cung cấp và chưa xác nhận grants ứng dụng. Không tự merge/tag 7–10, push hoặc deploy.
