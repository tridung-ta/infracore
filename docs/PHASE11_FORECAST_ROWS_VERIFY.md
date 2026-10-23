# Phase 11 — Điều hướng dòng ngân sách, 23/10/2026

Tiếp tục BUILD phần FE được phép; chỉ JS forecast và partial Smarty, không PHP/SQL/API/config. Không tạo gói/merge/tag7–10/push/deploy.

## Hành vi

Bảng ngân sách hiển thị20 dòng/trang với Trước/Sau, số trang/tổng dòng; nút thật hỗ trợ bàn phím, busy khóa nav. Toàn bộ documentData vẫn được giữ, chỉ slice phần hiển thị; không cộng tiền riêng trang thành tổng. Khi render nguồn mới (tính lại, xem/lưu snapshot), trang về1. Nguồn rỗng có thông báo và hai nút khóa.

Summary/công thức/DECIMAL/dữ liệu thiếu không đổi. Chuyển trang không request hoặc POST, không đổi filters/snapshot/export. Xuất vẫn theo toàn nguồn hiện tại/snapshot và giới hạn server, không chỉ trang bảng; payload không thêm page/rows hoặc tổng tiền client. Không sửa luồng lưu/chốt hay cấu hình ngân sách.

## Kiểm thật

| Lệnh/loại | Kết quả | Phạm vi |
|---|---|---|
| playwright-cli -s=ita-phase06 run-code --filename=tests/ita_phase11_forecast_rows_browser.js | 24 checks đạt | Admin/Quản lý với41 dòng giả; Support không request ngân sách; trang20/cuối/keyboard/empty, số tiền lớn, escape, tổng/nguồn không đổi, không HTTP thêm, snapshot reset, export không page/rows/tiền client, bounds1366/768/390 |
| playwright-cli -s=ita-phase06 run-code --filename=tests/ita_phase09_browser.js | 42 checks đạt | Hồi quy snapshot/CSRF/version/quyền/export/missing/defaults/static; không sửa assertion/fixture cũ |
| php tests/ita_phase03_render.php | Đạt | Smarty thật, không DB |
| node --check JS mới/sửa; git diff --check | Đạt | Syntax/whitespace |

Đã xem ảnh .playwright-cli/phase11-forecast-rows-390.png; bảng rộng cuộn vùng riêng, nav nằm trong trang. Ảnh chứa tên/markup giả để kiểm textContent, không dùng làm dữ liệu thật hoặc đưa vào deploy. Static preview18766 dùng node serve-preview.cjs từ đợt trước; không gọi PHP/DB thật. Detector template/JS trả0 finding, review độc lập không finding (reviewer chưa tự chạy browser).

Đã kiểm lại nội dung UTF-8 sau sửa helper để không giữ ký tự lỗi console; preview sinh/ảnh không commit. Không đổi CSS/theme/motion. Giao diện thật cần JS/partial đồng bộ nếu được triển khai sau.

## Giới hạn

**Client pagination**, không phải SQL pagination: server vẫn tính/trả toàn bộ tập dòng và giữ giới hạn1000 dòng/2000 thiết bị theo contract cũ; không tối ưu SQL hoặc giảm payload. Tập lớn vượt guard vẫn bị từ chối, không cắt ngầm. EXPLAIN/hiệu năng/DB/host/PHP8.3.33/layout PHP thật chưa kiểm; PHP render local8.5.10. Browser mock không chứng minh persistence, rollback hoặc file PDF thật (export fixture chỉ kiểm payload/transport).

DB/host chưa nghiệm thu. Phần BE budget rows trong PHASE11_PAGINATION_AUDIT vẫn mở nếu cần tối ưu sau đo; không tự mở gate. Chưa tạo gói/upload, không mở rộng delta S11 cũ. Rollback tương lai khôi phục source backup, giữ snapshot/audit/dữ liệu mới.
