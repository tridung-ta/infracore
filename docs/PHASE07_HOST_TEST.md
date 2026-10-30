# Bảo trì và ticket — UAT DB Phase 1–7 trước merge

**Chưa merge Phase 7, chưa deploy, chưa chạy DB thật.** User tự chọn thời điểm triển khai/UAT; agent không tạo DB hoặc kết nối host. Không dùng gói PDF cũ cho thay đổi ticket. Không có migration mới, không chạy lại CREATE.

## Trước kiểm thử

- User backup source và DB riêng, giữ cấu hình/khóa giấy phép ngoài Git; dùng tài khoản và thiết bị kiểm thử được duyệt, tránh dữ liệu vận hành. Chuẩn bị đủ ba vai trò và ít nhất hai bộ phận; không gửi password/key vào chat.
- Ảnh host cũ báo 10.6.28-MariaDB. Kiểm tra lại chỉ đọc: `SELECT VERSION(); SHOW VARIABLES LIKE 'check_constraint_checks';`. Agent chưa xác minh CHECK trên DB thật. Application phải chặn transition sai ngay cả khi CHECK không chạy.
- Hoàn tất checklist DB các phần trước: auth/revision/rate, asset scope/version/audit/soft delete, import rollback/trùng/500 dòng, assignment/PDF/ack, license encryption/overage/reauth/audit. Mock local không thay các bước này.

## Ticket và quyền

1. Ba vai trò tạo phiếu cho thiết bị trong scope; ngoài scope 403, ID khác store không truy cập được. Thiết bị retired/deleted không tạo/mở lại. Chỉ Admin/Manager gán kỹ thuật viên; mục chọn phải active và có scope với thiết bị, Hỗ trợ không gán lại qua API trực tiếp.
2. Chỉ pending → resolved/failed, failed → pending. Resolved không mở lại. Thiếu lý do, ngày trước ngày mở/sau hôm nay, số tiền âm/quá giới hạn đều bị chặn; đối chiếu payload và dữ liệu đọc lại.
3. Hai phiên cùng tạo cho một asset hoặc mở lại failed cạnh phiếu pending: chỉ một pending; phía còn lại báo lỗi rõ. Có dữ liệu pending trùng từ trước thì dừng đối chiếu, không xóa để vượt guard.
4. Mở/reopen đưa asset về maintenance; failed đưa broken; resolved có user/lượt giao về in_use. Resolved chưa phân bổ giữ maintenance và hiện cảnh báo chờ Admin. Chỉ Admin xác nhận sẵn sàng kèm lý do; Hỗ trợ không lách bằng trang đổi trạng thái tài sản.
5. Khi pending, thử giao mới, thanh lý, xóa mềm, đổi bộ phận và đổi trạng thái rời maintenance từ URL/API các phần cũ: bị chặn. Thử thu hồi bàn giao: vẫn cho phép. Thử asset đang in_use nhưng có pending từ dữ liệu lệch: giao mới vẫn bị chặn.
6. Phiếu resolved/failed: chỉ Admin sửa cost, có lý do/audit. Hỗ trợ mở lại failed không được đổi chi phí đã chốt qua lần đóng tiếp theo. Cho Admin sửa chi phí phiếu cũ khi asset có phiếu pending khác hoặc đã retired; không thay trạng thái asset. Sửa chi phí failed cũ không được làm mất cảnh báo chờ xác nhận của phiếu resolved mới.
7. Lịch sử trong chi tiết asset: chọn phiếu, đối chiếu event trạng thái và audit gán kỹ thuật viên/sửa cost. Gán không tạo event pending → pending. Thử nhiều hơn 20 phiếu/sự kiện để kiểm phân trang, quyền và thứ tự.

## Lịch và thống kê

8. Không có last_maintenance_at: ngày mua + chu kỳ loại (null mặc định 6 tháng), không có ngày mua thì chưa đủ dữ liệu. Ví dụ 31/08/2023 +6 =29/02/2024; 31/08/2024 +6 =28/02/2025. Sửa ngày mua/loại khi chưa bảo trì tính lại lịch ban đầu; đã có last_maintenance_at thì giữ lịch đã lưu. Import chỉ insert, không cập nhật tài sản trùng. Hồ sơ cũ thiếu due được tính khi đọc, không có backfill hàng loạt.
9. Periodic resolved cập nhật last/due theo handled_date VN, dữ liệu last lưu UTC đầu ngày VN; repair/failed/reopen không ghi là bảo trì định kỳ thành công. Đổi chu kỳ danh mục không ghi lại các lịch đã lưu. Đối chiếu badge danh sách, bàn giao và chuông; kiểm sát 00:00 VN.
10. Thống kê nhóm theo thiết bị/type/status và năm opened_at VN, đếm ID phiếu, không event; type=repair là đầu vào số ticket sửa chữa Phase 8. Reopen không tăng số phiếu. Chi phí là tổng hiện tại mỗi phiếu, không nhân theo số event. Hỗ trợ chỉ xem thống kê phiếu được gán cho mình trong scope.

## Giao dịch và nghiệm thu

11. Kiểm InnoDB, SQL thật, EXPLAIN với dữ liệu đủ lớn, khóa cùng thứ tự actor → asset → ticket, pending guard trên mọi writer. Với insert tài sản mới chưa có row để khóa, unique asset_code bảo vệ và không upsert. Kiểm deadlock/timeout trả 409 và không nhân đôi event.
12. Trên môi trường kiểm thử được kiểm soát, mô phỏng lỗi audit: ticket, event, asset và lịch phải rollback cùng nhau. Không tự phá bảng hay cấu hình production để tạo lỗi. Kiểm browser lỗi mạng không cho submit lại hồ sơ stale trước tải lại.

Chỉ sau khi user xác nhận DB UAT **Phase 1–7** mới xem xét merge Phase 7. Không mở Phase 8 trước mốc kiểm thử DB đã thống nhất. Rollback source giữ ticket/event/audit và dữ liệu đã ghi; không DROP/restore dump đè dữ liệu mới.
