# Một triệu bản ghi: khả năng hiện tại và kế hoạch kiểm — 28/10/2026

Đây là **rà source/kế hoạch**, không phải kết quả benchmark. Chưa sinh1triệu dòng, chưa kết nối DB, chưa stress host hoặc tăng cap. Ảnh user có badge100007 và thông báo sơ đồ vượt200; không suy ảnh thành bằng chứng mọi bảng/chức năng đã chạy đúng trên100k/1triệu dòng.

## Nếu DB có một triệu tài sản thì hiện giờ xảy ra gì?

| Luồng | Giới hạn source hiện tại | Ý nghĩa khi tổng DB lớn |
|---|---|---|
|Danh sách tài sản|COUNT +5 dòng/trang, scope/filter tại server|Không render1triệu dòng vào DOM; chưa chứng minh COUNT/LIKE/OFFSET trang sâu nhanh trên DB lớn|
|Import Excel|.xlsx tối đa5MiB và5.000dòng/lô, preview phân trang|File1triệu dòng bị từ chối. Tối thiểu200lô nếu chỉ tính trần dòng, còn giới hạn dung lượng; không khuyên nhập200lô trên host để benchmark|
|Asset-export|Tối đa5.000 tài sản/file, vượt bị từ chối|Cần lọc/cohort; không xuất1triệu dòng bằng request hiện tại|
|Dashboard/cảnh báo/report read-model|Lấy tối đa5001 để phát hiện vượt5.000 tài sản/giấy phép trong phạm vi; biểu đồ tối đa500nhóm|Phạm vi quá lớn có thể trả422 yêu cầu lọc. Không phải dashboard toàn bộ1triệu dòng đã tối ưu|
|Forecast hiện tại|Tối đa2.000 thiết bị nguồn,1.000dòng kết quả|Lọc đúng phạm vi hoặc bị từ chối; không bỏ guard để lấy toàn DB|
|Report export|PDF500dòng; Excel inventory5.000, Excel báo cáo khác1.000|Giữ giới hạn theo contract, không cắt ngầm hay tăng cap khi chưa đo|
|Mạng2D/3D|Tối đa200thiết bị/400liên kết mỗi tập; bảng liên kết20dòng/trang|Không vẽ toàn1triệu khối. Phân trang bảng liên kết không chia nhỏ tập node3D. Cần lọc nhóm mã/tên/bộ phận để tập hợp lệ|
|Generator local hiện có|scripts/local/ita_local_fixture.php và LocalFixture::load tạo tổng5.000 từ fixture6tài sản|Không có chế độ1triệu đã kiểm; không tự thay4994 thành999994 rồi chạy|

Source đối chiếu: ItaAssetObjects, ItaExcel/ItaImportStorage, ItaDashboardObjects/ItaDashboardRules, ItaForecastRules/Objects, ItaNetworkObjects và scripts/local/localfixture.class.php. LIMIT nhỏ chỉ giới hạn rows trả về; COUNT/aggregate/subquery/sort và OFFSET vẫn có thể tốn tài nguyên trên DB lớn. Chưa có EXPLAIN/timing/peak-memory thực nên không ghi PASS hiệu năng hoặc bảo đảm không timeout.

Lỗi UI trong ảnh đã sửa: bộ lọc mạng nằm ngoài vùng graph bị ẩn khi lỗi. Khi422 vẫn có chỗ thu hẹp rồi Áp dụng, không chỉ một nút retry cùng truy vấn vượt cap. Giữ guard200/400, scope và cả hai đầu liên kết; không trả một sơ đồ thiếu node/link rồi gọi là đầy đủ.

## Muốn hỗ trợ/kiểm một triệu cần bước riêng

1. Chốt mục tiêu:1triệu asset hay tổng cả ticket/audit/link, dữ liệu phân bố theo bộ phận/loại/status/ngày và số liên kết. Đo cùng cohort trước/sau; nhiều bảng phụ có thể lớn hơn asset. Không đo trên DB dự án host đang dùng.
2. Chỉ sau user xác nhận DB local cô lập đúng schema bằng câu đã thống nhất mới kết nối. Không dùng credential/config host. User backup/đủ dung lượng đĩa/giới hạn CPU trước. Không tạo DB hoặc chạy SQL từ tài liệu này.
3. Trình BE-GATE riêng cho generator/SQL và thay read-model nếu cần. Generator tương lai phải có allowlist loopback, marker/schema test, mã TEST- duy nhất, FK hợp lệ, chunk có giới hạn/commit từng lô, checkpoint/resume kiểm trùng, số liệu sau mỗi lô và dừng an toàn. Không gom1triệu object/workbook vào PHP/browser, không một transaction khổng lồ, không DROP/reset dữ liệu để chạy lại. Không sinh1triệu native user.
4. Chạy tăng dần10k→100k→1triệu sau từng lượt review. Ghi PHP/MariaDB/runtime thật, source/hash, engine/index/row count, kích thước data/index, cache state; warm-up và số lượt thống nhất. Thu timings median/p95/max, PHP peak memory đúng process, lỗi/timeouts/locks và query plan. Chưa có SLA duyệt thì báo số thực, không tự đặt PASS.
5. Đo danh sách trang đầu/trang sâu/tìm mã/tên, COUNT và aggregate; EXPLAIN các query thực cùng scope/filter, thống kê/budget/dashboard/report và graph cohort. Đề xuất index/keyset/summary/paging mới chỉ sau bằng chứng và SQL gate, không ALTER để chữa mò. Không đo browser heap thay PHP memory.
6. Test API/DOM trước bằng total=1.000.000 với response nhỏ đúng contract chỉ chứng minh trình bày/phân trang, **không chứng minh DB chịu1triệu**. Mạng cần thiết kế phân cấp/tập con bounded/drilldown nếu muốn khảo sát DB lớn; liên kết cắt qua biên nhóm phải được mô tả, không giấu thiếu dữ liệu.
7. Dù local đạt, sau upload được duyệt vẫn smoke host ngắn. Không chạy tải1triệu/seed/revoke/race trên host để nghiệm thu; không tự push/merge/tag7–10 hoặc tạo gói từ kế hoạch này.

| Mốc | DB/source/runtime | Rows thật/data+index | Query/luồng +scope/filter | Timing/memory/plan | Kết quả |
|---|---|---|---|---|---|
|10k /100k /1triệu|Chưa có môi trường xác nhận|Chưa sinh/đếm|Chưa chạy|Chưa đo|CHƯA KIỂM|
