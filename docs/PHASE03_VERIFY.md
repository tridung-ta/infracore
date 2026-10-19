# Phase 3 — Build và bàn giao để user test host

Ngày 19/10/2026. User duyệt build BE-GATE #03, yêu cầu không tạo DB test. Agent không tạo DB, không chạy SQL, không kết nối DB host và không deploy. Không cần chạy lại SQL Phase 1/2 hoặc migration mới.

Chỉ đạo tiếp theo: hoàn thành trong phạm vi local, chưa upload; kiểm DB để user tự làm sau. Gói snapshot `b965bd30a9b4` trước đó đã cũ so với các sửa local cuối, không dùng để deploy. Chưa có bản release production được nghiệm thu.

## Đã build

| Chức năng | Hành vi |
|---|---|
| F3.1 | Danh sách phân trang server hiện có (5 dòng/trang), scope store/bộ phận tiếp tục áp dụng |
| F3.2/F3.3 | Tạo/sửa mã, tên, loại, bộ phận, hãng, model, seri, cấu hình, OS, ngày/giá mua, bảo hành, nhà cung cấp, vị trí |
| F3.4 | Chuyển trạng thái theo quy tắc 4.2, bắt buộc lý do; Hỗ trợ không thanh lý; ghi chú riêng |
| F3.5 | Admin xóa mềm có xác nhận và lý do, không hard delete; mã vẫn được giữ duy nhất |
| F3.6 | Tab thông tin/lịch sử; lịch sử 50 thao tác mới nhất, Admin xem thêm ở Nhật ký |
| F3.7 | Bộ lọc loại, bộ phận, năm mua, khoảng ngày hết bảo hành, trạng thái và từ khóa |
| F3.8 | Admin thêm/sửa/ngừng dùng loại; giữ nguyên metadata khác và hồ sơ đang dùng loại inactive |
| F2.5 | Các lệnh ghi tài sản gọi audit trong cùng transaction, lưu trước/sau bằng allowlist không credential |

Admin toàn store; Quản lý tạo/sửa trong các bộ phận được gán; Hỗ trợ chỉ trạng thái/ghi chú. Luồng duyệt thanh lý của Quản lý chưa được build; không cho phép thanh lý trực tiếp thay thế quy trình duyệt. Không có Clear Trash/khôi phục xóa mềm, chuyển phân bổ hoặc sửa current_user_id ở Phase 3. Chuyển bộ phận/thanh lý/xóa mềm bị chặn nếu còn current_user_id hoặc phân bổ chưa thu hồi. Tạo mới mặc định in_use theo schema hiện tại, chưa có phân bổ.

Ghi dùng khóa bản ghi và version hash nội dung chống lost update; request version cũ trả 409. Trùng mã (kể cả xóa mềm) trả 409. Ngày/giá/độ dài validate server; giá xử lý chuỗi decimal, không float. Query cột ghi là allowlist. Runtime API mới: GET categories, POST asset-save/category-save; CSRF bắt buộc, store/actor từ session. Mã cũ và cấu hình private giữ nguyên.

## Kết quả tự động và giới hạn

- PHP lint các file thay đổi: đạt.
- `php tests/ita_phase03_unit.php`: 33 pure/scripted checks đạt; adapter mô phỏng transaction/SQL interface, **không chứng minh transaction DB, cú pháp SQL hoặc lock thực**.
- `php tests/ita_phase03_render.php`: Smarty thật render form tài sản/danh mục và escape output đạt, không DB.
- `node --check assets/ita/js/ita-assets.js` và shell: đạt.
- `playwright-cli -s=ita-phase03 run-code --filename tests/ita_phase03_browser.js`: 28 checks browser với API mock đạt. Form tạo 1366/768/390, payload và version, tab lịch sử, XSS text, category inactive giữ khi edit/ẩn khi create, lỗi API hiển thị, xóa xác nhận/hủy, filter query và controls của ba vai trò. Không phải đăng nhập/HTTP-PHP-DB E2E.
- Đã xem ảnh form mobile; đo page scrollWidth/editor width trong test. Impeccable detector một lần trên JS/CSS/form: `[]` (không findings).
- Hồi quy static `assets/ita/preview/verify-ui.js`: lọc/phân trang/empty/modal/alerts, 1366/768/390, lỗi mạng/retry và confirm escape đạt. Console có lỗi mạng cố ý tạo trong test; không phải kiểm DB.
- Review Agent phát hiện danh mục inactive không sửa được hồ sơ đang dùng; đã sửa UI và service, pure/scripted và browser kiểm trường hợp này đạt. Không truy cập DB trong review.
- Chưa chạy MariaDB/PHP host, EXPLAIN, benchmark 500 dòng, duplicate DB constraint thực, concurrency, audit rollback DB hoặc UAT Phase 3. Không đánh dấu nghiệm thu phase hoàn tất.

### Chốt phạm vi local

Cập nhật sau đọc lại Word: pure/scripted suite **36 checks đạt** (thêm 3 case khoảng bảo hành); browser vẫn **30 API mock checks đạt**. Helper field dùng 3 tham số thay 5; đã chạy lại form create/edit/status và các vai trò sau thay đổi. ERD/PERF_NOTES/RUN_LOCAL đã được cập nhật. Các số 33/28 dưới đây giữ làm lịch sử lượt kiểm trước.

- Lần cuối `tests/ita_phase03_browser.js`: **30 checks API mock đạt** (thay cho số 28 ban đầu). Bổ sung trạng thái trống bị chặn trước request và Support ghi chú được khi categories API lỗi; lỗi tải danh mục không che success lưu tài sản.
- `tests/ita_phase03_unit.php`: **33 pure/scripted checks đạt**; Smarty render và kiểm escape đạt. PHP/JS syntax check đạt.
- Hoàn tất implementation và kiểm chứng trong phạm vi local không DB. Các bước cần SQL/DB thật đã tách ở PHASE03_HOST_TEST.md, chưa chạy và chưa được tính đạt. Không yêu cầu user tạo DB test hoặc upload ngay.

## Thứ tự triển khai thủ công

1. Backup DB và source host ra ngoài repo. Giữ cấu hình `includes/ita_database.local.php` và auth_key hiện có.
2. Gói bàn giao là bản cập nhật cho hosting đã chạy Phase 2, không phải source cài mới. Tải/extract gói trên máy rồi upload nội dung runtime vào `/domains/infracore.dung.derasoft.com/public_html`, giữ cấu trúc thư mục. Không upload tests, dump, git, private config hoặc manifest vào web root.
3. Upload trong thời gian không có người chỉnh tài sản, theo bộ đồng bộ: PHP services/DAO trước, bootstrap + application + route gate tiếp, JS/CSS/template cuối. File mới phải đủ trước khi bootstrap load. Nếu không thể cutover đồng bộ, tạm ngừng nhận thao tác trong khoảng upload.
4. Ctrl+F5, đăng nhập và mở Tài sản CNTT. Nếu 503: dừng test ghi, báo URL/thời điểm/lỗi; không chạy lại CREATE hoặc xóa dữ liệu.
5. Làm checklist PHASE03_HOST_TEST.md. Gửi bước/kết quả thực để cập nhật nghiệm thu.

## Hoàn tác

Phục hồi các file runtime trước đợt upload từ bản backup. Không đổi auth_key hoặc bật legacy làm lối tắt. Giữ nguyên tài sản/log đã tạo; xóa mềm tài sản test bằng ứng dụng nếu phù hợp, không dùng DELETE/DROP. Không import dump ghi đè để chữa lỗi giao diện. Log ghi mới có thêm category.update, bản cũ vẫn xem được qua Tất cả thao tác.
