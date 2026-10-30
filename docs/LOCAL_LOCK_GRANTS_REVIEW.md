# Locking SELECT và grants runtime — rà source26/10/2026

Chỉ đọc source, không kết nối DB, không cấp/thu quyền. Phạm vi runtime **ITA bật/legacy tắt**; quét classes/modules/includes, đối chiếu call chain ITA. Mọi route dưới cùng prefix `/admin.php?op=ita&act=`. Không có `LOCK IN SHARE MODE` tường minh trong tập ITA đã rà; SERIALIZABLE có thể khiến DB dùng shared locking cho SELECT thường, không phải câu source mới.

## Quyền: không suy MySQL sang MariaDB10.6

**Không đề xuất thêm UPDATE ita_departments chỉ để SELECT FOR UPDATE trên MariaDB10.6.** Nhánh SQLCOM_SELECT trong [source MariaDB10.6 sql_parse.cc](https://github.com/MariaDB/server/blob/10.6/sql/sql_parse.cc) và mysql_test_select trong [sql_prepare.cc](https://github.com/MariaDB/server/blob/10.6/sql/sql_prepare.cc) kiểm SELECT_ACL, gồm prepared SELECT. Đây là kết luận đọc source upstream nhánh10.6, **chưa phải kiểm grant trên bản Laragon thực**. Không lấy yêu cầu của MySQL/phiên bản khác để cấp UPDATE rộng hơn cho MariaDB dự kiến.

[Tài liệu MariaDB FOR UPDATE](https://mariadb.com/docs/server/reference/sql-statements/data-manipulation/selecting-data/for-update) yêu cầu transaction/autocommit0 để khóa InnoDB có hiệu lực; quyền SQL và hiệu lực khóa là hai bước kiểm riêng. Không cần quyền LOCK TABLES cho các locking SELECT này. Query ngoài/subquery có phạm vi lock khác nhau; `EXISTS(dc_users)` dưới đây không được suy thành khóa native atomic. Không cam kết MyISAM rollback.

LOCAL_RUN giữ ita_departments **SELECT-only**, không INSERT/UPDATE vì runtime hiện không có writer bộ phận. Các bảng còn lại đã có UPDATE dự kiến vì **UPDATE thật**, không phải do chữ FOR UPDATE: assets, categories, assignments(return), licenses(metadata), allocations(release), roles/scopes, login_limits, settings, plans, imports, tickets, network_links. Ticket_events chỉ INSERT/SELECT theo source hiện tại: có thể bỏ UPDATE dự kiến cũ. Audit SELECT/INSERT, không UPDATE. dc_users giữ UPDATE(last_login,password_hash) vì login, không thêm UPDATE(id/status/store_id) để khóa. Trước cấp, phải rà effective grants/role của runtime thực và chạy từng locking query với đúng params fake sau xác nhận DB; denied thì ghi errno/query/table, không tự nới quyền.

## Tất cả36 vị trí locking SELECT ITA

SQL ở cột cuối là signature chính xác các phần SELECT/FROM/WHERE khóa, `?` giữ bind; dấu `…` chỉ bỏ danh sách cột hoặc điều kiện đã diễn giải, không phải SQL để copy chạy. Một vị trí reference động sinh hai bảng từ allowlist; không bỏ qua nhánh `$lock/$locking`.

| Bảng khóa | File trong classes/ (dòng tại source1b041db) | Route/call chain | Câu/điều kiện khóa |
|---|---|---|---|
|ita_login_limits|security/italoginlimiter.class.php:19|POST login; POST license-key limiter|SELECT attempts,UNIX_TIMESTAMP(UTC_TIMESTAMP())-UNIX_TIMESTAMP(window_started) age FROM ita_login_limits WHERE store_id=? AND bucket_hash=? FOR UPDATE|
|ita_assets|dao/itaassignmentobjects.class.php:10|POST assignment-save; ticket-save qua TicketObjects.asset(true)|SELECT * WHERE store_id=? AND id=? AND is_deleted=0 +FOR UPDATE nếu locking|
|ita_assets|services/itaassetservice.class.php:47|POST asset-save, lockAsset|SELECT * WHERE store_id=? AND id=? AND is_deleted=0 FOR UPDATE|
|ita_asset_categories|services/itaassetservice.class.php:89|POST asset-save create/update|SELECT id,status WHERE store_id=? AND id=? FOR UPDATE|
|ita_departments|services/itaassetservice.class.php:95|POST asset-save create/update có bộ phận|SELECT id WHERE store_id=? AND id=? AND status='active' FOR UPDATE|
|ita_assignments|services/itaassetservice.class.php:137|POST asset-save guard đổi trường/xóa|SELECT id WHERE store_id=? AND asset_id=? AND returned_at IS NULL LIMIT1 FOR UPDATE|
|ita_assignments|services/itaassignmentservice.class.php:21|POST assignment-save|SELECT * WHERE store_id=? AND asset_id=? AND returned_at IS NULL LIMIT1 FOR UPDATE|
|ita_user_roles|services/itaassignmentservice.class.php:47|POST assignment-save lockActor|SELECT role,enabled,auth_revision WHERE store_id=? AND user_id=? FOR UPDATE|
|ita_departments|services/itaassignmentservice.class.php:102|POST assignment-save kiểm nơi nhận|SELECT id WHERE store_id=? AND id=? AND status='active' FOR UPDATE|
|ita_user_roles (r); dc_users(u) trong EXISTS đọc|services/itaaccessservice.class.php:32|POST access|SELECT r.user_id,r.role,r.enabled,r.auth_revision FROM roles r WHERE r.store_id=? AND EXISTS(SELECT1 FROM dc_users u WHERE u.store_id=r.store_id AND u.id=r.user_id AND u.status=1) FOR UPDATE|
|ita_asset_categories|services/itacategoryservice.class.php:53|POST category-save sửa|SELECT id,code,name,status WHERE store_id=? AND id=? FOR UPDATE|
|ita_licenses|dao/italicenseobjects.class.php:9|POST license-save row(true)|SELECT * WHERE store_id=? AND id=? +FOR UPDATE nếu lock|
|ita_user_roles|services/itaforecastservice.class.php:9|POST forecast-config-save/forecast-plan-save/forecast-export|SELECT role,enabled,auth_revision WHERE store_id=? AND user_id=? FOR UPDATE|
|ita_user_scopes|services/itaforecastservice.class.php:14|Như trên, manager|SELECT department_id WHERE store_id=? AND user_id=? AND active=1 ORDER BY department_id FOR UPDATE|
|ita_asset_categories|services/itaforecastservice.class.php:18|Như trên|SELECT id WHERE store_id=? ORDER BY id LIMIT501 FOR UPDATE|
|ita_settings|services/itaforecastservice.class.php:20|Như trên|SELECT setting_key WHERE store_id=? ORDER BY setting_key FOR UPDATE|
|ita_departments|services/itaforecastservice.class.php:71|POST forecast-plan-save|SELECT id WHERE store_id=? AND id=? FOR UPDATE|
|ita_budget_plans|services/itaforecastservice.class.php:73|POST forecast-plan-save|SELECT * WHERE store_id=? AND year=? AND department_id=? FOR UPDATE|
|ita_asset_categories, ita_departments|services/itaimportrows.class.php:62|POST import-confirm validate rows locking=true|SELECT id FROM $table WHERE store_id=? AND code=? AND status='active' +FOR UPDATE; hai tên bảng hằng qua reference()|
|ita_import_batches|services/itaimportservice.class.php:102|POST import-confirm batch(true)|SELECT *,expires_at>UTC_TIMESTAMP() available WHERE store_id=? AND user_id=? AND id=? +FOR UPDATE|
|ita_user_roles|services/itaimportservice.class.php:129|POST import-confirm actor|SELECT role,enabled,auth_revision WHERE store_id=? AND user_id=? FOR UPDATE|
|ita_user_roles|services/italicensereveal.class.php:23|POST license-key|SELECT role,enabled,auth_revision WHERE store_id=? AND user_id=? FOR UPDATE|
|ita_user_roles|services/italicenseservice.class.php:39|POST license-save|SELECT role,enabled,auth_revision WHERE store_id=? AND user_id=? FOR UPDATE|
|ita_license_allocations|services/italicenseservice.class.php:81|POST license-save release|SELECT * WHERE store_id=? AND license_id=? AND id=? FOR UPDATE|
|ita_assets|services/italicenseservice.class.php:111|POST license-save per_device allocate/release|SELECT department_id,status,is_deleted WHERE store_id=? AND id=? FOR UPDATE|
|ita_departments|services/italicenseservice.class.php:121|POST license-save per_department allocate/release|SELECT id WHERE store_id=? AND id=? +(status active khi allocate) FOR UPDATE|
|ita_user_roles|services/itanetworkservice.class.php:8|POST network-save actor; GET network/network-detail không gọi actorLock|SELECT role,enabled,auth_revision WHERE store_id=? AND user_id=? FOR UPDATE|
|ita_assets|services/itanetworkservice.class.php:42|POST network-save endpoint locks|SELECT id,category_id,is_deleted WHERE store_id=? AND id=? FOR UPDATE|
|ita_network_links|services/itanetworkservice.class.php:47|POST network-save sửa/xóa|SELECT * WHERE store_id=? AND id=? AND is_deleted=0 FOR UPDATE|
|ita_network_links|services/itanetworkservice.class.php:64|POST network-save duplicate|SELECT id WHERE store_id=? AND source_asset_id=? AND target_asset_id=? AND is_deleted=0 FOR UPDATE|
|ita_user_roles|services/itareportservice.class.php:102|POST report-export audit sau render; GET dashboard/charts/alerts/report-preview không gọi audit này|SELECT role,enabled,auth_revision WHERE store_id=? AND user_id=? FOR UPDATE|
|ita_user_scopes|services/itareportservice.class.php:105|POST report-export recheck manager sau render|SELECT department_id WHERE store_id=? AND user_id=? AND active=1 ORDER BY department_id FOR UPDATE|
|ita_tickets|services/itaticketguard.class.php:8|Asset/assignment/ticket writers clear guard|SELECT id WHERE store_id=? AND asset_id=? AND status='pending' LIMIT1 FOR UPDATE|
|ita_tickets|services/itaticketservice.class.php:28|POST ticket-save assign/transition/cost|SELECT * WHERE store_id=? AND id=? AND asset_id=? FOR UPDATE|
|ita_tickets|services/itaticketservice.class.php:32|POST ticket-save operation khác cost|SELECT id WHERE store_id=? AND asset_id=? AND status='pending' AND id<>? LIMIT1 FOR UPDATE|
|ita_user_roles|services/itaticketservice.class.php:50|POST ticket-save|SELECT role,enabled,auth_revision WHERE store_id=? AND user_id=? FOR UPDATE|

Bảng trên có **36 vị trí source** (reference động tạo2 bảng); dùng danh sách dòng làm bằng chứng, không dùng số bảng làm số câu. Source line không thay provenance commit; khi code đổi phải quét lại. Command chỉ đọc: `rg -n -i 'for update|lock in share mode' classes -g 'ita*.php'`.

Ngoài ITA, quét rộng còn gặp locking SELECT ở PmTimesheetService/PmRateService/PmProjectService/PmAllocationService/PmAllocations và editorialVNPayPayments/EditorialPaymentTransactions. Chúng thuộc legacy, không nằm call chain ITA khi legacytắt, **không đề xuất thêm quyền các dc_pm_*/editorial tables**. Không kết luận những route legacy an toàn; mở lại legacy phải audit/grants riêng. Quét này không bao phủ SQL được plugin/server/routine ngoài repo sinh; host chưa inventory.
