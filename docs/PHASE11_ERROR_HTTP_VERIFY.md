# BE11(f) — Lỗi HTTP trong ứng dụng, 23/10/2026

User duyệt «Duyệt BUILD (f) trong ứng dụng». Chỉ app/gate error boundary, không sửa PHP-FPM/log destination/.user.ini/auto_prepend/HSTS/header policy mới/Apache ErrorDocument. Không DB/host/gói/deploy/merge/tag7–10. Local PHP8.5.10, PHP-FPM8.3 chưa kiểm.

## Hành vi

- Gate deny403/404 có trang HTML thay plain text; API/Accept JSON giữ JSON. Action API/export kể cả không hợp lệ không trả HTML200 thay file. Không đổi routing redirect legacy/allowlist/quyền/CSRF.
- ItaErrorResponse giữ status lỗi thật, no-store/nosniff và đúng MIME, xóa attachment/content-length/location còn sót trước phản hồi lỗi. JSON giữ success/data/message và thêm request_id; không đổi envelope thành công.
- Trang HTML dùng Smarty escape, token màu/font hiện có, mã tham chiếu do server tạo; query/header X-Request-ID không quyết định mã log. Login lỗi vẫn có form/token, trạng thái và message escaped; render login lỗi có fallback.
- Log ứng dụng có đúng event/request_id/status/kind; không ghi URL/query/body/cookie/token/key/password hoặc exception message/trace. Log là server error_log hiện có, **không tạo log endpoint/file công khai**. Không chứng minh log PHP/webserver/vendor khác cũng được khử dữ liệu; cần quyền/rotation/config host riêng.
- Lỗi bootstrap bắt được trả500 chung, lỗi dịch vụ/config hiện được app xử lý503 giữ semantics. Renderer có buffer và fallback HTML tối giản khi Smarty/template/cache lỗi, không gọi lại Smarty để render lỗi của chính renderer. HEAD không body. Khi headers đã gửi, chỉ ghi log response_started, **không nối lỗi vào bytes cũ**; không thể thu hồi bytes hoặc sửa status đã truyền.
- Cache template lỗi ngoài webroot, từ chối link trực tiếp và không dùng thư mục dưới root web; quyền/ACL host vẫn chưa xác minh.

Không phải mọi lỗi500 của server được xử lý: parse/startup xảy ra trước boundary, file error handler bị thiếu, OOM/unrecoverable fatal và Apache/proxy404/500 cần cấu hình vận hành riêng. F11.4 **đã build phần ứng dụng, vẫn chưa hoàn tất nghiệm thu host**.

## Kiểm chứng thật

| Lệnh | Kết quả | Loại/giới hạn |
|---|---|---|
| python -X utf8 tests/ita_phase11_error_http.py |56 đạt |PHP HTTP/Smarty thật trong fixture cô lập; fake configuration ném lỗi, không đọc private config/kết nối DB. Status/MIME/escaping/ref/log/HEAD/bootstrap/fallback/response started và login form |
| playwright-cli -s=ita-phase06 run-code --filename=tmp/phase11-error-ui/browser-run.js |41 đạt |HTML thực từ Smarty ở test HTTP, transport browser mock; viewport1366/768/390, target44px/focus/bounds/escaping/reference, không DB/host |
| python -X utf8 tests/ita_phase11_guard_http.py |109 đạt |Hồi quy guard/worker/deny HTTP fixture; copy thêm error boundary vào fixture, không giả định dependency tự có |
| php tests/ita_phase11_deny.php |75 đạt |Pure deny/rule ordering, không Apache execution |
| php tests/ita_write_routes.php |13 đạt |Không mở POST ngoài scope |
| php tests/ita_phase11_forecast_export.php; php tests/ita_phase11_import_export.php |30+54 đạt |Scripted post-render security, không DB |
| php tests/ita_phase09_unit.php; php tests/ita_phase03_render.php |134 +Smarty render đạt |Hồi quy không DB |
| php -l5 file PHP runtime mới/sửa; node --check test JS; git diff --check |Đạt |Parser/whitespace local |

Test HTTP sinh HTML/JS test tại tmp/phase11-error-ui, được ignore và loại khỏi deploy. Browser chạy file sinh từ tests/ita_phase11_error_page_browser.js; không chạy trực tiếp template có placeholder. Không thêm fixture vào runtime. Test JSON/log dùng sentinel dữ liệu giả, kiểm ứng dụng không ghi exception/request/CSRF/cookie; không suy đã audit log host thực.

Đã xem ảnh404 desktop/mobile; chuỗi img thử XSS hiển thị như text, không thực thi. Impeccable detector có2 warning font Inter (theo theme đã chốt), không có nghĩa lỗi bảo mật; giữ font hiện có, không báo detector sạch. Browser session cũ/server đã đóng dẫn tới ECONNREFUSED lúc đầu; đã khởi động đúng static preview18766, không coi đó là lỗi sản phẩm. Lần chạy test browser đầu dùng require không có trong sandbox; chuyển generator nhúng HTML fixture vào tmp, không nới assertion.

Review Agent độc lập rà runtime và chạy46 HTTP error +109 guard ở phiên bản đầu, không finding; agent chính bổ sung10 kiểm login/App.run/act không hợp lệ để tổng56, không đổi semantics renderer. Host/DB/Apache chưa chạy.

## Dependency và hoàn tác

Thay đổi runtime: classes/security/itaerrorresponse.class.php (mới), classes/services/itaapplication.class.php, includes/ita_bootstrap.inc.php, includes/project_gate.inc.php, modules/admin/ita.module.php; templates/admin/ita/error.tpl.html và assets/ita/css/ita-error-page.css (mới). Cần Smarty hiện có, tokens/fonts hiện có; không package mới. Các file phải tương thích khi bàn giao sau, đặc biệt gate/module cần errorresponse trước khi có request lỗi. **Chưa tạo gói/upload**; delta S11 export3 file trước không chứa các file này.

Rollback bằng backup source đồng bộ các file runtime trên; không đổi DB/config/key/dump hoặc xóa audit mới. Kiểm lỗi HTML/API/worker/deny sau rollback; không coi có hướng dẫn là rollback đã chạy. Khi user kiểm host sau, ghi “kiểm thủ công trên host, phạm vi hạn chế”, không tự nghiệm thu DB.
