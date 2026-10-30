# Phase 11 — Phân trang lịch sử tài sản, 23/10/2026

User «Duyệt BUILD phân trang lịch sử tài sản». Phạm vi đã duyệt: GET asset hiện có, COUNT + LIMIT/OFFSET, 20 thao tác/trang, sort ID giảm, Trước/Sau trong tab Lịch sử, quyền ba vai trò và phản hồi muộn khi đổi hồ sơ. Không DDL/index/DB/config/gói/deploy.

## Thay đổi

- ItaAssetObjects::detail(id,page=1): đọc asset đúng store/chưa xóa mềm, kiểm department trước mọi COUNT/list lịch sử. COUNT và rows cùng store/entity_type=asset/entity_id. page clamp về trang cuối hoặc1 khi rỗng; giữ history/history_has_more, thêm history_pagination={page,per_page:20,total}. Dispatcher truyền page đã positiveInt validate hiện có, không action mới.
- COUNT: `SELECT COUNT(*) n FROM ita_audit_logs WHERE store_id=? AND entity_type='asset' AND entity_id=?` với params store/id. List: `SELECT id,user_id,action,before_json,after_json,created_at FROM ita_audit_logs WHERE store_id=? AND entity_type='asset' AND entity_id=? ORDER BY id DESC LIMIT 20 OFFSET ?` với store/id/offset. Đây là hợp đồng source, chưa chạy EXPLAIN; không đề xuất index khi chưa có bằng chứng. Cần index mới thì gate SQL riêng.
- FE tab Lịch sử hiển thị số trang/tổng thao tác, Trước/Sau, busy và thông báo lỗi. Khi lỗi xóa nội dung lịch sử đang hiển thị, giữ metadata trang để user thử lại hướng chuyển trang; không tự retry. JSON audit hỏng hiển thị thông báo kiểm soát, không làm vỡ tab. Dữ liệu dùng textContent.
- Mỗi hồ sơ có generation; đóng/mở hồ sơ vô hiệu hóa kết quả history cũ. Shell có sequence cho GET chi tiết ban đầu để request asset cũ không thay dialog asset mới. Không đổi editor/version khi chỉ chuyển trang lịch sử. Request cũ có thể tiếp tục chạy tới timeout nhưng không được ghi DOM của hồ sơ mới.
- Lịch sử này chỉ là audit entity_type=asset hiện có, không giả thành lịch sử ticket/phân bổ đầy đủ. Liên kết/lịch sử bảo trì và phân bổ giữ nguyên.

## Kết quả thật

| Lệnh/loại | Kết quả | Phạm vi |
|---|---|---|
| php tests/ita_phase11_asset_history.php | 174 kiểm tra đạt | Query-contract double, ba vai trò, asset ngoài scope403/mất404 trước history, store/type/id, trang1/2/cuối/vượt/rỗng, clamp; không SQL execution |
| playwright-cli -s=ita-phase06 run-code --filename=tests/ita_phase11_asset_history_browser.js | 43 kiểm tra đạt | Ba vai trò, trang2/cuối, lỗi403 xóa content, clamp, rỗng, XSS/JSON hỏng, busy, bounds1366/768/390, phản hồi muộn history và chi tiết ban đầu; static/API mock |
| playwright-cli -s=ita-phase06 run-code --filename=tests/ita_phase03_browser.js | 30 kiểm tra đạt | Hồi quy tài sản/quyền/version; không sửa/nới bộ test cũ |
| php tests/ita_phase03_unit.php | 39 kiểm tra đạt | Pure/scripted, không DB |
| php tests/ita_phase07_unit.php | 237 kiểm tra đạt | Hồi quy ticket scripted, không DB |
| php tests/ita_write_routes.php | 13 kiểm tra đạt | Routing scripted |
| php tests/ita_phase03_render.php; php -l PHP mới/sửa; node --check JS mới/sửa; git diff --check | Đạt | Smarty thật không DB, syntax/whitespace |

Rà độc lập không finding; agent chạy174 checks đạt. Browser dùng static preview18766 đã xác minh tiến trình trong đợt thống kê liền trước, code lấy từ workspace hiện tại. Đã xem ảnh mobile .playwright-cli/phase11-asset-history-390.png, nav cuộn tới được trong dialog; ảnh test không đóng gói. Giữ giao diện hiện tại, không thay theme/Smarty.

## Giới hạn

PHP local8.5.10, chưa chạy8.3.33. Không tạo/kết nối DB; SQL/EXPLAIN/index/concurrency/PHP-FPM/host chưa kiểm, chưa nghiệm thu DB. COUNT và rows là hai lần đọc riêng, không thêm transaction/snapshot; audit mới đồng thời có thể khiến tổng và trang lệch hoặc OFFSET lặp/bỏ qua giữa các lần tải. Browser mock không chứng minh quyền DB thực. Shell sequence không thay kiểm quyền server.

Triển khai tương lai cần PHP DAO/dispatcher và JS assets/shell đồng bộ; chưa tạo gói hoặc upload. Delta S11 ba file cũ không bao gồm thay đổi này. Rollback source đã backup, giữ audit/dữ liệu mới. Không merge/tag7–10/push/deploy.
