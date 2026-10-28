# Quyết định của dự án 5

## 28/10/2026 — Duyệt Admin tạo native user và quyền từng người

User “bát đầu” sau gate bổ sung tạo tài khoản mới +gán permissions/scope. Chọn dùng access.manage +scope.store trong capabilities mode, không tạo role4 hoặc key mới (preset47 giữ nguyên); role metadata support_technician, native type='1' staff, không native Admin. Native status0 trước map/audit, activation cuối và compensation khóa đúng ID mới nếu lỗi; giữ giới hạn MyISAM/commit không rõ, không DELETE/reset credential cũ. Hash PASSWORD_DEFAULT, password placeholder ngẫu nhiên không MD5 mật khẩu thật. Unknown mandatory column →503, không ALTER/đoán giá trị. Chỉ source/test local giả; không đọc config private/DB/host/gói/upload. Chi tiết BE_GATE_14_ACCOUNT_CREATE và PHASE14_ACCOUNT_CREATE_VERIFY.

## 28/10/2026 — Năm/tiền đúng nghĩa, nhắc cảnh báo chỉ trong phiên

User chọn nguyên văn **“Bỏ nhắc mới trong phiên, giữ cảnh báo cần xử lý”** và **“Giữ quy tắc hiện tại, làm rõ và xác nhận trước lưu”**. Không thêm unread/read receipt trên server/DB. Chọn session/tab theo account, giữ active_total riêng; view thành công mới ẩn badge/chấm nhắc, lỗi đọc không mark. Snapshot chỉ tổng/trang canonical đầu, không hứa theo dõi mọi item. Không polling hoặc tự giải quyết cảnh báo nghiệp vụ.

Giữ năm thống kê2000–2199 theo năm mở phiếu,2011 hợp lệ khi người dùng chọn; không ép mọi query thành năm hiện tại. Giữ tên chỉ gồm số và số tiền không âm theo validator;22 là22VND, không tự nhân1000. Thêm xác nhận tổng chi phí, bounds ngày giấy phép trên FE và feedback cụ thể. Trang bàn giao chờ tìm/chọn thiết bị; GET boot chung và readback sau thao tác chủ động vẫn cần thiết. Không thay PHP/SQL/quyền, không chỉnh dữ liệu host hoặc tự upload.

## 27/10/2026 — BE14 đã duyệt source; kích hoạt qua SQL gate riêng

User xác nhận nguyên văn **“Duyệt BUILD source BE14 theo gate”**. Xây guard capability trên mọi route/suboperation, dùng quyền từng người và scope.store thay suy Admin từ role. Role hiện tại chỉ compatibility khi cờ chưa bật; không thêm role4, không fallback role trong mode mới. Scope toàn store không đồng nghĩa có mọi chức năng. access.read cần scope.store; access.manage/audit.read phụ thuộc access.read: chưa hỗ trợ quản trị/audit giới hạn từng bộ phận. assignment.read và ticket.create cần asset.read cho bộ chọn tài sản. Chọn thiếu dependency bị422, không âm thầm thêm quyền.

Giao diện nhóm quyền có tìm kiếm, giữ version và chỉ gửi permission catalogue server; không gửi role trong mode mới. Server chặn cấp quyền ngoài tập người cấp và sửa tài khoản có tập quyền vượt người cấp. Lưu quyền tăng revision, cùng transaction audit; không ghi native. Native MyISAM và SQL ngoài protocol không được hứa atomicity. Cờ ITA_CAPABILITIES_ENABLED mặc định tắt; SQL007/mapping/rollout vẫn gate riêng BE_GATE_14_SQL.md, chưa chạy DB/config/host. Giữ không gói/upload/push/merge/tag7–10.

## 27/10/2026 — Hai phong cách theo nút hiệu ứng và nâng cấp đăng nhập

User muốn khi Tắt là diện mạo mới, đăng nhập được nâng cấp. Chọn Command tối/cyan cho Bật, Focus sáng/tập trung cho Tắt; dùng semantic tokens và preference ita.motion hiện có. Theme là lựa chọn trình bày, không phụ thuộc tab ẩn hoặc reduced-motion; nút vẫn đổi phong cách trong reduced/config-off nhưng animation thật luôn bị chặn. Không dùng đổi theme để reload/fetch/reset dữ liệu hoặc thay quyền. Login giữ Smarty/form/auth contract, chỉ trình bày; preview MOCK chặn submit. Không PHP/DB/config/package mới, không gói/upload/push/merge/tag7–10. Kiểm local không thay nghiệm thu host.

## 26/10/2026 — Cho phép đóng gói để user kiểm host8.3

Nguồn user: **“tôi đã cài host ở 8.3 nếu muốn test thì bạn cứ đóng gói tôi đẩy lên host rồi test”**. Cho agent chuẩn bị/kiểm gói local, upload và kiểm host do user; thay lệnh dừng đóng gói trước đó, không cho agent truy cập host/DB/push/merge/tag7–10. User gửi thư mục D:\Hoc_PHP\php\php-8.5.10-nts-Win32-vs17-x64; chạy đúng php.exe ở đó trả **PHP8.5.10**, không8.3. Không có kết quả S26/unit/export trên8.3, không dựng số giả. Gói kiểm cộng dồn phải sourcefixed/manifest/hash/ZIPreadback/exclusions/backuprollback, không tự chạy migration005/006 hoặc giả định schemahost đầy đủ. Auto_prepend/HSTS vẫn không duyệt; kết quả host tương lai ghi phạm vi hạn chế, không tự nghiệm thuDB.

## 26/10/2026 — Chuyển sang chuẩn bị kiểm DB local cô lập

Nguồn: user quyết định tự dựng Laragon (Apache +PHP8.3.x +MariaDB10.6.x, vhostHTTPS local) để kiểm đầy đủ trước đóng gói. Duyệt **soạn tài liệu và script**, chưa cho kết nối/SQL. Chỉ sau câu riêng **«Tôi xác nhận cho chạy trên DB local [tên DB]»** mới được chạy đúng schema đó; không đọc/dùng config hoặc credential host. Công cụ mới không dùng ItaDb.configuration/env host, không tự tạoDB/reset, seed native giả bằng setupaccount riêng. Runtime theo granttừngbảng, audit append-only, không DDL/DELETE/LOCK TABLES/CREATE TEMPORARY TABLES theo source đã rà. Kết quả ghi **«kiểm trên local, DB cô lập»**, tách khỏi kiểmhost; smokehost ngắn sauupload vẫn cần. Quyết định này thay giới hạn cũ “không cần DBtestlocal” về **kế hoạch**; không phải quyền kết nối hiện tại, không nghiệm thuDB, không đónggói/upload/merge/tag7–10/push/deploy.

## 26/10/2026 — Ghi nhận giới hạn sau build S26-02

Nguồn: user đã xem S26_AUTH_VERIFY, ghi nhận local686checks và chưa PHP8.3/FPM/DB. Ngoại lệ nguyên văn **“Giữ Argon2: cho phép ngoại lệ một verify hash thật, không thêm dummy”**; Argon2 có chi phí khác bcrypt nên thời gian có thể khác, **«đếm verify không chứng minh hằng thời gian»**. Chấp nhận policy xác thực được giữ ở cả PmAuth và ItaAuthService: đổi một nơi phải rà nơi kia, không tự đồng bộ code trong bước tài liệu. Dummy chọn theo PASSWORD_DEFAULT; nếu không có variant khớp thì503 fail-closed, không tự sinh/nới cost để cho login qua. Trước kiểm host user phải biết native last_login và password_hash có thể ghi, MD5 giữ nguyên. S26-03 chờ đường dẫn8.3 riêng; S26 test đầu tiên và xác nhận dummy/default cost thực, hiện CHƯA CHẠY. Không code/gói/upload/merge/tag7–10/push/deploy; S26-01 vẫn hạn chế đã chấp nhận, không xóa/reset credential.

## 26/10/2026 — Duyệt BUILD S26-02 và ngoại lệ Argon2

Nguồn: user “OK BUILD S26-02 với chỉnh sửa” kèm7điều kiện và trả lời thêm **“Giữ Argon2: cho phép ngoại lệ một verify hash thật, không thêm dummy”**. Cho sửa duy nhất ItaAuthService(+test): mọi401 sau validation/limiter một verify, dummy cho missing/MD5-only sai, real cho bcrypt/Argon; cùng message/status và không native write401. Dummy là hằng số generated một lần, chọn algo/cost khớp PmAuth PASSWORD_DEFAULT; không gọi PmAuth verify lại, không sửa PmAuth. Giữ native success upgrade/last_login/audit, limiter/session/CSRF/long password; counter kiểm service và downstream, không đo timing. Đếm verify không chứng minh hằng thời gian. S26-01 MD5 tương thích đã chấp nhận, không xóa/reset credential. Auto_prepend/HSTS KHÔNG duyệt; PHP8.3 do user cung cấp riêng cho S26-03, chưa chạy. Không DB/gói/upload/merge/tag7–10/push/deploy.

## 26/10/2026 — Chấp nhận S26-01 và chỉ lập kế hoạch S26-02/03

Nguồn: user sau khi xem báo cáo20mục. S26-01 được ghi vào Hạn chế chấp nhận được: **MD5 native còn lưu vì tương thích hệ thống cũ; không xóa/reset credential**. Không sửa PmAuth/native/hash trong bước này. S26-02 chỉ soạn BE_GATE_11 cho ItaAuthService với yêu cầu một verify/nhánh thất bại, cùng message/status và test đếm số lần gọi, không đo thời gian; chưa duyệt BUILD. Điểm tổng1verify/hashgiả so với xác thực bcrypt thật phải được chốt trước triển khai, không hứa một contract mâu thuẫn. S26-03 chỉ kế hoạch kiểm PHP8.3 khi user cung cấp runtime, biến thể test đếm deprecation thay mask bỏ; chưa sửa/chạy. Mục17–20 giữ CHƯA KIỂM host. Không gói/upload/merge/tag7–10/push/deploy.

## 26/10/2026 — Duyệt DDL mạng #13, không thực thi bởi agent

Nguồn: user nguyên văn **“duyệt SQL #13 tiếp tục BUILD”**. Mở chuẩn bị migration006 khớp DDL BE_GATE_13_SQL.md: một bảng ita_network_links, unique active pair nullable/generated, composite FK cùng store, không seed hoặc bảng cũ/dc_*. Agent không tạo/kết nối DB hoặc chạy SQL/host. User backup/xác nhận môi trường rồi tự áp dụng khi sẵn sàng theo PHASE13_SQL_HOST_GUIDE.md. File được duyệt không chứng minh đã có bảng, enforcement hoặc nghiệm thu; không mở gói/merge/tag7–10/push/deploy. Không xin lại cùng DDL đã được duyệt.

## 23/10/2026 — Source BE13 được duyệt, tách SQL gate

Nguồn: user trả lời nguyên văn “Duyệt BUILD source BE13”. Cho build liên kết giữa hai tài sản phần cứng: Admin quản lý, Manager chỉ đọc khi cả hai đầu thuộc scope, Support không xem; CSRF/version/audit. Source local không phải quyền tạo/kết nối DB hoặc deploy. DDL chính xác để review tại BE_GATE_13_SQL.md, chưa duyệt/chưa chạy; không seed/scan/SNMP/cron. Giới hạn200node/400cạnh, vượt báo lỗi; bảng20/trang, sort ID giảm. Liên kết vô hướng chuẩn hóa hai ID/cổng, soft-delete, unique active pair dự kiến trong schema mới. Sơ đồ là khai báo, trạng thái asset không được mô tả là đo kết nối thật. PHASE13_BE_VERIFY.md ghi bằng chứng/giới hạn; không merge/tag7–10/push/deploy.

## 22/10/2026 — Ghi nhận lựa chọn triển khai trong gate #10

Một transaction đọc RR/SERIALIZABLE, không tự đổi isolation; tối đa5.000 tài sản/giấy phép và500 nhóm biểu đồ, vượt trả lỗi. Nhóm KPI tính PHP trên tập đã scope, điểm/repair preaggregate dùng SQL vòng đời hiện có. PHASE10_EXPLAIN.sql ghi6 source query cho hai vai trò, chưa thực thi.

Export dùng HMAC auth_key hiện có gắn actor/revision/scope, bộ lọc, document và mốc chung, hết hạn15 phút. Dữ liệu đổi buộc preview lại, không lưu snapshot báo cáo mới. Snapshot ngân sách vẫn #09. Native active được đọc lại trước bytes; không giả locking xuyên MyISAM. Cảnh báo license Quản lý chỉ lấy license có allocation máy/bộ phận trong scope; không thay quyền catalog/key #06. Các lựa chọn này nằm trong gate đã duyệt, chưa UAT DB.

## 21/10/2026 — Duyệt backend dashboard và báo cáo

User: «tôi duyệt bạn hãy bắt đầu BUILD». Mở BE-GATE #10 theo toàn bộ phạm vi và chỉnh sửa hiện có: Hỗ trợ chỉ bảng bảo trì, giới hạn Excel tồn kho 5.000/khác 1.000/PDF 500, vòng đời ngắn 12 tháng lịch, chuông hiệu lực, mốc thời gian chung, không polling nặng và dispose biểu đồ. Agent tự kiểm local bằng pure/scripted/browser mock, không tạo/kết nối DB. Không coi phê duyệt build là nghiệm thu DB Phase 1–9 hoặc quyền merge/tag7–9/deploy.

## 20/10/2026 — Ticket và đầu vào vòng đời

Theo user duyệt #07: số ticket sửa chữa dùng cho Phase 8 đếm ticket ID theo **opened_at, type=repair**, không đếm event hoặc ngày đóng; mở lại failed vẫn cùng ID. Năm/lát cắt thời gian hiển thị theo VN, truy vấn biên UTC. Hỗ trợ/Quản lý tạo theo scope, Admin mọi bộ phận. Chỉ Admin sửa chi phí của resolved/failed, bắt buộc lý do và audit. Không merge Phase 7 trước kiểm thử DB thật Phase 1–7. Cách tính lịch ban đầu từ ngày mua cần duyệt riêng khi sửa Phase 3–5.

## 20/10/2026 — Triển khai giấy phép trong schema hiện có

User duyệt #06 kèm Admin-only overage + lý do/audit; reveal POST+CSRF/no-store/rate limit/audit fail-closed và nhập lại mật khẩu; tự ẩn 10 giây. Reauth đọc password_hash/MD5 native theo ID đang đăng nhập, không gọi luồng login có writer dc_users. Không Composer hoặc DDL.

Quyết định an toàn từ code: ita_licenses không có department_id, không thể chứng minh metadata thuộc phạm vi manager. Chỉ Admin sửa catalog/key dùng chung; manager đọc catalog che key, số chỗ toàn store không chứa định danh và cấp/thu hồi máy/bộ phận trong scope. Native user không có membership được xác minh nên chỉ Admin cấp theo user. Không suy từ store_id hoặc user_scopes (trách nhiệm quản trị) thành phòng ban làm việc. Đây là giới hạn local, không tuyên bố đã hỗ trợ manager sửa metadata theo bộ phận.

Chỉ ghi quyết định đã chốt; giả thuyết ghi rõ ở mục «Vấn đề còn mở». Không kế thừa quyết định nghiệp vụ của dự án nguồn nếu chưa xem xét.

| Ngày | Quyết định | Lý do/phạm vi | Nguồn xác nhận | Kiểm chứng/giới hạn |
| --- | --- | --- | --- | --- |
| 16/10/2026 | Dùng nền Derasoft (PHP + Smarty + Bootstrap 5 + MySQL), chỉ cải tiến/thêm/sửa, không làm từ đầu | Yêu cầu đề bài; BE là của công ty | User | Audit Phase 0 xác nhận phiên bản và cấu trúc thật |
| 16/10/2026 | Dự án có đúng 10 yêu cầu bắt buộc như trong `docs/PROJECT_BRIEF.md` | Phạm vi đề bài | User (đề bài) | Mỗi yêu cầu ánh xạ tới phase trong `PLAN_FINAL` |
| 16/10/2026 | Trạng thái tài sản: đang dùng, bảo trì, hỏng, thanh lý; trạng thái ticket: pending, resolved, failed | Đề bài | User (đề bài) | Chuyển trạng thái hợp lệ theo `PLAN_FINAL` 4.2 |
| 16/10/2026 | Hướng thiết kế Enterprise IT Command Center; nền `#081425`, nhấn `#00D4FF`, thành công `#24D6A6`, cảnh báo `#FFB547`; 70% Animated Enterprise SaaS và 30% Cyber Command Center; có một trang hạ tầng 3D chuyên biệt | User chọn hướng thiết kế | User (ảnh đề xuất chốt) | Màu nghiêm trọng, bề mặt, chữ là đề xuất bổ sung, chưa chốt |
| 16/10/2026 | Công nghệ UI: Bootstrap 5, GSAP, Apache ECharts, CSS/SVG; GoView chỉ để tham khảo, không tích hợp | User chọn để giảm rủi ro tích hợp | User | Three.js chỉ cho trang 3D (Phase 13), chốt khi làm |
| 16/10/2026 | Quy trình tự chủ: Codex không chờ duyệt plan hay quy trình ở mỗi phase; chỉ hỏi user khi đụng BE; FE cứ làm | Tránh mất thời gian chờ duyệt | User | Ranh giới FE/BE và mẫu BE-GATE ở `AGENTS.md` Mục 2 |
| 16/10/2026 | Mỗi commit Git là một chức năng | Truy vết rõ ràng | User | Mã chức năng Fx.y trong `PLAN_FINAL` |
| 16/10/2026 | Giao diện chức năng cũ chỉ bị ẩn, không xóa bất cứ thứ gì | Giữ nguyên nền cũ | User | Bật lại được bằng cờ; `git diff --name-status` không có `D` trên file cũ |
| 16/10/2026 | Quyền Git: được commit local theo chức năng và merge local `--no-ff` vào nhánh tích hợp sau mỗi phase đã verify | Suy ra từ yêu cầu quy trình tự chủ và commit theo chức năng | User (suy ra, **cần xác nhận rõ**) | Không bao gồm push, merge vào nhánh chính, đổi remote, deploy |
| 16/10/2026 | Deploy production do user thực hiện bằng FileZilla | Quy trình hiện tại của user | User | Agent chỉ chuẩn bị gói và checklist |

## Các mục đã giải quyết, chuyển khỏi danh sách còn mở

Ngày trong bảng là ngày rà/ghi hồ sơ21/10/2026; nguồn bên cạnh chỉ rõ quyết định hoặc phê duyệt gốc, không suy ra đã nghiệm thu DB.

| Mục cũ | Ngày ghi | Quyết định đã có | Nguồn / giới hạn |
|---|---|---|---|
| 1 | 21/10/2026 | Origin tridung-ta/infracore và push nhánh làm việc đợt Phase5 đã được user cho phép | Trả lời user “Liên kết origin và push nhánh đang làm”, yêu cầu push đợt đó; checkpoint bfe3539 ghi bên dưới. Không tự suy thành yêu cầu push đợt7–9 hiện tại |
| 2 | 21/10/2026 | Dọn sạch là trạng thái Git và rác sinh tự động theo danh sách trắng, không xóa chức năng cũ | AGENTS.md mục7 của user; không tự chạy dọn ở bước này |
| 3 | 21/10/2026 | Vai trò ITA và scope quản trị theo user; Admin toàn store, Quản lý/Hỗ trợ theo phạm vi server | Gate #02/#06/#07 đã duyệt; metadata/key giấy phép dùng chung chỉ Admin sửa theo giới hạn triển khai #06, không suy user_scopes thành bộ phận làm việc |
| 4 | 21/10/2026 | Dùng credential dc_users hiện có, role/scope/session ITA riêng; bảng mới ita_, migrations trong database/migrations | User duyệt #02, code/audit và quyết định16/10 bên dưới; không tạo lại user native |
| 5 | 21/10/2026 | Kiến trúc không giả transaction/FK với native MyISAM; bảng ita_ khai báo InnoDB | Audit/schema và duyệt #01/#02; xác minh DB host đầy đủ vẫn ở O1 bên dưới |
| 6 | 21/10/2026 | Audit vào ita_audit_logs theo allowlist, không lưu raw IP vào audit; limiter dùng HMAC | Gate #02 và các gate writer đã duyệt, code hiện có; không thay bằng log native |
| 7 | 21/10/2026 | Giấy phép per_device/per_user/per_department; vượt mức khi allocation hiệu lực lớn hơn tổng chỗ | User duyệt #06; chỉ Admin xác nhận vượt mức, lý do và audit |
| 8 | 21/10/2026 | Ẩn legacy qua layout riêng và guard có cờ/allowlist, không xóa source | User/AGENTS mục7, #01 và audit entrypoint |
| 9 | 21/10/2026 | Excel import16 cột, bảy cột đầu bắt buộc; tối đa5MB/5.000 dòng; xuất tài sản5.000 dòng | User duyệt #04, PHASE04_VERIFY và contract ItaExcel; giới hạn PDF/báo cáo #10 mới là dự thảo |
| 10 | 21/10/2026 | Ngưỡng giấy phép/bảo hành theo config; chu kỳ mặc định6 tháng, lịch #07-SCHEDULE; vòng đời theo #08 | Gate #03/#06/#07/#07-SCHEDULE/#08 đã duyệt, DECISIONS các mục tương ứng; giá trị host cần kiểm O1 |
| 11 | 21/10/2026 | Ba thành phần ngân sách và dự phòng10%; định mức/giá do Admin cấu hình, thiếu không đoán | User duyệt #09 kèm snapshot và xác nhận công thức21/10, phần chốt cuối file |
| 12 | 21/10/2026 | Xác nhận bàn giao trong ứng dụng, PDF có khung ký tay; Admin không ký thay người nhận | User duyệt #05 và #05-PDF; không chữ ký số |
| 13 | 21/10/2026 | Phạm vi hiện tại chỉ cảnh báo trong ứng dụng, không cron/email mới | Gate #06/#07/#09; #10 dự thảo cũng loại trừ; yêu cầu mới sau này cần gate riêng |
| 14 | 21/10/2026 | Xác minh hash native, nâng cấp dần password_hash khi hợp lệ; MD5 dài hơn72 byte hoãn bcrypt để không cắt suffix | Gate #02 và quyết định16/10; không đổi credential để xử lý lỗi ITA |
| 15 | 21/10/2026 | JS đặt cục bộ, ghim phiên bản, không CDN production | User/AGENTS mục1 và mục5.1; được duyệt dùng không đồng nghĩa mọi dependency đã tracked hoặc đã cài |
| 16 (phạm vi) | 21/10/2026 | Trang hạ tầng2D/3D ở Phase13, làm sau cùng | Quyết định thiết kế16/10 và PLAN_FINAL; nguồn topology thật vẫn mở ở O5 |

## Vấn đề còn mở

Mỗi mục ghi phần việc phụ thuộc. Không coi im lặng là phê duyệt thao tác không thể đảo ngược.

| # | Việc thật sự còn mở | Phụ thuộc / trạng thái |
|---|---|---|
| O1 | Xác nhận DB Phase1–9 và gói/commit host đang chạy | **Chưa nghiệm thu**; chờ user ghi môi trường, phiên bản, engine/CHECK, rollback/audit, hai phiên và mục chưa kiểm. Không suy từ ảnh lẻ thành PASS hoặc commit upload |
| O2 | Bàn giao/push đợt7–9 và thời điểm triển khai tiếp | Chưa có yêu cầu thực hiện đợt này; không push/deploy, không merge/tag7/8/9 |
| O3 | Duyệt BE-GATE #10 sau chỉnh tài liệu | **Chưa duyệt**, chưa BE; các điểm còn đề xuất trong gate cần xác nhận. FE/mock độc lập nhãn MOCK được phép |
| O4 | Nguồn/phiên bản upstream Excel legacy và font Inter; bản dependency phục hồi đã kiểm | PDF có lock tracked; Excel/font chưa đủ provenance phiên bản. Đề xuất trong DEPLOY_LOG, chưa dựng lại/cài dependency hoặc coi vendor đã tracked |
| O5 | Nguồn topology, dữ liệu kết nối và quyền BE Phase13 | Chưa có dữ liệu thật/duyệt BE cụ thể; không tự dò mạng hoặc kết nối thiết bị |

## Quyết định tự chọn và xác nhận trong phiên 16/10/2026

- User yêu cầu bắt đầu theo file Word, xác nhận không dùng lại danh tính Git dự án thư viện; chưa cung cấp tên/email mới. Không tạo commit dưới tên cũ.
- User cho biết đã có DB; chưa xác định môi trường. Không tự kết nối bằng cấu hình nguồn cũ hoặc xem đây là phê duyệt ghi DB.
- Dùng git -c safe.directory=D:/InfraCore riêng từng lệnh để đọc repo do owner khác; không thay global config.
- Không dọn thêm source vì Git chưa có baseline và chưa phát hiện file rác được xác minh cần xóa.
- Root AGENTS.md sao chép nguyên hướng dẫn đã hợp nhất tại docs/AGENTS.md để áp dụng cho code toàn repo. Không ghi đè file root có sẵn (root trước đó chưa tồn tại).
- Xác nhận tiếp theo 16/10/2026: user cung cấp danh tính dungtatri <dungtatri.dev@gmail.com>; đã đặt và đọc lại user.name/user.email trong Git local của repo. Không đổi global config. User xác nhận có DB trên phpMyAdmin; vị trí local/hosting, tên schema và môi trường vẫn chưa rõ.

- User xác nhận dung_infracore trên hosting dành riêng dự án. Ảnh phpMyAdmin xác nhận một phần bảng cũ dùng MyISAM và InnoDB, collation hỗn hợp. Chưa có quyền ghi BE hoặc xác nhận backup.

- Build FE Phase 1: preview server chỉ phục vụ static qua localhost, không thay router PHP/.htaccess. Bootstrap 5.3.8 và license được sao chép vào assets/ita/vendor/bootstrap/5.3.8 từ bản đã có; không tải package. Dữ liệu 18 thiết bị/6 bộ phận là MOCK. Tất cả số KPI/biểu đồ tính từ cùng JSON, không giả số liệu hosting.

- User yêu cầu gửi câu lệnh tạo bảng để tự chạy trên DB host và báo lại. Cho phép soạn SQL F1.1–F1.4, không suy thành quyền agent thực thi trên host, seed hoặc sửa PHP/routing. Host theo header dump là MariaDB 10.6.28/PHP 8.3.33. SQL không FK tới dc_users, giữ nguyên dc_, user/store phải kiểm tại PHP. Bộ SQL chỉ tạo 12 bảng rỗng.

- User xác nhận SQL tạo bảng host đã thành công. Chưa xác nhận seed, PHP/routing hoặc deploy. Kết quả host là xác nhận của user, không phải agent thực thi/quan sát trực tiếp.

- Chỉ đạo «tiếp tục BUILD» sau BE-GATE #1 được áp dụng để tiếp tục PHP/routing local trong phạm vi đã trình bày; SQL hosting user tự chạy. Store_id=1 được xác nhận từ kết quả dc_users (20 user), không mặc định lấy ID user cho seed.
- Khung PHP Phase 1 vẫn MOCK, render Smarty trước config DB cũ. Không mở login/endpoint legacy tới DB nguồn trước khi auth Phase 2 có scope đúng. Dùng marker .ita-enabled/.ita-legacy-enabled chung cho PHP và Apache. Không chỉnh config thật hay tự deploy.

- Phase 2 được user cho phép build sau rà UI; user chọn Admin IT native ID 111/store 1. Tái sử dụng credential dc_users qua PmAuth, nhưng session, role và scope ITA độc lập với PM; không cấp quyền dựa trên role PM. Ghi DB host chỉ do user thực hiện qua SQL bàn giao.
- Mật khẩu native MD5 dài hơn 72 byte: xác minh đầy đủ, hoãn upgrade bcrypt để tránh cắt suffix; bcrypt hiện có không nhận input trên 72 byte. Native MyISAM chỉ cập nhật hash/last_login khi xác thực thành công; không coi rollback mapping InnoDB là rollback credential.
- Rate limit gắn canonical user ID để username/email không tạo hai hạn mức; HMAC của IP/identity, không lưu raw IP. Có ngưỡng account và IP, các worker dùng chung auth_key ổn định. Không tự tin X-Forwarded-For.

- 19/10/2026: user duyệt build Phase 3 và yêu cầu không tạo DB test. Không dùng lại DB fixture cũ trong đợt này, không mở private config hoặc tự kết nối host. Verify tự động giới hạn pure/scripted/browser API mock/Smarty, DB integration và performance để user test host thủ công; ghi rõ chưa chạy, không báo PASS.
- Danh mục inactive chỉ bị loại khỏi tạo/chuyển loại mới; tài sản hiện có được giữ nguyên loại đó khi sửa thông tin khác. Admin có thể ngừng dùng loại mà không xóa hồ sơ. Trong Phase 3, Quản lý không thanh lý trực tiếp; luồng duyệt riêng chưa build.

- Sau đối chiếu Word: tách rõ trạng thái implementation local với nghiệm thu DB; không buộc user tạo DB hoặc deploy sớm. Ở thời điểm đối chiếu chưa mở BE Phase 4; ngày 19/10 user đã duyệt riêng bên dưới. Các lookup/history còn bounded-limit chưa coi là đã đáp ứng hoàn toàn phân trang mọi danh sách; luồng duyệt thanh lý vẫn phải theo dõi riêng, không mất khỏi phạm vi gốc.

- 19/10/2026, user “OK BE Phase 4”: mở F4.1–F4.5 theo BE_GATE_04.md, không bao gồm tạo DB/host/deploy. Không migration mới. Excel dùng thư viện bundled và loader namespace riêng ITA; mẫu 16 cột, 7 cột đầu bắt buộc, text output giữ định danh/DECIMAL và chặn formula injection. Admin/Quản lý import/export theo scope; Hỗ trợ không được phép. Staging temp private ngoài webroot, hết hạn 2 giờ, nguồn immutable và token theo phiên. Export dùng chung bộ lọc listing, tối đa 5000 và từ chối vượt giới hạn; runtime memory thấp yêu cầu chia nhỏ file. Không cron cleanup mới ngoài gate. Các quyết định triển khai này là lựa chọn an toàn trong phạm vi đã duyệt, chưa là UAT DB.

- 19/10/2026, user “OK BE Phase 5”: mở F5.1/2/3/5/6 theo BE_GATE_05, không DDL/DB test/host/deploy; PDF dependency được duyệt riêng sau đó. Support thu hồi theo Word, không tạo giao mới. Acknowledge chỉ chính to_user_id có quyền ITA và scope, không Admin ký thay. Tài sản phải in_use và chưa giao; user target active cùng store giữ nguyên bộ phận asset, department target thay department_id với cả source/target scope. Native dc_users MyISAM chỉ đọc, không giả transaction xuyên engine. Ngày quá hạn theo VN, ngày ghi sự kiện UTC; không tự tạo chu kỳ/cron.

- User “OK PDF Phase 5” ngày 19/10: cho phép mPDF8.3.1 và dependency khóa riêng classes/ita-pdf; GD chỉ bật per-process local, không php.ini toàn cục. Build/verify tiếp ngày 20/10. Biên bản ghi rõ tên/thông tin thiết bị là hồ sơ hiện tại, không giả snapshot bất biến; kiểm scope/actor/version sau render và audit trước bytes. Package vendor/font/license riêng, không commit cache/vendor/PDF giả hoặc chạy Composer trên production.
- User chọn “Liên kết origin và push nhánh đang làm”, sau đó yêu cầu push đợt hiện tại trước rồi push cải tiến sau: cho phép origin https://github.com/tridung-ta/infracore.git và push nhánh làm việc. Checkpoint bfe3539 đã push feature/ita-phase05-pdf, tag backup/pre-ita-20261019 chỉ local. Không bao gồm merge main, force push hoặc deploy. Commit F5.4 riêng theo git-feature-commit, giữ DB UAT chưa chạy rõ ràng.


- 21/10/2026, tiếp tục FE ticket theo yêu cầu BUILD: bộ chọn thiết bị dùng GET data q/page đã có, explicit search và5kết quả/trang, không tải toàn bộ danh sách hoặc thêm endpoint. Không suy quyền từ FE; server vẫn quyết định scope và guard pending khi tạo. Native input ID giữ làm đường nhập trực tiếp; chọn thiết bị có nhãn rõ, sửa ID xóa nhãn. Không mở Phase8 trước UAT DB đã thống nhất.


## 21/10/2026 — #08 duyệt kèm chỉnh sửa mới nhất

User cho tiếp tục build8 nhưng KHÔNG merge/tag7. Nguyên văn xác nhậnDB: «Phase 1–7 chưa có xác nhận kiểm thử DB chi tiết (CHECK ticket_events, rollback InnoDB, hai phiên đồng thời, phiên bản MySQL/MariaDB, môi trường chưa được cung cấp)». Chỉ thay trạng thái saukhi ghixácnhậnchitiếtnguyênvăn.

Điểm hiển thị đúng3lẻ (thay phươngánrawgầnngưỡngởphản hồitrước),rawgiữ đểso/sort/detail,meterhỗtrợ>1. Cấu hìnhvắngkeydùngplan0.5/0.3/0.2và0.8/0.6cóghichú;keysaitrả422. Đãchọncontract5keylifecycle_*ởPHASE08_VERIFY,chỉđọc,khôngtựghi. Querypreaggregate repair theoasset/storevàopened_at,tấtcảstatus;groupsmẫusốtheotậplọccùngscope. NếucầnindexmớidừngSQLgate.68pure/scripted +37browsermock đạt,DB/EXPLAINchưa chạy; khôngseed,cron,packagehoặccấu hìnhservermới.


## 21/10/2026 — Ngân sách được duyệt kèm snapshot

User xác nhận “Duyệt bổ sung và BUILD”: mỗi lần lưu draft giữ snapshot đầu vào và số tiền. Tính lại cần xác nhận và version; finalized chỉ đọc. Dự phòng cố định 10%, ba thành phần độc lập, không DDL. Giá 0 nhập rõ khác null. Thiếu lịch sử chỉ fallback theo tỷ lệ Admin đã đặt, không đoán. Cấu hình forecast_category_parameters theo category; đơn giá lưu default_replacement_cost. Snapshot giữ đầu vào thiết bị/cohort chi phí, ngưỡng, giá/định mức/nhân sự và tổng; không ghi bù hoặc tự tính lại bản cũ. Writer cần RR/SERIALIZABLE; không tự đổi isolation server. DB/EXPLAIN/host UAT chưa chạy; không merge/tag. Xem PHASE09_VERIFY.md.

User yêu cầu Ruflo xuyên suốt: duy trì tiến độ, quyết định và bài học có bằng chứng; CLI --help trả 3.59.3. Không bật hooks, daemon, swarm, provider hoặc tuyên bố tự học nền. Xem RUFLO_WORKFLOW.md.

## 21/10/2026 — Chốt công thức và giới hạn ngân sách theo user

- Gate #09 đã được user duyệt, gồm bổ sung snapshot khi lưu và dự phòng cố định 10%.
- Bảo trì = chi phí hiện tại của phiếu repair/periodic có trạng thái resolved/failed, mở trong ba năm lịch hoàn chỉnh trước năm hiện tại, theo tập thiết bị hiện tại của bộ phận/loại, chia 3. Không phải mô hình tồn kho lịch sử. Giới hạn: thiết bị mới mua kéo thấp mức bình quân theo thiết bị; thiết bị sắp thay vẫn được tính chi phí bảo trì và thay thế trong cùng dự toán. Không tự loại chi phí bảo trì của nhóm sắp thay.
- Mua thêm = ceil(nhân sự tăng × định mức thiết bị/người) × đơn giá. Không trừ tồn kho.
- Dự phòng cố định 10% tổng ba thành phần thay thế, bảo trì, mua thêm đã làm tròn; giữ từng thành phần để cộng tay đối chiếu. Dữ liệu thiếu được đánh dấu, không đoán giá trị.
- Phase 1–9 giữ trạng thái DB **chưa nghiệm thu** đến khi user cung cấp xác nhận kiểm thử DB cụ thể. Các bằng chứng local/mock và ảnh kiểm thử trước đó không thay xác nhận này. Không merge/tag Phase 7, 8, 9.
- Phase 10 chưa bắt đầu BE. Chỉ chuẩn bị BE_GATE_10.md theo plan; tài liệu gate không phải phê duyệt, không tự tạo endpoint/PHP/SQL mới. FE/mock độc lập có thể làm nếu cần, phải đánh dấu MOCK.

## 21/10/2026 — Tự chọn cho bản FE/mock dashboard được phép

Tách HTML mock khỏi layout live, chỉ static GET fixture; không tự nối API hoặc thay màn hình production khi BE10 chưa duyệt. ECharts6.1.0 ghim cục bộ theo quyền thư viện UI trong AGENTS, có nguồn/hash/LICENSE/NOTICE; không cài Composer hoặc dựng lại dependency legacy. Mọi số liệu/vai trò là minh họa, một mốc browser chung, chỉ refresh do người dùng; không polling hoặc export giả. Không coi user “tiếp tục” là phê duyệt BE10/DB hoặc merge/tag7–9.
