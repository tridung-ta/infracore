# BE-GATE #11 — Tối ưu, bảo mật, hoàn thiện (NHÁP, CHƯA DUYỆT)

Theo chỉ đạo22/10: **chỉ soạn tài liệu, chưa build BE**. Không coi việc làm FE/tài liệu hoặc user duyệt #10 là duyệt gate này. Không tự chạy DB/EXPLAIN, cài package, sửa PHP/session/server config. Không merge/tag Phase7–10/push/deploy. DB/host Phase1–10 chưa nghiệm thu.

## Phạm vi đề xuất

- F11.1: đo và đối chiếu query/index theo EXPLAIN trên DB test được xác nhận; kế hoạch PHASE11_LOAD_TEST_PLAN.md. Chưa có index cụ thể được đề xuất khi chưa có bằng chứng.
- F11.2: rà auth/scope/CSRF/upload/export, cookie phiên và security headers theo runtime thật. Source ItaSession hiện đã có strict_mode/only_cookies/HttpOnly, Secure theo HTTPS, SameSite=Lax; **không mô tả chúng là chưa có hoặc tự đổi chính sách**. Cần kiểm headers/cookie thực tế trên8.3.33/HTTPS/proxy phù hợp.
- Bổ sung phân trang theo PHASE11_PAGINATION_AUDIT.md, ưu tiên thống kê ticket và lịch sử tài sản; thiết kế count/scope/sort trước khi code. Bounded-list/array pagination được ghi đúng hiện trạng, không thay guard bằng cắt ngầm.
- F11.4 BE nếu được duyệt sau: nối HTTP status/error envelope/trang lỗi với dispatcher khi cần; FE template hiện có không chứng minh status/routing server.

## Thay đổi dự kiến (chưa thực hiện)

### Phát hiện từ rà chỉ đọc F11.2 ngày22/10 — chưa sửa

S11-01 (PHASE11_SECURITY_REVIEW.md): forecast-export render xong chỉ kiểm lại mapping ITA/revision/scope qua ItaForecastService::lock/auditExport, chưa kiểm lại dc_users active cùng store trước audit/bytes. Đề xuất bổ sung recheck native tại ranh giới export tương tự report-export/handover, từ chối nếu tài khoản bị disable trong lúc render; test scripted giả lập thay đổi trong render và DB thật sau khi được duyệt. Không coi rollback InnoDB bảo vệ bảng native MyISAM. Chưa đổi PHP, chưa thực hiện gate này.

**Bản sửa dự kiến S11-01:** đọc lại native đúng actor.id +store_id và status=active sau render, trước ghi audit thành công và trước trả bất kỳ bytes file; không tồn tại/disable/store khác hoặc lỗi kiểm tra thì fail-closed, bỏ file đã render. Giữ kiểm role/enabled/revision/scope hiện có, không dùng audit thay kiểm native. Test bắt buộc (chưa viết/chạy): render bình thường→file/audit đúng một lần; native bị disable trong render→không bytes file, không audit export thành công; native mất/store đổi→từ chối; query/audit lỗi→không bytes; mapping/scope đổi→vẫn từ chối. Response lỗi kiểm soát không được mang MIME/chữ ký file. Sau khi duyệt mới bổ sung scripted test, DB concurrency thật vẫn cần môi trường được xác nhận.

**Đối chiếu đường xuất khác (chỉ đọc):** report-export có recheck native/role/revision/scope ID sau render, assignment-pdf còn đọc lại asset/document. asset-export và import-errors không có recheck sau render hoặc audit export; import-template cũng không nhưng chỉ là mẫu trống. Đề xuất xét S11-02 cho hai đường có dữ liệu (recheck actor/scope/batch owner-revision trước bytes, xác định audit phù hợp); mẫu trống tách mức rủi ro thấp, chưa mặc định mở rộng audit. report-export chưa đối chiếu lại từng asset chuyển bộ phận trong lúc render; cần chốt semantics snapshot/phạm vi và test riêng, không tuyên bố đã kiểm ownership đầy đủ. Tất cả đề xuất này **chưa duyệt/chưa sửa**, bảng bằng chứng tại PHASE11_SECURITY_REVIEW.

**Bề mặt legacy:** PHASE11_LEGACY_SURFACE.md + CSV2.092 path ghi direct guard khác với bảo vệ có điều kiện qua .htaccess. Điểm mở: MOCK JSON đang allowlist; tests/artifacts không deny prefix tường minh; project-start.php không có gate riêng; auto_prepend rỗng, direct module/upload/library phụ thuộc rewrite. Cần chốt hardening deny trên host và ngoại lệ runtime trước duyệt; không sửa PHP/rule hoặc tự xóa source legacy trong bước này.

F11.4 chỉ **một phần FE**. Phần BE còn lại cần thiết kế trang lỗi HTTP404/500/JSON theo loại request, mã HTTP thật, correlation/log vận hành không chứa secret và chính sách display_errors/log_errors (kể cả lỗi trước bootstrap). ITA module đã có display_errors=0, CSP/no-store/nosniff/X-Robots-Tag; không thêm lại như thể đang thiếu. Header cuối và cấu hình log host chưa xác minh. Không tự thay .htaccess/PHP/server config trong bước tài liệu.

- PHP DAO/services/controllers liên quan ticket statistics, asset history, lookup/aggregate/report pagination; ItaApplication/outer allowlist chỉ nếu thực sự cần endpoint mới và đã liệt kê action cụ thể trước build.
- ItaSession/auth/config middleware và entrypoints nếu kiểm chứng cần sửa cookie/security headers. CSP phải kiểm asset local/ECharts/PDF/download và form; không tự bật một policy gây chặn UI. HSTS chỉ sau xác nhận HTTPS/chứng chỉ/subdomain; không tin X-Forwarded-Proto từ nguồn chưa được xác minh.
- Không đổi compiler/vendor, không Composer mới trong gate dự kiến này. Instrumentation đo PHP memory phải private/tắt mặc định, được duyệt rõ; không tạo endpoint debug công khai.

## Migration, rủi ro và hoàn tác

**Chưa có DDL/seed/migration.** Index mới sẽ có gate SQL riêng với bảng/cột/thứ tự, index hiện tại, EXPLAIN, ảnh hưởng ghi, backup và hoàn tác. Không ALTER engine/schema cũ, không DROP/reset/restore đè dữ liệu.

Rủi ro: aggregate/count lệch scope, pagination làm đổi tổng/snapshot, lỗi cookie gây mất phiên, headers chặn library/download, đo tải ảnh hưởng hệ thống dùng chung, PHP8.5 PASS không bảo đảm8.3.33. Chỉ DB test cô lập sau xác nhận môi trường; user backup source/DB/khóa riêng. Rollback code/config về bản backup, giữ audit/dữ liệu mới; thao tác DB cụ thể không suy từ rollback source.

## Điểm cần xác nhận trước khi trình duyệt chính thức

1. Môi trường DB test và nguồn xác nhận PHP8.3.33; có sẵn runtime tương thích hoặc cách test CI/build, không tự cài runtime.
2. Thứ tự/lượng danh sách cần bổ sung theo inventory; số dòng/trang/search và total thống nhất. Không mở rộng scope hoặc quyền của3 vai trò.
3. Nguồn bằng chứng isolation của **kết nối ứng dụng**, EXPLAIN/query timings/memory; chưa đo thì ghi chưa đo.
4. Chính sách cookie/header thực tế mong muốn và cấu hình HTTPS/proxy hiện có; không tự siết HSTS/SameSite/CSP khi chưa biết tác động.
5. Giới hạn tải và SLA; có cho instrumentation private hay dùng observer có sẵn; dataset/backup/cleanup do ai thực hiện trên DB test.

Khi các contract trên đã cụ thể, cập nhật draft và trình user **OK / OK với chỉnh sửa / Không**. Hiện **không yêu cầu user duyệt ngay, chưa build BE**. FE responsive/trang lỗi và hướng dẫn sử dụng được làm độc lập, không thay PHP/DB.
