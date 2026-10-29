# Upload cập nhật InfraCore — 29/10/2026

Gói cập nhật cho **site InfraCore/Derasoft hiện có**, không phải bộ cài site trống. Source cố định `171edded9973dcee3a9d8e1f38eddfdcf8b03c30`. Kiểm local đạt; PHP8.3/FPM/DB/host chưa nghiệm thu. Agent không upload hoặc kết nối DB.

- ZIP: `artifacts/ita-host-20261029/infracore-update-phase14-171edde.zip`
- Dung lượng: **49.322.000 byte**.
- SHA256: `d4547bcb9a979d0f8827cd4aa96b3d5f6eb4f06844b780274d5fb27297ce8b76`.
- 1.417 file runtime, 105.093.451 byte trước nén; thêm MANIFEST.json và SHA256SUMS.txt, tổng 1.419 entry. Đã đọc lại ZIP thật kiểm allowlist/exclusions/path/case/link/CRC/size/hash/metadata; Get-FileHash độc lập khớp.
- Không config private, .env, SQL/dump, marker, .htaccess/.user.ini, MOCK/preview, tmp/tests/scripts/artifacts hoặc dữ liệu người dùng. Không tự bật flag, HSTS/auto_prepend hoặc chạy migration.

## 1. Trước upload

1. Chọn lúc không có người ghi dữ liệu. Backup toàn bộ source/config riêng, dump DB và khóa giấy phép **ngoài webroot**. Kiểm có thể lấy lại backup; không gửi config/dump/khóa lên chat.
2. Giữ native admin.php và nền Derasoft/Smarty đang dùng; gói có PmAuth/PmDb và module ITA đồng bộ. Không upload vào site khác dùng chung nền.
3. Xác minh PHP-FPM8.3.x/extensions/dependency/font, HTTPS/chứng chỉ, display_errors Off/log_errors On. Local8.5 còn deprecation vendor (33/37 dòng diagnostic của hai bộ export), không chứng minh FPM8.3 không lỗi.
4. Giữ DB/auth/license config, .ita-enabled và flag capabilities hiện có. User đã cung cấp ảnh SQL007/backfill47quyền/revision2: không chạy lại CREATE/backfill hoặc tăng quyền để chữa lỗi. Gói không có SQL mới.
5. Quyền runtime phải khớp chức năng: tạo user cần INSERT dc_users; sửa thông tin cần UPDATE username/fullname/email; reset cần UPDATE password/password_hash; login cần UPDATE last_login/password_hash. Đúng store/schema, không cấp DDL/DELETE toàn schema. Agent chưa kiểm grants của app, không dùng SHOW GRANTS phpMyAdmin thay bằng chứng.

## 2. Kiểm và upload

```powershell
Get-FileHash -Algorithm SHA256 -LiteralPath '.\infracore-update-phase14-171edde.zip'
```

Hash phải khớp trên. Giải nén **trên máy** vào thư mục mới. FileZilla bên phải chọn document root InfraCore đã xác minh, bên trái mở **bên trong runtime/**. Upload các mục bên trong runtime vào root, gộp và giữ đường dẫn; chỉ ghi đè file thuộc gói. Không mirror/delete file khác.

**Không upload thư mục runtime nguyên khối, ZIP, MANIFEST.json, SHA256SUMS.txt, report/checksum/hướng dẫn.** Không copy ita_database.local.php hoặc toàn project. Chờ hàng đợi hết, xử lý mọi truyền lỗi rồi Ctrl+F5; cache/opcache chỉ làm mới bằng cơ chế host được phép.

## 3. Smoke test sau upload — dữ liệu giả

1. HTTPS hợp lệ, Set-Cookie HttpOnly/Secure/SameSite, header cuối/CORS/no-store/display_errors theo PHASE11_HOST_TEST. Không dùng phpinfo public để chẩn đoán.
2. Tài khoản test login đúng/sai; sai trả401 cùng thông báo, không503. Login đúng ghi last_login và có thể nâng password_hash, giữ MD5 theo chính sách login. Không thử credential vận hành của người khác.
3. Dashboard/alerts/report tải được; kiểm tài sản/import/phân bổ/ticket/license/lifecycle/forecast, xuất một Excel/PDF giả và mở lại. Kiểm nhóm quyền mở không kéo giãn khung bên cạnh, thông báo sai trường/cancel/success.
4. Admin có access.manage +scope.store tạo tài khoản giả mới, cấp đúng chức năng/bộ phận, đăng nhập ở phiên riêng; truy cập trái quyền/scope bị403. Không thay tài khoản vận hành/thu hồi quản lý quyền cuối.
5. Chỉ tài khoản staff do InfraCore tạo, có audit user.create cùng store/entity được sửa/reset. Tài khoản cũ chỉ map ITA bị403 là đúng. Không thêm audit nguồn gốc giả để vượt chặn.
6. Reset cần mật khẩu quản trị xác nhận, password mới6ký tự/tối đa72byte, khác modern/MD5 cũ, nhập lại trùng. Sau reset password cũ hết hiệu lực, phiên ITA bị yêu cầu login lại; legacy MD5-only có thể mất đăng nhập, phiên legacy **không tự đóng**. Chỉ thử trên tài khoản giả đã được phép.
7. Xóa quyền ITA chỉ ngừng truy cập InfraCore, giữ tài khoản nền/history/scope/permissions. Kiểm audit theo thao tác; không DELETE native.

Native MyISAM không rollback cùng audit/revision InnoDB. Nếu503 hoặc mất phản hồi sau ghi: **không bấm gửi lại**, kiểm tài khoản và nhật ký trước. Không stress/race/revoke grants trên host. Ghi kết quả “kiểm thủ công trên host, phạm vi hạn chế”; không tự nghiệm thu toàn DB.

## 4. Hoàn tác

Ngừng ghi, khôi phục đúng các file source/vendor đã thay từ backup. Giữ config/key/DB/audit/tài khoản và dữ liệu mới; không restore dump đè hoặc DROP/DELETE. Rollback source không phục hồi password cũ, không thu hồi tài khoản mới hoặc đảo quyền đã lưu. Cần kiểm trạng thái riêng qua quản trị được phép; không dùng SQL credential reset làm lối tắt. Chưa thực hành rollback host trong lượt này.
