# BE-GATE #03 — Quản lý tài sản và hoàn thiện log tài sản

Ngày chuẩn bị: 19/10/2026. Trạng thái: chờ user duyệt; chưa sửa backend Phase 3.

## Phạm vi

F3.1–F3.8: danh sách phân trang, tạo/sửa tài sản, đổi trạng thái có lý do, xóa mềm, chi tiết/lịch sử, lọc nâng cao, quản lý danh mục. Kèm hoàn thiện F2.5: audit tạo/sửa/xóa mềm/đổi trạng thái cùng transaction ghi tài sản.

## Thay đổi dự kiến

- Mở rộng DAO `itaassetobjects`/`itaassetobjectinfo`, thêm service validation và ghi tài sản; nối dispatcher `ItaApplication` bằng endpoint JSON có CSRF.
- Thêm form tạo/sửa, xác nhận trạng thái/xóa, chi tiết và lịch sử, bộ lọc loại/năm mua/bảo hành; giữ giao diện và scope hiện có.
- Quyền theo BUSINESS_RULES_SOURCE.md 4.1: Admin toàn store; Quản lý tạo/sửa trong bộ phận được gán; Hỗ trợ chỉ sửa trạng thái/ghi chú trong phạm vi, không sửa thông tin chung, xóa hoặc thanh lý. Chỉ Admin thực hiện xóa mềm/thanh lý trực tiếp. Luồng duyệt thanh lý chưa có sẽ không được giả bằng quyền sửa trực tiếp.
- Store và actor lấy từ session; không nhận quyền từ payload. Không đổi dc_users, credential, auth_key, engine hoặc schema native.

## Migration và hoàn tác

Schema hiện tại đã có cột tài sản, trạng thái, soft delete, danh mục và audit. Dự kiến không cần DDL mới; không chạy lại phase01_create_ita_tables.sql hoặc migration 005. Nếu phát sinh nhu cầu thay schema ngoài phạm vi này, trình thay đổi cụ thể trước khi làm.

Hoàn tác runtime bằng phục hồi source trước cutover; giữ nguyên dữ liệu tài sản/log đã ghi. Không DROP, hard delete hoặc ghi đè dữ liệu mới bằng dump. Cập nhật tài sản và audit trong transaction InnoDB; ghi nhận giá trị trước/sau sau khi khóa bản ghi để tránh mất cập nhật đồng thời.

## Rủi ro, backup và kiểm chứng

User backup DB/source ngoài repo trước triển khai host. Agent chỉ phát triển trên DB fixture local, không kết nối hoặc ghi host. Bộ 500 tài sản kiểm hiệu năng chỉ tạo trong schema QA riêng, không seed host. Verify: ba vai trò, request trái quyền, validation/mã trùng, CSRF/XSS, rollback audit, EXPLAIN, form submit/read-back, responsive 1366/768/390 và Review Agent. User tự upload FileZilla, chạy SQL host nếu được bàn giao sau này.

## Giả định cần duyệt

Áp dụng ma trận quyền nêu trên; không sửa nghiệp vụ phân bổ/current_user trực tiếp để bỏ qua lịch sử Phase 5. Danh mục chỉ Admin quản lý; danh mục đang dùng không bị xóa cứng. Không triển khai Clear Trash trong Phase 3.

Trả lời mong muốn: **OK / OK với chỉnh sửa / Không**.
