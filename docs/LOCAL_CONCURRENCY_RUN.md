# Hai phiên Playwright CLI local — soạn26/10/2026, CHƯA CHẠY

User yêu cầu chỉ soạn. Đã đọc skill Playwright CLI; không mở browser, login, gửi GET/POST hoặc kết nối DB trong bước này. Harness `tests/ita_local_concurrency_browser.js` là function cho `playwright-cli run-code`, không có API mock. Syntax node --check không thực thi function. Chưa có PHP8.3 path/DBack thực; các trường placeholder không phải kết quả hoặc quyền chạy.

## Điều kiện và chuẩn bị phiên sau xác nhận riêng

1. User tự dựng Laragon theo LOCAL_RUN, cung cấp đúng tên DB và nhắn **«Tôi xác nhận cho chạy trên DB local [tên DB]»**. Ghi mapping vhost `https://infracore.test` thực sự loopback, không tunnel, app config đúng schema; script không thể chứng minh DATABASE() từ HTML và không thêm debug endpoint.
2. Seed thành công/metadata/grants/isolation RR đã kiểm riêng. Sao lưu local; không chạy load/thêm asset trong lúc seed baseline cần ổn định. Dùng tài khoản TEST, login biết trước last_login/password_hash có thể ghi native local. Không dùng state/password host.
3. Tạo hai login session **độc lập**, bằng hai Playwright context/profiles riêng, user khác nhau. Xuất storage state qua CLI khi được giao; lưu **hai tệp private ngoài checkout và mọi webroot**, ACL userlocal. State chứa cookie/CSRF/session secret: không chat/Git/gói/HAR/screenshot có credential. Không dùng hai bản sao cùng state. Không ghi mật khẩu vào scriptcommitted.
4. Sao chép harness sang đường dẫn private local (không commit/deploy), điền object `c`: origin giữ vhostlocal, schema/ack đúng, localOnly/noTunnel=true, states là hai pathprivate, users là hai nativeTESTID khớp session, scenario tương ứng. Bản repo mặc định fail-closed trước request. Không bật ignoreHTTPSErrors để che certificate sai.

## Ba lượt riêng, dữ liệu fake và payload đã đối chiếu source

| scenario | Hai actor phù hợp fixture | Dữ liệu trước chạy | POST và kiểm |
|---|---|---|---|
|ticket|Admin900001 +ManagerB900003|asset3004 TEST ởB, không pending|ticket-save operation=create, repair/medium/noteTEST; một200, một409; readback đúng1 pending và assetmaintenance|
|license|Admin900001 +ManagerA900002|license7001 per_device, seats2/used1; targets3002 và3003 ởA chưaallocated|license-save allocate, **cùngversion**, hai targetkhác, confirm_overage=false; một200, bên còn lại409(stale); readback used tăng1 và không vượtseats. 403/422 là lỗi cần phân loại, không PASS race|
|version|Admin900001 +ManagerB900003|asset3004/assetBfake khác, cả hai GET cùngversion|asset-save operation=note, reasonTEST khác, cùngversion; một200, một409; fields.status_reason sau đọc lại đúng bên thắng|

Không tự thu hồi/xóa/reset sau lượt. Ticket và license thay đổi baseline sau chạy, không chạy lại như thể chưa thay đổi. Chuẩn bị hồ sơ TEST mới bằng UI được phép hoặc DB local mới có xác nhận riêng. Fixture cùngactor sẽ serialize ngay khóa role nên harness bắt hai nativeID khác nhau; mỗi cặp vẫn phải có quyền thật trước thử.

Lệnh **tương lai, CHƯA CHẠY**, chỉ sau điền privatecopy và xác nhận:

```powershell
playwright-cli -s=ita-local-race open about:blank
playwright-cli -s=ita-local-race run-code --filename='<private-local-race.js>'
playwright-cli -s=ita-local-race close
```

Harness tạo hai browser.newContext từ state riêng, so ITA_SESSION khác nhau (không invalue), xác nhận ID/role/CSRF bằng app thật. Chặn origin ngoài vhost và POST ngoài3route; serviceworker bịblock. Cả hai page tới barrierclient rồi phát POST bằng **Promise.all**, mỗi request CSRF của chính session. Chỉ trả status/success/thời điểm, không body/token/cookie; finally đóng cảhai contexts.

**Promise.all không chứng minh SQL chồng lấn**: browser/PHPworkers/DBlock có thể serialize. Dùng evidence thời điểm request, haiApacheworkers/connection riêng và nếu được giao harnessserver/DBobserverlocal để chứng minh overlap; nếu không có, ghi “hai request phát đồng thời, overlapSQL chưa chứng minh”. Không sửa runtime/server để tạo barrier trong bước soạn. Harness yêu cầu409 ở request thua; 403/422 dừng để phân loại, không PASS chốngvượtchỗ. Vì version giấy phép gồm used_seats,409 có thể chỉ chứng minh stale guard; muốn chứng minh riêng nhánh excess cần phép thử **tuần tự riêng** dùng version mới, target chưa cấp và confirm_overage=false, kỳ vọng từ chối đúng nghiệp vụ. Không tự retry trong harness hoặc gọi phép thử tuần tự đó là concurrency.

Sau chạy, kết nối đọc DB riêng đã xác nhận đếm pending/allocation active, version/note và audit thành công đúng1 thao tác. FE/API readback không thay kiểm persistence/audit/rollback. Kết quả nhãn **«kiểm trên local, DB cô lập»**, gồm source/runtime, hai actorIDfake, DBrowcounts, status/timing, phần chưa kiểm; không kết luận host hoặc constant-time/auth timing. Lần soạn chỉ syntax JS đạt, không browser/DBPASS.
