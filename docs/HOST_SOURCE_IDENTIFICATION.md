# Xác định source host — bắt đầu26/10/2026

Đã chuẩn bị HOST_SOURCE_FINGERPRINTS.csv:30 dòng tham chiếu từ6 commit local và5 path. Đây là **SHA256 byte Git blob**, không phải kết quả đọc host hoặc checksum working tree. Mục tiêu chỉ thu hẹp các bản ứng viên, không khẳng định commit hiện chạy. Inventory host vẫn **CHƯA THU THẬP**; chưa có file host để đối chiếu, không tạo gói/deploy hoặc chạy host/DB.

## User thực hiện bằng FTP, không chạy gì trên host

1. Dùng FileZilla đúng vhost InfraCore, chọn transfer type **Binary**, chỉ tải xuống5 file dưới đây nếu có. Giữ cây path tương đối trong `tmp/host-inventory/files/` của workspace. Không upload/ghi đè gì lên host.
2. Tệp thiếu thì ghi **không thấy qua FTP**, không tạo file rỗng thay thế. Quyền không đủ/listing chưa đầy đủ ghi chưa rõ, không coi403/404 là bằng chứng không tồn tại.
3. Không mở hoặc thực thi PHP vừa tải. Không tải config, `.env`, khóa, credential, session, dump hoặc upload của người dùng. Nếu source host có secret hard-code ngoài dự kiến, không gửi nội dung vào chat; chỉ cung cấp hash/path/size đã che phần nhạy cảm.

| Path tương đối trên host | Đường local sau khi tải |
|---|---|
|classes/services/itaapplication.class.php|tmp/host-inventory/files/classes/services/itaapplication.class.php|
|includes/ita_mode.inc.php|tmp/host-inventory/files/includes/ita_mode.inc.php|
|modules/admin/ita.module.php|tmp/host-inventory/files/modules/admin/ita.module.php|
|templates/admin/ita/layout.tpl.html|tmp/host-inventory/files/templates/admin/ita/layout.tpl.html|
|assets/ita/js/ita-dashboard.js|tmp/host-inventory/files/assets/ita/js/ita-dashboard.js|

Đã kiểm `git check-ignore -v` cho listing/file đại diện: `/tmp/host-inventory/` được ignore, và `git ls-files -- tmp/host-inventory` không có file tracked. Không force-add; toàn bộ tmp vẫn loại khỏi deploy. Không lưu đường dẫn tuyệt đối/tài khoản host trong listing.

## Gửi lại thông tin

```text
Đã tải file đại diện qua FTP Binary vào tmp/host-inventory/files/.
Ngày/giờ tải: [...]
Đã tải: [liệt kê path tương đối]
Không thấy qua FTP: [...]
Chưa kiểm hoặc không đủ quyền đọc: [...]
Không upload/sửa/chạy gì trên host.
```

Nếu chỉ muốn gửi hash thay vì file, dùng Get-FileHash local cho đúng file tải xuống và gửi path tương đối/size/SHA256. Không gửi nội dung config hoặc source có secret. Đây là bước xác định source, **không phải yêu cầu thu toàn bộ inventory ngay**; listing root/modules/upload vẫn theo HOST_INVENTORY_TEMPLATE.md khi sẵn sàng.

## Cách agent đối chiếu sau khi có dữ liệu

- Chỉ đọc bytes/hash của file local đã được user cung cấp, không execute. CSV dùng các commit80929beff9bb,1691c43bd37d,2e95245,0ce2166,b4bd575 và HEAD tại lúc lập bảng là fdc8538; mỗi dòng giữ commit đủ40 ký tự, size và SHA256 thật.
- `absent_at_commit` chỉ nói file chưa có ở commit local đó; không nói host có/không. Cùng hash có thể khớp nhiều commit. Khớp5 file cũng chưa chứng minh toàn bộ runtime/dependency; host có thể trộn bản hoặc còn file cũ.
- Hash khác không tự chứng minh lỗi/mã độc. Kiểm binary transfer và đối chiếu manifest/file thật của gói ứng viên khi có; Git blob LF có thể khác file working tree/FTP text-mode CRLF. Không chuẩn hóa bytes để làm hash khớp và không suy Git blob chắc chắn là payload từng gói.
- Kết quả ban đầu ghi theo từng path: commit ứng viên khớp/không khớp/chưa có bằng chứng. Chưa đủ toàn release thì giữ “source host chưa xác định”; không dựa vài hash để đóng gói delta tùy ý.

Hiện chưa có kết quả host nên không ghi PASS hoặc thay trạng thái DB/host, không merge/tag7–10/push/deploy. Việc tạo gói vẫn cần xác nhận riêng sau đối chiếu.
