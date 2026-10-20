# Hiệu năng — bằng chứng và phần chưa chạy

Ngày 19/10/2026. Không tạo/kết nối DB trong Phase 3. Không suy diễn mock test thành EXPLAIN hoặc benchmark 500 tài sản.

| Hạng mục | Bằng chứng hiện có | Giới hạn |
|---|---|---|
| Phase 2 scoped list/aggregate | PHASE02_VERIFY.md ghi MySQL fixture: access=ref, idx_ita_assets_department, estimated rows=6 | Lịch sử fixture nhỏ; không chạy lại hoặc áp dụng cho host |
| Phase 3 danh sách | Source COUNT + LIMIT 5 OFFSET, store/is_deleted/department scope | Chưa EXPLAIN/latency thực |
| Năm mua | Range purchase_date thay YEAR(column) | Chưa đo optimizer |
| Bảo hành | Range warranty_end, index khai báo trong migration 002 | Chưa xác nhận index host |
| Từ khóa | Prepared LIKE escape literal %, _; nhiều cột OR, wildcard đầu | Không hứa index B-tree tối ưu contains search |
| Summary | Aggregate status/department, không query từng asset | Chưa đo GROUP BY/sort |
| History | LIMIT 51, hiển thị 50 mới nhất và báo còn dữ liệu | Chưa phân trang history riêng cho non-admin |
| Lookup loại/bộ phận | Bounded 501, báo lỗi vượt 500 | Chưa hoàn toàn đáp ứng quy tắc mọi danh sách phân trang |

Để user test DB sau: EXPLAIN bộ lọc kết hợp, admin/scope list, summary/history; benchmark 500 tài sản, trang sâu và contains search. Ghi key/type/rows/Extra/thời gian thực, không dữ liệu cá nhân/credential. Nhu cầu thêm index phải nêu thay đổi cụ thể, không tự ALTER host.
# Truy vấn bổ sung Phase 7 — chưa EXPLAIN DB thật

Ticket list theo store/asset/status/opened_at; dùng index hiện có ở migration004, không thêm DDL. Kiểm count/list cùng scope và paging20; thống kê group asset/type/status, range opened_at UTC thay YEAR(column), không JOIN events để tránh nhân COUNT/SUM. Hỗ trợ lọc technician_id.

Lịch sử UNION ALL events và audit gán/cost phải kiểm kế hoạch hai nhánh, paging20 và thứ tự. Waiting-ready dùng correlated latest lifecycle event theo created_at/id, không updated_at; cần EXPLAIN trên số ticket/event thực và xác minh index idx_ita_events_ticket + idx_ita_tickets_asset_status. Cảnh báo lịch fallback dùng DATE_ADD/CONVERT_TZ offset cố định, có thể không dùng range index khi COALESCE; chưa benchmark, không khẳng định tối ưu. Kiểm trước Phase8 cùng DB UAT Phase1–7; nếu cần index/schema mới phải gate SQL riêng.
