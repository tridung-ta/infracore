# Mốc source trước cải tiến — 30/10/2026

Theo yêu cầu user, push nhánh hiện tại lên GitHub và tạo tag **backup/pre-improvements-20261030** để truy xuất source trước đợt cải tiến tiếp theo. Không merge main/develop, không tag phase7–10-done và không deploy. Remote được giữ https://github.com/tridung-ta/infracore.git.

Source runtime gói cuối đã bàn giao: **6a9d1ebb0af03cab9b88ab202ff23b511b9eab22**, delta27file từ nền a7c2da1; user báo đã upload thành công. Mốc backup gồm source và các báo cáo sau đó, không tự chứng nhận host/DB/PHPFPM đã nghiệm thu. User chọn giữ quyền DB Full theo chính sách công ty; không gọi là grants tối thiểu và agent không thay grants.

## Lấy lại source

Trên máy có Git và quyền repo, có thể lấy mốc vào **thư mục mới** để so và chuẩn bị rollback, không ghi đè bản đang làm:

```powershell
git clone --branch backup/pre-improvements-20261030 https://github.com/tridung-ta/infracore.git InfraCore-before-improvements
```

Clone ở tag là detached HEAD; nếu muốn sửa trên bản lấy lại:

```powershell
git switch -c restore/before-improvements
```

Không reset --hard, không force push hoặc restore dump DB để quay lại code. Khi rollback host chỉ phục hồi file source đã thay tương ứng, giữ dữ liệu/account/password/quyền/audit mới; kiểm tương thích trước triển khai. SQL/DB user thực hiện theo phạm vi riêng.

## Giới hạn backup

Git lưu source **được tracked**, không chứa privateconfig/key/credential, DBdump, liveuploads, session/log hoặc vendor/font ngoàiGit. Repo còn source nền khôngtracked; clone không phải bộ cài/backuphost đầy đủ. Giữ backup đầy đủ source/config riêng của host, dependency đãpin/ZIP đãbàn giao và DB/key ở kho riêng ngoài máydev. Không hứa cloneGit đủ chạy website trống.

Workingchange riêng docs/PHASE11_SECURITY_TEST_20.md chứa indentation và nội dung checkpoint cũ; không ghi đè hoặc đưa vào commit mốc để tránh lùi hồ sơ bảo mật. File đó vẫn nằm nguyên trên workspace. Không thay đổi code trong lượt tạo mốc.
