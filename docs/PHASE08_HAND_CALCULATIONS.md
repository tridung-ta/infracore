# Đối chiếu tính tay vòng đời — quy tắc đã duyệt #08

Trọng số mặc định: tuổi0,5; sửa chữa0,3; chi phí/hỏng0,2. Thay thế >=0,8; theo dõi >=0,6 và <0,8. Các ví dụ điểm bên dưới cung cấp tuổi trực tiếp để kiểm công thức thuần; ngày mua được kiểm riêng. Không phải dữ liệu người dùng hoặc bằng chứng DB.

| Ca | Tuổi / tuổi thọ | Repair count | Giá mua / chi phí sửa | Trạng thái | Thành phần tuổi + sửa + chi phí | Điểm gốc | Hiển thị | Đánh giá |
|---|---|---|---|---|---|---|---|---|
| Tuổi bằng tuổi thọ |5/5|0|100/0|in_use|0,5+0+0|0,5|0,500|Bình thường|
| Đúng 3 phiếu |5/5|3|100/0|in_use|0,5+0,3+0|0,8|0,800|Thay thế|
|4 phiếu, thành phần sửa bị chặn tại 1 |5/5|4|100/0|in_use|0,5+0,3+0|0,8|0,800|Thay thế|
| Đúng ngưỡng theo dõi |5/5|1|100/0|in_use|0,5+0,1+0|0,6|0,600|Theo dõi|
| Đang hỏng dù thiếu giá |5/5|0|NULL/0|broken|0,5+0+0,2|0,7|0,700|Theo dõi|
| Giá 0, không broken |5/5|0|0/0|in_use|Thành phần chi phí không xác định|NULL|—|Chưa đủ dữ liệu|
| Giá rỗng, không broken |5/5|0|NULL/0|in_use|Thành phần chi phí không xác định|NULL|—|Chưa đủ dữ liệu|
| Thiếu tuổi hoặc tuổi thọ |NULL/5 hoặc5/NULL|0|100/0|broken|Thành phần tuổi không xác định|NULL|—|Chưa đủ dữ liệu|
| Điểm > 1, không chặn tuổi |10/5|4|100/0|broken|1+0,3+0,2|1,5|1,500|Thay thế|
| Gần ngưỡng nhưng chưa đạt |Điểm gốc đầu vào kiểm phân loại|—|—|—|So trực tiếp0,7999 với0,8|0,7999|0,800|Theo dõi|

Điểm hiển thị chỉ để đọc. Có thể thấy0,800 ở nhóm Theo dõi nếu điểm gốc dưới0,8; mục Giải thích luôn giữ điểm gốc. Sắp xếp SQL theo score_raw, không theo score_display. Native meter dùng cùng thang trên trang, max>=max(1,điểm lớn nhất), không giới hạn điểm tại 1.

## Ngày, cửa sổ và phiếu mở lại

- Ngày đánh giá2026-10-21, ngày mua 2022-10-21: 1461 ngày / 365,25 = 4 năm chính xác. Mua 2026-10-22 là tương lai → tuổiNULL, điểmNULL.
- Đánh giá 2024-02-29: trừ 12 tháng lịch và chặn cuối tháng → 2023-02-28. Start UTC = 2023-02-27 17:00:00; end UTC loại trừ = 2024-02-29 17:00:00 (00:00 ngày 01/03 VN).
- Đánh giá 2026-10-21: start UTC = 2025-10-20 17:00:00; end UTC = 2026-10-21 17:00:00. Đúng start được tính; trước start 1 giây không tính; đúng end không tính; trước end 1 giây được tính.
- Đây là cửa sổ từ ngày cách 12 tháng đến **hết ngày đánh giá**, đúng contract gate; không thay bằng 365 ngày cố định.
- Ticket repair ID 9 ở failed sau đó mở lại pending vẫn là 1 dòng trong ita_tickets. Query repair COUNT(*) theo bảng ticket PK, không JOIN ita_ticket_events; nhiều event/audit không nhân count/cost. Test thuần kiểm định nghĩa và querycontract; DB thật chưa chạy.
- Periodic không tham gia số thiết bị có lỗi hoặc chi phí sửa. Cost là tổng hiện tại của các ticket repair mở trong cửa sổ, gồm tất cả trạng thái, không phải snapshot bất biến.

## Nhóm và mẫu số

Ví dụ 3 thiết bị trong nhóm, 2 có ít nhất 1 repair: tỷ lệ lỗi 2/3=66,666…%. Không lấy tổng số ticket chia số thiết bị. Nếu chỉ 2 thiết bị có tuổi 4 và 6 năm, tuổi trung bình=(4+6)/2=5năm, công khai2 có tuổi / 1 thiếu tuổi. Không lấy trung bình của các trung bình nhóm.

## Kiểm tra đã chạy

`php tests/ita_phase08_unit.php`:68 pure/scripted checks đạt; không DB. Test bao gồm công thức, settings, ngày biên/cuối tháng, rawthreshold, reopeneddefinition và querybindings/scope. SQL DECIMAL và kết quả aggregate thực tế cần đối chiếu lại trên host; không ghi PASS DB.
