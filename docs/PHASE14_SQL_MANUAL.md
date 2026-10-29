# Phase14 — SQL do user chạy thủ công, 28/10/2026

User yêu cầu agent chỉ gửi câu lệnh SQL để user thao tác. Đã soạn file, **chưa kết nối/chạy DB, chưa bật cờ hoặc tạo gói/upload**. Dùng Database Safety cho bước chuẩn bị. Không cần gửi credential, hash hay dump cho agent.

## 1. Chọn đúng DB và sao lưu

Trong phpMyAdmin, bấm đúng database của InfraCore ở cột trái. Kiểm tên thật, không mặc định từ hồ sơ cũ. Export backup DB, backup source/config private ngoài webroot trước khi đổi. Dùng tài khoản DDL riêng; không dùng nó làm credential ứng dụng.

Vào tab **SQL**, mở file [phase14_preflight.sql](../database/host/phase14_preflight.sql), sao chép nội dung và chạy. Phải có ita_user_roles (auth_revision), ita_user_scopes, ita_departments, ita_audit_logs theo nền005; bảng ITA cần InnoDB. Phải có ít nhất một Admin mapping bật và native active trong store định chuyển. Nếu thiếu nền hoặc bảng ita_user_permissions đã tồn tại: dừng, đối chiếu cấu trúc/dữ liệu, không chạy lại CREATE/mapping để chữa.

## 2. Tạo bảng

Khi preflight đúng và backup có thật, mở [007_ita_user_permissions.sql](../database/migrations/007_ita_user_permissions.sql), dán vào tab SQL và chạy một lần trên database vừa xác nhận. Chỉ thêm bảng, không ghi dc_*. CREATE TABLE có implicit commit: ROLLBACK không hoàn tác DDL. Không dùng IF NOT EXISTS để bỏ qua schema sai.

## 3. Xem trước mapping trong transaction

Tạm ngừng ứng dụng/thao tác ghi và thay quyền trong cửa sổ chuyển đổi; không đổi trạng thái native từ hệ thống khác. MyISAM dc_users không được bảo vệ bởi transaction ITA. Giữ cờ capabilities tắt.

Mở [phase14_permissions_backfill.sql](../database/host/phase14_permissions_backfill.sql). Sửa đúng ba dòng đầu, ví dụ minh họa (thay bằng giá trị đã xác minh, không đoán ID):

```sql
SET @expected_schema = 'TEN_DATABASE_DA_XAC_MINH';
SET @store_id = 1; -- chỉ dùng1 nếu đúng store của bạn
SET @operator_id = 123; -- thay bằng user ID Admin IT active thực tế
```

Chạy **toàn bộ file trong một lần trên cùng connection**. Mặc định cuối file là `ROLLBACK;`: chỉ xem trước, không lưu mapping/revision. Guard ready_must_equal_1 phải bằng1. Nếu NULL/0 hoặc có bất kỳ SQL error: ROLLBACK, dừng và đối chiếu; không tiếp tục COMMIT. Nếu công cụ không giữ transaction cho cả block, chưa dùng để áp dụng.

Mapping lấy nguyên preset source tại65465bf: Admin47 quyền, Quản lý29, Hỗ trợ12; mỗi tài khoản enabled trong store được một preset tương ứng. Không cấp cho mapping disabled. Không ghi đè bất kỳ quyền nào: store đã có dù một row permission sẽ bị guard từ chối. Native enabled phải tồn tại/active, role hợp lệ và tài khoản không phải Admin phải có scope bộ phận active; auth_revision không được tràn. Guard lỗi không tự sửa dữ liệu để vượt qua.

Kiểm danh sách/count dự kiến với vai trò hiện có, operator đúng và không có quyền tùy chỉnh cần bảo tồn. Nếu mapping dự kiến không đúng ý, chưa áp dụng.

## 4. Áp dụng sau khi đã kiểm kết quả xem trước

Giữ cửa sổ ngừng ghi. Chạy lại **toàn bộ block đã điền ba giá trị**, chỉ thay dòng cuối `ROLLBACK;` bằng `COMMIT;`. Không thêm COMMIT sau ROLLBACK và không chạy từng phần rời. SQL chỉ ghi ita_user_permissions và tăng auth_revision/updated_by của mapping enabled; phiên cũ có thể hết hiệu lực. Không sửa password/status/last_login native bằng SQL này. Việc login sau đó vẫn có native writes như S26_AUTH_VERIFY mô tả.

Nếu có lỗi SQL trước COMMIT, chạy ROLLBACK và dừng. Sau COMMIT, mở [phase14_verify.sql](../database/host/phase14_verify.sql) kiểm lại bảng/count. Lưu xác nhận vận hành mapping cùng thời điểm/source/store/count ở hồ sơ private; script SQL thủ công không tự tạo bản audit nghiệp vụ user.access_update và không giả mạo nó. Chưa có kết quả thực để ghi PASS.

## 5. Quyền runtime và kích hoạt là bước riêng

Runtime cần SELECT/INSERT/UPDATE riêng bảng ita_user_permissions. Quản trị xác minh đúng runtime user/effective grants, không mặc định user phpMyAdmin là runtime. Không cấp CREATE/ALTER/DROP/DELETE/LOCK TABLES hay quyền toàn server vì bước này. Chưa biết identity/grants thật nên không cung cấp GRANT với user đoán.

**Chạy SQL xong chưa bật phân quyền mới.** Cần source BE14 đồng bộ +cấu hình ITA_CAPABILITIES_ENABLED được bật đúng vhost trong bước triển khai riêng sau xác nhận schema/mapping/grants. Agent không sửa config/bật host. Kiểm Admin còn quản lý được quyền, C phối hợp chức năng, no-permission403, scope, phiên cũ/revision, CSRF, version và export; không gọi là nghiệm thu từ count SQL.

Hoàn tác: giữ tính năng chưa bật nếu kiểm chưa đạt; nếu đã rollout, dừng ghi và khôi phục source/cờ đã backup sau review quyền (role-mode không tương đương quyền tùy chỉnh). Giữ bảng/mapping/audit/dữ liệu mới để đối chiếu, không DROP/DELETE hoặc restore dump đè dữ liệu. Không có tự động rollback DDL.

File SQL đặt ngoài webroot, không upload vào public_html hoặc đưa vào runtime ZIP. Gửi kết quả đã che thông tin nhạy cảm: schema/store đã xác nhận, ready, count ba role và lỗi nếu có; không gửi dữ liệu cá nhân/credential. Đây là kiểm thủ công do user, chưa phải agent chạy DB hoặc nghiệm thu toàn dự án.
