# Bảo trì và ticket — kết quả local 20/10/2026

Gate #07 được duyệt kèm 8 chỉnh sửa; #07-SCHEDULE được user duyệt riêng. Không DDL, Composer, DB connection/creation hoặc deploy. **Không merge trước UAT DB thật Phase 1–7; CHECK chưa xác minh thực tế.** Host theo ảnh cũ là MariaDB10.6.28.

## Phạm vi có trong code

- Tạo/list/detail phiếu theo store/scope, cả ba vai trò tạo. Admin/Manager gán; Admin/Hỗ trợ xử lý, app tự validate transition. Event chỉ cho tạo/chuyển hợp lệ; gán và sửa cost ghi audit, không event cùng trạng thái.
- Actor lock → asset lock → ticket lock; re-read ticket/version trong transaction. Một pending/asset qua guard dưới khóa asset. Event/audit/asset/due cùng giao dịch; lỗi trả rollback. Inventory writer tại ASSET_WRITERS.md, import chỉ insert mới.
- Pending chặn giao, rời maintenance, thanh lý, xóa mềm và chuyển bộ phận; không chặn thu hồi. Resolved chưa phân bổ giữ maintenance chờ Admin; thứ tự xử lý lấy event, không updated_at, nên chỉnh cost phiếu cũ không thay trạng thái chờ.
- Chỉ Admin sửa cost đóng với lý do; kể cả qua reopen, Hỗ trợ không đổi chi phí đã chốt. Không dùng float so sánh cost. Reopen failed về maintenance, pending khác trả409.
- Lịch ban đầu từ purchase_date/chu kỳ, không bịa ngày nhận; đọc fallback cho hồ sơ cũ, không ghi bù hàng loạt. Sửa nguồn ban đầu khi chưa bảo trì tính lại; lịch đã có bảo trì không tự viết lại. Periodic resolved cập nhật lịch; cuối tháng được clamp. Đã sửa projection/export để version không bao gồm field join.
- Chi tiết asset có lịch sử phiếu phân trang, gộp event và audit gán/cost. Thống kê nhóm asset/type/status theo opened_at UTC range từ năm VN; Hỗ trợ giới hạn technician_id của mình. Không nhân COUNT/SUM theo event.

## Bằng chứng

| Loại | Kết quả | Giới hạn |
| --- | --- | --- |
| `php tests/ita_phase07_unit.php` | 236 pure/scripted checks đạt | Adapter giả: scope/3 vai trò, app transitions, assignment audit, cost/reopen, rollback, waiting, ngày cuối tháng và sửa ngày mua, tham số năm VN. Không SQL engine thật |
| `tests/ita_phase07_browser.js` qua Playwright CLI ita-phase06 | 25 checks đạt | API mock; submit create/assign/close/cost/ready, lịch sử trong dialog asset, thống kê, XSS text, lỗi mạng, 3 vai trò, bbox1366/768/390; không persistence |
| Hồi quy PHP | Phase3 39, Phase4 88, Phase5 203, Phase6 159, PDF118 và Smarty render đạt | Không DB |
| Hồi quy browser | Phase6 28 checks đạt | API mock, không DB |
| Review | Review Agent chỉ ra 2 lỗi, đã sửa và review lại không còn actionable findings. Impeccable detector `[]`; ảnh ba kích thước đã kiểm | Không chứng minh concurrency/EXPLAIN/headers host |

Các fixture Phase3/5 được cập nhật để nhận truy vấn pending và lịch mới; giữ assertion quyền/trạng thái, thêm pending chặn xóa/thanh lý/giao và vẫn thu hồi. Browser test phát hiện có thể gõ trong lúc save đang refresh dẫn tới reset input; đã khóa input cùng busy state và chờ read-back đúng.

PHP lint các file sửa, JS syntax và diff whitespace kiểm trước lưu commit. DB host, CHECK, concurrency, transaction rollback thật, EXPLAIN, thời gian/múi giờ và UAT toàn Phase1–7 **chưa chạy**, không đánh dấu phase-done. Checklist: PHASE07_HOST_TEST.md.
