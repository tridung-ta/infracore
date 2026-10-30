# BE-GATE #07 — Bảo trì và ticket (đã duyệt kèm chỉnh sửa)

User duyệt 20/10/2026: cả ba vai trò tạo phiếu theo scope; rà tất cả asset writers và khóa asset trước ticket; pending chặn giao/thanh lý/xóa mềm/rời maintenance, vẫn thu hồi. Application tự kiểm transitions, không dựa CHECK; chỉ Admin sửa chi phí phiếu đóng có lý do/audit; badge chờ xác nhận sẵn sàng; lịch sử gộp events và audit gán. Không merge trước UAT DB Phase 1–7. Nội dung gốc bên dưới chịu các điều chỉnh này. Cách tính lịch ban đầu tác động Phase 3–5 chờ gate riêng BE_GATE_07_SCHEDULE.md.

Phạm vi: F7.1 tạo phiếu; F7.2 gán kỹ thuật viên và xử lý; F7.3 đồng bộ trạng thái thiết bị; F7.4 lịch sử trên chi tiết tài sản; F7.5 thống kê theo năm/thiết bị; F7.6 lịch bảo trì kế tiếp. Nền hiện tại: commit 068931b/ed363ac, Phase 6 local, chưa nghiệm thu DB.

Thay đổi dự kiến:

- Thêm ItaTicketObjects/ItaTicketObjectInfo, service/rules ticket; endpoint danh sách, chi tiết, chọn kỹ thuật viên, tạo/gán/chuyển trạng thái và thống kê qua dispatcher ITA hiện có.
- Sửa bootstrap, ItaApplication và audit allowlist; nối lịch sử ticket vào chi tiết tài sản. Bổ sung guard tại ItaAssetService để thao tác trạng thái thủ công không bỏ qua ticket pending.
- Template/JS/CSS riêng cho danh sách, phiếu và thống kê; bộ lọc, phân trang, thông báo lưu/lỗi và retry an toàn. Dữ liệu preview độc lập đánh dấu MOCK.
- Prepared query, CSRF cho POST, kiểm scope/store/actor và phiên bản; khóa asset rồi ticket theo thứ tự thống nhất, event + audit + trạng thái + lịch bảo trì trong một transaction InnoDB. Không ghi bảng dc_.

Migration: **không dự kiến DDL hoặc SQL host**. Đã đối chiếu migration 004: ita_tickets có type, technician_id, handled_date, resolved_at, cost, resolution_note; ita_ticket_events có CHECK giới hạn chuyển trạng thái. Migration 001 có maintenance_cycle_months, 002 có last_maintenance_at và next_maintenance_due. Tái sử dụng bảng/cột/index sẵn có. Nếu cần schema mới thì dừng, trình gate SQL riêng; không chạy lại CREATE.

Rủi ro và backup: xử lý đồng thời, scope kỹ thuật viên, đóng ticket làm thiết bị sẵn sàng sai, đếm lặp khi mở lại, ngày cuối tháng và audit lỗi. Không tạo/kết nối DB, không deploy. Test local pure/scripted/browser mock; UAT DB thật do user thực hiện sau, bắt buộc trước Phase 8. Trước triển khai user backup source/DB riêng; rollback source giữ tickets/events/audit/dữ liệu đã ghi, không DROP hoặc restore đè dữ liệu mới. Không package mới, cron/email hoặc thay đổi thông tin đăng nhập.

Giả định cần xác nhận:

1. Theo ma trận Word: Admin IT tạo phiếu và xử lý/gán; Quản lý hệ thống xem và gán trong scope; Hỗ trợ xử lý ticket thiết bị trong scope, không tự gán lại kỹ thuật viên. Chọn kỹ thuật viên từ tài khoản ITA active có trách nhiệm với bộ phận thiết bị (hoặc Admin toàn hệ thống), không suy membership từ store_id.
2. pending → resolved/failed; failed → pending; resolved không mở lại. Chuyển trạng thái cần ghi chú; đóng cần ngày xử lý hợp lệ và chi phí VND không âm. Gán kỹ thuật viên khi pending ghi audit riêng, không thêm event pending → pending vì CHECK hiện tại không cho phép.
3. Mỗi thiết bị chỉ có tối đa một ticket pending, bảo đảm bằng khóa asset và kiểm lại trong transaction. Không tạo/mở lại cho thiết bị retired/deleted; dữ liệu pending trùng có sẵn trả lỗi cần đối chiếu, không tự xóa/sửa để vượt chặn.
4. Mở phiếu sửa chữa hoặc định kỳ đưa thiết bị sang maintenance, failed đưa sang broken; resolved đưa về in_use nếu đang có người dùng hoặc lượt bàn giao hiệu lực. Nếu chưa phân bổ, giữ maintenance để Admin xác nhận trạng thái sẵn sàng sau kiểm tra; không tự gán user hoặc mở bàn giao. Chặn đổi trạng thái thủ công rời maintenance/thanh lý khi còn ticket pending.
5. Chỉ phiếu periodic resolved cập nhật last_maintenance_at và next_maintenance_due: lấy ngày xử lý theo VN cộng chu kỳ loại thiết bị, mặc định 6 tháng; ngày cuối tháng được chặn tại ngày cuối tháng đích. Sửa chữa không tự dời lịch định kỳ; failed/mở lại không ghi thành bảo trì thành công. Không tự tính lại toàn bộ ngày lịch sử khi đổi chu kỳ loại.
6. Thống kê theo năm mở phiếu tại VN, COUNT ticket ID riêng, tách repair/periodic và trạng thái; mở lại vẫn là cùng phiếu, không đếm event như phiếu mới. Chi phí là tổng hiện tại của phiếu, không cộng lại mỗi event. Lịch sử trạng thái/chi phí thay đổi giữ qua event/audit; không tuyên bố snapshot báo cáo đã chốt.

Trả lời mong muốn: **OK BE Phase 7 / OK với chỉnh sửa / Không**.

Điểm dừng bắt buộc: AGENTS.md mục 2 quy định PHP, endpoint và SQL mới phải được duyệt theo phase. Phê duyệt #06 không bao gồm #07. Đây chỉ là tài liệu phạm vi, chưa build backend ticket hoặc chạy DB.
