# Phase 5 — Kiểm chứng local ngày 19/10/2026

User đã duyệt “OK BE Phase 5”. Đã build F5.1 giao thiết bị, F5.2 thu hồi, F5.3 lịch sử, F5.5 người nhận tự xác nhận và F5.6 cảnh báo từ next_maintenance_due. F5.4 sau đó được duyệt “OK PDF Phase 5” và build local ngày 20/10; kết quả riêng ở PHASE05_PDF_VERIFY.md. Không tạo DB, đọc private config, chạy SQL host hoặc deploy.

| Kiểm tra | Kết quả thật | Giới hạn |
| --- | --- | --- |
| `php tests/ita_phase05_unit.php` | 173 pure/scripted checks đạt: quyền/scope, version, trạng thái, đúng người nhận, target active, due date, duplicate, audit failure, rollback/replay, phân trang, private path, controller CSRF thiếu/sai, cảnh báo quá hạn có store/scope/date predicate | Adapter mô phỏng truy vấn/trạng thái, không chứng minh SQL/transaction thật |
| `playwright-cli -s=ita-phase03 run-code --filename tests/ita_phase05_browser.js` | 29 browser API mock checks đạt: submit giao/ack/thu hồi, required field, payload/CSRF/version, role, text escaping, phân trang người nhận, xung đột/mất phản hồi/lỗi refresh, badge và trung tâm cảnh báo, ba viewport 1366/768/390 không tràn trang/control | Chưa chạy HTTP/auth/DB integration hoặc UAT host |
| `tests/ita_phase05_preview.js` | 22 static MOCK checks đã đạt trước khi nối live; script MOCK giữ tách biệt, runtime không tải fixture | Không phải dữ liệu thật |
| Hồi quy | Phase3 36 và Phase4 88 PHP checks đạt; browser API mock Phase3 30 và Phase4 30 checks đạt; Smarty render/escape đạt | Không DB |
| Syntax/review | `php -l` 10 file PHP liên quan đạt, `node --check` assignment/live/shell/test đạt, `git diff --check` không lỗi whitespace; Review Agent lượt cuối không thấy finding actionable | Không thay thế nghiệm thu DB/concurrency |

## Contract đã build

- GET `/admin.php?op=ita&act=assignments&asset_id=...&page=...`: asset tối thiểu/version, active assignment/version, history 20 dòng/trang; pdf_available phản ánh vendor và GD/mbstring hiện có. Không trả handover_file private. Scope kiểm từ asset hiện tại; lịch sử bộ phận cũ không cấp quyền vượt scope hiện tại.
- GET `assignment-recipients&q=...&page=...`: tối đa 20 tài khoản active của store mỗi trang; chỉ Admin/Quản lý. Chỉ đọc id, username, fullname; không email/password. Không suy bộ phận nhân viên từ role/schema PM. Giao cho người dùng giữ nguyên bộ phận của tài sản; giao cho bộ phận cập nhật department_id, kiểm cả source và target scope.
- POST `assignment-save`: JSON tối đa 32 KiB để đủ 4.000 ký tự UTF-8, X-CSRF-Token; operation assign/return/acknowledge. Browser gửi asset_id/asset_version; return/ack còn có assignment_id/assignment_version. ID/actor/store/số biên bản/ngày xác nhận do server quyết định, không tin client.
- Khóa role/revision → asset → active assignment; kiểm target department active. Tất cả writer mới và audit dùng cùng InnoDB transaction, không ghi dc_. Unique active_asset_id là chốt chống hai lượt giao mở. Duplicate/deadlock/lock timeout trả 409, gọi rollback; không tự retry ngầm.
- Chỉ giao tài sản in_use chưa có active assignment/current_user_id. Hỗ trợ không tạo giao nhưng được thu hồi trong scope. Thu hồi yêu cầu lý do/tình trạng, ghi return_note trong audit, không sửa lịch sử ghi chú gốc và không chuyển bộ phận tài sản về ngầm.
- Chỉ đúng to_user_id đang có quyền ITA và scope được acknowledge, không giả danh bằng Admin hoặc payload. Bàn giao bộ phận không có xác nhận điện tử. Đã nhận/đã thu hồi từ chối lần ghi lặp; không tạo audit lặp khi bị từ chối.
- Ngày quá hạn dùng Asia/Ho_Chi_Minh: next_maintenance_due < hôm nay và status khác retired. Badge ở danh sách và các cảnh báo trong phạm vi được cộng vào chuông/trung tâm cảnh báo. Một thiết bị có thể sinh hai cảnh báo khác lý do (hỏng và quá hạn); bộ đếm là số cảnh báo. Không sửa ngày bảo trì, cron hoặc email.
- UI khóa thao tác ghi sau kết quả chưa rõ/xung đột cho tới tải lại hồ sơ; thành công nhưng refresh thất bại báo rõ đã lưu và không hiển thị bản ghi cũ. Static vẫn ghi MOCK; runtime không hiện nội dung demo.

## Chưa chạy và việc còn mở

DB integration/read-back, rollback thật, hai người giao cùng thiết bị, race đổi quyền hoặc native user status, timezone/default timestamp của schema host, EXPLAIN và tải lớn chưa chạy. dc_users MyISAM chỉ đọc, không khóa transactional/FK được với native user; tài khoản native có thể đổi trạng thái sau bước kiểm tra, không tuyên bố bảo đảm xuyên engine. Không đổi engine/schema cũ để xử lý giới hạn này.

PDF đã cài dependency, render/font QA và browser mock theo PHASE05_PDF_VERIFY.md; chưa kiểm host. Checkpoint bfe3539 đã lưu trạng thái local Phase3/4/5, F5.4 lưu commit riêng; không tag phase-done khi tiêu chí DB/host còn thiếu. Không deploy; user tiếp tục giữ production riêng.
