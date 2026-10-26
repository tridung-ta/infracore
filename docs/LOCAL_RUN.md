# Chạy InfraCore trên Laragon — chuẩn bị 26/10/2026, CHƯA KẾT NỐI DB

User tự dựng Apache + PHP8.3.x + MariaDB10.6.x và vhost HTTPS cục bộ. Đây là hướng dẫn/script **chưa chạy trên DB**. Agent chỉ được kết nối sau câu xác nhận riêng: **«Tôi xác nhận cho chạy trên DB local [tên DB]»**, thay phần trong ngoặc bằng tên thực. Xác nhận phải nêu đúng một schema. Không dùng cấu hình, credential, dump dữ liệu hoặc khóa host. Không đóng gói/upload/merge/tag7–10/push/deploy.

## 1. Chuẩn bị môi trường riêng

- Dùng checkout local sạch; giữ code Smarty/Derasoft và các dependency hiện có. Không sao chép file private bị Git ignore từ workspace/host sang checkout này. Ghi commit40 ký tự và diff thực trước test.
- PHP8.3.x thực cho cả CLI và Apache: mysqli với mysqlnd, openssl (AES-256-GCM), mbstring, zip, fileinfo, DOM/libxml, zlib, GD; session/json/hash/filter cũng phải có. Kiểm `php -v`, `php --ini`, `php -m` bằng **đường dẫn php.exe Laragon** khi được giao chạy; PATH8.5 không chứng minh8.3. Không ghép DLL8.5 vào8.3.
- MariaDB10.6.x riêng trên loopback, schema mới `infracore_local_<tên>`, utf8mb4/utf8mb4_unicode_ci; InnoDB cho16 bảng ita_. Không mở DB ra mạng, không tunnel/port-forward tới host. Giữ engine cấu trúc dc_users đã xuất (MyISAM theo hồ sơ), không chuyển engine để làm rollback giả.
- Apache: mod_rewrite, mod_ssl, mod_headers và mod_expires; `AllowOverride All` trong đúng Directory của vhost, không bật directory listing (`Options -Indexes`), giới hạn `Require local`. .htaccess hiện dùng ExpiresActive nên cần mod_expires. Không áp cấu hình lên vhost khác.
- Vhost gợi ý `https://infracore.test`, DocumentRoot đúng checkout; certificate local được trình duyệt tin cậy. Ghi PHP Apache thực, HTTPS/cookie/header cuối cùng; CLI không thay kiểm này. Không bật HSTS/auto_prepend/.user.ini theo đề xuất cũ.
- Dependencies Excel/PDF/Inter khôi phục đúng baseline/lock theo DEPLOY_LOG mụcC, không cài latest. Thư mục compile Smarty/cache/temp local ghi được; staging import ngoài document root và ACL chỉ tài khoản chạy PHP. Không đưa test/config/docs ra mạng công cộng.

## 2. Cấu trúc legacy tối thiểu cần xuất — KHÔNG lấy dữ liệu

Trong runtime ITA (ITA bật, legacy tắt), chỉ có **dc_users**. Nguồn: ItaAuthService, ItaUserObjects, ItaAssignmentObjects, ItaLicenseReveal và adapter PmAuth dùng chung. Không cần xuất dc_roles/dc_departments hoặc dữ liệu user thật. Đây không phải danh sách dependency của các chức năng legacy khi bật lại.

| Cột dc_users | ITA đọc | ITA ghi khi vận hành |
|---|---|---|
|id|ID native, actor/mapping/recipient/reauth|Không|
|store_id|Kho/phạm vi native|Không|
|username|Định danh/login/hiển thị|Không|
|email|Định danh login|Không|
|fullname|Tên hiển thị/người nhận|Không|
|status|Native active=1|Không|
|password|MD5 native tương thích|Không; giữ nguyên|
|password_hash|Hash hiện đại/reauth|Login thành công: MD5 hoặc needs_rehash và mật khẩu ≤72 byte; Argon2 dài giữ theo contract|
|last_login|Không dùng làm đầu vào quyền ITA|UPDATE khi login thành công|

Xuất **chỉ cấu trúc CREATE TABLE dc_users**, kèm index/default/engine/collation, không INSERT/rows/triggers/routines/events/credential. Không dùng dump toàn DB. id/store_id signed INT theo nền, không tự đổi sang unsigned/FK. Giữ cột bổ sung của cấu trúc thật: script không sửa bảng. Nếu cột bổ sung NOT NULL không default thì khai báo giá trị giả rõ trong `legacy_defaults`; không đoán dữ liệu thật. Kiểm file cấu trúc không còn `USE`/CREATE DATABASE/DEFINER trỏ schema host trước import bằng tài khoản DDL local. Không import trực tiếp file có đích dung_infracore.

Fixture tạo 4 tài khoản giả bằng tài khoản setup, khác quyền runtime: test-admin, test-manager-a, test-manager-b, test-support; email example.invalid. Ba vai trò, hai manager để kiểm scope A/B. Manager-A MD5-only để kiểm upgrade; những user khác hash hiện đại. Không đăng nhập bằng tài khoản thật. Login test được phép ghi hai cột trên trong DB local; nhánh401 không ghi native. Chính sách ở PmAuth/ItaAuthService phải rà đồng bộ; đếm verify không chứng minh hằng thời gian.

## 3. Tạo schema và migration theo thứ tự — user thực hiện sau xác nhận

User tạo schema mới rỗng bằng quản trị Laragon; công cụ fixture **không tạo DB hoặc chạy DDL**. Chưa thực hiện bước nào. Trước mỗi import xác nhận `DATABASE()` đúng schema local và server loopback thực, không dựa tên tab phpMyAdmin.

| Thứ tự | Nguồn cấu trúc |
|---|---|
|0|dc_users: structure-only đã rà, không rows/triggers|
|1|database/migrations/001_ita_foundation.sql|
|2|database/migrations/002_ita_assets.sql|
|3|database/migrations/003_ita_allocations_licenses.sql|
|4|database/migrations/004_ita_operations.sql|
|5|database/migrations/005_ita_auth.sql|
|6|database/migrations/006_ita_network_links.sql|

**Cả sáu migration có `USE dung_infracore`. Không chạy nguyên bản cho local.** Tạo bản sao trong thư mục private local ngoài checkout, chỉ thay duy nhất câu USE sang tên schema đã xác nhận, rà diff các bản sao: ngoài USE, toàn bộ DDL giữ byte/nội dung nghiệp vụ. Không sửa migration gốc/Git để đổi đích. Không chạy seed/bootstrap SQL host. Dừng khi có bảng đã tồn tại hoặc lỗi, không `IF NOT EXISTS`, không DROP/TRUNCATE/reimport để bỏ qua. DDL implicit commit; nếu import dở, ghi mục đã chạy và xử lý riêng, không rollback giả.

Sau migration có16 ita_ tables theo `LocalFixture::TABLES`, cộng dc_users=17 bảng. So engine/collation/FK/CHECK metadata với sáu nguồn; con số không thay kiểm định nghĩa và enforcement. Không tạo bảng legacy khác để chữa lỗi ngoài scope. Nếu bootstrap cần thứ khác, dừng và báo path/query thực trước sửa.

## 4. Hai tài khoản DB và cấu hình local private

Tài khoản setup/DDL local riêng: chỉ đúng schema mới, CREATE/ALTER/INDEX/REFERENCES + SELECT/INSERT/UPDATE để import/seed; không đặt trong config ứng dụng. User/quản trị Laragon dùng tài khoản quản trị local riêng để cấp/thu grant khi thử audit; không cấp GRANT OPTION/global admin cho runtime. Không cần FILE/SUPER hoặc quyền mọi schema. Không tự phát hành GRANT lúc này.

Runtime: grant **từng bảng**, không `schema.*` INSERT (nếu có thì thu INSERT audit ở mức bảng không chặn được). Nhu cầu hiện tại:

| Bảng | Quyền dự kiến, phải kiểm effective grants thực |
|---|---|
|dc_users|SELECT trên8 cột đọc nêu trên; UPDATE(last_login,password_hash). Không INSERT/DELETE/DDL; nếu cơ chế SELECT FOR UPDATE yêu cầu khác, ghi lỗi chính xác trước nới|
|ita_audit_logs|SELECT, INSERT; append-only, không UPDATE/DELETE|
|ita_departments|SELECT (hiện không có route quản lý ghi bộ phận)|
|ita_ticket_events|SELECT/INSERT; source hiện chỉ append, không UPDATE|
|ita_asset_categories, ita_settings, ita_assets, ita_licenses, ita_license_allocations, ita_assignments, ita_tickets, ita_budget_plans, ita_import_batches, ita_user_roles, ita_user_scopes, ita_login_limits, ita_network_links|SELECT/INSERT/UPDATE theo nhu cầu source hiện tại; có thể siết thêm từng bảng/cột sau test|

Không DELETE/REPLACE/TRUNCATE/LOCK TABLES/CREATE TEMPORARY TABLE trong đường runtime ITA đã rà; không đề xuất DELETE, LOCK TABLES hoặc CREATE TEMPORARY TABLES. Không suy sang legacy/vendor mọi chức năng. Kiểm `CURRENT_USER()`, SHOW GRANTS của **runtime thực**, role/inherited effective grants và transaction lock thực trước kết luận. Không dùng kết quả tài khoản phpMyAdmin quản trị làm grant của app.

Rà36 vị trí locking SELECT tại [LOCAL_LOCK_GRANTS_REVIEW.md](LOCAL_LOCK_GRANTS_REVIEW.md). Có FOR UPDATE trên ita_departments (asset/assignment/license/import/forecast), nhưng source MariaDB10.6 kiểm SELECT_ACL cho SELECT/prepared SELECT; **chưa có căn cứ thêm UPDATE chỉ vì khóa**. Giữ SELECT-only bộ phận và kiểm với runtime Laragon thật sau xác nhận. Không áp kết luận này sang MySQL/phiên bản khác. Không thêm LOCK TABLES/UPDATE rộng để chữa lỗi chưa tái hiện.

Ưu tiên cấu hình app qua Apache vhost private **ngoài Git/webroot**: ITA_DB_HOST=127.0.0.1, ITA_DB_PORT cổng local, ITA_DB_NAME tên đã xác nhận, ITA_DB_USER/PASSWORD runtime, ITA_STORE_ID=1, ITA_AUTH_KEY ngẫu nhiên local≥32 ký tự, ITA_LICENSE_KEY base64 của32 byte ngẫu nhiên test. Sinh khóa bằng công cụ local khi được giao, không dùng/reveal khóa host. Giữ khóa test để đọc ciphertext fixture; không đổi sau seed tùy tiện.

ItaDb.configuration() vẫn require includes/ita_database.local.php **trước** env. Trong checkout sạch phải bảo đảm không có file host đó. Nếu chọn file này thay env, tự tạo mới local với array host/port/name/user/password/store_id/auth_key/allow_local_http; file đã gitignored, phải loại deploy. Không đọc/copy bản host đang có ở workspace khác. Không dùng default dung_infracore. Không mở endpoint phpinfo/debug để lộ env.

Tạo marker `.ita-enabled` tại đúng DOCUMENT_ROOT local; **không có `.ita-legacy-enabled`**. HTTPS ưu tiên. Chỉ khi chủ ý test HTTP loopback mới đặt ITA_ALLOW_LOCAL_HTTP=1; service còn kiểm REMOTE_ADDR127.0.0.1/::1, không áp cho host; HTTPS local không cần bật. Không đổi cờ legacy để chữa lỗi.

## 5. Bộ fixture chỉ local — đã soạn, CHƯA CHẠY DB

`scripts/local/ita_local_fixture.php` không include bootstrap/config ứng dụng, không đọc ITA_DB_* và không có nhánh tạo DB/migration/reset. Mặc định `--action=plan` không đọc config/kết nối. Seed/load thực bắt buộc `--execute`, DB prefix và **đúng câu xác nhận** trước khi đọc JSON; endpoint chỉlocalhost/127.0.0.1/::1 (localhost chuẩn hóa127.0.0.1), user tuyên bố không tunnel. Guard endpoint không chứng minh vật lý nếu người dùng tự đặt tunnel. Agent vẫn phải đợi xác nhận trong chat; tham số ack không thay quyền đó.

JSON riêng đặt ngoài workspace **và mọi document root**, ví dụ private directory do user chọn; ACL chỉ user local. Không commit hoặc gửi mật khẩu. Mẫu cấu trúc (placeholder, không phải config đã tạo):

```json
{
  "purpose": "infracore-local-fixture-v1",
  "local_only": true,
  "no_tunnel": true,
  "connection_role": "setup",
  "host": "127.0.0.1",
  "port": 3306,
  "name": "infracore_local_uat",
  "user": "<setup user local>",
  "password": "<setup password local>",
  "test_password": "<password GIA 12-64 byte>",
  "test_license_key": "<base64 32 byte test, cung khoa voi app local>",
  "legacy_defaults": {}
}
```

Chỉ sau xác nhận riêng mới dùng lệnh tương lai (điền đúng đường dẫn/runtime/schema; **chưa chạy**):

```powershell
& '<php83.exe>' scripts/local/ita_local_fixture.php --action=plan
& '<php83.exe>' scripts/local/ita_local_fixture.php --action=seed --db=infracore_local_uat --config='<private-local.json>' --execute --ack='Tôi xác nhận cho chạy trên DB local infracore_local_uat'
# Chỉ sau lượt kiểm bộ nhỏ; load yêu cầu vẫn đúng 6 tài sản, chưa thêm/xóa qua UI.
& '<php83.exe>' scripts/local/ita_local_fixture.php --action=load --db=infracore_local_uat --config='<private-local.json>' --execute --ack='Tôi xác nhận cho chạy trên DB local infracore_local_uat'
```

Seed yêu cầu dc_users và16 bảng ita_ rỗng, engine ita_ InnoDB, đủ cột native. Không upsert/cleanup. Native MyISAM INSERT không rollback được: nếu lỗi sau native, **dừng**, ghi seed dở, không retry/reset; xử lý môi trường dùng một lần riêng và xin tên DB/xác nhận mới nếu cần. Không áp script vào DB host kể cả đổi DNS thành loopback. Lỗi CLI chỉ in loại/code, không dump exception/config/password. Thiếu cột bổ sung được rà structure-only và điền legacy_defaults fake trước khi thử lại.

Seed tạo6 tài sản TEST- (3A+3B),5 loại, cấu hình forecast rõ units1.5 cho TEST-BUDGET và0 cho loại khác, maintenance fallback0 **fixture chủ ý**,4 users,2 giấy phép với key giả mã hóa,1 allocation để còn1 chỗ,7 ticket và events,2 network links (một link ngoài scope managerA). Không tạo budget snapshot giả: **lưu draft/tính lại qua UI**. UI hiện không có thao tác finalize; không hứa kiểm chốt bằng giao diện. Assignment/return/ack tạo qua UI, không giả audit thao tác seed thành nghiệp vụ.

Ví dụ ngân sách khả thi: lọc bộ phận B1002 +loại TEST-BUDGET2003, đúng1 asset3006 giá20 triệu/tuổi10 năm/tuổi thọ5;3 ticket closed có cost3 triệu từng năm trong3 năm lịch hoàn chỉnh trước năm seed (mở15/7, UTC03:00). Nhân sự tăng3, định mức1.5, đơn giá20 triệu: thay20 +bảo trì3 +mua thêm100 =123 triệu, dự phòng12.3 => **135.3 triệu cho cohort này**. Chốt năm dự toán/năm đánh giá hiện tại theo VN và kiểm raw/tier thực; không suy tổng mọi loại là135.3. Nếu test sang năm khác, không sửa lịch sử âm thầm; ghi cohort/mốc mới.

Load thêm4994 để **tổng5000**, trong đó đúng500 asset loại2005 TEST-PDF-500 ởA; mỗi10 asset thêm1 repair closed và events cho tải aggregate. Chạy trước khi tạo asset thủ công hoặc dùng DB cô lập thứ hai có xác nhận riêng. Không chạy load hai lần. Guard forecast2000/network200 vẫn giữ: dùng filter hoặc kỳ vọng từ chối theo contract khi vượt, không coi là lỗi và gỡ guard.

Kiểm DB theo [LOCAL_DB_TEST_PLAN.md](LOCAL_DB_TEST_PLAN.md). PHP8.3: **S26 counter trước tiên**, xác nhận dummy algo/cost mặc định8.3 rồi unit/export với counter deprecation theo PHASE11_PHP83_RETEST_PLAN. Kết quả hiện CHƯA CHẠY8.3/DB, không suy chuẩn bị script là nghiệm thu.

## Kiểm chứng khi soạn

PHP CLI8.5.10: lint hai file script và tests/ita_local_fixture_guard.php đạt; **33 pure guard checks đạt**, plan mặc định in kế hoạch, không config/DBIO. Chưa chạy seed/load/migration, chưa kiểm SQL/schema/crypto fixture với DB thật. Review độc lập chỉ đọc guard/schema/source: đã bỏ hướng dẫn finalize UI chưa tồn tại; không nhận xét nào thay bằng chứng DB. Runtime PHP8.3/Apache/Laragon chưa được cung cấp/kiểm.
