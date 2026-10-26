# SQL #13 — Hướng dẫn user áp dụng khi sẵn sàng, 26/10/2026

User xác nhận **“duyệt SQL #13 tiếp tục BUILD”**. Agent chỉ chuẩn bị migration/checklist, **chưa chạy SQL hoặc kết nối DB/host**, không tạo DB test, không gói/deploy/push/merge/tag7–10. Duyệt DDL không chứng minh đã áp dụng hoặc nghiệm thu DB. Không cần tạo DB mới; user chọn thời điểm kiểm thủ công trên DB dự án hiện có trong phạm vi cho phép.

## Đúng file và thứ tự

| Thứ tự | File | Tác động |
|---|---|---|
|1|database/host/phase13_preflight.sql|Chỉ SELECT/SHOW metadata và quyền của session công cụ SQL |
|2|database/migrations/006_ita_network_links.sql|USE dung_infracore, SET session charset/timezone, CREATE duy nhất ita_network_links theo gate đã duyệt |
|3|database/host/phase13_verify.sql|Chỉ SELECT/SHOW cấu trúc/index/FK/CHECK/generated column và đếm link |
|4|docs/PHASE13_EXPLAIN.sql|8 query EXPLAIN đọc từ DAO với tham số giả; thay store/scope/tìm kiếm đúng dữ liệu được phép sau khi schema tồn tại |

Không chạy lại migration001–005 hoặc file seed để bổ sung một bảng mới. Không upload docs/SQL/tests vào public_html. User tự mở file local trong công cụ SQL được phép, không dùng URL PHP/migration runner. Không dùng tài khoản DDL làm credential runtime của ứng dụng.

## Trước CREATE

1. Xác nhận target thực là schema **dung_infracore**, ghi môi trường, phiên bản DB/site, source/gói đang chạy và giờ kiểm. Không điền sẵn “không phải production”. Source BE13 là b4bd575 hoặc bản kế thừa đã đối chiếu; source host hiện chưa xác định. Chưa có source tương thích thì chỉ chuẩn bị schema khi đã cân nhắc phạm vi, không upload lẻ PHP/JS.
2. User backup source/config riêng và dump DB ngoài Git/webroot; ghi giờ và mã tham chiếu backup, không gửi dump/secret vào chat. Đảm bảo có kênh FTP/File Manager phục hồi source không phụ thuộc ứng dụng. DDL implicit commit, không dùng BEGIN/ROLLBACK để hứa hoàn tác CREATE.
3. Chạy preflight chỉ đọc. selected_schema phải đúng; ita_assets phải InnoDB, store_id INT có dấu, id BIGINT UNSIGNED và unique composite đầy đủ **(store_id,id)** đúng thứ tự, không index prefix. Host hồ sơ MariaDB10.6.28 chưa thay phiên bản thực. Nếu engine/kiểu/unique khác thì dừng, không ALTER bảng cũ để chữa lỗi.
4. network_table_exists phải0 và không có tên constraint mới xung đột. Nếu bảng đã có hoặc CREATE báo tồn tại: dừng, thu metadata đã che và đối chiếu, không DROP/IF NOT EXISTS/rerun mù. CREATE IF NOT EXISTS không được dùng để che schema khác.
5. SHOW GRANTS là quyền của session SQL hiện tại; chỉ user/quản trị dùng tài khoản DDL phù hợp khi áp dụng. Runtime dự kiến chỉ SELECT/INSERT/UPDATE đúng bảng; không DELETE/CREATE/ALTER/DROP. Không đưa credential DDL vào config, không cấp grant rộng để chữa lỗi. Quyền effective thực tế và isolation kết nối PHP vẫn chưa xác minh.

## CREATE và kiểm chỉ đọc sau tạo

Chạy migration006 đúng một lần khi các điều kiện trên đã được user xác nhận. Bảng mới rỗng, không có seed hoặc ghi dc_*. File khóa schema bằng USE dung_infracore, không tự tạo schema. Có thể cần metadata lock trên bảng cha; chọn thời điểm phù hợp, không giả định không có chờ khóa. Nếu có lỗi, ghi mã lỗi/tên statement và dừng, không tắt foreign_key_checks hoặc tiếp tục bằng sửa SQL ngoài gate.

Sau thành công chạy verify, đối chiếu với gate:

| Kiểm | Mong đợi | Kết quả thật |
|---|---|---|
| Engine/collation | InnoDB / utf8mb4_unicode_ci | CHƯA KIỂM |
| Cột |13 cột gồm active_slot generated STORED; id BIGINT UNSIGNED, store_id INT signed; datetime created/updated precision6 | CHƯA KIỂM |
| Unique pair | uq_ita_network_pair: store_id,source_asset_id,target_asset_id,active_slot; unique | CHƯA KIỂM |
| Index khác | PRIMARY(id), idx_ita_network_list(store_id,is_deleted,id), idx_ita_network_target(store_id,target_asset_id) | CHƯA KIỂM |
| FK |2 FK, mỗi FK2 cột cùng store tới ita_assets(store_id,id), UPDATE/DELETE RESTRICT | CHƯA KIỂM |
| CHECK |3 CHECK store>0, source<target, is_deleted IN(0,1); xem định nghĩa trong SHOW CREATE TABLE | CHƯA KIỂM |
| Generated expression | active_slot=1 khi chưa xóa; NULL khi xóa mềm | CHƯA KIỂM |
| Số dòng ban đầu |0 chỉ khi bảng vừa tạo và chưa có writer; nếu khác phải đối chiếu, không xóa | CHƯA KIỂM |

Metadata tồn tại **không chứng minh** CHECK/FK/unique đã được kiểm bằng thao tác trái phép, concurrency hoặc rollback. Không ép tổng số bảng/FK toàn schema thành số cũ: migration này thêm1 bảng và2 FK; auth và các bảng hiện có phải kiểm theo inventory thật.

## Kiểm ứng dụng sau khi source/schema được triển khai riêng

Chỉ dữ liệu giả/tài khoản test/phạm vi được phép. Không SQL ghi để tạo fixture và không tạo/sửa dc_users. Login có thể ghi last_login/password_hash; không nằm trong giới hạn tuyệt đối “không ghi dc_*” nếu chưa có ngoại lệ rõ. Nếu không có phiên test sẵn thì ghi chưa kiểm, không đổi auth.

| Kiểm thủ công bằng UI | Mong đợi | Kết quả thật |
|---|---|---|
| Admin tạo cặp phần cứng A–B, tải lại |Đúng loại/cổng/ghi chú; audit network.save; ghi tên hiển thị như văn bản | CHƯA KIỂM |
| Tạo B–A khi A–B còn hoạt động |409/trùng cặp; không tăng link hoạt động | CHƯA KIỂM |
| Sửa rồi dùng version cũ |409, không tự gửi lại; đối chiếu input trước khi lưu tiếp | CHƯA KIỂM |
| Ngừng liên kết rồi tạo lại A–B |Hồ sơ cũ is_deleted=1 được giữ, có audit network.delete, cặp mới hoạt động | CHƯA KIỂM |
| Manager scope chứa cả A và B |Xem được, không ghi; nếu một đầu ngoài scope thì không có cạnh/nút metadata ngoài scope; GET ID ngoài scope403 | CHƯA KIỂM |
| Support |403; không thấy topology hoặc request lấy graph từ UI | CHƯA KIỂM |
| Lọc và bảng nhiều trang |COUNT/bảng20 mỗi trang, graph cùng tập lọc, vượt200node/400cạnh báo lỗi không cắt ngầm | CHƯA KIỂM |
| 2D/3D và lỗi tải |Không tải MOCK ở live, không giả traffic; WebGL lỗi về2D | CHƯA KIỂM |

Không sửa tài sản thật, không thay cấu hình/vai trò vận hành để kiểm. Hai phiên đồng thời, unique/generated/CHECK/FK enforcement bằng dữ liệu trái phép, audit lỗi/rollback InnoDB, MyISAM revoke race, EXPLAIN/tải và PHP8.3/HTTPS thật **chưa kiểm**. Chỉ chạy các tình huống này khi có môi trường/phạm vi riêng phù hợp; scripted local không thay DB thật. Dashboard không503 chỉ là hành vi gián tiếp, không bằng chứng đầy đủ isolation PHP.

## Hoàn tác và gửi kết quả

Trước áp dụng: không có thay đổi DB cần hoàn tác. Sau CREATE: mặc định giữ bảng mới; lỗi upload thì phục hồi đúng source từ backup, giữ bảng/link/audit/dữ liệu mới. Không DROP/TRUNCATE/DELETE/restore dump đè. Nếu muốn gỡ bảng cần gate riêng xác định backup/dữ liệu/phụ thuộc; không có DOWN tự chạy trong bộ này.

Gửi kết quả nguyên văn kèm môi trường/phiên bản/source/giờ backup/mã mục/Đạt–Không–Chưa kiểm và metadata không nhạy cảm. Không gửi credential/key/cookie/config/dump/HAR. Agent sẽ ghi **“kiểm thủ công trên host, phạm vi hạn chế”** và phần chưa kiểm vào PROGRESS; không tự đổi nghiệm thu Phase1–10 hoặc merge/tag từ việc CREATE một bảng thành công.
