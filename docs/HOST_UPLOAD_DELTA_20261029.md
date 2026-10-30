# Delta chỉ file sửa — 29/10/2026

Theo yêu cầu user chỉ bàn giao file đã sửa, dùng **infracore-delta-a7c2da1-to-171edde.zip** thay gói cộng dồn49MB.22file runtime,73.089byte nén; SHA256 `16c52c75287afe0e09f196a052b99bfbedc0778e82db4e9007fdd837edfdbe5a`. Source171edde; nền yêu cầu gói a7c2da1 đã có trên host. Không xác nhận toàn bộ host trùng baseline chỉ từ ảnh; nếu host còn thiếu bản nền, delta này không tự bù các file không đổi.

Delta chọn giao của git diff a7c2da1→171edde và runtime allowlist đã kiểm; có22file mới/sửa, không DELETE. Bao gồm PmAuth CAS và ItaUserProfileService mới bắt buộc, PHP/template/CSS/JS đồng bộ. Không vendor/font/dependency cũ, SQL/config/marker/MOCK hoặc dữ liệu thật. ZIP đọc lại kiểm inventory/CRC/hash/metadata/exclusions đạt. Các bytes là cùng source đã qua kiểm local trong gói cộng dồn; không build runtime khác.

1. Backup22file sẽ bị thay, config riêng và DB/key ngoài webroot. Không gửi backup/credential lên chat.
2. Giải nén local. Upload **nội dung trong runtime/** vào document root InfraCore, giữ đường dẫn và chỉ ghi đè22file. Không upload ZIP/manifest/checksum hoặc mirror/delete.
3. Giữ config/cờ/quyền DB hiện có; không tự chạy SQL. Ctrl+F5 và smoke theo HOST_UPLOAD_PHASE14_20261029.md; xem các giới hạn grants, nguồn gốc user, reset/MyISAM/legacy/PHP8.3 trong đó.
4. Rollback đúng22file backup; giữ DB/audit/tài khoản/password mới. Rollback source không đảo password/quyền đã ghi. Chưa upload/DB/host nghiệm thu.
