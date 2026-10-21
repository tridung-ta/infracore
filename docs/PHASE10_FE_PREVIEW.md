# Dashboard Phase10 — FE/mock độc lập, 21/10/2026

**BE-GATE #10 chưa duyệt, chưa build BE.** DB Phase1–9 vẫn chưa nghiệm thu; không merge/tag7/8/9, không push/deploy. Đây chỉ là bản xem thử UI theo quyền FE/mock user đã cho phép.

## Mở bản xem thử

Tại root repo, chạy `node assets/ita/preview/serve-preview.cjs` nếu chưa có server18766. Không chiếm/chấm dứt tiến trình khác khi cổng đang dùng. Mở http://127.0.0.1:18766/assets/ita/mock/dashboard.html. Nếu server hiện có đang phục vụ đúng workspace thì không cần chạy lại. Dừng server tự chạy bằng Ctrl+C.

HTML riêng, không include vào layout live và không thay dashboard đang dùng. Mọi request chỉ GET static local. Fixture dashboard.json có9 thiết bị giả và5 dòng cảnh báo (4 hiệu lực,1 cũ). Chọn vai trò chỉ mô phỏng UI, không phải xác thực hoặc bằng chứng kiểm quyền server.

## Hành vi UI

- KPI từ cùng fixture: sổ tài sản9, đang quản lý8 (không thanh lý), có sửa chữa4/8=50%, đề xuất thay thế3. Hai phiếu sửa chữa trên cùng một thiết bị không tăng tử số distinct asset.
- Ba biểu đồ ECharts: tình trạng, tỷ lệ lỗi, ưu tiên thay thế; đổi nhóm loại/bộ phận/năm mua. Mỗi biểu đồ kèm bảng đối chiếu cùng dữ liệu. Năm mua thiếu hiển thị Chưa ghi nhận, không year0. Điểm gốc0.7996 không thành replace vì hiển thị làm tròn; điểm1.250 được hiển thị; thiếu điểm ghi rõ chưa xếp hạng.
- Quản lý mô phỏng scope CNTT:5 thiết bị,4 đang quản lý,3/4=75% lỗi,2 thay thế. Khi đổi vai trò, dropdown hiển thị đúng phạm vi đang dùng, không giữ nhãn bộ phận khác với số liệu.
- Hỗ trợ chỉ bảng bảo trì trong ứng dụng; không dashboard/badge/biểu đồ/export trong bản mock. Không có chức năng tải file cho bất kỳ vai trò nào ở bước này; không mô phỏng thành công một export chưa build.
- Chuông =4 cảnh báo đang hiệu lực trong phạm vi minh họa, không phải chưa đọc; một thiết bị có hai lý do giữ hai dòng. Không lưu trạng thái đã đọc.
- Một asOf được chụp sau lần tải fixture và dùng toàn màn hình; “Số liệu tại HH:mm”, ngày và giờ VN. Đây là thời gian browser của mock, chưa phải mốc truy vấn server. Chỉ bấm Làm mới mới tải lại và đổi mốc. Lỗi tải giữ mốc/số liệu cũ và báo lỗi, không tự retry.
- Không polling hoặc tự refresh biểu đồ nặng. Controller không setInterval/setTimeout. ECharts dispose và ResizeObserver disconnect khi đổi lọc, đổi vai trò/view hoặc pagehide; không tích lũy biểu đồ/listener theo mỗi render. Biểu đồ tắt animation, tương thích reduced-motion.
- Nếu không tải được ECharts vẫn có bảng đầy đủ; tên fixture dùng textContent, không render HTML. Bảng cuộn cục bộ, có vùng focus bàn phím.

## Kiểm chứng thật

`playwright-cli -s=ita-phase06 run-code --filename=tests/ita_phase10_preview_browser.js`: **46 kiểm tra browser static/mock đạt**, gồm tính tay, scope UI, mốc chung/giờ VN, thiếu dữ liệu, raw threshold, dispose, bàn phím Enter vào cảnh báo, lỗi/retry thủ công, fallback ECharts, escaping, bounds1366/768/390, không request PHP/POST/external hoặc JS runtime error. Clock test cố định21/10/2026; lần refresh lỗi giữ10:00, lần thành công đổi10:01. Kiểm không tự fetch quan sát trong luồng test và đối chiếu source không lập timer, không gọi đó là soak test dài hạn.

Đã xem ảnh desktop/mobile, sửa legend ECharts mặc định chạm vùng trục bằng top0; bảng/chart cùng thứ tự nhóm. Ảnh .playwright-cli/phase10-preview-{1366,768,390}.png. `node --check` controller/test đạt; manifest ECharts JS/LICENSE/NOTICE đọc lại SHA256 khớp. Rà độc lập phát hiện nhãn bộ phận lệch khi đổi vai trò; đã sửa và có regression Admin Vận hành → Quản lý CNTT.

Impeccable detector ban đầu nêu19 mẫu (chủ yếu màu/font của theme đã được user chốt và kicker). Giữ token/font hiện hữu theo AGENTS, bỏ kicker không cần thiết, không tuyên bố detector sạch. Không đổi locale hoặc ghi Unicode qua shell pipe.

## Giới hạn và việc còn chờ

Chưa có KPI/query/permission server, dữ liệu thật, export500/1.000/5.000 dòng, report preview thực, short-life report hoặc snapshot ngân sách #10. Điểm/cảnh báo là dữ liệu giả đầu vào, không phải chạy lại nghiệp vụ lifecycle trên DB. Không có DB integration, EXPLAIN hoặc UAT. Các ngưỡng/định nghĩa còn đề xuất trong gate10 chưa được duyệt chỉ vì UI này tồn tại.

Runtime ECharts6.1.0 nằm cục bộ; SOURCE.json ghi URL tag/hash/kích thước và LICENSE/NOTICE. Đây là dependency UI đã được AGENTS cho phép, không Composer mới; không nói rằng bộ Excel/PDF/font legacy đã được dựng lại hoặc tracked nhờ bước này.
