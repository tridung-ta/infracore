# Tiến độ dự án 5

Cập nhật 16/10/2026. Chưa nghiệm thu yêu cầu sản phẩm nào trong 10 yêu cầu. Không tính audit thành phần trăm hoàn thành chức năng.

## Mini plan Phase 0

1. Kiểm tra Git, dữ liệu nhạy cảm và dry-run dọn dẹp; giữ nguyên source.
2. Đọc entrypoint, DAO, auth, thư viện và cấu trúc route.
3. Ghi audit, kiểm kê cũ và trích kế hoạch từ Word.
4. Lint PHP toàn repo; smoke HTTP ở chế độ cô lập.
5. Chỉ tạo baseline khi danh tính tác giả và danh sách source an toàn đã được xác minh.

| Mã | Chức năng | Vùng | Trạng thái | Commit | Verify | Ghi chú |
| --- | --- | --- | --- | --- | --- | --- |
| F0.1 | Backup và dọn dẹp an toàn | Tài liệu/Git | [~] | chưa có | xem AUDIT.md | Chưa nghiệm thu Phase 0 |
| F0.2 | Audit nền Derasoft | Tài liệu/Git | [~] | chưa có | xem AUDIT.md | Chưa nghiệm thu Phase 0 |
| F0.3 | Kiểm kê giao diện cũ | Tài liệu/Git | [~] | chưa có | xem AUDIT.md | Chưa nghiệm thu Phase 0 |
| F0.4 | Kế hoạch và bảng tiến độ | Tài liệu/Git | [~] | chưa có | xem AUDIT.md | Chưa nghiệm thu Phase 0 |
| F0.5 | Lint và smoke baseline | Tài liệu/Git | [!] | chưa có | php -l: 1451/1456 đạt; HTTP smoke 5/5 trang cô lập | 5 lỗi thư viện mail cũ; xem BASELINE.md |
| F1.1 | tạo schema bộ phận, loại tài sản và cài đặt | SQL | [~] | commit F1.1 trên feature/ita-phase01-ui | local MySQL: tạo bảng/constraint đạt | SQL giao user; MariaDB host chờ user chạy |
| F1.2 | tạo schema tài sản và chỉ mục theo phạm vi | SQL | [~] | commit F1.2 trên feature/ita-phase01-ui | local MySQL: tạo bảng/constraint đạt | SQL giao user; MariaDB host chờ user chạy |
| F1.3 | tạo schema giấy phép và bàn giao thiết bị | SQL | [~] | commit F1.3 trên feature/ita-phase01-ui | local MySQL: tạo bảng/constraint đạt | SQL giao user; MariaDB host chờ user chạy |
| F1.4 | feat(db): tickets, ticket_events, audit_logs, budget_plans, import_batches [BE]. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F1.5 | feat(db): seed dữ liệu mẫu dev/test [BE]. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F1.6 | Design tokens + Bootstrap dark theme | FE | [x] | 051f80b | Playwright 1366/768/390 đạt, Bootstrap hash trùng nguồn | Chỉ FE, chưa tích hợp PHP |
| F1.7 | Layout sidebar, topbar, breadcrumb, toast | FE | [~] | chưa có | browser MOCK đạt; UI_PHASE01_VERIFY.md | Chưa commit và chưa tích hợp PHP |
| F1.8 | Component dùng chung | FE | [~] | chưa có | bảng/KPI/badge/modal chi tiết MOCK đạt | Modal xác nhận nghiệp vụ chờ nối hành động |
| F1.9 | Sidebar mới chỉ có điều hướng ITA | FE | [x] | commit F1.9 trên feature/ita-phase01-ui | browser MOCK 1366/768/390 đạt | Tách file riêng, giữ nguyên menu cũ; guard BE chưa làm |
| F1.10 | feat(legacy): guard route cũ theo cờ ITA_LEGACY_UI_ENABLED [BE]. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F2.1 | feat(auth): đăng nhập trên giao diện mới [BE+FE]. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F2.2 | feat(rbac): 3 vai trò + ma trận quyền 4.1 [BE]. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F2.3 | feat(rbac): middleware phạm vi bộ phận phụ trách [BE]. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F2.4 | feat(rbac): quản lý user và bộ phận phụ trách (Admin IT) [BE+FE]. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F2.5 | feat(audit): ghi log tạo/sửa/xóa tài sản và đổi trạng thái [BE]. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F2.6 | feat(audit): màn hình xem log có bộ lọc [BE+FE]. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F2.7 | feat(sec): rate limit đăng nhập, CSRF [BE]. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F3.1 | feat(asset): danh sách phân trang [BE+FE]. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F3.2 | tạo tài sản. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F3.3 | sửa tài sản. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F3.4 | đổi trạng thái có lý do + log. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F3.5 | xóa mềm. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F3.6 | chi tiết thiết bị (tab thông tin, lịch sử). | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F3.7 | feat(search): tìm kiếm nâng cao theo phòng ban, loại, năm mua, bảo hành. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F3.8 | feat(category): quản lý danh mục loại thiết bị. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F4.1 | feat(import): tải file mẫu Excel. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F4.2 | upload và validate (trùng mã, danh mục, ngày). | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F4.3 | xem trước và ghi theo lô trong transaction. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F4.4 | xuất file lỗi theo dòng. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F4.5 | feat(export): xuất danh sách tài sản ra Excel theo bộ lọc. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F5.1 | giao thiết bị cho nhân viên/phòng ban. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F5.2 | thu hồi. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F5.3 | lịch sử phân bổ. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F5.4 | biên bản bàn giao PDF. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F5.5 | xác nhận nhận bàn giao. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F5.6 | cảnh báo quá hạn bảo trì (badge ở danh sách + chuông). | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F6.1 | CRUD giấy phép (key mã hóa). | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F6.2 | cấp phát/thu hồi chỗ theo máy, người, phòng ban. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F6.3 | cảnh báo sắp hết hạn. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F6.4 | cảnh báo vượt mức. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F6.5 | xem key đầy đủ (chỉ Admin IT, có log). | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F7.1 | tạo phiếu bảo trì/ticket. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F7.2 | xử lý ticket (gán kỹ thuật viên, pending → resolved/failed, ghi ita_ticket_events). | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F7.3 | đồng bộ trạng thái thiết bị. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F7.4 | lịch sử bảo trì trên chi tiết thiết bị. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F7.5 | thống kê số lần bảo trì mỗi năm theo thiết bị. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F7.6 | tính next_maintenance_due theo chu kỳ. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F8.1 | tuổi đời và tỷ lệ tuổi/tuổi thọ. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F8.2 | điểm thay thế (4.4). | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F8.3 | tuổi trung bình, tỷ lệ lỗi theo loại và phòng ban. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F8.4 | danh sách đề xuất thay thế. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F9.1 | cấu hình định mức và tham số. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F9.2 | dự báo ngân sách bảo trì/thay thế. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F9.3 | thiết bị cần mua thêm theo kế hoạch mở rộng. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F9.4 | đổi bộ lọc (năm, phòng ban, loại). | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F9.5 | xuất ngân sách Excel/PDF. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F10.1 | dashboard KPI. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F10.2 | biểu đồ tình trạng theo loại, phòng ban, năm mua. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F10.3 | biểu đồ tỷ lệ lỗi. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F10.4 | biểu đồ thiết bị sắp thay thế. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F10.5 | báo cáo Excel (tồn kho, sắp bảo trì, vòng đời ngắn, ngân sách). | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F10.6 | báo cáo PDF. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F10.7 | trung tâm cảnh báo (chuông + trang). | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F11.1 | rà soát index theo EXPLAIN (docs/PERF_NOTES.md). | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F11.2 | rà soát bảo mật (XSS, CSRF, SQLi, upload, quyền). | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F11.3 | responsive lần cuối. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F11.4 | trang lỗi chuẩn và xử lý lỗi. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F11.5 | gói deploy và checklist FileZilla trong docs/DEPLOY_LOG.md. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F11.6 | hướng dẫn sử dụng. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F12.1 | hạ tầng motion + cờ UI_MOTION_ENABLED + reduced-motion. Sau đó mỗi màn một commit theo thứ tự: | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F12.2 | cảnh báo, | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F12.3 | ticket, | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F12.4 | dashboard, | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F12.5 | danh sách tài sản, | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F12.6 | chi tiết thiết bị, | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F12.7 | vòng đời, | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F12.8 | dự báo, | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F12.9 | đăng nhập. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F13.1 | feat(db): ita_network_links + dữ liệu mẫu [BE]. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F13.2 | trang 2D. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F13.3 | node pulse và đường dữ liệu chạy. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F13.4 | bản 3D Three.js nạp lazy + dự phòng 2D. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |

## Điểm chặn và giả định

- Git chưa có HEAD; đã xác nhận danh tính local dungtatri <dungtatri.dev@gmail.com>. Chưa tạo baseline source.
- User xác nhận dung_infracore trên hosting dành riêng dự án. Ảnh cho thấy MyISAM/InnoDB và collation hỗn hợp; chưa kiểm schema đầy đủ, truy cập hoặc backup ngoài repo.
- Chưa gửi BE-GATE #1: cần xác định DB dev/test và kiểm chứng schema để đề xuất migration đúng nền.
- Quyết định tự chọn ghi ở DECISIONS.md.
- Các dòng tổng hợp trong Word chưa có mã riêng (ví dụ motion F12.2–F12.9) phải tách rõ khi bắt đầu phase đó.
## Cập nhật schema

Đã đọc dung_infracore.sql, 96 bảng, 68 MyISAM/28 InnoDB; không có ita_. Báo cáo SCHEMA_AUDIT.md. F0.2 đã có bằng chứng schema offline; DB integration và backup dữ liệu chưa xác minh. Không thực thi SQL. Chuẩn bị BE-GATE #1 trên nền thực tế, gồm scope store_id và không FK dc_users.

## Build FE Phase 1 ngày 16/10/2026

Mini plan: (1) tokens/theme cục bộ; (2) layout và sidebar riêng; (3) component/mock tương tác; (4) Playwright 3 kích thước và lỗi tải; (5) review/commit và đề xuất BE-GATE.

F1.6 theme đã build/verify. F1.7 khung đã build/verify. F1.8 KPI, bảng, badge, modal chi tiết đã build/verify; modal xác nhận nghiệp vụ còn chờ hành động BE. F1.9 sidebar riêng không include chức năng cũ đã build/verify. Chưa nghiệm thu tích hợp PHP/DB. Chi tiết UI_PHASE01_VERIFY.md. BE-GATE đề xuất tại BE_GATE_01.md.

## SQL giao user chạy host

Phạm vi user yêu cầu: soạn SQL tạo 12 bảng ITA. File tổng database/host/phase01_create_ita_tables.sql, kèm preflight và verify. Local MySQL 8.4.11: migrations và bundle tạo 12 bảng, 30 kiểm tra ràng buộc đạt. MariaDB host chưa chạy; chờ user báo kết quả. Không seed hoặc sửa PHP. Chi tiết SQL_PHASE01_VERIFY.md.
