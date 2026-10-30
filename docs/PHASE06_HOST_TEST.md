# Giấy phép — kiểm tra host sau khi user sẵn sàng

Chưa deploy, không chạy SQL hoặc tạo DB. Không chạy lại migration 003. Phase 1–6 chưa được kiểm chứng đầy đủ trên DB thật trong đợt build này; bắt buộc hoàn tất kiểm thử DB trước Phase 8. Ảnh xác nhận đăng nhập/schema trước đây không thay kiểm thử transaction, scope và concurrency.

## Khóa mã hóa

- Dùng khóa ngẫu nhiên đúng 32 byte, encode base64; không dùng chuỗi tự nghĩ, auth_key, mật khẩu user hoặc khóa dev.
- Tạo trên máy quản trị tin cậy: `php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"`. Đây là lệnh tạo secret, không đưa output vào chat, Git, ảnh chụp, log hoặc tài liệu.
- Production đặt biến môi trường `ITA_LICENSE_KEY` cho đúng PHP-FPM pool/site bằng cấu hình hosting. File cấu hình secret do quản trị giữ ở ngoài document root, ngoài Git. Không đưa vào `.htaccess`, JS hoặc file runtime công khai. Nếu hosting không cho thiết lập môi trường, báo lại để chuẩn bị cách nạp private file riêng; không hard-code vào source.
- Backup khóa vào kho secret riêng với phân quyền hạn chế; tách khỏi dump DB. Backup DB không đủ để giải mã. Không đổi khóa khi đã có ciphertext nếu chưa có quy trình chuyển khóa được duyệt.
- Dev chỉ dùng khóa test ngẫu nhiên riêng cho dữ liệu giả; test PHP tự tạo trong process và không lưu khóa ra file.
- Trước deploy, chạy `php scripts/check_ita_license.php` trong môi trường CLI được cấp cùng biến môi trường PHP-FPM. Script chỉ kiểm OpenSSL/AES-256-GCM/khóa/round trip, không đọc config DB, không kết nối DB và không in secret. CLI đạt chưa chứng minh PHP-FPM đã nhận biến; cần UAT tạo/sửa/xem key bằng dữ liệu kiểm thử được user duyệt trên host.

## Ma trận thao tác

1. Admin: tạo giấy phép theo máy/người/bộ phận, ngày/chi phí sai bị chặn; tạo/sửa và đọc lại. Trống key khi sửa giữ key cũ. Ngừng sử dụng thay xóa. Không đổi loại khi có lịch sử.
2. Quản lý: xem catalog chung (metadata, số chỗ, key che); chỉ cấp/thu hồi máy hoặc bộ phận trong scope. Sửa metadata dùng chung và cấp theo user chỉ Admin vì chưa có quan hệ user–bộ phận được xác minh. Hỗ trợ: API giấy phép trả 403.
3. Kiểm cả URL/ID khác store, máy ngoài bộ phận, asset deleted/retired, bộ phận inactive, user inactive. Thu hồi vẫn cho đối tượng đã inactive/deleted nếu quyền hiện tại hợp lệ.
4. Hai phiên cùng cấp vào chỗ cuối/cùng đích: không tạo bản ghi trùng; manager không vượt mức; Admin cần checkbox và lý do. Giảm tổng chỗ dưới số đã cấp cũng cần xác nhận/lý do; audit `license.overage` có actor và lý do, không key.
5. Cảnh báo theo ngày VN: 31 ngày bình thường, 30/8 warning, 7/0/quá hạn critical; vượt mức chỉ khi used > total. Không có hạn không phát cảnh báo hết hạn. Kiểm ngày hết hạn được sử dụng hết ngày đó.
6. Xem key: chỉ POST+CSRF Admin, nhập lại mật khẩu. GET/thiếu CSRF/role khác bị chặn. Mọi phản hồi có no-store. Quá 5 lần/15 phút trả 429 (đếm cả lần thành công); kiểm bucket dùng chung nhiều phiên.
7. Key tự xóa khỏi DOM sau 10 giây, chuyển tab/rời trang/ẩn ngay; không có trong HTML nguồn, storage, log hoặc DTO list/detail. Mật khẩu xác nhận không ghi lại last_login/hash. Đổi role/revision giữa lúc xác nhận phải chặn.
8. Mô phỏng audit lỗi trên môi trường kiểm thử được kiểm soát: thao tác ghi rollback, key không trả về. Không tự gây lỗi schema trên production. Ciphertext sửa/sai khóa bị chặn; giữ khóa và dữ liệu cũ để phục hồi.
9. Kiểm InnoDB rollback thật, generated unique indexes, prepared SQL/EXPLAIN và tải lớn theo store. Đối chiếu used_seats bằng COUNT chỉ released_at IS NULL. History có phân trang 20 dòng và scope.

Rollback: trả source cũ từ backup, giữ DB/ciphertext/lịch sử/audit; không DROP hoặc restore dump đè dữ liệu mới. Chưa có migration mới.
