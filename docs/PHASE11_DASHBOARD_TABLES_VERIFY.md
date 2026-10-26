# Phase11 — Điều hướng bảng dashboard, 26/10/2026

Tiếp tục BUILD trong quyền FE hiện hành: chỉ sửa ita-dashboard.js và cache URL tại layout Smarty, không PHP/API/SQL/công thức/quyền hoặc dependency. SQL #13 vẫn chưa duyệt/chưa chạy; không DB test/host, không gói/merge/tag7–10/push/deploy. DB/host chưa nghiệm thu.

## Hành vi

- Ba bảng đối chiếu biểu đồ và bảng đề xuất thay thế có20 dòng/trang, Trước/Sau, trang/tổng dòng. Mỗi bảng có trang riêng; nav giữ trên DOM để giữ focus bàn phím, nhãn vùng riêng và aria-live.
- Chỉ slice mảng trình bày. Biểu đồ vẫn giữ toàn bộ nhóm/series trong response; chuyển trang bảng không fetch, không init/dispose chart và không đổi KPI, tỷ lệ lỗi, mốc thời gian hoặc report token/export. Không lấy tổng hoặc tỷ lệ riêng trang làm toàn tập.
- Nhận response mới đưa cả bốn bảng về trang1. Trong lúc tải khóa nav; nếu đọc lỗi, giữ dữ liệu/mốc cũ theo contract hiện có và mở lại nav của bảng cũ. Trang cuối/rỗng khóa nút phù hợp, không cắt dữ liệu ngầm.
- Hỗ trợ vẫn chỉ thấy bảng bảo trì hiện có, không fetch dashboard hoặc nhận các bảng nhóm mới. Không thay phân trang báo cáo/alerts từ server. Đầu ra tiếp tục textContent, bảng rộng cuộn trong vùng riêng.

## Kết quả thật

| Lệnh/kiểm | Kết quả | Giới hạn |
|---|---|---|
| playwright-cli -s=ita-phase06 run-code --filename=tests/ita_phase11_dashboard_tables_browser.js | 44 checks đạt | API mock: Admin/Manager với41 nhóm/41 đề xuất và Support; trang2/cuối, độc lập, keyboard/focus, XSS, không fetch, chart đủ41 nhóm và giữ instance, KPI/time/tỷ lệ giữ nguyên, busy/error/reset/empty, bounds1366/768/390 |
| playwright-cli -s=ita-phase06 run-code --filename=tests/ita_phase10_browser.js | 64 checks đạt | Hồi quy dashboard/alerts/report/export bằng API mock; không sửa test/fixture cũ |
| php tests/ita_phase03_render.php | Đạt | Smarty thật với dữ liệu giả, không DB |
| node --check assets/ita/js/ita-dashboard.js; git diff --check | Đạt | Syntax/whitespace |
| Impeccable detect --json assets/ita/js/ita-dashboard.js | [] | Giới hạn target đã đọc, không chứng nhận toàn ứng dụng |
| Review Agent độc lập | Không finding cần sửa | Chỉ đọc diff/test, không tự chạy browser/DB |

Đã xem ảnh .playwright-cli/phase11-dashboard-tables-390.png. Bảng cuộn nội bộ, nav/input không tràn document. Ảnh/static/API đều dữ liệu giả. Browser session và preview cũ đã dừng nên mở lại, khởi động đúng node assets/ita/preview/serve-preview.cjs tại127.0.0.1:18766 (exec session59951). Connection refused ban đầu là preview chưa chạy, không coi là lỗi sản phẩm hay kết quả đạt. Không chạy PHP/DB qua static server.

## Giới hạn và triển khai sau này

Đây là **client pagination**, không LIMIT/OFFSET SQL và không giảm payload/aggregate/chi phí tạo biểu đồ. Server vẫn trả toàn bộ nhóm/đề xuất trong guard hiện có; phần BE trong PHASE11_PAGINATION_AUDIT.md còn mở, không gỡ giới hạn. Biểu đồ nhiều nhóm vẫn cần đánh giá khả năng đọc/hiệu năng riêng, không tự làm mới biểu đồ nặng hoặc polling.

PHP local8.5.10 dùng render, chưa8.3.33/layout PHP thật/DB/host/EXPLAIN. Source BE13 trước đó b4bd575 đã build local, không được suy thành bảng/network host có thật. Khi được phép upload cần JS và layout đồng bộ, backup đúng file; rollback source giữ dữ liệu. Chưa tạo ZIP/gói hoặc deploy.
