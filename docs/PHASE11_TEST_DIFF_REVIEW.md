# Đối chiếu diff test Phase5/6 — 22/10/2026

Nguồn: `git show 3513f60 -- tests/ita_phase05_browser.js tests/ita_phase06_browser.js`, so với parent3513f60^. Chỉ đọc diff, không sửa test trong lần rà này. Chạy lại browser API mock: Phase4 **30**, Phase5 **29** đạt. Phase6 lần chạy trước **28** đạt; không chạy DB.

| File/vị trí trong commit | Trước | Sau | Ý nghĩa |
|---|---|---|---|
| Phase5 dòng19 | Không có fixture act=alerts | Thêm envelope as_of/page/per_page/total/active_total/rows, cảnh báo “Quá hạn bảo trì: QA” | Phục vụ endpoint cảnh báo Phase10 hiện tại |
| Phase5 dòng50–51 | Chờ #alerts-panel, tìm chuỗi trong #alert-list | Chờ #dashboard-live-panel và #dash-output chứa cùng chuỗi | Giữ nguyên điều kiện nội dung cảnh báo, đổi selector và thêm wait dữ liệu |
| Phase6 dòng18 | Không có fixture act=alerts | Thêm một dòng LIC-7/critical/Giấy phép QA/Giấy phép hết hạn và phân trang | Phục vụ bảng cảnh báo hiện tại |
| Phase6 dòng43–45 | Chờ #alert-list button; assert text chính xác “Xem giấy phép”; click nút rồi chờ licenses-panel | Chờ #dash-output có text chính xác “Giấy phép hết hạn”; assert chứa “Giấy phép QA”; chuyển hash=licenses rồi chờ panel | **Đổi assertion điều hướng UI**, không chỉ đổi selector. Không còn test click-through nút cũ vì UI hiện tại là bảng không có nút đó. Không coi assertion mới tương đương kiểm click-through |

Diff chỉ có7 dòng thêm/5 dòng bỏ ở hai file. **Không có assertion về quyền, CSRF, version, reauth, tự ẩn key, lưu trữ key hay audit lỗi bị xóa hoặc nới** trong commit này. Các kiểm tra còn nguyên:

- Phase5: payload asset_version/target_id/CSRF; assignment_version khi nhận/thu hồi; chỉ nhận biên bản của mình; khóa submit khi conflict/kết quả chưa rõ; quyền UI Quản lý/Hỗ trợ; Hỗ trợ không tải employee picker.
- Phase6: Admin vượt mức phải lý do/CSRF; reveal POST+CSRF+password; xóa password; key không vào localStorage/sessionStorage; tự xóa sau10 giây và rời trang; lỗi audit không lộ key; Manager không sửa metadata/reveal/override; Support không có controls.

Các assertion này kiểm FE/payload với API mock, **không chứng minh server thực thi quyền hoặc transaction**. Rà source server riêng tại PHASE11_SECURITY_REVIEW.md; DB/host chưa nghiệm thu. Không sửa PHP/DB, không merge/tag7–10/push/deploy.
