# Mẫu SHA256SUMS trên Windows — chưa chạy

Mẫu cho user chạy **local khi sẵn sàng**, PowerShell5.1/7 và Get-FileHash/.NET có sẵn trên Windows; không cần cài package, không chạy trên host. Agent chưa chạy mẫu, chưa tạo checksum/backup/gói. Chỉ đọc bộ dependency cụ thể, không thực thi PHP/Composer hoặc copy secret.

Chọn nguồn đã chốt và ổ/kho lưu **ngoài máy dev** được phép. Ví dụ X:\InfraCoreDependencies là placeholder, không khẳng định ổ này có thật; thay bằng đích đã kết nối/xác minh, không URL chứa credential. Tạo sẵn thư mục version mới tại đó bằng công cụ được phép, không ghi đè checksum baseline cũ. NAS/ổ gắn ngoài phải thực sự là nơi lưu ngoài máy dev theo quy trình backup, không chỉ là thư mục khác cùng ổ máy dev.

## Mẫu lệnh (không tự chạy khi đọc tài liệu)

```powershell
# User chỉnh hai đường dẫn trước khi chạy; destination phải tồn tại.
$dependencySource = (Get-Item -LiteralPath 'D:\InfraCore' -Force).FullName.TrimEnd('\')
$checksumDestination = (Get-Item -LiteralPath 'X:\InfraCoreDependencies\baseline-YYYYMMDD' -Force).FullName

function Write-DependencySums {
    param([string]$SetName, [string[]]$Inputs)
    $target = Join-Path $checksumDestination ($SetName + '-SHA256SUMS.txt')
    if (Test-Path -LiteralPath $target) { throw 'Checksum file already exists; use a new version directory.' }
    if ($checksumDestination.StartsWith($dependencySource + '\', [StringComparison]::OrdinalIgnoreCase) -or
        $checksumDestination.Equals($dependencySource, [StringComparison]::OrdinalIgnoreCase)) {
        throw 'Checksum destination must be outside the source tree.'
    }
    $stack = New-Object 'System.Collections.Generic.Stack[string]'
    $files = New-Object 'System.Collections.Generic.Dictionary[string,string]' ([StringComparer]::OrdinalIgnoreCase)
    foreach ($inputPath in $Inputs) {
        $candidate = [IO.Path]::GetFullPath((Join-Path $dependencySource $inputPath))
        if (-not $candidate.StartsWith($dependencySource + '\', [StringComparison]::OrdinalIgnoreCase)) { throw 'Outside source.' }
        # Check parents as well as leaf; never walk through junctions/symlinks.
        $ancestor = Get-Item -LiteralPath $candidate -Force -ErrorAction Stop
        while ($null -ne $ancestor) {
            if ($ancestor.Attributes -band [IO.FileAttributes]::ReparsePoint) { throw 'Linked path refused.' }
            if ($ancestor.FullName.TrimEnd('\').Equals($dependencySource, [StringComparison]::OrdinalIgnoreCase)) { break }
            $ancestor = if ($ancestor.PSIsContainer) { $ancestor.Parent } else { $ancestor.Directory }
        }
        $stack.Push($candidate)
    }
    while ($stack.Count -gt 0) {
        $item = Get-Item -LiteralPath $stack.Pop() -Force -ErrorAction Stop
        if ($item.Attributes -band [IO.FileAttributes]::ReparsePoint) { throw 'Linked path refused.' }
        if ($item.PSIsContainer) {
            foreach ($child in (Get-ChildItem -LiteralPath $item.FullName -Force -ErrorAction Stop)) { $stack.Push($child.FullName) }
        } else {
            $relative = $item.FullName.Substring($dependencySource.Length + 1).Replace('\', '/')
            if ($relative.Contains("`n") -or $relative.Contains("`r")) { throw 'Ambiguous manifest filename.' }
            if ($files.ContainsKey($relative)) { throw 'Duplicate input path.' }
            $files.Add($relative, $item.FullName)
        }
    }
    [string[]]$names = @($files.Keys)
    [Array]::Sort($names, [StringComparer]::Ordinal)
    if ($names.Length -eq 0) { throw 'No dependency files found.' }
    $lines = foreach ($relative in $names) {
        $sum = (Get-FileHash -LiteralPath $files[$relative] -Algorithm SHA256 -ErrorAction Stop).Hash.ToLowerInvariant()
        $sum + '  ' + $relative
    }
    # UTF-8 without BOM; explicit LF. Manifest is outside inputs, no self-hash.
    [IO.File]::WriteAllText($target, (($lines -join "`n") + "`n"), (New-Object Text.UTF8Encoding($false)))
    [pscustomobject]@{ Set = $SetName; Files = $names.Length; ChecksumFile = $target }
}

Write-DependencySums -SetName 'excel' -Inputs @('classes\PhpSpreadSheet')
Write-DependencySums -SetName 'inter' -Inputs @('assets\fonts\inter\InterVariable.woff2', 'assets\fonts\inter\OFL.txt')
Write-DependencySums -SetName 'pdf' -Inputs @('classes\ita-pdf\vendor', 'classes\ita-pdf\composer.json', 'classes\ita-pdf\composer.lock')
```

Mỗi bộ có tệp *-SHA256SUMS.txt riêng; đây chính là checksum manifest của bộ, không phải ZIP hoặc backup payload. Mẫu không sao chép dependency ra đích, nên **tạo checksum xong chưa có nghĩa đã sao lưu**. Cần thực hiện copy/version/read-back/restore riêng theo DEPLOY_LOG mụcC. Giữ cùng cấu trúc đường dẫn tương đối khi sao lưu và phục hồi; DejaVu nằm trong vendor, không thêm lần nữa gây trùng.

Excel dự kiến566 file theo baseline; Inter2 file; PDF đếm theo vendor/lock thực tế. Khác số phải đối chiếu nguồn/thư mục/license, không tự bỏ file để khớp. Các path trong mẫu tính từ root dự án; tree hash Excel lịch sử dùng path từ root classes/PhpSpreadSheet nên **hash của hai manifest không so trực tiếp**. SHA256 từng file vẫn so được khi ánh xạ cùng path, không sửa bytes/newline của dependency để làm hash khớp.

Trước khi chạy thật, rà nội dung/phạm vi bộ dependency không chứa tệp private ngoài dự kiến. SHA256 bảo vệ đối chiếu byte, không chứng minh nguồn tin cậy hoặc không có mã độc. Chưa chạy mẫu trên PowerShell5.1/7 trong lần soạn này; cần ghi runtime và kết quả thật khi user chạy, không ghi PASS trước.

## Kiểm phục hồi và lưu ngoài máy dev

Lấy bản copy từ kho ngoài máy dev vào staging sạch, không đè source/host. Đối chiếu mỗi đường dẫn trong checksum bằng Get-FileHash local; kiểm thiếu/thừa file và kích thước theo inventory, không chỉ kiểm tổng số. Có thể chạy lại mẫu với root staging và **đích checksum mới**, rồi so các dòng nếu cấu trúc path giống nhau. Khớp checksum mới là bước byte; render/mở lại XLSX/PDF/font vẫn phải kiểm riêng. Không chạy PHP dependency tải về chỉ để tính checksum.

| Bộ | Ngày/người thực hiện | Kho ngoài máy dev (mã tham chiếu, không credential) | Version/source/baseline | Số file | Tệp checksum +hash metadata nếu có | Đọc lại bản copy | Restore/runtime/test |
|---|---|---|---|---|---|---|---|
| Excel566 baseline | Chưa có | Chưa chọn | Chưa chốt lần backup mới | Chưa đếm lại | Chưa tạo | Chưa chạy | Chưa chạy |
| Inter+OFL | Chưa có | Chưa chọn | Upstream ref chưa xác minh | Chưa đếm lại | Chưa tạo | Chưa chạy | Chưa chạy |
| mPDF vendor/DejaVu/json/lock | Chưa có | Chưa chọn | Lock source đã có, backup mới chưa làm | Chưa đếm lại | Chưa tạo | Chưa chạy | Chưa chạy |

Tài liệu không mở quyền upload/backup hoặc tạo gói tự động. Không merge/tag Phase7–10/push/deploy; inventory host vẫn CHƯA THU THẬP.
