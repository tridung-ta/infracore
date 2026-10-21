# Dự báo và ngân sách — kiểm chứng local, 21/10/2026

Gate #09 được user duyệt kèm snapshot khi lưu và dự phòng cố định 10%. Không DDL, không kết nối/tạo DB, không deploy, không merge/tag.

## Hành vi đã xây dựng

- Thay thế = số thiết bị đạt điểm gốc theo ngưỡng vòng đời HOẶC đến tuổi thọ vào cuối năm chọn × giá thay thế theo loại. Điều kiện cùng đúng vẫn đếm một asset một lần.
- Bảo trì = chi phí hiện tại của phiếu repair/periodic resolved/failed mở trong ba năm lịch hoàn chỉnh trước năm hiện tại / 3, theo tập thiết bị hiện tại của từng bộ phận/loại. Đây là phép rút gọn của (chi phí / (3 × số thiết bị)) × số thiết bị; không làm tròn trung gian. Không phải tồn kho lịch sử. Không có phiếu thì chỉ dùng tỷ lệ Admin đã cấu hình × đơn giá × số thiết bị. Chi phí 0 có chứng cứ khác với thiếu dữ liệu.
- Mua thêm = ceil(nhân sự tăng × thiết bị/người) × đơn giá. Không trừ tồn kho. Không có kế hoạch khác với kế hoạch tăng 0.
- Tiền là chuỗi thập phân, không float; HALF_UP từng dòng đến 2 số lẻ; dự phòng = 10% cộng ba thành phần đã làm tròn. Khi thiếu dữ liệu, hiển thị tổng phần đã tính, không ngân sách hoàn chỉnh. Giá/định mức được đặt rõ bằng 0 là số 0 hợp lệ.
- Admin IT lưu đơn giá/định mức/tỷ lệ dự phòng bảo trì. Quản lý được xem và lưu kế hoạch trong bộ phận phụ trách; Hỗ trợ bị từ chối server. Key cấu hình mới forecast_category_parameters trong ita_settings, JSON theo category ID. Không ghi key dự phòng cũ: dự phòng ngân sách cố định 10% theo duyệt mới nhất.
- Mỗi lần lưu kế hoạch tạo snapshot schema_version=1: thời điểm, công thức, đầu vào giá/định mức/nhân sự, ngưỡng vòng đời, đầu vào từng thiết bị và cohort chi phí, dòng tiền và tổng. Giá/config/ticket đổi không làm thay snapshot. Bản nháp chỉ thay khi xác nhận tính lại với version. Bản finalized chỉ đọc; không có nút chốt/mở lại trong phạm vi này. Snapshot cũ thiếu/sai báo 409, không tự dùng số hiện tại.
- Snapshot lưu toàn bộ loại của một bộ phận, không lấy bộ lọc loại khi lưu. GET/export snapshot có thể lọc loại từ JSON đã lưu, tính tổng từ các dòng đã đóng băng. Không đọc giá/ticket mới để xuất bản đã lưu.
- POST+CSRF, kiểm lại actor/revision/scope; khóa actor → scope → categories theo ID → settings → department → plan. Transaction cần REPEATABLE READ hoặc SERIALIZABLE; cấu hình khác từ chối trước ghi. Lỗi audit rollback. Trùng/khóa chờ/deadlock trả 409, không tự gửi lại.
- Giới hạn 500 loại, 500 bộ phận, 1.000 dòng ngân sách, 2.000 thiết bị, snapshot 2 MB; vượt thì báo lỗi, không cắt ngầm. Thiết bị chưa bộ phận làm tổng thiếu dữ liệu và hiện số lượng, không âm thầm phân bổ.
- Excel ghi mọi ô bằng STRING tường minh, không công thức. PDF font DejaVu tiếng Việt, chặn tài nguyên HTTP/file ngoài. Audit trước phản hồi file, lỗi audit không trả file. Trình duyệt kiểm MIME và chữ ký PDF/ZIP, từ chối HTML 200.

## Kết quả thật

| Loại | Lệnh | Kết quả |
|---|---|---|
| Thuần/scripted | php tests/ita_phase09_unit.php | 134 kiểm tra đạt; double mô phỏng transport/transaction, không chứng minh rollback DB thật |
| Trình duyệt mock/static | playwright-cli -s=ita-phase06 run-code --filename=tests/ita_phase09_browser.js | 42 kiểm tra đạt; ba vai trò, xác nhận, CSRF/payload, snapshot, lỗi/giữ input/không retry POST, HTML 200/chữ ký sai, nhãn tiếng Việt/ghi chú mặc định, bounds 1366/768/390, không JS runtime error |
| File thật bằng dữ liệu giả | php -d extension=gd tests/ita_phase09_exports.php | XLSX mở lại, chuỗi =HYPERLINK vẫn STRING; PDF tạo thành công |
| PDF | Poppler pdftoppm -scale-to 1600 -png -singlefile; Python bundled pypdf | Đã xem ảnh PDF một trang; tiếng Việt rõ, không chồng/cắt bảng; text có xuống dòng được chuẩn hóa whitespace để kiểm chuỗi tiếng Việt |
| Hồi quy | php tests/ita_phase07_unit.php; php tests/ita_phase08_unit.php; php tests/ita_write_routes.php | 236 + 68 + 12 kiểm tra đạt, không DB |
| Template/syntax | php tests/ita_phase03_render.php; php -l các PHP mới/sửa; node --check JS | Đạt; Smarty thật, không DB |
| UI | Ảnh browser desktop/mobile, đo bounds, Impeccable detect target template | Đã xem/đo; detector không in finding |
| Rà độc lập | Review Agent, đọc diff PHP/FE | Đã sửa cờ saved, số 0, cấu trúc snapshot, lọc snapshot và kiểm file; không DB |

Cảnh báo thật: PhpSpreadsheet/ZipStream đang có deprecation trên PHP local khi tạo/đọc Excel; output file vẫn kiểm được. Không sửa vendor hoặc hạ error_reporting để giấu cảnh báo. Cần kiểm cấu hình production không in cảnh báo vào bytes tải file; nếu nâng dependency cần gate riêng. PDF GD chỉ bật per-process local, không đổi php.ini.

## Chưa chạy

DB integration/UAT, EXPLAIN thật, kiểm index, rollback/audit lỗi trên InnoDB thật, hai phiên cùng sửa config/kế hoạch, export trên PHP-FPM host và tải lớn chưa chạy. Không dùng kết quả mock thay PASS DB. Phase 7/8 và phần ngân sách chưa nghiệm thu DB.

SQL đọc chính xác đã xuất tại PHASE09_EXPLAIN.sql (4 query, hai vai trò); generator không thực thi SQL. Nếu EXPLAIN cần index mới phải gate SQL riêng.
