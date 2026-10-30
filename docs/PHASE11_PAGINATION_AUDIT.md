# Rà danh sách và phân trang —22/10/2026

## Cập nhật26/10 — Bảng dashboard có điều hướng FE

Ba bảng đối chiếu biểu đồ và bảng đề xuất thay thế20 dòng/trang phía client;44 browser mới +64 hồi quy đạt, xem PHASE11_DASHBOARD_TABLES_VERIFY.md. Chart vẫn toàn tập response, KPI/mốc số liệu giữ nguyên, chuyển trang không fetch hoặc dựng lại chart. Hàng Dashboard groups/replacements trong bảng lịch sử bên dưới chỉ còn đúng về chi phí server/bounded dataset; không còn render toàn bộ dòng bảng cùng lúc. Đây không phải SQL pagination: server vẫn đọc/tính/trả cùng tập có guard. Chưa đo SQL/payload/DB/host hoặc nâng giới hạn.

## Cập nhật23/10 — Dòng ngân sách có điều hướng FE, BE vẫn mở

Bảng ngân sách hiện20 dòng/trang phía client, giữ tổng/snapshot/export toàn nguồn;24 browser mới +42 hồi quy đạt, PHASE11_FORECAST_ROWS_VERIFY.md. Server vẫn tính/trả toàn bộ rows trong giới hạn1000 dòng/2000 asset; chưa LIMIT/OFFSET SQL hay giảm payload. Hàng Dòng forecast trong bảng dưới vẫn là việc tối ưu BE còn mở, không đánh dấu SQL pagination đã xong.

## Cập nhật23/10 — Nhóm vòng đời có điều hướng FE, BE vẫn mở

Loại thiết bị/Bộ phận hiện hiển thị20 nhóm/trang phía client, nhớ trang riêng và không đổi tổng quan/mẫu số.30 browser mới +63 hồi quy đạt, PHASE11_LIFECYCLE_GROUPS_VERIFY.md. Server vẫn aggregate/trả toàn bộ nhóm như trước; đây không phải LIMIT/OFFSET SQL, chưa tối ưu payload/SQL hoặc nghiệm thu DB. Hàng nhóm vòng đời trong bảng dưới vẫn là việc BE còn mở; không đánh dấu toàn bộ phân trang nhóm đã xong.

## Cập nhật23/10 — Danh sách Quản lý loại Admin đã phân trang local

User duyệt riêng; categories paged=1 có COUNT/search literal/LIMIT20/OFFSET, sort name,id và UI nav cho Admin.69 scripted +24 browser mới và hồi quy đạt, PHASE11_CATEGORY_PAGINATION_VERIFY.md. Hàng Danh mục bên dưới chỉ còn đúng với lookup/dropdown mảng guard500; management paginated đã tách contract. Không coi toàn bộ lookup đã phân trang, chưa DB/EXPLAIN/host.

## Cập nhật23/10 — Lịch sử tài sản đã phân trang local

User duyệt riêng; GET asset đã COUNT + LIMIT20/OFFSET, sort id DESC và nav trong tab Lịch sử; guard scope trước history, FE chặn response muộn.174 query-contract +43 browser mới và hồi quy đạt, PHASE11_ASSET_HISTORY_VERIFY.md. Hàng «Lịch sử tài sản LIMIT51» bên dưới là snapshot22/10 trước thay đổi. Chưa DB/EXPLAIN/host; các danh sách khác giữ trạng thái chưa build.

## Cập nhật23/10 — Thống kê ticket đã phân trang local

User duyệt riêng; ItaTicketObjects::statistics đã COUNT nhóm/tổng toàn tập và LIMIT20/OFFSET bind, sort asset/type/status; FE Trước/Sau giữ bộ lọc.190 scripted +43 browser mới và hồi quy đạt, PHASE11_TICKET_STATISTICS_VERIFY.md. Chưa DB/EXPLAIN/host; phần thống kê trong bảng lịch sử22/10 dưới đây là hiện trạng trước sửa. Các danh sách còn lại vẫn chưa mở gate/build, không coi hoàn tất toàn audit.

**Chỉ đọc source**, không sửa PHP/SQL, không chạy DB. Rà cùng Review Agent theo AGENTS. “Có phân trang SQL” nghĩa là count + page/per_page/total + truy vấn LIMIT/OFFSET, không phải chỉ có LIMIT hoặc render vài dòng. Đây là audit hiện trạng, chưa nghiệm thu persistence/performance.

## Đã có phân trang SQL và UI gọi trang

| Danh sách | Nguồn SQL | UI | Hiện trạng |
|---|---|---|---|
| Tài sản / bộ chọn thiết bị | itaassetobjects.class.php:107–112 | ita-shell.js / ita-tickets.js | COUNT +5 dòng/page; bộ chọn gửi page, không tải mọi asset |
| User | itauserobjects.class.php:31–36 | ita-admin.js | COUNT +20/page |
| Nhật ký | itaauditservice.class.php:57–62 | ita-audit.js | COUNT +20/page |
| Lịch sử phân bổ | itaassignmentobjects.class.php:24–31 | ita-assignments-live.js | COUNT +20/page |
| Bộ chọn người nhận | itaassignmentobjects.class.php:48–53 | ita-assignments-live.js | COUNT +20/page và tìm kiếm |
| License / allocations | italicenseobjects.class.php:23–40 | ita-licenses.js | COUNT +20/page cho từng danh sách |
| Ticket / lịch sử gộp | itaticketobjects.class.php:49–62 | ita-tickets.js | COUNT +20/page, gộp event và audit |
| Kỹ thuật viên | itaticketobjects.class.php:82–85 | ita-tickets.js | COUNT +20/page |
| Vòng đời danh sách chính | italifecycleobjects.class.php:74–93 | ita-lifecycle.js | COUNT +20/page, sort score gốc |

## Còn thiếu hoặc chưa phải SQL pagination

| Mức ưu tiên | Danh sách / nguồn | Cách làm hiện tại | Hạn chế / đề xuất để gate BE |
|---|---|---|---|
| Cao | Thống kê ticket — itaticketobjects.class.php:104; ita-tickets.js | GROUP BY asset/type/status, không LIMIT/cap; UI render hết rows | Có thể lớn theo số asset. Cần tổng hợp/tổng số nhóm, sort ổn định và SQL pagination hoặc giới hạn có lỗi rõ; client pagination không giảm truy vấn/payload |
| Cao | Lịch sử tài sản — itaassetobjects.class.php:123–127; ita-assets.js:92–98 | LIMIT51, trả50 mới nhất +history_has_more | Có dấu hiệu còn dữ liệu nhưng không tải trang cũ tại detail; đường chỉ dẫn nhật ký Admin không thay lịch sử đủ cho Quản lý/Hỗ trợ |
| Vừa | Danh mục — itacategoryservice.class.php:10–12 | LIMIT501, vượt500 trả422; danh sách/dropdown render cả tập | Có guard, không cắt ngầm, nhưng chưa search/page đúng nghĩa |
| Vừa | Bộ phận/lookup scope — itaassetobjects.class.php:35–39 | LIMIT501, vượt500 trả lỗi; dropdown/checkbox theo scope | Không có page/search; user ít bộ phận không đồng nghĩa đã giải quyết quy mô lớn |
| Vừa | Nhóm loại/bộ phận vòng đời — italifecycleobjects.class.php:78–79 | Aggregate mọi nhóm, FE render toàn bộ; loại lookup501 fail | Danh sách asset chính đã phân trang; bảng nhóm thì chưa. Cần giới hạn/điều hướng nhóm rõ, không lấy trung bình của trung bình |
| Vừa | Forecast config/department/plans — itaforecastobjects.class.php:11/37/56 | Các tập tối đa500, vượt trả lỗi; select/config/table render toàn bộ | Không phải pagination; cần contract riêng cho lookup/kế hoạch, không thay snapshot đã lưu |
| Vừa | Dòng forecast — itaforecastobjects.class.php:121–125; ita-forecast.js | Tối đa1.000 dòng/2.000 asset, vượt lỗi, render toàn bộ | Guard xuất/tính không phải phân trang hiển thị. Phân trang không được thay mẫu số/tổng hoặc nội dung snapshot |
| Vừa | Dashboard groups/replacements — itadashboardobjects.class.php:23–73 | Fetch tối đa5.001, vượt5.000 lỗi; nhóm>500 lỗi; render tất cả nhóm/thay thế | Bounded nhưng chưa page bảng thay thế/nhóm. Không âm thầm lấy topN làm toàn bộ số liệu |
| Vừa | Alerts #10 / report-preview — itadashboardobjects.class.php:82–88; itareportservice.class.php:71 | Server cắt mảng20 dòng sau khi đã đọc/tính toàn dataset có guard | UI có page/total thật, nhưng **không phải LIMIT/OFFSET SQL**; page2 vẫn có chi phí dựng cả tập. Cần đo trước khi chọn SQL pagination, không sửa công thức/scope |
| Thấp / cần ghi nhãn | Summary cũ — itaassetobjects.class.php:137–155 | Top8 bộ phận, top10 cảnh báo; waitingAlerts ticket:70 top10; licensealerts:55 top20 | Không coi là trang danh sách đầy đủ. Top8 bộ phận chưa có “khác”/has_more; các alert preview có total. #10 đã có danh sách riêng nhưng vẫn bounded-array pagination |

Query LIMIT1 để tìm assignment đang hiệu lực/guard, LIMIT2 kiểm định danh đăng nhập không phải danh sách cần phân trang. Giới hạn export5.000/1.000/PDF500 và guard dataset không phải pagination; không gỡ giới hạn để giả hoàn thành audit.

itauserobjects.class.php:access đọc toàn bộ department_ids đang active của một user cho kiểm quyền/checkbox. Đây là tập membership phục vụ xác thực, không được cắt riêng một trang rồi coi là toàn scope. Danh sách bộ phận để lựa chọn vẫn thuộc nhóm lookup cần rà riêng ở trên.

## Điều kiện chấp nhận cho đề xuất bổ sung (chưa build)

- Scope/store/auth kiểm trước count, aggregate và page; total cùng bộ lọc. Sort ổn định có ID tie-break; trang rỗng/ngoài phạm vi và dữ liệu giảm phải được xử lý rõ.
- Kiểm với dữ liệu ít/nhiều/biên trang, hai user khác scope và filter đổi. Không lấy việc chỉ có nút Trước/Sau làm chứng minh backend đã page.
- Lookup search/page không được bỏ danh mục inactive đang tham chiếu ở hồ sơ hiện tại. Không tải danh sách user toàn store rồi lọc quyền phía client.
- Với aggregate/snapshot, tách total toàn tập với rows của trang; không cộng riêng trang thành tổng ngân sách hoặc tính trung bình của trung bình.
- Mọi PHP/endpoint/index mới chờ BE_GATE_11.md được duyệt. Index cụ thể cần gate SQL riêng sau EXPLAIN DB test; không suy index tốt từ source.

Chưa sửa BE hoặc ghi PASS DB. Giữ không merge/tag7–10/push/deploy.
