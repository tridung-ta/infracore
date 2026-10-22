# BE-GATE #11 — Tối ưu, bảo mật, hoàn thiện (NHÁP, CHƯA DUYỆT)

Theo chỉ đạo22/10: **chỉ soạn tài liệu, chưa build BE**. Không coi việc làm FE/tài liệu hoặc user duyệt #10 là duyệt gate này. Không tự chạy DB/EXPLAIN, cài package, sửa PHP/session/server config. Không merge/tag Phase7–10/push/deploy. DB/host Phase1–10 chưa nghiệm thu.

## Phạm vi đề xuất

- F11.1: đo và đối chiếu query/index theo EXPLAIN trên DB test được xác nhận; kế hoạch PHASE11_LOAD_TEST_PLAN.md. Chưa có index cụ thể được đề xuất khi chưa có bằng chứng.
- F11.2: rà auth/scope/CSRF/upload/export, cookie phiên và security headers theo runtime thật. Source ItaSession hiện đã có strict_mode/only_cookies/HttpOnly, Secure theo HTTPS, SameSite=Lax; **không mô tả chúng là chưa có hoặc tự đổi chính sách**. Cần kiểm headers/cookie thực tế trên8.3.33/HTTPS/proxy phù hợp.
- Bổ sung phân trang theo PHASE11_PAGINATION_AUDIT.md, ưu tiên thống kê ticket và lịch sử tài sản; thiết kế count/scope/sort trước khi code. Bounded-list/array pagination được ghi đúng hiện trạng, không thay guard bằng cắt ngầm.
- F11.4 BE nếu được duyệt sau: nối HTTP status/error envelope/trang lỗi với dispatcher khi cần; FE template hiện có không chứng minh status/routing server.

## Thay đổi dự kiến (chưa thực hiện)

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
