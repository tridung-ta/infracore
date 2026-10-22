# Mẫu inventory host InfraCore — CHƯA THU THẬP

User điền bằng File Manager/FTP/quyền đọc được phép; không chạy PHP/cron/fixture/SQL trên host để lập inventory. Mẫu này chưa xác nhận tệp thực tế hoặc upload. Không gửi nội dung config, credential, key, session, dump hoặc raw log. Danh sách tên tệp cũng nên được bàn giao private nếu có dữ liệu nhạy cảm.

## Môi trường và bằng chứng

| Mục | Giá trị user điền |
|---|---|
| Ngày/giờ và người kiểm | Chưa cung cấp |
| Vhost riêng InfraCore / hostname | Chưa xác minh |
| Document root thực tế (giữ private nếu cần) | Chưa cung cấp |
| Webserver / PHP-FPM phiên bản | Chưa xác minh |
| Gói/source commit/hash đang chạy; ngày upload thật | Chưa biết |
| Nguồn inventory: File Manager/FTP listing/manifest | Chưa cung cấp |
| .ita-enabled có? / .ita-legacy-enabled phải không có | Chưa kiểm |
| Rewrite/AllowOverride và cấu hình deny có hiệu lực | Chưa kiểm |

Không điền secret hoặc giá trị cookie vào bảng. Với .user.ini/config chỉ ghi tồn tại/quyền/hash nếu được phép, không nội dung. Hash tệp là tùy chọn khi có công cụ đọc an toàn được phép; không thực thi tệp PHP để lấy hash.

## Thư mục gốc — liệt kê mọi tệp và thư mục thực tế

| Path tương đối vhost | File/dir/link | Size/mtime nếu có | Owner/quyền nếu xem được | Source/gói/SHA256 nếu xác minh | Có trong inventory local? | Kết luận/việc còn mở |
|---|---|---|---|---|---|---|
| admin.php | Chưa kiểm | | | | Chưa đối chiếu | |
| project-start.php | Chưa kiểm | | | | Chưa đối chiếu | |
| .htaccess / .user.ini (chỉ metadata) | Chưa kiểm | | | | Chưa đối chiếu | |
| .ita-enabled / .ita-legacy-enabled | Chưa kiểm | | | | Chưa đối chiếu | |
| [thêm từng path khác, kể cả PHP cũ/mobile] | | | | | | |

Phải bao gồm file ẩn trong listing nếu công cụ cho phép; không thấy vì công cụ ẩn dotfiles thì ghi chưa xác minh, không ghi “không có”. Không đi theo link ra vhost/thư mục khác để đọc khi chưa được phép.

## modules/admin và handler liên quan

| Path đầy đủ tương đối | Có trên host? | So PHASE11_PHP_SURFACE.csv | Root entry/URL gọi nếu biết | Guard/deny host đã kiểm? | Gói/hash/mtime | Ghi chú |
|---|---|---|---|---|---|---|
| modules/admin/[tên].module.php | Chưa kiểm | | | Chưa kiểm | | |
| modules/admin/manage/[tên].module.php | Chưa kiểm | | | Chưa kiểm | | |
| modules/ajax/[tên].module.php | Chưa kiểm | | | Chưa kiểm | | |
| [mobile/upload handler khác nếu tồn tại] | | | | | | |

Ghi mọi file PHP, không chỉ những file có tên upload. Module cũ giữ theo yêu cầu chỉ ẩn; phát hiện file dư không có nghĩa được phép xóa/chạy. Host có mobile.php ngoài cây local phải ghi thành “chỉ có host”, không giả định đã được gate bảo vệ.

## Thư mục upload / staging

| Path tương đối hoặc định danh private | Upload sống/staging | Nằm trong public_html? | Có PHP/executable hoặc link? (chỉ tên/metadata) | Owner/ACL | HTTP deny đã kiểm? | Hành động đề xuất, chưa thực hiện |
|---|---|---|---|---|---|---|
| [thư mục upload thật user xác nhận] | | Chưa kiểm | | | Chưa kiểm | |
| [staging import, không công khai path thực] | Staging | Phải ngoài document root | Chưa kiểm | Quyền riêng PHP worker | Chưa kiểm | |

Không mở nội dung file upload thật hoặc tải staging để kiểm. PHP trong upload là rủi ro cần quản trị đối chiếu; không thử thực thi. Không xóa uploads/config/DB để khớp inventory repo.

## Đối chiếu local ↔ host

Baseline: docs/PHASE11_PHP_SURFACE.csv (2.092 path tại lần rà22/10; không phải manifest release, không bao gồm mọi file static/generated/backup). So đường dẫn tương đối POSIX, giữ tên hoa/thường thực tế; đánh dấu khác case/trùng tên, không tự đổi tên trên host.

| Path | Local CSV | Host listing | File hash so cùng source nếu có | Phân loại | Việc cần xác nhận |
|---|---|---|---|---|---|
| [path] | Có/không | Có/không/chưa thấy | Khớp/khác/chưa có | Cả hai/chỉ host/chỉ local/chưa rõ | |

Đếm tổng và ghi số path chưa phân loại; thiếu hash thì chỉ xác nhận tên/metadata, không xác nhận cùng version. Tệp thuộc docs/tests/artifacts/scripts/MOCK phải đối chiếu exclusions và deny host, không vì xuất hiện trong CSV mà đưa vào deploy. Kết quả HTTP riêng ở PHASE11_HOST_TEST.md; listing không chứng minh deny,403 không chứng minh file không tồn tại.

## Xác nhận cuối (user điền nguyên văn)

- Đã liệt kê: [root / modules/admin / manage / ajax / upload / thư mục khác].
- Chưa liệt kê: [ghi rõ].
- File chỉ có host / không rõ nguồn: [path không nhạy cảm hoặc tham chiếu private].
- Rewrite/deny và marker: [đã kiểm gì / chưa kiểm].
- Source/gói hiện chạy: [xác minh được / chưa biết].
- Ngày xác nhận: [chưa có].

Không chuyển thành PASS DB hoặc phê duyệt sửa BE/deploy. Giữ không merge/tag Phase7–10/push/deploy đến khi được chỉ đạo riêng.
