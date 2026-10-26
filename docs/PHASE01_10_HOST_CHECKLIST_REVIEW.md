# Rà bộ kiểm host Phase 1–10 — 23/10/2026

**Cập nhật26/10/2026:** user đã chọn tự dựng Laragon/DB local cô lập để kiểm đầy đủ trước đóng gói. Checklist A–L có thể dùng ở local theo LOCAL_RUN.md/LOCAL_DB_TEST_PLAN.md, kết quả phải nhãn **«kiểm trên local, DB cô lập»**, không ghi vào cột host. Chưa có câu xác nhận tên DB nên agent CHƯA KẾT NỐI. Giới hạn không tạo lịch sử I1 bằng UI trên host vẫn đúng; fixture local mới có lịch sử fake3năm để kiểm cohort135.3triệu. Giữ bản rà lịch sử bên dưới; sau upload vẫn cần smokehost ngắn riêng, localPASS không thành hostPASS/merge/tag.

Tệp checklist user gửi là **kế hoạch chưa điền**, không phải xác nhận đã kiểm. Tất cả mục vẫn **CHƯA KIỂM**; không cập nhật nghiệm thu DB hoặc cho phép merge/tag từ tài liệu này. Agent chỉ đọc source/tài liệu local, không chạy host/SQL, không tạo DB/gói hoặc đổi code.

## Điều chỉnh trước khi dùng checklist

| Mục | Điều chỉnh / tiêu chí dùng khi ghi kết quả |
|---|---|
| Quy tắc, B1–B3 | Login thành công có UPDATE dc_users.last_login và có thể nâng hash: xem classes/services/itaauthservice.class.php và classes/security/pmauth.class.php. Với yêu cầu tuyệt đối không sửa dc_*, chỉ dùng phiên test đã có; không login/relogin. Chưa có phiên hoặc hết phiên thì ghi chưa kiểm. Kiểm login cần phạm vi riêng cho phép side effect này; không tự sửa auth để tránh ghi. Logout có thể làm mất phiên cần cho các bước sau, nên để cuối. |
| A1–A6 | Ghi phiên bản/engine/collation/grants thực, không điền sẵn từ hồ sơ. Chỉ đọc setting đã xác định không chứa secret, không xuất toàn bộ ita_settings lên chat. SHOW GRANTS trong phpMyAdmin chứng minh quyền của chính tài khoản đó, không mặc định là tài khoản runtime ứng dụng. Số bảng/FK là baseline migration, cần đối chiếu đúng schema và phạm vi ITA. CHECK tồn tại không chứng minh enforcement. |
| A7 | Dashboard tải thành công là quan sát hành vi, không thay bằng chứng isolation của chính kết nối ứng dụng. Ghi status/lỗi thực riêng; isolation vẫn chưa xác nhận nếu chỉ có query ở phpMyAdmin. Không thêm endpoint chẩn đoán hoặc đổi cấu hình để vượt guard. |
| B5 | Tách quản trị loại (Admin) khỏi GET categories dùng cho dropdown: lookup danh mục được các vai trò dùng theo contract hiện tại. Không kỳ vọng mọi GET categories đều 403 cho Quản lý/Hỗ trợ. |
| B6 | Kiểm audit theo từng thao tác nghiệp vụ có contract audit, không khẳng định mọi ghi kỹ thuật đều có before/after. Không lưu password/key/token vào bằng chứng. |
| C1 | “6 tài sản, 2 A và 2 B” chưa đủ sáu: chốt danh sách cụ thể trước khi thao tác, không tự phân bổ hai tài sản còn lại. |
| C3 | Validator tài sản hiện kiểm định dạng ngày và bảo hành không trước ngày mua; không có quy tắc cấm ngày mua tương lai trong ItaAssetValidation. Vòng đời đánh dấu tuổi không hợp lệ. Không ghi FAIL vì chưa có quy tắc cấm đã duyệt. Giá âm/ngày sai vẫn phải bị từ chối. |
| D5 | Dùng tệp văn bản trơ có nội dung không phải XLSX rồi đổi đuôi; không cần PHP/EXE thực thi. Không chạy tệp tải lên. Dữ liệu giả/import confirm qua UI ghi ita_ là khác với lệnh SQL ghi trực tiếp bị cấm. |
| F2–F4 | Raw key không có trong listing/detail thông thường; response reveal được Admin xác thực lại cố ý có key. Không chụp body/key đó. Ngưỡng limiter cần đối chiếu config/source đang chạy, không suy cố định 5 lần/15 phút từ checklist. |
| F7 | Các mốc 30/7 chỉ đúng khi cấu hình thực đúng các ngưỡng đó; ghi giá trị đã kiểm, ngày VN và thời điểm đánh giá. Không sửa cấu hình thật để làm số khớp. |
| H1 | Tuổi dùng ngày thực/365,25, nên đúng 5 năm lịch chưa chắc tỷ lệ chính xác 1. Tính lại bằng ngày mua và ngày đánh giá thực; dùng trọng số/ngưỡng đã xác minh. Tham chiếu PHASE08_HAND_CALCULATIONS.md. |
| H2–H3 | Broken vẫn có thành phần hỏng=1 khi thiếu giá mua; không mặc định mọi hồ sơ thiếu giá đều không có điểm. Thiếu tuổi/tuổi thọ thì thiếu điểm. Mẫu số chỉ gồm phần cứng đủ điều kiện phân tích, cùng scope/bộ lọc; không dùng toàn bảng loại. |
| I1 | 135,3 triệu chỉ là fixture tính tay có đúng cohort/đơn giá/định mức/nhân sự nêu trong ví dụ. Không thể mặc định tạo chi phí của ba năm trước bằng UI hiện tại; thiếu dữ liệu giả lịch sử thì ghi chưa kiểm, không SQL ghi hoặc giả mạo opened_at. Kiểm đúng điều kiện thay thế cuối năm và tránh đếm trùng. |
| J1 | Hai SELECT tổng quát trong bản gửi chưa đủ đối chiếu mọi KPI: phải cùng store, scope, bộ lọc, trạng thái và tập đủ điều kiện của từng chỉ số. Dùng query đã xuất trong PHASE10_EXPLAIN.sql; không coi sai cohort là lỗi công thức. |
| J6–J7 | So export với tổng bộ lọc, không chỉ các dòng trang preview hiện tại. Excel tồn kho tối đa 5.000 dòng, PDF 500, báo cáo khác 1.000: vượt phải từ chối. Tách token/độ mới report-export khỏi forecast-export và snapshot đã lưu; snapshot không tự đổi theo giá/dữ liệu hiện tại. |
| K | Chỉ thử hai phiên trên hồ sơ giả được phép; kiểm chỗ cuối giấy phép bằng vai trò không có override. Không tác động tài khoản vận hành hoặc tài sản thật. Không suy kết quả đồng thời từ hai thao tác tuần tự. |
| L | ALL/temporary/filesort không tự động là FAIL; cần cardinality, kế hoạch và dữ liệu phù hợp. Index mới vẫn cần gate SQL riêng. |
| 13 | Đổi tiêu đề thành “Chưa kiểm trong phạm vi này”. Không phải các tình huống đó không thể kiểm thủ công nói chung. Test scripted/mock local không chứng minh CHECK, rollback, race hoặc hiệu năng DB thật; không ghi chúng đã đạt trên DB. |
| 14 | Không điền sẵn “không phải production”: user xác nhận môi trường thực. Ghi “kiểm thủ công trên host, phạm vi hạn chế”, kết quả từng mục và phần chưa kiểm. Đạt một nhóm không tự suy nghiệm thu toàn phase; không tự merge/tag 7–10. |

## Cách gửi kết quả thật

Giữ mã A–L để đối chiếu bản checklist đã gửi. Mỗi dòng ghi Đạt / Không / Chưa kiểm, thời điểm, source/gói nếu xác định được và bằng chứng đã che thông tin nhạy cảm. Tách kết quả quan sát UI, query metadata và phần chưa kiểm; không gửi dump, config, HAR, cookie hoặc key.

Khi nhận kết quả, ghi nguyên văn xác nhận của user vào PROGRESS kèm nhãn **kiểm thủ công trên host, phạm vi hạn chế**. Hiện chưa có số liệu thực nên không điền PASS, số lượng đạt hoặc trạng thái nghiệm thu. Không cần tạo DB test local; kiểm host do user thực hiện khi sẵn sàng trong phạm vi cho phép.
