# S11-01/S11-02 — Kiểm chứng local, 22/10/2026

## Bổ sung23/10 — hồi quy bảo mật không DB

Suite forecast export hiện **30 checks** (thêm Quản lý bình thường, scope thu hồi và dòng ngoài bộ phận dù actor còn scope); controller export hiện **54 checks** (thêm source staging mất/revision đổi trong render). Chạy PHP CLI local, transport giả, không DB/HTTP thật. Các con số21/48 bên dưới là lần kiểm22/10, giữ để truy vết. Không dùng kết quả bổ sung để ghi host/DB đạt.

User duyệt «Duyệt BUILD S11-01 và S11-02 trước». Không DB/host, không thêm audit GET, không config/rewrite/header/auto_prepend, không merge/tag7–10/push/deploy. PHP CLI local8.5.10; PHP8.3/HTTP/PHP-FPM thật chưa chạy.

## S11-01 — forecast-export

ItaForecastService::auditExport kiểm native id/store/status=1 trong transaction sau render, trước audit thành công. Null/query lỗi bị từ chối và không đến bytes; giữ kiểm role/revision/scope/snapshot hiện có. Không coi MyISAM native được khóa nguyên tử với InnoDB.

- `php tests/ita_phase11_forecast_export.php`: **21 scripted checks đạt**, synthetic render rồi gọi service thật; baseline, disable/missing/store không khớp (mô phỏng truy vấn không tìm thấy), query lỗi, audit lỗi, role đổi. Kiểm output rỗng/audit thành công0 khi thất bại, rollback double. Không phải HTTP/header hoặc DB concurrency test.
- `php tests/ita_phase09_unit.php`: **134 đạt**; fixture thêm đúng SELECT native actor1/store1/status1, không trả thành công mọi query lạ.
- PHP lint service/test/fixture đạt. Review Agent độc lập không có actionable finding ở bản S11-01; lần rà đầu chạy15 check, sau bổ sung missing/store tổng21 được agent chính chạy lại.

`itaapplication` vẫn render→auditExport→set download headers/echo; không thay endpoint/payload. Host cần user kiểm thủ công sau, chưa nghiệm thu DB.

## S11-02 — asset-export/import-errors

Controller giữ workbook trong memory, rồi đọc lại native active/store +ITA role/revision và bộ phận active. Thu hồi quyền/query lỗi chặn trước download. asset-export đọc lại đúng tập dòng theo filter/scope sau render; khác membership/dữ liệu →409, không trả file cũ. Đây là semantics “dữ liệu hiện tại không đổi khi render”, không tự biến thành snapshot lịch sử.

import-errors lấy fingerprint batch/store/user/source/revision trước render, đọc lại với actor mới sau render; expiry từ chối410 kể cả batch completed đã hết hạn, khác fingerprint409. Không thêm audit mới, không đổi GET/CSRF hoặc import-template trống. Audit GET/amplification và report-export từng asset đổi ownership vẫn là việc riêng còn mở, không coi S11-02 đã giải quyết chúng.

- `php tests/ita_phase11_import_export.php`: **48 scripted checks đạt**, controller/DAO/service thật, chỉ renderer/storage/DB thay bằng double. Mô phỏng native disable/query lỗi, role/revision/scope đổi, asset đổi tập dòng, batch expiry/owner/fingerprint đổi; assert status, output bytes và thực sự đọc identity sau render. Không gọi DB hoặc tạo staging/file download thật.
- `php tests/ita_phase04_unit.php`: **88 checks đạt** với XLSX thật/dữ liệu giả, pure rules/scripted adapter, không DB.
- `php tests/ita_write_routes.php`: **13 routing checks đạt**, không đổi route/allowlist POST.
- PHP lint hai import file và test đạt; git diff --check đạt. Review Agent độc lập chạy48+21 kiểm tra, không có actionable finding trong diff.

HTTP headers không được chứng minh bởi CLI test; module/app error envelope và thứ tự headers/echo đã đối chiếu source. Chưa kiểm native MyISAM race thật, transaction DB, PHP-FPM8.3, HTTP/file trên host. Không tuyên bố thu hồi quyền nguyên tử tại thời điểm truyền hết file: vẫn có khoảng sau recheck cuối; đây là kiểm lại có giới hạn trước bytes.
