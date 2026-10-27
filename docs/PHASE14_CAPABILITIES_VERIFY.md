# BE14 — Quyền theo chức năng, kiểm source local 27/10/2026

User duyệt **“Duyệt BUILD source BE14 theo gate”**. Đã xây source PHP/Smarty/JS và kiểm bằng fake transport/API mock; **chưa kết nối DB, chưa chạy SQL, chưa bật tính năng hoặc kiểm host**. Không sửa dc_*, PmAuth, config host, HSTS hoặc auto_prepend. Không tạo gói/upload/push/merge/tag7–10.

## Hành vi và bố cục

Mỗi người có danh sách permission key cố định và phạm vi bộ phận. C có thể có asset.read +asset.create +report.read dù metadata role cũ vẫn là Hỗ trợ; không thêm role4. Giao diện nhóm chức năng có tìm kiếm, nhóm đang có quyền được mở sẵn, ô role cũ ẩn trong chế độ mới. Chưa cấp quyền thì từ chối, không fallback từ tên role Admin. Checkbox không tự bổ sung quyền khác; server trả422 nếu thiếu quyền đọc/phụ thuộc.

Server kiểm từng GET/POST và suboperation; template/JS chỉ trình bày effective permissions do server trả. Mở URL trái quyền không hiện panel và không cho phép server trả dữ liệu của chức năng đó. Khả năng xem dữ liệu không tự cấp quyền ghi. Phạm vi toàn store phải có scope.store; quyền đó không tự cấp bất kỳ CRUD nào.

Lưu quyền chỉ ghi ita_user_roles/scopes/permissions và audit trong transaction; không sửa cột dc_users. Role gửi giả từ client bị bỏ qua. Tăng auth_revision sau thay đổi để phiên cũ hết hiệu lực; kiểm version chống ghi đè. Người cấp không được cấp quyền mình không có hoặc sửa người đang có tập quyền vượt phạm vi mình quản lý. access.read cần scope.store; access.manage và audit.read cần access.read: phiên bản này chỉ cho quản trị/audit toàn store, chưa có quản trị quyền theo từng bộ phận.

Lưu mạng kiểm cả hai đầu cũ/mới trong phạm vi dưới khóa; người mang role cũ khác Admin vẫn lưu được nếu có network.manage. Giữ ownership tự nhận bàn giao, ràng buộc trạng thái/version, CSRF, reauth/rate-limit/key tự ẩn, lý do override, native active/store và recheck export. Các guard khóa revision dùng cùng hàng role với writer quyền; không yêu cầu tên role là Admin trong mode mới.

## Matrix permission ở biên HTTP

Catalogue chính xác là `ItaPermissions::KEYS`; không nhận permission ngoài catalogue. Các dependency được `ItaPermissions::dependencies` kiểm, không cấp ngầm. Mọi dòng vẫn cần phiên hợp lệ, store/scope/validation; POST vẫn cần CSRF.

| GET/action | Quyền |
|---|---|
|data|Boot DTO; chỉ có danh sách/tổng tài sản khi asset.read; thiếu quyền trả danh sách rỗng, vẫn trả effective permissions và danh mục scope cho khởi tạo UI|
|asset|asset.read|
|categories lookup / paged|Lookup danh mục cho phiên hợp lệ / category.manage cho quản trị phân trang|
|users / access / audit|access.read / access.manage / audit.read|
|assignments / assignment-recipients / assignment-pdf|assignment.read / assignment.create / assignment.pdf|
|licenses, license|license.read|
|tickets, ticket / ticket-technicians|ticket.read / ticket.assign|
|ticket-statistics|ticket.statistics, hoặc ticket.statistics_self chỉ các phiếu technician=self|
|lifecycle, lifecycle-detail|lifecycle.read|
|forecast, forecast-config, forecast-plans, forecast-plan|forecast.read|
|dashboard, dashboard-charts / alerts|dashboard.read / alert.read|
|report-preview|report.read; riêng maintenance có report.maintenance nếu không có quyền đọc báo cáo đầy đủ|
|network, network-detail|network.read, cả hai đầu liên kết trong scope|
|import-template, import-preview, import-errors / asset-export|import.read / asset.export|

| POST/action hoặc operation | Quyền |
|---|---|
|asset-save create/update/status/note/delete|asset.create / asset.update / asset.status / asset.note / asset.delete; retired còn cần asset.retire; mở lại thiết bị đang chờ ready cần ticket.ready|
|category-save / access|category.manage / access.manage|
|import-upload / import-confirm|import.upload / import.confirm|
|assignment-save assign/return/acknowledge|assignment.create / assignment.return / assignment.acknowledge; acknowledge vẫn đúng người nhận|
|license-save save/allocate/release|license.manage / license.allocate / license.allocate; theo người thêm license.allocate_user; vượt mức thêm license.overage|
|license-key|license.reveal +xác thực lại/rate-limit/audit|
|ticket-save create/assign/transition/cost/ready|ticket.create / ticket.assign / ticket.process / ticket.cost / ticket.ready|
|forecast-config-save / forecast-plan-save / forecast-export|forecast.configure / forecast.plan / forecast.export|
|report-export / network-save|report.export / network.manage|
|login/logout|Giữ auth/limiter/session/CSRF hiện hành, không yêu cầu permission để logout|

assignment.read và ticket.create cần asset.read để dùng bộ chọn thiết bị; quyền ghi license cần license.read, quyền ghi ticket cần ticket.read. category.manage, license.manage/reveal/allocate_user và forecast.configure có dependency scope.store vì đụng danh mục chung hoặc tài khoản trong store. Các helper phân tích dùng chung có thể phục vụ nhiều service; guard HTTP vẫn kiểm đúng chức năng ngoài cùng, không suy đọc forecast đồng nghĩa được mở trang lifecycle.

## Kết quả thật

| Lệnh/bộ kiểm | Kết quả | Giới hạn |
|---|---|---|
|php tests/ita_phase14_unit.php|121 kiểm đạt|Catalogue/dependency, C=1/2/6, GET matrix, direct writer denial, grant/version/revision/subset/last-manager/audit rollback invocation, scoped network save; fake transport, không InnoDB/concurrency proof|
|playwright-cli -s=ita-admin-refine run-code --filename=tests/ita_phase14_browser.js|19 kiểm đạt|Editor production qua API mock, C độc lập role, payload/version, URL trái quyền, chuông đúng quyền, bounds1366/768/390, không POST|
|php tests/ita_s26_auth.php; ita_preupload_auth.php|686 +29 đạt|Counter/auth/session/limiter giả lập, không DB/HTTP login thật|
|php tests/ita_phase03_unit.php đến ita_phase10_unit.php|39/88/203/159/237/72/134/52 đạt|Pure/scripted; Phase4 có XLSX thật, mask deprecation cũ vẫn còn; không phải PHP8.3|
|php tests/ita_phase11_forecast_export.php; ita_phase11_import_export.php; ita_phase13_unit.php; ita_write_routes.php|30/54/179/13 đạt|Guard/render/query/route contract giả, không DB|
|python tests/ita_phase11_privacy_http.py; ita_phase11_error_http.py; ita_phase11_guard_http.py|102/56/109 đạt|HTTP fixture PHP thật, không Apache/host|
|Browser phase05/06/07/09/13_live, phase11_admin|29/28/64/42/31/14 đạt|Hồi quy role-mode qua API mock, không DB thật|
|Browser ita_admin_readability_browser|26 đạt|MOCK nhật ký, A/B/C và drilldown nhóm; không quyền DB|
|Browser ita_phase11_lifecycle_groups_browser; ita_phase10_browser|30 +70 đạt|Phân trang nhóm và dashboard/Command–Focus qua API mock, không DB|
|php tests/ita_phase03_render.php; PHP lint file sửa; node --check JS|Đạt|Smarty/syntax local|
|Review agent độc lập|Đã sửa chặn role cũ ở lưu mạng; lần rà sau không blocker mới|Chỉ đọc diff, không chạy DB/host; không chứng nhận toàn repo|

Đã xem ảnh editor390px: ô chọn theo nhóm, tìm kiếm và scope không tràn trang. Browser server là Node static preview127.0.0.1:18766, không chạy entrypoint DB; kiểm PHP dùng8.5.10. Không coi lỗi preview server chưa mở ban đầu là lỗi sản phẩm; đã mở đúng server và chạy kiểm sau đó. PHP8.3/FPM/DB/EXPLAIN/concurrency chưa kiểm.

## Kích hoạt, rollback và giới hạn

`ITA_CAPABILITIES_ENABLED` mặc định **không định nghĩa/tắt**; role-mode cũ vẫn hoạt động. Chưa sửa config để bật. Khi bật mà thiếu bảng/quyền không hợp lệ thì fail-closed503, không fallback role. SQL/mapping/rollout trình riêng tại BE_GATE_14_SQL.md; không dùng migration cũ/credential DDL để ứng dụng tự tạo bảng.

Trước bật cần đối chiếu/backfill đủ catalogue từ preset đã review, có ít nhất một tài khoản quản lý quyền native active, xác nhận các tài khoản mẫu, quyền DB theo bảng, revision và kiểm DB thật. Chưa tự bootstrap quyền Admin từ user đăng nhập đầu tiên. Bảo vệ manager cuối chỉ trong protocol writer/transaction ITA; native MyISAM có thể đổi ngoài transaction, không hứa atomicity với dc_users hoặc thao tác SQL ngoài ứng dụng.

Backup source/config riêng và DB do user trước triển khai. Rollback nguồn/cờ từ bản đã backup, giữ bảng/audit/credential/dữ liệu mới; không DROP/reset/restore đè. Role-mode cũ có thể cấp rộng hơn hoặc hẹp hơn tổ hợp mới: phải dừng ghi và review quyền trước rollback, không coi hai policy tương đương. Chưa có ZIP mới hoặc quyền upload.

Xem local tĩnh: `http://127.0.0.1:18766/#audit`, `#users`, `#lifecycle` — đều dữ liệu giả, checkbox MOCK chưa lưu thật. Source editor production đã kiểm qua API mock như bảng trên; không tự bật mode mới chỉ để trình diễn.
