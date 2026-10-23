# Phase 13 — Hạ tầng mạng FE MOCK, 23/10/2026

F13.3: nút minh họa luồng chạy một lần900ms và pulse600ms, chỉ opacity/transform; không đo lưu lượng. Offline không chạy dot; Dừng/tắt motion/reduced/hidden/rời view hủy và xóa dot. Không WAAPI thì nút bị khóa, sơ đồ2D vẫn dùng được.9 browser flow checks đạt sau bổ sung fallback, không network/DB thật.

User xác nhận «Chưa duyệt BE13, hoàn thiện FE MOCK». F13.1 bảng/migration/API mạng thật chưa build. Không DB/SQL/host/network scanner hoặc dữ liệu kết nối thật. FE2D/3D tiếp tục độc lập, không push/deploy/gói/merge/tag.

## F13.2 — Sơ đồ2D

JSON MOCK8 node/7 link, tên không phải ID tài sản thật; SVG/textContent và bảng đối chiếu từ cùng tập lọc. Chỉ giữ cạnh khi cả hai đầu hiện trong bộ lọc. Chọn qua sơ đồ/bàn phím hoặc danh sách button; detail nói rõ dữ liệu giả. node/edge giới hạn200/400 và schema validation, không đo trạng thái/tốc độ thật.

`tests/ita_phase13_network_browser.js` qua playwright-cli session ita-phase06:12 checks đạt, error/retry/8node7link/XSS/keyboard/filter/reset/bounds1366/768/390 và live không fetchMOCK. Smarty render/syntax đạt, không DB. File MOCK phải loại khỏi mọi gói deploy. Truy cập hashnetwork trong live chỉ thông báo chưa có BE, không gọi API mạng.

BE_GATE_13.md là draft; user chưa duyệt. Không tạo migration/seed/PHP hoặc coi FE mẫu là nghiệm thu yêu cầu dữ liệu mạng thật.
