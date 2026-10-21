# Kiểm chứng local vòng đời — 21/10/2026

## Phạm vi đã build

- F8.1: tuổi theo số ngày thực / 365,25 và tỷ lệ tuổi trên tuổi thọ.
- F8.2: điểm thay thế với ba thành phần, cấu hình và giải thích.
- F8.3: tuổi trung bình, số thiết bị có sửa chữa và tỷ lệ lỗi theo loại/bộ phận.
- F8.4: danh sách lọc theo phạm vi, phân trang 20 dòng, điểm gốc giảm dần và chi tiết giải thích.

Admin xem toàn store; Quản lý theo scope; Hỗ trợ bị từ chối 403 trước truy vấn. Hai action GET là `lifecycle` và `lifecycle-detail`; POST bị outer gate từ chối. Không ghi dữ liệu, tự thanh lý, thêm package hoặc sửa schema.

SQL dùng CTE: eligible → repair → base → ratios → scored → graded. Chỉ tính phần cứng chưa thanh lý/chưa xóa mềm; vẫn giữ loại inactive đang được tham chiếu. Ticket repair tính theo opened_at trong 12 tháng giờ VN, không JOIN events. Điểm SQL dùng DECIMAL; không ROUND khi sort hoặc so ngưỡng. DTO hiển thị đúng 3 chữ số và giữ chuỗi điểm gốc trong JSON/detail. Meter dùng cùng thang trên trang, hỗ trợ điểm trên 1.

## Kết quả thật

| Lệnh | Loại / kết quả |
|---|---|
| `php tests/ita_phase08_unit.php` | 68 pure/scripted checks đạt, adapter giả, không DB |
| `php tests/ita_phase07_unit.php` | 236 pure/scripted checks đạt |
| `php tests/ita_phase03_render.php` | Smarty thật render/escape đạt, không private config/DB |
| `playwright-cli -s=ita-phase06 run-code --filename=tests/ita_phase08_browser.js` | 37 browser API mock/static checks đạt |
| `playwright-cli -s=ita-phase06 run-code --filename=tests/ita_phase07_browser.js` | 64 browser API mock checks đạt, hồi quy khung/menu |

Browser kiểm ba vai trò, hiển thị 3 chữ số, điểm gốc, meter trên 1, phân trang/bộ lọc, rỗng, lỗi cấu hình, retry GET, escaping, static MOCK không gọi API production và viewport 1366/768/390. Đã đo bounds và xem ảnh desktop/mobile; bảng thiết bị chuyển thành khối thông tin trên mobile, bảng nhóm cuộn trong vùng riêng. PHP lint các file mới/sửa, JS syntax, diff-check đạt. Impeccable detector trả `[]`. Review Agent rà backend/query và FE chưa phát hiện lỗi cần sửa. Những kết quả này không phải kiểm chứng SQL thực thi.

## Contract cấu hình chỉ đọc

Đọc năm khóa string trong ita_settings theo store:

| Khóa | Mặc định khi vắng |
|---|---|
| lifecycle_weight_age | 0.5 |
| lifecycle_weight_repairs | 0.3 |
| lifecycle_weight_cost | 0.2 |
| lifecycle_replace_threshold | 0.8 |
| lifecycle_watch_threshold | 0.6 |

Khóa vắng dùng mặc định riêng của khóa đó; màn hình báo đang dùng mặc định. Khóa có NULL/rỗng hoặc không phải số thập phân không âm với tối đa 6 chữ số lẻ trả 422. Ba trọng số trong [0,1], tổng đúng 1 (kiểm bằng số nguyên phần triệu); ngưỡng theo dõi nhỏ hơn ngưỡng thay thế, tối đa 10. Không tự tạo khóa hoặc thêm form lưu cấu hình. Seed cũ không có năm khóa này nên dùng mặc định là bình thường.

## Giới hạn bắt buộc giữ

«Phase 1–7 chưa có xác nhận kiểm thử DB chi tiết (CHECK ticket_events, rollback InnoDB, hai phiên đồng thời, phiên bản MySQL/MariaDB, môi trường chưa được cung cấp)»

Phase 7/8 chưa nghiệm thu DB. Không merge/tag hoặc deploy. Chưa chạy CTE, casts, DECIMAL, aggregate và EXPLAIN trên DB thật. Các SELECT riêng có thể thấy cập nhật giữa các lần đọc; đây là dữ liệu hiện tại, không phải snapshot báo cáo chốt. Lookup có giới hạn 501 và trả lỗi khi vượt 500 loại; không âm thầm cắt danh sách. Nếu cần index mới phải dừng ở gate SQL riêng.


## Tinh chỉnh giao diện cùng ngày — sau checkpoint 4929140

- Tổng quan chia rõ nhãn/số liệu, ngày ngắn gọn. Ghi chú dùng mặc định chuyển thành dòng thông tin kèm nút mở cách tính, không đổi route.
- Bộ lọc chính gồm từ khóa và đánh giá; bộ phận/loại nằm trong phần mở rộng, có số bộ lọc đang áp dụng và nút xóa lọc về trang1.
- Điểm/đánh giá nằm cạnh tên thiết bị. Thanh màu dùng semantic tokens và thang chung trên trang; điểm vẫn đúng3chữ số và giữ raw cho phân loại/server sort.
- Bảng nhóm chuyển loại/bộ phận bằng button aria-pressed, hỗ trợ bàn phím. Dùng dữ liệu đã trả, không đổi aggregate hoặc phát sinh API.
- Chi tiết tách ba thành phần, có tên thiết bị, dòng được chọn và phần mở rộng giữ chính xác điểm gốc. Đóng trả focus về nút dòng đã mở.
- Motion180ms trong ita-motion.css, chỉ transform/opacity; tắt theo cờ hiện có hoặc prefers-reduced-motion. Mục tiêu bấm tối thiểu44px ở mobile.

`playwright-cli -s=ita-phase06 run-code --filename=tests/ita_phase08_browser.js`: **63 checks đạt**, API mock/static, không DB. Đã kiểm thao tác mới, read-only retry, quyền ba vai trò, raw score, XSS text và bounds1366/768/390. `node --check assets/ita/js/ita-lifecycle.js`, Smarty thực render/escape, diff-check đạt. Detector[]; xem ảnh desktop/mobile; review độc lập chưa thấy lỗi cần sửa.

Gói `ita-lifecycle-ui-update.zip` là bản cộng dồn Phase8, gồm12runtime files (11file gốc và ita-motion.css), không cần cài gói vòng đời cũ trước. Nền yêu cầu Phase7 qua2aa3c98. Giữ backup/rollback và hướng dẫn PHASE08_HOST_TEST.md; không SQL/config/seed/mock trong runtime. Agent không deploy hoặc đổi trạng thái nghiệm thu DB.
