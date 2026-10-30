# InfraCore — Bộ kiểm thủ công từng bước theo menu

Soạn26/10/2026, dựa source runtime46ef989 và các template/JS đang có. **KẾ HOẠCH CHƯA CHẠY TRÊN HOST**. Bộ này hướng dẫn bạn tự nhập dữ liệu giả qua giao diện; agent không đăng nhập, truy cập host hoặc chạy SQL. File kiểm thử tải lên bằng nút Chọn file trong ứng dụng, **không copy bộ này vào public_html/FileZilla**.

Đi theo13 mục menu từ trên xuống. Tổng quan/Cảnh báo/Nhật ký được xem trước khi có dữ liệu rồi quay lại sau khi tạo dữ liệu. Khi ghi kết quả dùng **«kiểm thủ công trên host, phạm vi hạn chế»**, không tự nghiệm thu DB/rollback/concurrency từ việc nhìn thấy màn hình.

## 0. Chuẩn bị một lần

1. Backup source/config/khóa và DB bằng công cụ quản trị được phép; không gửi bản dump/password/key/cookie lênchat. Không chạySQLghi, seed, reset hoặc sửa dc_users trực tiếp. Đảm bảo đã cập nhật gói nền và5file delta3D mới nếu muốn kiểm360°/fullscreen.
2. Mở đúng website bằng HTTPS. Nhấn **Ctrl+0** để zoom100%, sau đó **Ctrl+F5**. Nếu trình duyệt báo đỏ Không bảo mật: ghi CHƯA ĐẠT phầnTLS, mở thông tin chứng chỉ để quản trị xử lý; không coi cóhttps trongURL là chứng chỉ đạt.
3. Chỉ dùng tài khoản TEST đã được quản trị tạo trước: một AdminIT, một Quản lý, một Hỗ trợ. **ITA không có nút tạo usernative**; không thấy user test thì bỏ qua kiểm đa vai trò, không lấy user vận hành để sửa quyền. Login thành công có ghi `dc_users.last_login`, có thể nâng `password_hash` khi MD5/needs_rehash và≤72byte; MD5 `password` giữ nguyên. Chỉ login user test đã chấp nhận ảnh hưởng này.
4. Admin đăng nhập trên cửa sổ thường. Khi đến kiểm vai trò, dùng **profile/browser khác** cho Quản lý và Hỗ trợ; các cửa sổ InPrivate của cùng profile thường dùng chung cookie, không coi là hai phiên độc lập.
5. Chuẩn bị hai bộ phận **được phép nhận dữ liệu giả**, đặt tên viết tắt **A/B** trong sổ. Ví dụ A=Công nghệ thông tin, B=Kế toán chỉ khi bạn được phép thử ở đó. ITA hiện không có màn tạo bộ phận; nếu chỉ cóA thì kiểmA, phầnB/phạm vi đểCHƯA KIỂM. Không đổi tên/bộ phận thật để giống ví dụ.
6. Tạo thư mục trên máy `InfraCore-UAT-26` để lưu file tải, ảnh đãche và kết quả. Giải nén bộ kiểm vào đó. Mở `KET_QUA.csv` bằng Excel hoặc trình soạnthảo; các dòng ban đầu là **Chưa kiểm**.
7. Dùng tiền tố **TEST26-** cho mọi hồ sơ mới. Nếu trước đó đã dùng, đổi đồng bộ thành TEST27- trong hướng dẫn và cácXLSX trước chạy; không xóa dữ liệu cũ để tái sử dụng mã. Không ghi mật khẩu vào sổ, chỉ ID/mã hồ sơ test.

### Bộ dữ liệu sẽ nhập

| Ký hiệu | Mã tài sản | Tên nhập | Bộ phận | Ngày mua / Giá(VND) | Bảo hành | Dùng để |
|---|---|---|---|---|---|---|
|M1|TEST26-01|TEST26 - Máy 1 cũ|A|2015-01-01 /20000000|2017-01-01|Tuổi thọ, thay thế, cảnh báo|
|M2|TEST26-02|TEST26 - Máy 2|A|2026-10-26 /20000000|2027-10-26|Giao/thu hồi, license, network|
|M3|TEST26-03|TEST26 - Switch 3|A|2026-10-26 /10000000|2027-10-26|License/network|
|M4|TEST26-04|TEST26 - Máy 4|B|2026-10-26 /15000000|2027-10-26|Ticket thành công/phạm vi|
|M5|TEST26-05|TEST26 - Máy 5|B|2026-10-26 /15000000|2027-10-26|Ticketfailed/mở lại|
|M6|TEST26-06|TEST26 - Máy 6 thiếu giá|B|2026-10-26 /**để trống**|2027-10-26|Thiếu dữ liệu|

Nếu kiểm sau26/10, thay ngày mua mới thành **ngày kiểm hiện tại**, bảo hành một năm sau; M1 giữ ngàycũ. Chọn **một loại phần cứng đang có và được phép**, ưu tiên loại đã có tuổi thọ5năm trong hệ thống. Ghi tên loại là **C**. Nếu không có tuổi thọ, vòng đời phải báo thiếu, không tự coi đó là lỗi. Form loại mới chỉ có mã/tên/trạng thái, **không có ô sửa tuổi thọ**. Không sửaDB để thêm tuổi thọ.

## 1. Tổng quan

### TC01 — Mở và ghi số liệu ban đầu

1. Bấm **Tổng quan** ở menu trái. Chờ dòng báo đãtải hoặc thông báolỗi rõràng.
2. Chụp/phầnghi sốtài sản, tìnhtrạng, sốcảnhbáo và **Số liệu tạiHH:mm**. Ghi baseline, không mặcđịnh DB chỉcó1tài sản từảnhcũ.
3. Bấm ô **Bộ phận**, chọnA; bấm **Áp dụng**. Xem biểuđồ và bảngđối chiếu ngay dướibiểuđồ cùngbộ lọc. Bấm **Làm mới** một lần; giờ số liệu phải phảnánh lầnđọc mới.
4. Nếu lỗi503, ghi nguyên thông báo/mãthamchiếu đãche; dừng các mục phụ thuộc báo cáo, **không đổiisolation/grants**. Màn tảiđược không tự chứng minh isolation kết nốiPHP đãđượcxác nhận.

**Mong đợi:** không treo/stacktrace, cóbảng đối chiếu, không tựrefreshliêntục. Sau tạo6tài sản ởmục2 sẽ quaylại kiểm tăng6, khôngđoántổng trước.

## 2. Tài sản CNTT

### TC02 — Kiểm quản lý loại bằng dữ liệu riêng

1. Bấm **Tài sản CNTT** → **Quản lý loại tài sản**.
2. Bấm **Thêm loại mới** nếu đang hiện form sửa. Điền **Mã loại=TEST26-CAT**, **Tên loại=TEST26 - Loại import**, **Trạng thái=Hoạt động**.
3. Bấm **Lưu loại tài sản**. Nhập TEST26 vào ô **Tìm mã hoặc tên loại** → **Tìm loại**. Phải tìmđược loại vừa tạo.
4. Chọn dòng loại mới, sửa tên thành `TEST26 - Loại import đã sửa` → **Lưu loại tài sản** → tìm lại để đối chiếu. Giữ trạng tháiHoạt động cho mục3; không ngừng các loại đang vận hành. Bấm **× Đóng danh mục**.

### TC03 — Thêm M1 từng cú bấm

1. Bấm **Thêm tài sản**. Ô **Mã tài sản** nhập TEST26-01; **Tên tài sản** nhập TEST26 - Máy 1 cũ.
2. Bấm **Loại tài sản** → chọn loạiC đã ghi ởphần0. Bấm **Bộ phận** → chọnA.
3. Điền Thương hiệu=TEST, Model=UAT26, Sốseri=TEST26-S01, Hệđiềuhành=TEST, Ngày mua=2015-01-01, Giá mua=20000000, Bảo hànhđến=2017-01-01, Nhàcungcấp=TEST - không mua thật, Vịtrí=KHU TEST, Cấu hình/thôngsố=Dữ liệu giả kiểm chức năng.
4. Với ôdate, chọn ngày bằng lịch hoặc nhập theo địnhdạng browser đang hiển thị; đối chiếu ngày sau lưu, không nhầm dd/mm với yyyy-mm-dd. **Giá nhập20000000, không dấu chấm/ngănnghìn**.
5. Bấm **Lưu thay đổi** một lần → chờ kết quả. Gõ TEST26-01 trong **Tìm tài sản** rồi Enter/chờlọc; bấm mũitên mở hồ sơ cùngdòng.
6. Xem tab **Thông tin**, đối chiếu mã/tên/bộ phận/ngày/giá. Đóng hồ sơ, tải lại trang; tìm lại để kiểm đã lưu, không chỉtoast.

### TC04 — Tạo M2 đếnM6

1. Với mỗi dòng ởbảngdữ liệu, bấm **Thêm tài sản**, điền mã/tên/bộ phận/ngày/giá/bảo hành tươngứng; loạiC giốngM1, seri lần lượtTEST26-S02…S06, cácôkhác dùnggiátrịTEST như trên.
2. Bấm **Lưu thay đổi** → tìm bằng mã → mởđọc lại. **M6 giá mua đểtrống, không nhập0**.
3. Cuối cùng tìm `TEST26-` phải có6 hồ sơ mới (trước import). Ghi mã/ngày/bộ phận vào sổ; không đếmcácfile lỗiimport chưaconfirm.

### TC05 — Sửa, ghi chú, lịch sử và validation

1. Tìm M2 → mở hồ sơ → **Sửa thông tin** → đổi **Vị trí=KHU TEST - bàn 2** → **Lưu thay đổi**. Mở lại kiểm.
2. Bấm **Ghi chú** → nhập `TEST26 kiểm ghi chú` → lưu. Mở tab **Lịch sử**, mở một dòng để xem trước/sau; chỉ xem dữ liệu test, không gửiảnh cóuser/thôngtin thật.
3. Thử **Thêm tài sản** với mã TEST26-02 đã có, tênTEST trùng và loạiC → lưu. Phải bị từ chối trùng, tổng vẫn6; đóngform.
4. Thử form mới mã TEST26-BAD, giá=-1 hoặc bỏ trốngTên → lưu. Browser/server phảichặn; không có hồ sơ BAD. Hủyform, không điềngiátrị khác để vô tình tạo hồ sơ.
5. Không kiểm“ngày mua tươnglai phải từ chối” như một yêu cầu: validator hiện cho ngàyhợp lệ, vòng đời đánhdấu tuổi khônghợp lệ. Không tạo hồ sơ ngoài kế hoạch chỉ để đổi kết quả.

### TC06 — Bộ lọc và xóa mềm riêng

1. Bộ lọcTìmtài sản=TEST26-, Bộ phận=A → ghi số3; chọnB → số3; chọnTấtcả →6. NếuBkhông có, ghiCHƯA KIỂM phầnB.
2. Tạo thêm tài sảnriêng TEST26-DEL theoM2 nhưng tênTEST26 - Chỉ xóa mềm. Mở nó → **Xóa mềm** → nhập lý do `TEST26 kiểm xóa mềm` → **Xóa mềm** ở hộpxác nhận.
3. Tìm lại TEST26-DEL không còn trong danhsách đang dùng; không chọnM1–M6 đểxóa. Giữ bằng chứng audit ởmục10; không ClearTrash/SQLDELETE.

## 3. Import / Export

### TC07 — Lấy đúng mã bộ phận và tải mẫu ứng dụng

1. Trở vềTài sản: lọcM2 **TEST26-02**. Bấm **Import / Export** → tab **3. Xuất dữ liệu** → Trạng thái=Tấtcả → **Xuất Excel**.
2. Mở file tải được bằngExcel, phải có M2. Cột `department_code` (cộtF) là mã thật củaA; ghi vào sổ **MA_A**. Không đoán mã từtên hiển thị hoặc sốID. CộtB `category_code` cũng là mã loạiC; chỉ đọc, không nhập sửa dữ liệucũ vào hệthống.
3. Nếu file không phảicohortM2, không dùng dữ liệuthật để nhập lại. Hủy đọc đó và kiểm bộ lọc/chọn đúngM2; không gửi file thật choagent.
4. Bấm tab **1. Chuẩn bị file** → **Tải mẫu Excel**; mở file mẫu, đối chiếu16cột. Không tự đổi tên/thứtựcột.

### TC08 — File lỗi: chỉ xem trước và tải lỗi

1. Mở `02_IMPORT_CO_LOI.xlsx` trong bộ kiểm **trên máy**. Thay mọi `DIEN_MA_BO_PHAN_A` ởcộtF bằng MA_A. Giữ lỗi đãđặt: giá âm, trạng tháisai, mã trùng/Mã loạikhôngtồntại. LưuAs bản mới `.xlsx`.
2. Bấm **2. Chọn file & xem trước** → **Chọn tệp** → chọn bản lỗi đãsửa mãA → **Open/Mở** → bấm nút kiểm tra/tải lên bên cạnh (nút `import-upload`).
3. Chờ bảngdòng, đọc cột **Kết quả kiểm tra**. Phải có lỗidòng, vàkhông được nhập cảlô. **Không bấm Xác nhận nhập toàn bộ lô**, kểcả nếu nút khôngdisable; ghiFAIL nếu UI cho lôlỗi như hợp lệ.
4. Bấm **Tải Excel lỗi**, mởfile tải; cột lỗi phảiđọc được, chuỗigiả không biếnthành công thức. Trở vềTài sản tìm TEST26-MIX/TEST26-IM01: chưa được tạo từlôlỗi.

### TC09 — Import2 dòng hợp lệ

1. Mở `01_IMPORT_2_DONG.xlsx` local; cộtB giữ TEST26-CAT (đã tạo TC02); thay placeholdercộtF bằng MA_A. Không thay mã TEST26-IM01/IM02 nếu chưa tồn tại; nếu đã có thì đổi đồngbộ mãmới trước kiểm.
2. Quay Import tab2 → Chọn filebảnhợp lệ → bấmkiểm tra/tảilên → chờ bảng. Phải có **2dòng hợp lệ/0lỗi**. Nếu khôngđúng, dừng sửa file, khôngconfirm.
3. Bấm **Xác nhận nhập toàn bộ lô** **một lần**; chờ hoànthành. Nếu thông báo“chưa biết kết quả”, bấm **Kiểm tra trạng thái lô**, không bấmconfirmliêntục.
4. Trở về Tài sản tìm TEST26-IM → phải2thiết bị; mở từnghồ sơđọc lại. Tổng TEST26- là8 hồ sơhoạtđộng (6tay+2import; DEL đãxóamềm khôngtính).
5. Chọn lại chínhfile hợp lệ →kiểm tra: mã trùng phải bịbáo, khôngthêm2bản sao. Khôngconfirmlôtrùng.

### TC10 — Tệp giả và export

1. Tab2 chọn `03_KHONG_PHAI_XLSX.xlsx` (chỉvănbảntrơ, khôngPHP/EXE) →kiểm tra. Phải từ chối địnhdạng/XLSX, không tạotàisản. Nếu UI lỗi mà không thấybuttonkiểm tra, ghitrạng tháibrowser thực.
2. Tài sản lọcTEST26- rồi Import tab3 → **Xuất Excel** → mởfile tải. Kiểm8dòngđúngcohort,16cột, tiếngViệt, cộtgiáM6 trống khác0. Nếu bạn thêmhồ sơ phụkhác thì đối chiếutheosổ thực, khôngép8.
3. Không dùng host để nạp5000/5001 chỉvì bộkiểm này. Boundary/tải lớn làkế hoạch riêng chưachạy.

## 4. Phân bổ & thu hồi

### TC11 — Giao M2 cho bộ phận, PDF và thu hồi

1. Bấm **Phân bổ & thu hồi**. Ô **Tìm thiết bị theo mã, tên hoặc seri** nhập TEST26-02 → **Tìm thiết bị**.
2. Ô **Thiết bị trong phạm vi của bạn** chọnM2 → **Tải lại hồ sơ**. Kiểm đang dùng/khôngpending; nếu có lượtgiaocũ củaM2 ngoàibộkiểm thì dừng đối chiếu, khôngthu hồ sơkhác.
3. **Bàn giao cho=Bộ phận** → **Người/bộ phận nhận=A**. Hạnthuhồi đểtrống hoặc ngày kiểm+7ngày. Ghi chú=`TEST26 giao M2` → **Xác nhận giao thiết bị**.
4. Chờ lượtđangmở và lịch sử xuất hiện; bấm **Tải PDF lượt đang mở**, mởfile. Kiểm mãM2/A/sốbiênbản/tiếngViệt; bàn giao cho bộ phận **không có chữký xác nhậnđiệntử cá nhân**, không ghiFAIL vì thiếu chữký đó.
5. Tại **Lý do/tình trạng khi thu hồi**, nhập `TEST26 thu hồi kiểm thử, máy nguyên trạng` → **Xác nhận thu hồi thiết bị**. Đọc lại lịch sử đã có ngàytrả, không còn lượtđangmở.

### TC12 — Giao cho user test và xác nhận nhận (nếu có)

1. M2 tải lạihồ sơ → Bàngiaocho=Nhânviên → ôTìm người nhận nhập usernameTEST → **Tìm người nhận** → chọn đúng user test trongdropdown.
2. Ghi chú `TEST26 giao cá nhân` → **Xác nhận giao thiết bị**. Trong **phiên của chính người nhận** mởPhân bổ/M2 → bấm **Xác nhận tôi đã nhận thiết bị**.
3. Trở Admin → Tải lạihồ sơ/PDF: chỉ người nhận thực đượcxác nhận, Adminkhôngkýthay. Saukiểm Admin thu M2 lại nhưTC11. Không cóuser testđượccấpITA thì ghiCHƯA KIỂM, không dùnguser thật.

## 5. Trung tâm cảnh báo

### TC13 — Cảnh báo bảo hành và bộ lọc

1. Bấm **Trung tâm cảnh báo** → Bộ phận=A → **Áp dụng** → **Làm mới**. Tìm M1 TEST26-01; bảo hành2017 đãquáhạn nên kiểm cócảnhbáo đúnglý do.
2. Ô **Mã hoặc tên** nhậpTEST26-01 → **Lọc cảnh báo**. Đổi **Mức độ/Lý do** tươngứng rồi lọc lại. Không coi mọi lý do đều chứaM1.
3. So **sốkết quảkhớp** với **tổngcảnhbáo đanghiệu lực**. Chuông đếm cảnhbáođanghiệu lực, không phải chưađọc; nhập filterkhônglàmchuôngthànhsốdòngđanglọc.
4. Giữ trang đểquaylạiTC18 sau tạo giấy phépcảnhbáo. Nếungưỡngcấu hìnhhost khác mặcđịnh thì ghi giátrịthực, khôngđoán30/7.

## 6. Phần mềm & giấy phép

### Cách lấy ID máy an toàn — chỉ đọc, không tạo phiếu

Khi formcấp license yêu cầu **ID số**, đó không phải mã TEST26-02. Cách lấyquaUI: bấmBảo trì & hỗ trợ → **Tạo phiếu mới** → ô **Mã, tên hoặc sốseri** nhậpTEST26-02 → **Tìm thiết bị** → bấm đúngkết quảM2. Đọc **ID thiết bị** tựđiền, ghiM2_ID vào sổ. **Không bấm Tạo phiếu**. Làm tươngtựM3, rồi trở về **Phần mềm & giấy phép**. Bướcđọc phụnày không được ghi là đãkiểm ticket.

### TC14 — Tạo giấy phép giả1chỗ và xem key

1. Bấm **Thêm giấy phép**. NhậpTênphầnmềm=`TEST26 - License 1`; Nhàcungcấp=TEST; Hìnhthức=Theo máy; Tổng số chỗ=1.
2. Ngàybắtđầu=ngày kiểm; Ngàyhết hạn=ngày kiểm+30ngày hoặc muộnhơn; Chi phí=1000000; Trạng thái=Đang sử dụng; Key mới=`TEST-KEY-26-KHONG-PHAI-LICENSE-THAT`.
3. Khôngtick vượt chỗ. Bấm **Lưu giấy phép**. TìmTEST26 → **Tìm giấy phép** → **Mở hồ sơ**; key trongdanhsách phải bịche.
4. Tại **Xem key đầy đủ**, nhập lạimật khẩu củaAdminkhôngchia sẻ → **Xác nhận & xem key**. Đốichiếu key giả, chờ10giâyphảitựẩn; **Ẩn ngay** cũngẩn. Không chụpkey/password/token (dù mẫu) vàoảnh gửi chung. Không lặp5lầnsai làmkhóaphiên.
5. Nếu503 thiếukey/env/GDkhác, ghi thông báo vàdừng chức năngphụthuộc; không tạokhóa mới đểghiđè ciphertexthost.

### TC15 — Cấp, chống cấp vượt chỗ, thu hồi

1. Tronghồ sơLicense1, ô **ID máy/tàikhoản/bộ phậntheohìnhthức** nhậpM2_ID → **Cấp phát**. Phải hiển thị1/1, lịch sửcóM2.
2. NhậpM3_ID → **Cấp phát**, **khôngtickAdmin xác nhận cấpvượt mức**. Phải từ chối hoặc409yêucầutải lại; tải lạihồ sơvàkiểmmới1chỗđangcấp. Không mặcđịnh mọi403 là đạt nếuuserhếtphiên/sai scope.
3. DòngM2 tronglịch sửcấp → **Thu hồi**. Đọc lại0/1 vàdòngM2 cóngàytrả; cấpchoM3 rồiđọc lại1/1. Khôngtựxóagiấy phép.

### TC16 — Cấp vượt có lý do (Admin, tùy chọn)

Chỉ licensegiảLicense1: M3 đanggiữ1/1 → nhậpM2_ID → tick **Admin IT xác nhận cấp vượt mức** → lý do `TEST26 kiểm override giấy phép giả` → Cấp phát. Đọc lại2/1 vànhật kýoverride. Giữđể kiểmcảnhbáo rồi **thu hồiM2** đưa về1/1. Nếu khôngmuốn kiểmoverride ghiCHƯA KIỂM, khôngápdụnglicensevận hành.

### TC17 — Giấy phép hết hạn giả

1. ThêmLicense2 tên`TEST26 - License hết hạn`, Theomáy, seats1, bắtđầungày kiểm-60ngày, hết hạnngày kiểm-1ngày, cost0, key giả=`TEST-EXPIRED-26` → Lưu.
2. Mở hồ sơ → nhậpM2_ID → Cấp phát. Phải từ chối giấy phéphết hạn; không được cóallocationhoạtđộng.

### TC18 — Quay lại cảnh báo

BấmTrung tâm cảnh báo → xóaôMã/tên cũ → Bộ phận=Tấtcảtrongphạm vi → Áp dụng/Làm mới. TìmLicense2/TEST26, kiểm lý dohết hạn. Nếu đã làmTC16, thời điểm2/1 cócảnhbáovượt; sau thu M2/Làm mới không còncảnhbáovượt choLicense1. Không đòicảnhbáotổngvề0 vìhost còndữ liệukhác.

## 7. Bảo trì & hỗ trợ

### TC19 — Phiếu sửa chữa M4, giao người xử lý

1. Bấm **Bảo trì & hỗ trợ** → **Tạo phiếu mới**. TìmTEST26-04 bằngpicker như hướngdẫnlấyID; bấmM4. GhiIDvào sổ, khôngđiềnmãchuỗi vàoôID.
2. Loại phiếu=Sửa chữa, Mức độ=Vừa, Sự cố=`TEST26 kiểm sửa chữa M4` → **Tạo phiếu** một lần.
3. Mởchi tiếtphiếu nếuchưa tựmở; ghisố/IDphiếuP1. M4 phảichuyểnbảo trì, phiếuĐangchờxửlý.
4. Phần **Người phụ trách**: chọnHỗtrợtest cóscopeB hoặcAdminTest; lý dogán=`TEST26 phân công` → **Lưu người xử lý**. Nếu chỉ cóscopeA, không đượcchọnngườikhônghợp lệ; chọnAdminTest hoặcghiCHƯA KIỂM phầnHỗtrợ.
5. Thử tạo thêmphiếu pendingchoM4 khiP1cònmở: phải bịchặn, không có2pending. Khôngchuyểnsangassetvận hành để thử.

### TC20 — Hoàn tất, chi phí và sẵn sàng

1. BằngAdmin hoặcHỗtrợđúng scope/đượcphépxửlý, mởP1 → phần **Xử lý phiếu** chọn **Đã hoàn tất**, Ngày xử lý=ngày kiểm, Tổng chi phí=100000, Kếtquả=`TEST26 đã sửa M4` → **Cập nhật trạng thái**.
2. Đọc lạiphiếuđóng/resolved và100000; tài sản không tựđượccoilàsẵn sàng nếu chưaAdminxác nhận. Admin mở **Xác nhận thiết bị sẵn sàng**, nhập `TEST26 đã kiểm hoạt động` → **Xác nhận sẵn sàng**. Đọc lạiM4đang dùng.
3. Admin mở **Điều chỉnh chi phí**, Chi phíđúng=120000, Lý do=`TEST26 điều chỉnh hóa đơn giả` → **Lưu chi phí & nhật ký**. Đọc lại120000 vàlịch sử, khôngcòn100000.

### TC21 — Failed →mở lại cùng phiếu →resolved

1. TạoP2Sửa chữa trênM5, noteTEST26 thửkhôngkhắc phục. GhiIDphiếu. Xửlý **Chưa khắc phục**, ngày hiện tại, cost50000, note=`TEST26 chưa có linh kiện giả` → Cậpnhật.
2. Đọc lạiM5Hỏng/P2failed. TrongchínhP2 chọn **Đang chờ xử lý** (mở lại), note=`TEST26 mở lại cùng phiếu` → Cậpnhật. Phải giữ IDP2, không tạoIDmới, M5Bảo trì.
3. ĐóngP2Đã hoàn tất với **Tổng chi phí=150000** vànote`TEST26 hoàn tất sau mở lại`. Đây là **tổnghiện tại**, khôngcộng50000+150000. Admin xác nhậnsẵn sàng nhưTC20.

### TC22 — Phiếu định kỳ và thống kê

1. TạoP3 **Bảo trì định kỳ** trênM6, noteTEST26 kiểm định kỳ; đóngresolved cost30000, ngày kiểm, lý doTEST26 hoànthành; Adminxác nhậnsẵn sàng.
2. Bộ lọc **ID thiết bị=M5_ID** → **Tải danh sách**; phầnthốngkê chọnnămhiện tại → **Xem thống kê**. Phải1phiếuP2, chi phí150000, không đếm3eventthành3phiếu.
3. LầnlượtxemM4=1phiếu120000; M6=1periodic30000. KhôngbỏlọcrồiđòitổngDBchỉ3 vìcóphiếu cũ. Manager cóthểgán nhưngkhôngmặcđịnhcóquyềnxửlý; vai tròkiểmởmục9.

## 8. Vòng đời tài sản

### TC23 — Thiết bị cũ và dữ liệu thiếu

1. Bấm **Vòng đời tài sản**, ô **Mã hoặc tên** nhậpTEST26-01 → **Áp dụng**. Mở **Xem lý do**.
2. Đốichiếungày mua2015, tuổi thọloạiC nếu đã có. NếuC=5năm, M1đãquátuổi thọ;raw/tierphải phù hợpcông thứcđang dùng. Điểmhiển thị3sốlẻ, không dùngsốđãlàmtròn đểtựsuyraw/sort.
3. TìmTEST26-06 → Áp dụng → Xemlý do: giá muatrống phải được giải thích thiếu; không tựcoilà0. NếuCthiếutuổi thọ thìđánhdấuthiếutuổi thọ, không đòimọiMđềucóđiểm.
4. Mở **Bộ phận & loại thiết bị** đểlọcA/B/C; đổi **Loại thiết bị/Bộ phận** dướiSosánhnhóm. Mẫusốtheođúngbộ lọc/scope, khôngtoànDB.

### TC24 — Repair khác periodic

TìmM5 → Xemlý do:repair12tháng đếm**1ticketID P2** sau mở lại, không đếmevent. TìmM6:P3periodic không đượctínhthànhrepair. M4 làrepair1. Khôngtuyênbốđiểmđãđối chiếutay nếu chưa có đầy đủraw/giá/tuổi thọ.

## 9. Tài khoản & phân quyền

### TC25 — Cấp quyền cho user TEST sẵn có

1. TrởAdminTest → bấm **Tài khoản & phân quyền**. DùngTrước/Sau tìm đúngusernameTEST đãchuẩnbị; **không sửachínhAdminđang dùng hoặcAdminduynhất**.
2. DòngManagerTest → **Phân quyền** → Vai tròIT=Quản lý hệthống → tickCho phép truy cập IT → chỉ tickBộ phậnA → **Lưu quyền truy cập**.
3. DòngSupportTest → Phân quyền → Hỗtrợkỹthuật → Cho phép truy cập IT → bộ phậnB (hoặcA theo bộtest) → Lưu. GhiuserID/scope, không ghi mật khẩu.
4. Nếu không cóuser test hoặcchỉ1Adminvận hành: **CHƯA KIỂM**, không tạouserbằngSQL/đổiuser thật.

### TC26 — Đối chiếu vai trò bằng phiên riêng

1. Đăng nhậpManagerTest trênprofilekhác → Tài sản tìmTEST26-: chỉthấyM1/M2/M3/IM01/IM02thuộcA, khôngM4/M5/M6ởB; sốthựcthay đổi nếu đãthêmhồ sơphụ.
2. Manager mởGiấy phép: catalogchekey, khôngnútAdminxemkey/sửametadata; khôngcấptheouser. Import/cấp phát/ticketgán chỉscope; không tựthửtáchiệpdữ liệungoàiscope bằngPOST.
3. Manager tựgõ URL `admin.php?op=ita#users` và `#audit`: phải khônghiển thịquảntrị; menuẩn khôngđủchứngminhserver403. GETIDngoàiscope quaDevTools làkiểmriêng, không ghiPASSserverchỉtừmenu.
4. Đăng nhập SupportTest ở phiên riêng: tài sản/ticket chỉ trong bộ phận B; không có quyền tạo mới/sửa toàn trường tài sản, không xem giấy phép/vòng đời/ngân sách/hạ tầng mạng/quản trị. Báo cáo chỉ bảo trì trong ứng dụng, **không export**.
5. Để kiểm Hỗ trợ xử lý, **không dùng P1 đã đóng ở TC20**. Admin tạo một phiếu sửa chữa mới **P4 trên M6**, nội dung `TEST26 kiểm vai trò Hỗ trợ P4`, rồi chọn SupportTest trong Người phụ trách và bấm **Lưu người xử lý**. Ghi ID P4 vào sổ.
6. Trong phiên SupportTest, bấm Bảo trì & hỗ trợ → nhập M6_ID → Tải danh sách → Mở phiếu P4. Chọn Đã hoàn tất, ngày hiện tại, tổng chi phí40000, lý do `TEST26 Support hoàn tất P4` → Cập nhật trạng thái. Admin quay lại P4 → Xác nhận thiết bị sẵn sàng. Nếu không có user test đúng scope thì ghi Chưa kiểm, không nới quyền người thật.
7. Nếu làm P4, thống kê M6 **sau TC26** là2 phiếu P3+P4, tổng70000; repair12tháng M6 tăng từ0 lên1. Kết quả TC22/TC24 trước P4 vẫn là bằng chứng tại thời điểm trước, không ghi đè lịch sử hoặc coi khác số là lỗi.

### TC27 — Thu quyền ITA của user TEST

AdminTest: mở ManagerTest → Phân quyền → bỏ tick Cho phép truy cập IT → Lưu quyền truy cập. Tải lại phiên ManagerTest: phải mất quyền/401/yêu cầu đăng nhập lại theo ứng dụng, không tiếp tục tải file. **Bật lại đúng scope/vai trò cũ sau kiểm**. Không sửa status của dc_users, không thu quyền Admin chính. Đây là thu quyền giữa các request, **không phải thu quyền giữa lúc render**.

## 10. Nhật ký thao tác

### TC28 — Đối chiếu tạo/sửa/xóa/quyền

1. AdminTest bấm **Nhật ký thao tác** → Từngày/Đếnngày=ngày kiểm → **Lọc nhật ký**.
2. Loạithao tác=Tạo tài sản → Lọc → tìm entitytesttươngứng; đổiSửa tài sản/Xóa mềm/Đổi quyền vàlọclạilần lượt. DùngTrước/Sau khi nhiềudòng.
3. Mở **Trước/Sau** củaM2/DEL/user TEST; sođúngthao tác vàIDvừaghi. XemTất cả thao tác đểtìmticket/license/exportkhông nằmtrongdropdownloạihạn chế, khôngđoánbịchưaauditvìkhông cóoption.
4. Không đòimọiGET/ghikỹthuậtđềucó auditbefore/after. Khônggửi rawJSON cóthôngtinthật; chỉ ghi entity/action/mốcthời gian của hồ sơtest.

## 11. Dự báo ngân sách

### TC29 — Đọc và cấu hình loạiTEST, không đoán135,3triệu

1. Adminbấm **Dự báo ngân sách** → Năm=**2099** (nămtestxa, tránhđèkế hoạchthật), Bộ phận=A, Loại=TEST26 - Loại import đãsửa → **Tính dự báo hiện tại**. Nếu cókế hoạchthật2099/A thì **khônglưu**, chọnnămtestkhácđãkiểm tratrống hoặcghiCHƯA KIỂM.
2. Mở **Đơn giá & định mức — Admin IT** → Loại=TEST26-CAT → Giá thay thế=20000000, Thiết bị/người=1.5, Bảo trì dự phòng(%/năm)=0 → **Lưu cấu hình**. Đây làtham sốcủaloại giả, **không đổiđịnh mứcloạiCthật**.
3. BấmTính dự báo hiện tạilại. Dữ liệuthiếutuổi thọ ởloại mới làdựkiến; tổng chỉphầnđãtính, khôngngân sáchđầy đủ. Không đòi135,3triệu: bộhostUIkhông tạođượcticket3nămtrước vàkhông ghiSQL.

### TC30 — Snapshot draft giữ nguyên

1. Mở **Kế hoạch đã lưu & kế hoạch mở rộng** → chọnA/nămtesttrống, Số nhân sự tăng=3, Ghi chú=`TEST26 kế hoạch giả không sử dụng thực tế` → **Lưu kế hoạch & snapshot**. **Snapshot lưu toànloạicủaA, khôngchỉloạiđanglọc**; nếuAkhông đượcphéplưu snapshot tổng thì bỏqua, không bấm.
2. Bấm **Xem snapshot đã lưu**, ghitổng/dòngloạiTEST26. Dòngmua thêm của riêngloạiTEST: ceil(3×1.5)×20000000=100000000, nếu3đầuvàonàyđãđượcxác nhận. Cácphầnkháccóthểthiếu, không coitổngsnapshotchỉ100triệu.
3. Cấu hìnhriêngloạiTEST26 đổigiáthaythế=22000000 →Lưu → Tínhhiện tại: dòngmua thêmhiện tạitheocùngheadcount=110000000 khiheadcountnguồnđúng3. BấmXem snapshot đã lưu: vẫngiữgiácũ20triệu/số tiềncũ.
4. Muốn thaydraft: tick **Tôi xác nhận tính lại và thay snapshot của bản nháp đã lưu** → Lưu kế hoạch & snapshot → đọc lạibản mới. Ghi version/lần sau theoUI. **Không có nútfinalize trongscopehiện tại**, khôngtìmhoặctạoSQLđểchốt.
5. XuấtExcel/PDF đúngnguồnđangxem; mởfilekiểmtiền/tiếngViệt. Saukiểm cóthể đưađơngiáloại giả về20triệu đểdễđối chiếu, ghiđúnglịch sử, không sửasnapshotcũâmthầm.

## 12. Báo cáo

### TC31 — Preview rồi Excel/PDF

1. Bấm **Báo cáo** → Bộ phận=A → Báocáo=Tồn kho → **Áp dụng**. Đọctổngdòngvàgiờ số liệu, kiểmM1/M2/M3/IM01/IM02 cótheophạm vithực. CácassetcũcũngcóthểcùngA, không đòitổngchỉ5.
2. Bấm **Xuất Excel**, mởfile tải: đối chiếutổngbộ lọc, mã/trạng thái/ngày; khôngchỉcácdòngtrangpreview. Bấm **Xuất PDF**, mởfilevàkiểmfont/dấutiếngViệt/cột/trang.
3. Chọnlần lượtcácoptionkhác đangcó (bảo trì/giấy phép/vòng đờingắn), Áp dụngmỗi lần. Bảo trìđối chiếuP1/P2/P3theoscope; giấy phépLicensegiả; vòng đờingắn=M1nếuđủtuổi thọ, cóđiểm/tierhoặcthiếudữ liệuđượcgiải thích.
4. Nếu409nguồn đổi, **Áp dụng/tải lạipreview rồi tựbấmxuấtlại**, khôngspamPOST. Nếu503/warningchenfile, giữfile lỗi/metadatađãche, khônghạerrorreporting/đổiDBđểbỏqua.
5. Exceltồnkho5000, Excelkhác1000, PDF500 làcap, khôngcắtngầm. Bộtestnhỏnàykhôngchứngminhboundary hoặchiệunăng5000; đểCHƯA KIỂM.

## 13. Hạ tầng mạng (mục cuối menu)

### TC32 — Tạo mô hình3thiết bị bằng UI

1. AdminTest bấm **Hạ tầng mạng**; ôTìmmã/tên nhậpTEST26- → **Áp dụng**. Nếu503thiếutable/source thì dừng, không tựchạyDDLtừbộkiểm.
2. Cuộnđến **Quản lý liên kết** → Thiết bịthứnhất=M1, Thiết bịthứhai=M2, Loại liên kết=Ethernet, Cổng1=TEST-P1, Cổng2=TEST-P2, Dunglượngmô tả=1Gbps, Ghi chú=`TEST26 M1-M2 khai báo giả` → **Lưu liên kết**.
3. Bấm **Tạo liên kết khác** → chọnM2 vàM3, cácôcổng TEST-P3/TEST-P4, Ethernet/1Gbps/noteTEST26M2M3 →Lưu. Lặp tạoM3–M4 nếuBđượcphépdùngđể kiểmphạm vi. Khôngtạo linktrênassetvận hành.
4. Đọc lạibảngđúng2linkA hoặc3linkkhicóA–B; 2Dhiển thịnútthiết bị. ChọnnodeM2→phầnchitiếtđúngthiết bị; đây làkhai báo, khôngđo mạng/scan/ping.

### TC33 — 3D, tênkhối và360°

1. Bấm **Xem3D** → chờkhối/nhãn hiện. Tênnhỏphải làtênM1/M2/M3đã tạo; nếuchỉcó1thiết bịthìvẫnchỉ1khối, không tựsinh3máytrênDB.
2. Rêchuộtnhẹ đểnghiêng. **Giữchuộttrái trongkhung →kéo ngang →thả**, rồi giữ→kéo từtrênxuống. Kéo nhiềulượtđểđủ360°, thử nhìn dưới lên; thả giữgóc. Kéokhông đượcchọnnhầmnode; bấmkhôngkéomớichọn.
3. Bấm **Xoay trái/Xoay phải**, kiểmvẫnđượcdùng. Bấm **Toàn màn hình3D**, dùngtoolbarbêntrong: Chọnthiết bịM2, xoaytrái/phải; bấm **Thoát toàn màn hình**, vào lại rồi nhấn **Esc** đểthoát.
4. Chuyển **Sơ đồ2D** rồi3D, đổifilter/bộ phận: khônglặpcanvas/nhãn, khôngmấtđiềukhiển. NếuWebGLbịtừ chối,2D/bảngphảicòndùngđược; ghiCHƯA KIỂM3Dhardware, khônggiảPASS.

### TC34 — Sửa/ngừng liên kết và scope

1. Admin chọn **Sửa** trênlinkM1–M2 → đổiGhi chú=`TEST26 sửa link` →Lưu →đọc lại. Khôngcần đổiasset/cổngđể testđượclưu.
2. LinkM2–M3 → **Ngừng liên kết** → xác nhận hộpthoại. Bảng/sơđồkhôngcònlinkactive đó; khôngxóamáy.
3. ManagerTestscopeA mởmạng: khôngformghi, cólinkM1–M2; linkM3–M4A–B không đượchiện. Hỗtrợkhông đượcxemtopology. NếuthiếutàikhoảnB, ghiCHƯA KIỂMphạm vi, khôngkếtluậnchỉtừAdmin.

## 14. Quay lại đối chiếu và kết thúc

### TC35 — Tổng quan sau dữ liệu test

1. Admin: Tổng quan → Tấtcả/Áp dụng/Làm mới. So vớibaselineTC01: nếu không cóai khácghidữ liệu, tăng**8assetactive** (6tay+2import,DELxóamềm). TheoA tăng5,Btăng3. NếuchạyTC06phụkhác, đối chiếutheosổthực.
2. Trạng tháiM4/M5/M6phảiđang dùng sauAdminready, giấy phép/hết hạn/cảnhbáotheoTC16–18thời điểmđãthu M2. KPIkhông tựdựatrêndòngpreviewphântrang. Khôngtựcộngtổngcácảnhkhácmốcthờigian.

### TC36 — Kết thúc không phá dữ liệu

1. LưuKET_QUA.csv cộtĐạt/Không/Chưakiểm từngTC, ngày/role/sourceZIPnếubiết; ghicácIDtestchứkhôngpassword/cookie/key.
2. Phân quyềnuser test đưa vềtrạng tháitrước kiểmđã ghinhận; khôngthayAdminchính. Thuallocation/lượtbàngiaogiả đangmở khi cần, khôngSQLDELETE/cleartrash hoặcđụnghồ sơthật.
3. HồsơTEST26- cóthểgiữđểđọc lạiaudit. Khônghứadọnsạchbằngxóavĩnh viễn; đóngsnapshot/rate-limit/ciphertext làdữ liệupersistent, khôngrestoreDBđè.
4. Đăng xuất cácphiêntestcuối cùng. Gửi kết quảđãche choagent; ghiđúng **«kiểm thủ công trên host, phạm vi hạn chế»**, không tựghiđãtestDBtoànbộhoặcchophépmerge/tag7–10.

## Những mục không được đánh dấu Đạt từ bộ này

CHECKenforcement quaINSERT/ROLLBACK, auditfailure/thuINSERTgrant, rollbackMyISAM, thu quyềngiữalúcrender, haiPOSTthựcđồngthời,EXPLAIN/index/tải5000/PDF500, unit8.3counters/deprecation, bảođảmDBkhôngpublic/grantseffective/backuprestore thực. Cácmục17–20 bảo mậtkiểmriêngtrên hostingpanel. Bộnhỏnày khôngthay kế hoạchlocalisolated hoặcchứngnhậnsecurity/DB.

Nếu gặp lỗi: **dừng đúng thao tác**, ghimãTC/role/mã TEST/ngày giờ/thông báo/statusNetworkđãche. Khônggửidump/HAR/config/password/key, không bấmLưulạinhiềulầnkhi kết quảchưa rõ. Thấy“MOCK” trênruntimehost hoặcURLtảitệpchuyểnsanglogin, khôngPASSchức năngthật.
