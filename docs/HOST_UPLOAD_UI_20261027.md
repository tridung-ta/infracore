# Upload giao diện InfraCore — 27/10/2026

Gói **delta FE 19 file**, cho nền InfraCore hiện có đã dùng các màn/dashboard/network và Three.js0.180.0. Không phải bộ cài mới. Không PHP/SQL/config/marker/vendor/key, không migration; không tự cập nhật backend hoặc schema. Gồm các cải tiến giao diện tại092afec, ed34749 và704b818; source payload cố định **704b8189b7034d8d8b1e1576488437b7d6a0945c**.

- ZIP: `artifacts/ita-host-20261027/infracore-ui-update-704b818.zip`
- Kích thước: **40.491 byte**;19 runtime file,100.432 byte trước nén; MANIFEST.json và SHA256SUMS.txt là2 entry metadata riêng.
- SHA256: `ed27ce03c8c4f07fe16e9356cf8920dbb2bab6e5a324894fdb607344e02026ff`
- Đã đọc lại ZIP thật: tập file, đường dẫn/loại trừ, CRC, size/SHA256 từng file, manifest/checksums đạt. PowerShell Get-FileHash xác nhận cùng ZIP hash. Chưa upload/kiểm host/PHP8.3/FPM/DB.

## 1. Tải gói và backup

1. Tải ZIP về máy. Có thể mở PowerShell tại thư mục đã tải và chạy `Get-FileHash -Algorithm SHA256 -LiteralPath '.\infracore-ui-update-704b818.zip'`, so với hash trên. Không chạy script trên host.
2. Trong FileZilla, mở đúng vhost InfraCore: `domains` → `infracore.dung.derasoft.com` → `public_html` theo cấu trúc hosting hiện có. Đối chiếu đây là thư mục site InfraCore chứa admin.php, assets và templates; không chọn public_html của domain khác hoặc gốc tài khoản.
3. Trước ghi đè, tải bản backup riêng ngoài webroot của các file trong bảng dưới; cách dễ hơn là backup ba cây `assets/ita/css`, `assets/ita/js`, `templates/admin/ita` từ host về thư mục backup mới. User tự backup dump DB qua công cụ hosting được phép, giữ ngoài webroot/Git và không gửi credential/dump vào chat. Agent không chạy DB hoặc tạo dump.
4. Ghi ngày/giờ và vị trí backup. Không dùng ZIP cũ làm bằng chứng source host; nếu nền thiếu dashboard/network/Three.js hoặc không xác định tương thích, dừng upload để đối chiếu.

## 2. Giải nén và upload từng bước

1. Giải nén ZIP **trên máy local** vào thư mục mới. Bên trong có `runtime`, `MANIFEST.json`, `SHA256SUMS.txt`.
2. Khung bên trái FileZilla: mở **bên trong runtime/**. Bạn sẽ thấy hai thư mục **assets** và **templates**.
3. Khung bên phải: giữ đúng public_html của InfraCore.
4. Chọn **assets** và **templates** ở bên trái → chuột phải → **Upload**. Gộp vào hai thư mục hiện có và giữ nguyên đường dẫn. Chỉ ghi đè các file có trong gói; không dùng đồng bộ/xóa file ngoài danh sách.
5. **Không** kéo cả thư mục runtime lên host. Không upload ZIP, MANIFEST, SHA256SUMS, tài liệu hoặc file verify/review lên public_html. Không upload toàn workspace.
6. Chờ hàng đợi truyền hết, kiểm tab **Các lần truyền gặp lỗi** không còn file của lượt này. Sau đó mở site và nhấn **Ctrl+F5**; Ctrl+0 để xem ở zoom100%.

## 3. Danh sách19 file

| Nhóm | Path tương đối từ public_html |
|---|---|
| CSS | assets/ita/css/ita-tokens.css |
| CSS | assets/ita/css/ita-refinements.css |
| CSS | assets/ita/css/ita-focus.css |
| CSS | assets/ita/css/ita-login.css |
| CSS | assets/ita/css/ita-network.css |
| JS | assets/ita/js/ita-appearance.js |
| JS | assets/ita/js/ita-assignments-live.js |
| JS | assets/ita/js/ita-assignments.js |
| JS | assets/ita/js/ita-dashboard.js |
| JS | assets/ita/js/ita-form-guidance.js |
| JS | assets/ita/js/ita-network-3d.js |
| JS | assets/ita/js/ita-network-scene.js |
| JS | assets/ita/js/ita-network.js |
| JS | assets/ita/js/motion/ita-core.js |
| Smarty | templates/admin/ita/layout.tpl.html |
| Smarty | templates/admin/ita/login.tpl.html |
| Smarty | templates/admin/ita/partials/icons.tpl.html |
| Smarty | templates/admin/ita/partials/sidebar.tpl.html |
| Smarty | templates/admin/ita/partials/network.tpl.html |

Các nhánh hỗ trợ MOCK trong JS production được giữ nhưng live có guard không tải fixture. File MOCK/preview thực, tests/tmp/artifacts/scripts, config/.env/dump, marker/.htaccess/.user.ini không nằm trong gói. Header `kind` chung của builder vẫn ghi cumulative host test update; **package_type ghi rõ UI FE DELTA EXACTLY19FILES** và tập file trên là phạm vi thực, không phải cumulative backend update.

## 4. Smoke test sau upload — user thực hiện

Ghi kết quả từng mục Đạt/Không/Chưa kiểm, source/gói/ngày upload thực. Chỉ ghi “kiểm thủ công trên host, phạm vi hạn chế”; không suy nghiệm thu DB.

1. Dùng đúng https:// và kiểm cảnh báo chứng chỉ của trình duyệt; ảnh trước có “Không bảo mật”, không coi URL https là đã kiểm TLS. Gói FE này không sửa chứng chỉ/cấu hình host.
2. Trang đăng nhập: form hiện đúng, chuyển Command/Focus, hiện/ẩn mật khẩu, mobile không tràn. Đăng nhập bằng tài khoản được phép. Login thành công có thể ghi dc_users.last_login và password_hash khi upgrade/needs_rehash với password<=72byte; cột MD5 giữ nguyên. Gói này không đổi hành vi đó.
3. Trong app, đổi chế độ ở sidebar: nền/form/bảng khác rõ; bộ lọc và nội dung đang nhập giữ nguyên. Máy có giảm chuyển động vẫn đổi phong cách được nhưng không ép animation.
4. Tài sản/phân bổ: đọc một hồ sơ được phép, lịch sử/trạng thái/PDF rõ; không cố ghi dữ liệu thật chỉ để thử giao diện. Nếu kiểm ghi, chỉ dùng hồ sơ TEST được phép theo bộ hướng dẫn đã bàn giao.
5. Tổng quan/báo cáo: số liệu vẫn tải, đổi màu biểu đồ không thay tổng, không503. Đây là quan sát UI, không chứng minh isolation kết nối DB.
6. Hạ tầng mạng: 2D/3D, hình theo loại/tên, chọn khối/tên, kéo360°, xoay nút, fullscreen/Esc; đổi theme vẫn có đúng một scene và tên. Màu/hình là minh họa từ metadata, không đo kết nối mạng thật.
7. DevTools Network: các CSS/JS mới trả200; không404 cho ita-focus.css/ita-login.css/ita-refinements.css/ita-appearance.js/ita-form-guidance.js. Kiểm cookie/header cuối theo PHASE11_HOST_TEST, không gửi cookie/password/HAR/key vào báo cáo.

Nếu giao diện cũ còn hiện sau Ctrl+F5, báo lại ảnh/status/console đã che; không xóa toàn bộ cache/session hoặc sửa config làm lối tắt. Host có thể cần kiểm compiled Smarty theo cơ chế quản trị đã được phép, không tự thêm PHP debug endpoint.

## 5. Hoàn tác

Nếu có lỗi sau upload, dừng thao tác ghi rồi phục hồi **đúng các file đã thay** từ backup host; không lấy bản local bất kỳ thay backup. Với file mới trước đây chưa có, khôi phục template/JS cũ sẽ không tham chiếu nữa; không cần xóa nguồn ngoài danh sách để làm rollback. Ctrl+F5 và kiểm lại login/chức năng cũ.

Giữ DB, tài khoản, password_hash/last_login, audit và mọi dữ liệu phát sinh. **Không restore dump đè, không DROP/DELETE hoặc chạy migration ngược** để hoàn tác gói FE. Ghi thời điểm/source đã phục hồi và mục chưa kiểm.
