# Một lượt upload phần sửa đã chốt — 30/10/2026

Gói delta cuối thay cho các delta ngày29/10. Nền yêu cầu **a7c2da1 đã có trên host**; nếu đã upload delta171edde thì vẫn dùng được gói này vì gom các file cần thiết tới source **6a9d1eb**. Không cần upload nhiều gói nối tiếp. Không phải bộ cài site trống; baseline host toàn bộ chưa được xác minh bằng inventory.

Phạm vi đã build: quản lý tài khoản có kiểm nguồn gốc/quyền, CAS bảo vệ reset; thông báo và ràng buộc; khung nhóm quyền độc lập; danh sách máy tự tải khi vào Phân bổ/Thu hồi nhưng không tự chọn/mở hồ sơ; iconmenu khác nhau; Focus khi tắt hiệu ứng. Không thêm chức năng/SQL ngoài gate đã duyệt.

## Upload một lượt

ZIP: `artifacts/ita-host-20261030/infracore-delta-final-20261030-a7c2da1-to-6a9d1eb.zip` (**80.865byte**). SHA256 `437913a471e3dabd05a956ba2fffe7346a75ac71591e023e379691a23c4d6acd`.

1. Chọn lúc không ai ghi dữ liệu. Backup file source sẽ thay, config riêng, DB và khóa giấy phép ngoài webroot; kiểm lấy lại được backup. Không gửi credential/dump lên chat.
2. Tải ZIP cuối được dẫn trong báo cáo FINAL_BUILD_VERIFY_20261030.md; kiểm SHA256 theo báo cáo rồi giải nén **trên máy**.
3. FileZilla chọn đúng document root InfraCore ở bên phải. Bên trái mở **bên trong runtime/**, upload tất cả27file trong cây đó, giữ đường dẫn và gộp thư mục. Chỉ ghi đè file thuộc gói; không mirror/delete.
4. **Không upload nguyên runtime/, ZIP, MANIFEST.json, SHA256SUMS.txt hoặc tài liệu/checksum/report.** Giữ ita_database.local.php, key/cờ/marker và dependency hiện có. Không chạy migration/backfill/SQL mới; không tự đổi grants.
5. Đợi hàng đợi truyền hết và không còn lỗi, Ctrl+F5. Cache/opcache chỉ làm mới bằng cơ chế hosting được phép.

## Kiểm nhanh sau upload

- HTTPS/chứng chỉ, cookie HttpOnly/Secure/SameSite, header/CORS/no-store/display_errors cuối đúng checklist host. PHP8.3/FPM/extensions/grants thực chưa được test local8.5 thay thế.
- Mở Phân bổ/Thu hồi: danh sách mã/tên tự tải, chọn máy bằng mũi tên, Trước/Sau/tìm kiếm hoạt động, chưa chọn thì chưa mở hồ sơ. Chọn hồ sơ giả, kiểm chức năng đúng quyền.
- Kiểm iconmenu khôngtrùng; mở một nhóm quyền không kéo cao khung đóng; thông báo có tiêu đề/nút đóng; thành công4giây/lỗi giữ. Tắt hiệu ứng chuyển Focus, menu/form/table/mạng vẫn đọc rõ.
- Chỉ dùng tài khoản giả: tạo/cấp đúng chức năng+scope, phiên riêng kiểm trái quyền403; sửa/reset chỉ staff có audit user.create nguồn gốc InfraCore. Tài khoản cũ chỉ map ITA bị403 là đúng. Không giả audit nguồn gốc để vượt chặn.
- Reset cần reauthAdmin, password6ký tự/tối đa72byte/confirmkhớp/khácmodern+MD5cũ. MD5-onlylegacy có thể mất đăng nhập; phiênlegacy khôngtựđóng. Login thành công ghi last_login và có thể nâng password_hash. Không thử tài khoản vận hành khác.
- Dashboard/alerts/report không503; tải Excel/PDF dữ liệu giả và mở lại; kiểm audit thao tác. Không stress/race/revokegrants hoặc ghiSQL để testhost.

MyISAM không atomic với audit/revisionInnoDB. Nếu503/mấtphảnhồi saughi, không bấm gửi lại: kiểm tài khoản/nhật ký trước. Xóa quyềnITA khôngxóanative/history. Ghi “kiểm thủ công trên host, phạm vi hạn chế”; chưa tự nghiệm thu DB/host hoặc merge/tag7–10.

## Rollback

Ngừng ghi, khôi phục đúng file đã thay từ backup. Giữ DB/audit/account/password/quyền/dữ liệu mới; không DROP/DELETE/restore dump đè. Rollback source không đảo password hoặc quyền đã ghi. Agent chưa upload/push/deploy.
