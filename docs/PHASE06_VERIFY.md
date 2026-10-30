# Giấy phép — bằng chứng local ngày 20/10/2026

BE-GATE #06 được duyệt kèm chỉnh sửa của user. Không migration, không tạo/kết nối DB và không deploy. Không cài package mới. OpenSSL có sẵn.

## Hành vi đã triển khai

- Catalog giấy phép dùng chung theo store: tìm tên, phân trang 20 dòng, tạo/sửa/ngừng sử dụng. Ciphertext AES-256-GCM có phiên bản, nonce ngẫu nhiên 12 byte, tag 16 byte và AAD theo store; key mã hóa 32 byte từ env riêng, không fallback key mặc định. DTO loại ciphertext và chỉ trả trạng thái có key/mặt nạ.
- Cấp phát một chỗ đúng loại máy/người/bộ phận; thu hồi giữ lịch sử; khóa role/license/target khi ghi, kiểm version, existing generated unique index chặn trùng đích đang hiệu lực. Lỗi audit rollback cùng transaction ITA. Không FK/ghi dc_users MyISAM.
- Chỉ Admin IT xác nhận vượt số chỗ hoặc giảm tổng xuống dưới số đã cấp; checkbox bool + lý do bắt buộc, audit riêng license.overage. Manager bị 403 nếu vượt, không tự tăng tổng chỗ.
- Cảnh báo ngày VN: <=30 warning, <=7 hoặc quá hạn critical; dùng vượt mức khi used > total. Không hạn không cảnh báo hết hạn. SQL chỉ lấy 20 giấy phép cảnh báo và aggregate tổng riêng; hiển thị danh sách, dashboard và chuông. Giấy phép inactive chỉ còn cảnh báo nếu vượt mức.
- Xem key chỉ POST+CSRF, no-store (kể cả lỗi trước auth), Admin phải nhập lại mật khẩu. Reauth chỉ đọc native hash/MD5, không ghi last_login hoặc nâng hash. Giới hạn chung DB qua bảng ita_login_limits sẵn có: 5 lần/actor và 30 lần/IP trong 15 phút, đếm cả thành công, namespace tách login. Kiểm lại Admin/revision, khóa role và license, decrypt rồi audit/commit trước trả key. Audit lỗi không trả plaintext.
- UI không ghi key/password vào storage, URL hoặc HTML khởi tạo. Mật khẩu và key nhập mới được xóa khỏi input; key trả về chỉ gắn textContent trong bộ nhớ trang, xóa sau 10 giây, blur, ẩn trang, chuyển route hoặc Ẩn ngay. Không cam kết xóa mọi bản sao trong bộ nhớ engine/devtools.

## Kiểm chứng đã chạy

| Loại | Lệnh / kết quả | Giới hạn |
| --- | --- | --- |
| Pure crypto/rules | `php tests/ita_phase06_crypto.php`: 32 checks đạt | Round trip, sai key/AAD, ciphertext sửa, mẫu 1.000 nonce không trùng, 3 vai trò, 31/30/8/7/0/quá hạn, vượt mức, validation; không bảo đảm xác suất nonce bằng 0 tuyệt đối |
| Scripted service | `php tests/ita_phase06_unit.php`: 159 checks đạt, kiểm DAO/service, version, scope, audit rollback, mật khẩu, rate/revision, release, hết hạn/inactive/chưa hiệu lực, GET và thiếu CSRF | Adapter giả; không chứng minh persistence/locking/rollback/EXPLAIN MySQL |
| Browser | `playwright-cli -s=ita-phase06 run-code --filename tests/ita_phase06_browser.js`: 28 checks đạt | API mock; submit thật từ form, payload/read-back mock, 3 vai trò, 1366/768/390, XSS text, cảnh báo, lỗi mạng/audit, tự ẩn sau 10 giây, không JS runtime error |
| Preflight | `php scripts/check_ita_license.php`: thiếu key trả exit 1; env test ngẫu nhiên đúng 32 byte trả OK/exit 0 | Không DB/config bootstrap; chưa chạy trong PHP-FPM production |
| Hồi quy | PHP Phase3 36, Phase4 88, Phase5 173; PDF renderer 118; Smarty render/escape đạt; browser Phase5 29 checks đạt | Không DB/host |
| Review | Review Agent hai lượt không có lỗi actionable; Impeccable detector `[]`; đã xem ảnh 1366/768/390 | Không thay nghiệm thu dữ liệu thực |

Một lần browser test ban đầu chờ đoạn thông báo rỗng là visible nên timeout; đã sửa test chờ panel/dữ liệu, không đổi sản phẩm để chiều test. `git diff --check`/PHP lint/JS syntax kiểm riêng trước commit.

## Giới hạn và việc tiếp theo

Catalog chưa có ownership bộ phận: chỉ Admin sửa metadata/key; manager đọc catalog dùng chung (không định danh người nhận) và chỉ cấp/thu hồi máy/bộ phận trong scope. Native user chưa có membership đáng tin cậy nên cấp theo người chỉ Admin; không tự thêm schema hoặc suy phạm vi từ store_id. Đây là giới hạn được ghi rõ, không tuyên bố đầy đủ manager CRUD theo bộ phận.

Chưa kiểm thật HTTP/session/header no-store trên hosting, DB locking/concurrency/read-back/EXPLAIN, bucket đa phiên hoặc triển khai secret trong PHP-FPM. Phase 1–6 chưa kiểm chứng đầy đủ DB thật; **phải kiểm thử DB trước Phase 8**. Không tag phase-done. Hướng dẫn khóa và UAT: PHASE06_HOST_TEST.md. Không dùng gói PDF cũ để deploy thay đổi giấy phép mới.
