# Quyết định của dự án 5

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

## Vấn đề còn mở

Mỗi mục ghi phần việc phụ thuộc. Không coi im lặng là phê duyệt thao tác không thể đảo ngược.

| # | Câu hỏi | Phần việc phụ thuộc | Mặc định tạm dùng (chưa phải quyết định) |
| --- | --- | --- | --- |
| 1 | Có cho phép `git push` không, và push lên remote nào, nhánh nào? | Mọi commit, bàn giao | Không push cho đến khi user xác nhận |
| 2 | «Dọn sạch thư mục» có nghĩa là working tree sạch và bỏ file rác sinh tự động (không xóa mã nguồn chức năng cũ) hay nghĩa khác? | F0.1 | Hiểu theo nghĩa trước; chỉ xóa theo danh sách trắng |
| 3 | Phân biệt Admin IT và Quản lý hệ thống; phạm vi bộ phận phụ trách gắn với user hay vai trò | Phase 2 | Ma trận ở `PLAN_FINAL` 4.1 |
| 4 | Dùng lại bảng user/role của Derasoft hay tạo mới; đường dẫn migration; tiền tố bảng | Phase 1 | Dùng lại user/role, thêm `ita_user_scopes`; theo audit |
| 5 | Storage engine của bảng cũ (có MyISAM không) và ảnh hưởng tới transaction/FK | Phase 0, 1, 4 | Bảng `ita_` InnoDB; không giả định transaction với bảng cũ |
| 6 | Log thao tác: dùng tracking data sẵn có hay `ita_audit_logs`; có lưu IP không | Phase 2 | `ita_audit_logs`, lưu user_id, chưa lưu IP |
| 7 | Giấy phép tính chỗ theo máy, người hay phòng ban; cách tính vượt mức | Phase 6 | Cả ba kiểu; vượt mức = bản ghi chưa thu hồi > tổng chỗ |
| 8 | Mức ẩn route cũ: chỉ ẩn menu hay chuyển hướng cả URL cũ | Phase 1 | Mức 2 (chuyển hướng, có cờ) |
| 9 | Cột file Excel import, giới hạn dung lượng/số dòng | Phase 4 | 5 MB, 5.000 dòng, cột theo `PLAN_FINAL` 4.6 |
| 10 | Ngưỡng cảnh báo, tuổi thọ chuẩn, chu kỳ bảo trì theo loại | Phase 3, 7, 8 | `PLAN_FINAL` 4.3 và 4.4 |
| 11 | Công thức dự báo ngân sách và định mức thiết bị/người | Phase 9 | `PLAN_FINAL` 4.5 |
| 12 | Ký biên bản bàn giao trên hệ thống hay ký tay | Phase 5 | Xác nhận trên hệ thống và PDF có khung ký |
| 13 | Gửi mail hoặc cron nhắc việc | Phase 6, 7 | Chưa làm, chỉ cảnh báo trong ứng dụng |
| 14 | Hash mật khẩu của nền hiện tại và cách nâng cấp | Phase 2 | Nâng cấp dần sang `password_hash` khi đăng nhập |
| 15 | Thư viện JS đặt cục bộ hay CDN | Phase 1, 12 | Đặt cục bộ, ghim phiên bản |
| 16 | Có làm Phase 13 (hạ tầng mạng 2D/3D) và dữ liệu kết nối lấy từ đâu | Phase 13 | Làm sau cùng, dữ liệu mẫu |

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
