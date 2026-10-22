# S11-01/S11-02 — Kiểm chứng local, 22/10/2026

User duyệt «Duyệt BUILD S11-01 và S11-02 trước». Không DB/host, không thêm audit GET, không config/rewrite/header/auto_prepend, không merge/tag7–10/push/deploy. PHP CLI local8.5.10; PHP8.3/HTTP/PHP-FPM thật chưa chạy.

## S11-01 — forecast-export

ItaForecastService::auditExport kiểm native id/store/status=1 trong transaction sau render, trước audit thành công. Null/query lỗi bị từ chối và không đến bytes; giữ kiểm role/revision/scope/snapshot hiện có. Không coi MyISAM native được khóa nguyên tử với InnoDB.

- `php tests/ita_phase11_forecast_export.php`: **21 scripted checks đạt**, synthetic render rồi gọi service thật; baseline, disable/missing/store không khớp (mô phỏng truy vấn không tìm thấy), query lỗi, audit lỗi, role đổi. Kiểm output rỗng/audit thành công0 khi thất bại, rollback double. Không phải HTTP/header hoặc DB concurrency test.
- `php tests/ita_phase09_unit.php`: **134 đạt**; fixture thêm đúng SELECT native actor1/store1/status1, không trả thành công mọi query lạ.
- PHP lint service/test/fixture đạt. Review Agent độc lập không có actionable finding ở bản S11-01; lần rà đầu chạy15 check, sau bổ sung missing/store tổng21 được agent chính chạy lại.

`itaapplication` vẫn render→auditExport→set download headers/echo; không thay endpoint/payload. Host cần user kiểm thủ công sau, chưa nghiệm thu DB.
