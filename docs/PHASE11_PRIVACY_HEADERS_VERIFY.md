# BE11(g) giới hạn trong ứng dụng — 23/10/2026

User duyệt «Duyệt BUILD (g) giới hạn trên»: Referrer-Policy strict-origin-when-cross-origin và Permissions-Policy camera=(), microphone=(), geolocation=(). **Không HSTS**, không .user.ini/auto_prepend hoặc cấu hình host. Không DB/host/gói/merge/tag7–10/push/deploy.

## Thay đổi và phạm vi

ItaErrorResponse::privacyHeaders đặt đúng hai header, dùng ở module ITA trước bootstrap và ở error response sau kiểm headers_sent. Header lặp được thay thế cùng tên, không tạo hai policy mâu thuẫn trong response do PHP này tạo. Không thay CSP, auth/scope/CSRF, Smarty, download payload, status hoặc redirect.

Rà chỉ đọc ITA JS/template/service theo mediaDevices/getUserMedia/geolocation/watchPosition/getCurrentPosition/camera/microphone: không thấy call tính năng đó trong source được rà. Không kết luận legacy/vendor/toàn trình duyệt chưa dùng; nếu thêm quét camera hoặc vị trí sau này, cần xem lại policy theo tính năng thực, không tự bật lại toàn origin.

Referrer policy vẫn gửi URL theo mặc định cùng origin và chỉ origin qua khác origin HTTPS theo policy; không thay kiểm quyền hoặc chống CSRF. Permissions-Policy phụ thuộc trình duyệt hỗ trợ. Không tuyên bố đã thử phần cứng thật hay browser permission prompt.

Áp dụng cho shell/login/API/file qua module và lỗi qua boundary (kể cả gate). Không tự thêm policy vào mọi static asset, redirect gate, worker, legacy-restored hoặc endpoint ngoài module. Header proxy/Apache có thể bổ sung/ghi đè; cần kiểm giá trị cuối trên host. HSTS chưa được thêm vào code này, không suy proxy host không có HSTS.

## Kết quả thật

| Lệnh | Kết quả | Giới hạn |
|---|---|---|
| python -X utf8 tests/ita_phase11_privacy_http.py |102 đạt |HTTP PHP thật, module/error boundary thật nhưng app/bytes PDF-XLSX giả; không DB/host |
| python -X utf8 tests/ita_phase11_error_http.py |56 đạt |Hồi quy lỗi HTTP/Smarty/login/fallback/log |
| python -X utf8 tests/ita_phase11_guard_http.py |109 đạt |Hồi quy guard/worker/deny fixture không DB |
| php -l classes/security/itaerrorresponse.class.php; php -l modules/admin/ita.module.php; git diff --check |Đạt |Local PHP8.5.10, không PHP-FPM8.3 |

Ma trận header gồm HTML200, JSON200, download200, bootstrap lỗi HTML/JSON500, boundary403/404 và HEAD; kiểm đúng một header mỗi policy, status/MIME/body/download không đổi, lỗi không attachment, không HSTS, CSP đang dùng vẫn còn. File tải là transport giả, không thay test file thật đã có. Review Agent độc lập chạy102 checks đạt, không actionable finding.

## Host và rollback — chưa thực hiện

User kiểm header cuối cho HTML/API/file/error bằng DevTools sau deploy được phép; đối chiếu proxy/PHP-FPM, không đổi server trong lúc test. Kiểm chức năng hiện có, MIME/no-store và không có policy trùng. Không bật HSTS khi chưa xác nhận HTTPS ổn định cả domain và phạm vi subdomain.

Hai file runtime thay đổi là classes/security/itaerrorresponse.class.php và modules/admin/ita.module.php; cần nền(f) có class boundary. Rollback source hai file đồng bộ từ backup, không DB/config hoặc audit; HSTS không liên quan vì chưa bật. Chưa tạo gói/upload hoặc thử rollback host. Delta export3 file cũ không chứa thay đổi này.
