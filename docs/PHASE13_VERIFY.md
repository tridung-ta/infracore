# Phase 13 — Hạ tầng mạng FE MOCK, 23/10/2026

## F13.4 — 3D lazy và fallback

Three0.180.0 pin r180 từ upstream chính thức, module/core/license/package.json và PROVENANCE.json lưu cùng source; .gitattributes giữ byte checksum. Chỉ dynamic import khi bấm3D; lúc2D không tải Three. Scene render theo thao tác/resize/select, không requestAnimationFrame loop. Bộ lọc dựng scene mới và dispose scene cũ, camera fit bounding sphere theo aspect; node có màu token. Chọn bằng danh sách/table vẫn dùng được; không giả trạng thái thật.

23 browser checks trong ita_phase13_3d_browser.js đạt: real WebGL trên topology giả, lazy2module, canvas1366/768/390, chọn/xoay/filter, quay2D/rời route/context loss cleanup, import lỗi/noWebGL/lỗi render ban đầu/lỗi render muộn đều fallback không canvas cũ hoặc uncaught JS. Đã xem ảnh3D mobile và sửa camera bị cắt khối ở mép; không suy canvas bounds đơn thuần là mọi hình đã fit.

Tái hiện renderfail thấy canvas còn sau lỗi trước return; dùng cleanup idempotent cả catch khởi tạo. Review phát hiện lỗi render sau init chưa catch; thêm test latefail tái hiện đỏ, sửa draw ready/catch/dispose/fallback và kiểm23 đạt. Observer resize/listener/GPU geometry/material/renderer được giải phóng, không ghi đè thông báo fallback bằng thành công. Reviewer rà cuối không finding, không tự chạy browser;4/4 hash/size Three khớp manifest local do reviewer tính độc lập. Không tuyên bố WebGL mọi máy/host đạt.

Nguồn dependency: https://github.com/mrdoob/three.js/tree/r180 (MIT), https://raw.githubusercontent.com/mrdoob/three.js/r180/package.json. Không dùng latest, không Composer/Node production/CDN runtime. Hash byte nguồn ở PROVENANCE.json, không giả đã có trên host. Bản3D và JSON topology chỉ dành FE MOCK, chưa đưa vào gói runtime; release tương lai phải rà điều kiện layout và loại trừ MOCK theo DEPLOY_LOG, không copy source nhánh này thẳng lên host.

## Trạng thái cuối

F13.2/F13.3/F13.4 hoàn tất FE MOCK local; F13.1 [G] user chưa duyệt BE13 nên không bảng/migration/PHP/API mới. Graph/node/links không đại diện tài sản DB thật, không discovery/scan/ping. Browser mock không nghiệm thu DB hoặc scope backend; live không tải topology mẫu. Giữ source mới chưa upload; rollback tương lai source giữ dữ liệu.

F13.3: nút minh họa luồng chạy một lần900ms và pulse600ms, chỉ opacity/transform; không đo lưu lượng. Offline không chạy dot; Dừng/tắt motion/reduced/hidden/rời view hủy và xóa dot. Không WAAPI thì nút bị khóa, sơ đồ2D vẫn dùng được.9 browser flow checks đạt sau bổ sung fallback, không network/DB thật.

User xác nhận «Chưa duyệt BE13, hoàn thiện FE MOCK». F13.1 bảng/migration/API mạng thật chưa build. Không DB/SQL/host/network scanner hoặc dữ liệu kết nối thật. FE2D/3D tiếp tục độc lập, không push/deploy/gói/merge/tag.

## F13.2 — Sơ đồ2D

JSON MOCK8 node/7 link, tên không phải ID tài sản thật; SVG/textContent và bảng đối chiếu từ cùng tập lọc. Chỉ giữ cạnh khi cả hai đầu hiện trong bộ lọc. Chọn qua sơ đồ/bàn phím hoặc danh sách button; detail nói rõ dữ liệu giả. node/edge giới hạn200/400 và schema validation, không đo trạng thái/tốc độ thật.

`tests/ita_phase13_network_browser.js` qua playwright-cli session ita-phase06:12 checks đạt, error/retry/8node7link/XSS/keyboard/filter/reset/bounds1366/768/390 và live không fetchMOCK. Smarty render/syntax đạt, không DB. File MOCK phải loại khỏi mọi gói deploy. Truy cập hashnetwork trong live chỉ thông báo chưa có BE, không gọi API mạng.

BE_GATE_13.md là draft; user chưa duyệt. Không tạo migration/seed/PHP hoặc coi FE mẫu là nghiệm thu yêu cầu dữ liệu mạng thật.
