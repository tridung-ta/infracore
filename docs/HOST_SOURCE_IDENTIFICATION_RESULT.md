# Đối chiếu file đại diện host — 26/10/2026

User cung cấp4 file tải về local và xác nhận **“ita-dashboard.js file này chưa có”**. Agent chỉ tính hash/size các file local user chỉ định, không mở hoặc thực thi PHP, không truy cập FTP/host/DB. Không copy nội dung file host vào Git/gói. Đối chiếu với HOST_SOURCE_FINGERPRINTS.csv bằng byte gốc, không sửa newline/encoding.

## Kết quả có bằng chứng

**4/4 file được cung cấp khớp SHA256 với checkpoint Phase8 `1691c43bd37da547bd4d4f7733f90f2b15516bdc`.** Đây là checkpoint ứng viên phù hợp cho những file đã nhận, không phải xác nhận toàn release hoặc gói đã upload. Thời điểm/kiểu truyền FTP và đường gốc host không được agent tự kiểm.

| Path tương đối dự kiến | Size byte đã đọc | SHA256 local | Kết quả |
|---|---|---|---|
|modules/admin/ita.module.php|767|e16b9054c21902522df164df1e6a4297c3f3e7d71346488312618321c371d61b|Khớp Phase8; cũng khớp các ứng viên Phase7/9/10, không nhận diện riêng một commit |
|includes/ita_mode.inc.php|2146|1c6003a3a57010cbfff22096273a6f138cbf0aa5286388a1cbf50db7c3f7d339|Khớp Phase8 trong bộ ứng viên |
|classes/services/itaapplication.class.php|12804|3603375723a74487d667a9e1554cd9e2d8d9678406c76250474f4b7dc55ee018|Khớp Phase8 trong bộ ứng viên |
|templates/admin/ita/layout.tpl.html|3496|582fb3f70e4cf9b725d3e89dd00dcc003ae899add2754c49495aeec15cbf3616|Khớp Phase8 trong bộ ứng viên |
|assets/ita/js/ita-dashboard.js|Chưa có file để đọc|Không tính|User báo chưa có; phù hợp việc file vắng ở checkpoint Phase8, chưa kiểm listing host độc lập |

File user tải nằm chung một thư mục local, nên ánh xạ path theo danh sách đã yêu cầu và tên file user cung cấp; không tự chứng minh đường đặt file thực trên vhost. Không tạo file dashboard rỗng để thay thế.

## So với các checkpoint tham chiếu

| Commit ứng viên | Số file mẫu khớp /4 |
|---|---|
|80929beff9bb — Phase7|1/4 |
|1691c43bd37d — Phase8|4/4 |
|2e95245 — Phase9|1/4 |
|0ce2166 — Phase10|1/4 |
|b4bd575 — BE13 source|0/4 |
|fdc8538 — source có migration006|0/4 |

Chỉ so bộ6 checkpoint đã chọn, không tìm mọi commit có cùng blob. Không kết luận file khác hash là lỗi/mã độc. Bằng chứng gợi ý nền Phase8 ở các file mẫu và chưa có file dashboard theo user; **không đủ nói toàn bộ Phase9–13 vắng mặt**, vì host có thể chứa file dư/trộn bản.

## Hệ quả cho bước bàn giao

- Không dùng delta S11 ba file hoặc upload riêng PHP/JS mới: file dispatcher/layout/mode host mẫu còn ở Phase8; cần kế hoạch cập nhật cộng dồn đồng bộ cùng transitive dependency/routing/template/assets mới.
- Chưa xác định đầy đủ DAO/service/auth/guard/.htaccess/vendor/font/config/marker hoặc schema host. Inventory root/modules/upload toàn bộ vẫn **CHƯA THU THẬP**; đây chỉ là đợt thu4 file đại diện.
- SQL13 đã duyệt file, chưa có xác nhận user chạy migration006. Không suy file vắng hoặc hash source thành bảng chưa/có, migration đã chạy hoặc DB đã nghiệm thu.
- Chưa tạo gói hoặc cho phép upload từ kết quả này. Khi user yêu cầu tạo gói, cần chốt source, payload cho phép, dependency/provenance, manifest/hash, loại private/MOCK/tests/tmp và kiểm ZIP theo F11.5. Backup source/DB trước triển khai do user thực hiện; rollback source giữ dữ liệu.

Không thay trạng thái DB/host chưa nghiệm thu, không merge/tag7–10/push/deploy. Kết quả ở đây là **đối chiếu byte file mẫu**, không kiểm chức năng hoặc kiểm thủ công DB trên host.
