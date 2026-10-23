# Phase 11 — Danh sách Quản lý loại, 23/10/2026

User «Duyệt BUILD danh sách Quản lý loại». Chỉ mở chế độ phân trang/search cho Admin trong GET categories hiện có, giữ version edit và dropdown. Không DDL/index/DB/config/gói/merge/tag7–10/push/deploy.

## Contract và hành vi

- GET categories không có paged giữ nguyên mảng dropdown, store filter, LIMIT501 và lỗi422 nếu vượt500; Admin/Quản lý/Hỗ trợ vẫn có lookup như trước. Loại inactive vẫn có trong dữ liệu để giữ hồ sơ đang tham chiếu, tạo mới vẫn chỉ chọn active.
- GET categories?paged=1&page=1&q=... trả rows/page/per_page=20/total. Server requireAdmin trước truy vấn; chế độ paged khác chuỗi1 trả422. page dùng positiveInt toàn dispatcher hiện có, rồi clamp. q text tối đa100 ký tự, trim, tìm chuỗi literal trong mã/tên; `%`, `_`, `!` được escape với ESCAPE '!'. Không lọc bỏ inactive trong danh sách quản lý.
- COUNT và list cùng store/search; ORDER BY name,id ổn định; OFFSET bind. Version từng row tính từ đúng id/code/name/status, khớp SELECT FOR UPDATE của writer cũ. Writer/category-save/CSRF/audit/version contract không đổi.
- Search Enter là GET, về trang1. Trước/Sau dùng từ khóa đã áp dụng, sửa input chưa search không làm đổi điều kiện paging. Bản sửa đang nhập và version đã chọn được giữ khi chuyển trang; không lấy version của row khác.
- Lưu thành công reset form và xóa rõ hidden ID/version; cần chọn lại row để lấy version mới. Lưu thành công nhưng reload lỗi vẫn xóa ID/version, không gửi lại POST. Lỗi409 giữ nội dung để user xử lý, không tự tải version mới rồi ghi đè. Thêm loại mới/reset cũng xóa hidden ID/version.
- Busy khóa search/list/editor; đóng dialog được khi chỉ đọc, bị chặn trong lúc lưu. Sequence bỏ phản hồi đọc cũ sau đóng/mở lại. Lỗi đọc xóa list và có thông báo, search cho phép thử lại rõ ràng. Không tự polling hoặc retry.

## Kiểm thật local

| Lệnh/loại | Kết quả | Giới hạn |
|---|---|---|
| php tests/ita_phase11_category_pagination.php | 69 checks | Query-contract double: Admin/store/search escape/name,id/page/clamp, hai vai trò bị403 trước SQL, invalid mode/q, lookup500 guard, version conflict và UPDATE/audit đúng version; không SQL execution |
| playwright-cli -s=ita-phase06 run-code --filename=tests/ita_phase11_category_pagination_browser.js | 24 checks | Admin search/page/edit/version/CSRF/409/save/reload lỗi/reset/late-close/empty/bounds1366/768/390; Quản lý/Hỗ trợ ẩn management; static/API mock |
| playwright-cli -s=ita-phase06 run-code --filename=tests/ita_phase03_browser.js | 30 checks | Hồi quy tài sản, quyền/version và inactive; chỉ đổi fixture categories theo paged, không xóa/nới assertion |
| php tests/ita_phase03_unit.php; php tests/ita_write_routes.php | 39 +13 checks | Pure/scripted, không DB |
| php tests/ita_phase11_asset_history.php; php tests/ita_phase11_ticket_statistics.php | 174 +190 checks | Hồi quy query-contract, không DB |
| php tests/ita_phase03_render.php; php -l PHP mới/sửa; node --check JS mới/sửa; git diff --check | Đạt | Smarty thật, syntax/whitespace |

Rà độc lập không finding; agent chạy63 checks trước khi root thêm6 checks version writer, root chạy69. Detector template/JS trả0 finding, không thay audit toàn app. Đã xem ảnh mobile .playwright-cli/phase11-category-pagination-390.png, paging cuộn tới được trong dialog; ảnh và preview sinh không commit/deploy. Browser đầu phát hiện reset hidden ID/version không xóa như mong đợi; đã thêm handler reset explicit và chạy lại đạt, không nới assertion.

## Query và giới hạn

COUNT: `SELECT COUNT(*) n FROM ita_asset_categories WHERE store_id=?`.

List: `SELECT id,code,name,status FROM ita_asset_categories WHERE store_id=? ORDER BY name,id LIMIT 20 OFFSET ?`.

Nếu q khác rỗng, cả hai WHERE thêm chính xác ` AND (code LIKE ? ESCAPE '!' OR name LIKE ? ESCAPE '!')`; params store, `%literal_escaped%`, `%literal_escaped%`; list thêm offset. Version được tính PHP, không thêm cột. Đây là query source để EXPLAIN sau, chưa chạy DB hoặc đề xuất index. Cần index mới phải gate SQL riêng.

COUNT và list đọc riêng, không snapshot chung; dữ liệu đổi đồng thời có thể làm số trang và rows lệch. Search chứa wildcard đầu không bảo đảm hiệu năng/index; chưa đo tải. PHP local8.5.10, chưa chạy8.3.33/host/DB/EXPLAIN/concurrency/rollback thật. Các lookup/dropdown vẫn giới hạn500, chưa hoàn tất phân trang danh mục toàn ứng dụng. Giới hạn này có thể báo lỗi trên form khác dù Admin management đã xem được nhiều loại hơn500; không âm thầm gỡ guard.

Triển khai tương lai cần service/dispatcher + JS + partial Smarty đồng bộ; chưa tạo gói, không đưa vào delta S11 ba file cũ. Backup file thay và DB trước upload do user thực hiện; rollback source giữ dữ liệu/audit mới. DB/host chưa nghiệm thu.
