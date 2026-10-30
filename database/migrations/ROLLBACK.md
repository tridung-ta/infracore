# Hoàn tác schema ITA Phase 1

## Bổ sung006 / SQL #13, 26/10/2026

006_ita_network_links.sql chỉ thêm ita_network_links theo gate đã duyệt, không seed hoặc sửa bảng cũ. Agent chưa áp dụng. DDL có implicit commit, không có DOWN tự chạy. Nếu lỗi hoặc rollback source: giữ bảng/link/audit và dữ liệu mới, phục hồi đúng source backup; không DROP/DELETE/TRUNCATE hoặc restore dump đè. Nếu bảng đã có thì đối chiếu SHOW CREATE TABLE, không chạy lại CREATE mù hoặc IF NOT EXISTS. Gỡ bảng là gate riêng với backup/phụ thuộc/phạm vi được xác nhận. Xem docs/PHASE13_SQL_HOST_GUIDE.md; phần Phase1 dưới đây giữ nguyên lịch sử.

Các migration 001–004 chỉ tạo bảng mới, không chạm bảng `dc_`. DDL tạo bảng có implicit commit; `ROLLBACK` không hoàn tác được cả lô tạo bảng. Không bọc migration trong transaction rồi coi đó là bảo vệ toàn bộ lô.

Nguồn MariaDB: https://mariadb.com/docs/server/reference/sql-statements/transactions/sql-statements-that-cause-an-implicit-commit

Trên host, phương án mặc định khi chưa tích hợp ứng dụng là giữ các bảng mới không sử dụng và báo kết quả/lỗi. Không xóa bảng để chạy lại, không tắt kiểm tra khóa ngoại, không tiếp tục bỏ qua lỗi.

Nếu migration dừng giữa chừng, gửi tên bảng đã tạo và thông báo lỗi đầy đủ (không kèm mật khẩu). Agent sẽ đối chiếu `SHOW CREATE TABLE` và chuẩn bị phần còn thiếu. Không chạy lại cả file một cách mù quáng.

Việc xóa bảng mới là thao tác riêng, chỉ thực hiện sau khi xác nhận bảng nào được tạo bởi migration này, đã có backup ngoài repo, chưa có writer và không có dữ liệu cần giữ. Không cung cấp lệnh xóa trong gói UP gửi user. Thứ tự phụ thuộc ngược nếu phải chuẩn bị hoàn tác riêng:

1. `ita_import_batches`, `ita_budget_plans`, `ita_audit_logs`, `ita_ticket_events`.
2. `ita_tickets`, `ita_assignments`, `ita_license_allocations`.
3. `ita_licenses`, `ita_assets`, `ita_settings`.
4. `ita_asset_categories`, `ita_departments` (quan hệ cha/con phòng ban phải được kiểm tra).

Các bảng đã có dữ liệu nghiệp vụ không được xóa như một bước rollback tự động. Khôi phục backup cũng cần xác định ảnh hưởng tới dữ liệu sinh sau thời điểm backup.
