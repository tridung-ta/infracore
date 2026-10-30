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


## Phase 8 — Truy vấn EXPLAIN chính xác, chưa chạy DB

`php scripts/ita_phase08_explain.php` sinh PHASE08_EXPLAIN.sql từ chính ItaLifecycleObjects::queryPlans, không kết nối DB. Có 10 câu: admin/system_manager × count/list/summary/category/department. Ngày mẫu 21/10/2026, store 1, phạm vi Quản lý là bộ phận 1; cần đổi theo môi trường thật trước khi chạy.

CTE chung: cfg (ngày/trọng số/ngưỡng) → eligible (store, chưa xóa/thanh lý, phần cứng, scope và bộ lọc) → repair (COUNT/SUM theo type và opened_at, JOIN eligible) → base → ratios DECIMAL → score_raw → graded. Tất cả kế hoạch dùng cùng tier filter nếu được chọn. Detail thêm ID vào eligible, dùng list plan sau kiểm ownership. Tìm từ khóa dùng contains LIKE có escape ký tự %, _ và =; không hứa B-tree tối ưu.

Các query settings và lookup không phải aggregate. Cần kiểm EXPLAIN thực tế cho CTE materialization, GROUP BY, sort và index ngày mở phiếu; không coi việc có index trong migration là bằng chứng hiệu năng. Nếu cần index mới: gate SQL riêng, không tự ALTER hoặc tạo DB test.


## Ngân sách #09 — local, chưa EXPLAIN thật

PHASE09_EXPLAIN.sql xuất 4 truy vấn chính xác từ queryPlans (hai vai trò × nguồn vòng đời và preaggregate lịch sử). Không JOIN event/audit vào SUM chi phí. Cửa sổ ba năm lịch hoàn chỉnh trước năm hiện tại, UTC nửa mở. Giới hạn 1.000 dòng/2.000 thiết bị không cắt ngầm; cần đo host trước khi nới. Snapshot giữ mọi loại của bộ phận; export bản đã lưu chỉ đọc JSON. Chưa thêm index; cần index mới phải gate SQL riêng.
