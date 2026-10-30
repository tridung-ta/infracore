# Các writer tài sản — đối chiếu source 20/10/2026

Inventory runtime ITA qua tìm INSERT/UPDATE trong classes/modules/includes; database/tests chỉ là fixture, không chạy. Không tìm thấy writer ITA khác trong legacy module hiện có.

| Đường ghi | Status có bị ghi? | Khóa / guard cần áp dụng |
| --- | --- | --- |
| ItaAssetService::saveFields create | Dùng default in_use, không nhận status đầu vào | Hàng mới chưa có ticket; không thể khóa hàng chưa tồn tại; unique asset_code chống trùng |
| ItaAssetService::saveFields update | Không; danh sách fields không có status | Khóa asset trước references; pending chặn đổi bộ phận để không chuyển scope ticket đang xử lý |
| ItaAssetService::change status, gồm thanh lý | Có, UPDATE status | Khóa asset trước ticket; pending chặn rời maintenance/thanh lý; chờ xác nhận sẵn sàng chỉ Admin chuyển về in_use |
| ItaAssetService::change delete | Chỉ is_deleted, không status | Khóa asset trước ticket; pending chặn xóa mềm |
| ItaAssetService::change note | Không; status_reason | Khóa asset; vẫn cho ghi chú |
| ItaAssignmentService::assign | Không; current_user_id/department_id | Actor rồi asset trước assignment/ticket; pending chặn giao mới dù asset bị lệch status |
| ItaAssignmentService::receive | Không; current_user_id=NULL | Actor rồi asset trước assignment; thu hồi vẫn cho phép khi pending |
| ItaAssignmentService::acknowledge | Không ghi asset | Actor rồi asset trước assignment; giữ luồng xác nhận cũ |
| ItaImportService::insert | INSERT gồm status từ dữ liệu validate | Chỉ insert mới; không upsert/update asset cũ. Unique code chặn nhập đè. Không có ticket trước khi asset mới tồn tại |
| Ticket service Phase 7 | Có; maintenance/broken/in_use, lịch bảo trì | Actor rồi asset, sau đó ticket; mọi writer đồng bộ asset phải giữ thứ tự asset trước ticket |

Không có endpoint thanh lý riêng: thanh lý qua asset status retired. Không thêm writer SQL trực tiếp/cron hoặc sửa dữ liệu cũ. Row lock trên references có sau asset; không tuyên bố hết deadlock nếu chưa kiểm concurrent DB thật. Chuỗi giao mới có thể giữ assignment sau asset rồi đọc pending; ticket service không khóa assignment khi đọc trạng thái bàn giao dưới cùng khóa asset, tránh đảo thứ tự.

Host đã được user gửi VERSION() = **10.6.28-MariaDB** trước đây; chưa kết nối lại hoặc kiểm biến check_constraint_checks. Chưa xác minh CHECK trên DB thật. Chuyển trạng thái ticket phải được validate ở application dù CHECK bật hay tắt.
