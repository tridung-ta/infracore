# Phase 4 — Checklist UAT khi user sẵn sàng triển khai

Chưa deploy. Không cần thêm bảng/cột hoặc chạy lại SQL Phase 1/2. Source local hiện chưa là gói phát hành; không upload toàn repo hoặc dùng gói Phase 3 cũ. Khi user yêu cầu bàn giao, cần chốt source, manifest/hash và dependency PhpSpreadsheet hiện có, giữ cấu hình host riêng. Không đưa dump/config/log/test/seed vào runtime.

Trước UAT: user sao lưu DB và source ngoài webroot; xác nhận đúng ứng dụng và store, PHP có zip/dom/xml/mbstring, thư mục temp private ghi được và ngoài webroot. Kiểm upload_max_filesize ≥5M, post_max_size lớn hơn 5M. Giới hạn memory có thể yêu cầu chia nhỏ file; không đổi server bằng checklist này.

| Ca kiểm thử | Thao tác | Kết quả mong đợi |
| --- | --- | --- |
| Mẫu thật | Admin mở Import/Export → Chuẩn bị file → Tải mẫu | XLSX mở được, 16 header đúng, một sheet; không dữ liệu thật hoặc công thức |
| Preview hợp lệ | Điền 2 tài sản mới mã `QA-P4-...`, loại/bộ phận active đã có trong store, đủ 7 cột đầu; upload | 2 hợp lệ, 0 lỗi; chưa xuất hiện ở danh sách tài sản. Chuyển tab giữ file |
| Confirm | Bấm Xác nhận nhập toàn bộ lô | Thông báo số tài sản, trạng thái completed, danh sách cập nhật; audit asset.create và import.complete |
| Chống ghi lại | Kiểm tra trạng thái; thử lại đúng batch/token ở phiên hiện tại | Completed, không thêm tài sản/audit lần hai |
| File lỗi | Trùng mã trong file (kể cả khác hoa/thường/dấu theo collation), mã đã có trong DB/thùng rác, mã loại/bộ phận sai, thiếu seri, ngày 30/02, giá âm, sai status | Có lỗi theo dòng, không cho confirm. Tải Excel lỗi mở được, giữ dòng nguồn và giải thích; mã có dấu phải đối chiếu collation thật |
| An toàn file | Sai đuôi/nội dung, công thức thật, nhiều sheet, file quá 5 MB/5.000 dòng | Từ chối rõ ràng, không tạo tài sản; không tự điều chỉnh file |
| Quản lý | Import/export bộ phận được cấp; thử bộ phận khác; thử retired có lý do | Đúng scope được phép; ngoài scope và thanh lý bị chặn; gọi URL trực tiếp không vượt quyền |
| Hỗ trợ | Thử URL template/upload/confirm/export trực tiếp | 403; không chỉ ẩn nút |
| Actor/batch | Dùng ID batch của tài khoản khác; bỏ/sửa CSRF/token; đổi quyền sau upload | 404/403/409 thích hợp; không có tài sản mới |
| Hết hạn | Giữ batch quá 2 giờ rồi kiểm tra/xác nhận | Báo hết hạn; yêu cầu upload lại, chưa ghi tài sản |
| Revalidate | Sau preview hợp lệ, tài khoản khác tạo một mã trùng hoặc ngưng danh mục; confirm lô cũ | 409, không nhập một phần; upload lại sẽ thấy lỗi |
| Hai phiên | Hai tài khoản cùng preview mã mới giống nhau, confirm gần đồng thời | Chỉ một lô có thể ghi; lô còn lại lỗi và không có dòng thừa; batch/audit nhất quán |
| Mất mạng | Sau bấm confirm, ngắt mạng ngắn rồi bật lại → Kiểm tra trạng thái lô | Không tự ghi lại; completed thì không upload lại. Nếu chưa biết kết quả, đối chiếu batch/danh sách trước khi làm tiếp |
| Export | Chọn q/loại/bộ phận/năm/bảo hành ở danh sách, chọn trạng thái trong tab xuất | File khớp filter và scope, không giới hạn ở trang hiện tại; hơn 5000 thì yêu cầu thu hẹp, không xuất thiếu âm thầm |
| Injection | Tên tài sản bắt đầu bằng `=`, `+`, `-`, `@`, seri có số 0 đầu | File output giữ text/định danh; Excel không thực thi công thức |
| Quy mô | File 500 dòng, sau đó tăng nếu host đủ memory | Ghi thời gian/memory thực, không tuyên bố đạt nếu chưa chạy. Không thử tải lớn khi host đang phục vụ người dùng |

Kiểm rollback do lỗi giữa lô chỉ làm ở môi trường/đợt bảo trì được user cho phép, không cố gây lỗi hoặc đổi schema trên production đang chạy. Adapter local chỉ chứng minh code gọi rollback, không chứng minh storage engine thực.

Gửi kết quả theo ca: PASS/FAIL, thông báo, số dòng trước/sau, batch ID; không gửi mật khẩu, cookie, token, private config hoặc dump. Tài sản QA dùng thao tác xóa mềm trong UI nếu đã được cho phép; không chạy DELETE/DROP hoặc xóa audit/batch để làm sạch.
