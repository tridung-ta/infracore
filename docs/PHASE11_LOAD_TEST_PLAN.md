# Kế hoạch kiểm tải trên DB test — chưa chạy

Ngày22/10/2026. Chỉ là kế hoạch, **không tạo/kết nối DB, sinh seed, chạy tải hoặc đo số liệu trong phiên này**. DB test phải được user xác nhận riêng; không dùng production làm môi trường phát triển/kiểm tải. Phần instrumentation PHP/config/index mới phải chờ BE-GATE #11 (SQL mới có gate riêng khi biết index cụ thể).

## Điều kiện trước khi chạy

1. User xác nhận DB test cô lập, phiên bản MySQL/MariaDB, PHP-FPM8.3.33 hoặc runtime tương đương host, CPU/RAM, memory_limit và timeout; ghi source commit, backup và cách bỏ fixture không đụng dữ liệu thật.
2. Kiểm **session isolation của kết nối ứng dụng** RR/SERIALIZABLE trước dashboard; phpMyAdmin session không đại diện PHP. GD/mbstring, temp ngoài webroot, PDF/Excel/font đầy đủ, display_errors Off/log_errors On. Không đo khi request chỉ đang trả503 do thiếu cấu hình.
3. Quyền Admin và Quản lý/Hỗ trợ dùng tài khoản giả; không nhập dữ liệu cá nhân/key thật. Không coi dữ liệu MOCK trình duyệt là tải SQL.

## Bộ dữ liệu dự kiến

- Khoảng5.000 tài sản, nhiều bộ phận/loại/năm mua/trạng thái, có NULL/ngày tương lai/retired/deleted; vẫn trong giới hạn nhóm500. Phân bố lệch để một bộ phận có nhiều thiết bị.
- Cỡ0/500/2.500/5.000 asset để so độ tăng;5.001 phải từ chối rõ, không cắt. Counts tính tay từ fixture, không chỉ đo nhanh.
- Ticket repair lặp trên cùng asset, periodic, pending/resolved/failed, opened_at ở biên12 tháng và cuối tháng; không tạo lại ID khi mở lại. History đủ để đo preaggregate và nhánh awaiting_ready.
- License allocation trong/ngoài scope và nhiều lý do trên một đối tượng. Không cần key thật.
- PDF tồn kho đúng500 dòng, thêm case501 phải từ chối; text tiếng Việt dài. Excel biên5.000 và5.001, báo cáo khác1.000/1.001. Budget giữ giới hạn hiện có2.000 asset/1.000 dòng; bộ dữ liệu5.000 không được dùng để âm thầm bỏ guard này, cần lọc bộ phận trước.

## Các lượt đo dự kiến

| Lượt | Tải | Số lần đề xuất | Ghi lại |
|---|---|---|---|
| Kiểm đúng dữ liệu | Một request từng endpoint và vai trò | 1–3 | HTTP/status/body, counts/scope/as_of, thiếu dữ liệu, loại trừ retired, mẫu số |
| Dashboard lạnh | Request đầu sau cách làm lạnh cache đã xác nhận | 3 | Ghi rõ phương pháp; không tự restart DB/FPM hoặc flush cache dùng chung |
| Dashboard ấm | 1 client, bộ lọc cố định ở từng cỡ dữ liệu | 20 | min/median/p95/max, time_total, TTFB, bytes, HTTP/lỗi |
| Đồng thời | 1→5→10 client đọc, user chủ động giới hạn | 60s/mức | Throughput, p95, timeouts, CPU/DB/FPM memory; dừng nếu ảnh hưởng dịch vụ |
| PDF500 | 1 client;500 dòng tiếng Việt rồi501 | 5 | Render+audit+download total, PHP peak memory, bytes/trang, font/cắt bảng;501 không trả file |
| Xuất/scope đổi | Môi trường test kiểm quyền bị thu hồi/audit lỗi | Kịch bản có kiểm soát | Không bytes file khi thất bại; không tự retry POST; đối chiếu audit |

Các cỡ/lượt là đề xuất, không phải kết quả hoặc SLA đã chốt. Mốc sàng lọc ban đầu để thảo luận: dashboard ấm p95≤2s, PDF500≤15s, peak PHP≤75% memory_limit; phải user chốt theo hạ tầng trước khi đánh giá PASS/FAIL hiệu năng.

## Thu thập bộ nhớ và truy vấn

- HTTP client có thể đo time_starttransfer/time_total/size_download/http_code; **RSS của curl/browser không phải bộ nhớ PHP**.
- PHP peak cần số liệu từ observer/APM/FPM logging đã có và được phép. Nếu chưa có instrumentation hợp lệ, ghi **bộ nhớ chưa đo**, trình phần instrumentation riêng trong gate #11; không thêm endpoint public hoặc sửa PHP trong bước lập kế hoạch.
- Dùng PHASE10_EXPLAIN.sql và PHASE09_EXPLAIN.sql với đúng scope/fixture. Chưa thực thi. EXPLAIN ANALYZE có thể chạy query thật, cần xác nhận DB test trước. Lưu row estimates/actual nếu có, scan/sort/temp table và tổng rows examined; không suy index tốt từ unit test.
- Cần index mới: đưa tên bảng/cột/thứ tự/index hiện tại, EXPLAIN trước/sau dự kiến, chi phí ghi/backup/rollback vào gate SQL riêng. Không tự tạo index từ kế hoạch này.

## Mẫu biên bản chưa có số liệu

| Source / runtime / DB test | Dataset / client / cache | Endpoint | HTTP | median/p95/max | PHP peak / memory_limit | Counts/scope đúng | Kết luận |
|---|---|---|---|---|---|---|---|
| Chưa xác nhận | Chưa tạo/chưa chạy | Dashboard5.000 | Chưa đo | Chưa đo | Chưa đo | Chưa kiểm | Chưa chạy |
| Chưa xác nhận | PDF500 | report-export | Chưa đo | Chưa đo | Chưa đo | Chưa kiểm | Chưa chạy |

Không lấy mock/local PHP8.5 hoặc file PDF một trang đã xem làm PASS cho kế hoạch tải này. Không merge/tag7–10/push/deploy.
