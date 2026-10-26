# F11.5 — Công cụ inventory và kiểm gói, chưa đóng gói ứng dụng

26/10/2026: chuẩn bị từ yêu cầu tiếp tục bàn giao sau đối chiếu4file Phase8. Sau đó user bổ sung kiểm20mục Word và yêu cầu ổn mới đóng gói/upload; **ZIP ứng dụng vẫn chưa được tạo**. PREUPLOAD_SECURITY_VERIFY.md ghi các mục mở. Các công cụ ở đây chỉ chuẩn bị inventory và kiểm ZIP khi có bước bàn giao sau, không deploy/FTP/DB hoặc thay config host.

## Công cụ

- scripts/prepare_ita_release.py yêu cầu source commit cố định và file JSON mới trong artifacts; đọc Git blob cùng dependency local, không đọc private config hoặc thực thi PHP. Chỉ ghi **REVIEW INVENTORY ONLY**, không có chức năng tạo ZIP. Từ chối thiếu nguồn/dependency, link ra ngoài, version/source-ref/dist-ref PDF khác lock, Excel/font khác baseline. Inventory giữ size/SHA256/origin/version_or_ref; thiếu upstream Excel/Inter vẫn ghi unknown/baseline.
- Selection gồm ITA PHP/bootstrap/error/controller/template, CSS/JS production, vendor UI ghim phiên bản, project-start.php, PmDb/PmAuth, font+OFL, Excel/PDF/DejaVu. Giữ prerequisite native Derasoft/Smarty/admin.php/ajax.php/config/marker/schema cần xác minh, không coi bản mẫu4file đủ chứng minh nền host hoàn chỉnh.
- Loại rõ4file dashboard/reports-preview JS/CSS; không lấy assets/ita/mock/preview, scripts/tests/tmp/artifacts/config/secret/marker. Không xóa code cũ trong workspace/host. Bỏ workbook Translations.xlsx dành cho maintainer, không phải runtime; bỏ vendor/mpdf/mpdf/tmp/.gitignore vì temp runtime đã được ứng dụng đặt riêng ngoài root.
- .htaccess lấy đúng Git blob ở **manual-review/** với hash, không nằm trong runtime tự upload. User cần backup/đối chiếu vhost trước áp dụng. Không sinh rule mới/.user.ini/HSTS/auto_prepend/marker. SQL đã duyệt và EXPLAIN chỉ handoff/sql ngoài webroot, cho qua bằng exact path+hash; không có dump. Gói tương lai phải kèm báo cáo preupload mới từ source đã chốt.

## Validator độc lập

scripts/ita_release_validation.py kiểm inventory và **đọc entry ZIP thật**, không extract. Caller phải truyền manifest đã review từ ngoài ZIP, không tin manifest ZIP tự khai. So tập file chính xác, manifest toàn bộ, SHA256SUMS LF, size/hash/CRC. Metadata không tự hash; ZIP/metadata sidecar hash phải do bước build sau ghi riêng.

Từ chối path absolute/drive/UNC/backslash/NUL/dot/trailingdot-space, duplicate khác hoa thường, prefix cấm ở runtime/handoff/nested; basename MOCK và .ita-legacy-enabled không có ngoại lệ. .htaccess/.user.ini/.ita-*/SQL/INI chỉ qua đúng path+hash đã review. PHP trong upload/uploads/staging bị chặn; không tự biết mọi alias upload trên host. Từ chối directory/special/link/reparse/encrypted/oversize entries và ZIP khác manifest. Chặn trường hợp ZipInfo tự cắt filename tại NUL bằng đối chiếu orig_filename.

Đây không phải bộ phát hiện mọi secret theo nội dung. Allowlist bytes/nguồn phải được review; không cho bản sao private đổi tên vào inventory. Không lấy exceptions/manifest từ chính ZIP để làm bằng chứng tin cậy. Không dùng validator này để tuyên bố ZIP ứng dụng đã đạt khi chưa có ZIP thực.

## Kết quả thật

| Kiểm | Kết quả |
|---|---|
|python tests/ita_release_validation_test.py|66checks đạt, ZIP synthetic chỉ trong memory; path/case/marker/preview/private/SQLexception/manifest/hash/CRC/duplicate/link/NUL/reparse |
|Python ast.parse ba file mới|Đạt syntax; không import/chạy host/DB |
|Inventory source dd03897003f7f06855f13b29e3ab25b5d2746408, trước bổ sung báo cáo security vào handoff|1415file,105032697byte:1401runtime+13handoff+1manual-review; **không ZIP** |
|JSON inventory cũ trong artifacts/ita-release-review/candidate-dd03897-after-security.json|SHA256 4ef9afc3268d51768bd8d32e87a92e78e25d384a93be877daebccef47bd92818; review tạm, không phải manifest release |
|PDF installed vs committed lock|8package version/source/dist refs khớp;697file nguồn,696file chọn; không bảo đảm upstream nội dung chỉ từ metadata, từng file có hash trong inventory |
|Excel|566file nguồn,565file chọn; source tree hash888154326da0fc145d3e6d0d6810818c63881882fa0819ad7f77f93ef2dbe797 khớp baseline |
|Inter|2file font/OFL khớp baseline; upstream ref chưa xác minh |
|Review độc lập|Không finding còn mở sau rà source/allowlist; review không tự chạy DB/host |

Lượt inventory đầu dừng đúng guard Excel: thứ tự Path Windows khác sort ordinal POSIX đã ghi trong hồ sơ, không phải byte dependency bị đổi. Đã sửa sort key và chạy lại thành công, không đổi baseline. Root review sau đó tái hiện ZIP NUL-name được ZipInfo cắt tên; đã sửa validator và thêm2checks NUL/reparse (64→66).

## Giới hạn và bước sau

Inventory dd03897 là bản review cũ trước commit kiểm security, **không dùng để tạo release hiện tại**. Sau khi các mục trong PREUPLOAD_SECURITY_VERIFY được xử lý/chốt mới chạy lại source commit cuối, review nội dung/inventory/prerequisite, tạo manifest/SHA256SUMS/sidecar và ZIP mới rồi validate ZIP thật. Hiện chưa có archive hash, chưa thử restore dependency ngoài máy dev, chưa upload hoặc xác nhận source đầy đủ host. Không chạy script đóng gói lịch sử để bỏ qua các kiểm này.

Không push/merge/tag7–10, không tạo DB test/SQL host hoặc sửa PHP ứng dụng. Việc lưu công cụ local không mở quyền triển khai.
