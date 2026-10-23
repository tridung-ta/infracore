# S11-01/S11-02 — Kiểm thủ công trên host, phạm vi hạn chế

Soạn22/10/2026; **tất cả kết quả CHƯA KIỂM**. Chỉ tài liệu, không tạo gói/ZIP, không upload hoặc chạy host/DB. User kiểm thủ công trên DB dự án hiện có khi sẵn sàng: không tạo DB mới, không SQL ghi, không sửa dc_*. Không thêm BE, không merge/tag7–10/push/deploy. Kết quả checklist này **không phải nghiệm thu DB**.

## 1. Đúng file và điều kiện nền

Đối chiếu `git show --name-only` hai commit:

| Commit | PHP đã đổi, path tương đối | Đưa vào runtime delta? |
|---|---|---|
| b879282a4ed6980dfdb5b1983b679f81e75beb7d (S11-01) | classes/services/itaforecastservice.class.php | Có |
| b879282… | tests/ita_phase11_forecast_export.php | Không: test local |
| b879282… | tests/fixtures/ita_forecast_db.php | Không: fixture local |
| 561d33531ca108c3f5d231e47a0f04a3d4e604f4 (S11-02) | classes/services/itaimportcontroller.class.php | Có |
| 561d335… | classes/services/itaimportservice.class.php | Có |
| 561d335… | tests/ita_phase11_import_export.php | Không: test local |

**Runtime đúng3 file**, lấy cả ba từ source cố định561d33531ca108c3f5d231e47a0f04a3d4e604f4 (đã bao gồm S11-01), không trộn import controller mới với service cũ vì cần errorsVersion(). Không cần thay schema, JS/template/config/routing cho chính delta này.

Delta không phải bộ cài đủ ứng dụng. Nền tương thích tham chiếu là parent S11-01 **77b74f8** (cây local đã có chức năng4/9/10); chưa chứng minh host đang có nền đó. Cần đối chiếu source/hash host trước upload, không suy từ giao diện. Hồ sơ cũ ghi routing60f4f49 từng được kiểm trong luồng ticket, **không đủ** xác nhận routing ngân sách/import hay toàn bộ nền S11 hiện tại.

Dependency bắt buộc **đã có trên host, không mặc định gom thêm vào delta**:

- Dispatcher/routing: admin.php, includes/project_gate.inc.php, includes/ita_mode.inc.php (forecast-export POST allowlist), modules/admin/ita.module.php, includes/ita_bootstrap.inc.php, classes/services/itaapplication.class.php có đúng nhánh forecast-export/asset-export/import-errors và lỗi JSON.
- Auth/adapter/guard: classes/database/itadb.class.php, pmdb.class.php; classes/security/itahttperror.class.php, itainput.class.php, itasession.class.php; classes/dao/itauserobjects.class.php và itauserobjectinfo.class.php; auth/PmAuth/bootstrap transitive dependencies hiện có. current() phải kiểm native active/store +ITA enabled/revision +effective scopes, không thay bằng PM role.
- Asset/import: classes/dao/itaassetobjects.class.php, itaassetobjectinfo.class.php; classes/services/itaassetvalidation.class.php, itaexcel.class.php, itaimportstorage.class.php, itaimportrows.class.php và thư viện Excel được loader dùng. Staging private ngoài document root, quyền riêng, batch/metadata tương thích.
- Forecast: classes/dao/itaforecastobjects.class.php, itaforecastobjectinfo.class.php; classes/services/itaforecastrules.class.php, itaforecastmoney.class.php, itaforecastexport.class.php, itaforecastpdf.class.php, itaauditservice.class.php và chuỗi lifecycle/schedule/rules/bootstrap hiện có. PDF vendor classes/ita-pdf/vendor, DejaVu/font/license và Excel bundled phải có, đúng baseline/lock đã đối chiếu.
- FE đúng các action đang gọi trong ita-forecast/import hiện có; phiên CSRF hợp lệ. PHP-FPM host theo hồ sơ8.3.33 cần xác minh, GD/mbstring/zip/fileinfo/DOM/OpenSSL theo tính năng; local8.5 không chứng minh8.3. Isolation **kết nối ứng dụng** RR/SERIALIZABLE để dashboard/forecast không503. Không thêm endpoint debug hoặc đổi DB config để vượt chặn.

Nếu thiếu/khác nền hoặc dependency, ghi **chưa đủ điều kiện**, không upload mù3 file rồi dùng lỗi làm test; không tự mở rộng gói sang dependency/config/PHP khác. Phải lập danh sách thay đổi riêng để user xác nhận.

## 2. Giới hạn dữ liệu và phiên test

- Dùng tài khoản test **đã tồn tại**, bộ phận/hồ sơ giả được phép, phiên Admin/Quản lý/Hỗ trợ đã đăng nhập sẵn. Không tạo user native, không sửa mật khẩu/status dc_users. Luồng login hiện có có thể UPDATE dc_users.last_login/password_hash; vì vậy **đăng nhập mới/tái đăng nhập không nằm trong điều kiện không sửa dc_***. Nếu chưa có phiên hoặc phiên hết hạn thì dừng và ghi chưa kiểm, không chữa bằng sửa auth/SQL.
- Chỉ thao tác UI trên dữ liệu giả thuộc bộ phận test đã có; UI có thể ghi các bảng ita_ (asset giả/import staging/mapping quyền/audit), điều này khác SQL ghi trực tiếp bị cấm. Không bấm Confirm/Apply import. Không dùng dữ liệu thật, không giảm quyền Admin vận hành/Admin cuối cùng.
- Admin lọc **đúng bộ phận test**, không export toàn store. Nếu chưa có bộ phận/tài khoản/dữ liệu giả phù hợp, ghi chưa kiểm; không tự thêm bằng SQL. Không sửa giá/config/kế hoạch thật để tạo tình huống đủ dữ liệu.
- Sao lưu3 file sẽ thay và dump DB **trước upload** do user thực hiện, giữ private ngoài webroot/Git; backup khóa riêng nếu có. Không restore dump vào DB dự án để kiểm. Checklist không trao quyền agent đọc dump/credential hoặc thao tác host.

## 3. Checklist thao tác

Kết quả chọn **Đạt / Không / Chưa kiểm**, kèm giờVN và mã tham chiếu bằng chứng đã che secret. Không gửi HAR/cookie/CSRF/password/key hoặc toàn response dữ liệu thật.

| Mã | Vai trò / bước | Thao tác an toàn và tiêu chí | Kết quả | Bằng chứng/ghi chú |
|---|---|---|---|---|
| P1 | User/quản trị | Ghi source/hash3 file nền/phiên bản PHP-FPM/extensions và gói upload thực; chưa đủ nền thì dừng | Chưa kiểm | |
| P2 | User/quản trị | Backup đúng3 file +dump DB private trước upload; không overwrite config | Chưa kiểm | |
| P3 | Admin/Quản lý | Mở dashboard/forecast, đúng phạm vi; không503. Không có503 không tự chứng minh isolation, cần bằng chứng kết nối ứng dụng riêng | Chưa kiểm | |
| A1 | Admin test | Xuất tài sản theo bộ phận test có vài asset giả; XLSX mở được, đúng mã/dòng/phạm vi, không warning/HTML | Chưa kiểm | |
| M1 | Quản lý test | Cùng bộ phận test: xuất tài sản và mở XLSX; không có asset ngoài scope | Chưa kiểm | |
| A2 | Admin test | Tính ngân sách với bộ lọc bộ phận test, đọc nhãn dữ liệu thiếu/nguồn; xuất Excel/PDF nếu sẵn sàng, đối chiếu số hiển thị và tiếng Việt | Chưa kiểm | Không sửa config thật để ép đủ dữ liệu |
| M2 | Quản lý test | Xuất ngân sách bộ phận được cấp; nguồn/năm/phạm vi đúng, không dữ liệu ngoài scope | Chưa kiểm | |
| I1 | Admin/Quản lý test | Tải mẫu, nhập1–2 dòng toàn giả, cố ý mã loại không tồn tại để Preview có lỗi; upload một lần, **không Confirm/Apply** | Chưa kiểm | Chỉ staging/batch, không tạo asset qua import |
| I2 | Chính người upload | Tải file lỗi import trước hạn; mở XLSX đúng dòng giả/lỗi; không thử batch người khác hoặc file staging trực tiếp | Chưa kiểm | |
| S1 | Hỗ trợ test | Mở ứng dụng/thiết bị trong scope; các nút export tài sản/ngân sách/import không được cấp. Nút ẩn chỉ chứng minh UI | Chưa kiểm | |
| S2 | Hỗ trợ test | Nếu có cách gửi request hợp lệ trong phiên test đã được cho phép, kiểm server từ chối asset-export/import-errors và forecast-export, không file bytes. Không tái dùng CSRF/cookie của Admin; không đổi role native | Chưa kiểm | Không có request an toàn thì để chưa kiểm server, không coi S1 thay S2 |
| R1 | Admin vận hành, tài khoản khác | Dùng UI phân quyền ITA, **tắt enabled ITA của tài khoản Quản lý test** (không dc_users.status); ghi cấu hình ITA cũ để phục hồi | Chưa kiểm | Mapping/scope/audit ita_ được ghi qua UI |
| R2 | Phiên Quản lý test cũ | Sau khi R1 đã xong, thử xuất lại trong trang cũ; request từ chối/401/redirectlogin, không file. Không nhập thông tin để login lại | Chưa kiểm | Đây là thu hồi **trước request**, không giữa render |
| R3 | Admin vận hành | Phục hồi đúng mapping ITA test qua UI theo ghi nhận cũ; không đổi dc_*, không khôi phục Admin cuối. Phiên test cũ có thể invalid revision, không cố tái login trong đợt này | Chưa kiểm | |
| H1 | DevTools Network | Với export thành công: status200, MIME XLSX/PDF, attachment, no-store/nosniff theo module; mở file được. Lỗi trả JSON/401/403/409/410 theo tình huống, không attachment giả hoặc HTML200 tải thành file | Chưa kiểm | Kiểm header cuối proxy/Apache/PHP-FPM, không lưu token |
| L1 | Admin nhật ký | forecast.export thành công có audit; user.access_update khi R1/R3. Lần export sau thu hồi không có forecast.export thành công mới; dựa đúng user/thời điểm | Chưa kiểm | |
| L2 | Admin nhật ký | asset-export/import-errors **không có export audit mới theo chính sách hiện có**; không mong event mới do S11-02. Không có audit không thay bằng chứng đã chặn bytes | Chưa kiểm | |

Upload file lỗi tạo batch/staging ita_ và có thời hạn; không xóa SQL/cron thủ công để dọn sau test. Chỉ ghi nhận mã batch test để quản trị xử lý theo quy trình đã được duyệt.

## 4. Những mục KHÔNG được chứng minh trong đợt thủ công hạn chế này

| Mục | Trạng thái | Vì sao |
|---|---|---|
| Native account bị disable/mất/store đổi **giữa lúc render** | Chưa kiểm | Không sửa dc_* hoặc gây thay đổi production giữa request |
| Thu hồi ITA role/scope **giữa lúc render** | Chưa kiểm | R1/R2 chỉ kiểm request tiếp theo, không điều khiển đúng ranh giới render |
| Asset/batch/dữ liệu đổi giữa render | Chưa kiểm | Không instrumentation/fault injection/chạy song song trong scope này |
| Race MyISAM/thu hồi quyền nguyên tử | Chưa kiểm | Native không cùng bảo đảm transaction InnoDB; không suy từ scripted |
| Rollback khi audit/query lỗi giữa chừng | Chưa kiểm | Không cố gây lỗi log/DB trên host |
| Hai phiên thao tác đồng thời/race lock | Chưa kiểm | Các bước test tuần tự; hai phiên để Admin thu hồi rồi user thử lại không phải test concurrency |
| Rollback source/restore dump thực tế, tải lớn | Chưa kiểm | Chỉ chuẩn bị backup; không cố gây lỗi hoặc restore DB để test |

Local21+48 scripted đã kiểm các mô phỏng, **không dùng thay kết quả host/DB**. Đợt này không trực tiếp chứng minh ranh giới sau render của S11-01/S11-02 có hoạt động trên DB thật; chỉ kiểm baseline và thu hồi trước request trong điều kiện an toàn.

## 5. Kế hoạch delta F11.5 — KHÔNG tạo ZIP lúc này

Chỉ sau câu xác nhận riêng **«Tôi xác nhận tạo gói delta S11»** mới tạo và kiểm gói local, không suy câu đó là quyền upload/deploy. Runtime3 file lấy từ commit561d335… đầy đủ ở mục1; handoff tài liệu để ngoài webroot. Dependency bắt buộc là **điều kiện nền**, không tự thêm vendor hoặc source khác vào delta. Source host chưa tương thích thì dừng kế hoạch3 file.

Manifest dự kiến: source_commit đầy đủ, base requirement/hash host nếu xác minh, scope S11-01/02, path POSIX/size_bytes/SHA256 từng runtime file, dependency requirement và rollback; SHA256SUMS.txt sắp path UTF-8/LF, không self-hash; checksum metadata/ZIP sidecar ngoài gói. Không điền hash trước khi thực sự tạo/đọc file từ commit cố định.

Allowlist runtime đúng3 path mục1; không test/fixture, SQL, dump, config, keys, .env, marker, .htaccess/.user.ini, vendor, tmp/host-inventory, toàn tmp/tests/scripts/artifacts/.git/.playwright-cli/assets/ita/mock/preview hoặc HTML MOCK. Không copy toàn repo. Rule deny ZIP +normalize/case-insensitive/basename/paths và kiểm ZIP↔manifest/hash/CRC theo DEPLOY_LOG mụcB; từ chối entry thiếu/thừa/cấm, không chỉ tin manifest nguồn. Chưa viết/chạy validator hoặc script đóng gói mới trong nhiệm vụ tài liệu này.

User backup3 file hiện chạy +dump DB trước upload. Khi được phép upload sau: thay3 file như một bộ tương thích trong cửa sổ hạn chế thao tác; không để controller mới gọi service cũ. Rollback **chỉ khôi phục source đã backup**, giữ DB/audit/batch/snapshot/dữ liệu mới; không DROP/DELETE/restore dump đè. Cache/opcache theo cơ chế hosting được phép, không thêm endpoint reset/debug. Ghi source/hash phục hồi nếu có, không coi có hướng dẫn là đã thử rollback.

## 6. Mẫu kết quả user gửi

```text
Kiểm thủ công trên host, phạm vi hạn chế — ngày/giờ: …
Source/gói/hash đã upload: … / chưa xác định
PHP-FPM/version, môi trường: …
Mục đã kiểm: [mã +Đạt/Không và bằng chứng đã che]
Mục chưa kiểm: …
Không SQL ghi, không sửa dc_*; dùng phiên test có sẵn: [xác nhận/chưa rõ]
Phục hồi mapping ITA test sau R1: …
```

Khi user gửi kết quả, ghi **nguyên văn** vào PROGRESS với nhãn **«kiểm thủ công trên host, phạm vi hạn chế»**; không nâng thành nghiệm thu DB Phase1–10 hoặc xác nhận concurrency/rollback chưa chạy. Hiện chưa có kết quả, tất cả mục giữ Chưa kiểm.
