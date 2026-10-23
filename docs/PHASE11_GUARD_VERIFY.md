# BE11(c)/(d) — Kiểm bảo mật local, 23/10/2026

User duyệt «Duyệt BUILD (c) và (d)» và yêu cầu kiểm kỹ bảo mật. Chỉ guard project-start và deny nhạy cảm; không auto_prepend/.user.ini/header/error-page, không DB/host/gói/deploy hoặc merge/tag7–10. PHP CLI local8.5.10, chưa PHP-FPM8.3/Apache thật.

## (c) project-start guard

project-start.php gọi require_once project_gate trước output trừ khi gate đã cho phép nội bộ bằng hằng ITA_LANDING_ALLOWED=true. Gate chỉ đặt hằng trước cleanup worker/landing legacy-hidden. Query string không đặt được hằng; không thêm ngoại lệ HTTP gọi worker qua project-start trực tiếp.

`python -X utf8 tests/ita_phase11_guard_http.py`: **38 HTTP fixture checks đạt**. PHP built-in server chỉ bind127.0.0.1 với source guard/landing được copy vào tmp fixture; admin/module thay stub không DB. Không đổi marker thật trong workspace. Test trực tiếp project-start **không router/rewrite**, GET/HEAD/POST và worker/op/constant query giả; ITA stub dispatch một lần; legacy-hidden landing/worker một lần, ITA worker GET/HEAD/POST; legacy restoration giữ nguyên.

Đây là HTTP thật của guard PHP với endpoint fixture, không phải auth/DB integration hoặc mô phỏng Apache rewrite. Marker/worker URI probe chỉ tồn tại trong fixture, không đưa vào runtime. PHP lint2 file đạt; review độc lập không actionable finding ở mục(c). Chưa kiểm vhost, DOCUMENT_ROOT, AllowOverride hoặc headers sau proxy.
