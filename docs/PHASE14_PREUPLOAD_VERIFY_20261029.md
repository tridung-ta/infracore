# Kiểm lại và bàn giao local — 29/10/2026

User yêu cầu chạy lại toàn bộ sửa và gửi file upload. Source runtime cố định `171edded9973dcee3a9d8e1f38eddfdcf8b03c30`; không lấy working diff riêng docs/PHASE11_SECURITY_TEST_20.md. Không đọc private config, kết nối DB hoặc chạy host. Đây là gói cập nhật kiểm host, không nghiệm thu toàn bộ DB/production.

## Kết quả chạy thật

| Nhóm | Kết quả | Giới hạn |
|---|---|---|
|PHP unit03–10|39/88/203/159/237/72/134/52 checks đạt|Pure/fake transport;04 có XLSX thật, mask deprecation cũ còn giữ|
|S11 exports / BE13|30/54/179 checks đạt|Guard/query contract giả, không SQL/persistence|
|Quyền/tạo user/lọc|125/139/13 checks đạt|Service thật/fake DB, không tạo tài khoản thật|
|Auth/S26/routes/profile/race|29/756/16/77/13 checks đạt|Counter không chứng minh hằng thời gian; race điều khiển fake, không MyISAM thực|
|HTTP fixture|102/56/109 đạt|Không Apache/FPM/proxy/DB/host|
|Smarty/Node model|Render/escaping đạt;21 model checks đạt|Dữ liệu giả|
|SQL tĩnh/ZIP validator|57network,88preset-pair;66validator checks đạt|Không SQL server; validator test ZIP synthetic|
|Export thật|Phase09/10 exit0; XLSX mở lại/STRING và PDF render thành công|PHP8.5.10 có33/37 dòng diagnostic Deprecated; không FPM8.3/HTTPdownload|
|Payload lint|**1.147PHP file đạt** qua stdin đúng bytes Git/dependency inventory|Chỉ lint, không execute/require DB/config|
|Browser chính|**36suites/1.119checks đạt**|Static/API mock/NodeRAM; không login/session/DB thật|

Browser gồm các luồng tài sản/import/phân bổ/license/ticket/lifecycle/forecast/dashboard/report, responsive/errors, 3D/fullscreen/orbit, quyền/tạo/sửa/reset/xóa quyền/lọc/thông báo/theme. Browser kết quả thật và từng count ở artifacts/ita-host-20261029/browser-summary.json; diagnostic giả ở tmp/release-20261029-*.log, không đóng gói. Đã kiểm bổ sung các bảng phân trang/motion/nhật ký/PDF trong tổng số trên.

Không chạy fixture/reset/phase02 DB tests hoặc local concurrency harness; chưa MariaDB/EXPLAIN/tải1triệu/PHP8.3/FPM. Không tăng cap hoặc bỏ assertion để đạt.

## ZIP thật

`artifacts/ita-host-20261029/infracore-update-phase14-171edde.zip`,49.322.000byte, SHA256 `d4547bcb9a979d0f8827cd4aa96b3d5f6eb4f06844b780274d5fb27297ce8b76`.1.417runtime/105.093.451byte +2metadata. Actual ZIP readback inventory/allowlist/exclusions/path/case/link/CRC/size/SHA256/manifest/checksum đạt; Get-FileHash độc lập khớp. KhôngSQL/config/marker/.htaccess/.user.ini/tmp/tests/scripts/artifacts/MOCK/preview/data thật.

Dependency: PDF lock/installed version+refs khớp; Excel566baseline tree khớp, bỏ workbook duy trì; Interfont+license hash khớp. Không nâng vendor hoặc tuyên bố upstream Excel/Inter/backup ngoài máy đã xác minh đầy đủ.

PmAuth CAS và service profile/reset mới có trong runtime; sửa/reset chỉ staff có audit nguồn gốc user.create. Không thêm migration/config để bật chức năng. MyISAM/legacy sessions/grants/PHP8.3 còn giới hạn tại HOST_UPLOAD_PHASE14_20261029.md. User backup source/config/DB/key trước upload; rollback source giữ dữ liệu mới. Agent chưa upload/push/merge/tag7–10.

Review độc lập đọc actual ZIP:1.419entry, CRC/manifest/file hashes/checksum/review hash khớp, PmAuth +ItaUserProfileService đúng bytes fixed commit; không blocker trong phạm vi gói. Không giải nén/chạy PHP/DB/host trong review.
