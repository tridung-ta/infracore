# Chạy khung PHP InfraCore local

Từ thư mục gốc repo:

```powershell
php -S 127.0.0.1:18768 router.php
```

Mở http://127.0.0.1:18768/admin.php?op=ita. Dữ liệu vẫn MOCK. Không cần cấu hình hoặc kết nối DB để xem khung này; không coi đây là bản quản trị đã có đăng nhập.

Cờ do operator quản lý, không phải URL query:

- Có .ita-enabled, không có .ita-legacy-enabled: khung ITA; route cũ ẩn, POST bị chặn.
- Có cả hai: ITA_LEGACY_UI_ENABLED=true; đi lại đường bootstrap legacy. Chỉ operator bật sau khi xác nhận config/DB đúng; đây không phải test tự động với hosting.
- Không có .ita-enabled: cơ chế .legacy-hidden trước đó vẫn áp dụng. Giữ file .legacy-hidden để fallback an toàn.

Chưa upload gói này lên host; Apache rewrite, host PHP 8.3 và legacy restoration cần kiểm riêng. Không upload config/dump/backup/test từ workspace. Bản gốc các file routing đã lưu ngoài cây runtime tại .source-backup/ita-phase01-routing trong workspace local.