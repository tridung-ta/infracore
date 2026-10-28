# Bộ lọc mạng không biến mất khi quá giới hạn — 28/10/2026

Ảnh user: server trả “Sơ đồ vượt200thiết bị. Hãy thu hẹp…”, nhưng bộ lọc ở trong network-content và catch ẩn cả content. Đây là lỗi FE gây bế tắc; cap200thiết bị/400liên kết là guard chủ động, không bỏ để giải quyết ảnh.

Đã đưa form lọc và hướng dẫn giới hạn ra ngoài graph/content. Khi tải lỗi/422, ẩn graph cũ và thông báo lỗi nhưng vẫn tìm mã/tên, chọn bộ phận và Áp dụng được. Khi thiếu quyền, cả filter/help/content đều ẩn và không request topology. Reply cũ vẫn không khôi phục graph sau đổi quyền. Không đổi query/endpoint/scope/mapping/SQL hoặc quyền quản lý mạng.

Đã tái hiện trên template+JS baselineb22ebe3 qua override browser/API giả: assertion “Over-limit hides graph but retains recovery filters admin_it” đỏ. Lượt fixture đầu chỉ thay template nhưng dùng JS mới thiếu phần tử help bị timeout; đã sửa fixture để dùng đồng bộ template+JS cũ, không nhận timeout đó là kết quả sản phẩm. Không thay/reset source cũ.

`playwright-cli -s=ita-admin-refine run-code --filename=tests/ita_phase13_live_browser.js`: **35 kiểm đạt**, gồm422giữfilter→tìm QA-→graph phục hồi cho Admin/Quản lý, role/CSRF/version/3D/lỗi và replymuộn, bounds1366/768/390. `php tests/ita_phase13_unit.php`:179pure/query-contract đạt; Smarty render/node syntax/diff check đạt. Review agent không finding chặn. Không DB/host/concurrency hoặc benchmark.

Một triệu bản ghi chưa được kiểm/hỗ trợ toàn bộ; xem MILLION_ROW_READINESS.md. Không sinh dữ liệu, tăng cap, đóng gói/upload/push/merge/tag7–10. Rollback source đồng bộ template/JS/layout cache, giữ bảng/liên kết/audit/dữ liệu thật.
