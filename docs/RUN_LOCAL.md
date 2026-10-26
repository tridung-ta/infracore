# Chạy InfraCore local — cập nhật Phase 4

## Hướng dẫn hiện hành —26/10/2026

File này giữ ghi chú preview và lịch sử Phase1–5; không dùng các trạng thái cũ như “PDF chưa nối” để kết luận source hiện tại. Dựng runtime thật bằng Laragon Apache/PHP8.3/MariaDB10.6/HTTPS theo **[LOCAL_RUN.md](LOCAL_RUN.md)**. Kiểm DB theo [LOCAL_DB_TEST_PLAN.md](LOCAL_DB_TEST_PLAN.md); grants/locking theo [LOCAL_LOCK_GRANTS_REVIEW.md](LOCAL_LOCK_GRANTS_REVIEW.md), hai phiên theo [LOCAL_CONCURRENCY_RUN.md](LOCAL_CONCURRENCY_RUN.md).

Hiện chưa có đường dẫn PHP8.3 thực được cung cấp và chưa xác nhận schema: **S26/unit/export8.3 và DB integration CHƯA CHẠY**. Khi có runtime, S26 chạy đầu tiên, đối chiếu dummy/default algo-cost thực. Agent chỉ kết nối DB sau câu **«Tôi xác nhận cho chạy trên DB local [tên DB]»**. Không chạy fixture/reset DB cũ hoặc lấy config/credential host; không đóng gói/upload/merge/tag7–10/push/deploy. Các lệnh dùng `php` dưới đây là ghi chú cũ, không chứng minh đang dùng8.3.

Phase 5: mở `http://127.0.0.1:18766/#assignments` sau build preview (vẫn MOCK, không ghi). Backend đã nối theo BE-GATE #05 nhưng cần auth/DB để chạy runtime thật, không dùng static preview thay thế UAT. PDF chưa nối. Kiểm local không DB:

```powershell
php tests/ita_phase05_unit.php
node --check assets/ita/js/ita-assignments-live.js
playwright-cli -s=ita-phase03 run-code --filename tests/ita_phase05_preview.js
playwright-cli -s=ita-phase03 run-code --filename tests/ita_phase05_browser.js
```

Xem PHASE05_VERIFY.md và PHASE05_HOST_TEST.md. Không chạy fixture/reset/integration DB cũ.

PDF F5.4 (20/10): vendor local đã cài từ composer.lock, GD có sẵn nhưng chỉ bật riêng cho process kiểm thử:

```powershell
php -d extension=gd tests/ita_phase05_pdf.php
php tests/ita_phase05_pdf.php --unavailable
playwright-cli -s=ita-phase03 run-code --filename tests/ita_phase05_pdf_browser.js
```

Parser `tests/ita_phase05_pdf_text.py` cần Python có pypdf/pdfplumber (đã chạy bằng Python bundled); Poppler render các PDF giả trong tmp/pdfs để nhìn từng trang. Không commit tmp/pdfs. Môi trường khác phải `composer install --no-dev --no-scripts --no-plugins` ở classes/ita-pdf bằng PHP có GD/mbstring; không ignore platform requirement. Xem classes/ita-pdf/README.md và PHASE05_PDF_VERIFY.md. Static preview không sinh PDF từ DB; browser test mock transport, PHP CLI mới render file thật bằng dữ liệu giả.

## Cách dùng hiện tại, không cần DB

Hướng dẫn shell MOCK bằng PHP phía dưới là lịch sử Phase 1, đã lỗi thời sau khi thêm auth Phase 2. Không dùng runtime PHP để xem preview không DB.

Tại `D:\InfraCore`:

```powershell
node assets/ita/preview/build-preview.cjs
node assets/ita/preview/serve-preview.cjs
```

Mở `http://127.0.0.1:18766/`: static preview, 18 tài sản minh họa, không xác thực/lưu thật hoặc đọc config private.

```powershell
php tests/ita_phase03_unit.php
php tests/ita_phase03_render.php
node --check assets/ita/js/ita-assets.js
playwright-cli -s=ita-phase03 open http://127.0.0.1:18766/
playwright-cli -s=ita-phase03 run-code --filename tests/ita_phase03_browser.js
php tests/ita_phase04_unit.php
node --check assets/ita/js/ita-import-live.js
playwright-cli -s=ita-phase03 run-code --filename tests/ita_phase04_preview.js
playwright-cli -s=ita-phase03 run-code --filename tests/ita_phase04_browser.js
```

Các script trên không tạo/kết nối DB; browser tự mock API. Không chạy fixture/reset, database/tests/phase01_schema.php hoặc SQL integration cũ vì chúng dùng DB. Không trỏ config local về host. Kiểm port/process trước khi mở server, không dừng tiến trình khác tùy tiện.

Phase 4 static: mở `http://127.0.0.1:18766/#imports`; chỉ MOCK, không đọc Excel hoặc ghi dữ liệu. Kiểm XLSX thật chạy bằng PHP unit CLI với file giả trong temp; không phải integration. Runtime đã xác thực dùng service thật sau khi user quyết định triển khai. Xem `PHASE04_VERIFY.md` và `PHASE04_HOST_TEST.md`.

Runtime PHP thật từ Phase 2 yêu cầu auth/HTTPS/DB; cờ legacy không dùng để bỏ qua auth. Các ghi chú dưới đây giữ làm lịch sử, không phải chỉ dẫn vận hành hiện tại.

## Lịch sử Phase 1

Từ thư mục gốc repo:

```powershell
php -S 127.0.0.1:18768 router.php
```

Mở http://127.0.0.1:18768/admin.php?op=ita. Dữ liệu vẫn MOCK. Không cần cấu hình hoặc kết nối DB để xem khung này; không coi đây là bản quản trị đã có đăng nhập.

Cờ do operator quản lý, không phải URL query:

- Có .ita-enabled, không có .ita-legacy-enabled: khung ITA; route cũ ẩn, POST bị chặn.
- Có cả hai: ITA_LEGACY_UI_ENABLED=true; đi lại đường bootstrap legacy. Chỉ operator bật sau khi xác nhận config/DB đúng; đây không phải test tự động với hosting.
- Không có .ita-enabled: cơ chế .legacy-hidden trước đó vẫn áp dụng. Giữ file .legacy-hidden để fallback an toàn.

Chưa upload gói này lên host; Apache rewrite, host PHP 8.3 và legacy restoration cần kiểm riêng. Không upload config/dump/backup/test từ workspace. Bản gốc các file routing đã lưu ngoài cây runtime tại .source-backup/ita-phase01-routing trong workspace local.
