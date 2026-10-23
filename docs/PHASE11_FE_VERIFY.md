# Phase 11 — Kiểm chứng FE local, 22/10/2026

**Cập nhật23/10:** (f) trong ứng dụng đã được duyệt/build,56 HTTP/Smarty +41 browser error-page đạt, PHASE11_ERROR_HTTP_VERIFY.md. Những dòng F11.4 “chỉ FE” dưới là lịch sử22/10; hiện app có lỗi HTTP/log/ref, **server config/host vẫn chưa kiểm**, F11.4 chưa nghiệm thu toàn bộ.

Chỉ sửa template/CSS/JavaScript và tài liệu. Không sửa PHP, DB, auth, cookie hoặc security headers. BE_GATE_11.md là bản nháp chưa duyệt; DB/host Phase 1–10 chưa nghiệm thu. Không merge/tag Phase 7–10, không push/deploy.

## Trang lỗi F11.4

**F11.4 một phần**: hoàn tất trạng thái lỗi FE khi tải dữ liệu. Trang lỗi HTTP404/500, ghi log vận hành và chính sách display_errors còn thuộc BE_GATE_11 nháp, chưa build/chưa kiểm host.

Khối lỗi tải trang có tiêu đề, hướng xử lý, focus và hành động phù hợp cho mất kết nối, 400/401/403/404/409/422/429/500/503. Nội dung dùng textContent và giới hạn độ dài; không tự retry hoặc gửi lại POST. Lỗi HTML/nonJSON giữ mã HTTP thật, thay vì bị hiểu thành mất kết nối. Luồng chuyển về đăng nhập khi HTTP401 live giữ nguyên. Đây là trạng thái FE khi tải dữ liệu, không phải trang lỗi HTTP/routing mới; thông báo form của từng chức năng giữ nguyên.

## Responsive F11.3

Đo bounding box và scrollWidth tại 1366/768/390 cho chín màn chính, form phân quyền, nhật ký và đăng nhập minh họa. Bảng rộng cuộn trong vùng riêng được phép; test phải đo overflow-x và kích thước thực, không bỏ qua chỉ vì có class table. Không có tràn ngang toàn trang trong các tình huống đã kiểm.

Sửa dòng hướng dẫn cuộn bảng ngân sách và aria-label bị lỗi ký tự. Không đổi công thức hoặc API. Login preview sinh từ template thật, có nhãn MOCK và ngăn submit; không xác thực hoặc gửi dữ liệu. File sinh trong assets/ita/mock không đưa vào gói deploy.

## Lệnh và kết quả thật

Static server workspace tại http://127.0.0.1:18766. Browser session ita-phase06, chạy bằng `playwright-cli -s=ita-phase06 run-code --filename=tests/<file>`:

| File/lệnh | Kết quả | Phạm vi |
|---|---|---|
| ita_phase11_errors_browser.js | 61 đạt | Trạng thái lỗi, escaping, focus, retry thủ công, HTML403/404/503, responsive |
| ita_phase11_responsive_browser.js | 58 đạt | Chín màn static, bounds, nhãn tiếng Việt, không gọi PHP |
| ita_phase11_admin_browser.js | 14 đạt | Admin/audit API mock, login trình bày, không POST auth/access |
| ita_phase03_browser.js | Đạt, 30 kiểm tra | API mock tài sản |
| ita_phase04_browser.js | 30 đạt | Import/export API mock |
| ita_phase05_browser.js | 29 đạt | Phân bổ/thu hồi API mock, CSRF/version/quyền và lỗi |
| ita_phase06_browser.js | 28 đạt | Giấy phép API mock, reauth/CSRF, tự ẩn key, audit lỗi, ba vai trò |
| ita_phase07_browser.js | 64 đạt | Ticket API mock |
| ita_phase08_browser.js | 63 đạt | Vòng đời API mock/static |
| ita_phase09_browser.js | 42 đạt | Ngân sách API mock/static |
| ita_phase10_browser.js | 64 đạt | Dashboard/report/alert API mock |
| php tests/ita_phase03_render.php | Đạt | Smarty thật, escaping, không DB |
| node --check JS mới/sửa; git diff --check | Đạt | Syntax/whitespace |

Test Phase5/6 ban đầu timeout vì dùng vùng/nút cảnh báo cũ. Đã cập nhật fixture và selector theo bảng cảnh báo Phase10 hiện tại; không khôi phục giao diện cũ hoặc bỏ assertion bảo mật giấy phép/phân bổ. Bộ test auth cũ dùng DB fixture không chạy vì ngoài phạm vi không DB.

Số30/29 được đối chiếu lại bằng chạy hai suite22/10. Diff chính xác và phạm vi assertion thay đổi: PHASE11_TEST_DIFF_REVIEW.md. Riêng test6 thay assertion nút điều hướng cũ bằng assertion nội dung cảnh báo; không còn chứng minh thao tác bấm nút “Xem giấy phép” vì bảng hiện tại không có nút đó.

**Giới hạn responsive:** F11.3 đo trên trang tĩnh MOCK. Các suite đổi data-mode và chặn API vẫn dùng HTML từ static server, không phải layout được PHP thật render. Cần chạy lại trên host sau deploy bằng tài khoản thật từng vai trò; chưa ghi PASS cho bước này.

Đã xem ảnh lỗi desktop/mobile, ngân sách390, vòng đời768, login390; ảnh đo các màn nằm trong .playwright-cli (không đóng gói). Impeccable detector không trả finding trên các file lỗi và template ngân sách được kiểm; không suy thành audit sạch toàn ứng dụng. Review Agent rà diff cuối không còn phát hiện cần sửa trong phạm vi FE.

Runtime PHP kiểm chứng là CLI local **8.5.10**, host theo hồ sơ **8.3.33**. **Chưa chạy trên PHP8.3**. Browser mock không chứng minh SQL, session/cookie, HTTP server thực, rollback, concurrency hoặc persistence. Kiểm tải5.000 asset/PDF500 tại PHASE11_LOAD_TEST_PLAN.md **chưa chạy**. Các danh sách cần phân trang BE nằm trong PHASE11_PAGINATION_AUDIT.md, chỉ báo cáo, chưa sửa backend.
