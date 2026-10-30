# BE11(c)/(d) — Kiểm bảo mật local, 23/10/2026

User duyệt «Duyệt BUILD (c) và (d)» và yêu cầu kiểm kỹ bảo mật. Chỉ guard project-start và deny nhạy cảm; không auto_prepend/.user.ini/header/error-page, không DB/host/gói/deploy hoặc merge/tag7–10. PHP CLI local8.5.10, chưa PHP-FPM8.3/Apache thật.

## (c) project-start guard

project-start.php gọi require_once project_gate trước output trừ khi gate đã cho phép nội bộ bằng hằng ITA_LANDING_ALLOWED=true. Gate chỉ đặt hằng trước cleanup worker/landing legacy-hidden. Query string không đặt được hằng; không thêm ngoại lệ HTTP gọi worker qua project-start trực tiếp.

`python -X utf8 tests/ita_phase11_guard_http.py`: **38 HTTP fixture checks đạt**. PHP built-in server chỉ bind127.0.0.1 với source guard/landing được copy vào tmp fixture; admin/module thay stub không DB. Không đổi marker thật trong workspace. Test trực tiếp project-start **không router/rewrite**, GET/HEAD/POST và worker/op/constant query giả; ITA stub dispatch một lần; legacy-hidden landing/worker một lần, ITA worker GET/HEAD/POST; legacy restoration giữ nguyên.

Đây là HTTP thật của guard PHP với endpoint fixture, không phải auth/DB integration hoặc mô phỏng Apache rewrite. Marker/worker URI probe chỉ tồn tại trong fixture, không đưa vào runtime. PHP lint2 file đạt; review độc lập không actionable finding ở mục(c). Chưa kiểm vhost, DOCUMENT_ROOT, AllowOverride hoặc headers sau proxy.

## (d) Deny nhạy cảm và static allowlist

`.htaccess` thêm rule403 conditional ITA on/legacy off **trước** static allowlist: tests/artifacts/scripts/assets/ita/mock và mọi file con, không phân biệt hoa thường. Bỏ mock khỏi allowlist static. Rule dùng F/END đã có cơ chế END của nền; không đổi auto_prepend/.user.ini hoặc Options toàn site chưa xác minh. Các directory bị deny nên không phục vụ listing khi rule có hiệu lực, nhưng không tuyên bố toàn host đã tắt listing.

`ita_mode` cũng chặn các prefix này404 khi đi qua gate/router; decode một lần, từ chối path còn%/NUL/backslash/dot segment trước static, chuẩn hóa slash lặp trước kiểm prefix. Không đưa double-encoded URI vào static/legacy fallback. Khi tắt ITA hoặc bật legacy marker, hành vi khôi phục cũ giữ nguyên theo contract, không tuyên bố mode đó cũng được hardening.

| Lệnh | Kết quả | Giới hạn |
|---|---|---|
| php tests/ita_phase11_deny.php |75 đạt |Pure routing/Apache rule ordering source, không Apache execution |
| python -X utf8 tests/ita_phase11_guard_http.py |109 đạt |PHP built-in HTTP thật:38 guard ban đầu +71 deny/static; fixture có file nhạy cảm thật và router thật, không DB |
| php tests/ita_write_routes.php |13 đạt |Allowlist action ghi không hồi quy |
| php -l includes/ita_mode.inc.php, project_gate.inc.php, project-start.php và test PHP |Đạt |Parser local8.5, không8.3 |

Test deny có GET/HEAD/POST thư mục/file, mixed case, percent/encoded slash/double encode, repeated slash, traversal/backslash/NUL. Fixture xác minh status403/404 (không redirect), body không lộ marker, HEAD rỗng, CSS production vẫn phục vụ. Không chạy test cũ trỏ auth fixture/DB18769. Review Agent độc lập chạy75+109 đạt và không actionable finding trong diff.

**Apache chưa chạy**: không thấy httpd/apache2 trong PATH kiểm tra; chưa cài runtime mới. Vì PHP built-in không xử lý .htaccess, không ghi PASS cho rewrite/AllowOverride/alias/vhost. User cần kiểm PHASE11_HOST_TEST khi sẵn sàng; host/inventory vẫn chưa kiểm/CHƯA THU THẬP.

## Rủi ro vẫn mở và hoàn tác

Chỉ project-start có guard mới, không tự chèn gate vào mọi file PHP legacy. Với direct module/library/upload hoặc static file khác, bảo vệ host vẫn phụ thuộc rewrite và marker đúng DOCUMENT_ROOT. Không coi deny local là toàn bề mặt legacy an toàn. Alias/symlink/vhost khác, cấu hình Apache không hỗ trợ/không đọc .htaccess, chứng chỉ/headers/PHP-FPM8.3, auth/DB/concurrency/rollback audit thật chưa kiểm.

Hoàn tác(c): phục hồi project-start +project_gate cùng bộ; worker cần tương thích allowance. Hoàn tác(d): phục hồi .htaccess +ita_mode đồng bộ qua kênh ngoài app nếu site lỗi. Giữ DB/audit/source legacy, không restore dump/xóa dữ liệu để chữa lỗi. Không tạo ZIP/upload; delta S11 cũ3 file export không chứa4 file guard/rewrite của(c)/(d), không dùng nó để tuyên bố host có các deny mới.
