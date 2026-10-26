# Tổng kết học lỗi và lập luận —26/10/2026

Theo yêu cầu user: **tạm dừng công việc để rà lại toàn bộ việc hôm nay, từ lỗi sai đến cách lập luận**. Chỉ bổ sung hồ sơ đã làm sạch; không tiếp tục build giao diện/PHP, chạy DB/host, tạo ZIP hoặc upload. Đây là trí nhớ dự án để đọc lại khi tiếp tục, không phải tuyên bố mô hình tự học hoặc chạy nền.

## 1. Trạng thái chính xác lúc dừng

Nhánh feature/ita-phase13-network, checkpoint trước tổng kết **3045b62**; source tính năng3D cuối **46ef989**. Trước sửa tài liệu tổng kết, tracked working tree sạch. Agent chưa kết nối DB local/host và chưa upload/deploy/push/merge/tag7–10. PHPCLI thực **8.5.10**, không phải8.3; DB/host vẫn chưa nghiệm thu.

| Phần đã thực hiện | Bằng chứng / giới hạn cần giữ |
|---|---|
|Kiểm bảo mật20 mục, S26-02 auth|S26_AUTH_VERIFY/PREUPLOAD_SECURITY_VERIFY;686 checks counter auth qua fake transport. Argon2 ngoại lệ một verify hash thật; count không chứng minh hằng thời gian. Không đổi PmAuth, không xóa MD5, không sửa credential thật|
|Chuẩn bị Laragon/DB cô lập|LOCAL_RUN/LOCAL_DB_TEST_PLAN, commit1b041db; fixture seed/load có guard,33pure guard checks; chỉ chuẩn bị, chưa seed/migration/DB|
|Rà khóa/grants, concurrency và migration host|36/36 vị trí locking SELECT ITA; harness hai context/Promise.all chưa chạyDB;005/006 phải đối chiếu metadata hiện tại, không suy thiếu từ12 bảng lịch sử; commite7f6001|
|Gói cộng dồn kiểm host|Source81f3c55, ZIP49.319.548byte, kiểm cấu trúc/CRC/hash thật; nguồn host chỉ4samplefiles không chứng minh toàn bộ release. Agent không upload; ảnh user không nhận diện duy nhất gói/commit|
|Tương tác3D|3830a4c hover →22b025e fullscreen/nhãn →46ef989 drag360°. Cuối:28orbit+32pointer/tap+23fullscreen+23WebGLchecks, Smarty/syntax; WebGL/FullscreenAPI thật trên dữ liệuMOCK, khôngDB/host|
|Gói deltaFE3D|5file nguồn46ef989, ZIP10.993byte; kiểm lạiCRC/hash/manifest;652c912 hồ sơ. ZIP cộng dồn cũ không tự có thay đổi này|
|Bộ kiểm thủ công|36case/13menu, guide+2XLSX+plaintextbadXLSX+CSV/sổID;17ae623/3045b62.8checks đọc lại file,71deprecation trên8.5; user chưa chạy hết, CSV đểChưa kiểm|
|Ảnh kiểm trên host do user gửi|Chứng minh giao diện/số liệu hiển thị tại thời điểm ảnh. Không đủ chứng minh toàn bộ quyền, SQL, persistence, export, rollback, TLS/grants hoặc nghiệm thuDB|

Mã đầy đủ/hashes nằm trong DEPLOY_LOG và các VERIFY liên quan, không chép lại rawlog, nội dung config, cookie, khóa hay dữ liệu thật vào bản tổng kết này.

## 2. Bài học từ lỗi và bằng chứng

### A. Nhận diện hash khác với xác thực hash

- **Lỗi sản phẩm đã sửa:** dùng password_get_info để nhận diện bcrypt có thể bỏ sót2a/2b/2x mà password_verify vẫn chấp nhận. Không được gọi một lần verify là an toàn nếu classifier chặn user hợp lệ.
- **Bằng chứng:** regression được tái hiện trong S26; sửa nhận diện format/cost, giữ guard mật khẩu>72byte và không fallbackMD5 khi modernhash không rỗng.686checks có counter service/downstream; PmAuth giữ nguyên.
- **Lập luận cần giữ:** không suy bcrypt/Argon2 có cùng chi phí. Argon2 được user duyệt ngoại lệ một realverify, không thêmdummy. Đếm verify không chứng minh constant-time; nativeMyISAM không rollback cùngInnoDB. Hai nơi giữ policy phải rà đồng bộ khi một nơi thay đổi.

### B. Xác minh runtime bằng binary, không bằng dự định hoặc tên thư mục

- User ban đầu gửi placeholder, sau đó gửi đường dẫn cóPHP8.5.10; chạy đúng php.exe -v xác nhận8.5.10. Host được user báo8.3, nhưng không có testCLI/FPM8.3 củaagent.
- Không chạy PHP trênPATH rồi đổi nhãn thành8.3, không tự tải runtime hoặc kết nốiDB để hoàn thành một mục chưa được cấp quyền. Ghi rõ “chưa chạy” và tiếp tục phần độc lập.
- Việc user đổi từ kế hoạch local sang tự upload kiểmhost là quyền đóng gói mới; không suy thành quyền agentđăngnhập/DB/deploy hoặc tự nghiệm thu.

### C. Phân loại bằng chứng trước khi sửa

- **Ảnh nhỏ:** ảnh Báo cáo hiện zoom50%. Kích thước nhỏ trong ảnh đó có bằng chứng về zoom; trước khi đổiCSS phải đưa100% và đo bounds/computedstyle. Không khẳng định mọi ảnh nhỏ cùng nguyên nhân.
- **ẢnhHTTPS đỏ:** “https://” và “Không bảo mật” không đủ chẩn đoán lỗi chứng chỉ cụ thể. Chưa có chi tiết certificate, không nóiTLS đã đạt hoặc tự bậtHSTS.
- **Ảnh đọc được dữ liệu:** không suy dashboard không503 là đã xác minh isolation của kết nối ứng dụng; không suy một ticket hiển thị là CHECK/rollback/concurrency đều đạt.
- **Import0hợp lệ/2lỗi:** ảnh ghi mãloại/bộphận không tồn tại hoặc ngừng hoạt động. Nútconfirm bịguard là hành vi dự kiến, không phải lý do bỏguard. Đối chiếu category_code/department_code, trạng thái vàscope rồi upload/xemtrước lại; không bật nút bằngDevTools hoặc sửaDB.

### D. Fixture mẫu phải chỉ rõ phần chưa được xác minh

- File “2dòng hợp lệ” chỉ đúng cấu trúc và dữ liệu mẫu sau khi user điền mã bộphận thật và tạo loạiTEST26-CAT. Tên file dễ khiến người dùng nghĩ có thể nhập ngay; từ nay phầnREADME/tên mô tả cần nhấn mạnh “mẫu cần điền mã”, không gọi là hợp lệ trênhost trước validation.
- Mã bộphận không phải tên hoặc ID. Dùng export của asset được phép để đọc code, không đoán từảnh hoặc tạoUI/bảng không tồn tại.
- Tạo fixture qua writer thật và đọc lại kiểm cấu trúc/ôSTRING là bằng chứngfile, không phải pass lookup/scope/DB. Bản dựng đầu thiếu requireItaInput: lỗi builder, đã sửa; không coi đó là lỗi import trênhost.71deprecation8.5 được ghi, không che mask/nângvendor cho đẹp kết quả.
- Không nhồi thêm sheet hướng dẫn vào workbookimport cần1sheet/16cột; hướng dẫn để file riêng. Văn bản trơ đổiđuôi đủ thử định dạng, không cầnPHP/EXE thật.

### E. Lập chuỗi dữ liệu theo trạng thái thực và mốc kiểm

- Review bắt được TC26 định choSupport xử lýP1 sau khiP1 đãresolved/ready ởTC20. Trạng tháiresolved không mở lại; đây là lỗi logic hướng dẫn.
- Đã dùng P4mới trênM6 sau khi cấp scopeSupport; ghi rõ M6 sauP4 có2phiếu/P3+P4,tổng70000,repair1. Kết quả TC22/24 trướcP4 giữ mốc trước, không ép hai thời điểm cùng số.
- Khi hướng dẫn theo menu, một số màn phụ thuộc dữ liệu tạo ở màn sau. Ghi bước quay lại rõ ràng; đừng hứa chỉ một lượt đọc từ trên xuống là kiểm đủ mọi trạng thái.
- Ngân sách135,3triệu cần lịch sử3năm/cohort chính xác. HostUI mởticket ở hiện tại, không thể giả quákhứ; kiểm snapshot/đầu vào khả thi và để mục thiếu lịch sửChưa kiểm, không SQLghi làm lối tắt.

### F. Chốt ngôn ngữ tương tác trước khi triển khai

- “Đi theo con trỏ” ban đầu được hiểu là hover±gócnhỏ. User sau đó yêu cầu360° và thấy dọc còn hạn chế. Cần phân biệt ngay: hover/parallax, kéo orbit360°, pan vàzoom là các thao tác khác nhau.
- Đã giữhovernhẹ và thêm draghai trục khôngclamp; pointercapture cho kéo ngoài mép, thả giữgóc, khônginertia. Không tuyên bố chỉrê chuột khôngbấm có thể quay liên tục360°.
- Không bỏ nút cũ; cập nhật mô tảUI/aria/cacheversion và test thao tác thật theo contract mới. Không thay thuật toán/quyền dữ liệu để phục vụ hiệu ứng.

### G. Thay pointer phải rà tất cả thiết bị nhập

- **Regression phát hiện bởi review:** chuyển pick từpointerdown sang dragmouse/up làm mất tap touch/pen vì down chỉ nhậnmouse.
- Sửa: tách tapstate khôngcapture/ngănscroll; ngưỡng4px cảmove vàup, cancel/leave khôngselect; chuột kéo vượtngưỡng khôngpick.32pointerchecks gồm mouse/touch/pen, normal/reduced-motion; không xóa assertion bảo vệ chọnthiếtbị.
- Rà cleanup trên2D/filter/rờitrang/contextlost/disposegiữa capture, không chỉ khi scene đứng yên. HủyRAF/listener/observer vàgeometry/material/GPU; không để retry hoặc pointerup muộn phục hồi scene cũ.

### H. Toàn màn hình là một bề mặt thao tác riêng

- **GapUX tìm bởi review:** fullscreenchỉcanvas làm nútxoay ởngoài inaccessible. Với reduced-motion tắthover, người dùng bànphím không còn cách xoay.
- Sửa bằng toolbar trongfullscreen cóxoay/chọnthiếtbị/thoát, focus/ariađồngbộ, Esc; test keyboard thật và so frame thay đổi, không chỉ nhìn nút tồn tại.
- fullscreenElement có thể đổi trước fullscreenchange/toolbarvisible; test phải chờ UIevent đúng. Escautomation ban đầu khôngthoát; không gọi APIbù rồi ghiEscPASS. Controller scopedhandler và test escapeExited=true xácnhận cuối.
- Promise rejection khi browser từchối fullscreen phải giữ khung3D/2D hoạtđộng, không ép policy/HTTPS/config server để làmtestqua.

### I. Nhãn3D cần hình học theo camera và kiểm mobile

- Nhãn theo localY có thể rơi xuống dưới khối khi lật. Đã chiếu8góc khối lênscreen để tìm méptrên.
- Mobile8node có nhãn gần/chồng: đẩy nhãn lên có giới hạn, đo boundingboxes ở768/390; không chỉ nhìn desktop.
- textContent, pointer-events:none vàdropdown/danh sách tên đầyđủ giúp giữ escaping/picking/accessibility. Không tuyên bố thuậttoán hiện bảođảm200node khôngchồng khi hếtchỗ.
- Tất cả máy vẫn dùng khối cơ bản và màu trạng thái là hạn chế **chưa sửa**. Yêu cầu mới về printer/desktop/monitor/kiểu khác phải dùng hình dáng vàlegend/type riêng, không ghi đè nghĩa màu trạng thái hoặc tự suy loại nghiệp vụ chính xác từ tên.

### J. Phân biệt lỗi test, lỗi công cụ và lỗi ứng dụng

- Spy WebGLRenderer.prototype.render không bắt được renderer dùng method trêninstance; poseundefined/timeout là instrumentation sai. Sửa bắtgroup thực quaScene.add và drawcalls, không sửa sản phẩm theo kết quảspy chưa đúng.
- EADDRINUSE do preview cũ cònchạy: xác minhPID/commandline của đúngserve-preview, khôngkilltùytiện. CLIAppDataEPERM/CIMdenied làgiới hạntool; dùng quyềntool đãchấpnhận, không nhầm với lỗiapp hoặc tự chuyển sanghost.
- PowerShell truyền inlineUnicode có thể khiến dictionary Việt khôngkhớp. Đọc lại file thật; dùng fileUTF8/apply_patch thay vì tin thôngbáo“normalized”. Lệnhchạyxong không đủ chứngminh nội dung đãđúng.
- Không che lần lỗi bằng retryPASS: giữ nguyên nhân cụ thể vàphạmvi. Test cần assert hànhvi/DOM/frame/response, không chỉ cóclass/icon/văn bản.

### K. Đóng gói phải dựa nguồn cố định và đọc lại thành phẩm

- Review phát hiện manifest thiếu release_id/buildUTC/buildenvironment/PHPthực; đã bổ sung trướcbuildthật. CSV/XLSX/ZIP cóhash không thay provenance hoặc hiệu lực trênhost.
- Mở ZIPthật đối chiếu allowlist/exclusions/case/link/CRC/size/hash/manifest/checksums, hashmetadata ởsidecar tránhselfhash; không tin riêng manifest tựkhai trongZIP.
- Mỗi cải tiến sau gói phải nói rõ **gói cũ chưa có thay đổi**, không ghiđè ZIP/hash cũ. GóiFE5file khác gói nền1401runtime vàkhác bộkiểmthủcông; khôngđưa tests/tmp/mock/config vào runtime.
- Bộkiểm thủcông chỉgiải nénlocal, XLSX chọn quaformapp; khôngupload bộhướngdẫn/CSV/filetest vào public_html bằngFTP.

### L. Quyền hạn và giao tiếp cũng là một phần kiểm chứng

- Người dùng cho dùng tài khoản hiện tại để kiểmAdmin với dữliệuTEST, không đồng nghĩa được hạvai trò/thu quyềnAdminduynhất. Cần nêu2cột native có thểghi khi login; quyềnManager/Support đểChưa kiểm khi thiếu userriêng.
- Không lặp yêu cầu DBtestlocal khi userđãchọn kiểmhost; khi họ đổi lại sanglocal, chuẩn bịscript nhưng vẫn chờ câu xácnhậnđúngschema trướckếtnối. Kế hoạch không phải phêduyệt thựcthi.
- Không nói “xong toàn bộ phase” dựa testmock. Báo chính xác thayđổi, sốcheck/phạmvi, phầnchưachạy, commit vànơiđọclại; hướng dẫn nên dùng câu Việt có khoảngtrắng, tên nút đúngUI và từng bước ngắn.

## 3. Rà ràng buộc mới — CHỈ ĐỌC, chưa sửa

| Nhóm | Kết luận source cần giữ khi tiếp tục |
|---|---|
|Tên/mã máy|Tên200/mã64, trimkhôngrỗng; không có whitelist “chỉchữ”. Không tự cấm số/dấuViệt/punctuation. PHPđếm codepoint, maxlengthHTMLđếmUTF16, cần rà emoji và trước/sautrim|
|Ngày/giá tài sản|Ngày hợplệ từnăm1000, bảohành≥ngàymua; không có rule cấmngàymuatươnglai. Giá optional16chữsốnguyên+2lẻ, khôngâm; null khác0|
|ID/note|PositiveID canonical1..999999999, khôngleadingzero/decimal/exponent; NUL/UTF8/maxphải đồngbộ vớiserver|
|Giấy phép|NgàyGregorian/order nhưng PHP hiện chưa chặn trước1000; cost hiện cho leadingzero khácasset. Không âmthầm gom cùngvalidator rồi đổicontract. Seats≤1000000, lýdooverride1000|
|Ticket/assignment|Costrequired16+2, handled_date giữa ngày mở vàhômnayVN, note4000; due_return≥hômnayVN. Phải xử lý ngày theo timezone, không theoUTC/localbrowser ngẫu nhiên|
|Forecast/network/access/login|Units≤1000/percent≤100 với6lẻ; endpointkhácnhau; nonadmincầnscope; password≤1024**byteUTF8**khôngNUL, không phải1024ký tự. FE chưa có đủguard, chưa sửa/chạykiểm mới|

Nguồn: review source độc lập cácItaInput/Validation/Rules vàformJS, không phải DB/HTTPintegration. Nếu muốn cấm ngàymuatươnglai, siết tên/ký tự, đổi normalizationmoney hoặc đồngnhất minyearlicense thì cần BE-GATE mới; không đưa những thayđổi này vàoCSS/FE để láchgate.

## 4. Việc dở và bước tiếp tục sau khi user cho làm lại

Yêu cầu gần nhất: cải tiến toàn bộ giao diện trong hướnghiện có; thôngbáo/lịchsửphânbổ; iconBudget/Report khácnhau; bật/tắt hiệuứng rõkhácnhau; hìnhdáng/màu riêngprinter/desktop/monitor/server/network; ràràngbuộc; **userkiểmlocaltrước**, chưauphost.

**Mới khảo sát, chưa sửa runtimeFE/PHP cho đợt này.** Đã đọcskill/contextImpeccable vàsource, không khởiđộnghooks/daemon. ReviewAgent đãtrảcontractvalidation. User chưa cần thêm loại đặcbiệt, nhưng muốn sau này tựthêm hình3D cho thiếtbịmới. Gateupload/storage/modelpersist mới chỉ là hướng cầnsoạn, **chưa có endpoint/upload/modelcustom/storage hoặcBE-GATEđượccấpquyền**; không được ghi đãbuild.

Khi tiếp tục: đọc AGENTS/PROGRESS/DECISIONS/LESSONS vàgitstatus; rà lại finding trên source hiện tại; làm FE theo chức năng nhỏ, giữ contracts/serverguards; test thực browser1366/768/390/errors/keyboard/reduced-motion vàreview cuối. Tách thayđổiPHP/nghiệpvụ mới hoặc uploadmodelthànhgate cụthể córủiro/storage/format/giớihạn/rollback. Khôngtruycậphost/DB, khôngđónggói/upload/push/merge/tag vìchỉđọc bản tổngkết này.
