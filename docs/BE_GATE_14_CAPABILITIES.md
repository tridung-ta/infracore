# BE-GATE #14 — Quyền theo chức năng (SOURCE ĐÃ DUYỆT)

User27/10/2026 xác nhận **“Duyệt BUILD source BE14 theo gate”**. Cho build/test local không DB trong phạm vi dưới; SQL vẫn trình riêng, không thực thi hoặc upload. Các đoạn “chưa duyệt” bên dưới ghi thời điểm soạn ban đầu, được thay bởi xác nhận source này.

27/10/2026: user muốn tài khoản C dùng tổ hợp chức năng1–2–6 mà không thêm role mới. Đã chuẩn bị FE MOCK, **chưa đổi server/quyền/DB**. AGENTS.md mục2 yêu cầu duyệt trước mọi PHP/migration/SQL ghi. User xem demo không tự nghĩa là duyệt gate này.

## Phạm vi xin duyệt source

Thay quyết định truy cập theo role cứng bằng permission key + scope tại server, cho toàn bộ đường ITA đang có, gồm GET chi tiết/download và POST. Ba role hiện tại chỉ làm **mẫu quyền ban đầu/compatibility**, không thêm role cho mỗi tổ hợp. Chọn quyền từng người; chưa chọn thì từ chối. Quyền không thay ràng buộc trạng thái, version, ownership, store hoặc CSRF.

1. Catalogue permission cố định trong source (không nhận tên quyền tùy ý từ client), phân biệt đọc/tạo/sửa/trạng thái/ghi chú/xóa mềm tài sản; quản lý loại; upload/confirm/export; xem/giao/thu hồi/tự xác nhận/PDF bàn giao; xem/sửa/cấp-thu hồi/override/reveal giấy phép; xem/tạo/gán/xử lý/chi phí/ready/thống kê ticket; tổng quan/cảnh báo/vòng đời; đọc/cấu hình/lưu/xuất ngân sách; đọc/xuất báo cáo; mạng; audit; quản lý quyền. Catalogue cuối phải đối chiếu từng action/suboperation và trình matrix trước mở tính năng. Demo checkbox chỉ là bản thảo, không chứng minh guard đã nối.
2. Mỗi tài khoản lưu tập quyền riêng. Ví dụ A=asset.read/create/update; B=license.read/allocate +report.read; C=asset.read/create +report.read. Không thêm role4. Dependency đọc để thực hiện ghi phải kiểm cả UI/server; không cho tick thiếu dependency rồi vô tình cấp quyền ngầm.
3. Scope bộ phận vẫn kiểm server; scope toàn store là cờ được cấp riêng, không suy từ tên role. Liên kết mạng kiểm cả hai đầu; tự nhận bàn giao vẫn chỉ chính người nhận; report maintenance/statistics-self của Hỗ trợ phải có key/điều kiện riêng để chuyển đổi không nới quyền. Không cấp CRUD tài sản đầy đủ thay cho quyền ghi chú.
4. Quản trị qua quyền access.manage; không cho cấp quyền/phạm vi vượt quyền được phép ủy quyền của chính người cấp. Bảo vệ người quản trị cuối cùng đang hoạt động bằng transaction/lock, giữ audit và auth_revision để phiên cũ mất hiệu lực. Không chỉ ẩn menu.
5. Key giấy phép vẫn cần quyền riêng +reauth/rate-limit/audit fail-closed/no-store/tự ẩn; cấp vượt mức vẫn key riêng +xác nhận/lý do. Quyền export vẫn kiểm lại actor/native/scope/revision trước audit/bytes theo contract đã có. Không nới vì chuyển từ role sang capability.

## File và dữ liệu dự kiến

- Lớp capability/catalogue mới; ItaUserObjectInfo/ItaUserObjects/ItaSession/AccessService và dispatcher ItaApplication; DAO/service/rules ITA đang dùng isAdmin/requireAdmin/actor.role. Nối frontend từ server effective permissions, không tin checkbox client.
- Nhóm guard cần rà: asset/category/import/assignment/handover/license/reveal/ticket/lifecycle/forecast/dashboard/report/network/audit/user. Truy vết từng route/suboperation, không chỉ replace chuỗi role toàn repo.
- Đề xuất migration **007_ita_user_permissions.sql**: bảng ita_user_permissions (store_id,user_id,permission_key,active,updated_by,updated_at), khóa duy nhất store/user/key, InnoDB/utf8mb4. Catalogue trong code; không FK vào dc_users MyISAM. Có thể cần thêm thông tin scope toàn-store trong bảng ITA sau khi chốt schema chính xác.
- DDL/mapping quyền cũ→mới sẽ trình **gate SQL riêng** trước thực thi. Chỉ soạn khi source gate được duyệt; agent không tạo/kết nối DB hoặc chạy migration/seed/GRANT. Không sửa dc_*, native auth/hash/cookie/security headers.
- Cờ kích hoạt mới mặc định chưa bật; khi bật mà thiếu bảng/catalogue không khớp thì fail-closed, không âm thầm fallback cho phép theo role. Không bật trên host trong bước BUILD.

## Rủi ro, kiểm và hoàn tác

Rủi ro cao: sót GET/POST/suboperation, quyền đọc gián tiếp qua báo cáo/export, scope bị mở rộng, tự tăng quyền hoặc mất người quản trị cuối, đổi quyền giữa request. Phải có matrix route×capability×scope, test forged keys/no permissions/dependency/stale revision/cross-store/cross-department, grant/revoke/audit lỗi/last-manager, dữ liệu key không lộ, export recheck và giữ hành vi role cũ qua mapping ban đầu. Chạy fake transport local trước; DB/host/concurrency ghi chưa kiểm đến khi user cho môi trường và xác nhận riêng.

Backup source/config private và DB trước áp dụng do user/quản trị. Rollback source/cờ về baseline đã backup, giữ bảng/quyền/audit/dữ liệu mới, không DROP/restore dump đè. **Role-only cũ có thể khác quyền tùy chọn mới**, phải dừng thao tác ghi và đối chiếu trước rollback; không gọi rollback này là tương đương chính sách quyền mới. Không bật .ita-legacy-enabled làm lối tắt.

Trả lời mong muốn: **Duyệt BUILD source BE14 theo gate này / OK với chỉnh sửa / Chưa duyệt**. Duyệt source không cho chạy SQL/DB hoặc deploy. Chưa đóng gói/push/merge/tag7–10/upload.
