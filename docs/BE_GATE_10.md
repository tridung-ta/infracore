# BE-GATE #10 — Dashboard và báo cáo (dự thảo, chưa duyệt)

Chỉ chuẩn bị phạm vi theo PLAN_FINAL.md. **Chưa bắt đầu BE Phase 10, không build trong bước này.** DB Phase 1–9 vẫn **chưa nghiệm thu**, chờ xác nhận chi tiết từ user; không merge/tag Phase 7, 8, 9. Phê duyệt #09 không bao gồm #10.

## Phạm vi theo plan

| Mã | Chức năng dự kiến |
|---|---|
| F10.1 | Dashboard KPI |
| F10.2 | Biểu đồ tình trạng theo loại, bộ phận, năm mua |
| F10.3 | Biểu đồ tỷ lệ lỗi |
| F10.4 | Biểu đồ thiết bị sắp thay thế |
| F10.5 | Excel: tồn kho, sắp bảo trì, vòng đời ngắn, ngân sách |
| F10.6 | PDF các báo cáo tương ứng |
| F10.7 | Trung tâm cảnh báo: chuông và trang danh sách |

## Thay đổi dự kiến, chỉ thực hiện sau duyệt riêng

- DAO ItaDashboardObjects/ItaDashboardObjectInfo; rules/controller báo cáo và cảnh báo riêng; nối bootstrap/ItaApplication. Tận dụng hợp đồng asset, ticket, license, lifecycle và forecast hiện có, không viết lại công thức.
- GET dashboard, dashboard-charts, alerts, report-preview; POST report-export (Excel/PDF) có CSRF. Tên action dự kiến, sẽ đối chiếu giới hạn dispatcher trước build. JSON success/data/message; prepared params, store/role/scope kiểm server trước count, aggregate, pagination và export.
- Nếu tái sử dụng endpoint hiện có cần thay PHP ngoài phần đọc đơn thuần, thay đổi đó thuộc #10, không coi là đã được #09 duyệt. Bổ sung outer POST allowlist chỉ action đã duyệt và audit allowlist cho xuất báo cáo.
- Template/JS/CSS riêng, biểu đồ ECharts cục bộ đã được cho phép trong AGENTS; bảng đối chiếu và trạng thái thiếu dữ liệu đi cùng biểu đồ. Không CDN hoặc Composer mới. Trong lúc chưa duyệt chỉ FE/mock độc lập có nhãn MOCK, không nối endpoint mới.

Migration: **không dự kiến DDL, seed hoặc SQL host**. Đọc bảng hiện có; chỉ ghi audit xuất báo cáo sau duyệt, không ghi bảng dc_, không sửa snapshot/config/giá/thiết bị. Cần bảng/cột/index mới phải dừng và trình gate SQL riêng. Không tự lưu trạng thái đã đọc/đã xử lý cảnh báo, không cron/email/push notification.

## Giả định cần user duyệt trước build BE

1. **Quyền:** Admin IT xem toàn store; Quản lý xem bộ phận trong scope. Hỗ trợ **không export ở #10**, chỉ xem bảng bảo trì được phép trong ứng dụng; không mở rộng quyền KPI/tài sản hiện có từ gate này, không KPI chi phí, báo cáo ngân sách/vòng đời hoặc key giấy phép. Quyền xử lý phiếu đã duyệt ở #07 không bị thay bởi tài liệu này. Mọi endpoint export phải từ chối Hỗ trợ ở server. URL trực tiếp ngoài scope403, không chỉ ẩn menu.
2. **Mẫu số dashboard:** tập tài sản chưa xóa mềm trong scope, có cả retired nếu biểu đồ hiển thị trạng thái đó. KPI “đang quản lý” loại retired và ghi rõ định nghĩa, không coi nó bằng tổng sổ tài sản. Phân tích vòng đời/tỷ lệ lỗi dùng tập phần cứng chưa retired theo #08, có nhãn mẫu số riêng. Thiếu loại/bộ phận/năm mua vào nhóm “Chưa ghi nhận”, không biến NULL thành năm0. Lookup inactive đang được tham chiếu vẫn được đếm. Không JOIN ticket/event làm nhân số tài sản.
3. **Tỷ lệ lỗi:** theo đúng #08: distinct asset có ít nhất một ticket repair mở trong 12 tháng lịch tới hết ngày đánh giá VN / tổng asset thuộc tập phân tích. Tất cả trạng thái ticket, mở lại không tăng số. Tuổi trung bình chỉ lấy ngày mua hợp lệ, công khai số thiếu; không lấy trung bình của trung bình nhóm.
4. **Sắp thay thế và vòng đời ngắn:** biểu đồ đề xuất thay thế dùng tier replace của #08, điểm gốc so/sắp xếp, hiển thị3 số lẻ và hỗ trợ điểm>1. Theo yêu cầu chỉnh tài liệu của user ngày21/10, “vòng đời ngắn” = **đã tới hoặc còn tối đa12 tháng lịch tới tuổi thọ**, kèm cột điểm và tier. Tính mốc từ ngày mua/tuổi thọ loại, xử lý ngày cuối tháng; thiếu dữ liệu đánh dấu chưa đủ, không suy từ tên loại. Điều kiện này đã được chốt cho dự thảo, không đồng nghĩa duyệt build BE.
5. **Sắp bảo trì và cảnh báo:** đề xuất cửa sổ sắp bảo trì tối đa30 ngày, tách quá hạn, dùng lịch đã duyệt #07/#07-SCHEDULE. Cảnh báo bảo hành/giấy phép dùng contract ngưỡng hiện có; chờ Admin xác nhận sẵn sàng dùng #07. Mỗi lý do có khóa entity_type/entity_id/reason, một thiết bị có nhiều lý do vẫn hiển thị riêng. **Chuông = số cảnh báo đang hiệu lực trong quyền xem, không phải số chưa đọc hoặc số thiết bị**; giao diện ghi rõ “Cảnh báo đang hiệu lực”. Danh sách có severity, bộ lọc, phân trang; không lộ key hoặc cảnh báo ngoài scope. Không thêm trạng thái đã đọc/ngày đọc.
6. **Báo cáo ngân sách:** tái sử dụng #09, ba thành phần và dự phòng10%; công khai thiếu dữ liệu và nguồn dự báo hiện tại/snapshot. Xuất kế hoạch đã lưu chỉ đọc snapshot theo quyền hiện tại, không tính lại bằng giá/ticket mới. Không chốt/mở lại hoặc sửa snapshot; không cộng lẫn dự báo hiện tại và snapshot thành một tổng.
7. **Xuất báo cáo:** Excel tồn kho tối đa **5.000 dòng** theo Phase4; mọi PDF tối đa **500 dòng**; các báo cáo Excel khác tối đa **1.000 dòng**. Đây là số dòng dữ liệu, không gồm tiêu đề/tổng; PDF500 được ưu tiên bất kể loại báo cáo. Vượt giới hạn phải từ chối, yêu cầu thu hẹp bộ lọc, không cắt ngầm. Excel mọi văn bản ghi STRING, kiểm chuỗi đầu =,+,-,@; PDF tiếng Việt, chặn tài nguyên ngoài. Cùng phạm vi, bộ lọc, thời điểm và định nghĩa tổng giữa preview/bảng/file; ghi rõ báo cáo hiện tại không phải snapshot đã chốt. Kiểm lại actor/scope trước bytes, audit thành công trước trả file; lỗi audit fail-closed. Cache-Control no-store/private, kiểm MIME và chữ ký file ở FE. Giới hạn #10 này không phải quyền sửa exporter #09 khi gate chưa duyệt.
8. **Một mốc số liệu:** toàn dashboard nhận cùng as_of do server chọn một lần theo giờ VN; mọi KPI/biểu đồ/bảng/cảnh báo dùng mốc đó. Hiển thị “số liệu tại HH:mm” (giờ VN), response giữ timestamp đầy đủ ngày/múi giờ. Ưu tiên tính trong cùng request; nếu phải tách, phải truyền và kiểm cùng mốc, không lấy now riêng cho từng biểu đồ. Mốc thời gian chung không tự chứng minh dữ liệu nhất quán khi có ghi đồng thời; thiết kế đọc nhất quán và đối chiếu DB là việc cần kiểm sau duyệt.
9. **Làm mới và vòng đời biểu đồ:** không polling dưới60 giây, không tự làm mới biểu đồ nặng; ưu tiên nút làm mới do user chủ động. Không gọi lại khi tab ẩn/rời trang, không chồng request; dữ liệu lỗi ghi rõ và giữ mốc số liệu cũ nếu vẫn hiển thị. ECharts phải dispose khi đổi bộ lọc hoặc rời trang, hủy request/listener/timer liên quan; mỗi biểu đồ có bảng đối chiếu cùng dữ liệu. Chỉ dựng lại khi cần, tôn trọng reduced-motion.

Các chỉnh sửa1–9 trên là nội dung tài liệu theo user; **BE-GATE #10 vẫn chưa duyệt**, chưa viết BE. FE/mock độc lập nhãn MOCK được phép nếu cần.

## Rủi ro, kiểm chứng, backup và rollback

- Rủi ro chính: lệch mẫu số, aggregate nhân dòng, tổng badge lệch danh sách, lộ KPI chi phí qua JSON/biểu đồ, snapshot ngân sách bị tính lại, dữ liệu thiếu hiển thị thành0 và thời gian VN/UTC ở ngày biên.
- Sau khi duyệt: pure/scripted, browser API mock ba vai trò, responsive1366/768/390, bàn phím/reduced-motion, lỗi/empty/403; render/reopen Excel/PDF dữ liệu giả, rà độc lập. Xuất chính xác query aggregate cho user EXPLAIN; không tự tuyên bố index đạt.
- Chưa tạo/kết nối DB hoặc deploy. User cung cấp xác nhận kiểm thử DB cụ thể Phase1–9 trước khi cập nhật trạng thái nghiệm thu; không dùng mock thay DB PASS. Điều kiện bắt đầu BE #10 ngoài duyệt gate phải được user xác nhận rõ sau bước chờ này.
- Trước triển khai: user xác nhận môi trường, backup source/DB/khóa riêng, kiểm checklist DEPLOY_LOG. Rollback source giữ audit, config, plans/snapshot và dữ liệu mới; không DROP/reset/restore DB đè dữ liệu phát sinh.

Trả lời khi user sẵn sàng duyệt: **OK BE Phase 10 / OK với chỉnh sửa / Không**. Hiện chỉ chuẩn bị tài liệu; chưa yêu cầu bắt đầu hoặc thực hiện BE #10.
