# BE-GATE HOST-MIGRATIONS-005-006 — NHÁP, CHƯA DUYỆT THỰC THI

Ngày26/10/2026. Chỉ kế hoạch đối chiếu; chưa đọc metadata/kết nối/SQL host, không tạo gói/upload. Duyệt SQL13 trước đây không thay xác nhận môi trường và lần chạy host này. Không merge/tag7–10/push/deploy.

## Phạm vi và bằng chứng còn thiếu

Hồ sơ cũ xác nhận12 tên bảng baseline001–004 và11FK; không chứng minh inventory **hiện tại** hoặc005/006 thiếu. Host từng login ITA được nên có thể005 đã có; tuyệt đối không chạy005 theo phép trừ12→16. Cần user cung cấp metadata đọc hiện tại, không dump rows/config/secret. Agent chưa chạy.

| Migration | Bảng kỳ vọng | Trạng thái host hiện tại |
|---|---|---|
|001|ita_departments, ita_asset_categories, ita_settings|Có bằng chứng lịch sử; hiện chưa đối chiếu|
|002|ita_assets|Như trên|
|003|ita_licenses, ita_license_allocations, ita_assignments|Như trên|
|004|ita_tickets, ita_ticket_events, ita_audit_logs, ita_budget_plans, ita_import_batches|Như trên|
|005|ita_user_roles, ita_user_scopes, ita_login_limits|Chưa xác định, không coi thiếu|
|006|ita_network_links|Chưa xác định, không coi thiếu|

So sánh tập16tên local theo migration source (localDB cũng chưa chạy), từng hostpath/schema/engine/collation/cột/index/FK/CHECK/generated. Tên đúng không đủ; bảng có sẵn nhưng khác cấu trúc cần gate riêng, không DROP/CREATE/ALTER tự sửa. Ghi both/host-only/local-plan-only/unknown, không so số tổng đơn thuần. dc_users ngoài16, chỉ metadata có liên quan signedid/store/MyISAM.

## Thay đổi dự kiến nếu xác minh thiếu

Chỉ additive CREATE của đúng migration còn thiếu: **005 trước006**, sau xác nhận001–004 đầy đủ;006 FK tớiassets,005 scopesFKdepartments. Nếu005 đã đủ thì bỏ qua **toàn file**, không chạy lại. Nếu chỉ một số bảng của005 tồn tại, dừng và trình deltaDDL chính xác riêng, không IF NOT EXISTS che sai lệch. Không seed/authmapping/DCwrite hoặc copy credential. 006 đủ thì không chạy. Bản gốc USE dung_infracore phải so đúng schema host user xác nhận; nếu khác cần bản đích đã review riêng, không thay schema ngầm.

## Backup, dry-run và kiểm

1. User xác nhận vhost/schema/source thực +cửa sổ triển khai +backup source/DB/khóa ngoàiwebroot, checksum và khả năng khôi phục. DDLaccount riêng, runtime không CREATE/ALTER/DROP. Không gửi credential.
2. Dry-run **tĩnh**: so inventory/SHOW CREATE TABLE đã che, engine/collation/FK/generated/index/CHECK, xung đột tên, quyền/quota/lock impact, hashDDL/source cố định. SQL CREATE không có rollback/dry-run vô hại; không chạy rồi ROLLBACK để giả preview. Rehearsal chỉ DBlocal được xác nhận riêng.
3. Trình duyệt lại gate với **DDL cụ thể và danh sách bảng thực sự thiếu**, môi trường/backup/ảnh hưởng/grants/hoàn tác. Gate nháp này chưa cho chạy bất kỳ câuSQLhost nào.
4. Sau user duyệt riêng, user chạy migration được chọn bằngDDLaccount, dừng lỗi đầu tiên; kiểm metadata/countFK/CHECK thực, grants runtime từngbảng và smokePHP/HTTPS/login/dashboard/network scope. Không mặc định SQLsuccess lànghiệmthuDB.

## Rủi ro và hoàn tác

DDL implicit commit/metadata lock, lỗi giữa file để lại một phần, authmapping sai có thể mất quyền, MyISAMnative không atomic vớiita_. Không đổi engine hoặcreset account. Thất bại: dừng ghi chức năng mới, khôi phục source đãbackup nếu tươngthích, **giữ bảng/dữ liệu mới**, không DROP/restore dump đè dữ liệu. Migrationadditive có thể để bảng chưa dùng; rollbackdestructive chỉ sau gate riêng chứng minh không dữ liệu và backup. Ghi thao tác thật và phần chưa kiểm; không đưa tựđộngDOWN vào gói.

Trả lời mong muốn sau khi có metadata: OK / OK với chỉnh sửa / Không cho **phạm viDDL xác định lại**. Hiện chưa yêu cầu user duyệt chạy bản nháp thiếu inventory.
