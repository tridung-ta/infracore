# BE-GATE #13 — Hạ tầng mạng thật (SOURCE ĐÃ DUYỆT)

23/10/2026: user xác nhận **“Duyệt BUILD source BE13”**. Phạm vi source dưới đây được mở; SQL chính xác tách tại BE_GATE_13_SQL.md, **chưa duyệt/chưa chạy**. Xem PHASE13_BE_VERIFY.md cho kết quả local và giới hạn; không suy thành deploy, quyền DB hoặc nghiệm thu host.

User yêu cầu BUILD liên tục; phần FE2D/3D dùng MOCK làm độc lập ngay. Gate này trình riêng vì có bảng/SQL/PHP mới; không coi yêu cầu tiếp tục là quyền agent thực thi DB/host. Không tạo DB test, không chạy migration/seed hoặc SQL ghi, không deploy/merge/tag/push.

## Phạm vi xin duyệt

F13.1: soạn migration tạo duy nhất ita_network_links và rollback tài liệu; DAO/validation/service/controller cho liên kết giữa hai tài sản phần cứng hiện có. Không tạo tài sản giả trong DB. Dữ liệu mẫu chỉ JSON MOCK phía FE; seed SQL nghiệp vụ nếu cần sẽ xin riêng.

Contract đề xuất: id, store_id, source_asset_id, target_asset_id, link_type (ethernet/fiber/wireless), source_port/target_port (text giới hạn64), capacity_label (text64, mô tả không phải số đo realtime), note (text1000), is_deleted, created_at/updated_at và version từ nội dung server. Khóa/index/FK chính xác chỉ soạn sau đối chiếu schema source; bảng InnoDB/utf8mb4, không FK hoặc ghi dc_*. Liên kết vô hướng, không tự nối, chặn trùng hai đầu đã chuẩn hóa trong store bằng guard transaction; cơ chế unique với soft-delete phải trình rõ trong migration trước dùng. Không tự đoán kết nối từ tên/IP, không scanner/ping/SNMP/cron.

Admin IT xem và quản lý liên kết store; Quản lý chỉ đọc liên kết khi cả hai đầu thuộc scope; Hỗ trợ không xem topology theo đề xuất. Guard hai đầu trước count/list/detail và khi ghi, asset chưa xóa mềm, không phần mềm. Không lộ node/metadata ngoài scope qua cạnh biên. GET network/network-detail có page/search và giới hạn trả dữ liệu rõ; POST network-save có CSRF/version/audit, lỗi audit rollback; xóa mềm. Action/audit mới phải bổ sung allowlist server. Giới hạn đề xuất200 node/400 cạnh cho một view, vượt báo lỗi/chọn phạm vi nhỏ hơn, không cắt ngầm. Danh sách quản trị20/page, sort ổn định.

## Rủi ro, kiểm và hoàn tác

Mất scope qua cạnh, hai phiên tạo trùng, unique soft-delete, FK/store sai, scene tải lớn. Local pure/scripted/browser API mock/lint/render, không DB. EXPLAIN/index thực, FK/concurrency/rollback trên host do user kiểm sau, giữ chưa nghiệm thu. Trước upload/migration user backup source/DB; rollback source giữ dữ liệu. Rollback bảng chỉ là tài liệu cho user trong môi trường/phạm vi đã xác nhận, không DROP tự động hoặc dùng rollback source làm quyền xóa dữ liệu mới.

Trước khi chạy SQL thật cần gate SQL có DDL chính xác, engine/keys/version DB, backup, tác động/rollback đã review; duyệt build source ở đây không phải duyệt agent chạy DB. Không Composer/server config/HSTS/auto_prepend mới.

Trả lời mong muốn: **Duyệt BUILD BE13 theo phạm vi / Chỉnh sửa / Chưa duyệt**. Cho tới khi có trả lời, chỉ FE MOCK và tài liệu; schema/migration/PHP chưa tạo.
