# Tiến độ dự án 5

## 23/10/2026 — Điều hướng bảng nhóm vòng đời, FE độc lập

Tiếp tục BUILD phần FE được phép, không mở BE mới. Loại thiết bị/Bộ phận20 nhóm/trang, nhớ trang riêng, reset khi response mới; paging dùng cache không fetch/không đổi KPI/mẫu số hoặc trang thiết bị.30 browser mới +63 vòng đời hồi quy/Smarty/syntax/diff đạt, detector0 finding và review độc lập không defect. PHASE11_LIFECYCLE_GROUPS_VERIFY.md. Đây là client pagination, không phải SQL pagination: server vẫn aggregate/trả toàn bộ nhóm; BE nhóm và DB/host chưa nghiệm thu. Không PHP/SQL/DDL/config/gói/merge/tag7–10/push/deploy.

| Mã | Chức năng | Vùng | Trạng thái | Commit | Verify | Ghi chú |
|---|---|---|---|---|---|---|
| F11.3 bổ sung | Điều hướng nhóm vòng đời | FE | [x] local | Commit chức năng chứa dòng này | 30 browser mới +63 hồi quy/Smarty/syntax | Backend groups chưa SQL pagination, layout PHP thật chưa kiểm |

## 23/10/2026 — Quản lý loại Admin phân trang và tìm kiếm, build local

User «Duyệt BUILD danh sách Quản lý loại». GET categories paged=1 kiểm Admin ở server, COUNT và20 rows/page, tìm literal mã/tên, sort name,id. FE giữ bản sửa/version qua paging, reset ID/version explicit sau save, không tự retry POST và chặn response cũ khi đóng/mở.69 scripted +24 browser mới1366/768/390 +30 browser hồi quy;39 asset/13 routes/174 history/190 statistics,Smarty/lint/diff đạt, detector0 finding, review độc lập không finding. PHASE11_CATEGORY_PAGINATION_VERIFY.md. Dropdown vẫn mảng/guard500/inactive; DB/EXPLAIN/PHP8.3/host chưa kiểm. Không DDL/index/gói/merge/tag7–10/push/deploy.

| Mã | Chức năng | Vùng | Trạng thái | Commit | Verify | Ghi chú |
|---|---|---|---|---|---|---|
| F11.1 bổ sung theo gate | Danh sách Quản lý loại Admin | FE/BE | [x] local | Commit chức năng chứa dòng này | 69 scripted +24 browser mới, hồi quy đạt | Lookup/dropdown chưa phân trang, DB/host chưa nghiệm thu |

## 23/10/2026 — Phân trang lịch sử tài sản, build local

User «Duyệt BUILD phân trang lịch sử tài sản». GET asset trả20 thao tác/trang cùng COUNT, sort id DESC; quyền asset kiểm trước history. Tab Lịch sử có Trước/Sau, tổng/page/busy/lỗi, sequence/generation ngăn phản hồi hồ sơ cũ.174 query-contract +43 browser mới ba vai trò1366/768/390 +30 browser hồi quy +39 asset/237 ticket/13 routes/Smarty/lint đạt; review độc lập không finding. PHASE11_ASSET_HISTORY_VERIFY.md ghi lệnh và giới hạn; không DB/EXPLAIN/PHP8.3/host, không DDL/index/transaction/gói/merge/tag7–10/push/deploy.

| Mã | Chức năng | Vùng | Trạng thái | Commit | Verify | Ghi chú |
|---|---|---|---|---|---|---|
| F11.1 bổ sung theo gate | Phân trang lịch sử tài sản | FE/BE | [x] local | Commit chức năng chứa dòng này | 174 scripted +43 browser mới, hồi quy đạt | DB/host chưa nghiệm thu |

## 23/10/2026 — Phân trang thống kê ticket được duyệt và build local

User «Duyệt BUILD phân trang thống kê ticket». COUNT nhóm/tổng toàn tập cùng scope và GROUP BY, 20 nhóm/trang, thứ tự ổn định, page validate/clamp; FE giữ bộ lọc đã áp dụng và kết quả trước khi tải lỗi, cost chuỗi.190 query-contract +237 scripted ticket +43 browser mới ba vai trò/bounds1366/768/390 +64 browser hồi quy +13 routes/Smarty/lint/diff đạt; detector template trả0 finding, review độc lập không finding. PHASE11_TICKET_STATISTICS_VERIFY.md ghi lệnh/giới hạn. Chưa DB/EXPLAIN/PHP8.3/host; không thêm index/DDL/transaction, không gói/merge/tag7–10/push/deploy.

| Mã | Chức năng | Vùng | Trạng thái | Commit | Verify | Ghi chú |
|---|---|---|---|---|---|---|
| F11.1 bổ sung theo gate | Phân trang thống kê ticket | FE/BE | [x] local | Commit chức năng chứa dòng này | 190 scripted +43 browser mới; hồi quy237/64/13 | F11.1 index/tải và DB vẫn chưa nghiệm thu |

## 23/10/2026 — BUILD(g) giới hạn privacy headers

User duyệt(g) app: Referrer-Policy strict-origin-when-cross-origin và Permissions-Policy tắt camera/microphone/geolocation sau rà source ITA.102 HTTP privacy +56 error +109 guard/lint/review đạt; PHASE11_PRIVACY_HEADERS_VERIFY.md. Không HSTS, auto_prepend/.user.ini/config host hoặc DB/gói/merge/tag7–10/push/deploy. Không suy header PHP local thay policy cuối sau proxy trên host.

## 23/10/2026 — BUILD(f) xử lý lỗi trong ứng dụng

User duyệt «Duyệt BUILD (f) trong ứng dụng». Đã có HTML Smarty lỗi đúng status, JSON API/export +request_id, log structured không request/exception secrets, bootstrap500/render fallback và không append response đã gửi; login form giữ nguyên.56 HTTP/Smarty +41 browser1366/768/390 đạt; hồi quy109 guard/75 deny/13 routes/30+54 export/134 forecast/Smarty đạt, lint/review đạt. PHASE11_ERROR_HTTP_VERIFY.md ghi giới hạn. F11.4 build app local xong, host/PHP-FPM/Apache/log config vẫn chưa kiểm; không PHP-FPM/.user.ini/auto_prepend/HSTS/ErrorDocument/gói/DB/merge/tag7–10/push/deploy.

## 23/10/2026 — Tiếp tục kiểm bảo mật và BUILD(c)/(d)

(d) đã build local: .htaccess deny tests/artifacts/scripts/MOCK trước static, ita_mode chặn tương ứng và path mơ hồ, bỏ mock khỏi allowlist.75 pure +109 PHP HTTP fixture +13 route đạt, lint/review độc lập đạt; PHASE11_GUARD_VERIFY.md. Không Apache local/host/DB, không cài runtime; không tuyên bố toàn legacy an toàn. Không .user.ini/auto_prepend/header/page lỗi hoặc gói/deploy. Delta S11 export3 file trước không gồm4 file guard/rewrite mới.

(c) đã build local: project-start qua gate trước output, hằng nội bộ chỉ gate đặt cho worker/landing tránh lặp.38 HTTP PHP fixture checks đạt (không router, không DB, marker bản copy), lint/review đạt; PHASE11_GUARD_VERIFY.md. Apache/host/PHP8.3 chưa chạy; không auto_prepend/.user.ini hoặc deploy.

User duyệt «Duyệt BUILD (c) và (d)», đồng thời yêu cầu kiểm kỹ bảo mật. Mở project-start guard và deny tests/artifacts/scripts/MOCK; không auto_prepend/.user.ini/header/error-page/DB/deploy. Trước khi sửa guard, bổ sung test S11 export:30 forecast +54 controller scripted đạt, thêm Manager scope/ngoài bộ phận và staging mất/revision đổi. Không đổi production export; HTTP/DB thật chưa kiểm.

## 23/10/2026 — Checkpoint GitHub theo yêu cầu user

User yêu cầu push các commit lên GitHub trước khi tiếp tục, chỉnh sửa sau lưu commit mới. Lưu riêng PHASE11_S11_HOST_TEST.md đã soạn từ22/10: đúng3 file runtime S11, dependency/nền cần đối chiếu, kiểm thủ công host phạm vi hạn chế và kế hoạch delta chưa tạo. Tài liệu lưu ý login mới có writer dc_users nên đợt không sửa dc_* chỉ dùng phiên test có sẵn; không suy thu hồi trước request là test giữa render. Verify đối chiếu git show hai commit/source và diff; review độc lập tài liệu chưa hoàn tất do agent không khả dụng phiên trước. Host/DB chưa chạy, không ZIP/upload/merge/tag; được push nhánh làm việc theo chỉ đạo mới, không stage file nền/vendor/local ngoài phạm vi.

## 22/10/2026 — Duyệt BUILD giới hạn export S11-01/S11-02

S11-02 build local: import controller kiểm actor/native/role/revision/effective scopes sau render, asset-export refetch tập dòng hiện tại và chặn thay đổi409; import-errors fingerprint owner-bound batch/source trước/sau, expiry410. Giữ GET/không thêm audit, không đổi template trống/report-export. Verify48 scripted controller +88 import/XLSX +13 routes +21 S11-01 +134 forecast đạt, lint/review đạt; PHASE11_EXPORT_VERIFY.md. HTTP/PHP8.3/DB/host chưa chạy. Không config/rewrite/auto_prepend/gói/merge/tag7–10/push/deploy.

User «Duyệt BUILD S11-01 và S11-02 trước». S11-01 native recheck sau render đã build local:21 scripted export +134 hồi quy ngân sách đạt, lint/review đạt; PHASE11_EXPORT_VERIFY.md. Không DB/host, không đổi config/headers/auto_prepend hoặc mở các mục khác gate11. Không merge/tag7–10/push/deploy; DB chưa nghiệm thu. Các ghi chú gate11 hoàn toàn chưa duyệt ở lịch sử dưới không thay phê duyệt giới hạn này.

## Chỉ đạo hiện hành22/10/2026 — Không tạo DB test

User nguyên văn: «à phần test DB thì không cần thiết tôi sẽ test kiểm thử thủ công trên host sau không tự í tạo DB test ở local bạn chỉ cần test trong phạm vi tôi cho phép là được rồi».

Agent không tự tạo/kết nối DB test hoặc chạy DB/host. Local chỉ kiểm trong phạm vi đã duyệt (pure/scripted/static/browser API mock/render không DB); không bắt user chọn DB test để tiếp tục phần local được phép. Các kế hoạch DB test đã soạn là lịch sử/không phải yêu cầu thực hiện hiện hành; kiểm DB thật do user thực hiện thủ công trên host khi sẵn sàng. DB/host vẫn chưa nghiệm thu đến khi có kết quả cụ thể, không chuyển mock thành PASS DB. Không tự merge/tag7–10/push/deploy; BE_GATE_11 vẫn nháp/chưa duyệt, không suy thay đổi cách test thành quyền build BE.

## 22/10/2026 — Phase11 FE/tài liệu, BE chưa duyệt

DEPLOY_LOG mụcC lưu nguyên văn phản hồi checksum local của user với các placeholder [phiên bản]/[số file]/[có/chưa]/[khớp/khác/chưa làm] còn chưa điền. Chờ số liệu/xác nhận cụ thể, không ghi read-back khớp hoặc restore đã kiểm; agent không chạy checksum/kho ngoài/gói/host/DB. Không thay trạng thái inventory CHƯA THU THẬP hoặc nghiệm thu DB.

F11.5 bổ sung deny ZIP marker legacy/preview basename mọi path, config/marker chỉ allowlist duyệt, PHP trong upload/staging và test cases chưa viết/chạy. Rà source ITA+PmDb/PmAuth chỉ đọc không thấy LOCK TABLES/CREATE TEMPORARY TABLE/DDL; chưa đề xuất thêm hai quyền, lượt DB test “app không phát sinh DDL” cần log phù hợp, chưa chạy. Soạn DEPENDENCY_CHECKSUM_WINDOWS mẫu PowerShell cho user tự tạo checksum3 bộ ngoài máy dev; chưa chạy mẫu/tạo file checksum/backup/gói. Chỉ sửa tài liệu, inventory CHƯA THU THẬP; không BE/host/DB/merge/tag7–10/push/deploy.

F11.5 tài liệu22/10: DEPLOY_LOG tái cấu trúc4 phần (trạng thái/checklist/dependency/nhật ký ngày), kiểm bằng so source Git: giữ nguyên24 mục cũ sau hạ heading, không mất/sửa body lịch sử; mục không ngày để cuối, không tự gán ngày. Bổ sung đặc tả validator ZIP +test matrix chưa viết/chạy; backup dependency ngoài máy dev +restore chưa thực hiện; hash file FTP đại diện chưa thu thập; kế hoạch runtime DB test/SHOW GRANTS chưa chạy. Chỉ tài liệu, không script/gói/host/DB/BE; inventory CHƯA THU THẬP, không merge/tag7–10/push/deploy.

F11.6 bổ sung cách đọc phản hồi lưu/tải lại thất bại/kết quả chưa rõ, nguồn hiện tại/snapshot/mặc định/thiếu dữ liệu và vòng thao tác ba vai trò. Đối chiếu với luồng FE/service hiện có; không mô tả chốt kế hoạch/mua sắm hoặc BE11 chưa build là đã có. Không dùng ảnh mới, không yêu cầu UAT host lúc này; DB/host chưa nghiệm thu.

Rà chỉ đọc3 script package_ita_phase03/pdf/host: selection hiện không gom tmp/ root; DEPLOY_LOG ghi loại toàn tmp/host-inventory khỏi mọi payload/manifest deploy, kiểm ZIP thật khi được giao đóng gói sau. Chưa chạy script/tạo gói hoặc sửa script. BE_GATE_11 thêm bảng điều kiện duyệt theo từng mục, chưa build. Inventory vẫn **CHƯA THU THẬP**, không đối chiếu host, không merge/tag7–10/push/deploy.

Chuẩn bị nơi lưu inventory private: thêm ignore `/tmp/host-inventory/`, hướng dẫn listing.txt không commit/deploy; chưa tạo/đọc listing. User xác nhận **CHƯA THU THẬP**, chưa điền “Xác nhận cuối”, không đối chiếu hoặc suy nội dung host. Verify check-ignore và ls-files cho path dự kiến, không host/DB.

Theo hướng dẫn user, HOST_INVENTORY_TEMPLATE bổ sung cách gửi listing theo nhóm thư mục tương đối thay nhập từng dòng; size/mtime tùy có, hash không bắt buộc, Codex đối chiếu khi nhận listing. Không mở/xóa file lạ hoặc tự đổi marker legacy; chưa thu thập host, chưa deploy, kiểm DB thật Phase1–10 vẫn còn mở. Chỉ sửa tài liệu.

Bổ sung tài liệu theo user: BE_GATE_11 vẫn **nháp/chưa build**, tách(a)–(g) S11-01/S11-02, project-start guard có chống lặp, deny nhạy cảm, auto_prepend rủi ro cao/.user.ini cache, HTTP/log và header/HSTS có điều kiện; mỗi mục có thay đổi/rủi ro/host test/rollback. HOST_INVENTORY_TEMPLATE chưa có dữ liệu host; checklist thêm GET/HEAD/POST rỗng scripts/license/database/project-start, không directory listing. DEPLOY_LOG đề xuất vhost riêng và runtime DB không DDL/DELETE; source ITA+PmAuth không thấy bảng nào cần DELETE, chưa kiểm grants host. Không sửa PHP/.htaccess/.user.ini/DB, không tạo gói/merge/tag7–10/push/deploy; host chưa chạy.

F11.2 **rà soát source xong, kiểm thử trên host chưa chạy**. Bổ sung inventory2.092 PHP local/7 root, ops admin/ajax/mobile không có file riêng, upload/module cũ và guard có điều kiện tại PHASE11_LEGACY_SURFACE/CSV. Không kết luận legacy an toàn: rewrite/marker/host chưa xác minh, MOCK JSON allowlist, tests/artifacts chưa deny prefix, project-start không có gate riêng. Gate11 thêm bản sửa/test S11-01 cụ thể và rà6 đường export (asset-export/import-errors còn thiếu post-render recheck; mẫu trống rủi ro thấp). **Chưa sửa code**. PHASE11_HOST_TEST/DEPLOY_LOG có checklist cookie/header cuối/deny đường nhạy cảm/legacy/staging private; tất cả host chưa chạy. Không merge/tag7–10/push/deploy.

F11.6: USER_GUIDE bổ sung lộ trình riêng Admin/Quản lý/Hỗ trợ, phân biệt tạo/gán/xử lý phiếu, giấy phép/key, snapshot và báo cáo. Không dùng ảnh; quy định ảnh MOCK phải giữ badge/chú thích nếu bổ sung sau. Chỉ mô tả chức năng local hiện có, DB/host chưa nghiệm thu; cần kiểm layout PHP thật bằng tài khoản thật sau deploy. Verify đối chiếu source/ma trận server trong PHASE11_SECURITY_REVIEW, không UAT host.

F11.5 **chuẩn bị**: DEPLOY_LOG bổ sung allowlist/exclusions runtime (toàn MOCK/tests/tmp/artifacts/preview), contract manifest/path/size/SHA256/provenance và rollback source giữ dữ liệu mới. Chưa tạo gói/manifest file inventory thực tế hoặc hash ZIP #11; chưa upload/rollback host. Không dùng hash baseline dependency thay hash release.

F11.2: đã rà bảo mật **chỉ đọc source**, báo cáo PHASE11_SECURITY_REVIEW.md gồm HTML/JSON/PDF/Excel,15 action POST/CSRF, SQL động/prepared, upload, route×3 vai trò (cả GET lẻ), cookie/header/log hiện có. Finding S11-01: forecast-export thiếu recheck native active sau render; đưa BE_GATE_11 **nháp**, chưa sửa PHP. Không phải chứng nhận an toàn toàn legacy hoặc PASS DB/host. Runtime header/cookie/thu hồi quyền đồng thời chưa chạy.

F11.3/F11.4 đã kiểm local: 58 responsive +14 admin/login trình bày +61 trạng thái lỗi đạt tại1366/768/390; Smarty/syntax và hồi quy browser3–10 đạt bằng static/API mock. Chi tiết PHASE11_FE_VERIFY.md. Không sửa PHP/DB. F11.3 sửa nhãn cuộn ngân sách bị lỗi ký tự; F11.4 giữ HTTP status khi phản hồi khôngJSON, không tự gửi lại POST. Test5/6 đã cập nhật theo vùng cảnh báo Phase10, giữ kiểm tra quyền/CSRF/key. Review Agent rà cuối không còn finding trong phạm vi FE.

| Mã | Chức năng | Vùng | Trạng thái | Commit | Verify | Ghi chú |
|---|---|---|---|---|---|---|
| F11.3 | Responsive cuối và nhãn cuộn ngân sách | FE | [x] local | 424dfb3 | 58+14 browser static/API mock | Không xác thực login thật; không DB |
| F11.4 | Trạng thái lỗi tải trang chuẩn | FE/BE | [~] một phần | 3b3403c (FE) | 61 browser mock; Smarty/syntax | HTTP404/500, log và display_errors thuộc BE_GATE_11 nháp, chưa build |
| F11.6 | Hướng dẫn sử dụng | Docs | [x] tài liệu | d1201d3 | Đối chiếu contract/source | Host chưa nghiệm thu |

PHP local8.5.10; host8.3.33; chưa chạy8.3. F11.1 kiểm tải chưa chạy; index/cookie/headers/phân trang BE chỉ bản nháp BE_GATE_11.md, chưa duyệt/chưa build. Không merge/tag Phase7–10, không push/deploy. Phase11 chưa hoàn tất toàn bộ BE/hiệu năng/host.

Đính chính phạm vi22/10 theo user: F11.3 xong **FE local trên trang tĩnh MOCK**, chưa đo layout PHP thật; cần chạy lại host sau deploy bằng tài khoản thật3 vai trò. Chạy lại ita_phase04_browser.js: **30 check đạt**; ita_phase05_browser.js: **29 check đạt**, API mock. F11.4 **một phần** như bảng, không coi61 check FE là hoàn tất trang lỗi HTTP/log/display_errors. Đối chiếu diff test5/6 tại PHASE11_TEST_DIFF_REVIEW.md: không xóa/nới assertion quyền/CSRF/version/reauth/tự ẩn key/audit lỗi; đã thay kiểm tra nút cảnh báo cũ bằng nội dung bảng mới.

F11.3 lưu riêng cùng bộ đo responsive và generator login MOCK; file HTML sinh không stage hoặc đóng gói. Commit bảo trì test cảnh báo3513f60 chỉ thay fixture/selector theo contract Phase10; không thêm lại nút điều hướng cũ. Hồ sơ host e716784, rà phân trang/gate nháp e273fa3, runtime/số test9fccd97.

F11.6: USER_GUIDE.md đã soạn theo template/contract hiện có, có phân biệt MOCK/local với host chưa nghiệm thu, quyền3 vai trò, snapshot và giới hạn từng trang. Verify đọc source/nhãn form và diff; chưa kiểm người dùng trên host. Không thêm chức năng BE hoặc tuyên bố tất cả danh sách đã phân trang.

Rà phân trang chỉ đọc đã xong tại PHASE11_PAGINATION_AUDIT.md, có Review Agent đối chiếu source/UI. Phân biệt SQL COUNT/LIMIT/OFFSET, array pagination sau fetch, fail-overflow và topN; ưu tiên thống kê ticket/lịch sử asset. BE_GATE_11.md **nháp, chưa duyệt/chưa build** cho index, cookie/security headers và phân trang bổ sung. Không sửa PHP/DB hoặc dùng báo cáo thay PASS DB.

| Mã | Chức năng | Vùng | Trạng thái | Commit | Verify | Ghi chú |
|---|---|---|---|---|---|---|
| F11.5 (chuẩn bị) | Hồ sơ host/isolation và loại MOCK khỏi gói | Docs | [~] | Commit tài liệu kèm dòng này | Đọc source PmDb/gate10, đối chiếu host log và diff; không DB | Chưa tạo gói/deploy; isolation phải kiểm trên kết nối ứng dụng |
| F11.1 (kế hoạch) | Kiểm tải5.000 asset/PDF500 trên DB test | Docs | [~] | Commit tài liệu kèm dòng này | Soạn PHASE11_LOAD_TEST_PLAN.md, chưa chạy | Chưa có DB test được xác nhận; không seed/instrument/index |

User ghi nhận Phase10 build local, DB/host **chưa nghiệm thu**; **không merge/tag Phase7–10**, không push/deploy. Chỉ FE và tài liệu #11 được làm; index/EXPLAIN, cookie/session, security headers, SQL và phân trang backend chỉ soạn gate nháp, chưa build.

Đã chạy lại `php tests/ita_phase08_unit.php`: **72 pure/scripted check đạt**, sửa PHASE08_VERIFY.md khớp. Runtime kiểm bằng `php -v`: **PHP CLI local8.5.10**. Host theo thông tin đã cung cấp: **8.3.33**; **chưa chạy test trên8.3**. PATH và thư mục runtime D:/Hoc_PHP/php chỉ xác minh được8.5.10; các đường dẫn C:/xampp/php/php.exe và C:/tools/php83/php.exe không có. Không cài hoặc tải runtime mới; không coi kết quả8.5 là chứng nhận8.3.

### 22/10/2026 — Chốt hồ sơ kiểm chứng #10

Rà cuối phát hiện chart bị trống khi Tổng quan → Tài sản → Tổng quan do cache còn hợp lệ trong khi chart đã dispose. Đã xóa view/documentData khi rời trang; browser thêm4 check cho Admin/Quản lý, toàn suite **64 đạt**. Phiên browser/server cũ đã đóng; lần đầu không chạy được do ECONNREFUSED. Đã khởi động lại đúng static server workspace rồi kiểm, không coi lỗi môi trường là lỗi sản phẩm. Commit fix0ce2166; không DB/host.

Đã có7 commit riêng F10.1–F10.7; đối chiếu hash trong bảng bên dưới. PHASE10_VERIFY.md ghi lệnh/kết quả và giới hạn; PHASE10_HOST_TEST.md dành cho user kiểm sau upload. Kiểm lại local22/10: 52 pure/scripted +7 ngân sách, route13 và Smarty/syntax đạt; không chạy DB. DB Phase1–10 **chưa nghiệm thu**, chưa EXPLAIN/index thật, concurrency/rollback thật hoặc PHP-FPM export. Không merge/tag7–9/push/deploy. Vendor Excel vẫn có deprecation PHP local, chưa nâng dependency.

## 21/10/2026 — BE #10 đã được duyệt, triển khai local

User nguyên văn: «tôi duyệt bạn hãy bắt đầu BUILD». Các ghi chú chưa duyệt #10 bên dưới là lịch sử trước phê duyệt. DB Phase 1–9 vẫn **chưa nghiệm thu**, không merge/tag7–9/push/deploy. Agent tự kiểm local, user kiểm host sau upload.

| Mã | Chức năng | Vùng | Trạng thái | Commit | Verify | Ghi chú |
|---|---|---|---|---|---|---|
| F10.1 | KPI dashboard theo phạm vi, mốc giờ VN chung | FE/BE | [x] build local | a017327 | 35 pure/scripted; 9 browser API mock; Smarty/syntax đạt | Đọc RR/SERIALIZABLE; tối đa 5.000 asset, từ chối vượt; chưa DB/EXPLAIN/UAT |
| F10.2 | Biểu đồ tình trạng theo loại/bộ phận/năm mua, bảng đối chiếu | FE/BE | [x] build local | 86ba2bd | 35 pure/scripted; 9 browser API mock; JS syntax đạt | ECharts 4 trạng thái xếp chồng, không animation/polling; chưa DB |
| F10.3 | Biểu đồ tỷ lệ lỗi, bảng tử số/mẫu số | FE/BE | [x] build local | 38adbbe | 35 pure/scripted; 9 browser API mock; JS syntax đạt | Đếm distinct asset repair trong cửa sổ 12 tháng; không đếm event; chưa DB |
| F10.4 | Biểu đồ đề xuất thay thế, bảng điểm 3 số lẻ | FE/BE | [x] build local | 477d9f9 | 35 pure/scripted; 72 hồi quy vòng đời; 9 browser API mock; JS syntax đạt | Sort/tier theo DECIMAL gốc; giữ điểm >1; chưa DB |
| F10.5 | Preview và Excel tồn kho/bảo trì/vòng đời ngắn/ngân sách | FE/BE | [x] build local | a0b5b16 | 52 pure/scripted +7 ngân sách; 15 browser API mock; XLSX thật mở lại STRING; route13 đạt | HMAC preview 15 phút, dữ liệu đổi trả409; audit trước bytes; native actor/scope kiểm lại; chưa DB |
| F10.6 | PDF bốn báo cáo, tối đa 500 dòng dữ liệu | FE/BE | [x] build local | d607518 | PDF thật tiếng Việt đã render/xem; 52 pure/scripted, 134 hồi quy ngân sách; 15 browser báo cáo đạt | Font/runtime PDF dùng lại; không package mới; chưa PHP-FPM/DB/host |
| F10.7 | Trung tâm cảnh báo live và chuông hiệu lực | FE/BE | [x] build local | 0ca9a7e | 52 pure/scripted +7 ngân sách, 60 browser API mock; hồi quy browser8:63/9:42 đạt | Phân trang/lọc, nhiều lý do riêng; manager license theo allocation scope; không lưu đã đọc; chưa DB |

Lớp đọc dùng lại điểm DECIMAL của vòng đời, transaction đọc nhất quán và helper chung cho các báo cáo tiếp theo. SQL đọc xuất tại PHASE10_EXPLAIN.sql; generator không thực thi SQL, không DDL.


### 21/10/2026 — Agent tự kiểm local theo chỉ đạo mới

User giao agent tự kiểm local, user kiểm thủ công sau upload host. Chạy lại ba suite FE/MOCK #10 trên source sau 0c64089: dashboard 46, reports 50, alerts 37, tổng **133 đạt**; node syntax hai controller đạt. Kiểm tiến trình preview node.exe và entrypoint static localhost:18766. Không phát hiện lỗi mới trong phạm vi test; không sửa product code. Chi tiết PHASE10_REVIEW_CHECKLIST.md. Không yêu cầu user chạy checklist local; không thay kết quả mock bằng PASS DB/host. BE10 chưa duyệt, DB1–9 chưa nghiệm thu; không merge/tag7–9/push/deploy.

## 21/10/2026 — F10.7 trung tâm cảnh báo FE/MOCK

| Mã | Chức năng | Vùng | Trạng thái | Commit | Verify | Ghi chú |
|---|---|---|---|---|---|---|
| F10.7 (trình bày) | Lọc mức độ/lý do/tìm thiết bị, phân trang cảnh báo | FE/MOCK | [x] prototype | 11f6adc | 37 browser mock mới; hồi quy dashboard 46 và báo cáo 50; syntax/diff đạt; xem ảnh 1366/768/390; rà độc lập không finding | Chuông giữ tổng hiệu lực trong scope; không đổi mốc số liệu hoặc fetch khi lọc; xem PHASE10_ALERT_PREVIEW.md |

Tiếp tục ngày 21/10: đã gom PHASE10_REVIEW_CHECKLIST.md với URL, kết quả kỳ vọng tính tay và các ô user tự kiểm; chỉnh ghi chú dashboard cũ để phản ánh report/cảnh báo prototype hiện có. Chỉ sửa tài liệu, không chạy lại browser cho thay đổi tài liệu; đối chiếu fixture/test hiện có và diff. Không coi checklist chưa đánh dấu là UAT đạt.

BE #10 vẫn **chưa duyệt/chưa build**. DB Phase 1–9 **chưa nghiệm thu**. Không merge/tag Phase 7–9, push hoặc deploy. Prototype không chứng minh quyền server hoặc kết quả DB.

Cập nhật 21/10/2026. Chưa nghiệm thu đầy đủ yêu cầu sản phẩm nào trong 10 yêu cầu. Bảng đầu là kế hoạch gốc; các mục cập nhật cuối tài liệu ghi trạng thái build/verify mới nhất, không tính audit thành phần trăm chức năng.

## Trạng thái hiện hành sau đối chiếu Word

### Phase 8 — Đã duyệt backend, đang build local

| Mã | Chức năng | Vùng | Trạng thái | Commit | Verify | Ghi chú |
|---|---|---|---|---|---|---|
| F8.1 | Tuổi và tỷ lệ tuổi thọ | BE+FE | [~] | 0c2e0a7 | 68 pure/scripted, 37 browser mock, Smarty đạt | Đã build local; DB chưa chạy |
| F8.2 | Điểm và giải thích | BE+FE | [~] | 0c2e0a7 | Biên 0,6/0,8, điểm >1, 3 số lẻ và settings đạt | DECIMAL trên DB chưa kiểm |
| F8.3 | Tuổi TB và tỷ lệ lỗi theo nhóm | BE+FE | [~] | 0c2e0a7 | Query bindings/scope và group mock đạt | 10 câu EXPLAIN chưa chạy |
| F8.4 | Đề xuất, bộ lọc và phân trang | BE+FE | [~] | 0c2e0a7 | Paging mock, ba vai trò, viewport đạt | Chỉ GET, không tự thanh lý |

Chi tiết: PHASE08_VERIFY.md, PHASE08_HAND_CALCULATIONS.md, PHASE08_HOST_TEST.md. Hồi quy Phase 7: 236 pure/scripted và 64 browser mock checks đạt. Review Agent chưa phát hiện lỗi backend hoặc FE. Không migration/index mới, DB, deploy, merge hoặc tag.

**Xác nhận kiểm thử DB theo user (nguyên văn ngày 21/10/2026):**

«Phase 1–7 chưa có xác nhận kiểm thử DB chi tiết (CHECK ticket_events, rollback InnoDB, hai phiên đồng thời, phiên bản MySQL/MariaDB, môi trường chưa được cung cấp)»

Không ghi PASS cho phần này; Phase 7 và Phase 8 chưa được coi là nghiệm thu DB. User yêu cầu tiếp tục build #08 nhưng KHÔNG merge, KHÔNG tag Phase7. Khi user cung cấp xác nhận kiểm thử cụ thể, ghi nguyên văn rồi mới cập nhật trạng thái. Ảnh/luồng host từng phần bên dưới chỉ là bằng chứng từng ca, không thay thế xác nhận đầy đủ.

Gate #08 duyệt kèm cập nhật mới nhất: điểm hiển thị đúng3chữ số, raw compare/sort, thanh hỗ trợ>1; settings thiếu key dùng mặc định có ghi chú, key sai lỗi; bắt buộc ví dụ tính tay và query EXPLAIN; không SQL/index mới nếu chưa gate riêng. Không tạo/kết nối DB hoặc deploy.


### Phase 7 — Đã duyệt kèm chỉnh sửa, đang build local

**UAT host đã xác nhận luồng bảo trì cơ bản ngày 21/10:** tạo/list/read-back phiếu, gán #111, periodic pending → resolved, chi phí 100.000 VND; SQL xác nhận asset maintenance, last_maintenance_at 2026-10-20 17:00:00 UTC (21/10 VN), next_due 2027-04-21. Admin xác nhận sẵn sàng, SQL asset in_use và giữ lịch; thống kê 2026 đúng 1 periodic resolved/100.000 VND. Lỗi POST403 đã sửa ở 60f4f49. Chưa có bằng chứng đầy đủ về quyền ngoài scope, đồng thời, guard pending, failed/reopen và điều chỉnh chi phí; chưa nghiệm thu toàn bộ/merge.

Đã chuẩn bị gói upload cộng dồn từ source80929be theo yêu cầu user, 1.340 runtime files; manifest/CRC/hash đọc lại đạt, chi tiết DEPLOY_LOG.md và HOST_UPLOAD_PHASE07.md. Agent chưa upload. User đã gửi metadata DB dung_infracore/MariaDB10.6.28, 5 bảng chính InnoDB, CHECK=ON ở phiên phpMyAdmin; nghiệp vụ DB Phase1–7 vẫn chưa nghiệm thu.

Không merge Phase 7 trước khi user kiểm thử DB thật Phase 1–7. Chưa xác minh CHECK trên DB thật; phiên bản host theo ảnh cũ 10.6.28-MariaDB, chưa kết nối lại. Service kiểm transitions độc lập CHECK. User duyệt #07-SCHEDULE riêng; lịch ban đầu từ ngày mua đã nối Phase 3–5, không DDL/backfill/DB/deploy. Không mở Phase 8 trước kiểm thử DB đã thống nhất.

| Mã | Chức năng | Vùng | Trạng thái | Commit | Verify | Ghi chú |
| --- | --- | --- | --- | --- | --- | --- |
| F7.1 | Tạo/list/detail phiếu | BE/FE | [~] | c4c5942 | scripted236 + browser25 | Ba vai trò theo scope; DB chưa chạy |
| F7.2 | Gán/xử lý/cost | BE/FE | [~] | c4c5942 | scripted + form mock đạt | Admin-only closed cost, app transitions |
| F7.3 | Đồng bộ/guard asset | BE/FE | [~] | c4c5942 | Phase3 39, Phase5 203 | Pending vẫn cho thu hồi; chưa DB locking |
| F7.4 | Lịch sử gộp | BE/FE | [~] | c4c5942 | browser inline history đạt | Phân trang20, SQL UNION chưa chạy DB |
| F7.5 | Thống kê theo asset/năm | BE/FE | [~] | c4c5942 | tham số/query contract + UI đạt | opened_at/type=repair cho Phase8; EXPLAIN chưa chạy |
| F7.6 | Lịch bảo trì | BE/FE | [~] | ddc9c87 | cuối tháng, nguồn ngày mua/last đạt | Gate schedule duyệt; DB timezone chưa chạy |

### Phase 6 — Gate đã duyệt kèm chỉnh sửa, đang build local

User duyệt #06 ngày 20/10: chỉ Admin xác nhận vượt mức với lý do/audit; xem key POST+CSRF/no-store/rate limit/nhập lại mật khẩu/tự ẩn 10 giây/audit fail-closed; preflight khóa. Không DDL, không DB/deploy. **Phase 1–6 chưa được kiểm chứng đầy đủ trên DB thật; cần kiểm thử DB trước Phase 8.** Không dùng kết quả mock thay nghiệm thu DB.

Giới hạn an toàn từ schema: giấy phép không có bộ phận sở hữu và native user chưa có membership đã xác minh. Metadata chung chỉ Admin sửa; manager xem catalog che key và cấp/thu hồi máy/bộ phận theo scope; cấp theo user chỉ Admin. Không thêm cột để suy diễn quyền.

| Mã | Chức năng | Vùng | Trạng thái | Commit | Verify | Ghi chú |
| --- | --- | --- | --- | --- | --- | --- |
| F6.1 | Danh mục và key mã hóa | BE/FE | [~] | 068931b | pure32 + scripted159 + browser28 đạt | Không DB; PHASE06_VERIFY.md |
| F6.2 | Cấp phát/thu hồi | BE/FE | [~] | 068931b | scripted scope/transaction/overage + browser đạt | DB concurrency chưa chạy |
| F6.3–F6.4 | Hết hạn/vượt mức | BE/FE | [~] | 068931b | ngày biên + chuông/dashboard mock đạt | Chưa DB/timezone host |
| F6.5 | Xem key có reauth/audit | BE/FE | [~] | 068931b | audit failure/rate/role + browser tự ẩn10s đạt | HTTP/DB host chưa kiểm |

### Phase 5 — BE và PDF đã duyệt, đã nối local, host UAT chưa chạy

User duyệt **“OK BE Phase 5”** và **“OK PDF Phase 5”** ngày 19/10/2026. Đã nối giao nhân viên/bộ phận, thu hồi (gồm Support trong scope), lịch sử 20 dòng/trang, người nhận tự xác nhận, badge/chuông quá hạn từ ngày bảo trì có sẵn. Ngày 20/10 đã hoàn thiện PDF thật bằng mPDF8.3.1, tải từ lượt đang mở/lịch sử, kiểm lại actor/scope/version và audit trước trả bytes. Không DDL, không ghi dc_, tạo DB hoặc kết nối/deploy host. GD local chỉ bật per-process; host cần kiểm riêng.

`php tests/ita_phase05_unit.php`: **173 pure/scripted checks đạt**, không DB. `tests/ita_phase05_browser.js`: **29 browser API mock checks đạt**; static giữ 22 checks. PDF: **118 PHP/renderer/scripted +2 thiếu-GD +18 browser transport mock checks đạt**; pypdf/pdfplumber và Poppler xác minh 1/2 trang, tiếng Việt, số biên bản, seri, giờ VN và khung ký. Chi tiết PHASE05_PDF_VERIFY.md. Checkpoint bfe3539 đã commit/push nhánh feature/ita-phase05-pdf theo yêu cầu user; F5.4 lưu commit riêng. Không tag phase-done vì DB/host UAT còn mở.

| Mã | Chức năng | Vùng | Trạng thái | Commit | Verify | Ghi chú |
| --- | --- | --- | --- | --- | --- | --- |
| F5.1–F5.3 | Giao/thu hồi/lịch sử | FE/BE | [~] | bfe3539 checkpoint | Pure/scripted173 + browser API mock29 đạt | Code local có, DB/concurrency chưa chạy |
| F5.4 | Biên bản PDF | BE/FE | [~] | 5f1e6ea | PHP118 + thiếu-GD2 + browser18 + parser/render đạt | Code local hoàn tất, auth/DB/host UAT chưa chạy |
| F5.5–F5.6 | Xác nhận nhận/quá hạn bảo trì | FE/BE | [~] | bfe3539 checkpoint | Rule/scope/alert query + browser submit/badge/center đạt | DB/read-back/timezone host chưa chạy |

### Phase 4 — BE-GATE đã duyệt, đã build local, DB UAT chưa chạy

User duyệt **“OK BE Phase 4”** ngày 19/10/2026. Đã nối tải mẫu XLSX, upload/đọc/validate, staging private, preview 20 dòng/trang, confirm một transaction có audit và chống ghi hai lần, Excel lỗi, export theo filter/scope. Dùng PhpSpreadsheet có sẵn; không package mới, không DDL, không tạo/kết nối DB, không deploy. Static preview vẫn giữ 4 kịch bản MOCK và khóa ghi thật.

`tests/ita_phase04_unit.php`: **88 checks đạt** (XLSX thật + pure + adapter giả, không DB). `tests/ita_phase04_browser.js`: **30 checks đạt** (API mock, 1366/768/390, upload/preview/confirm/download/lỗi/mất phản hồi/role). `tests/ita_phase04_preview.js`: **16 checks đạt** (MOCK không ghi). Chi tiết `PHASE04_VERIFY.md`; phần DB/host còn lại ở `PHASE04_HOST_TEST.md`. Phase3 DB UAT và Git theo chức năng còn mở, không tag phase-done. Không deploy hoặc dùng gói cũ.

| Mã | Chức năng | Vùng | Trạng thái | Commit | Verify | Ghi chú |
| --- | --- | --- | --- | --- | --- | --- |
| F4.1 | Tải mẫu Excel | FE/BE | [~] | chưa commit | XLSX thật + browser API mock đạt | Code local có; HTTP host/Excel desktop chưa chạy |
| F4.2 | Upload và validate | FE/BE | [~] | chưa commit | pure/XLSX/adapter + browser API mock đạt | SQL collation/DB thật chưa chạy |
| F4.3 | Preview và confirm theo lô | FE/BE | [~] | chưa commit | adapter transaction/idempotency/role + browser API mock đạt | InnoDB rollback/concurrency thật chưa chạy |
| F4.4 | Excel lỗi theo dòng | FE/BE | [~] | chưa commit | writer thật + browser download mock đạt | Host UAT chưa chạy |
| F4.5 | Export theo filter/scope | FE/BE | [~] | chưa commit | adapter query/scope/limit + browser download mock đạt | SQL/performance/host UAT chưa chạy |

- Phạm vi đang build: Phase 3 và BE Phase 4 đã duyệt; không tự mở BE Phase 5. Host Phase 2 đã được user xác nhận login username/Gmail và logout, chưa coi là toàn bộ RBAC UAT đạt.
- Local Phase 3: **36 pure/scripted checks +30 browser API mock checks đạt**, Smarty render/escape đạt. Đã kiểm khoảng bảo hành nhập ngược, mảng thay ngày, ngày không tồn tại; helper form còn 3 tham số.
- Có ERD.md từ migrations và PERF_NOTES.md phân biệt bằng chứng lịch sử/việc chưa chạy. RUN_LOCAL.md đã sửa: preview không DB dùng static server, PHP thật yêu cầu auth/DB.
- Chưa có DB integration Phase 3, benchmark 500 dòng hoặc nghiệm thu production. Không tạo DB, không kết nối host, không upload. Luồng duyệt thanh lý của Quản lý và phân trang lookup/history vẫn là chênh lệch còn mở; không đánh dấu toàn bộ Word đã hoàn thành.
- Các đoạn nhật ký phía dưới có thời điểm riêng; câu «chưa có HEAD», «chưa có bảng», «host chờ import» ở thời điểm audit không mô tả trạng thái hiện tại. Mã Phase 3 đang ở working tree, không gộp thành một commit nhiều chức năng hoặc tạo tag phase-done khi nghiệm thu còn thiếu.

## Mini plan Phase 0

1. Kiểm tra Git, dữ liệu nhạy cảm và dry-run dọn dẹp; giữ nguyên source.
2. Đọc entrypoint, DAO, auth, thư viện và cấu trúc route.
3. Ghi audit, kiểm kê cũ và trích kế hoạch từ Word.
4. Lint PHP toàn repo; smoke HTTP ở chế độ cô lập.
5. Chỉ tạo baseline khi danh tính tác giả và danh sách source an toàn đã được xác minh.

| Mã | Chức năng | Vùng | Trạng thái | Commit | Verify | Ghi chú |
| --- | --- | --- | --- | --- | --- | --- |
| F0.1 | Backup và dọn dẹp an toàn | Tài liệu/Git | [~] | chưa có | xem AUDIT.md | Chưa nghiệm thu Phase 0 |
| F0.2 | Audit nền Derasoft | Tài liệu/Git | [~] | chưa có | xem AUDIT.md | Chưa nghiệm thu Phase 0 |
| F0.3 | Kiểm kê giao diện cũ | Tài liệu/Git | [~] | chưa có | xem AUDIT.md | Chưa nghiệm thu Phase 0 |
| F0.4 | Kế hoạch và bảng tiến độ | Tài liệu/Git | [~] | chưa có | xem AUDIT.md | Chưa nghiệm thu Phase 0 |
| F0.5 | Lint và smoke baseline | Tài liệu/Git | [!] | chưa có | php -l: 1451/1456 đạt; HTTP smoke 5/5 trang cô lập | 5 lỗi thư viện mail cũ; xem BASELINE.md |
| F1.1 | tạo schema bộ phận, loại tài sản và cài đặt | SQL | [~] | commit F1.1 trên feature/ita-phase01-ui | local MySQL: tạo bảng/constraint đạt | SQL giao user; MariaDB host chờ user chạy |
| F1.2 | tạo schema tài sản và chỉ mục theo phạm vi | SQL | [~] | commit F1.2 trên feature/ita-phase01-ui | local MySQL: tạo bảng/constraint đạt | SQL giao user; MariaDB host chờ user chạy |
| F1.3 | tạo schema giấy phép và bàn giao thiết bị | SQL | [~] | commit F1.3 trên feature/ita-phase01-ui | local MySQL: tạo bảng/constraint đạt | SQL giao user; MariaDB host chờ user chạy |
| F1.4 | tạo schema ticket, audit, ngân sách và import | SQL | [~] | commit F1.4 trên feature/ita-phase01-ui | local MySQL: tạo bảng/constraint đạt | SQL giao user; MariaDB host chờ user chạy |
| F1.5 | feat(db): seed dữ liệu mẫu dev/test [BE]. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F1.6 | Design tokens + Bootstrap dark theme | FE | [x] | 051f80b | Playwright 1366/768/390 đạt, Bootstrap hash trùng nguồn | Chỉ FE, chưa tích hợp PHP |
| F1.7 | Layout sidebar, topbar, breadcrumb, toast | FE | [x] | c61ae8f | PHP/Smarty + browser MOCK 1366/768/390 đạt | Chưa có live DB/auth |
| F1.8 | Component dùng chung | FE | [x] | d342024 | bảng/KPI/badge/modal chi tiết + confirm cancel/accept/escaping đạt | API confirm không tự ghi DB |
| F1.9 | Sidebar mới chỉ có điều hướng ITA | FE | [x] | commit F1.9 trên feature/ita-phase01-ui | browser MOCK 1366/768/390 đạt | Tách file riêng, giữ nguyên menu cũ; guard BE chưa làm |
| F1.10 | feat(legacy): guard route cũ theo cờ ITA_LEGACY_UI_ENABLED [BE]. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F2.1 | feat(auth): đăng nhập trên giao diện mới [BE+FE]. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F2.2 | feat(rbac): 3 vai trò + ma trận quyền 4.1 [BE]. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F2.3 | feat(rbac): middleware phạm vi bộ phận phụ trách [BE]. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F2.4 | feat(rbac): quản lý user và bộ phận phụ trách (Admin IT) [BE+FE]. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F2.5 | feat(audit): ghi log tạo/sửa/xóa tài sản và đổi trạng thái [BE]. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F2.6 | feat(audit): màn hình xem log có bộ lọc [BE+FE]. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F2.7 | feat(sec): rate limit đăng nhập, CSRF [BE]. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F3.1 | feat(asset): danh sách phân trang [BE+FE]. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F3.2 | tạo tài sản. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F3.3 | sửa tài sản. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F3.4 | đổi trạng thái có lý do + log. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F3.5 | xóa mềm. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F3.6 | chi tiết thiết bị (tab thông tin, lịch sử). | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F3.7 | feat(search): tìm kiếm nâng cao theo phòng ban, loại, năm mua, bảo hành. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F3.8 | feat(category): quản lý danh mục loại thiết bị. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F4.1 | feat(import): tải file mẫu Excel. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F4.2 | upload và validate (trùng mã, danh mục, ngày). | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F4.3 | xem trước và ghi theo lô trong transaction. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F4.4 | xuất file lỗi theo dòng. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F4.5 | feat(export): xuất danh sách tài sản ra Excel theo bộ lọc. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F5.1 | giao thiết bị cho nhân viên/phòng ban. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F5.2 | thu hồi. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F5.3 | lịch sử phân bổ. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F5.4 | biên bản bàn giao PDF. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F5.5 | xác nhận nhận bàn giao. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F5.6 | cảnh báo quá hạn bảo trì (badge ở danh sách + chuông). | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F6.1 | CRUD giấy phép (key mã hóa). | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F6.2 | cấp phát/thu hồi chỗ theo máy, người, phòng ban. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F6.3 | cảnh báo sắp hết hạn. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F6.4 | cảnh báo vượt mức. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F6.5 | xem key đầy đủ (chỉ Admin IT, có log). | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F7.1 | tạo phiếu bảo trì/ticket. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F7.2 | xử lý ticket (gán kỹ thuật viên, pending → resolved/failed, ghi ita_ticket_events). | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F7.3 | đồng bộ trạng thái thiết bị. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F7.4 | lịch sử bảo trì trên chi tiết thiết bị. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F7.5 | thống kê số lần bảo trì mỗi năm theo thiết bị. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F7.6 | tính next_maintenance_due theo chu kỳ. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F8.1 | tuổi đời và tỷ lệ tuổi/tuổi thọ. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F8.2 | điểm thay thế (4.4). | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F8.3 | tuổi trung bình, tỷ lệ lỗi theo loại và phòng ban. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F8.4 | danh sách đề xuất thay thế. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F9.1 | cấu hình định mức và tham số. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F9.2 | dự báo ngân sách bảo trì/thay thế. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F9.3 | thiết bị cần mua thêm theo kế hoạch mở rộng. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F9.4 | đổi bộ lọc (năm, phòng ban, loại). | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F9.5 | xuất ngân sách Excel/PDF. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F10.1 | dashboard KPI. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F10.2 | biểu đồ tình trạng theo loại, phòng ban, năm mua. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F10.3 | biểu đồ tỷ lệ lỗi. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F10.4 | biểu đồ thiết bị sắp thay thế. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F10.5 | báo cáo Excel (tồn kho, sắp bảo trì, vòng đời ngắn, ngân sách). | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F10.6 | báo cáo PDF. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F10.7 | trung tâm cảnh báo (chuông + trang). | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F11.1 | rà soát index theo EXPLAIN (docs/PERF_NOTES.md). | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F11.2 | rà soát bảo mật (XSS, CSRF, SQLi, upload, quyền). | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F11.3 | responsive lần cuối. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F11.4 | trang lỗi chuẩn và xử lý lỗi. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F11.5 | gói deploy và checklist FileZilla trong docs/DEPLOY_LOG.md. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F11.6 | hướng dẫn sử dụng. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F12.1 | hạ tầng motion + cờ UI_MOTION_ENABLED + reduced-motion. Sau đó mỗi màn một commit theo thứ tự: | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F12.2 | cảnh báo, | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F12.3 | ticket, | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F12.4 | dashboard, | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F12.5 | danh sách tài sản, | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F12.6 | chi tiết thiết bị, | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F12.7 | vòng đời, | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F12.8 | dự báo, | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F12.9 | đăng nhập. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F13.1 | feat(db): ita_network_links + dữ liệu mẫu [BE]. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F13.2 | trang 2D. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F13.3 | node pulse và đường dữ liệu chạy. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |
| F13.4 | bản 3D Three.js nạp lazy + dự phòng 2D. | theo PLAN_FINAL | [ ] | chưa có | chưa chạy | |

## Điểm chặn và giả định

- Git chưa có HEAD; đã xác nhận danh tính local dungtatri <dungtatri.dev@gmail.com>. Chưa tạo baseline source.
- User xác nhận dung_infracore trên hosting dành riêng dự án. Ảnh cho thấy MyISAM/InnoDB và collation hỗn hợp; chưa kiểm schema đầy đủ, truy cập hoặc backup ngoài repo.
- Chưa gửi BE-GATE #1: cần xác định DB dev/test và kiểm chứng schema để đề xuất migration đúng nền.
- Quyết định tự chọn ghi ở DECISIONS.md.
- Các dòng tổng hợp trong Word chưa có mã riêng (ví dụ motion F12.2–F12.9) phải tách rõ khi bắt đầu phase đó.
## Cập nhật schema

Đã đọc dung_infracore.sql, 96 bảng, 68 MyISAM/28 InnoDB; không có ita_. Báo cáo SCHEMA_AUDIT.md. F0.2 đã có bằng chứng schema offline; DB integration và backup dữ liệu chưa xác minh. Không thực thi SQL. Chuẩn bị BE-GATE #1 trên nền thực tế, gồm scope store_id và không FK dc_users.

## Build FE Phase 1 ngày 16/10/2026

Mini plan: (1) tokens/theme cục bộ; (2) layout và sidebar riêng; (3) component/mock tương tác; (4) Playwright 3 kích thước và lỗi tải; (5) review/commit và đề xuất BE-GATE.

F1.6 theme đã build/verify. F1.7 khung đã build/verify. F1.8 KPI, bảng, badge, modal chi tiết đã build/verify; modal xác nhận nghiệp vụ còn chờ hành động BE. F1.9 sidebar riêng không include chức năng cũ đã build/verify. Chưa nghiệm thu tích hợp PHP/DB. Chi tiết UI_PHASE01_VERIFY.md. BE-GATE đề xuất tại BE_GATE_01.md.

## SQL giao user chạy host

Phạm vi user yêu cầu: soạn SQL tạo 12 bảng ITA. File tổng database/host/phase01_create_ita_tables.sql, kèm preflight và verify. Local MySQL 8.4.11: migrations và bundle tạo 12 bảng, 30 kiểm tra ràng buộc đạt. MariaDB host chưa chạy; chờ user báo kết quả. Không seed hoặc sửa PHP. Chi tiết SQL_PHASE01_VERIFY.md.

## Xác nhận từ user sau import SQL host

Ngày 16/10/2026: user báo «đã thành công» sau hướng dẫn chạy phase01_verify.sql. Ghi nhận user xác nhận tạo bảng trên dung_infracore/MariaDB hosting thành công; agent không kết nối host và chưa nhận ảnh bộ đếm 12/12/12 hoặc danh sách đủ 11 khóa ngoại sau lần import cuối. Không suy diễn xác nhận này thành quyền seed, sửa PHP/routing hoặc deploy. Migration F1.1–F1.4 đã bàn giao và được user xác nhận chạy; ứng dụng chưa tích hợp DB.
## Đính chính xác nhận hosting

Ảnh user gửi tiếp theo cho thấy selected_database=dung_infracore nhưng truy vấn metadata dùng DATABASE() trả 0/NULL/NULL, không thấy FK. Chưa xác nhận tạo đủ 12 bảng trên host; kết luận thành công trước đó chỉ dựa trên lời báo của user và được thay bằng «chờ chẩn đoán». Trước đó import có thông báo #1050 ita_departments already exists. Chưa rõ nguyên nhân hai quan sát khác nhau; không mặc định bảng bị mất hoặc migration thất bại. Đã thay bộ lọc metadata trong preflight/verify bằng tên schema cố định để giảm phụ thuộc ngữ cảnh truy vấn. Cần SHOW TABLES và SHOW CREATE TABLE với schema chỉ định rõ. Không chạy lại CREATE hoặc xóa bảng khi chưa đối chiếu.
## Bằng chứng SHOW TABLES trên host

User gửi ảnh SHOW TABLES FROM dung_infracore LIKE 'ita%': liệt kê đủ 12 tên bảng đúng migration. SHOW CREATE TABLE dung_infracore.ita_departments trả về cấu trúc nhưng ảnh bị rút gọn. Đã xác nhận tồn tại đủ bảng; không chạy lại CREATE. Chưa xác nhận toàn bộ cột/engine/collation/11 FK qua ảnh này. Nguyên nhân truy vấn information_schema trước đó dùng DATABASE() trả 0 chưa xác minh; không kết luận bảng chưa được tạo. File verify đã dùng tên schema cố định.
## Xác nhận số lượng khóa ngoại trên host

Ảnh mới từ user cho thấy truy vấn information_schema.TABLE_CONSTRAINTS với CONSTRAINT_SCHEMA='dung_infracore' và loại FOREIGN KEY trả tổng 11 dòng. Kết hợp SHOW TABLES trước đó: đã xác nhận đủ 12 tên bảng và 11 khóa ngoại trên MariaDB host. Chưa có ảnh bộ đếm engine/collation 12/12/12 hoặc toàn bộ định nghĩa cột. Không yêu cầu chạy lại CREATE. Quyền PHP/routing và seed vẫn chưa được user phê duyệt.
## Build PHP sau khi user yêu cầu tiếp tục

Mini plan: render khung bằng Smarty; kiểm soát route trước bootstrap; hoàn thiện component xác nhận; kiểm route/UI thật; soạn danh mục đúng store. Đã render /admin.php?op=ita bằng Smarty; 32 checks HTTP/flags đạt và browser 1366/768/390 đạt. SQL danh mục 6/6/6 store_id=1 đã kiểm lặp local, host chưa chạy. Chi tiết PHP_PHASE01_VERIFY.md. F1.7 và F1.8 FE đã kiểm chứng; F1.10 còn Apache/legacy restoration và allowlist auth Phase 2; F1.5 còn bộ seed đầy đủ. Không tuyên bố Phase 1 hoàn tất.

### Tách seed thành hai chức năng tự chứa

- F1.5a: SQL danh mục khởi tạo 6 loại/6 bộ phận/6 cấu hình cho store 1, giữ dữ liệu hiện có; đã kiểm lặp local, host chờ user chạy.
- F1.5b: bộ seed 300–500 tài sản và dữ liệu nghiệp vụ dev/test, chưa làm; cần chọn actor dev/test đúng, không gán thao tác giả cho user thật.
- F1.10: mã gate đã kiểm local; còn Apache/host và khôi phục legacy E2E, chưa nghiệm thu toàn bộ.

Commits build mới: F1.7 c61ae8f; F1.8 d342024; gate PHP F1.10 fcc900d (còn kiểm Apache/legacy); danh mục F1.5a 0495e7d (host chờ user chạy). Không merge/tag phase done.

## File kết quả ita_settings.sql từ user

Đã đọc tests/ita_settings.sql, không thực thi: có 6 cặp setting_key/setting_value khớp danh mục khởi tạo (budget_contingency_percent=10, currency=VND, display_timezone=Asia/Ho_Chi_Minh, license_critical_days=7, license_warning_days=30, warranty_warning_days=30). INSERT chỉ xuất setting_key và setting_value, không có store_id trong khi CREATE định nghĩa store_id NOT NULL không default; file này không đủ để xác nhận scope store_id=1 hoặc dùng như bản backup phục hồi nguyên bảng. DDL xuất cũng không thể hiện đầy đủ engine/collation/CHECK. Không import lại file. Chưa nhận kết quả categories/departments sau chạy catalog; cần SELECT đếm ba nhóm theo store_id=1.
## Tinh chỉnh UI theo Impeccable

Mini plan: khảo sát giao diện và directive; chỉnh chữ/icon/responsive; kiểm hai lượt có giới hạn; review và commit riêng. Đã hoàn tất polish FE: chữ rõ hơn, icon SVG cùng hệ, KPI tablet hai cột, select mobile toàn hàng, focus bàn phím và skeleton. Playwright MOCK qua PHP/Smarty đạt; không tràn ở 1366/768/390/320/683. Cặp semantic contrast thấp nhất 4.82:1. Detector một lần có warning Inter được giữ theo hệ font hiện có. Chi tiết UI_POLISH_IMPECCABLE.md. Không thay PHP/DB. User cũng đã gửi ảnh xác nhận danh mục host 6/6/6 store 1 ở bước trước; không yêu cầu chạy lại.

## Phase 2 — Build được user cho phép sau rà UI

User chọn Admin IT đầu tiên: store 1 / user 111. Mini plan: xác thực native và session riêng; quyền/scope server; quản lý mapping và audit; kiểm HTTP/DB/browser; bàn giao SQL host. Đã kiểm local 46 browser + 32 routing + 8 integration checks, review độc lập không phát hiện lỗi. Hosting chưa chạy SQL Phase 2 hay kết nối runtime. Chi tiết PHASE02_VERIFY.md và BE_GATE_02.md.

| Mã | Chức năng | Vùng | Trạng thái | Commit | Verify | Ghi chú |
|---|---|---|---|---|---|---|
| F2.1 | Đăng nhập giao diện mới | BE+FE | [x] local | 6f461ef | PHP lint, browser đăng nhập/đăng xuất | Host chờ SQL/config; tái dùng credential native |
| F2.2 | Ba vai trò | BE | [x] local | e41e922 | URL/API trái quyền 403 | Không kế thừa PM Admin; quyền CRUD kiểm tiếp Phase 3 |
| F2.3 | Scope bộ phận/store | BE | [x] local | 9fcc1f2 | Hai non-admin, cross-store, dept, session revision | Áp dụng API tài sản đọc hiện có |
| F2.4 | Quản lý quyền user hiện có | BE+FE | [x] local | fa05be8 | Cấp/đổi quyền, last Admin guard, UI persisted | Không tạo tài khoản/đổi mật khẩu native |
| F2.5 | Audit CRUD tài sản | BE | [~] | — | Writer allowlist integration đạt | Hook cần Phase 3 CRUD, chưa xong |
| F2.6 | Xem log và bộ lọc | BE+FE | [x] local | 9a6e9ab | Browser đọc audit quyền và màn hình | Log auth/quyền đã gắn |
| F2.7 | Rate limit/CSRF | BE | [x] local | 44c4246 | Thử sai lần 6:429; thiếu token:403 | Shared DB buckets, HTTPS host chờ UAT |

Không merge/tag phase done: F2.5 và host UAT còn mở. Danh mục host Phase 1 đã được user xác nhận 6/6/6 bằng ảnh.

Lưu local: F2.7 `44c4246`; F2.2: DAO tách vai trò ITA và kiểm native active mỗi request, SQL bootstrap 111 có guard. Verify vai trò qua browser/API trái quyền và bootstrap SQL local đạt; chưa áp dụng host.

F2.2 lưu `e41e922`. F2.3: DAO scoped read, API pagination/aggregate và hiển thị live có retry; browser xác nhận scope/403/404 và hồi quy static MOCK đạt. Chưa có CRUD hoặc số liệu host.

F2.3 lưu `9fcc1f2`. F2.4: màn hình phân quyền tài khoản hiện có, bắt buộc scope non-admin, bảo vệ Admin active cuối cùng và tăng revision thu hồi session; browser cấp/đổi quyền, UI đọc lại, session cũ bị chặn đạt.

F2.4 lưu `fa05be8`. F2.6: nhật ký Admin lọc thao tác/người/ngày Việt Nam, dữ liệu trước/sau được lọc allowlist; browser đọc log thật fixture và integration bỏ secret đạt. F2.5 còn hook CRUD Phase 3.

F2.6 lưu `9a6e9ab`. F2.1: tích hợp login/logout, Smarty protected shell và dispatcher API, audit auth, cấu hình riêng; browser 3 vai trò đạt. SQL đọc/khởi tạo Admin/verify đã thực thi local; EXPLAIN scoped list/aggregate dùng idx_ita_assets_department (ref, 6 rows). Chi tiết lệnh, rollback và bước host ở PHASE02_VERIFY.md.

Tích hợp F2.1 lưu `6f461ef`. Lần kiểm cuối: 47 browser checks (bổ sung không lỗi JS runtime trên role pages), 32 routing, 8 DB integration đều đạt. Không có thao tác hosting hoặc push. Working tree còn source legacy chưa có baseline như audit trước; không stage mã cũ ngoài phạm vi.

## Sửa F2.4 sau UAT host

User xác nhận login bằng username/Gmail và logout/login trên host thành công. User báo không biết lưu quyền có thành công không. Tái hiện local phát hiện selector bộ phận sai gây lỗi trước POST, lỗi ngoài viewport và cột thao tác admin bị ẩn tại tablet. Đã sửa JS/CSS, version asset trong layout. `tests/ita_access_feedback.js`: 13 checks đạt gồm submit form thật/read-back DB ở 390/768/1366, lỗi validation/mạng/refresh và JS runtime. Host chờ upload 3 file giao diện và UAT lại, không cần SQL mới. Bài học cụ thể ở LESSONS.md; 47 checks cũ không có coverage submit UI này.

## Tiếp tục ngày 19/10/2026

- Rà lại source và trạng thái Git: không có tracked change trước khi tiếp tục; commit gần nhất `9bc9e06` sửa F2.4. Không chạy lại CREATE bảng hoặc dùng credential host.
- Bằng chứng user ngày 16/10: SQL tạo 3 bảng auth thành công; bootstrap store 1/user 111/admin_it/enabled 1; đăng nhập username/Gmail và đăng xuất trên host thành công. Thông báo không thể thu hồi Admin IT cuối cùng xác nhận guard đã phản hồi trên host, không chứng minh lưu quyền tài khoản phụ thành công.
- F2.4 vẫn cần UAT tài khoản phụ trên host; F2.5 cần hook CRUD. Không đánh dấu nghiệm thu Phase 2 toàn bộ.
- Đã đối chiếu schema ita_assets và ma trận quyền 4.1 từ tài liệu nguồn. Chuẩn bị BE_GATE_03.md cho F3.1–F3.8 và F2.5; backend Phase 3 chờ duyệt theo AGENTS mục 2. Không cần chạy lại file SQL Phase 1 đang mở trong IDE.

## Phase 3 — Build đã được duyệt, không tạo DB test

User duyệt «được bắt đầu BUILD» sau yêu cầu không tạo DB test, user sẽ test thủ công host. Agent không tạo/kết nối DB, không chạy lại SQL cũ và không deploy. Mini plan: xây service validation/transaction/audit; nối API + form; kiểm pure/scripted/Smarty/browser mock; review độc lập; đóng gói để user test host.

| Mã | Chức năng | Vùng | Trạng thái | Commit | Verify | Ghi chú |
|---|---|---|---|---|---|---|
| F3.1 | Danh sách phân trang | BE+FE | [~] | nền 9fcc1f2; runtime mới chờ UAT | kiểm mock UI, chưa benchmark | 5 dòng/trang, chưa EXPLAIN mới/500 dòng |
| F3.2 | Tạo tài sản | BE+FE | [~] | chờ UAT DB | pure/scripted + browser payload đạt | Chưa INSERT thật |
| F3.3 | Sửa thông tin | BE+FE | [~] | chờ UAT DB | version và inactive category pure/mock đạt | Chưa test locking/concurrency DB |
| F3.4 | Trạng thái/lý do/ghi chú | BE+FE | [~] | chờ UAT DB | transitions/role pure + form mock đạt | Chưa UPDATE thật |
| F3.5 | Xóa mềm | BE+FE | [~] | chờ UAT DB | admin-only/assigned guard scripted; confirm UI mock | Không hard delete |
| F3.6 | Chi tiết/lịch sử | BE+FE | [~] | chờ UAT DB | tabs/escaping/Smarty/mock đạt | 50 log mới nhất, DB chưa chạy |
| F3.7 | Lọc nâng cao | BE+FE | [~] | chờ UAT DB | query params UI mock đạt | Năm mua dùng range, chưa EXPLAIN |
| F3.8 | Quản lý loại | BE+FE | [~] | chờ UAT DB | form mock, inactive rule pure/scripted | Admin, không xóa cứng |
| F2.5 | Hook audit tài sản | BE | [~] | chờ UAT DB | scripted rollback khi audit error | Chưa chứng minh rollback InnoDB thật |

Kết quả: 33 pure/scripted checks, 28 browser API mock checks, Smarty render/escape, PHP/JS lint đạt; hồi quy static MOCK đạt (console có lỗi mạng được test chủ động tạo). Impeccable detector `[]`; đã xem form mobile. Review Agent phát hiện 1 lỗi inactive category, đã sửa; lượt cuối không thấy lỗi mới. Không đồng nhất test mock với DB integration. Runtime chưa commit hoàn tất chức năng/merge/tag vì tiêu chí SQL/read-back DB còn chờ UAT do user đảm nhận; đã chuẩn bị snapshot bàn giao có hash và checklist PHASE03_HOST_TEST.md.

### Chốt local theo chỉ đạo bổ sung

User yêu cầu chưa upload, không tạo DB và hoàn thành trong phạm vi local; các việc cần DB để user test thủ công sau. Đã bổ sung chặn trạng thái trống tại form, tách tải loại khỏi ghi chú/trạng thái và giữ thông báo lưu khi danh mục tải lỗi. Browser API mock lần cuối **30 checks đạt**; pure/scripted vẫn **33 checks**, Smarty render đạt. Hoàn tất phạm vi local; không tuyên bố DB/production đạt. Snapshot cũ `b965bd30a9b4` không còn cập nhật, chưa dùng để deploy. Chưa mở Phase 4 hoặc yêu cầu thêm DB.


### 21/10/2026 — Sửa POST bị chặn trước dispatcher

[~] F7.1: xác định includes/ita_mode.inc.php thiếu ticket-save trong POST allowlist, trả plain-text 403 trước application. Bổ sung ticket-save và hai endpoint đã duyệt #06 license-save/license-key. Không đổi quyền/CSRF, SQL hoặc config. Verify: php tests/ita_write_routes.php đạt 12 pure routing checks; php tests/ita_phase07_unit.php đạt 236 pure/scripted checks; php -l hai file PHP đạt. Chưa kiểm ghi DB thật, chưa xác định lỗi GET danh sách trên host; Phase 1–7 chưa nghiệm thu DB, không merge Phase7 hoặc bắt đầu Phase8.


### 21/10/2026 — Cải tiến giao diện Bảo trì & hỗ trợ (FE)

[~] F7.1–F7.5 phần trình bày: danh sách rõ mã thiết bị/phiếu, trạng thái Việt hóa, số tiền định dạng chính xác chuỗi DECIMAL; phiếu đang chọn có nền nhấn. Form tạo có lối vào riêng, thu gọn sau lưu thành công. Chi tiết hai cột desktop, một cột mobile; lịch sử giờ VN và mô tả thay đổi dễ đọc; thống kê dạng bảng. Thông báo cố định không kéo màn hình, thành công tự ẩn sau 6 giây khi không có focus, lỗi giữ lại và nút tải lại chỉ GET. Motion 150–180ms tách file, hỗ trợ UI_MOTION_ENABLED=false và prefers-reduced-motion.

Verify local: `playwright-cli -s=ita-phase06 run-code --filename=tests/ita_phase07_browser.js` **43 browser API mock checks đạt**, ba vai trò, viewport1366/768/390 (không tràn và bounds đạt), escaping, payload/CSRF, read-only recovery, giảm motion, DECIMAL lớn. `node --check` hai JS đạt; `php tests/ita_phase03_render.php` Smarty thật render/escape đạt, không DB. Impeccable detector `[]`; đã xem ảnh desktop/mobile. Review Agent phát hiện làm tròn Number ở chi phí lớn, đã sửa định dạng chuỗi và thêm regression. Không PHP/SQL/auth mới, chưa upload/deploy và không dùng mock thay nghiệm thu DB. Chi tiết: docs/PHASE07_UI_VERIFY.md.


### 21/10/2026 — Chọn thiết bị khi tạo phiếu (FE)

F7.1: thêm tìm/chọn theo mã, tên hoặc seri trong form tạo phiếu; dùng GET data q/page sẵn có (5 dòng/trang), tự điền ID và nhãn thiết bị, vẫn cho nhập ID thủ công. Không chọn thiết bị retired; việc đủ điều kiện/pending/store/scope vẫn do service kiểm khi POST. Enter ở ô tìm không submit phiếu. Kết quả rỗng/lỗi có hướng dẫn, phân trang giữ từ khóa, lỗi xóa kết quả cũ; chọn xong thu gọn phần tìm. Không endpoint/PHP/SQL mới, không Phase8/merge/DB/deploy.

Verify: tests/ita_phase07_browser.js **64 API mock checks đạt**, ba vai trò, picker và form đo tại1366/768/390, Enter/empty/403/escaping/retired/pagination/selected/manual-ID và hồi quy thao tác cũ; node --check JS, Smarty render, diff-check đạt; detector[]. Review độc lập không phát hiện lỗi. Chưa kiểm tìm kiếm trên DB host; API data hiện có tính cả summary nên hiệu năng DB vẫn cần UAT, không thêm lookup endpoint chưa duyệt. Gói runtime cộng dồn giao diện giữ7file, xem PHASE07_UI_VERIFY.md.


Checkpoint local #08: `0c2e0a7` — phân tích và giải thích vòng đời thiết bị. Build/verify local đã lưu; DB vẫn CHƯA XÁC NHẬN. Không merge/tag Phase 7 hoặc Phase 8.


### 21/10/2026 — Tối ưu giao diện Vòng đời tài sản

F8.1–F8.4 phần trình bày: tổng quan thành các cặp nhãn/số liệu, rút gọn ngày đánh giá và ghi chú mặc định; bộ lọc bộ phận/loại mở rộng có nút xóa lọc. Ưu tiên điểm/đánh giá ngay sau tên thiết bị, màu thanh theo đánh giá, hiển thị thang điểm của trang. Bảng nhóm chuyển giữa loại/bộ phận từ cùng response, không gọi thêm API. Chi tiết có ba thành phần dễ đọc; điểm gốc và dữ liệu đối chiếu giữ nguyên trong phần mở rộng. Đóng chi tiết trả focus về dòng vừa mở. Motion ngắn chỉ transform/opacity, tôn trọng cờ hiện có và reduced-motion.

Verify: `tests/ita_phase08_browser.js` đạt **63 browser API mock/static checks**, ba vai trò, bộ lọc nâng cao/reset, chuyển nhóm bàn phím không fetch, raw/3 số lẻ, lỗi và retry, focus, reduced-motion, viewport1366/768/390. JS syntax, Smarty render và diff-check đạt; detector[]; đã xem ảnh desktop/mobile. Review Agent không phát hiện lỗi cần sửa. Không đổi PHP/API/công thức/quyền hoặc trạng thái DB. Gói cộng dồn mới: artifacts/ita-phase08/ita-lifecycle-ui-update.zip; chưa upload/deploy, merge hoặc tag.


### 21/10/2026 — BUILD dự báo/ngân sách local, #09 đã duyệt

| Mã | Chức năng | Vùng | Trạng thái | Commit | Verify | Ghi chú |
|---|---|---|---|---|---|---|
| F9.1 | Cấu hình đơn giá/định mức | FE/BE | [x] local | 2e95245 | 134 pure/scripted, browser mock | Chỉ Admin; không DDL/DB thật |
| F9.2 | Ba thành phần và dự phòng 10% | FE/BE | [x] local | 2e95245 | Tính tay tổng 135,3 triệu; decimal chuỗi | Thiếu khác 0; không đếm trùng |
| F9.3 | Mở rộng nhân sự và snapshot | FE/BE | [x] local | 2e95245 | Scripted freeze/recalc/409/audit failure | Finalized chỉ đọc; rollback là double |
| F9.4 | Lọc năm/bộ phận/loại | FE/BE | [x] local | 2e95245 | 42 browser mock tổng, bounds1366/768/390 | Snapshot lọc JSON, không đọc giá mới |
| F9.5 | Excel/PDF ngân sách | FE/BE | [x] local | 2e95245 | XLSX reopen STRING; PDF render/text/ảnh | Host/FPM chưa chạy; vendor deprecation |

134 pure/scripted +42 browser mock/static đạt. Hồi quy 236 ticket +68 lifecycle +12 route, Smarty/syntax/diff-check đạt; browser vòng đời 63 checks đạt. EXPLAIN đã xuất 4 query đọc, chưa thực thi. Rà độc lập đã sửa metadata snapshot/số0/cấu trúc/lọc và tải nhầm HTML. Không tạo/kết nối DB, upload/deploy, merge hoặc tag. DB Phase7/8/9 chưa nghiệm thu. Xem PHASE09_VERIFY.md và PHASE09_HOST_TEST.md.

Giữ nguyên xác nhận: «Phase 1–7 chưa có xác nhận kiểm thử DB chi tiết (CHECK ticket_events, rollback InnoDB, hai phiên đồng thời, phiên bản MySQL/MariaDB, môi trường chưa được cung cấp)». Chỉ cập nhật khi user cung cấp chi tiết.

Ruflo: đã đọc skill gốc và xác nhận CLI3.59.3 bằng --help. Duy trì tài liệu/bài học đã làm sạch; không bật hooks/daemon/swarm hoặc tuyên bố tự học nền. Xem RUFLO_WORKFLOW.md. Lần commit trước không chạy được vì bộ duyệt tự động chạm giới hạn sử dụng, không phải kết luận thao tác không an toàn.

Checkpoint: quy trình Ruflo `50e71c2`; ngân sách local `2e95245`. Lần tiếp tục đã lưu commit thành công. Nhánh feature/ita-phase09-forecast, chưa push/merge/tag/deploy. Không có xác nhận DB mới từ user.

### 21/10/2026 — Yêu cầu trước bước tiếp theo của user

- Đã ghi công thức bảo trì ba năm/chia3 và hai giới hạn (thiết bị mới kéo thấp bình quân, thiết bị sắp thay vẫn tính cả bảo trì và thay thế), mua thêm ceil không trừ tồn kho, dự phòng cố định10% và phê duyệt #09 vào DECISIONS.md.
- Đã thêm checklist host vào DEPLOY_LOG.md: PHP thực của site, display_errors khi xuất, gd/mbstring, isolation RR/SERIALIZABLE và ITA_LICENSE_KEY. Tất cả mục checklist **chưa kiểm**, không coi tài liệu là kết quả host.
- **Phase 1–9: DB chưa nghiệm thu.** Chờ user cung cấp xác nhận kiểm thử DB cụ thể rồi mới cập nhật trạng thái. Không merge/tag Phase7/8/9; không push/deploy.
- **Phase10 BE chưa bắt đầu.** BE_GATE_10.md chỉ là dự thảo theo F10.1–F10.7; chưa viết PHP, endpoint, SQL hoặc nối dữ liệu mới. Chưa cần bổ sung FE/mock trong bước tài liệu này.
- Verify tài liệu: đối chiếu PLAN_FINAL, code nguồn hợp đồng hiện có và diff/UTF-8; không chạy DB/host. Dự thảo gate cần duyệt riêng khi user sẵn sàng; không coi yêu cầu chuẩn bị gate là phê duyệt build.

### 21/10/2026 — Chỉnh gate10 và dọn hồ sơ theo user, chỉ tài liệu

- Gate10 vẫn **chưa duyệt**, BE chưa bắt đầu. Đã ghi Hỗ trợ không export/chỉ bảng bảo trì trong ứng dụng; Excel tồn kho5.000/PDF500/Excel khác1.000, từ chối vượt; định nghĩa vòng đời ngắn12 tháng kèm điểm/tier; chuông là cảnh báo hiệu lực; chung as_of và “số liệu tại HH:mm”; không polling dưới60giây/không tự refresh biểu đồ nặng; dispose ECharts khi đổi lọc/rời trang, mỗi chart kèm bảng.
- DECISIONS chuyển16 mục cũ sang quyết định đã có, ghi ngày rà và nguồn cụ thể; chỉ giữ vấn đề thực còn mở (DB/host, bàn giao, gate10, dependency provenance, nguồn topology13). Không xóa lịch sử hoặc suy các quan sát thành nghiệm thu.
- DEPLOY_LOG thêm bảng host: các thao tác đã thấy được tách khỏi commit/gói upload chưa xác định; gói chỉ local không coi là đã triển khai. Có đề xuất phục hồi PDF từ lock và Excel/font từ baseline/nguồn có hash; kiểm git ls-files cho thấy vendor PDF, Excel legacy và Inter chưa tracked, không nói chúng nằm trong Git.
- Verify chỉ đọc file/schema metadata local, lock, manifest ZIP, git ls-files, hash file/cây và diff/UTF-8. Chưa dựng lại dependency, chưa cài package, chưa chạy code/DB/host hoặc làm FE/mock mới. DB Phase1–9 **chưa nghiệm thu**, không merge/tag7/8/9, không push/deploy.

### 21/10/2026 — F10.1 bản xem thử dashboard FE/mock độc lập

User “tiếp tục” trong phạm vi FE/mock đã cho phép, không phải duyệt BE10. Đã làm trang riêng assets/ita/mock/dashboard.html: KPI/ba biểu đồ có bảng, lọc nhóm/bộ phận và vai trò minh họa, chuông cảnh báo hiệu lực, bảng bảo trì Hỗ trợ, mốc chung giờ VN và làm mới thủ công. Không đổi layout live/PHP/endpoint/SQL. ECharts6.1.0 cục bộ ghim phiên bản, license/notice, nguồn/hash; không CDN runtime. Không polling/export.

Verify: **46 browser static/mock checks đạt**, viewport1366/768/390, keyboard, clock cố định, dispose khi lọc/rời trang, fallback biểu đồ, escaping, source không timer; node syntax và hash manifest đạt. Rà độc lập sửa bộ lọc vai trò lệch nhãn; có regression. Đã xem ảnh desktop/mobile, sửa legend; detector có19 cảnh báo mẫu theme/font/kicker, giữ thiết kế đã chốt và bỏ kicker, không báo detector sạch. Chi tiết PHASE10_FE_PREVIEW.md.

F10.1 FE prototype local đã có; các biểu đồ F10.2–F10.4 mới minh họa, không nghiệm thu chức năng BE. F10.5/F10.6 chưa build; F10.7 chỉ mock. **BE10 chưa duyệt/chưa bắt đầu; DB1–9 chưa nghiệm thu**, không merge/tag7–9/push/deploy. Nhánh local feature/ita-phase10-dashboard-preview, không merge nhánh9.

| Mã | Chức năng | Vùng | Trạng thái | Commit | Verify | Ghi chú |
|---|---|---|---|---|---|---|
| F10.1 | Bản xem thử KPI độc lập | FE/MOCK | [x] prototype | 63ca436 | 46 browser static/mock; syntax/hash staged đạt | Không nối layout live; không BE/DB |
| F10.1–F10.7 | Backend dashboard/báo cáo/cảnh báo | BE | [G] chưa duyệt | Chưa có | Chưa chạy | Gate10 chỉ dự thảo; DB1–9 chưa nghiệm thu |

### 21/10/2026 — Xem trước báo cáo FE/mock độc lập

Tiếp tục theo phạm vi FE/mock, không suy duyệt BE10. Trang riêng assets/ita/mock/reports.html đọc reports.json: bốn bảng, lọc/phân trang, vai trò minh họa, vòng đời ngắn12 tháng có điểm/tier; ngân sách mẫu riêng để đổi giá trong bộ nhớ và so với snapshot giả; bộ kiểm giới hạn5.000/1.000/500 không sinh/tải tệp. Không đổi dashboard live, PHP, SQL hoặc service export9.

Verify: **50 browser static/mock checks đạt**, tính tay/current/snapshot, biên số dòng/ngày, lỗi/retry, missing/0, keyboard/escaping, bounds1366/768/390 và chỉ GET local; node syntax đạt, đã xem ảnh desktop/mobile, rà độc lập không còn finding. Xem PHASE10_REPORT_PREVIEW.md. F10.5/F10.6 chỉ thêm presentation prototype, chưa build xuất Excel/PDF thật; BE10 vẫn [G], DB1–9 chưa nghiệm thu, không merge/tag7–9/push/deploy.

| Mã | Chức năng | Vùng | Trạng thái | Commit | Verify | Ghi chú |
|---|---|---|---|---|---|---|
| F10.5/F10.6 (trình bày) | Xem trước báo cáo và thử giới hạn | FE/MOCK | [x] prototype | 1a7abc0 | 50 browser static/mock + syntax/diff/review đạt | Không sinh tệp; không phải nghiệm thu export hoặc BE |
