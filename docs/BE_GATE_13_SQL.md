# BE-GATE #13-SQL — NHÁP DDL, CHƯA DUYỆT / CHƯA CHẠY

User đã duyệt **BUILD source BE13**, chưa duyệt SQL này. Đây là bản đề xuất chính xác để review riêng, không phải migration đã áp dụng. Agent không tạo/kết nối DB, không chạy DDL/seed/EXPLAIN trên host. Không thay bảng hiện có hoặc thêm index vào bảng cũ. Dữ liệu mẫu chỉ JSON MOCK; không seed mạng thật.

## Thay đổi dự kiến

Một bảng `ita_network_links`, dùng InnoDB/utf8mb4 như baseline. Đối chiếu source `002_ita_assets.sql`: store_id INT có dấu; asset id BIGINT UNSIGNED; unique (store_id,id) đã có. Hai FK composite chống nối nhầm store. Chỉ hardware chưa soft-delete được writer PHP chấp nhận, không suy FK tự kiểm điều kiện đó.

```sql
-- CHƯA DUYỆT: user không chạy trước khi xác nhận gate SQL và backup.
CREATE TABLE `ita_network_links` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `store_id` INT NOT NULL,
  `source_asset_id` BIGINT UNSIGNED NOT NULL,
  `target_asset_id` BIGINT UNSIGNED NOT NULL,
  `link_type` ENUM('ethernet','fiber','wireless') NOT NULL,
  `source_port` VARCHAR(64) NOT NULL DEFAULT '',
  `target_port` VARCHAR(64) NOT NULL DEFAULT '',
  `capacity_label` VARCHAR(64) NOT NULL DEFAULT '',
  `note` TEXT NOT NULL,
  `is_deleted` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `active_slot` TINYINT GENERATED ALWAYS AS
      (CASE WHEN `is_deleted` = 0 THEN 1 ELSE NULL END) STORED,
  `created_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `updated_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
      ON UPDATE CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ita_network_pair` (`store_id`,`source_asset_id`,`target_asset_id`,`active_slot`),
  KEY `idx_ita_network_list` (`store_id`,`is_deleted`,`id`),
  KEY `idx_ita_network_target` (`store_id`,`target_asset_id`),
  CONSTRAINT `fk_ita_network_source` FOREIGN KEY (`store_id`,`source_asset_id`)
      REFERENCES `ita_assets` (`store_id`,`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_ita_network_target` FOREIGN KEY (`store_id`,`target_asset_id`)
      REFERENCES `ita_assets` (`store_id`,`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ck_ita_network_store` CHECK (`store_id` > 0),
  CONSTRAINT `ck_ita_network_pair` CHECK (`source_asset_id` < `target_asset_id`),
  CONSTRAINT `ck_ita_network_deleted` CHECK (`is_deleted` IN (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

`active_slot=1` chỉ cho một cặp vô hướng đang dùng; NULL cho các hồ sơ xóa mềm để giữ lịch sử và cho phép tạo lại cặp. Writer chuẩn hóa ID tăng dần và hoán đổi cổng theo đầu tương ứng. Version SHA256 từ các trường lưu + updated_at(6), không thêm cột revision. Không FK/ghi dc_*; user_id audit do schema audit hiện có xử lý.

## Điều kiện và cách kiểm sau khi được duyệt riêng

User xác nhận phiên bản DB thực, môi trường/phạm vi, source và backup source/DB trước thao tác. Kiểm SHOW CREATE TABLE ita_assets, unique composite và kiểu/collation/engine thật; dừng nếu khác baseline, bảng đã tồn tại hoặc tên constraint xung đột. Không chạy CREATE IF NOT EXISTS để che cấu trúc khác. Host hồ sơ MariaDB10.6.28 chưa thay kiểm runtime; generated column/unique nullable/CHECK/FK cần kiểm thực tế khi được phép. Runtime không cần CREATE/ALTER/DROP/DELETE; DDL dùng tài khoản riêng do user quản trị, không đặt trong config ứng dụng.

Sau tạo (chỉ user thực hiện khi được duyệt): SHOW CREATE TABLE/metadata; fake pair đúng/sai store, duplicate đảo đầu, soft-delete rồi tạo lại, scope hai đầu, audit failure rollback và hai phiên tạo cặp trùng. Các kiểm ghi không được suy là đã cho phép chỉ từ tài liệu này. Query EXPLAIN đọc ở PHASE13_EXPLAIN.sql; index trên là thiết kế mới chưa đo, không tuyên bố tối ưu. Nếu cần index khác/bảng cũ phải gate SQL mới.

## Rủi ro và hoàn tác

DDL có implicit commit, không giả transaction rollback CREATE. Unique chỉ đúng khi CHECK/chuẩn hóa và cấu trúc được xác minh; native dc_users MyISAM có cửa sổ thu hồi quyền không khóa được bằng transaction InnoDB. READ của sơ đồ và writer yêu cầu RR/SERIALIZABLE; không tự đổi isolation server. Chưa đo tải/EXPLAIN/FK/lock/rollback DB thật.

Nếu chưa áp dụng DDL: không có gì để hoàn tác. Nếu đã tạo và có dữ liệu: rollback source đã backup, giữ bảng/liên kết/audit; không DROP hoặc restore dump đè. Chỉ cân nhắc gỡ bảng trong một gate riêng với backup và xác nhận không dữ liệu cần giữ; không cung cấp DOWN tự chạy. Không upload source BE13 đơn lẻ khi host thiếu bảng/nền tương thích.

Trả lời mong muốn cho bước sau: **Duyệt SQL #13 / Chỉnh sửa / Chưa duyệt**. Duyệt SQL không phải quyền agent chạy host/DB; user tự thực hiện thao tác đã thống nhất.
