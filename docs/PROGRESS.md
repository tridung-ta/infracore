# Tiến độ dự án 5

Cập nhật 21/10/2026. Chưa nghiệm thu đầy đủ yêu cầu sản phẩm nào trong 10 yêu cầu. Bảng đầu là kế hoạch gốc; các mục cập nhật cuối tài liệu ghi trạng thái build/verify mới nhất, không tính audit thành phần trăm chức năng.

## Trạng thái hiện hành sau đối chiếu Word

### Phase 7 — Đã duyệt kèm chỉnh sửa, đang build local

**UAT host đang có lỗi mở:** user tạo được asset UAT-IT-001/id1, next_due2027-04-20; đã mở trang ticket nhưng báo tạo phiếu/tải danh sách không thấy phản hồi. Chưa chứng minh INSERT ticket thành công. Đã tái hiện/sửa FE phản hồi ngoài viewport và thiếu completion; chuẩn bị hotfix2file. Nguyên nhân ghi DB host chưa xác minh, Phase7 tiếp tục chưa nghiệm thu/merge.

Đã chuẩn bị gói upload cộng dồn từ source80929be theo yêu cầu user, 1.340 runtime files; manifest/CRC/hash đọc lại đạt, chi tiết DEPLOY_LOG.md và HOST_UPLOAD_PHASE07.md. Agent chưa upload. User đã gửi metadata DB dung_infracore/MariaDB10.6.28, 5 bảng chính InnoDB, CHECK=ON ở phiên phpMyAdmin; nghiệp vụ DB Phase1–7 vẫn chưa nghiệm thu.

Không merge Phase 7 trước khi user kiểm thử DB thật Phase 1–7. Chưa xác minh CHECK trên DB thật; phiên bản host theo ảnh cũ 10.6.28-MariaDB, chưa kết nối lại. Service kiểm transitions độc lập CHECK. User duyệt #07-SCHEDULE riêng; lịch ban đầu từ ngày mua đã nối Phase 3–5, không DDL/backfill/DB/deploy. Không mở Phase 8 trước kiểm thử DB đã thống nhất.

| Mã | Chức năng | Vùng | Trạng thái | Commit | Verify | Ghi chú |
| --- | --- | --- | --- | --- | --- | --- |
| F7.1 | Tạo/list/detail phiếu | BE/FE | [~] | c4c5942 | scripted236 + browser25 | Ba vai trò theo scope; DB chưa chạy |
| F7.2 | Gán/xử lý/cost | BE/FE | [~] | c4c5942 | scripted + form mock đạt | Admin-only closed cost, app transitions |
| F7.3 | Đồng bộ/guard asset | BE/FE | [~] | c4c5942 | Phase3 39, Phase5 203 | Pending vẫn cho thu hồi; chưa DB locking |
| F7.4 | Lịch sử gộp | BE/FE | [~] | c4c5942 | browser inline history đạt | Phân trang20, SQL UNION chưa chạy DB |
| F7.5 | Thống kê theo asset/năm | BE/FE | [~] | c4c5942 | tham số/query contract + UI đạt | opened_at/type=repair cho Phase8; EXPLAIN chưa chạy |
| F7.6 | Lịch bảo trì | BE/FE | [~] | ddc9c87 | cuối tháng, nguồn ngày mua/last đạt | Gate schedule duyệt; DB timezone chưa chạy |

### Phase 6 — Gate đã duyệt kèm chỉnh sửa, đang build local

User duyệt #06 ngày 20/10: chỉ Admin xác nhận vượt mức với lý do/audit; xem key POST+CSRF/no-store/rate limit/nhập lại mật khẩu/tự ẩn 10 giây/audit fail-closed; preflight khóa. Không DDL, không DB/deploy. **Phase 1–6 chưa được kiểm chứng đầy đủ trên DB thật; cần kiểm thử DB trước Phase 8.** Không dùng kết quả mock thay nghiệm thu DB.

Giới hạn an toàn từ schema: giấy phép không có bộ phận sở hữu và native user chưa có membership đã xác minh. Metadata chung chỉ Admin sửa; manager xem catalog che key và cấp/thu hồi máy/bộ phận theo scope; cấp theo user chỉ Admin. Không thêm cột để suy diễn quyền.

| Mã | Chức năng | Vùng | Trạng thái | Commit | Verify | Ghi chú |
| --- | --- | --- | --- | --- | --- | --- |
| F6.1 | Danh mục và key mã hóa | BE/FE | [~] | 068931b | pure32 + scripted159 + browser28 đạt | Không DB; PHASE06_VERIFY.md |
| F6.2 | Cấp phát/thu hồi | BE/FE | [~] | 068931b | scripted scope/transaction/overage + browser đạt | DB concurrency chưa chạy |
| F6.3–F6.4 | Hết hạn/vượt mức | BE/FE | [~] | 068931b | ngày biên + chuông/dashboard mock đạt | Chưa DB/timezone host |
| F6.5 | Xem key có reauth/audit | BE/FE | [~] | 068931b | audit failure/rate/role + browser tự ẩn10s đạt | HTTP/DB host chưa kiểm |

### Phase 5 — BE và PDF đã duyệt, đã nối local, host UAT chưa chạy

User duyệt **“OK BE Phase 5”** và **“OK PDF Phase 5”** ngày 19/10/2026. Đã nối giao nhân viên/bộ phận, thu hồi (gồm Support trong scope), lịch sử 20 dòng/trang, người nhận tự xác nhận, badge/chuông quá hạn từ ngày bảo trì có sẵn. Ngày 20/10 đã hoàn thiện PDF thật bằng mPDF8.3.1, tải từ lượt đang mở/lịch sử, kiểm lại actor/scope/version và audit trước trả bytes. Không DDL, không ghi dc_, tạo DB hoặc kết nối/deploy host. GD local chỉ bật per-process; host cần kiểm riêng.

`php tests/ita_phase05_unit.php`: **173 pure/scripted checks đạt**, không DB. `tests/ita_phase05_browser.js`: **29 browser API mock checks đạt**; static giữ 22 checks. PDF: **118 PHP/renderer/scripted +2 thiếu-GD +18 browser transport mock checks đạt**; pypdf/pdfplumber và Poppler xác minh 1/2 trang, tiếng Việt, số biên bản, seri, giờ VN và khung ký. Chi tiết PHASE05_PDF_VERIFY.md. Checkpoint bfe3539 đã commit/push nhánh feature/ita-phase05-pdf theo yêu cầu user; F5.4 lưu commit riêng. Không tag phase-done vì DB/host UAT còn mở.

| Mã | Chức năng | Vùng | Trạng thái | Commit | Verify | Ghi chú |
| --- | --- | --- | --- | --- | --- | --- |
| F5.1–F5.3 | Giao/thu hồi/lịch sử | FE/BE | [~] | bfe3539 checkpoint | Pure/scripted173 + browser API mock29 đạt | Code local có, DB/concurrency chưa chạy |
| F5.4 | Biên bản PDF | BE/FE | [~] | 5f1e6ea | PHP118 + thiếu-GD2 + browser18 + parser/render đạt | Code local hoàn tất, auth/DB/host UAT chưa chạy |
| F5.5–F5.6 | Xác nhận nhận/quá hạn bảo trì | FE/BE | [~] | bfe3539 checkpoint | Rule/scope/alert query + browser submit/badge/center đạt | DB/read-back/timezone host chưa chạy |

### Phase 4 — BE-GATE đã duyệt, đã build local, DB UAT chưa chạy

User duyệt **“OK BE Phase 4”** ngày 19/10/2026. Đã nối tải mẫu XLSX, upload/đọc/validate, staging private, preview 20 dòng/trang, confirm một transaction có audit và chống ghi hai lần, Excel lỗi, export theo filter/scope. Dùng PhpSpreadsheet có sẵn; không package mới, không DDL, không tạo/kết nối DB, không deploy. Static preview vẫn giữ 4 kịch bản MOCK và khóa ghi thật.

`tests/ita_phase04_unit.php`: **88 checks đạt** (XLSX thật + pure + adapter giả, không DB). `tests/ita_phase04_browser.js`: **30 checks đạt** (API mock, 1366/768/390, upload/preview/confirm/download/lỗi/mất phản hồi/role). `tests/ita_phase04_preview.js`: **16 checks đạt** (MOCK không ghi). Chi tiết `PHASE04_VERIFY.md`; phần DB/host còn lại ở `PHASE04_HOST_TEST.md`. Phase3 DB UAT và Git theo chức năng còn mở, không tag phase-done. Không deploy hoặc dùng gói cũ.

| Mã | Chức năng | Vùng | Trạng thái | Commit | Verify | Ghi chú |
| --- | --- | --- | --- | --- | --- | --- |
| F4.1 | Tải mẫu Excel | FE/BE | [~] | chưa commit | XLSX thật + browser API mock đạt | Code local có; HTTP host/Excel desktop chưa chạy |
| F4.2 | Upload và validate | FE/BE | [~] | chưa commit | pure/XLSX/adapter + browser API mock đạt | SQL collation/DB thật chưa chạy |
| F4.3 | Preview và confirm theo lô | FE/BE | [~] | chưa commit | adapter transaction/idempotency/role + browser API mock đạt | InnoDB rollback/concurrency thật chưa chạy |
| F4.4 | Excel lỗi theo dòng | FE/BE | [~] | chưa commit | writer thật + browser download mock đạt | Host UAT chưa chạy |
| F4.5 | Export theo filter/scope | FE/BE | [~] | chưa commit | adapter query/scope/limit + browser download mock đạt | SQL/performance/host UAT chưa chạy |

- Phạm vi đang build: Phase 3 và BE Phase 4 đã duyệt; không tự mở BE Phase 5. Host Phase 2 đã được user xác nhận login username/Gmail và logout, chưa coi là toàn bộ RBAC UAT đạt.
- Local Phase 3: **36 pure/scripted checks +30 browser API mock checks đạt**, Smarty render/escape đạt. Đã kiểm khoảng bảo hành nhập ngược, mảng thay ngày, ngày không tồn tại; helper form còn 3 tham số.
- Có ERD.md từ migrations và PERF_NOTES.md phân biệt bằng chứng lịch sử/việc chưa chạy. RUN_LOCAL.md đã sửa: preview không DB dùng static server, PHP thật yêu cầu auth/DB.
- Chưa có DB integration Phase 3, benchmark 500 dòng hoặc nghiệm thu production. Không tạo DB, không kết nối host, không upload. Luồng duyệt thanh lý của Quản lý và phân trang lookup/history vẫn là chênh lệch còn mở; không đánh dấu toàn bộ Word đã hoàn thành.
- Các đoạn nhật ký phía dưới có thời điểm riêng; câu «chưa có HEAD», «chưa có bảng», «host chờ import» ở thời điểm audit không mô tả trạng thái hiện tại. Mã Phase 3 đang ở working tree, không gộp thành một commit nhiều chức năng hoặc tạo tag phase-done khi nghiệm thu còn thiếu.

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
| F1.4 | tạo schema ticket, audit, ngân sách và import | SQL | [~] | commit F1.4 trên feature/ita-phase01-ui | local MySQL: tạo bảng/constraint đạt | SQL giao user; MariaDB host chờ user chạy |
| F1.5 | feat(db): seed dữ liệu mẫu dev/test [BE]. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F1.6 | Design tokens + Bootstrap dark theme | FE | [x] | 051f80b | Playwright 1366/768/390 đạt, Bootstrap hash trùng nguồn | Chỉ FE, chưa tích hợp PHP |
| F1.7 | Layout sidebar, topbar, breadcrumb, toast | FE | [x] | c61ae8f | PHP/Smarty + browser MOCK 1366/768/390 đạt | Chưa có live DB/auth |
| F1.8 | Component dùng chung | FE | [x] | d342024 | bảng/KPI/badge/modal chi tiết + confirm cancel/accept/escaping đạt | API confirm không tự ghi DB |
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

## Xác nhận từ user sau import SQL host

Ngày 16/10/2026: user báo «đã thành công» sau hướng dẫn chạy phase01_verify.sql. Ghi nhận user xác nhận tạo bảng trên dung_infracore/MariaDB hosting thành công; agent không kết nối host và chưa nhận ảnh bộ đếm 12/12/12 hoặc danh sách đủ 11 khóa ngoại sau lần import cuối. Không suy diễn xác nhận này thành quyền seed, sửa PHP/routing hoặc deploy. Migration F1.1–F1.4 đã bàn giao và được user xác nhận chạy; ứng dụng chưa tích hợp DB.
## Đính chính xác nhận hosting

Ảnh user gửi tiếp theo cho thấy selected_database=dung_infracore nhưng truy vấn metadata dùng DATABASE() trả 0/NULL/NULL, không thấy FK. Chưa xác nhận tạo đủ 12 bảng trên host; kết luận thành công trước đó chỉ dựa trên lời báo của user và được thay bằng «chờ chẩn đoán». Trước đó import có thông báo #1050 ita_departments already exists. Chưa rõ nguyên nhân hai quan sát khác nhau; không mặc định bảng bị mất hoặc migration thất bại. Đã thay bộ lọc metadata trong preflight/verify bằng tên schema cố định để giảm phụ thuộc ngữ cảnh truy vấn. Cần SHOW TABLES và SHOW CREATE TABLE với schema chỉ định rõ. Không chạy lại CREATE hoặc xóa bảng khi chưa đối chiếu.
## Bằng chứng SHOW TABLES trên host

User gửi ảnh SHOW TABLES FROM dung_infracore LIKE 'ita%': liệt kê đủ 12 tên bảng đúng migration. SHOW CREATE TABLE dung_infracore.ita_departments trả về cấu trúc nhưng ảnh bị rút gọn. Đã xác nhận tồn tại đủ bảng; không chạy lại CREATE. Chưa xác nhận toàn bộ cột/engine/collation/11 FK qua ảnh này. Nguyên nhân truy vấn information_schema trước đó dùng DATABASE() trả 0 chưa xác minh; không kết luận bảng chưa được tạo. File verify đã dùng tên schema cố định.
## Xác nhận số lượng khóa ngoại trên host

Ảnh mới từ user cho thấy truy vấn information_schema.TABLE_CONSTRAINTS với CONSTRAINT_SCHEMA='dung_infracore' và loại FOREIGN KEY trả tổng 11 dòng. Kết hợp SHOW TABLES trước đó: đã xác nhận đủ 12 tên bảng và 11 khóa ngoại trên MariaDB host. Chưa có ảnh bộ đếm engine/collation 12/12/12 hoặc toàn bộ định nghĩa cột. Không yêu cầu chạy lại CREATE. Quyền PHP/routing và seed vẫn chưa được user phê duyệt.
## Build PHP sau khi user yêu cầu tiếp tục

Mini plan: render khung bằng Smarty; kiểm soát route trước bootstrap; hoàn thiện component xác nhận; kiểm route/UI thật; soạn danh mục đúng store. Đã render /admin.php?op=ita bằng Smarty; 32 checks HTTP/flags đạt và browser 1366/768/390 đạt. SQL danh mục 6/6/6 store_id=1 đã kiểm lặp local, host chưa chạy. Chi tiết PHP_PHASE01_VERIFY.md. F1.7 và F1.8 FE đã kiểm chứng; F1.10 còn Apache/legacy restoration và allowlist auth Phase 2; F1.5 còn bộ seed đầy đủ. Không tuyên bố Phase 1 hoàn tất.

### Tách seed thành hai chức năng tự chứa

- F1.5a: SQL danh mục khởi tạo 6 loại/6 bộ phận/6 cấu hình cho store 1, giữ dữ liệu hiện có; đã kiểm lặp local, host chờ user chạy.
- F1.5b: bộ seed 300–500 tài sản và dữ liệu nghiệp vụ dev/test, chưa làm; cần chọn actor dev/test đúng, không gán thao tác giả cho user thật.
- F1.10: mã gate đã kiểm local; còn Apache/host và khôi phục legacy E2E, chưa nghiệm thu toàn bộ.

Commits build mới: F1.7 c61ae8f; F1.8 d342024; gate PHP F1.10 fcc900d (còn kiểm Apache/legacy); danh mục F1.5a 0495e7d (host chờ user chạy). Không merge/tag phase done.

## File kết quả ita_settings.sql từ user

Đã đọc tests/ita_settings.sql, không thực thi: có 6 cặp setting_key/setting_value khớp danh mục khởi tạo (budget_contingency_percent=10, currency=VND, display_timezone=Asia/Ho_Chi_Minh, license_critical_days=7, license_warning_days=30, warranty_warning_days=30). INSERT chỉ xuất setting_key và setting_value, không có store_id trong khi CREATE định nghĩa store_id NOT NULL không default; file này không đủ để xác nhận scope store_id=1 hoặc dùng như bản backup phục hồi nguyên bảng. DDL xuất cũng không thể hiện đầy đủ engine/collation/CHECK. Không import lại file. Chưa nhận kết quả categories/departments sau chạy catalog; cần SELECT đếm ba nhóm theo store_id=1.
## Tinh chỉnh UI theo Impeccable

Mini plan: khảo sát giao diện và directive; chỉnh chữ/icon/responsive; kiểm hai lượt có giới hạn; review và commit riêng. Đã hoàn tất polish FE: chữ rõ hơn, icon SVG cùng hệ, KPI tablet hai cột, select mobile toàn hàng, focus bàn phím và skeleton. Playwright MOCK qua PHP/Smarty đạt; không tràn ở 1366/768/390/320/683. Cặp semantic contrast thấp nhất 4.82:1. Detector một lần có warning Inter được giữ theo hệ font hiện có. Chi tiết UI_POLISH_IMPECCABLE.md. Không thay PHP/DB. User cũng đã gửi ảnh xác nhận danh mục host 6/6/6 store 1 ở bước trước; không yêu cầu chạy lại.

## Phase 2 — Build được user cho phép sau rà UI

User chọn Admin IT đầu tiên: store 1 / user 111. Mini plan: xác thực native và session riêng; quyền/scope server; quản lý mapping và audit; kiểm HTTP/DB/browser; bàn giao SQL host. Đã kiểm local 46 browser + 32 routing + 8 integration checks, review độc lập không phát hiện lỗi. Hosting chưa chạy SQL Phase 2 hay kết nối runtime. Chi tiết PHASE02_VERIFY.md và BE_GATE_02.md.

| Mã | Chức năng | Vùng | Trạng thái | Commit | Verify | Ghi chú |
|---|---|---|---|---|---|---|
| F2.1 | Đăng nhập giao diện mới | BE+FE | [x] local | 6f461ef | PHP lint, browser đăng nhập/đăng xuất | Host chờ SQL/config; tái dùng credential native |
| F2.2 | Ba vai trò | BE | [x] local | e41e922 | URL/API trái quyền 403 | Không kế thừa PM Admin; quyền CRUD kiểm tiếp Phase 3 |
| F2.3 | Scope bộ phận/store | BE | [x] local | 9fcc1f2 | Hai non-admin, cross-store, dept, session revision | Áp dụng API tài sản đọc hiện có |
| F2.4 | Quản lý quyền user hiện có | BE+FE | [x] local | fa05be8 | Cấp/đổi quyền, last Admin guard, UI persisted | Không tạo tài khoản/đổi mật khẩu native |
| F2.5 | Audit CRUD tài sản | BE | [~] | — | Writer allowlist integration đạt | Hook cần Phase 3 CRUD, chưa xong |
| F2.6 | Xem log và bộ lọc | BE+FE | [x] local | 9a6e9ab | Browser đọc audit quyền và màn hình | Log auth/quyền đã gắn |
| F2.7 | Rate limit/CSRF | BE | [x] local | 44c4246 | Thử sai lần 6:429; thiếu token:403 | Shared DB buckets, HTTPS host chờ UAT |

Không merge/tag phase done: F2.5 và host UAT còn mở. Danh mục host Phase 1 đã được user xác nhận 6/6/6 bằng ảnh.

Lưu local: F2.7 `44c4246`; F2.2: DAO tách vai trò ITA và kiểm native active mỗi request, SQL bootstrap 111 có guard. Verify vai trò qua browser/API trái quyền và bootstrap SQL local đạt; chưa áp dụng host.

F2.2 lưu `e41e922`. F2.3: DAO scoped read, API pagination/aggregate và hiển thị live có retry; browser xác nhận scope/403/404 và hồi quy static MOCK đạt. Chưa có CRUD hoặc số liệu host.

F2.3 lưu `9fcc1f2`. F2.4: màn hình phân quyền tài khoản hiện có, bắt buộc scope non-admin, bảo vệ Admin active cuối cùng và tăng revision thu hồi session; browser cấp/đổi quyền, UI đọc lại, session cũ bị chặn đạt.

F2.4 lưu `fa05be8`. F2.6: nhật ký Admin lọc thao tác/người/ngày Việt Nam, dữ liệu trước/sau được lọc allowlist; browser đọc log thật fixture và integration bỏ secret đạt. F2.5 còn hook CRUD Phase 3.

F2.6 lưu `9a6e9ab`. F2.1: tích hợp login/logout, Smarty protected shell và dispatcher API, audit auth, cấu hình riêng; browser 3 vai trò đạt. SQL đọc/khởi tạo Admin/verify đã thực thi local; EXPLAIN scoped list/aggregate dùng idx_ita_assets_department (ref, 6 rows). Chi tiết lệnh, rollback và bước host ở PHASE02_VERIFY.md.

Tích hợp F2.1 lưu `6f461ef`. Lần kiểm cuối: 47 browser checks (bổ sung không lỗi JS runtime trên role pages), 32 routing, 8 DB integration đều đạt. Không có thao tác hosting hoặc push. Working tree còn source legacy chưa có baseline như audit trước; không stage mã cũ ngoài phạm vi.

## Sửa F2.4 sau UAT host

User xác nhận login bằng username/Gmail và logout/login trên host thành công. User báo không biết lưu quyền có thành công không. Tái hiện local phát hiện selector bộ phận sai gây lỗi trước POST, lỗi ngoài viewport và cột thao tác admin bị ẩn tại tablet. Đã sửa JS/CSS, version asset trong layout. `tests/ita_access_feedback.js`: 13 checks đạt gồm submit form thật/read-back DB ở 390/768/1366, lỗi validation/mạng/refresh và JS runtime. Host chờ upload 3 file giao diện và UAT lại, không cần SQL mới. Bài học cụ thể ở LESSONS.md; 47 checks cũ không có coverage submit UI này.

## Tiếp tục ngày 19/10/2026

- Rà lại source và trạng thái Git: không có tracked change trước khi tiếp tục; commit gần nhất `9bc9e06` sửa F2.4. Không chạy lại CREATE bảng hoặc dùng credential host.
- Bằng chứng user ngày 16/10: SQL tạo 3 bảng auth thành công; bootstrap store 1/user 111/admin_it/enabled 1; đăng nhập username/Gmail và đăng xuất trên host thành công. Thông báo không thể thu hồi Admin IT cuối cùng xác nhận guard đã phản hồi trên host, không chứng minh lưu quyền tài khoản phụ thành công.
- F2.4 vẫn cần UAT tài khoản phụ trên host; F2.5 cần hook CRUD. Không đánh dấu nghiệm thu Phase 2 toàn bộ.
- Đã đối chiếu schema ita_assets và ma trận quyền 4.1 từ tài liệu nguồn. Chuẩn bị BE_GATE_03.md cho F3.1–F3.8 và F2.5; backend Phase 3 chờ duyệt theo AGENTS mục 2. Không cần chạy lại file SQL Phase 1 đang mở trong IDE.

## Phase 3 — Build đã được duyệt, không tạo DB test

User duyệt «được bắt đầu BUILD» sau yêu cầu không tạo DB test, user sẽ test thủ công host. Agent không tạo/kết nối DB, không chạy lại SQL cũ và không deploy. Mini plan: xây service validation/transaction/audit; nối API + form; kiểm pure/scripted/Smarty/browser mock; review độc lập; đóng gói để user test host.

| Mã | Chức năng | Vùng | Trạng thái | Commit | Verify | Ghi chú |
|---|---|---|---|---|---|---|
| F3.1 | Danh sách phân trang | BE+FE | [~] | nền 9fcc1f2; runtime mới chờ UAT | kiểm mock UI, chưa benchmark | 5 dòng/trang, chưa EXPLAIN mới/500 dòng |
| F3.2 | Tạo tài sản | BE+FE | [~] | chờ UAT DB | pure/scripted + browser payload đạt | Chưa INSERT thật |
| F3.3 | Sửa thông tin | BE+FE | [~] | chờ UAT DB | version và inactive category pure/mock đạt | Chưa test locking/concurrency DB |
| F3.4 | Trạng thái/lý do/ghi chú | BE+FE | [~] | chờ UAT DB | transitions/role pure + form mock đạt | Chưa UPDATE thật |
| F3.5 | Xóa mềm | BE+FE | [~] | chờ UAT DB | admin-only/assigned guard scripted; confirm UI mock | Không hard delete |
| F3.6 | Chi tiết/lịch sử | BE+FE | [~] | chờ UAT DB | tabs/escaping/Smarty/mock đạt | 50 log mới nhất, DB chưa chạy |
| F3.7 | Lọc nâng cao | BE+FE | [~] | chờ UAT DB | query params UI mock đạt | Năm mua dùng range, chưa EXPLAIN |
| F3.8 | Quản lý loại | BE+FE | [~] | chờ UAT DB | form mock, inactive rule pure/scripted | Admin, không xóa cứng |
| F2.5 | Hook audit tài sản | BE | [~] | chờ UAT DB | scripted rollback khi audit error | Chưa chứng minh rollback InnoDB thật |

Kết quả: 33 pure/scripted checks, 28 browser API mock checks, Smarty render/escape, PHP/JS lint đạt; hồi quy static MOCK đạt (console có lỗi mạng được test chủ động tạo). Impeccable detector `[]`; đã xem form mobile. Review Agent phát hiện 1 lỗi inactive category, đã sửa; lượt cuối không thấy lỗi mới. Không đồng nhất test mock với DB integration. Runtime chưa commit hoàn tất chức năng/merge/tag vì tiêu chí SQL/read-back DB còn chờ UAT do user đảm nhận; đã chuẩn bị snapshot bàn giao có hash và checklist PHASE03_HOST_TEST.md.

### Chốt local theo chỉ đạo bổ sung

User yêu cầu chưa upload, không tạo DB và hoàn thành trong phạm vi local; các việc cần DB để user test thủ công sau. Đã bổ sung chặn trạng thái trống tại form, tách tải loại khỏi ghi chú/trạng thái và giữ thông báo lưu khi danh mục tải lỗi. Browser API mock lần cuối **30 checks đạt**; pure/scripted vẫn **33 checks**, Smarty render đạt. Hoàn tất phạm vi local; không tuyên bố DB/production đạt. Snapshot cũ `b965bd30a9b4` không còn cập nhật, chưa dùng để deploy. Chưa mở Phase 4 hoặc yêu cầu thêm DB.


### 21/10/2026 — Sửa POST bị chặn trước dispatcher

[~] F7.1: xác định includes/ita_mode.inc.php thiếu ticket-save trong POST allowlist, trả plain-text 403 trước application. Bổ sung ticket-save và hai endpoint đã duyệt #06 license-save/license-key. Không đổi quyền/CSRF, SQL hoặc config. Verify: php tests/ita_write_routes.php đạt 12 pure routing checks; php tests/ita_phase07_unit.php đạt 236 pure/scripted checks; php -l hai file PHP đạt. Chưa kiểm ghi DB thật, chưa xác định lỗi GET danh sách trên host; Phase 1–7 chưa nghiệm thu DB, không merge Phase7 hoặc bắt đầu Phase8.
