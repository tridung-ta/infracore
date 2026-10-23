# Phase 12 — UI Motion local, 23/10/2026

F12.5: assets, 13 probes cộng dồn trong ita_phase12_screens_browser.js đạt; giữ dữ liệu/request và không animate canvas liên tục.

F12.4: dashboard, 10 probes cộng dồn trong ita_phase12_screens_browser.js đạt; giữ dữ liệu/request và không animate canvas liên tục.

F12.3: ticket, 7 probes cộng dồn trong ita_phase12_screens_browser.js đạt; giữ dữ liệu/request và không animate canvas liên tục.

User yêu cầu BUILD liên tục qua các phase. Motion là FE theo PLAN_FINAL; không PHP/DB/API/config host, không merge/tag7–10/push/deploy hoặc tạo gói. Giữ Smarty và hệ thống token hiện tại.

## F12.1 — Hạ tầng

ItaMotion dùng Web Animations API, chỉ opacity/transform,180ms, không lặp; animation không giữ inline style sau kết thúc. Cờ UI_MOTION_ENABLED=false thắng tùy chọn người dùng; prefers-reduced-motion hủy ngay animation, nút không bật vượt policy OS. Tùy chọn lưu localStorage ita.motion (không thông tin tài khoản); lỗi storage được bỏ qua an toàn. Nút ở sidebar, không overlay thao tác. Hidden/pagehide/view change hủy cả animation và RAF chờ; pageshow nối lại observer. Observer giới hạn childList trực tiếp/hidden/open, không theo dõi toàn subtree hoặc attributes style.

`playwright-cli -s=ita-phase06 run-code --filename=tests/ita_phase12_motion_browser.js`:21 checks đạt; normal/reduced/flagfalse, hủy, persist, keyboard-native button và bounds1366/768/390. Lần đầu assertion chạy trước event media change; sửa test chờ nút cập nhật, không nới policy. Smarty thật/syntax/diff đạt. Browser/static local không DB/host/PHP8.3.

Motion không thay dữ liệu, quyền, request hoặc chặn nội dung chờ animation. Cấu hình hiệu năng: một chuyển động ngắn cho nội dung vừa thay, không animate canvas/biểu đồ liên tục. Chưa đo thiết bị yếu/host thật.
