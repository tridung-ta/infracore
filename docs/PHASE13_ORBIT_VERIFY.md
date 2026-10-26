# Phase13 — Kéo chuột xoay360° ngang/dọc,26/10/2026

User thấy hoverdọc bịgiới hạn và yêu cầu xem360° toànmôhình. ChỉFE: scene/controllerJS, versioncache tronglayout vàtest. KhôngPHP/API/SQL/auth/config/DB/host, không thêmOrbitControls/package.

## Hành vi và cách dùng

Giữ **chuột trái trên khung3D rồi kéo** ngang hoặc dọc: yaw/pitch cộng dồn khôngclamp góc, độnhạy0.012radian/pixel (~524pixel/vòng360°). Kéo nhiều lượt để xem toànmôhình, cả phía dưới. Pointercapture giữ thao tác khi ra ngoài mép canvas; thảchuột giữgóc, khôngquán tính/tựquay. Nút xoay, hovernhẹ cũ, nhãn vàtoànmànhình vẫn còn. Hovernhẹ vẫn bounded±0.65/0.4; phần khônggiới hạn là **kéo chuột**, không phải xoayliêntục chỉ bằnghover.

Ngưỡng4pixel phânbiệtclick vàdrag, xét cảmove vàtọađộpointerup; chỉclick khôngkéo mới raycast chọnkhối. Touch/pen giữtap chọn, vuốt/cancel/leave khôngchọn vàkhôngcapture/ngănscroll; xoaytựdo là chuột. Prefers-reduced-motion tắt hover tựphảnứng, nhưng **kéo chủđộng** vànút còn dùngđược. Dispose hủyRAF/drag/tap/listeners; request render khi cóthao tác, khôngloop.

Nhãn tính méptrên từ8góc khốichiếu lênscreen, nên vẫn ởtrên hìnhkhi môhình lật, khônglấylocalY làm“trên” cốđịnh. GiữtextContent/collisionbounded/font/token cũ, khôngghidữliệu thiếtbị.

## Kiểm thật local

Staticpreview127.0.0.1:18766 (node serve-preview củaworkspace), PlaywrightCLI sessionita-orbit, topologyMOCK8node/7link, Three/WebGL vàFullscreenAPI thật. Không giả ảnhbằngcanvasmock, khôngendpointPHP/DB/host.

| Test/lệnh | Kết quả thật | Phạm vi |
|---|---|---|
|playwright-cli -s=ita-orbit run-code --filename=tests/ita_phase13_orbit_browser.js|**28checks đạt**|Mouse thực/pointercapture, ngang/dọc>2π, kéo ngược, ravùngcanvas, giữgóc, khôngselectsaukéo, cancel, disposekhicapture, fullscreen, normal/reducedmotion|
|ita_phase13_pointer_browser.js quaCLI nhưtrên|**32checks đạt**|Hover/nút cũ, raycastdown+up, touch/pen tap/swipe/cancel, idle/RAF/dispose, reducedmotion|
|ita_phase13_fullscreen_browser.js quaCLI nhưtrên|**23checks đạt**|Nhãn/escaping/fullscreen/keyboard/select/Esc/focus/collisionmẫu768/390; escapeExited=true|
|ita_phase13_3d_browser.js quaCLI nhưtrên|23checks đạt tronglượt hồiquy feature|Fallbackimport/render/contextlost/bounds1366/768/390 vàdispose|
|php tests/ita_phase03_render.php|Đạt|Smarty thật/escaping, PHP8.5.10, khôngDB|
|node --check scene/controller/tests; git diff --check|Đạt|Syntax/whitespace|
|Review độc lập|Đã sửa regressiontouch/pen; ràlại khôngfinding mới|Chỉđọcsource, khôngtựchạybrowser/DB/host|

Đã xemảnh .playwright-cli/phase13-orbit-underside.png chụpgiữa lượtkéodọc khi pitchđãquaπ; ảnhMOCK, khôngcommit/deploy. Review phát hiện down mới chỉcho mouse khiến touch/pen khôngpick; đãtách tapstate và bổsung32checks nêu trên, không nới assertionchọnthiếtbị. Testpointer cũ đổipointerdown đơn sang down+up theo contractclick mới, vẫn raycastkhốithật vàkiểmID.

## Giới hạn/bàn giao

Chưa kiểm trênhost/trìnhduyệtthiếtbịthật/touchhardware/driverkhác. Đây là xoaymôhình quanh2trục, khôngpan/zoom/tựdò mạng. Topologydày vẫn có thể gầnnhãn khi hếtvùngtrên, dùngselector trongfullscreen/danh sách. Không kếtluậnPHP8.3/DBtừWebGLtest. SourceZIP81f3c55 đãbàn giao **khôngcó pointer/fullscreen/orbit mới**; khôngghiđèZIP/hashcũ, chưa tạo gói mới. Khi cậpnhật sau cầncácfileFE đồngbộ/beforebackup/rollbacksourcegiữDB/config/keys. Khôngupload/push/merge/tag7–10.
