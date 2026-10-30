# Phase 11 — Điều hướng nhóm vòng đời, 23/10/2026

Tiếp tục BUILD phần FE độc lập theo quyền FE hiện hành trong AGENTS. Chỉ sửa ita-lifecycle.js và partial Smarty lifecycle; không PHP/SQL/API/DDL/config. Không cần mở gate BE mới, không tạo gói/merge/tag7–10/push/deploy.

## Hành vi

- Bảng Loại thiết bị và Bộ phận hiển thị tối đa20 nhóm/trang, Trước/Sau và số trang/tổng nhóm. Mỗi chế độ nhớ trang riêng khi đổi qua lại.
- Dữ liệu nhóm giữ nguyên thứ tự và giá trị từ response hiện có. Chuyển trang không fetch, không đổi bộ lọc, KPI, điểm/thành phần hoặc trang thiết bị. Không tính tuổi trung bình của các dòng trên trang thành tuổi trung bình toàn bộ; mẫu số tỷ lệ lỗi vẫn là total của đúng nhóm từ response.
- Khi nhận response mới (áp dụng/xóa bộ lọc, tải lại hoặc chuyển trang thiết bị), cả hai trang nhóm trở về1. Trang rỗng giữ trạng thái giải thích; hai nút bị vô hiệu khi không có hướng chuyển. Busy tải dữ liệu khóa cả nav nhóm. Các nút và aria-live giữ trên DOM khi thay bảng nên hỗ trợ bàn phím.
- Nhóm null hiển thị Chưa phân bổ, tên nhóm được textContent escape. Bảng rộng cuộn ngang bên trong; không đổi CSS/theme/motion.

## Kết quả thật

| Lệnh/loại | Kết quả | Giới hạn |
|---|---|---|
| playwright-cli -s=ita-phase06 run-code --filename=tests/ita_phase11_lifecycle_groups_browser.js | 30 checks đạt | Admin/Quản lý với41 loại và21 bộ phận giả; Support không request report; paging20/cuối/nhớ trang, keyboard, null/XSS, mẫu số, KPI/thiết bị giữ nguyên, không HTTP thêm, reset/empty, bounds1366/768/390 |
| playwright-cli -s=ita-phase06 run-code --filename=tests/ita_phase08_browser.js | 63 checks đạt | Hồi quy API mock/static hiện có; không đổi assertion hoặc fixture cũ |
| php tests/ita_phase03_render.php | Đạt | Smarty thật, dữ liệu giả, không DB |
| node --check JS mới/sửa; git diff --check | Đạt | Syntax/whitespace |

Đã xem ảnh .playwright-cli/phase11-lifecycle-groups-390.png; tên chứa markup là chuỗi giả để kiểm escape, không phải HTML thực thi. Static preview tại127.0.0.1:18766 khởi động bằng node assets/ita/preview/serve-preview.cjs; không chạy PHP/DB qua server này. Preview/ảnh không commit/deploy. Impeccable detector template/JS trả0 finding; review độc lập không defect, chưa tự chạy browser tại agent review.

Browser session ban đầu đã đóng và static server đã dừng; khởi động lại đúng preview rồi chạy kiểm thành công. Không coi lỗi môi trường ban đầu là lỗi sản phẩm hoặc kết quả test đạt. Dữ liệu browser là mock, không chứng minh SQL/phân quyền/host thực.

## Giới hạn còn lại

Đây là **phân trang phía client**, không phải SQL pagination: server vẫn aggregate và trả toàn bộ by_category/by_department trong mỗi response. Chỉ giảm DOM mỗi bảng, không chứng minh giảm SQL/payload hoặc hiệu năng DB. Phần BE nhóm trong PHASE11_PAGINATION_AUDIT vẫn mở, cần gate riêng trước thay PHP/endpoint/index. Không gỡ guard danh mục hoặc thay công thức vòng đời.

PHP local8.5.10 dùng render; chưa kiểm8.3.33/layout PHP thật/DB/EXPLAIN/host. DB/host chưa nghiệm thu. Triển khai tương lai cần JS và partial đồng bộ, backup file thay trước upload; rollback source giữ dữ liệu. Chưa tạo gói/deploy hoặc đưa vào delta S11 cũ.
