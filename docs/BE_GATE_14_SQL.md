# BE-GATE #14 SQL — NHÁP, CHƯA DUYỆT/CHƯA CHẠY

Source BE14 đã duyệt riêng; tài liệu này **không phải quyền chạy SQL, kết nối DB, bật config hoặc upload**. Không có migration007 đã áp dụng. Không dùng config/credential host để soạn.

Phạm vi đề xuất: thêm một bảng ita_user_permissions cho catalogue trong ItaPermissions::KEYS, mapping ban đầu từ ba preset source đã review; không thay schema/dữ liệu dc_*. Scope toàn store dùng permission scope.store, không thêm cột vào bảng cũ.

DDL dự kiến cho migration007, **chỉ văn bản chưa thực thi**; tên schema đích phải chốt riêng, không hard-code USE production:

```sql
CREATE TABLE ita_user_permissions (
    store_id INT NOT NULL,
    user_id INT NOT NULL,
    permission_key VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    active TINYINT UNSIGNED NOT NULL DEFAULT 1,
    updated_by INT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (store_id,user_id,permission_key),
    KEY idx_ita_permissions_key (store_id,permission_key,active,user_id),
    CONSTRAINT ck_ita_permissions_store CHECK (store_id > 0),
    CONSTRAINT ck_ita_permissions_user CHECK (user_id > 0),
    CONSTRAINT ck_ita_permissions_active CHECK (active IN (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

Không FK vào dc_users MyISAM. Không DDL từ runtime. Runtime cần SELECT/INSERT/UPDATE đúng bảng mới; không DELETE/CREATE/ALTER/DROP/LOCK TABLES/CREATE TEMPORARY TABLES mới. Migration bằng tài khoản DDL riêng do user khi duyệt.

Trước duyệt/thực hiện sau này:

1. Xác nhận đúng schema/môi trường/source/hash đang chạy, version/engine/collation, đã có005/auth_revision và migration nền cần thiết. Backup source/config private và DB ngoài webroot; xác minh phục hồi. Không suy16 bảng local đồng nghĩa host đủ.
2. Dry-run bằng inventory/query đọc: danh sách mapping role/enabled/store hiện có, catalogue preset từ source cố định, đếm quyền dự kiến mỗi tài khoản; loại bỏ dữ liệu thật khỏi báo cáo. Không chạy INSERT ở bước dry-run. Đối chiếu dependency và đảm bảo ít nhất một native active quản lý quyền sau mapping.
3. Sau gate riêng mới tạo007 chính xác +mapping có scope store rõ ràng, review SQL và kiểm DB cô lập được user cho phép. Backfill trong transaction InnoDB, không sửa native; ghi người thực hiện/canonical permission keys và tăng auth_revision cho mapping bị đổi. Không ghi đè grants tùy chỉnh đã tồn tại; phát hiện trùng/migration dở phải dừng đối chiếu.
4. User review C=1/2/6, no-permission403, CSRF/version/revision/scope, grant subset/last-manager và export recheck; quyền key vẫn reauth. Kiểm EXPLAIN/count/lock/concurrency riêng, không suy từ fake tests.
5. Bật cờ chỉ sau quyền và schema đầy đủ bằng config đã duyệt cho đúng vhost. Không bật trên host trong đợt source này. Nếu thiếu bảng/quyền hoặc503 thì dừng, không fallback ngầm.

Rủi ro: map sai mở rộng/thu hẹp quyền, mất manager cuối, cấu hình bật sớm gây503, native MyISAM thay đổi ngoài transaction, rollback role-mode không tương đương tổ hợp tùy chỉnh. Hoàn tác nhanh: dừng ghi, phục hồi source/cờ đã backup sau review policy; giữ bảng007, grants/audit/dữ liệu mới để đối chiếu; không DROP/DELETE/reset credential/restore đè. Thiếu quyền mới thu đúng quyền đã thêm sau khi app không còn tham chiếu, qua quản trị riêng; không tự phát GRANT/REVOKE.

Trả lời mong muốn ở bước sau: duyệt SQL007/mapping và môi trường cụ thể, hoặc chỉnh đề xuất. Hiện **chưa xin chạy hoặc kết nối bất kỳ DB nào**; chưa đóng gói/upload/push/merge/tag7–10.
