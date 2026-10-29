# Phase 14 — Bảo vệ tài khoản, 29/10/2026

Chỉ source/test local; không kết nối DB, không đọc cấu hình riêng, không sửa tài khoản host, không tạo gói/upload/push/merge/tag.

## Hành vi và giới hạn

- Sửa/reset cần access.manage +scope.store, tài khoản nhân viên type=1 có mapping ITA và audit user.create cùng store/entity. Tài khoản cũ chỉ được map ITA bị 403; không tạo audit nguồn gốc giả để vượt chặn.
- Reset cần xác thực lại mật khẩu quản trị có limiter, version, CSRF. Mật khẩu mới tối thiểu 6 ký tự/tối đa 72 byte, xác nhận trùng, không rỗng/NUL; phải khác cả modern password và MD5 cũ.
- Ghi hash PASSWORD_DEFAULT cùng placeholder ngẫu nhiên thay MD5 cũ, tăng auth_revision. CAS ở ITA/PmAuth chặn login cũ ghi đè credential đã đổi. Revision đạt giới hạn bị chặn trước native write; kiểm affected rows sau tăng revision.
- DTO/audit không chứa credential. Hủy xác nhận/rời form không POST, xóa các ô mật khẩu. Kết quả 503 chưa rõ khóa thử lại; quản trị phải đối chiếu tài khoản và nhật ký.
- Xóa quyền ITA giữ native, scope, permission và lịch sử; bảo vệ người quản lý quyền cuối.
- Native MyISAM không atomic với audit/revision InnoDB; lỗi sau native write có thể đã đổi credential. Legacy MD5-only có thể mất đăng nhập; phiên legacy không tự đóng, các writer legacy khác chưa được tuần tự hóa toàn bộ. Không tuyên bố nghiệm thu DB/host hoặc hằng thời gian.

## Kết quả thật trên PHP CLI 8.5.10

| Bộ kiểm | Kết quả | Phạm vi |
|---|---|---|
| php tests/ita_user_profile.php | 77 checks đạt | Service thật, transport giả: nguồn gốc, quyền, validation, CAS, audit fault, revision giới hạn, MyISAM giả |
| php tests/ita_password_reset_race.php | 13 checks đạt | PmAuth thật, race điều khiển bằng fake transport; không DB concurrency |
| php tests/ita_s26_auth.php | 756 checks đạt | Counter verifier/downstream, hồi quy login/session/CSRF; không đo thời gian |
| php tests/ita_preupload_auth.php | 29 checks đạt | Auth/session/limiter giả |
| php tests/ita_write_routes.php | 16 checks đạt | Method/CSRF route contract |
| php tests/ita_phase03_render.php | Đạt | Smarty thật, dữ liệu giả |
| playwright-cli -s=ita-local-review run-code --filename=tests/ita_user_profile_browser.js | 20 checks đạt | Node RAM mock, nguồn gốc, 1366/768/390, hủy/rời form, 401/503, không giữ plaintext |
| playwright-cli -s=ita-local-review run-code --filename=tests/ita_access_remove_browser.js | 9 checks đạt | Node RAM mock, xóa quyền/last manager/permission |

Chưa PHP8.3/FPM/DB/host. Browser không chứng minh persist credential; bộ service kiểm riêng bằng transport giả. Audit nguồn gốc không chứng minh tài khoản chưa được cấp quyền ở ứng dụng cũ sau khi tạo; vẫn phải xác nhận tác động dùng chung.

## Tự xem local

Chạy start-local-review.cmd, mở http://127.0.0.1:18767/#users. MOCK-C là tài khoản giả có nguồn gốc tạo trong mô hình; các tài khoản giả cũ không có nguồn gốc bị chặn sửa/reset. Mật khẩu quản trị **chỉ trong MOCK** là MOCK-ONLY. Không nhập credential host vào preview. Thử hủy xác nhận, đặt lại, rời form và xóa quyền ITA; không có DB thật được sửa.
