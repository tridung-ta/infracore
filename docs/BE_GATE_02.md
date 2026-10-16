# BE-GATE 02 — Đăng nhập và phân quyền

User đã yêu cầu rõ «sau khi rà soát giao diện rồi bắt đầu BUILD bước này», sau đó chọn tài khoản 111 làm Admin IT đầu tiên. Đây là quyền triển khai Phase 2 local; SQL host do user chạy, không phải quyền deploy hay kết nối host tự động.

Phạm vi: đăng nhập tái sử dụng credential dc_users; session ITA riêng, ba vai trò, scope bộ phận/store; Admin quản lý mapping tài khoản hiện có; audit đăng nhập/đăng xuất/đổi quyền, màn hình lọc log; CSRF và rate limit DB. API đọc tài sản và thống kê có scope phục vụ kiểm chứng dữ liệu thật. CRUD tài sản Phase 3 chưa triển khai.

Migration 005 chỉ thêm ita_user_roles, ita_user_scopes, ita_login_limits. Bootstrap riêng cho store 1/user 111, chỉ khi native status=1 và chưa có Admin IT enabled. Không cấp quyền kế thừa từ PM. Không sửa schema native. Đăng nhập thành công có thể ghi password_hash/last_login theo cơ chế native; dc_users MyISAM nên không rollback cùng transaction mapping InnoDB.

Backup: user xuất toàn bộ dung_infracore ra ngoài repo trước SQL và cutover. DDL có implicit commit; nếu import một phần, gửi kết quả trước khi chạy tiếp, không DROP/reimport. Hoàn tác bằng ngừng phục vụ runtime Phase 2 và giữ nguyên bảng/mapping/log, phục hồi source/config từ backup có kiểm soát; không tự bật legacy hoặc phục hồi dump ghi đè dữ liệu mới. Không xóa hash mới sau khi nâng cấp credential.
