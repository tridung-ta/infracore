# Phạm vi dự án — Dự án 5

Mục để trống hoặc ghi «chưa xác định» là chưa có thông tin; Agent không được tự gọi là đã hoàn tất. Phase 0 điền các mục còn thiếu từ kết quả audit.

- **Tên dự án:** Enterprise IT Asset & Infrastructure Management System (Hệ thống Quản lý & Tối ưu Hạ tầng CNTT Doanh Nghiệp).
- **Vấn đề cần giải quyết:** quản lý tập trung tài sản CNTT của doanh nghiệp (thiết bị, giấy phép phần mềm), theo dõi bảo trì và hỗ trợ kỹ thuật, phân bổ và thu hồi, phân tích vòng đời, dự báo ngân sách thay thế và bảo trì, có phân quyền theo bộ phận phụ trách.
- **Người dùng chính:** Admin IT, Quản lý hệ thống, Nhân viên hỗ trợ kỹ thuật. Nhân viên hỗ trợ chỉ quản lý thiết bị thuộc bộ phận phụ trách. Phân biệt chi tiết Admin IT và Quản lý hệ thống: chưa xác định (mặc định ở `docs/PLAN_FINAL.md`, xác nhận ở BE-GATE #2).
- **Luồng quan trọng:** import hoặc tạo tài sản → phân bổ cho nhân viên/phòng ban kèm biên bản bàn giao → phát sinh ticket bảo trì và xử lý (pending, resolved, failed) → cảnh báo (giấy phép hết hạn/vượt mức, bảo hành, quá hạn bảo trì) → phân tích vòng đời và đề xuất thay thế → dự báo ngân sách → dashboard và báo cáo Excel/PDF.
- **Stack/kiến trúc thực tế:** PHP 8.x trên nền kiến trúc MVC tự xây của Derasoft (`classes/dao` = Model, `modules` = Controller, `templates` = View Smarty), Bootstrap 5, MySQL. Phiên bản PHP, Smarty, storage engine thực tế: chưa xác định, audit Phase 0 xác nhận. Thư viện dự kiến: PhpSpreadsheet (Excel), mPDF hoặc TCPDF (PDF), ECharts, GSAP, Three.js (chỉ trang hạ tầng 3D, Phase 13).
- **Code và dịch vụ đã có:** nền Derasoft (cấu trúc, auth, tracking data, import/export, PDF, soft delete). Chi tiết: chưa audit, xem `docs/AUDIT.md` sau Phase 0. Không có dịch vụ ngoài đã biết. Không kế thừa nghiệp vụ của các dự án khác chạy trên cùng nền.
- **Môi trường local/staging/production:** local: chưa xác định; staging: chưa xác định; production: máy chủ Derasoft, **user tự deploy bằng FileZilla**. Không dùng production để phát triển.
- **Chức năng trong phạm vi:**
  1. Đăng nhập, phân quyền 3 vai trò, middleware theo bộ phận phụ trách, log thao tác theo user.
  2. Quản lý tài sản 6 loại, thông tin chi tiết, 4 trạng thái, tìm kiếm nâng cao.
  3. Phần mềm và giấy phép: key, thời hạn, cảnh báo sắp hết hạn/vượt mức.
  4. Phiếu bảo trì và ticket, thống kê số lần bảo trì mỗi năm theo thiết bị.
  5. Vòng đời tài sản: tuổi đời, tuổi trung bình, tỷ lệ lỗi, đề xuất thay thế.
  6. Phân bổ và thu hồi, lịch sử, biên bản bàn giao, cảnh báo quá hạn bảo trì.
  7. Dự báo thay thế và ngân sách theo kế hoạch mở rộng phòng ban.
  8. Dashboard, biểu đồ, báo cáo Excel/PDF.
  9. Import Excel (kiểm tra trùng mã, validate), export báo cáo.
  10. Giao diện responsive Bootstrap 5, sidebar quản trị, truy vấn MySQL tối ưu (phân trang, chỉ mục, subquery hợp lý).
  - Mở rộng (không bắt buộc): UI Motion (Phase 12); trang hạ tầng mạng 2D rồi 3D (Phase 13).
- **Chức năng ngoài phạm vi:** machine learning; ứng dụng mobile native; quét/khám phá thiết bị mạng tự động; mua sắm/thanh toán; sửa hoặc xóa chức năng, dữ liệu, schema cũ (giao diện cũ chỉ bị ẩn). Gửi mail và cron nhắc việc: chưa quyết định, mặc định chưa làm.
- **Ràng buộc dữ liệu, bảo mật và giao diện:**
  - Dữ liệu: không `DROP TABLE`, không xóa dữ liệu, không đổi tên bảng/cột cũ; bảng mới tiền tố `ita_`, InnoDB, utf8mb4; migration đánh số có hoàn tác; backup trước migration; VND; múi giờ Asia/Ho_Chi_Minh.
  - Bảo mật: prepared statement, escape XSS, CSRF, rate limit đăng nhập, phân quyền kiểm ở server, key giấy phép mã hóa và che khi hiển thị, không commit secret hay dữ liệu thật.
  - Giao diện: Enterprise IT Command Center; nền `#081425`, nhấn `#00D4FF`, thành công `#24D6A6`, cảnh báo `#FFB547`; tỉ lệ 70% Animated Enterprise SaaS và 30% Cyber Command Center; token trong CSS variables; motion tách riêng và tôn trọng reduced-motion.
  - Quy ước code Derasoft: xem `AGENTS.md` Mục 4.
- **Điều kiện nghiệm thu:** từng phase đạt mục Nghiệm thu trong `docs/PLAN_FINAL.md` (Phần 6); cả 10 yêu cầu bắt buộc có chức năng chạy được, đã verify, có commit tương ứng; giao diện cũ ẩn được và bật lại được bằng cờ; `git diff --name-status` không có file cũ bị xóa; không PASS ghi cho phần chưa chạy.
- **Lệnh chạy/build/lint/test đã xác minh:** chưa xác minh. Dự kiến: `php -l <file>` cho lint PHP; Playwright CLI cho kiểm thử giao diện; `EXPLAIN` cho truy vấn. Lệnh chạy ứng dụng local và test hiện có: Phase 0 điền sau khi chạy thử thật.
- **Người phê duyệt triển khai và quy trình backup/rollback:** người phê duyệt: chủ dự án (user). Backup: dump DB ra ngoài repo trước mỗi migration; rollback bằng phần hoàn tác của migration cộng bản dump. Chi tiết quy trình thật: Phase 0 và Phase 11 điền vào `docs/DEPLOY_LOG.md`.

Không điền mật khẩu, token hoặc thông tin khách hàng thật vào tài liệu này.
