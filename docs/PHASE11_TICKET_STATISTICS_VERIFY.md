# Phase 11 — Phân trang thống kê ticket, 23/10/2026

User duyệt «Duyệt BUILD phân trang thống kê ticket». Chỉ thay DAO hiện có, JavaScript và partial Smarty; giữ GET ticket-statistics, không endpoint mới/DDL/index/package/config. Không DB/host, không tạo gói, merge/tag7–10/push/deploy.

## Contract

- Nhóm theo asset_id, asset_code, type, status; 20 nhóm/trang. Trả year/rows/page/per_page/total (số nhóm) và summary.ticket_count/cost toàn bộ bộ lọc. Không cộng riêng trang làm tổng. Chi phí trả chuỗi thập phân, FE định dạng bằng chuỗi.
- COUNT nhóm và tổng từ derived table của đúng truy vấn GROUP BY dùng cho danh sách. Scope/store/is_deleted/asset/year áp dụng trước aggregate. Hỗ trợ giữ technician_id=self; GET asset ngoài scope vẫn403. Năm theo VN, biên UTC >= đầu năm và < đầu năm kế tiếp; không JOIN event, mở lại không tăng số ticket.
- page positiveInt, mặc định1; trang vượt tổng được clamp, tập rỗng trả page1/total0. ORDER BY asset_id,type,status cố định, LIMIT20/OFFSET bind. Khóa nhóm này duy nhất vì mỗi asset có một asset_code trong cùng lần đọc.
- FE Trước/Sau, số trang/số nhóm và tổng toàn bộ; giữ bộ lọc đã submit khi chuyển trang dù input đã sửa. Submit lại áp dụng input mới và về trang1. Caption ghi năm và ID đã áp dụng. Lỗi giữ dữ liệu/trang trước, không tự gửi lại, busy khóa nút. Bảng rộng cuộn vùng riêng.

## Kiểm thật local

| Lệnh/loại | Kết quả | Giới hạn |
|---|---|---|
| php tests/ita_phase11_ticket_statistics.php | 190 checks | Double hợp đồng query/params; ba vai trò, scope rỗng/ngoài scope, asset filter, năm nhuận, biên UTC, clamp, input xấu, cost lớn; không thực thi SQL |
| php tests/ita_phase07_unit.php | 237 checks | Pure/scripted ticket; một prepared-summary read mới làm tăng số check |
| playwright-cli -s=ita-phase06 run-code --filename=tests/ita_phase11_ticket_statistics_browser.js | 43 checks | Ba vai trò, paging/filter/tổng/empty/error/clamp/escaping/bounds1366/768/390; static/API mock |
| playwright-cli -s=ita-phase06 run-code --filename=tests/ita_phase07_browser.js | 64 checks | Hồi quy ticket, giữ assertion quyền/CSRF/version; chỉ cập nhật fixture thống kê có metadata/summary |
| php tests/ita_write_routes.php | 13 checks | Routing scripted, không DB |
| php tests/ita_phase03_render.php; php -l PHP mới/sửa; node --check JS mới/sửa; git diff --check | Đạt | Smarty thật không DB; syntax/whitespace |

Server static127.0.0.1:18766 xác minh process20568, D:\node.exe assets/ita/preview/serve-preview.cjs. Xem ảnh mobile .playwright-cli/phase11-ticket-statistics-390.png; ảnh là dữ liệu giả có chuỗi XSS được escape, không đưa vào deploy. Preview sinh từ partial thật, file sinh không commit/đóng gói. Rà độc lập không thấy finding; agent chạy183 check trước khi thêm7 check asset/scope cuối, root chạy190 sau bổ sung. Không coi review thay SQL/host UAT.

PHP CLI local8.5.10, chưa chạy8.3.33. Chưa DB integration, EXPLAIN, index, thời gian/bộ nhớ hoặc host. COUNT/summary và danh sách là hai truy vấn riêng, không thêm transaction/snapshot trong phạm vi này; dữ liệu đồng thời thay đổi có thể làm tổng và trang lệch nhau. Phân trang giảm payload/render, không chứng minh giảm chi phí aggregate; COUNT vẫn phải tính toàn tập. Nếu EXPLAIN cần index mới phải trình SQL gate riêng. DB/host vẫn chưa nghiệm thu.

## Truy vấn để đối chiếu EXPLAIN sau này (chưa chạy)

Hai query runtime chính xác có cấu trúc dưới đây; W và params do scope/filter tạo, không phải chuỗi client truyền vào. Không paste dấu ? vào phpMyAdmin như query đã bind; user thay bằng giá trị dữ liệu giả hợp lệ của phạm vi được phép khi chủ động kiểm. Không chạy SQL từ tài liệu này tự động.

```sql
-- G =
SELECT t.asset_id,a.asset_code,t.type,t.status,COUNT(*) n,COALESCE(SUM(t.cost),0) cost
FROM ita_tickets t
JOIN ita_assets a ON a.store_id=t.store_id AND a.id=t.asset_id
WHERE /* W */ a.store_id=? AND a.is_deleted=0
AND t.opened_at>=? AND t.opened_at<?
GROUP BY t.asset_id,a.asset_code,t.type,t.status;
-- Query 1: SELECT COUNT(*) total,COALESCE(SUM(g.n),0) ticket_count,
-- COALESCE(SUM(g.cost),0) cost FROM (G sans semicolon) g
-- Query 2: G sans semicolon ORDER BY t.asset_id,t.type,t.status LIMIT 20 OFFSET ?
```

W chính xác: `a.store_id=? AND a.is_deleted=0`; non-Admin thêm ` AND a.department_id IN (?,...)` hoặc ` AND 1=0` khi không scope; có asset_id thêm ` AND t.asset_id=?`; Hỗ trợ thêm ` AND t.technician_id=?`. Thứ tự params: store, department IDs (non-Admin), asset ID nếu có, technician ID nếu Hỗ trợ, startUTC/endUTC; query2 thêm offset. Ví dụ năm2026: start2025-12-31 17:00:00 và end2026-12-31 17:00:00. Query asset filter còn gọi guard SELECT asset hiện có trước cả hai aggregate, không bỏ qua kiểm quyền để EXPLAIN.
