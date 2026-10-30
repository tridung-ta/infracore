# BE-GATE #07-SCHEDULE — Lịch bảo trì ban đầu (đã duyệt)

User duyệt ngày 20/10/2026: **OK #07-SCHEDULE**. Cho phép sửa Phase 3–5 đúng phạm vi dưới đây, không DDL/DB/deploy.

Phạm vi: yêu cầu bổ sung số 6 của user. Khi chưa có last_maintenance_at, lấy purchase_date cộng maintenance_cycle_months (mặc định 6), chặn ngày cuối tháng. Schema không có ngày nhận riêng đã xác minh; không dùng created_at hoặc ngày bàn giao như ngày nhận.

Thay đổi dự kiến: service lịch dùng chung; gọi tại tạo/sửa tài sản Phase 3 và insert import Phase 4 để ghi next_maintenance_due, sửa projection/cảnh báo đọc Phase 5 để hồ sơ cũ thiếu ngày vẫn được tính từ ngày mua. Không tự ghi bù hàng loạt. Khi thiếu cả ngày mua và lần bảo trì, hiển thị chưa đủ dữ liệu thay vì bịa ngày.

Migration: không DDL, không SQL host, không thêm ngày nhận. Nếu cần ngày nhận thật thì gate SQL riêng. Chu kỳ thay đổi không viết lại hồ sơ lịch sử đã có ngày due được lưu; sửa ngày mua chỉ tính lại lịch ban đầu khi chưa có bảo trì và lịch chưa được ghi từ kết quả xử lý.

Rủi ro: lệch chu kỳ/ngày cuối tháng, ghi đè lịch đã chốt, thay đổi cảnh báo Phase 5. Test thuần và browser mock, không DB. Rollback source giữ các ngày đã ghi; user backup trước UAT/deploy. Không tạo DB hoặc tự triển khai.

Trả lời mong muốn: **OK #07-SCHEDULE / OK với chỉnh sửa / Không**. Trong lúc chờ tiếp tục ticket và các guard đã duyệt #07; chưa sửa writer/lịch Phase 3–5 cho cách tính ban đầu này.
