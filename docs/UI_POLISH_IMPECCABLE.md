# Tinh chỉnh Impeccable giao diện ITA

Ngày 16/10/2026. Yêu cầu user: dùng skill Impeccable để tinh chỉnh UI hiện có. Phạm vi FE, không sửa PHP/routing, SQL hoặc dữ liệu.

## Thay đổi

- Giữ thế giới thị giác navy/cyan, font Inter và chức năng hiện có; tăng cỡ chữ nhãn, form, dữ liệu và metadata. Bảng từ 10 lên 12 px; tên thiết bị 13 px; form desktop 13 px, mobile 16 px; số liệu dùng tabular numerals.
- Sidebar desktop 256 px để đủ nhãn; tablet vẫn compact 76 px, KPI hai cột ở 768–960 px; mobile select toàn hàng để không cắt chữ.
- Thay glyph rời rạc bằng sprite SVG tự vẽ cùng nét; icon chuông, thiết bị, công cụ, điều hướng và chi tiết thống nhất. Không thêm thư viện/CDN.
- Bỏ hai nhãn trang trí phía trên heading; giữ nguyên tên trang, số liệu, thông tin MOCK và nội dung nghiệp vụ. Nhãn hồ sơ/Mock chuyển dưới tiêu đề modal.
- Bổ sung skeleton tĩnh cho 4 KPI; giữ loading/error/empty/disabled thực, không thêm animation trang trí.
- Vùng bấm mobile tối thiểu 44 px cho button đã kiểm; form dùng 16 px; focus chuyển vào main sau điều hướng từ menu mobile. Selection/caret/native scrollbar dùng token hiện có, không custom scrollbar.
- Dọn selector mất consumer do các thay đổi này, không chồng thêm stylesheet mới.

## Quy trình và kết quả

Impeccable context chạy thành công trên templates/admin/ita/layout.tpl.html. Không có PRODUCT.md/DESIGN.md; directive SCOPED_EXISTING_ALLOWED cho phép polish dựa trên code hiện có. Đã đọc polish, operate và craft-floor; không chuyển sang redesign.

Đã kiểm theo hai lượt có giới hạn: dựng và quan sát desktop/tablet/mobile, sửa cùng lượt các vấn đề logo xuống dòng và select mobile cắt chữ, sau đó kiểm xác nhận cuối. Không chạy vòng polish mở rộng.

- node --check assets/ita/js/ita-shell.js: đạt.
- Preview build từ template: đạt.
- Playwright verify-ui.js qua PHP/Smarty: tìm kiếm, lọc kết hợp, phân trang, reset/empty, menu, chi tiết, cảnh báo, lỗi tải 503/retry, reduced-motion, modal confirm/cancel/escaping: đạt; không có JS runtime error. Lỗi resource 503 trong console là trường hợp mô phỏng có chủ đích.
- 1366/768/390: chụp và kiểm ảnh; scrollWidth bằng viewport. Kiểm bổ sung reflow ở 320 và 683 px đạt; không coi đây là chứng nhận browser zoom 200% thật.
- Toàn bộ svg use có symbol đích; mobile form 16 px, select rộng tối thiểu 250 px ở viewport 320; close dialog 44 px, focus main sau chọn menu mobile đạt.
- Đo từ token đang render: text/bg 15.79:1; muted/surface 7.49:1; muted/accent-soft 6.39:1; success 6.99:1; warning 7.82:1; danger 4.82:1; accent 7.98:1. Đây là các cặp semantic đã đo, không tuyên bố toàn bộ sản phẩm đạt WCAG.
- Mechanical detector chạy đúng một lần trên các target đổi: chỉ có cảnh báo overused-font Inter. Giữ có chủ đích vì Inter đã là font dự án và Operate cho phép sans quen thuộc; không thay identity chỉ để xóa cảnh báo. Không có hook tự động được bật.

## Giới hạn

UI vẫn dữ liệu MOCK. Chưa kiểm screen reader thật, Safari/iOS, browser zoom thật, live DB/auth hoặc hosting. Không sửa backend hoặc triển khai. Có thể dùng /impeccable init ở phiên sau để lưu ngữ cảnh sản phẩm và chuẩn thiết kế dùng lâu dài.