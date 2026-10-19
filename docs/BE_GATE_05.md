# BE-GATE #05 — Phân bổ và thu hồi (đã duyệt)

User duyệt ngày 19/10/2026: **“OK BE Phase 5”**. Đang triển khai local F5.1/2/3/5/6, không DB/host/deploy. F5.4 còn phụ thuộc gate thư viện PDF riêng bên dưới.

Phạm vi: F5.1 giao thiết bị cho nhân viên/bộ phận; F5.2 thu hồi; F5.3 lịch sử; F5.4 biên bản PDF; F5.5 xác nhận nhận; F5.6 hiển thị quá hạn bảo trì từ next_maintenance_due đã có. Không mở cron/gửi email hoặc tự tính chu kỳ bảo trì Phase 7.

Thay đổi dự kiến: DAO/service PHP mới cho ita_assignments, kiểm scope/ownership/trạng thái server, endpoint list/detail/assign/return/acknowledge/PDF; dispatcher/bootstrap/audit allowlist và giao diện kết nối thật. Khóa tài sản/lượt giao khi ghi, kiểm không retired/không có lượt giao đang mở, version chống ghi đè, CSRF và audit cùng transaction InnoDB. Không ghi schema/bảng dc_ hoặc kế thừa role PM.

Migration: **không dự kiến DDL**. Đã đọc migration 003: có acknowledged_by/acknowledged_at, handover_no/file, returned_at và generated active_asset_id với unique(store_id,active_asset_id). Tái sử dụng các cột này, không chạy lại CREATE. Nếu runtime cho thấy cần schema mới thì trình gate SQL bổ sung trước khi sửa. Không chạy SQL host, không tạo DB test.

PDF F5.4: inventory local mới thấy adapter writer PDF của PhpSpreadsheet, chưa có bằng chứng bộ renderer mPDF/TCPDF đầy đủ. Gate này cho phép khảo sát/chuẩn bị contract PDF nhưng **không cho phép cài composer/package mới**. Khi xác định dependency/phiên bản và cách đóng gói sẽ trình gate bổ sung cụ thể; không giả PDF bằng HTML hoặc đánh dấu F5.4 đã hoàn thành.

Rủi ro và backup: giao trùng khi hai phiên thao tác, thu hồi ngoài scope, giả danh xác nhận người nhận, PDF lộ dữ liệu hoặc lỗi font. User sao lưu trước áp dụng host; rollback code không xóa lượt giao/audit đã ghi, không DROP/restore dump đè dữ liệu. Test local pure/adapter/browser mock; transaction/concurrency/DB thực user UAT sau, không deploy tự động.

Giả định cần xác nhận:

1. Theo ma trận Word: Admin IT và Quản lý hệ thống giao/thu hồi trong phạm vi; Hỗ trợ kỹ thuật được thu hồi/nhận lại trong scope, không tạo bàn giao mới. Server kiểm quyền này riêng cho từng thao tác.
2. Xác nhận điện tử chỉ do chính tài khoản người nhận đang có quyền vào ITA thực hiện; không cho quản trị giả danh người nhận. Nhân viên không có quyền ITA và bàn giao cho bộ phận dùng biên bản ký nhận, chưa tự mở cổng đăng nhập mới. Nếu cần quản trị ghi nhận bản ký nhận thay mặt, phải có trạng thái riêng và audit đúng actor, không gọi là người nhận tự xác nhận.
3. Thiết bị ngoài retired nhưng hỏng/bảo trì sẽ bị chặn giao mới để tránh phát thiết bị không sẵn sàng. Không đổi trạng thái tài sản chỉ để vượt chặn.
4. Badge quá hạn chỉ hiển thị dữ liệu next_maintenance_due có sẵn; không tự seed ngày hoặc tạo cron. Phạm vi chuông chỉ trong store/bộ phận actor.

Gate đã được phê duyệt cho phạm vi trên theo AGENTS mục 2. Không bao gồm cài thư viện PDF, migration mới, tạo DB test hoặc deploy. Static preview vẫn MOCK; runtime local đã nối endpoint ghi theo quyền. Kiểm chứng và giới hạn ghi ở PHASE05_VERIFY.md, không coi adapter/mock là nghiệm thu DB.
