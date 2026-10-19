# BE-GATE #04 — Import/export Excel tài sản (đã duyệt)

User duyệt ngày 19/10/2026: **“OK BE Phase 4”**. Phạm vi: F4.1 tải mẫu Excel, F4.2 upload/validate, F4.3 preview/xác nhận import atomic, F4.4 Excel lỗi theo dòng, F4.5 export theo filter/scope. Backend đã triển khai local theo phạm vi này; DB integration chưa chạy. Không có migration mới.

Thay đổi dự kiến sau duyệt: PHP reader/writer PhpSpreadsheet có sẵn (kiểm loader/dependency trước, không tự thêm composer); service validate file/dòng, staging private và endpoint tải mẫu/upload/preview/confirm/error/export qua dispatcher ITA. Dùng ita_import_batches, ita_assets, ita_audit_logs hiện có; mọi writer cùng transaction InnoDB, không ghi dc_. Không tạo DB test hoặc kết nối host; kiểm local bằng file giả/pure/mock, DB integration user test sau.

Migration: dự kiến không cần DDL, không chạy lại CREATE. Nếu cần thêm bảng/cột/index hoặc dependency thì trình thay đổi cụ thể trước. Hoàn tác runtime giữ dữ liệu/log đã nhập; không DROP hoặc import dump đè dữ liệu mới.

Giả định theo Word 4.6: .xlsx tối đa 5 MB/5.000 dòng; cột bắt buộc mã, loại, tên, seri, ngày mua, bộ phận, trạng thái; map loại/bộ phận theo mã; trùng trong file/DB bị chặn, kiểm scope từng dòng, ngày/tiền/trạng thái hợp lệ. Admin/Quản lý có quyền import trong scope, Hỗ trợ không; không cho Quản lý dùng import để vượt quyền thanh lý. Chưa xác nhận thì không ghi; confirm kiểm lại dữ liệu/quyền/trùng và chống apply hai lần. Lỗi toàn lô rollback; export giới hạn/cảnh báo, chống formula injection, không leak data ngoài scope. Không tự xóa staging/dữ liệu có sẵn bằng cleanup rộng.

Rủi ro/backup: file giả mạo/zip bomb/XXE/formula, batch hết hạn, thay quyền giữa preview/confirm, import trùng đồng thời, tác dụng ngoài transaction. File private không thực thi, random ID, token/batch gắn actor/store; backup DB/source trước user áp dụng host. Không deploy, không SQL host tự động.

Phê duyệt chỉ áp dụng phạm vi trên. Không tạo DB, không kết nối host, không deploy. Xem `PHASE04_VERIFY.md` cho kết quả local và `PHASE04_HOST_TEST.md` cho phần UAT còn lại. Static preview vẫn là MOCK và khóa thao tác thật; giao diện runtime đã xác thực dùng endpoint thật.
