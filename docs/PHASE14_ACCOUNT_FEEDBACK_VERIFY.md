# Thông báo và ràng buộc quản lý tài khoản — 29/10/2026

Phạm vi: toàn bộ quản lý tài khoản (tạo, phân quyền, sửa thông tin, đặt lại mật khẩu, xóa quyền ITA). Chỉ FE; không thay PHP/SQL/chính sách validation server, không DB/host/gói/upload.

- Trường bắt buộc, tên đăng nhập sai định dạng, email sai và mật khẩu không khớp có thông báo chỉ rõ trường/cách sửa. Native validation chặn gửi; aria-invalid được gỡ khi nhập lại.
- Đang tải/lưu có trạng thái chờ; hủy có thông báo chưa gửi thay đổi. Đóng form xóa ô mật khẩu. Hủy/rời view khi chờ xác nhận không gửi request ghi.
- Thành công/hủy/kết quả tìm kiếm tự ẩn sau 4 giây. Lỗi và kết quả ghi chưa rõ giữ để xử lý; không tự gửi lại yêu cầu ghi. Thông báo server về quyền, nguồn gốc, version, duplicate, limiter vẫn được hiển thị; không biến lỗi thành thành công.
- Sửa phần giới thiệu cũ nói không đổi mật khẩu để khớp chức năng reset có kiểm quyền/nguồn gốc đã duyệt.

## Kiểm thật

| Lệnh | Kết quả | Giới hạn |
|---|---|---|
| playwright-cli -s=ita-local-review run-code --filename=tests/ita_account_feedback_browser.js | 14 checks đạt | Native invalid/không POST, cancel, mismatch, 1366/768/390, không JS exception; Node RAM mock |
| playwright-cli -s=ita-local-review run-code --filename=tests/ita_user_profile_browser.js | 21 checks đạt | Reset/edit/reauth/503/navigation, giữ cảnh báo khi quay lại, mock |
| playwright-cli -s=ita-local-review run-code --filename=tests/ita_phase14_user_create_browser.js; tests/ita_access_race_browser.js | 28 +6 checks đạt | Hồi quy tạo tài khoản/phản hồi trễ, mock |
| playwright-cli -s=ita-local-review run-code --filename=tests/ita_access_remove_browser.js | 9 checks đạt | Xóa quyền/last manager/permission, mock |
| node --check assets/ita/js/ita-admin.js; php tests/ita_phase03_render.php | Đạt | Syntax/Smarty, không DB |
| Impeccable detect JS/template | 0 finding | Detector tĩnh, không chứng minh UX/DB |

Mở start-local-review.cmd và http://127.0.0.1:18767/#users để xem dữ liệu giả. Không nhập mật khẩu host. Chưa kiểm FPM/DB/host, chưa gói mới/push/deploy.
