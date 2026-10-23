# Phase 12 — UI Motion local, 23/10/2026

## Kết quả cuối Phase12 local

F12.1–F12.9 đã triển khai FE.21 infrastructure +22 screen DOM probes +9 login MOCK checks đạt; hồi quy ticket64/vòng đời63/ngân sách42/dashboard64 đạt, Smarty/syntax/diff đạt. Rà độc lập không finding; reviewer không tự chạy browser. Probe screens kích thay đổi DOM trực tiếp, không chứng minh mọi animation trong mọi luồng thật; các hồi quy riêng kiểm luồng/payload/quyền bằng API mock.

Ticket observer đã chuyển sang message/details nơi nội dung thực thay đổi trực tiếp, không quan sát subtree toàn form. Không animate canvas/chart liên tục. Nội dung luôn hiển thị nếu JS/motion không chạy. Đã giữ tính riêng của mỗi màn qua các file motion; login không đổi auth. Core, các module và template phải đi cùng checkpoint đầy đủ.

Mọi kết quả là local/mock; PHP8.3/HTTPS/host/layout dữ liệu thật chưa kiểm, DB Phase1–10 vẫn chưa nghiệm thu. Không merge/tag7–10/push/deploy, không tạo gói. Không đổi thư viện/server/package Composer. Phase13 FE MOCK tiếp tục riêng.

F12.9: login Smarty giữ nguyên auth/form/token; motion vào card, toggle và reduced-motion.9 browser MOCK checks đạt, không submit xác thực. Core pageshow chỉ refresh khi BFCache persisted để không hủy hiệu ứng vào trang ngay ở load đầu. Review độc lập không finding, chưa chạy browser ở agent review.

F12.8: ngân sách, 22 DOM motion probes cộng dồn đạt; không thay dữ liệu hoặc request.

F12.7: vòng đời, 19 DOM motion probes cộng dồn đạt; không thay dữ liệu hoặc request.

F12.6: chi tiết tài sản, 16 DOM motion probes cộng dồn đạt; không thay dữ liệu hoặc request.

F12.2: cảnh báo,4 probes ban đầu và13 probes cộng dồn đạt. File đăng ký cảnh báo được lưu bổ sung sau các commit F12.3–F12.5; phải lấy checkpoint đầy đủ, không dùng các commit giữa chặng làm gói độc lập. Không viết lại lịch sử.

F12.5: assets, 13 probes cộng dồn trong ita_phase12_screens_browser.js đạt; giữ dữ liệu/request và không animate canvas liên tục.

F12.4: dashboard, 10 probes cộng dồn trong ita_phase12_screens_browser.js đạt; giữ dữ liệu/request và không animate canvas liên tục.

F12.3: ticket, 7 probes cộng dồn trong ita_phase12_screens_browser.js đạt; giữ dữ liệu/request và không animate canvas liên tục.

User yêu cầu BUILD liên tục qua các phase. Motion là FE theo PLAN_FINAL; không PHP/DB/API/config host, không merge/tag7–10/push/deploy hoặc tạo gói. Giữ Smarty và hệ thống token hiện tại.

## F12.1 — Hạ tầng

ItaMotion dùng Web Animations API, chỉ opacity/transform,180ms, không lặp; animation không giữ inline style sau kết thúc. Cờ UI_MOTION_ENABLED=false thắng tùy chọn người dùng; prefers-reduced-motion hủy ngay animation, nút không bật vượt policy OS. Tùy chọn lưu localStorage ita.motion (không thông tin tài khoản); lỗi storage được bỏ qua an toàn. Nút ở sidebar, không overlay thao tác. Hidden/pagehide/view change hủy cả animation và RAF chờ; pageshow nối lại observer. Observer giới hạn childList trực tiếp/hidden/open, không theo dõi toàn subtree hoặc attributes style.

`playwright-cli -s=ita-phase06 run-code --filename=tests/ita_phase12_motion_browser.js`:21 checks đạt; normal/reduced/flagfalse, hủy, persist, keyboard-native button và bounds1366/768/390. Lần đầu assertion chạy trước event media change; sửa test chờ nút cập nhật, không nới policy. Smarty thật/syntax/diff đạt. Browser/static local không DB/host/PHP8.3.

Motion không thay dữ liệu, quyền, request hoặc chặn nội dung chờ animation. Cấu hình hiệu năng: một chuyển động ngắn cho nội dung vừa thay, không animate canvas/biểu đồ liên tục. Chưa đo thiết bị yếu/host thật.
