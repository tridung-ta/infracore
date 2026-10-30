# Phase13 — Toàn màn hình và nhãn từng khối3D,26/10/2026

User yêu cầu thêm nút phóng toàn màn hình phần3D và tên nhỏ trên mỗi khối (ví dụ máy1/2/3). Chỉ FE: controller/sceneJS, networkCSS, layout/partialSmarty vàtest; không PHP/API/SQL/DB/host. Giữ pointerhai trục và mọi nút cũ.

## Hành vi

- Nút “Toàn màn hình3D” chỉ bật khi scene3D khả dụng và browser cho phép FullscreenAPI. Fullscreen đúng khung3D, không toàn bộdashboard; canvas/camera/nhãn đổi kích thước bằng ResizeObserver, không looprender.
- Trong fullscreen có thanh Chọn thiết bị/Xoaytrái/Xoayphải/Thoát; dùng được bàn phím và prefers-reduced-motion (hover tắt). Thanh này ẩn trong khung bình thường. Focus đi vào nút thoát và trả về nút mở sau thoát. Esc thoát, aria-pressed đồng bộ khi browser/app thoát; rời trang hoặc trở2D tự yêu cầu đóng fullscreen. RequestFullscreen từ chối thì giữ3D và báo có kiểm soát.
- Mỗi mesh có một nhãn DOM textContent từ name (fallbackid), đặt trên vị trí chiếu từ thế giới3D lêncanvas, cập nhật mỗi draw. Fontnhỏ11px, tên dài ellipsis; tên đầy đủ chọn qua danh sách/dropdown. Nhãn được giữ trong chiều ngang canvas và đẩy lên thêm khi gặp nhãn đã đặt, có giới hạn mép trên. Không đổi tên/ID trong dữ liệu thành Máy1/2/3 tự động. Overlay pointer-events:none, không chặn chọnkhối/chuột; không dùng innerHTML hoặccanvastexttexture. Nhãn ngoài clippingdepth ẩn; khi dispose/đổifilter không giữlayercũ.
- Màu/bề mặt dùng token hiện có; không thêm package/CDN/controlsbênngoài. CacheversionCSS/controller/scene đã tăng.

## Kiểm thật local — không DB

Preview build từSmarty, staticserver127.0.0.1:18766 được xácminh node.exe assets/ita/preview/serve-preview.cjs PID19360. Lần thử khởiđộng thêm báoEADDRINUSE; không dừng processkhác, xácminh servercũ rồi dùng lại. PlaywrightCLI sessionita-fullscreen. WebGL/FullscreenAPI thật, dữ liệuMOCK; browser API mock ở testlive không thay serverquyền/DB.

| Lệnh/kiểm | Kết quả | Giới hạn |
|---|---|---|
|playwright-cli -s=ita-fullscreen run-code --filename=tests/ita_phase13_fullscreen_browser.js|**23 checks đạt**, escapeExited=true|Fullscreen thực/resize/Esc/focus/toolbar/keyboardreducedmotion/select/labelsXSS/layerdisposal/failure/bounds768/390 và8nhãn không chồng|
|ita_phase13_pointer_browser.js quaCLI như trên|24checks đạt|Xoaychuột/bútchọnmesh/raycast/reducedmotion/touch/idle/dispose, topologygiả|
|ita_phase13_3d_browser.js quaCLI như trên|23checks đạt|Fallbackimport/render/contextlost/filter/dispose vàbounds1366/768/390|
|ita_phase13_live_browser.js quaCLI như trên|31checks đạt|API mock, CSRF/version/roles/late response; khôngDB/HTTPauththật|
|php tests/ita_phase03_render.php|Đạt|Smarty/escaping, PHP8.5.10, khôngDB|
|node --check scene/controller/test; git diff --check|Đạt|Syntax/whitespace|

Đã nhìn ảnh phase13-fullscreen-1366.png và ảnhmobile phase13-labels-390.png được tạo trong .playwright-cli (khôngcommit/deploy). Tên giả chứa `<img…>` chỉ là text, không thực thiHTML. Ảnhmobile ban đầu có8nhãn gần/chồng, đã thêm bố trí đẩy nhãn lên có giới hạn; test cuối đo boundingboxes không chồng ở768/390 trên bộ mẫu8node. Không bảo đảm mọi topology dày200node không chồng khi hết không gian trên; chọn bằng dropdown/danh sách vẫn đầyđủ.

Review độc lập tìm gap: nútxoay ngoài fullscreen không truycậpđược khi giảmchuyểnđộng. Đã thêm toolbarbêntrong và test keyboardrotationthực; không bỏ yêu cầu giữnút. Lượt đầu test đọc visibility ngay sau fullscreenElement đổi có race với fullscreenchange; đã chờ nút thoát visible. Browser automation banđầu khôngthoát bằngEsc mặcđịnh; controller thêm handlerEsc chỉ trên khungfullscreen củaapp, test cuối xácnhận thoát trước mọi fallbackAPI; không gọi APIbù đểgắnPASS choEsc. Không cófinding mãứngdụng nào được ghi là đãsửa từlỗitestchờUI.

## Bàn giao

Chưa kiểmFullscreen trênhost/thiếtbịmobilethật/trìnhduyệtkhônghỗtrợFullScreenAPI. Không thay cấu hìnhPermissionsPolicy/CSP/HTTPS để ép fullscreen. LocalPHP8.5 khôngchứngminh8.3/FPM; đây là tươngtácFE. Gói cũ source81f3c55 **không chứa pointer/fullscreen/labels mới**; không sửaZIP hoặchashcũ. Cậpnhật tươnglai cần5fileFE đồngbộ: ita-network.css, ita-network-3d.js, ita-network-scene.js, layout.tpl.html, partialnetwork.tpl.html; backup trướcupload, rollbacksource giữDB/config/key/dữliệu. Chưa tạoZIP mới/upload/push/merge/tag7–10.
