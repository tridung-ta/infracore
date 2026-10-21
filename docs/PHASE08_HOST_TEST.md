# Kiểm thử vòng đời trên host — chưa chạy

Không có migration, seed hoặc SQL ghi mới. Agent chưa upload hoặc kết nối DB. User sao lưu source và DB theo quy trình trước triển khai.

1. Đăng nhập Admin, mở `https://infracore.dung.derasoft.com/admin.php?op=ita#lifecycle`. Nếu chưa có khóa cấu hình, phải thấy ghi chú **đang dùng giá trị mặc định**.
2. Đối chiếu một thiết bị: ngày mua, tuổi thọ của loại, giá mua, trạng thái và các phiếu `repair` trong cửa sổ ngày. Dùng PHASE08_HAND_CALCULATIONS.md để tính tay. Phiếu `periodic` của UAT-IT-001 trước đây không được tăng số lần sửa chữa.
3. Điểm hiển thị đúng 3 chữ số thập phân. Mở **Giải thích** xem điểm gốc và ba thành phần. Điểm hiển thị 0,800 có thể thuộc nhóm Theo dõi nếu điểm gốc dưới 0,8. Điểm lớn hơn 1 không bị cắt.
4. Hồ sơ thiếu ngày mua, ngày mua tương lai, thiếu tuổi thọ hoặc giá bằng 0 phải được đánh dấu chưa đủ dữ liệu theo gate. Thiết bị đang hỏng được tính thành phần hỏng dù thiếu giá, nhưng thiếu tuổi vẫn không có điểm.
5. Đổi bộ phận, loại, đánh giá và từ khóa: tổng số, danh sách và các bảng nhóm phải dùng cùng tập lọc. Phân trang 20 dòng, điểm gốc giảm dần, điểm NULL cuối, ID ổn định khi bằng điểm.
6. Quản lý chỉ thấy phạm vi phụ trách. GET `lifecycle-detail` với ID ngoài phạm vi phải trả 403. Hỗ trợ gọi trực tiếp GET `lifecycle` hoặc `lifecycle-detail` phải trả 403; không chỉ kiểm menu ẩn.
7. Đối chiếu `opened_at` với biên UTC trong response. Mở lại một phiếu `repair` vẫn là cùng ID. Điều chỉnh chi phí hợp lệ có thể đổi tổng chi phí hiện tại nhưng không tăng số phiếu.
8. Khóa cấu hình vắng dùng mặc định; khóa tồn tại nhưng sai phải trả 422 và không giữ bảng kết quả cũ. Không sửa cấu hình trên DB thật chỉ để thử lỗi nếu chưa có phạm vi và backup được duyệt.
9. Mở PHASE08_EXPLAIN.sql: có 10 câu EXPLAIN sinh từ bộ dựng truy vấn, gồm 2 vai trò × count/list/summary/category/department. Chọn đúng schema, sửa ngày/store/phạm vi mẫu cho phù hợp. Chạy từng câu và gửi key/type/rows/Extra cùng thời gian thực. Không chạy ALTER hoặc tự thêm index.
10. Ghi nguyên văn môi trường, phiên bản, các mục đã/chưa kiểm vào PROGRESS. Chưa có kết quả thì giữ «chưa kiểm», không nghiệm thu DB, merge hoặc tag.

Rollback source: khôi phục các file từ backup, giữ DB/config/keys. Không DROP, restore đè dữ liệu mới hoặc chạy lại CREATE.
