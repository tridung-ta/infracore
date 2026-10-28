# Rà lại trước gói cập nhật Phase14 — 28/10/2026

User yêu cầu “kiểm tra lại toàn bộ thêm1lượt nếu ổn thì gửi file upload”. Đã kiểm local theo các bộ phù hợp, không DB/host/private config; chuẩn bị gói **cập nhật để user kiểm host**, không kết luận toàn bộ phase đã nghiệm thu. Runtime source cố định a7c2da18b7e0ff590859b6d5afbbdd7d1b4e146f. Working changes tài liệu/preview/test và file user sửa PHASE11_SECURITY_TEST_20 không lấy làm runtime; không stage file user đó.

## Kết quả chạy lại thật

| Nhóm | Lệnh / danh sách | Kết quả và giới hạn |
|---|---|---|
|PHP lint payload|php -l qua stdin đúng bytes của từng entry PHP theo inventory fixed commit/dependency hash|**1.145file đạt** trên CLI8.5.10; không thực thi PHP/require/config/DB; report lint-a7c2da1.json trong artifacts|
|Unit/service|php tests/ita_phase03_unit.php đến08_unit.php|39/88/203/159/237/72 đạt, fake transport/pure;04 có XLSX thật, mask deprecation của test cũ không chứng minh zero warning|
|Ngân sách/dashboard|php tests/ita_phase09_unit.php;10_unit.php|134/52 đạt, không DB|
|Export guard|php tests/ita_phase11_forecast_export.php;11_import_export.php|30/54 đạt; synthetic render/storage trong guard, không HTTP+DB|
|Mạng/quyền/tạo user|php tests/ita_phase13_unit.php;14_unit.php;14_user_create.php|179/121/135 đạt; query/bind/validation/compensation fake, không DB/atomicity proof|
|Auth/session/CSRF|php tests/ita_preupload_auth.php;ita_s26_auth.php;ita_write_routes.php|29/686/13 đạt; counter không chứng minh hằng thời gian, session giả local|
|Smarty|php tests/ita_phase03_render.php|Render thật/escape đạt, không DB|
|HTTP local|python tests/ita_phase11_privacy_http.py;error_http.py;guard_http.py|102/56/109 đạt, module/gate/Smarty fixture; không Apache/FPM/proxy/host|
|SQL artifact|python tests/ita_phase13_sql_contract.py;ita_phase14_sql_contract.py|57checks +88preset-pair static contract đạt; không SQL syntax/enforcement trên DB|
|Validator|python tests/ita_release_validation_test.py|66checks đạt, ZIP synthetic riêng, không thay actual ZIP validation|
|Node model|node tests/ita_manual_review_model.cjs|21checks đạt, không PHP/DB|
|Browser|playwright-cli -s=ita-local-review run-code --filename=tests/<test>.js|**26suites/948checks đạt**; static/API mock/Node RAM, không login/SQL/persistence thật; responsive/WebGL/fullscreen được kiểm local|
|File thật|php -d extension=gd tests/ita_phase09_exports.php;ita_phase10_exports.php|Exit0, XLSX mở lại/formula-like STRING, PDF bytes tạo được. Logs có33/37 dòng diagnostic khớp Deprecated trên8.5 (không phải bộ đếm unique deprecation events). Chưa8.3/FPM/downloadhost; không render ảnh PDF trong lượt này.|

26browser suites: Phase03/04/05/05PDF/06/07/08/09/10;11responsive/history/category/stats/lifecyclegroups/forecastrows/dashboardtables/errors;12loginmotion/motion;13live/fullscreen/orbit;14capabilities;alert-session;manual-review;user-create. Counts từng bộ ở artifacts/ita-host-20261028/browser-summary.json; logs giả ở tmp, không commit/gói.

Không chạy tests/ita_phase02_checks/fixture/browser, database/tests/phase01_schema.php hoặc local concurrency harness vì có thể kết nối/ghi DB ngoài quyền. Không chạy EXPLAIN/tải thật hoặc sinh1triệu. Không đọc config/credential/DB/host. Server Node18766/18767 phục vụ fixture; không PHP appserver hoặc session thật trên host.

## Lỗi lượt kiểm và cách xử lý

- Lượt gọi browser đầu truyền expression path sai cú pháp PowerShell, CLI báo SyntaxError trước test. Đổi thành argument string đầy đủ; node syntax của test không lỗi, lượt chuẩn chạy được. Giữ log ban đầu trong tmp; không gọi đó là defect sản phẩm.
- Alert-session test dùng cache URL shell20261028-feedback cũ. Source shell đã đổi20261028-usercreate trong BUILD tạo account, nên cập nhật expected map riêng cho từng script, không bỏ kiểm version hoặc đổi sản phẩm. Test17checks sau đó đạt, gồm storage failure; đây là sửa test theo contract hiện hành.
- Không có defect runtime mới buộc sửa PHP trong lượt này. Cảnh báo dependency/PHP8.5 giữ rủi ro S26-03, không nâng vendor hoặc che output.

## Gói thực tế và review

- ZIP `artifacts/ita-host-20261028/infracore-update-phase14-a7c2da1.zip`: **49.310.447byte**, SHA256 `4afc98d1c84f343c4cb112a21c8a098fd4cdee355238f63c50917faf05f973b9`.
- 1.415runtime/105.049.248byte +2metadataentry. Inventory bỏ handoff SQL/docs/.htaccess cũ; exceptions rỗng, runtime-only. Manifest nêu rõ007/mapping/flag thủ công/grants, không autoenable và các cờ DB/host/PHP83 chưa kiểm.
- Prepare rà lock mPDF/installed package version/ref, Excel566 baseline tree88815432… (payload565 sau loại workbook duy trì), PDFvendor source697/payload696 sau loại tmp, Inter2file/hash cũ. Không nâng dependency, không khẳng định upstream Excel/Inter đã xác minh hoặc backup ngoài máy dev đã restore.
- Builder đọc lại ZIP thật: tập allowlist/exclusions/path/case/links/CRC/size/SHA256 từng file/metadata. Get-FileHash độc lập khớp ZIP hash. Review Agent đọc actual ZIP xác nhận1.417entry/hash/exclusions và prerequisite, không finding chặn bàn giao kiểm host.
- Không mang private config/marker/.htaccess/.user.ini/SQL/tmp/tests/scripts/artifacts/MOCK/preview/launcher hoặc dữ liệu thật. Tài liệu này/hướng dẫn upload ở ngoài runtime ZIP; ZIP/manifest/checksums không upload webroot.

Hướng dẫn HOST_UPLOAD_PHASE14_20261028.md: backup source/config/DB/key, upload nội dung runtime, smokehost và rollbacksource giữ dữ liệu. Chưa upload/đổi grants/cờ/host, chưa PHP8.3/DB nghiệm thu; không merge/tag7–10/push/deploy. Nút tạo tài khoản thật chỉ dùng khi native schema/grants/capabilities mode đã được xác nhận; không suy từ47quyền của Admin111 trong ảnh SQL.
