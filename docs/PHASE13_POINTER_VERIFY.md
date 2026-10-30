# Phase13 — Xoay 3D theo con trỏ,26/10/2026

Cập nhật: user yêu cầu xoay360° thêm, đã bổ sung **kéo chuột** khônggiới hạn ngang/dọc tại PHASE13_ORBIT_VERIFY.md. Hovernhẹ cũ giữgiới hạn như ghi dưới; mô tảbanđầu khôngcócamera/xoaytựdo khôngthay trạngtháimới. Clickraycast hiệnphânbiệt bằngpointerdown/up khôngkéo; touch/pen giữtap riêng. Bằngchứng24checks dưới là lượtbanđầu, bộpointer hiện32checks sau bổsungtap.

User yêu cầu rê chuột trái/phải/ngang/dọc/lên/xuống, giữ nút chức năng. Chỉ FE: ita-network-scene.js, ita-network-3d.js và version script trong layout Smarty. Không PHP/API/SQL/auth, không host/DB, không sửa dữ liệu hoặc mô hình mạng thật.

Trong khung3D, chuột điều khiển yaw±0.65radian và pitch±0.4radian theo vị trí trong canvas. Gộp pointermove vào tối đa một RAF đang chờ, render theo thao tác, không loop tự quay/polling. Pointerleave reset offset chuột, giữ góc nền do nút trái/phải thiết lập. Giữ nút2D/3D/trái/phải, raycast chọn khối và danh sách chọn thiết bị. Touch không bắt chuyển động để giữ cuộn trang; prefers-reduced-motion tắt hover, nút xoay vẫn dùng, đổi preference reset góc hover. Dispose hủy RAF/listener/observer/material/geometry/context; đổifilter/2D/rờitrang không giữ scene cũ. URL controller/module tăng version để tránh cache cũ.

## Kết quả thật local

Static server `node assets/ita/preview/serve-preview.cjs` ở127.0.0.1:18766, chỉ MOCK; preview dựng bằng build-preview.cjs. Playwright CLI session ita-pointer, không truy cập host hoặc endpointPHP thật.

| Lệnh | Kết quả | Phạm vi |
|---|---|---|
|playwright-cli -s=ita-pointer run-code --filename=tests/ita_phase13_pointer_browser.js|**24 checks đạt**|WebGL thật trên topology8node/7linkgiả; hướnghai trục/giới hạngóc/buttonbase/leave/idle/touch/reduced-motion/raycast/list/dispose|
|playwright-cli -s=ita-pointer run-code --filename=tests/ita_phase13_3d_browser.js|**23 checks đạt**|WebGL fallback/import/render/contextlost/filter/rebuild/dispose; bounds1366/768/390|
|playwright-cli -s=ita-pointer run-code --filename=tests/ita_phase13_live_browser.js|**31 checks đạt**|API mock, vai trò/CSRF/version/no retry/late response; không server auth/DB|
|php tests/ita_phase03_render.php|Đạt|Smarty thật/escaping, khôngDB, PHP8.5.10|
|node --check scene/controller/test; git diff --check|Đạt|Syntax/whitespace|
|Review độc lập|Không finding cần sửa|Chỉ đọc diff, không tự chạy browser/DB|

Đã xem ảnh .playwright-cli/phase13-pointer-1366.png, khối và liên kết WebGL hiển thị sau rê chuột, nút còn nguyên. Ảnh là MOCK, không deploy/commit. Test instrument Scene.add/Raycaster và WebGL draw trong context test để đọc góc/drawcount, không thêm debug global vào code ứng dụng.

Ban đầu instrumentation test nhắm WebGLRenderer.prototype.render nhưng renderer dùng method riêng ởinstance; test timeout/poseundefined. Đã sửa test bắt group thực qua Scene.add, giữ source render nguyên vẹn; chờ mediachange/reset trước đo preference. Không coi các lỗi instrumentation ban đầu là lỗi sản phẩm hoặc xóa assertion. CLI ban đầu bị giới hạn ghi sessionAppData, đã chạy với quyền tool được chấp nhận; không chuyển sang browserhost.

## Giới hạn và bàn giao

Chưa kiểm PHP-layout thật/host/WebGLdriver khác/tài khoản thật. Không phải điều khiển thiết bị/mạng hoặc đo trạng thái mạng. Chỉ xoay/ngiêng toàn mô hình, không tự thay tọa độ thiết bị hoặc dịch chuyển camera tự do. Không thêm OrbitControls/package. Bản runtime ZIP source81f3c55 bàn giao trước **chưa chứa thay đổi pointer**; không sửa ZIP/hash cũ. Khi cập nhật host cần đồng bộ đúng3file FE nêu trên, backup tệp thay trước upload; rollbacksource giữ DB/config/key/dữ liệu. Chưa tạo ZIP mới/upload/push/merge/tag7–10.
