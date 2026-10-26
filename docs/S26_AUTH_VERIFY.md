# S26-02 — Một verify cho nhánh401, kiểm local26/10/2026

User duyệt BUILD S26-02 chỉ **classes/services/itaauthservice.class.php (+test)**, không đổi PmAuth. User bổ sung: **“Giữ Argon2: cho phép ngoại lệ một verify hash thật, không thêm dummy”**. Không DB/host, không gói/upload/merge/tag7–10/push/deploy. Auto-prepend và HSTS **không được duyệt**. PHP8.3/S26-03 chờ runtime user cung cấp, chưa chạy.

## Thay đổi ứng dụng

- Validation422/limiter429 đứng trước verifier. User không tồn tại, định danh trùng, disabled hoặc mapping không hiệu lực dùng một dummy bcrypt; MD5-only sai cũng một dummy bcrypt, kết quả dummy không cấp quyền. Bcrypt sai dùng đúng một hash thật. Argon2 dùng đúng một verify hash thật theo ngoại lệ đã duyệt, không cộng dummy.
- Dummy là các **hằng số đã tạo một lần** bằng password_hash(PASSWORD_BCRYPT), không phải credential thật và không tạo lại trong request. Có variant cost10/cost12; chọn variant mà password_needs_rehash(hash,PASSWORD_DEFAULT) trảfalse, cùng thuật toán/cost default hiện hành mà PmAuth dùng. Không chọn theo input user hoặc giảm cost để test nhanh. Nếu không có variant khớp cấu hình default thì503 kiểm soát, không sinh hash hoặc tự đoán cấu hình. Local8.5 chọn cost12 và password_get_info khớp hash mới theo PASSWORD_DEFAULT; không suy đã chạy PHP8.3.
- Mọi nhánh401 của service sau validation/limiter đi qua một điểm verify. Không gọi PmAuth từ ITA để tránh verify lần hai hoặc native write sớm. Kiểm lại identity/current user/quyền/revision/scope/credential sau verify và **trước mọi ghi native**; đúng password nhưng bị từ chối ở các check này vẫn cùng401/message, không upgrade/last_login.
- PmAuth trên đĩa giữ nguyên. ItaAuthService giữ chính sách native success tương ứng PmAuth: password_hash nếu MD5/needs_rehash với password<=72byte, last_login khi thành công; không sửa password MD5/status/identity. Native UPDATE thêm điều kiện status=1. Audit auth.login và clear canonical limiter giữ thứ tự sau native success. Không rollback giả dc_users MyISAM.
- Password legacy>72byte được kiểm MD5 đầy đủ, không upgrade sang bcrypt bị cắt suffix; bcrypt>72 bị từ chối với một dummy, không dựa prefix72byte. Argon2 dài được giữ xác thực thật và last_login, không tự ép bcrypt. Bcrypt2a/2b/2x hợp lệ được nhận diện dù PHP password_get_info có thể trảunknown; không fallback MD5 khi password_hash có giá trị.
- Không thêm dummy/hash ở catch hoặc guard429/422/403/503. Lỗi DB trước verifier không hash. Nếu lỗi native/audit xảy ra **sau** khi đã verify hợp lệ, lỗi vận hành vẫn đi ra503 qua boundary hiện có, không thêm verifier để bù; không thể hoàn tác công việc CPU đã xảy ra. Như contract cũ, audit lỗi sau native success có thể để native last_login/hash đã đổi trên MyISAM; đây không phải401 và không được nhận là rollback DB đã kiểm.

## Counter và kết quả thật

`php tests/ita_s26_auth.php`: **686 checks đạt**, gồm check bind/query contract, không phải686 lần thử xâm nhập. Không đo thời gian hoặc benchmark. Test dùng source ItaAuthService và PmAuth thật từ repo, instrument call site vào counter **trong bộ nhớ**; file trên đĩa không bị sửa bởi instrumentation. Spy protected verifier đối chiếu với counter gọi password_verify/password_hash thực trong service. Counter constructor/authenticate/password_verify của downstream PmAuth có positive control trước rồi reset, và bằng0 trong mọi luồng ITA đã kiểm.

| Nhánh test | Verify / hash tạo mới / native write |
|---|---|
|User vắng hoặc định danh trùng|1dummy /0 /0;401message giống nhau |
|Disabled/mapping hết hiệu lực dù password đúng|1dummy /0 /0;401 |
|MD5-only sai, malformed modern hash|1dummy /0 /0;401; không fallback MD5 khi modern khác rỗng |
|Bcrypt sai (2y,2a,2b,2x)|1real /0 /0;401 |
|Đúng MD5/bcrypt nhưng native biến mất, mapping/revision/identity/credential đổi sau verify|1đã thực hiện /0 /0;401; không verify lại |
|MD5 đúng ngắn hoặc bcrypt needs_rehash|1verify,1hash mới/native success +audit; giữ password MD5 |
|Bcrypt đúng không cần rehash|1real, không tạo hash, native last_login +audit |
|MD5 đúng dài>72byte|1dummy và MD5 full-value, không tạo bcrypt, native last_login +audit |
|MD5 dài sai suffix hoặc bcrypt>72byte|1dummy /0 /0;401 |
|Argon2 sai|1real /0 /0;401 theo ngoại lệ user |
|Argon2 đúng dài|1real, không dummy/upgrade bcrypt, last_login +audit |
|Validation422/limiter429/transport failure trước verify|0verify,0hash,0native; không đổi guard/status |
|CSRF403|Guard ứng dụng thật qua reflection trước connect;0hash mới/verify |
|Native/audit lỗi sau verify bcrypt hợp lệ|1verify đã xảy ra, không padding/tạo thêm hash; lỗi vận hành đi ra boundary503 |

Login đúng/email, canonical username/email limiter5lần rồi429, session/current thật với fake DB, ID+CSRF rotation, CSRF cũ bị403 và expiry không ghi native thêm đã kiểm. Không tạo session/tài khoản thật; thư mục session random dưới tmp chỉ chứa dữ liệu giả và được cleanup sau test.

## Hồi quy và review

| Lệnh | Kết quả | Giới hạn |
|---|---|---|
|php tests/ita_preupload_auth.php|29checks đạt|PmAuth/session/limiter thật, fake transport, không DB |
|php tests/ita_phase06_unit.php|159checks đạt|Crypto/scope/audit/reauth scripted |
|php tests/ita_phase11_forecast_export.php; php tests/ita_phase11_import_export.php|30 +54checks đạt|Actor/export guard scripted, không DB |
|php tests/ita_phase13_unit.php; php tests/ita_write_routes.php|179 +13checks đạt|Query/route contract, không DB |
|python tests/ita_phase11_privacy_http.py; ita_phase11_error_http.py; ita_phase11_guard_http.py|102 +56 +109checks đạt|HTTP PHP fixture, không Apache/host hoặc authHTTP+DB thật |
|php -l service và test mới; git diff --check|Đạt|Syntax/whitespace |
|Review độc lập|Đã sửa regression bcrypt2a/2b/2x và kiểm lại|Không review nào tự chạy DB/host |

Regression bcrypt đã tái hiện bằng test đỏ trước khi sửa: password_verify nhận hash2a/2b/2x nhưng password_get_info không nhận tên algo. Sau nhận diện đúng format/cost bcrypt, test đúng/sai/one-real-pass. Không sửa PmAuth hoặc dependency để xử lý.

## Giới hạn và hoàn tác

**Đếm verify không chứng minh hằng thời gian.** Cost/algorithm hash thật có thể khác default hoặc Argon2; thời gian SQL/lookup/limiter/OS cũng khác. Không khẳng định đã loại mọi enumeration hoặc đo timingHTTP. Native MyISAM/quyền có thể đổi sau lần recheck cuối; không hứa atomicity giữa native và ita_. Lock/rollback/shared limiter/thu hồi giữa request trên DB thật chưa kiểm.

### Hạn chế chấp nhận được — user xác nhận26/10

- Ngoại lệ user nguyên văn: **“Giữ Argon2: cho phép ngoại lệ một verify hash thật, không thêm dummy”**. Argon2 có chi phí khác bcrypt nên thời gian có thể khác; **«đếm verify không chứng minh hằng thời gian»**.
- **Policy ở hai nơi:** PmAuth và ItaAuthService cùng giữ quy tắc xác thực/native success. Đổi algorithm/cost/rehash, định danh, disabled hoặc long-password ở một nơi phải rà nơi kia và hồi quy tương ứng; không coi chúng tự đồng bộ. Đây là chi phí duy trì được chấp nhận, không phải quyền sửa PmAuth trong bước này.
- **Dummy theo PASSWORD_DEFAULT:** chỉ chọn hằng số có algorithm/cost khớp default mà PmAuth dùng. Không có variant khớp thì **503**, không sinh hash mỗi request, không hạ cost hoặc bỏ guard. Vì vậy host phải kiểm login thất bại ra401 thay vì503 để phát hiện runtime/config chưa tương thích; không suy local8.5 sang8.3.
- S26-01 MD5 native giữ để tương thích như user đã chấp nhận; không xóa/reset/NULL credential.

PHP8.3 chưa có runtime user cung cấp nên **CHƯA CHẠY**. Theo PHASE11_PHP83_RETEST_PLAN.md, sau xác minh runtime/extensions, test S26 chạy trước các bộ chức năng và ghi metadata dummy được chọn so với cost default thực. Lượt cập nhật này chỉ tài liệu, không chạy lại686checks hoặc thêm kết quả runtime mới.

Chỉ test local8.5.10; chưa8.3/FPM/HTTPS/loginHTTP+DB thật. Không chạy fixture/reset DB cũ hoặc đọc config/private hash. S26-01 MD5 native còn giữ là hạn chế user đã chấp nhận, không xóa/reset/NULL credential. Nếu đổi policy hash trong PmAuth sau này, cần rà đồng bộ service/test; không tự nới dummy cost hoặc bỏ counter.

Rollback source: backup/khôi phục đúng ItaAuthService và đồng bộ test/hồ sơ liên quan; giữ PmAuth/credential/limiter/audit/native last_login và dữ liệu mới. Không reset user, xóa MD5 hoặc restore dump. Rollback mở lại S26-02 cũ nên phải ghi rõ. Chưa đóng gói/upload/deploy; host/DB chưa nghiệm thu.
