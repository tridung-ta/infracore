# BE-GATE #09 — Dự báo và ngân sách (đã duyệt kèm bổ sung)

Phạm vi: F9.1 cấu hình định mức/tham số; F9.2 ngân sách bảo trì và thay thế; F9.3 mua thêm theo kế hoạch tăng nhân sự; F9.4 lọc năm/bộ phận/loại; F9.5 xuất Excel/PDF. Nền: vòng đời local 0c2e0a7 và giao diện 1691c43. User xác nhận giao diện ổn, không phải xác nhận kiểm thử DB. Giữ nguyên PROGRESS: Phase1–7 chưa có xác nhận DB chi tiết; Phase7/8 chưa nghiệm thu DB, không merge/tag.

## Thay đổi dự kiến

- ItaForecastObjects/ItaForecastObjectInfo; rules, config service, plan service và exporter riêng. Nối bootstrap, ItaApplication, audit allowlist và outer POST allowlist chỉ cho các action đã duyệt.
- GET forecast, forecast-config, forecast-plans; POST forecast-config-save, forecast-plan-save, forecast-export (Excel/PDF). JSON success/data/message, POST+CSRF, prepared statements, actor/store/scope/version.
- Chỉ Admin sửa tham số toàn store và đơn giá theo loại; Admin/Quản lý sửa kế hoạch draft trong scope, Hỗ trợ không xem/sửa/xuất dự báo. Mọi endpoint kiểm server; URL trực tiếp ngoài scope403. Không ghi bảng dc_.
- Cấu hình: ita_settings lưu định mức thiết bị/người và tỷ lệ bảo trì dự phòng theo category ID, budget_contingency_percent hiện có. Đơn giá dùng ita_asset_categories.default_replacement_cost. Kế hoạch dùng ita_budget_plans(year, department_id, planned_headcount_increase, note). Writer có khóa/version, audit cùng transaction InnoDB; lỗi audit rollback; không xóa cứng.
- Export tái sử dụng PhpSpreadsheet và mPDF đã có, không Composer mới. Kiểm quyền/phạm vi lại, audit thành công trước trả file; lỗi audit không xuất. File ghi ngày tính, bộ lọc, từng thành phần, dữ liệu thiếu, nguồn mặc định và tổng đối chiếu. Không nhúng secret hoặc tải tài nguyên ngoài.

Migration: **không dự kiến DDL, seed hoặc SQL host**. Chỉ UPDATE/INSERT có kiểm soát các trường/bảng nêu trên sau khi duyệt gate; không chạy lại CREATE hoặc tự điền hàng loạt. Nếu cần cột/index thì dừng, trình gate SQL riêng. Bảng finalized hiện có giữ nguyên, không đổi snapshot hoặc mở lại trong gate này.

## Rủi ro và backup

Sai mẫu số bảo trì, giả lịch sử tồn kho, thiếu đơn giá, làm tròn tiền, đếm trùng thiết bị, hai người sửa cùng kế hoạch, xuất ngoài scope và audit lỗi. Pure/scripted/browser mock local, file Excel/PDF thật bằng dữ liệu giả; không tạo/kết nối DB hoặc deploy. Xuất chính xác query aggregate cho user EXPLAIN. User backup source/DB/keys trước triển khai; rollback source giữ dữ liệu kế hoạch/config/audit đã ghi, không DROP/restore đè. Không cron/email, mua hàng tự động hoặc AI dự báo.

## Giả định cần xác nhận

1. Dự báo dùng dữ liệu hiện tại, cho năm hiện tại đến2200 theo giờ VN; không coi tính lại năm cũ là snapshot báo cáo chốt. Các thiết bị phần cứng chưa retired/xóa mềm là tập phân tích; bộ lọc và scope áp dụng đồng nhất cho danh sách, tổng, mẫu số và export.
2. Thay thế: thiết bị có điểm gốc hiện tại >= ngưỡng thay thế đã cấu hình của Phase8 **hoặc đã/chạm tuổi thọ trước hoặc đến cuối năm chọn**. Thiết bị đạt cả hai chỉ tính một lần. Không tự dự báo điểm tương lai từ ticket chưa có. Giá thay thế lấy default_replacement_cost của loại, không tự lấy giá mua cũ như báo giá mới. Thiếu giá/tuổi/điểm cần thiết được đánh dấu chưa đủ dữ liệu, không âm thầm tính0 và báo tổng hoàn chỉnh.
3. Bảo trì: dùng chi phí hiện tại của các phiếu repair và periodic resolved/failed, mở trong3năm lịch hoàn chỉnh trước năm hiện tại (mốcVN, querybiênUTC). Mẫu số đề xuất =3 × số thiết bị hiện còn dùng cùng loại/phạm vi; trung bình mỗi thiết bị mỗi năm nhân số thiết bị hiện tại. Ghi rõ đây là ước tính theo tập thiết bị hiện tại, không giả snapshot tồn kho lịch sử. Phiếu mở lại pending không được coi là chi phí đã đóng; không JOIN events để nhân SUM.
4. Không có phiếu trong cửa sổ thì dùng tỷ lệ bảo trì dự phòng do Admin cấu hình × đơn giá × số thiết bị. Có phiếu chi phí0 là dữ liệu0 hợp lệ, không đồng nghĩa thiếu lịch sử. Tỷ lệ/đơn giá chưa được đặt thì báo chưa đủ dự toán; không tự bịa một tỷ lệ hoặc đơn giá.
5. Mua thêm: ceil(nhân sự tăng × định mức thiết bị/người) theo từng bộ phận/loại, rồi nhân đơn giá. Nhân sự tăng0 hoặc định mức được đặt rõ bằng0 tạo nhu cầu0 hợp lệ; thiếu định mức không tự coi là0. Không tự trừ tồn kho để đổi công thức gốc. Chưa có dòng kế hoạch năm/bộ phận thì hiện chưa lập kế hoạch, không tuyên bố nhân sự tăng0 đã được xác nhận.
6. Dự phòng mặc định10% theo plan khi key vắng, có ghi chú; key có nhưng sai trả lỗi. Tỷ lệ bảo trì/định mức không có mặc định trong Word nên yêu cầu cấu hình; không fallback giả. Trọng số/ngưỡng vòng đời đọc đúng contract Phase8, không thêm trang sửa chúng ở #09.
7. Tiền xử lý bằng DECIMAL/chuỗi thập phân, không float để cộng ngân sách. Làm tròn HALF_UP đến2chữ số ở từng dòng thành phần; dự phòng tính từ tổng các dòng đã làm tròn; tổng cuối bằng tổng thành phần và dự phòng. Giao diện/Excel/PDF đối chiếu cùng số tiền và đơn vị VND.
8. Admin/Quản lý lưu kế hoạch draft trong scope; trùng(store,year,department) trả về cập nhật có version hoặc409, không ghi đè. finalized là chỉ đọc, không đổi snapshot và không thêm thao tác finalize/reopen trong gate này. Chức năng chốt ngân sách nếu cần sẽ là gate riêng.
9. Excel/PDF có giới hạn số dòng và từ chối vượt giới hạn, không cắt ngầm. Dự toán thiếu dữ liệu vẫn có thể xuất với trạng thái **chưa đầy đủ** và tổng phần đã tính, không đặt tên là tổng ngân sách hoàn chỉnh. Ghi rõ báo cáo tính tại thời điểm hiện tại, không snapshot đã chốt.

Trả lời mong muốn: **OK BE Phase 9 / OK với chỉnh sửa / Không**.

## Bổ sung sau phản hồi user ngày 21/10/2026

- Ngân sách có đúng ba thành phần: thay thế, bảo trì, mua thêm theo mở rộng; dự phòng 10% hiển thị riêng. Cho phép cộng tay đối chiếu từ từng dòng đến tổng. Đề xuất giữ dự phòng cố định 10% trong #09, không thêm tùy chọn sửa tỷ lệ khi chưa được xác nhận; nội dung này thay phần cấu hình tỷ lệ ở mục 6 nếu user duyệt.
- Thiếu giá thay thế, chi phí bảo trì hoặc định mức thiết bị/người phải ghi **chưa đủ dữ liệu**. Chỉ dùng tỷ lệ bảo trì thay thế khi Admin đã cấu hình rõ; không suy thiếu dữ liệu thành 0. Số 0 được nhập/xác minh rõ vẫn phân biệt với thiếu dữ liệu.
- Thay thế dùng một tập asset ID duy nhất: đạt tuổi thọ và đạt ngưỡng điểm đồng thời chỉ tính một lần.
- Quyền đề xuất giữ nguyên: chỉ Admin IT lưu cấu hình/đơn giá; Admin IT và Quản lý lưu kế hoạch mở rộng theo bộ phận trong phạm vi server cho phép; Hỗ trợ không xem hoặc xuất ngân sách.
- Excel: mọi dữ liệu văn bản được ghi bằng kiểu STRING tường minh, kể cả chuỗi bắt đầu bằng =, +, -, @; kiểm lại kiểu ô sau khi mở file. PDF kiểm render tiếng Việt, không chỉ kiểm file tồn tại. Export kiểm lại scope và giới hạn dòng trước khi tạo file, không cắt ngầm.

### Điểm cần duyệt bổ sung: đóng băng kế hoạch đã lưu

Schema 004 đã có calculation_snapshot_json, status và finalized_at; không cần DDL để lưu snapshot. Gate ban đầu chỉ cho lưu nhân sự tăng ở draft và không tạo thao tác chốt. Để đáp ứng yêu cầu số liệu đã lưu không tự đổi khi sửa giá/tham số, đề xuất bổ sung vào gate:

1. Mỗi lần bấm **Lưu kế hoạch**, server tính và lưu snapshot theo năm/bộ phận, gồm phiên bản công thức, thời điểm VN/UTC, bộ lọc, đầu vào, giá/định mức, nguồn chi phí, dữ liệu thiếu, ba thành phần và dự phòng/tổng. Lưu cùng headcount, audit trong một transaction; không nhận kết quả tiền do client tự gửi.
2. Trang **Kế hoạch đã lưu** và export của kế hoạch luôn đọc snapshot. Đổi cấu hình/giá không cập nhật snapshot. Dự báo hiện tại là màn hình riêng, ghi rõ chưa lưu. Muốn cập nhật draft phải bấm **Tính lại và lưu**, có xác nhận thay thế số liệu cũ và kiểm phiên bản; audit lưu dấu thay đổi. Không tự ghi bù snapshot cho dữ liệu cũ.
3. Bản ghi finalized chỉ đọc và không mở lại. Snapshot cũ thiếu/sai trả lỗi rõ, không fallback sang giá hiện tại. Chưa thêm thao tác finalize trong #09; nếu cần nút chốt sẽ trình riêng. Khi đọc snapshot vẫn kiểm quyền bộ phận hiện tại, không tin scope lưu trong JSON.
4. Snapshot theo toàn bộ các loại thiết bị của bộ phận để không ghi đè một kế hoạch năm/bộ phận bằng kết quả bộ lọc một loại. Bộ lọc loại trên kế hoạch đã lưu chỉ lọc các dòng snapshot và tính tổng các dòng đó, không query lại giá hoặc ticket hiện tại.

**User xác nhận “Duyệt bổ sung và BUILD” ngày 21/10/2026. Snapshot khi lưu draft và dự phòng cố định 10% đã được duyệt.** Đây là mở rộng so với gate ban đầu, không phải yêu cầu tạo bảng/cột mới.

Căn cứ điểm dừng: AGENTS.md mục2 yêu cầu gate trước PHP/endpoint/SQL ghi ngoài phạm vi đã duyệt. #08 không bao gồm writer cấu hình/kế hoạch hoặc export ngân sách #09. Trong lúc chờ chỉ làm FE/mock độc lập có nhãn MOCK; không chạy DB hoặc triển khai.

Bổ sung đã duyệt thay giới hạn ban đầu chỉ lưu nhân sự draft. GET forecast-plan đọc một snapshot; không finalize/reopen, DDL, DB agent, merge/tag/deploy.
