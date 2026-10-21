# BE-GATE #08 — Vòng đời tài sản (đã duyệt kèm chỉnh sửa)

User duyệt ngày 21/10/2026, theo chỉ đạo mới nhất: hiển thị đúng 3 chữ số thập phân, giữ điểm gốc để so ngưỡng/sắp xếp và hỗ trợ điểm >1. Khóa cấu hình vắng dùng mặc định có ghi chú; khóa có giá trị sai trả lỗi. Bắt buộc ví dụ tính tay, ngày biên, mở lại phiếu và truy vấn EXPLAIN chính xác; index mới phải qua gate SQL riêng. Xác nhận DB: CHƯA XÁC NHẬN. KHÔNG merge hoặc tag Phase 7 trong bước này.

Phạm vi: F8.1 tuổi và tỷ lệ tuổi/tuổi thọ; F8.2 điểm thay thế theo Word 4.4; F8.3 tuổi trung bình và tỷ lệ lỗi theo loại/bộ phận; F8.4 danh sách đề xuất có lọc, phân trang và giải thích từng thành phần. Cho phép build local #08 nhưng không coi Phase 7 hoặc 8 đã nghiệm thu DB.

Thay đổi dự kiến: ItaLifecycleObjects/ItaLifecycleObjectInfo, ItaLifecycleRules và controller qua ItaApplication, bootstrap, GET lifecycle và lifecycle-detail; POST không cần. Template/JS/CSS vòng đời dùng số liệu server, JSON success/data/message. Admin IT xem toàn store, Quản lý xem scope; Hỗ trợ không được xem báo cáo vòng đời (theo Word4.1, chỉ báo cáo bảo trì của mình). GET id ngoài scope403; không lộ key, thông tin user hoặc dữ liệu ngoài scope. FE MOCK độc lập có nhãn rõ trong lúc chờ.

Migration: không dự kiến DDL/seed hoặc SQL ghi. Tái sử dụng ita_assets.purchase_date/purchase_price/category_id/department_id/status/is_deleted, ita_asset_categories.default_lifespan_years/is_software, ita_tickets.type/opened_at/cost và ita_settings. Không ghi bảng dc_, không tính bù dữ liệu hoặc tự đổi trạng thái/duyệt thanh lý. Nếu cần cột/index mới thì dừng, trình gate SQL riêng.

Rủi ro/backup: sai mẫu số, ngày biên12tháng, NULL/giá0, đếm event như ticket và aggregate JOIN nhân dòng. Dùng preaggregate ticket theo asset/store, prepared params, scope trước count/aggregate và phân trang. Có thể cần tối ưu theo EXPLAIN do user chạy trên host; chưa tuyên bố index đạt. Pure/scripted/browser API mock local, không tạo/kết nối DB hoặc deploy. User backup source/DB trước upload/UAT; rollback source giữ dữ liệu hiện có, không DROP/restore đè. Không Composer/cron/email mới.

Giả định cần xác nhận:

1. Chỉ thiết bị phần cứng chưa xóa mềm và chưa retired vào phân tích, kể cả loại inactive đang được tham chiếu. Không tính giấy phép/phần mềm như thiết bị. Giá/tuổi thiếu được đánh dấu chưa đủ dữ liệu, không biến NULL thành0 để xếp hạng.
2. Ngày đánh giá là hôm nay giờ VN (không cho xem quá khứ như snapshot). Tuổi theo số ngày thực từ purchase_date /365,25; ngày mua tương lai không có tuổi hợp lệ. Tuổi thọ lấy default_lifespan_years của loại; thiếu thì đánh dấu thiếu cấu hình, không tự đoán loại qua tên. Chỉ hiển thị làm tròn, so ngưỡng bằng giá trị chưa làm tròn.
3. Cửa sổ12tháng: từ ngày đánh giá trừ12tháng lịch (chặn cuối tháng) đến hết ngày đánh giá VN, query >=startUTC và <ngày kế tiếpUTC. Count ticket ID riêng type=repair theo opened_at (đã chốt #07), tất cả trạng thái, mở lại không tăng số. Chi phí dùng tổng cost hiện tại cùng cohort repair; không đếm periodic và không cộng audit/event; không tuyên bố số liệu bất biến sau sửa chi phí.
4. Công thức Word:0,5*(tuổi/tuổi thọ)+0,3*min(repair_count/3,1)+0,2*(broken?1:min(repair_cost/purchase_price,1)). Không chặn phần tuổi tại1 hoặc điểm tổng tại1. Đang broken vẫn tính thành phần hỏng=1 khi thiếu giá mua; nếu không broken và thiếu/giá0 thì điểm chưa đủ dữ liệu, không chia0. Thiếu tuổi/tuổi thọ luôn chưa có điểm.
5. Điểm>=0,8 đề xuất thay thế; >=0,6 và <0,8 theo dõi; <0,6 bình thường. Đề xuất chỉ đọc, không tự thanh lý hoặc phát sinh mua sắm. Bảng phân trang và detail nêu3thành phần để đối chiếu tính tay.
6. Tỷ lệ lỗi = số asset có ít nhất1repair trong cửa sổ / tổng asset thuộc nhóm đang phân tích; numerator distinct asset_id. Tuổi trung bình chỉ trên asset có ngày mua hợp lệ, công khai số hồ sơ thiếu tuổi. Bộ lọc bộ phận/loại áp dụng đồng nhất toàn bộ aggregate và danh sách; không lấy trung bình của trung bình nhóm.
7. Đọc trọng số/ngưỡng từ ita_settings nếu có key/giá trị đã xác minh trong seed; cấu hình không hợp lệ trả lỗi kiểm soát, không âm thầm thay số. Không thêm trang lưu cấu hình trong gate này; phát hiện thiếu contract cấu hình thì trình rõ trước triển khai phần liên quan.

Trả lời mong muốn: **OK BE Phase 8 / OK với chỉnh sửa / Không**.


## Xác nhận bổ sung mới nhất ngày 21/10/2026

KHÔNG merge, KHÔNG tag Phase7 trong bước này. Điểm hiển thị đúng3chữ số thập phân; điểm gốc ở phần giải thích, so ngưỡng/sắp xếp không làm tròn. Nguyên văn user yêu cầu ghi vào PROGRESS:

«Phase 1–7 chưa có xác nhận kiểm thử DB chi tiết (CHECK ticket_events, rollback InnoDB, hai phiên đồng thời, phiên bản MySQL/MariaDB, môi trường chưa được cung cấp)»

Phase7 và8 chưa được coi là nghiệm thu DB. Chỉ cập nhật trạng thái sau khi ghi nguyên văn xác nhận chi tiết do user cung cấp. Không coi mẫu còn placeholder ở phản hồi trước là kết quả kiểm thử.
