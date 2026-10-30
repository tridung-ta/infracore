# Kiểm thử ngân sách trên host — do user thực hiện sau

Chưa upload/deploy trong phiên này. Trước kiểm thử cần xác nhận môi trường, backup source/DB riêng và đối chiếu schema migration 001/004 hiện có. Không chạy lại CREATE. Không tạo DB test bằng agent.

1. Admin mở `admin.php?op=ita#forecast`. Hiện ba thành phần và dự phòng 10%; ban đầu thiếu đơn giá/định mức/kế hoạch phải báo chưa đủ dữ liệu.
2. Cấu hình loại thử bằng UI: giá 20.000.000, thiết bị/người 1,5; tỷ lệ bảo trì dự phòng 10%. Lưu phải có thông báo. Mở hai phiên cùng config version; phiên cũ phải 409, không ghi đè.
3. Với một bộ phận, nhập nhân sự tăng 3, lưu. Snapshot chứa toàn bộ loại trong bộ phận, kể cả khi bộ lọc hiện tại chỉ chọn một loại. Bấm Xem snapshot: nguồn phải ghi đã lưu.
4. Đổi giá/định mức, sửa chi phí ticket, rồi xem/xuất snapshot: tiền giữ nguyên. Dự báo hiện tại có thể đổi. Không tự cập nhật snapshot hoặc snapshot cho bản ghi cũ.
5. Bản nháp: sửa nhân sự nhưng chưa xác nhận tính lại thì không POST; xác nhận và lưu mới thay snapshot. Hai phiên cùng sửa: chỉ phiên version mới được lưu, phiên cũ 409. Nếu finalized đã tồn tại, kiểm chỉ đọc, server từ chối mọi sửa. Không tự chốt bằng SQL để thử trên dữ liệu thật.
6. Thiếu giá/định mức/chi phí/tỷ lệ fallback: hiện lý do, không biến thành 0. Thử giá/nhân sự/định mức được đặt rõ 0, phân biệt với thiếu. Thiết bị đạt cả hai điều kiện thay thế chỉ đếm một lần. Mở lại ticket thành pending không thuộc cohort bảo trì đã đóng.
7. Quản lý chỉ xem/lưu/xuất bộ phận trong scope; URL/POST ngoài scope trả 403. Hỗ trợ không xem API/config/kế hoạch/export, không chỉ kiểm ẩn menu. Thu hồi quyền giữa render và export phải bị từ chối.
8. Excel/PDF phải cùng nguồn, bộ lọc và tổng đang xem. Với snapshot, lọc loại chỉ lọc JSON đã lưu. Chuỗi văn bản bắt đầu =,+,-,@ vẫn là text khi mở Excel. PDF tiếng Việt đủ dấu. HTML lỗi HTTP200 không được tải thành báo cáo.
9. Mô phỏng lỗi audit/rollback và hai phiên đồng thời chỉ trong môi trường đã được xác nhận phù hợp, không phá dữ liệu production. Kiểm giao dịch không để lại plan/config dở. Transaction isolation khác REPEATABLE READ/SERIALIZABLE phải bị từ chối trước ghi.
10. Chạy EXPLAIN đọc trong PHASE09_EXPLAIN.sql, thay store/bộ phận mẫu đúng môi trường. Gửi kế hoạch thực thi trước khi quyết định index. Kiểm giới hạn dòng không bị cắt ngầm.

Ví dụ tính tay đầy đủ: 1 thiết bị thay thế × 20 triệu + bảo trì 9 triệu / 3 năm + ceil(3 × 1,5) × 20 triệu = 123 triệu; dự phòng 12,3 triệu; tổng 135,3 triệu. Cần tự bố trí dữ liệu phù hợp bằng UI trong môi trường đã xác nhận; fixture local chỉ giả lập, không seed DB host.

Rollback: khôi phục source đã backup. Giữ plans, snapshot JSON, config, audit và dữ liệu mới. Không DROP, reset hoặc restore DB đè dữ liệu phát sinh. Không ghi PASS DB đến khi user cung cấp xác nhận cụ thể.
