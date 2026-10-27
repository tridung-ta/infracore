# Nhật ký và bảng nhóm dễ đọc — 27/10/2026

Phần FE theo yêu cầu user: nhật ký có thao tác theo nhóm, người thực hiện chọn tên từ users API hiện có và Tải thêm nếu nhiều tài khoản; ngày nhanh Hôm nay/7ngày/30ngày/mọi thời điểm; tìm/xóa bộ lọc rõ ràng. Không lọc giả riêng20 dòng trang hiện tại: live gửi action/user_id/from/to cho server như cũ.

Tên người chỉ được gắn khi có dữ liệu tra cứu; ID chưa có trong trang users đã tải vẫn hiển thị “Tài khoản #ID”, không đoán tên. Users API hiện có vẫn Admin-only cho đến BE14. Chọn Tải thêm tải trang users tiếp theo; không gọi lookup riêng từng log. Không đọc private config/DB/host.

Thao tác và loại đối tượng có nhãn tiếng Việt. Before/after hiện bảng Nội dung/Trước/Sau chỉ cho trường thay đổi; sự kiện null/null không mở JSON rỗng. Render textContent, không innerHTML; key/password/token được bỏ khỏi phần trình bày phòng vệ, dữ liệu audit gốc không bị sửa. Giá trị quá1000 ký tự được ghi rõ rút gọn. JSON lỗi chỉ ảnh hưởng chi tiết bản ghi đó.

Bảng vòng đời đổi “Thiết bị” thành **Số thiết bị**, tách Có tuổi hợp lệ/Thiếu tuổi, nhãn nhóm theo loại/bộ phận và cột tỷ lệ Có sửa chữa/Tổng số. Nút Xem N thiết bị áp dụng đúng filter hiện có, về trang1 và mở vùng bộ lọc; không đổi công thức/KPI/scope/API. Nhóm null không có ID filter được giải thích và khóa nút, vì API hiện không có filter “chưa phân bổ”; không giả danh sách hiện tại là đủ toàn nhóm.

Mock local: http://127.0.0.1:18766/#audit và /#users, /#lifecycle. Users có ví dụ A=1/2/3, B=4/5/6, C=1/2/6 bằng checkbox, không POST/persistence/cấp quyền thật. Catalogue mock còn là bản thảo; BUILD BE14 đã được user duyệt riêng và đang làm, chưa mô tả checkbox này là phân quyền server hoàn tất.

| Lệnh | Kết quả thật | Giới hạn |
|---|---|---|
| playwright-cli -s=ita-admin-refine run-code --filename=tests/ita_admin_readability_browser.js |26checks đạt|Audit/mock permission/group drilldown, bounds1366/768/390, không POST|
| Cùng lệnh, ita_phase11_admin_browser.js |14checks đạt|Layout admin/audit/login, API mock, không auth/access POST|
| Cùng lệnh, ita_phase11_lifecycle_groups_browser.js |30checks đạt|Giữ paging20/denominator/scopeUI theo fixtures, không thêm HTTP khi chỉ phân trang nhóm|
| node --check JS mới/sửa; php tests/ita_phase03_render.php |Đạt|Syntax/Smarty dữ liệu giả, không DB|

Test mới ban đầu đếm cả tr trong bảng chi tiết lồng nhau, gây chờ sai sau lọc; sửa selector thành `#audit-rows > tr`, giữ assertion số bản ghi cấp ngoài. Không nới assertion quyền/CSRF/version/reauth/audit. Chưa host/PHP8.3/DB; chưa gói mới/upload/push/merge/tag7–10.

Review độc lập phát hiện formatter dịch nhầm tên/ghi chú trùng chuỗi enum (ví dụ active). Đã giới hạn dịch vào trường role/status, tra map bằng Object.hasOwn, giữ dữ liệu tự do nguyên văn. `ita_audit_formatter_browser.js`:8checks đạt, gồm nested secret/malformed JSON/XSS/prototype name. Script permissions-preview đang chỉ MOCK và phải được thay bằng editor production trong BE14 trước đóng gói live; chưa tạo gói mới.
