# Cải tiến chọn máy, icon, thông báo và Focus — 30/10/2026

Theo4ảnh user: chỉ FE, không PHP/SQL/API/schema/config, không kết nối DB hoặc host. Không tạo gói mới/upload/push/merge/tag7–10. Gói delta29/10 không chứa thay đổi này.

## Hành vi

- Phân bổ/thu hồi: đưa bộ chọn thiết bị lên trước, nút **Mở danh sách thiết bị** tải trang đầu bằng GET data hiện có; không cần nhập tìm kiếm. Native select có mũi tên, mỗi mục gồm mã +tên máy; Trước/Sau giữ phân trang5thiết bị/trang theo API, không tải toàn DB. Tìm mã/tên/seri vẫn có như lựa chọn phụ. Không tự mở thiết bị đầu hoặc tự ghi; chỉ change select/reload mới mở hồ sơ.
- Icon13mục menu chính khác nhau: Import/Export là tệp/mũi tên; phân bổ là hộp thiết bị; tài khoản là người/lá chắn; nhật ký là clipboard; mạng dùng sơ đồ mạng. SVG native, không ảnh/downloadpackage.
- Tài khoản/phân quyền: thông báo có biểu tượng, tiêu đề Hoàn tất/Cần kiểm tra/Đang xử lý/Thông tin, nội dung và nút đóng có nhãn truy cập. Nội dung dùng textContent, không render HTML tài khoản. Pending không tự ẩn/không đóng trong lúc xử lý; thành công/info tựẩn4giây; lỗi giữ lại, đóng chỉ ẩn thông báo, không nới validation hoặc tựretry.
- Focus khi tắt hiệu ứng: mục đang chọn rõ hơn, form/thead/zebra/hover/panel/nhóm quyền/khung mạng thống nhất token, giữ native select arrow. Không thêm animation; không đổi dữ liệu/quyền hoặc rebuild quy trình BE. Giữ fontInter hiện có, không đổi vendor/theme framework.

## Kiểm thật local

| Bộ | Kết quả | Giới hạn |
|---|---|---|
| tests/ita_ui_refine_20261030_browser.js | 22 checks đạt | PickerGET/chọnexplicit/phântrang/noPOST, icon13khác/đủsymbol, thôngbáođóng/mở, Focus, bounds1366/768/390 |
| tests/ita_account_feedback_browser.js | 14 checks đạt | Invalid/cancel/mismatch/noPOST, NodeRAMmock |
| tests/ita_phase05_browser.js | 35 checks đạt | Phân bổ ba vai trò/CSRF/version/PDF/error bằng APImock, không DB |
| tests/ita_user_profile_browser.js | 21 checks đạt | Edit/reset/reauth/503/rời view, NodeRAMmock |
| tests/ita_theme_contrast_browser.js | 12 checks đạt | Computed contrast≥4.5, nativearrow/dialog hai chế độ |
| tests/ita_theme_login_browser.js | 42 checks đạt | Hai chế độ/input không mất, nofetch/noPOST, WebGL/reducedmotion/responsive |
| node --check JS sửa; php tests/ita_phase03_render.php; git diff --check | Đạt | JSsyntax/Smarty thật/whitespace, không DB |

Đã xem ảnh feedback desktop/mobile. Detector cảnh báo Inter hiện có (giữ font theo hệ thống thiết kế); cảnh báo viềnnhấn một phía đã bỏ. Review source độc lập chưa thấy lỗi rõ ở picker/feedback, agent review không chạy browser và không dùng được Git trong phiên đó. Browser session/servers cũ đã dừng nên lượt đầu thiếu môi trường; sau khởi động đúng18766/18767, lệnh kiểm thực trên server mới đạt. Không coi lỗi môi trường là defect sản phẩm hoặc kết quả test đã đạt.

Xem http://127.0.0.1:18767/#assignments và #users, nút hiệu ứng chuyển Command/Focus. Dữ liệu RAMgiả, không nhập credentialhost. Chưa PHP8.3/FPM/DB/host nghiệmthu. Backup file thay trước upload; rollbacksource giữ dữ liệu.
