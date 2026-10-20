# BE-GATE #05-PDF — F5.4 biên bản bàn giao (đã duyệt)

User đã duyệt **“OK PDF Phase 5”** ngày 19/10/2026. Tiếp tục hoàn thiện local ngày 20/10/2026; không tạo DB, đổi php.ini toàn cục hoặc deploy.

Phạm vi: thêm renderer và endpoint PDF cho lượt bàn giao đã lưu; nội dung số biên bản, thiết bị, bên giao/nhận, ngày, tình trạng/ghi chú và khung ký tiếng Việt. Không biến xác nhận bản ký thành người nhận tự acknowledge.

Đã triển khai: dependency riêng tại `classes/ita-pdf/` (composer.json/lock/vendor) với **mpdf/mpdf 8.3.1** ghim chính xác; service PDF và GET assignment-pdf qua dispatcher, kiểm store/scope trước/sau render, audit tải biên bản; template biên bản riêng. Không cài vào hoặc thay loader legacy/PhpSpreadsheet. Composer local `--no-scripts --no-plugins`, 8 package khóa trong lock; audit không báo advisory tại thời điểm kiểm. Không bỏ qua platform requirement hoặc security advisory.

Nguồn xác minh: [mPDF package 8.3.1](https://packagist.org/packages/mpdf/mpdf) và [yêu cầu chính thức](https://mpdf.github.io/about-mpdf/requirements-v7.html). Bản 8.3.1 hỗ trợ PHP 8.3/8.5, yêu cầu mbstring và gd. Local hiện có mbstring nhưng chưa bật GD; chỉ kiểm DLL/runtime local và đề xuất cách chạy per-process trước, **không sửa php.ini/server toàn cục**. Host GD chưa xác nhận. Nếu cần thay môi trường ngoài phạm vi này sẽ báo riêng. License GPL-2.0-only giữ kèm dependency.

Migration: không DDL/seed. Không xóa assignment/audit khi rollback; rollback code tắt nút/endpoint PDF, giữ lịch sử. Nếu cache PDF, chỉ private temp riêng từng tác vụ ngoài webroot, không ghi public URL; không bắt buộc sửa handover_file.

Rủi ro/backup: dependency/font tăng dung lượng bàn giao, HTML injection/SSRF/LFI và font tiếng Việt. Chỉ render template do ứng dụng kiểm soát, escape mọi text, không cho HTML/CSS/URL/tệp do người dùng nhập, không tải ảnh/font từ mạng. Kiểm output bằng PDF parser và render nhìn trang/signature; test pure/mock không DB, không deploy. Gói sau duyệt phải kèm dependency, font/license, manifest/hash; Composer không chạy ở production.

Phạm vi đã duyệt: thêm dependency và dependency gián tiếp đã khóa, chỉ các vai trò đọc được assignment trong scope tải PDF; log đúng actor. GD được bật per-process cho lệnh kiểm thử local, không sửa php.ini. Host GD chưa xác nhận. Root composer platform nhắm PHP 8.3.33, test thực dùng PHP 8.5.10.

Gate đã được duyệt; không hỏi lại. Kết quả ở `PHASE05_PDF_VERIFY.md`. Không mở rộng sang schema mới hoặc deploy; việc push nhánh hiện tại được user cho phép riêng sau đó.
