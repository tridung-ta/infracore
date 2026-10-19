# Phase 4 — Kết quả local ngày 19/10/2026

BE-GATE #04 đã được user duyệt. F4.1–F4.5 đã có code local; chưa nghiệm thu DB hoặc production. Không tạo DB, không đọc cấu hình private, không kết nối host, không upload/deploy.

| Phạm vi | Kiểm chứng thực tế | Kết quả/giới hạn |
| --- | --- | --- |
| XLSX và quy tắc import | `php tests/ita_phase04_unit.php` | 88 checks: đọc/ghi file XLSX thật, giữ tiếng Việt/seri có số 0/giá chính xác, chống công thức, XXE, liên kết ngoài (cả entity trong thuộc tính), tỷ lệ nén bất thường, sai header, rỗng, quá dòng, staging quá lớn, memory thấp; controller từ chối CSRF thiếu/sai, method sai, vai trò trái quyền; các truy vấn/ghi/rollback/idempotency dùng adapter giả, không phải DB integration |
| Giao diện runtime | `playwright-cli -s=ita-phase03 run-code --filename tests/ita_phase04_browser.js` | 30 browser checks; API và binary download được mock, không phải HTTP/DB E2E; upload có CSRF, giữ file, preview/phân trang, lỗi, xác nhận, mất phản hồi, trạng thái hoàn tất, export, ba vai trò; 1366/768/390 px không tràn trang, control nằm trong viewport, không lỗi JS; thông báo hiện ở mọi tab |
| Preview độc lập | `playwright-cli -s=ita-phase03 run-code --filename tests/ita_phase04_preview.js` | 16 checks; không upload/ghi, 4 kịch bản MOCK, keyboard tabs, ba viewport |
| Hồi quy Phase 3 | `php tests/ita_phase03_unit.php`; `php tests/ita_phase03_render.php` | 36 checks pure/scripted, Smarty render và escape đạt; không DB |
| Syntax | `php -l` 11 file PHP Phase 4/routing/bootstrap/audit/DAO/test; `node --check` import/live/shell | Đạt; `git diff --check` không lỗi whitespace. Không coi lint là kiểm chứng DB |

Review Agent phát hiện staging lưu trùng `values`/`fields` có thể ghi file lớn hơn giới hạn đọc. Đã bỏ bản sao fields, kiểm kích thước JSON trước khi ghi và thêm case từ chối 32 MiB. Các lô hoàn tất đọc được trạng thái ngay cả khi staging không còn; không cần đọc lại file để biết đã ghi hay chưa.

Review Agent lượt cuối không còn finding actionable. Đã nhìn ảnh desktop/mobile, giữ theme hiện có và đo control/overflow ba viewport; không suy từ ảnh/mock ra UAT runtime. Giới hạn quá nhiều định dạng được kiểm trong scanner để tránh XLSX gây phình object; chưa benchmark corpus file Excel đa dạng.

## Hợp đồng và quyết định triển khai

- Route: GET `import-template`, `import-preview&batch_id=...&page=...`, `import-errors&batch_id=...`, `asset-export` trên `/admin.php?op=ita&act=...`; POST `import-upload` (multipart file, X-CSRF-Token), `import-confirm` (JSON batch_id/confirm_token, X-CSRF-Token).
- Admin IT và Quản lý hệ thống import/export; Quản lý chỉ trong scope, không import trạng thái retired. Hỗ trợ kỹ thuật bị từ chối tại server. Auth tải lại actor theo request; confirm khóa role và kiểm revision trước khi ghi.
- Mẫu một sheet, 16 cột cố định; 7 cột đầu bắt buộc theo Word 4.6. Ngày chuỗi `yyyy-mm-dd` hoặc ô Excel ngày có định dạng; không nhận công thức/ô lỗi. Các ô output ghi text tường minh để giữ định danh và giá DECIMAL, không chạy formula. Không tự tạo danh mục/bộ phận hoặc sửa tài sản trùng.
- Max 5 MB/5.000 dòng; ZIP tối đa 200 entries, 32 MiB giải nén, 24 MiB/entry, kiểm tỷ lệ nén. Host có bộ nhớ thấp có thể cần chia file nhỏ hơn; kiểm dự toán memory trước load/write. Không sửa php.ini. `fileinfo` được dùng khi có; khi không có vẫn bắt buộc mở ZIP, kiểm cấu trúc và từng XML. Local thiếu fileinfo; loader ITA chỉ nạp 4 namespace thư viện có sẵn, không sửa loader legacy.
- Mã trùng kiểm theo `utf8mb4_unicode_ci` bằng WEIGHT_STRING và query tham số theo nhóm 100; DB thật/case/accent/concurrency **chưa chạy**. Unique key là chốt cuối, lỗi duplicate khi confirm trả 409 và gọi rollback.
- Staging ở thư mục temp private ngoài ROOT_PATH, random ID; không lưu XLSX gốc, chỉ nguồn đã đọc/lỗi/revision, tối đa 32 MiB. Batch gắn store/user, hết hạn sau 2 giờ; confirm token HMAC gắn batch và phiên. Không đưa đường dẫn private cho browser. Chưa có job dọn staging (không tự mở rộng cron ngoài gate).
- Confirm đọc nguồn server, kiểm lại danh mục/bộ phận/quyền/trùng; dùng một transaction cho tài sản, audit từng tài sản, batch completed và import.complete. Không ghi bảng dc_. Lỗi trước commit gọi rollback; các bảo đảm trên InnoDB cần UAT thật.
- Mất phản hồi confirm: khóa xác nhận và upload, cho phép kiểm trạng thái lô; không tự gửi lại. Completed không ghi lại. Export dùng chung predicate lọc/scope với listing, đọc tối đa 5001, từ chối nếu vượt 5000 (không cắt file âm thầm).

## Chưa kiểm chứng

Upload qua PHP thực/giới hạn host, SQL WEIGHT_STRING MariaDB, engine và transaction rollback thực, hai phiên confirm đồng thời, quyền thay đổi đồng thời, tốc độ/bộ nhớ 500–5.000 dòng trên host, mở file bằng Excel desktop. Không chạy fixture/reset cũ. Không có tag phase-done hoặc tuyên bố production sẵn sàng. Source Phase 3/4 đang working tree, chưa tạo commit đa chức năng để thay cho nghiệm thu còn thiếu.
