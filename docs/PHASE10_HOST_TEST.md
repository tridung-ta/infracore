# Kiểm thử host sau khi user triển khai dashboard và báo cáo

Trạng thái: **chưa chạy**. Agent đã tự kiểm local; tài liệu này dành cho user tự kiểm sau upload, không yêu cầu tạo DB test. Không chạy thử phá dữ liệu trên production. Chưa có gói/commit đang chạy host được xác nhận.

## Trước triển khai

- Xác nhận môi trường, backup source/DB/khóa ngoài Git theo DEPLOY_LOG. Không restore đè dữ liệu mới khi rollback.
- Dùng source cố định gồm đủ các commit F10.1–F10.7 trong PHASE10_VERIFY.md; không chỉ upload JS/template. Có thay đổi application/bootstrap/outer POST allowlist/audit service và helper snapshot metadata.
- Không có migration/seed/DDL mới. Kiểm PHP version, GD/mbstring, PDF vendor/font và Excel library đang có, ECharts6.1.0 local đủ JS/LICENSE/NOTICE/SOURCE. Không chạy Composer trên host.
- display_errors Off khi tải file, log_errors On; auth_key và ITA_LICENSE_KEY vẫn private ngoài Git. Report HMAC dùng auth_key hiện có, không cần tạo khóa báo cáo mới.
- Transaction isolation cần RR hoặc SERIALIZABLE. Nếu khác, ứng dụng trả503; không đổi isolation toàn server như cách chữa nhanh.

## Dashboard và quyền

1. Admin mở /admin.php?op=ita#overview, đối chiếu số tài sản/tổng từng trạng thái bằng dữ liệu host. “Đang quản lý” loại retired; tỷ lệ lỗi có mẫu số phần cứng riêng.
2. Đổi loại/bộ phận/năm mua; chart/bảng phải cùng số liệu và mốc giờ VN. Không tự refresh khi để trang mở.
3. Quản lý chỉ bộ phận phụ trách. Request department_id ngoài scope trả403. Hỗ trợ chỉ bảng bảo trì; gọi trực tiếp dashboard/alerts/lifecycle/budget/report-export bị403.
4. Cảnh báo có nhiều dòng khi một đối tượng có nhiều lý do. Lọc mức độ/lý do/tìm tên làm đổi số khớp, không biến số chuông thành số chưa đọc. License không có allocation được xác minh trong scope không lộ qua cảnh báo Quản lý.
5. Bảo trì lấy lịch effective: last maintenance hoặc ngày mua + chu kỳ; trường thiếu phải thể hiện thiếu. Vòng đời ngắn dùng12 tháng lịch và có điểm/tier, không đồng nghĩa danh sách điểm≥ngưỡng thay thế.

## Báo cáo và file

1. Mở #reports. Preview đủ bốn loại, phân trang đúng, source hiện tại/snapshot rõ. Snapshot ID phải đúng năm/bộ phận đã chọn và quyền hiện tại.
2. Excel tồn kho:5.000 được phép về quy tắc,5.001 từ chối; Excel khác1.000/1.001; mọi PDF500/501. Có thể cần thu hẹp hơn do memory/runtime host; từ chối rõ, không cắt.
3. Mở XLSX kiểm văn bản bắt đầu =,+,-,@ không thành công thức. PDF hiển thị tiếng Việt, đủ cột/trang. MIME và bytes không bị cảnh báo PHP/HTML đăng nhập chèn vào.
4. Preview rồi thay dữ liệu ở phiên khác: export trả409 nếu document đổi; preview quá15 phút cũng yêu cầu tải lại. Snapshot ngân sách không đổi khi sửa giá hiện tại.
5. Kiểm CSRF sai/mất bị từ chối. Native account/role/scope bị thu hồi trong lúc render không được nhận file. Chỉ kiểm gây lỗi audit/rollback/revocation trên môi trường phù hợp đã xác nhận; local double không thay bước này.
6. Sau thành công có audit report.export. Lỗi audit không trả bytes file; lỗi mạng không tự gửi lại POST.

## EXPLAIN, nghiệm thu và rollback

PHASE10_EXPLAIN.sql có6 truy vấn source cho Admin/Quản lý, store1/scope10 chỉ là ví dụ giả; thay bằng ID đúng môi trường. Budget tái sử dụng PHASE09_EXPLAIN.sql. Không có CREATE/ALTER/INSERT trong file EXPLAIN. Nếu cần index mới, dừng và gửi gate SQL riêng.

Ghi rõ môi trường/phiên bản DB, commit đã upload, kết quả từng bước, CHECK ticket_events/rollback InnoDB/hai phiên đồng thời và mục chưa kiểm. Chỉ cập nhật PROGRESS khi user cung cấp xác nhận cụ thể. Phase1–10 hiện chưa nghiệm thu DB; không merge/tag7–9.

Rollback source về bộ trước #10 đã backup, giữ nguyên audit/config/snapshot/dữ liệu mới; không DROP hoặc restore đè DB. Chưa có xác nhận đã thực hiện rollback host.
