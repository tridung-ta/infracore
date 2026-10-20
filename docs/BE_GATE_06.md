# BE-GATE #06 — Phần mềm và giấy phép (đã duyệt kèm chỉnh sửa)

User duyệt ngày 20/10/2026: chỉ Admin xác nhận vượt mức, bắt buộc lý do/audit; xem key bằng POST+CSRF, no-store, rate limit, fail-closed audit, tự ẩn và nhập lại mật khẩu chỉ đọc. Bổ sung hướng dẫn khóa, preflight CLI và test crypto/quyền/ngày biên. Không DDL/DB thật. Các đoạn đề xuất bên dưới được áp dụng cùng các điều chỉnh này.

Phạm vi: F6.1 quản lý giấy phép; F6.2 cấp phát/thu hồi chỗ; F6.3 cảnh báo hết hạn; F6.4 cảnh báo vượt mức; F6.5 Admin IT xem key đầy đủ có audit. Tiếp nối PDF local tại 5f1e6ea, không coi host UAT Phase 5 đã hoàn tất.

Thay đổi dự kiến: DAO ItaLicenseObjects/ItaLicenseObjectInfo, service validation/cấp phát/mã hóa; dispatcher ItaApplication, bootstrap và audit allowlist; endpoint danh sách/chi tiết/tạo/sửa/ngừng sử dụng/cấp phát/thu hồi/xem key. Template và JavaScript riêng cho giấy phép. Không xóa cứng giấy phép hoặc lịch sử cấp phát.

Migration: không dự kiến DDL. Tái sử dụng ita_licenses và ita_license_allocations trong migration 003, không chạy lại CREATE. Không ghi bảng dc_. Nếu phát hiện cần thêm cột/index thì dừng và trình gate SQL riêng.

Mã hóa: AES-256-GCM qua OpenSSL có sẵn, nonce ngẫu nhiên, ciphertext có phiên bản; khóa 32 byte lấy từ cấu hình private/env riêng ngoài Git, không sinh khóa production tự động, không dùng mật khẩu đăng nhập làm khóa. Thiếu khóa thì từ chối thao tác cần mã hóa/giải mã có kiểm soát. Không ghi key thô vào log, danh sách, HTML nguồn hoặc localStorage; chỉ endpoint Admin IT trả key theo yêu cầu có CSRF, no-store và audit trước phản hồi. Không thêm Composer package.

Rủi ro và backup: mất khóa mã hóa sẽ không đọc lại key, nên cần backup khóa riêng với DB trước triển khai do user thực hiện. Khóa giấy phép khi cấp phát/thu hồi và kiểm trùng để tránh đếm sai; audit cùng transaction InnoDB cho thao tác ghi. Rollback code giữ nguyên ciphertext, allocations và audit; không DROP, reset hoặc restore đè dữ liệu. Chỉ test pure/scripted/browser mock local, không tạo/kết nối DB, không upload/deploy. DB locking, EXPLAIN, persistence và host UAT để user kiểm sau.

Giả định cần xác nhận:

1. Admin IT quản lý giấy phép và key; Quản lý hệ thống quản lý thông tin/cấp phát trong phạm vi nhưng chỉ thấy key che; Hỗ trợ không được xem key. Mọi endpoint kiểm quyền server và store; allocation theo máy/bộ phận phải kiểm scope. Cấp cho user chỉ khi xác minh được phạm vi, không suy bộ phận từ store_id.
2. Một allocation đang hiệu lực dùng một chỗ, đúng một đích theo license_type; không tự sửa tổng số chỗ để khớp đã cấp. Cho phép vượt mức có xác nhận rõ và audit, hiển thị critical theo quy tắc gốc; không âm thầm cấp vượt.
3. Hạn còn tối đa 30 ngày warning, tối đa 7 ngày hoặc đã hết critical; vượt mức khi allocated > total_seats. Ngày theo Asia/Ho_Chi_Minh. Không thêm cron/email.
4. Ngừng sử dụng thay xóa cứng; không cho cấp phát mới giấy phép inactive/hết hạn. Sửa giảm tổng chỗ có xác nhận nếu tạo vượt mức. Lịch sử giữ nguyên.

Trả lời mong muốn: **OK BE Phase 6 / OK với chỉnh sửa / Không**.

Căn cứ: AGENTS.md mục 2 yêu cầu dừng trước thay đổi PHP/auth/config/SQL ngoài phạm vi đã duyệt. Các phê duyệt #05 và #05-PDF không bao gồm #06.
