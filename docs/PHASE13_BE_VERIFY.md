# BE13 — Liên kết tài sản thật, kiểm chứng local 23/10/2026

User duyệt “Duyệt BUILD source BE13”. Source DAO/service/API/Smarty/JS đã xây dựng, **không tạo/kết nối DB, không chạy host/SQL, không DDL/seed hoặc gói mới**. BE_GATE_13_SQL.md là gate riêng chưa duyệt. DB/host chưa nghiệm thu; không merge/tag7–10/push/deploy.

## Hành vi source

- GET network trả phần cứng chưa xóa mềm theo store/scope/tìm mã hoặc tên/bộ phận, gồm cả tài sản retired theo contract phần cứng hiện có. Không đoán topology, không scanner/ping/SNMP/cron hoặc traffic thật. Nhãn trạng thái ở chi tiết là trạng thái **tài sản**, không phải kết nối mạng.
- Quản lý chỉ nhận cạnh khi **cả hai đầu** trong scope và tập lọc; kiểm trước COUNT/graph/page. GET network-detail ID ngoài scope trả403; không tồn tại/đầu đã xóa hoặc phần mềm trả404. Support bị403 trước query, UI không fetch topology.
- Graph tối đa200 node/400 cạnh; vượt trả422, không cắt ngầm. Bảng quản trị COUNT + LIMIT20/OFFSET, ID giảm dần; topology và tổng không phụ thuộc trang bảng. Có thiết bị chưa phân bổ cho Admin, Quản lý không nhận thiết bị ngoài scope.
- POST network-save chỉ Admin, requirePost+CSRF qua dispatcher/allowlist hiện có, validation type/ID/text, ID tăng dần và cổng đổi theo đầu tương ứng. Mỗi cặp có một liên kết đang dùng. Update/soft-delete yêu cầu version SHA256 từ nội dung lưu và updated_at(6); create không nhận version cũ.
- Transaction RR/SERIALIZABLE, không tự đổi isolation. Khóa mapping actor → thiết bị cũ/mới theo ID tăng → liên kết; kiểm revision/role/enabled, đọc native active cùng store. So lại bản link đã khóa với cả bản ban đầu và version client. Duplicate guard current-read, schema unique active pair dự kiến làm lớp cuối; deadlock/timeout/duplicate trả409 không retry. Audit network.save/network.delete cùng transaction, lỗi audit rollback qua adapter. Không ghi dc_* và không DELETE vật lý.
- Live dùng API, không tải hoặc fallback JSON MOCK; Admin có form tạo/sửa/ngừng, Manager chỉ đọc. Filter reset trang; lỗi POST giữ input, không tự retry; phản hồi GET muộn sau rời view/thu hồi vai trò không khôi phục UI. Read lỗi ẩn graph cũ, có retry thủ công. 3D dùng cùng tập dữ liệu, lazy local, dispose/fallback như MOCK; live tắt minh họa luồng, không gắn số đo mạng giả.
- Không đổi auth/login, không Composer/dependency/version Three mới. Bump cache shell/network/3D và module scene để tránh asset cũ giữ live guard/nhãn MOCK. Smarty và live.js cần triển khai đồng bộ khi được phép.

## Kết quả thật

| Lệnh / kiểm | Kết quả | Giới hạn |
|---|---|---|
| php tests/ita_phase13_unit.php | 179 checks đạt | Thuần + fake transport/query contract, có gọi method dispatcher thật bằng reflection để kiểm method/CSRF/JSON; không gọi run/config/connect, không HTTP/DB integration |
| playwright-cli -s=ita-phase06 run-code --filename=tests/ita_phase13_live_browser.js | 31 checks đạt | API mock ba vai trò, bounds1366/768/390, XSS text, CSRF/version/payload,409 giữ input/không retry, soft-delete xác nhận,3D/dispose, phản hồi muộn sau đổi role |
| ita_phase13_network_browser.js | 12 checks đạt | Hồi quy MOCK; riêng expectation live cập nhật theo gate mới: nav Admin được mở, response server sai bị từ chối và không fallback MOCK; không nới assertion zero MOCK fetch |
| ita_phase13_3d_browser.js | 23 checks đạt | WebGL thật với topology giả; lazy/import/render/context loss/fallback/dispose, không chứng minh GPU mọi máy |
| php tests/ita_phase07_unit.php; php tests/ita_phase08_unit.php; php tests/ita_phase11_category_pagination.php | 237 +72 +69 đạt | Hồi quy thuần/scripted, không DB |
| python tests/ita_phase11_guard_http.py | 109 đạt | HTTP PHP fixture thật có marker riêng, không Apache/host/DB |
| php tests/ita_phase03_render.php; php -l PHP mới/sửa; node --check JS mới/sửa; git diff --check | Đạt | Smarty thật với dữ liệu giả, syntax/whitespace |
| Impeccable detect template/live.js | [] | Không suy thành audit sạch toàn ứng dụng |
| Review Agent độc lập | Đã sửa hai finding về nhãn MOCK và cache URL; rà backend không thêm finding | Agent review chỉ đọc, không chạy browser/DB |

Đã xem ảnh mobile .playwright-cli/phase13-live-390.png; bảng cuộn nội bộ, form một cột, không document overflow. Preview từ template tại127.0.0.1:18766; xác minh process1704 node.exe chạy assets/ita/preview/serve-preview.cjs. Lần thử khởi động thêm báo EADDRINUSE vì preview đã chạy, không phải lỗi sản phẩm. Browser session cũ đóng nên mở lại. Không dùng dữ liệu/tài khoản thật, không gọi host. PHP local8.5.10, chưa8.3.33.

## SQL, vận hành và phần chưa kiểm

PHASE13_EXPLAIN.sql có **8 query chính xác** từ DAO (4 Admin/4 Manager), tham số giả đã bind thành literal để user review/thay scope. Sinh bằng php tests/ita_phase13_explain.php, không thực thi SQL; đây không phải bằng chứng EXPLAIN đạt. Gate SQL có schema mới/keys/FK/generated active_slot dự kiến, chưa migration/DDL được áp dụng. Runtime chỉ cần SELECT/INSERT/UPDATE phù hợp, không CREATE/DROP/ALTER/DELETE.

Chưa kiểm: bảng/FK/CHECK/generated column thật, unique soft-delete/create lại, transaction isolation ứng dụng, EXPLAIN/index/tải, concurrency/dedup hai actor thật, rollback khi audit lỗi trên InnoDB, thu hồi native giữa request/MyISAM race, persistence, PHP8.3/HTTPS/layout PHP/host. Native chỉ đọc không đồng nghĩa khóa transaction bảo vệ MyISAM. Không dùng kết quả fake transport thay các mục này.

Host hiện chưa xác định source/hash và chưa có bảng mới được xác nhận. **Không upload bộ source này như delta S11 cũ hoặc chỉ chép JS/PHP riêng lẻ**. Khi được phép triển khai: user backup source/DB, xác minh nền/schema/dependency và chạy checklist theo gate được duyệt; rollback source từ backup, giữ link/audit/dữ liệu mới, không DROP/restore đè. Chưa tạo gói/manifest ZIP hoặc deploy.
