# Bàn giao bộ kiểm thủ công —26/10/2026

User yêu cầu dữ liệu giả và hướng dẫn từng cú bấm, từ menu Tổng quan đến Hạ tầng mạng. Đây là **bộ user tự kiểm**, không phải gói cập nhật website hoặc kết quả đã nghiệm thu.

- ZIP: artifacts/ita-manual-uat-20261026-v3/InfraCore-Bo-Kiem-Thu-Thu-Cong.zip, **30.382byte**.
- SHA256: **b64f822cc2d09854163f28857ec80df361a4a90680c4018ed7ad479344c30a1e**.
- Source tài liệu/builder cố định:17ae623a519d06a2b2edb45de9f26b6de982437d. Runtime tham chiếu46ef989. Artifact đãignore, khôngcommit binary/kết quả người dùng điền.

## Nội dung

| Tệp | Dùng để |
|---|---|
|README.txt|Đọc trước, phân biệt file kiểm với gói uploadFTP|
|HUONG_DAN_TUNG_BUOC.md|36case TC01–TC36/13mục menu;6assetfake+2import, assignment/license/tickets/roles/snapshot/network vàquaylạiđốichiếu|
|KET_QUA.csv|36 dòng banđầu **Chưa kiểm**; ngày/role/IDfake/bằngchứngđãche|
|SO_ID_TEST.csv|Ghi mãbộphận/loại/IDM1–M6/P1–P4/license/plan, khôngpassword/cookie/key|
|01_IMPORT_2_DONG.xlsx|2 dòng mẫu, phải tạoTEST26-CAT và thayplaceholdermãbộphậnA trước xemtrước/confirm|
|02_IMPORT_CO_LOI.xlsx|5 dòng:1hợplệ+4lỗichủý (trùngmã/giáâm/statussai/loạikhôngcó); chỉxemtrước/tảilỗi, khôngconfirm|
|03_KHONG_PHAI_XLSX.xlsx|Vănbảntrơ đổiđuôi, khôngPHP/EXE; kiểm từchốiformat|

Thêm MANIFEST.json/SHA256SUMS.txt trongZIP, checksumZIPsidecar ngoàiZIP. Tất cả **không upload vào public_html/FileZilla**. Chỉ chọn XLSX qua formImport củaứngdụng sau khiđãđọc hướngdẫn.

## Kiểm đã chạy local

`php -l scripts/build_manual_uat_kit.php` đạt. `php scripts/build_manual_uat_kit.php --output=artifacts/ita-manual-uat-20261026-v3` chỉ includeItaExcel/ItaInput/ItaHttpError, khôngbootstrap/config/DB. Writer/reader thật tạo và đọclại2/5dòng, từnggiátrịtrùngkhớp; plaintextXLSXbị422: **8checks đạt**, PHPCLI8.5.10 và**71deprecationvendor**, khônggiấumask/nângdependency. Lượtđầu thiếu requireItaInput đãdừng, giữthưmụcpartial riêng; chỉgói v3hoànchỉnh được bàn giao, khôngoverwritebản user điền.

Kiểm độc lập local bằng Pythonstdlib: XLSXsheet1 có3/6rowkèmheader,16cột,0thẻformula; CSVcó36mãTCliêntục vàtấtcảstatusChưakiểm; tài liệubyteskhớpgitshowcommit; ZIP9entryđúngtập7payload+2metadata, đọc lại toànbộCRC/size/SHA256/manifest/checksums đạt. **Chưa validate mãbộphận/loại/ID vớiDBhost**, placeholderkhôngphảidữliệuhợplệđểconfirmngay.

Review độc lập đãđốichiếumẫu/template/logic: sửa việc địnhxửlýP1đãresolved ởbướcSupport bằngP4mớiriêngM6, ghisốliệutrước/sauđúng. Không tự tạousernative/bộphận hoặcngàyopened_atquákhứ; thiếutàikhoảntest/scope thìChưakiểm. KhôngđổiAdminchính hoặcplanđangdùng; khôngSQLghi/thuquyềnDB/tải5000tựđộng. Quyền agent vẫn khônghost/DB/upload/push/merge/tag7–10; kếtquảusertrảlạinhãn **«kiểm thủ công trên host, phạm vi hạn chế»**, khôngtựnghiệmthuDB.
