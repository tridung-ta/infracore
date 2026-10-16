# Kế hoạch triển khai dự án 5

Nguồn: Phần 6 của tài liệu Word ngày 16/10/2026. Giữ nguyên phạm vi; trạng thái thật ở PROGRESS.md.

## Điều chỉnh từ audit 16/10/2026

- PHP local 8.5.10; Smarty 4.5.5; Bootstrap 5.3.8 có sẵn tại css/vendor/bootstrap-5.3.8/.
- database/migrations/ hiện rỗng; chưa có schema hoặc DB dev/test được xác nhận. Engine bảng cũ chưa kiểm chứng.
- .legacy-hidden đang chặn toàn bộ entrypoint trước DB; F1.10 phải xử lý cả project_gate.inc.php và router/server rewrite, không chỉ dispatcher admin.
- Không mặc định tái sử dụng role PM cho IT; dc_pm_roles và dc_users mới chỉ được xác định từ code.
- Git main chưa có commit; chưa có develop hoặc baseline để tag. Danh tính Git hiện có mang tên dự án khác, cần user xác nhận trước commit nguồn.
- Không sửa PHP hoặc DB ở Phase 0. Không đánh dấu Phase 0 hoàn tất khi chưa có baseline, tag và smoke test.
- Phần quy tắc nghiệp vụ 4.1–4.7 trong Word là nguồn áp dụng; các giả định BE phải được liệt kê khi mở gate.

## Các phase và nghiệm thu

6. Kế hoạch theo phase

Quy ước: mỗi mã Fx.y là một chức năng = một commit (Phần 9). Nhãn [FE] làm ngay không hỏi; nhãn [BE] phải qua BE-GATE trước (Phần 7.3). Nhánh mỗi phase: feature/ita-phaseNN-<tên>. Tên skill ghi đúng như đã cài trong Codex (Phần 8).


### Phase 0 — Audit, dọn dẹp an toàn, baseline

Mục tiêu: hiểu nền Derasoft thật, thư mục sạch, không mất gì.

Chức năng: F0.1 chore(repo): tag backup + dọn rác an toàn (Phần 10.1). F0.2 docs(audit): audit nền Derasoft (cấu trúc thư mục, auth, tên bảng, hash mật khẩu, đường dẫn migration, cron, thư viện có sẵn, phiên bản PHP/Smarty) vào docs/AUDIT.md. F0.3 docs(legacy): kiểm kê giao diện và chức năng cũ vào docs/LEGACY_INVENTORY.md. F0.4 docs(plan): chốt docs/PLAN_FINAL.md và docs/PROGRESS.md. F0.5 test(baseline): php -l toàn repo và smoke test hiện trạng.

Vùng: chỉ đọc code, git, tài liệu; không sửa PHP/DB nên không có BE-GATE.

Skill: Git Feature Commit, Database Safety (chỉ đọc schema), System Design (kiến trúc trong AUDIT), Code Review (đối chiếu quy ước), Ecc (nếu là workflow khảo sát code bạn quen dùng).

Nghiệm thu: tag backup tồn tại, git status sạch, có đủ CLEANUP_LOG, AUDIT, LEGACY_INVENTORY, PLAN_FINAL, PROGRESS.


### Phase 1 — Nền dữ liệu và khung giao diện

Chức năng: F1.1 feat(db): bảng ita_departments, ita_asset_categories, ita_settings [BE]. F1.2 feat(db): ita_assets + index [BE]. F1.3 feat(db): licenses, allocations, assignments [BE]. F1.4 feat(db): tickets, ticket_events, audit_logs, budget_plans, import_batches [BE]. F1.5 feat(db): seed dữ liệu mẫu dev/test [BE]. F1.6 feat(ui): design tokens + Bootstrap dark theme [FE]. F1.7 feat(ui): layout sidebar, topbar, breadcrumb, toast [FE]. F1.8 feat(ui): component dùng chung (bảng phân trang, KPI card, badge trạng thái, modal xác nhận) [FE]. F1.9 feat(legacy): sidebar mới không có mục chức năng cũ [FE]. F1.10 feat(legacy): guard route cũ theo cờ ITA_LEGACY_UI_ENABLED [BE].

Vùng: F1.1–F1.5 và F1.10 gộp thành một BE-GATE; F1.6–F1.9 chạy ngay song song trong lúc chờ.

Skill: System Design (ERD, docs/ERD.md), Mysql Patterns, Database Safety, API Design (quy ước JSON), Impeccable, Feature Development, Git Feature Commit.

Nghiệm thu: migration chạy xuôi và ngược trên DB test; ERD đạt 3NF; layout đúng ở 1366/768/390 px; git diff --name-status không có file cũ bị xóa.


### Phase 2 — Đăng nhập, phân quyền, log (yêu cầu 1)

Chức năng: F2.1 feat(auth): đăng nhập trên giao diện mới [BE+FE]. F2.2 feat(rbac): 3 vai trò + ma trận quyền 4.1 [BE]. F2.3 feat(rbac): middleware phạm vi bộ phận phụ trách [BE]. F2.4 feat(rbac): quản lý user và bộ phận phụ trách (Admin IT) [BE+FE]. F2.5 feat(audit): ghi log tạo/sửa/xóa tài sản và đổi trạng thái [BE]. F2.6 feat(audit): màn hình xem log có bộ lọc [BE+FE]. F2.7 feat(sec): rate limit đăng nhập, CSRF [BE].

Skill: Feature Development, Error Handling, Database Safety, Code Review, Playwright CLI (đăng nhập 3 vai trò, thử truy cập chéo bộ phận), Review Agent cuối phase.

Nghiệm thu: ma trận quyền 4.1 đạt; URL trực tiếp vào thiết bị ngoài phạm vi trả 403; log đủ ai, khi nào, trước/sau; log không chứa mật khẩu.


### Phase 3 — Quản lý tài sản (yêu cầu 2)

Chức năng: F3.1 feat(asset): danh sách phân trang [BE+FE]. F3.2 tạo tài sản. F3.3 sửa tài sản. F3.4 đổi trạng thái có lý do + log. F3.5 xóa mềm. F3.6 chi tiết thiết bị (tab thông tin, lịch sử). F3.7 feat(search): tìm kiếm nâng cao theo phòng ban, loại, năm mua, bảo hành. F3.8 feat(category): quản lý danh mục loại thiết bị.

Skill: Mysql Patterns (phân trang, index, tìm kiếm), Impeccable (UI danh sách), Playwright CLI, Error Handling, Feature Development.

Nghiệm thu: 500 tài sản phân trang mượt, EXPLAIN dùng index; NV hỗ trợ chỉ thấy bộ phận mình; mã trùng bị chặn; validate đủ.


### Phase 4 — Import / Export (yêu cầu 9)

Chức năng: F4.1 feat(import): tải file mẫu Excel. F4.2 upload và validate (trùng mã, danh mục, ngày). F4.3 xem trước và ghi theo lô trong transaction. F4.4 xuất file lỗi theo dòng. F4.5 feat(export): xuất danh sách tài sản ra Excel theo bộ lọc.

Skill: Spreadsheets (tạo file mẫu hợp lệ và file lỗi để test, mở file export kiểm tra), Error Handling, Database Safety, Mysql Patterns (insert theo lô).

Nghiệm thu: 500 dòng hợp lệ nhập thành công; file lỗi báo đúng dòng; trùng mã bị chặn trong file và với DB; lỗi hệ thống thì rollback cả lô.


### Phase 5 — Phân bổ và thu hồi (yêu cầu 6)

Chức năng: F5.1 giao thiết bị cho nhân viên/phòng ban. F5.2 thu hồi. F5.3 lịch sử phân bổ. F5.4 biên bản bàn giao PDF. F5.5 xác nhận nhận bàn giao. F5.6 cảnh báo quá hạn bảo trì (badge ở danh sách + chuông).

Skill: PDF (render và kiểm tra biên bản: font tiếng Việt, khung ký), Feature Development, Playwright CLI, Mysql Patterns.

Nghiệm thu: không giao được thiết bị retired hoặc đang giao; PDF có số biên bản, đúng dấu tiếng Việt.


### Phase 6 — Phần mềm và giấy phép (yêu cầu 3)

Chức năng: F6.1 CRUD giấy phép (key mã hóa). F6.2 cấp phát/thu hồi chỗ theo máy, người, phòng ban. F6.3 cảnh báo sắp hết hạn. F6.4 cảnh báo vượt mức. F6.5 xem key đầy đủ (chỉ Admin IT, có log).

Skill: Database Safety, Error Handling, Code Review (trọng tâm bảo mật), Mysql Patterns.

Nghiệm thu: key không lộ trong DB, log, HTML nguồn; cảnh báo đúng ngưỡng 4.3.


### Phase 7 — Bảo trì và ticket (yêu cầu 4)

Chức năng: F7.1 tạo phiếu bảo trì/ticket. F7.2 xử lý ticket (gán kỹ thuật viên, pending → resolved/failed, ghi ita_ticket_events). F7.3 đồng bộ trạng thái thiết bị. F7.4 lịch sử bảo trì trên chi tiết thiết bị. F7.5 thống kê số lần bảo trì mỗi năm theo thiết bị. F7.6 tính next_maintenance_due theo chu kỳ.

Skill: Feature Development, API Design, Playwright CLI, Mysql Patterns.

Nghiệm thu: chỉ chuyển trạng thái hợp lệ; NV hỗ trợ không xử lý được ticket ngoài phạm vi.


### Phase 8 — Vòng đời tài sản (yêu cầu 5)

Chức năng: F8.1 tuổi đời và tỷ lệ tuổi/tuổi thọ. F8.2 điểm thay thế (4.4). F8.3 tuổi trung bình, tỷ lệ lỗi theo loại và phòng ban. F8.4 danh sách đề xuất thay thế.

Skill: Mysql Patterns (aggregate, subquery), Visualize (dựng thử biểu đồ trước khi code), Analytics Dashboard (chỉ tham khảo bố cục bảng số liệu), Code Review.

Nghiệm thu: công thức khớp ví dụ tính tay trong docs/; truy vấn thống kê dùng index.


### Phase 9 — Dự báo và ngân sách (yêu cầu 7)

Chức năng: F9.1 cấu hình định mức và tham số. F9.2 dự báo ngân sách bảo trì/thay thế. F9.3 thiết bị cần mua thêm theo kế hoạch mở rộng. F9.4 đổi bộ lọc (năm, phòng ban, loại). F9.5 xuất ngân sách Excel/PDF.

Skill: Financial Budget và Three-Statement Forecast (chỉ tham khảo cách bố trí bảng khi thiết kế file xuất), Spreadsheets (kiểm tra file), Visualize.

Nghiệm thu: từng thành phần ngân sách cộng khớp tổng; đổi bộ lọc cho kết quả đúng.


### Phase 10 — Dashboard và báo cáo (yêu cầu 8)

Chức năng: F10.1 dashboard KPI. F10.2 biểu đồ tình trạng theo loại, phòng ban, năm mua. F10.3 biểu đồ tỷ lệ lỗi. F10.4 biểu đồ thiết bị sắp thay thế. F10.5 báo cáo Excel (tồn kho, sắp bảo trì, vòng đời ngắn, ngân sách). F10.6 báo cáo PDF. F10.7 trung tâm cảnh báo (chuông + trang).

Skill: Impeccable, Visualize, Spreadsheets, PDF, Playwright CLI, Mysql Patterns.

Nghiệm thu: số liệu dashboard khớp truy vấn tay; mọi biểu đồ tôn trọng phạm vi quyền.


### Phase 11 — Tối ưu, bảo mật, hoàn thiện (yêu cầu 10)

Chức năng: F11.1 rà soát index theo EXPLAIN (docs/PERF_NOTES.md). F11.2 rà soát bảo mật (XSS, CSRF, SQLi, upload, quyền). F11.3 responsive lần cuối. F11.4 trang lỗi chuẩn và xử lý lỗi. F11.5 gói deploy và checklist FileZilla trong docs/DEPLOY_LOG.md. F11.6 hướng dẫn sử dụng.

Skill: Mysql Patterns, Code Review, Review Agent, Error Handling, Database Safety, Playwright CLI, System Design, Documents (nếu cần xuất hướng dẫn dạng Word).

Nghiệm thu: không truy vấn nặng không index; checklist bảo mật đạt; sẵn sàng để user deploy.


### Phase 12 — UI Motion (mở rộng)

Chức năng: F12.1 hạ tầng motion + cờ UI_MOTION_ENABLED + reduced-motion. Sau đó mỗi màn một commit theo thứ tự: F12.2 cảnh báo, F12.3 ticket, F12.4 dashboard, F12.5 danh sách tài sản, F12.6 chi tiết thiết bị, F12.7 vòng đời, F12.8 dự báo, F12.9 đăng nhập.

Skill: Impeccable, Playwright CLI (không lỗi console, thử reduced-motion), Image Gen (chỉ khi cần texture nền; ưu tiên CSS/SVG).

Vùng: [FE] hoàn toàn; cần endpoint mới thì qua BE-GATE.


### Phase 13 — Hạ tầng mạng 2D rồi 3D (tùy chọn)

Chức năng: F13.1 feat(db): ita_network_links + dữ liệu mẫu [BE]. F13.2 trang 2D. F13.3 node pulse và đường dữ liệu chạy. F13.4 bản 3D Three.js nạp lazy + dự phòng 2D.

Skill: Visualize, Impeccable, Playwright CLI, Database Safety.

## Điều chỉnh sau khảo sát SQL

Xem SCHEMA_AUDIT.md. Thiết kế bảng IT cần bảo toàn scope store_id của nền. Không FK tới dc_users MyISAM; user_id tương thích int có dấu. Không đổi engine/schema cũ. Rollback transaction chỉ bảo đảm cho writer InnoDB mới; không ghi bảng cũ trong seed IT. Schema export không thay backup dữ liệu.

## Tách đầu việc seed khi triển khai

F1.5a là danh mục khởi tạo độc lập, không ghi đè cấu hình; F1.5b là bộ seed nghiệp vụ dev/test số lượng lớn. Mỗi phần có commit và verify riêng. F1.5 tổng chỉ hoàn tất khi cả hai đạt. F1.10 chỉ nghiệm thu khi cả gate PHP và kiểm Apache/khôi phục legacy đạt; hiện mới kiểm PHP local.
