# F5.4 — Kết quả PDF local ngày 20/10/2026

BE-GATE #05-PDF đã được user duyệt. Đã có renderer thật, endpoint tải, tải từ lượt đang mở hoặc từng dòng lịch sử; static preview vẫn MOCK. Không tạo DB, kết nối host, sửa php.ini hoặc deploy.

| Kiểm chứng | Kết quả | Giới hạn |
| --- | --- | --- |
| `php -d extension=gd tests/ita_phase05_pdf.php` | 118 checks đạt: PDF thật, template escape, scoped roles, actor reload, đổi quyền/đổi version trong lúc render, audit và audit failure, temp cleanup | Quyền/SQL/audit dùng adapter giả; không DB integration |
| `php tests/ita_phase05_pdf.php --unavailable` | 2 checks: thiếu GD trả 503 có kiểm soát | GD chỉ tắt trong process mặc định local; không thay host |
| `tests/ita_phase05_pdf_text.py` bằng Python bundled có pypdf/pdfplumber | Hai PDF giả: 1 và 2 trang, tiếng Việt, số biên bản, seri 0 đầu, giờ VN, khung ký ở trang cuối, text nằm trong trang; không annotation/action thực thi; OpenAction chỉ là page/zoom destination `/XYZ` của mPDF | Không phải tài liệu người dùng thật |
| Poppler `pdftoppm -png` và nhìn từng trang | Đã xem PDF ngắn và cả 2 trang PDF dài; không mất dấu, chồng chữ, cắt bảng hoặc tách đôi khung ký | Chưa mở bằng Excel/Acrobat/ứng dụng host; không ký số |
| `tests/ita_phase05_pdf_browser.js` qua Playwright CLI | 18 checks đạt, transport/API mock: tải lượt hiện tại/lịch sử, ba vai trò, 403/nội dung giả/mất mạng/retry, GD thiếu, ba viewport | Binary browser được mock; PDF thật kiểm riêng bằng PHP/parser |
| Composer update/install/audit/platform | mPDF8.3.1 +7 dependency; `audit --locked --no-dev --no-plugins` không advisory; gd/mbstring/zlib/php local đạt | PHP host 8.3.33 nhắm qua platform solver, chưa chạy host |
| Hồi quy | PHP Phase3 36, Phase4 88, Phase5 173 và Smarty render đạt | Không DB |
| Builder `python tests/ita_pdf_package.py` | Git fixture tạm: ZIP inventory/hash/commit đúng, chặn source dirty và vendor lệch lock | Chưa phải chạy gói runtime thật; thực hiện sau commit để source cố định |

## Hợp đồng và dữ liệu

GET `/admin.php?op=ita&act=assignment-pdf&id=<assignment_id>` cần session ITA. Không dùng asset_id làm ID lượt giao. Store, scope tài sản hiện tại và role/revision được kiểm, kiểm lại sau render. Hồ sơ đổi version trong lúc tạo trả 409. Render/audit lỗi thì không trả PDF bytes. Mỗi lần tạo thành công ghi `assignment.pdf_download` với actor và handover_no, không log tên người nhận hoặc nội dung biên bản. Audit ghi nhận server tạo/chuẩn bị trả file, không chứng minh browser đã nhận hết file.

Biên bản lấy số/ngày/đích/người giao/xác nhận từ assignment đã lưu; tên và thông tin thiết bị là hồ sơ hiện tại (ghi rõ trong PDF), không tuyên bố snapshot bất biến lúc bàn giao. Bản ghi đã thu hồi vẫn tải từ lịch sử, có tình trạng thu hồi lấy từ audit nếu có. Không giả Admin ký thay người nhận, không ký số và không biến chữ ký trên giấy thành acknowledge điện tử.

Mẫu chỉ in text đã escape, không nhận HTML/CSS/URL/tệp từ user. mPDF có HTTP client và local asset loader từ chối mọi request, stream wrapper whitelist rỗng; font là DejaVu Sans từ package cục bộ. Temp ngẫu nhiên riêng từng lần ngoài webroot, cleanup chỉ cây của lần đó và không theo symlink. Không ghi public PDF/handover_file hoặc dọn cache/thư mục cũ.

## Git và bàn giao

Checkpoint bfe3539 đã lưu mã local Phase3–5 và đã push lên origin/feature/ita-phase05-pdf theo yêu cầu user; không phải tag nghiệm thu phase. F5.4 được commit riêng sau kiểm chứng. vendor không đưa Git; composer.json/lock/README đưa Git để tái lập. Builder `scripts/package_ita_pdf.py` chỉ đóng gói từ source committed và vendor khớp lock, có manifest/hash và đọc lại zip; không deploy, không là backup toàn app/DB. Gói delta phụ thuộc checkpoint và phải giữ config host riêng.

Host UAT còn mở: session/HTTP/download thật, scope với dữ liệu thật, audit read-back, font/bộ nhớ trên PHP8.3.33, GD/extensions, webserver headers/cache và đóng gói triển khai thực tế. Không đánh dấu toàn Phase5 hoặc 10 yêu cầu đã nghiệm thu.
