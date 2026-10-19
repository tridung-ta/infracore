# AGENTS.md — Dự án 5: Enterprise IT Asset & Infrastructure Management System

Hướng dẫn cho Agent (Codex). Bản này hợp nhất bộ Agent của dự án 1 với plan dự án 5. Nếu repo đã có AGENTS.md khác, **hợp nhất, không ghi đè**; giữ các quy tắc của dự án đích còn đúng.

Đọc trước khi sửa bất cứ thứ gì: `docs/PROJECT_BRIEF.md`, `docs/PLAN_FINAL.md`, `docs/DECISIONS.md`, `docs/LESSONS.md`, `docs/PROGRESS.md`, và `docs/AUDIT.md` (sau Phase 0).

## 1. Hiểu dự án trước khi thay đổi

- Khảo sát code, entrypoint, kiến trúc, scripts, kiểm thử hiện có (Phase 0). Không giả định stack, database, theme hay cách triển khai từ dự án cũ.
- Không mang sang dự án này: MyISAM, theme Midnight Gold, tên bảng/cột, ID user, URL host, công thức OT của dự án 1.
- **Thực tế trong code thắng tài liệu.** Nếu khác nhau, theo code và ghi chênh lệch vào `docs/AUDIT.md`.
- Làm đến khi yêu cầu được xử lý và kiểm chứng. Việc triển khai thông thường trong phạm vi plan thì tự quyết; thiếu quyết định ảnh hưởng đáng kể đến sản phẩm hoặc dữ liệu thì hỏi (xem Mục 2).
- Giữ thay đổi không liên quan và chức năng đang dùng. Không rewrite lớn. Thêm package PHP (composer) là BE-GATE. Thư viện UI đã duyệt sẵn: Bootstrap 5, ECharts, GSAP, và Three.js (chỉ Phase 13), đặt cục bộ tại `assets/ita/vendor/<tên>/<phiên-bản>/`, ghim phiên bản, không cần Node build ở production.

## 2. Tự chủ và BE-GATE

- **Không chờ duyệt plan hay quy trình.** Tự phân tích, làm, verify, review, commit, rồi sang phase kế tiếp ngay.
- **Chỉ dừng ở BE-GATE.** Gom một yêu cầu cho mỗi phase theo mẫu bên dưới. Trong lúc chờ, tiếp tục phần FE không phụ thuộc (dùng mock, đánh dấu `MOCK`).
- Không coi im lặng là phê duyệt thao tác không thể đảo ngược.
- Điểm mơ hồ không thuộc BE: chọn phương án an toàn nhất, ghi vào `docs/DECISIONS.md` (mục quyết định tự chọn), làm tiếp.
- Một chức năng thất bại sau 3 hướng khác nhau: đánh dấu `[!]`, ghi nguyên nhân, chuyển sang chức năng độc lập kế tiếp.

| | FE: làm ngay | BE: phải qua BE-GATE |
|---|---|---|
| File | Template `.tpl`, `assets/ita/**`, partial, phần Smarty chỉ để trình bày | Mọi `.php` trong `classes/`, `modules/`, auth, session, middleware, routing/dispatcher, config, cron |
| Dữ liệu | Mock JSON trong `assets/ita/mock/` | Migration, bảng, cột, index, seed, mọi câu SQL ghi |
| Khác | Hiệu ứng, bố cục, responsive | Endpoint JSON/ajax mới, package composer mới, `.htaccess`, cấu hình server |

Chỉ đọc (SELECT, đọc code, `EXPLAIN` trên DB test) không cần gate.

```
BE-GATE #<phase>
Phạm vi: <các chức năng Fx.y liên quan>
Thay đổi dự kiến: <file PHP / bảng / cột / index / endpoint>
Migration: <đường dẫn file, tóm tắt, có phần hoàn tác>
Rủi ro và backup: <...>
Giả định cần xác nhận: <danh sách>
Trả lời mong muốn: OK / OK với chỉnh sửa / Không
```

Sau khi user OK, làm đủ phạm vi đã nêu. Thay đổi BE ngoài phạm vi đã duyệt là một gate mới.

## 3. Chọn công cụ và skill

- Dùng skill phù hợp nếu đã được cài, đọc hướng dẫn của skill trước khi áp dụng. Không bật mọi workflow cùng lúc.
- Mọi phase: Git Feature Commit (mỗi commit), Feature Development (triển khai và tự kiểm chứng), Code Review (tự rà trước commit), Review Agent (rà diff cuối phase), Error Handling.
- UI: Impeccable, kiểm tra trên trình duyệt thật bằng Playwright CLI (đăng nhập theo vai trò, chụp 1366/768/390 px, lỗi console, thử URL trái quyền).
- Database: Database Safety (backup, dry-run, hoàn tác), Mysql Patterns (schema, index, phân trang, aggregate), API Design (JSON `{success, data, message}`, key snake_case), System Design (ERD, AUDIT).
- File: Spreadsheets (tạo file mẫu/lỗi để test import, mở file export kiểm tra), PDF (render và nhìn lại PDF, tiếng Việt), Documents (chỉ khi cần tài liệu Word). Code xuất Excel/PDF trong PHP vẫn viết bằng PhpSpreadsheet và mPDF/TCPDF.
- Visualize: dựng thử biểu đồ/sơ đồ trước khi code thật. Image Gen: chỉ khi cần texture, ưu tiên CSS/SVG.
- ECC dùng có chọn lọc để khảo sát code; ghi bài học theo dự án, **không tự bật hooks hoặc dịch vụ nền**.
- Financial Budget, Three-Statement Forecast, Analytics Dashboard chỉ để tham khảo bố cục bảng, không thay logic PHP.
- Không dùng: Shadcn (React), Pets/Create pet/Update pet, Sites, Computer Use (mặc định dùng Playwright CLI), Excel Live Control, Plugin Management, Skill Installer, Template Creator, và các skill dựng slide/tài liệu kinh doanh. Docker Patterns chỉ khi cần dựng môi trường local.
- Bộ file này không cài đặt hay mang theo skill, runtime, tài khoản, MCP hay thông tin đăng nhập.

## 4. Quy ước code Derasoft (tuân thủ khi nền Derasoft cho phép)

- Biến cục bộ và function: camelCase; class: PascalCase; hằng số: UPPER_SNAKE_CASE; tên file: chữ thường; key API/JSON: snake_case.
- Tên file module `[action][module].module.php`, template theo mẫu `[op][act][mod]` đúng như file đang có (đối chiếu ở audit, không tự bịa quy ước mới).
- Mỗi dòng một lệnh; hàm tối đa 50–60 dòng, tối đa 3–4 tham số; áp dụng SOLID; try-catch, không để trang crash; comment `/** */` cho hàm public phức tạp.
- Smarty: truyền biến bằng `assign`; không viết logic nghiệp vụ trong template.
- Module mới đặt tiền tố `ita`; DAO theo cặp `ItaXxxObjects` / `ItaXxxObjectInfo`; bảng mới tiền tố `ita_`.

## 5. Code và dữ liệu

- Kiểm tra validation, auth, permission và ownership đúng tầng. **Phân quyền kiểm tra ở server**, không chỉ ẩn menu. Truy cập thiết bị ngoài phạm vi bộ phận phụ trách trả 403.
- Dùng parameterized query/prepared statement; escape đầu ra theo ngữ cảnh; CSRF token cho form và ajax thay đổi dữ liệu; rate limit đăng nhập; `password_hash` (nâng cấp dần nếu nền đang dùng hash cũ).
- Key giấy phép mã hóa khi lưu, che khi hiển thị, chỉ Admin IT xem đầy đủ và mỗi lần xem đều ghi log.
- Trước thao tác dữ liệu: xác nhận môi trường đích, backup và phạm vi ghi. Không giả định rollback bảo vệ được mọi engine hoặc mọi side effect.
- **Kiểm tra storage engine của bảng cũ ở Phase 0.** Bảng `ita_` dùng InnoDB, utf8mb4. Không đổi engine hay schema bảng cũ làm lối tắt. Bảng cũ là MyISAM thì không có transaction/FK với bảng đó; kiểm tra ở tầng ứng dụng và ghi vào `docs/AUDIT.md`.
- Không dùng production để phát triển. Không xóa dữ liệu, `DROP TABLE`, đổi tên bảng/cột cũ, reset repo hay viết lại lịch sử làm lối tắt. Delete thường là soft delete; Clear Trash chỉ 1–2 tài khoản được phép.
- Không lưu secret, credential, session, dump, dữ liệu cá nhân thật hoặc raw log nhạy cảm vào Git, bài học hay tài liệu.
- Đọc/ghi file UTF-8 rõ ràng, không BOM; sau khi thay thế hàng loạt phải xác minh nội dung thật. Không sửa locale toàn cục chỉ để chữa một lệnh.
- **Không xóa bất cứ thứ gì của chức năng cũ, chỉ ẩn** (Mục 7). Bảng và dữ liệu cũ không đổi.
- Khi dọn thư mục: kiểm đường dẫn tuyệt đối nằm trong workspace, trạng thái Git, symlink/junction; không đi theo link ra ngoài để xóa. Chỉ xóa theo danh sách trắng cụ thể (Mục 7.1).

## 5.1 Giao diện

- Hướng thiết kế Enterprise IT Command Center, 70% Animated Enterprise SaaS và 30% Cyber Command Center. Màu khai báo bằng CSS variables trong `assets/ita/css/ita-tokens.css`; cấm hard-code màu ngoài file token. Hiệu ứng sáng chỉ ở khung và điểm nhấn, không trong bảng và form.
- Motion tách riêng (`ita-motion.css`, `js/motion/*.js`), bật/tắt bằng `UI_MOTION_ENABLED`, tôn trọng `prefers-reduced-motion`, chỉ animate `transform` và `opacity`. Overlay không nhận pointer; hiệu ứng không được cản thao tác, không lặp vô ích, hủy nhanh được.
- Khi đổi theme, kiểm computed style, specificity và semantic tokens ở form, menu, dialog, select (giữ mũi tên), hover/disabled/error; không chỉ kiểm body.
- Responsive kiểm ở 1366, 768, 390 px bằng cách đo bounding box và rule thắng, không chỉ nhìn ảnh.

## 6. Kiểm chứng và học lỗi

- Đọc `docs/LESSONS.md`, **tái hiện lỗi trên phiên bản hiện tại rồi mới kết luận nguyên nhân**. Bài học cũ không phải chẩn đoán mặc định.
- Chạy kiểm tra phù hợp với thay đổi: `php -l` mọi file PHP đã sửa; Playwright CLI cho UI; `EXPLAIN` cho truy vấn danh sách/thống kê; thử dữ liệu sai; kiểm XSS/CSRF cơ bản. Với UI, kiểm cả luồng thao tác, payload và trạng thái lỗi, không chỉ ảnh, chữ, màu.
- Test lỗi thời thì sửa theo contract đã xác minh. Không nới assertion bảo mật hay đổi sản phẩm chỉ để test PASS.
- Phân biệt unit, mock/fixture, HTTP/DB integration, browser E2E và UAT người dùng. **Ghi lệnh, kết quả thật và giới hạn; không ghi PASS cho phần chưa chạy.** Test chạy đúng server (kiểm port, process owner); chạy qua entrypoint thật.
- Lỗi gián đoạn: giữ output/timing, điều tra có giới hạn; không gọi retry PASS là root cause.
- Có trường hợp mới thì ghi vào `docs/LESSONS.md` theo mẫu: triệu chứng, phân loại, nguyên nhân có bằng chứng (hoặc đang mở), cách sửa, kiểm tra, phạm vi áp dụng. Không copy cả transcript.

## 7. Dọn dẹp và ẩn giao diện cũ

### 7.1 Dọn dẹp an toàn trước khi bắt đầu (F0.1)
«Dọn sạch» nghĩa là working tree sạch và bỏ file rác sinh tự động. **Không xóa mã nguồn chức năng cũ.**
1. `git status`, `git branch`, `git log -5`.
2. Có thay đổi chưa commit: commit lên nhánh `backup/pre-ita-YYYYMMDD`, tạo tag cùng tên, quay lại nhánh làm việc.
3. Nhắc user sao lưu toàn bộ thư mục và DB ra ngoài repo; kiểm có bản dump trước khi động tới DB.
4. `git clean -n -d` (chỉ xem thử). Chỉ xóa theo danh sách trắng bằng đường dẫn cụ thể: cache/compiled Smarty (`templates_c`), thư mục tạm, `*.log`, `.DS_Store`, `Thumbs.db`, `*.bak`, `*.orig`, `*.swp`, `node_modules` nếu không dùng.
5. Không xóa: mã nguồn modules/classes/templates/css/js/assets, uploads, config, `.env`, dump DB, PHP vendor đang dùng, và mọi thứ không chắc chắn.
6. Ghi từng mục đã xóa vào `docs/CLEANUP_LOG.md`; cập nhật `.gitignore`; `php -l` toàn repo; commit F0.1.

### 7.2 Ẩn giao diện chức năng cũ
- **Mức 1 [FE] (F1.9):** layout, sidebar, dashboard mới là file riêng; sidebar mới không có mục chức năng cũ; menu/partial cũ để nguyên, không include nữa.
- **Mức 2 [BE] (F1.10):** guard ở nơi dispatch module. Khi `ITA_LEGACY_UI_ENABLED = false` (mặc định), URL chức năng cũ chuyển về dashboard mới kèm thông báo; khi `true`, mọi thứ cũ chạy như trước. Module dùng chung mà giao diện mới còn gọi (đăng nhập, upload, ajax) được liệt kê riêng và không bị chặn.
- `docs/LEGACY_INVENTORY.md`: Chức năng cũ | Route/URL | File module | File template | Cách ẩn | Cách khôi phục.
- Nghiệm thu: tắt cờ thì không còn thấy giao diện cũ; bật cờ thì cũ chạy y nguyên; `git diff --name-status` không có `D` trên file cũ.

## 8. Git và bàn giao

- Kiểm tra status/diff, remote và **danh tính tác giả** trước commit/push. Không mang Git config của dự án khác sang mặc định.
- **Một commit = một chức năng** (mã Fx.y trong `docs/PLAN_FINAL.md`). Commit gồm đủ migration (nếu có), PHP, template, asset và dòng `PROGRESS`, đã verify. Không gộp hai chức năng, không tách nửa chức năng, không commit `wip`.
- **Chỉ stage file liên quan, không `git add .`**; không commit rỗng; không sửa ngày để tăng contribution. Lỗi phát hiện sau commit: commit mới `fix(scope)`; chỉ `git commit --amend` khi chưa push.
- Định dạng: `<type>(<scope>): <mô tả ngắn tiếng Việt, tối đa 72 ký tự>`, thân gồm Chức năng, Thay đổi, Verify (loại test + lệnh + kết quả thật), BE-GATE (số hiệu hoặc không cần). type: feat, fix, docs, chore, refactor, perf, test, style. scope: auth, rbac, audit, asset, category, search, import, export, assign, license, ticket, lifecycle, forecast, dashboard, report, alert, ui, motion, network, db, perf, sec, legacy, repo.
- Nhánh: mỗi phase `feature/ita-phaseNN-<tên>`; sửa gấp `fix/ita-<mô tả>`. Tag `backup/pre-ita-YYYYMMDD` và `ita-phaseNN-done`. Dùng nhánh mặc định và nhánh tích hợp hiện có của repo (audit xác nhận), dưới đây gọi là `main` và `develop`.
- Cấm: `git reset --hard`, `git clean -fd`, `git push --force`, `git branch -D`, viết lại lịch sử.
- **Quyền hạn (theo `docs/DECISIONS.md`):** được commit local theo chức năng và merge local `--no-ff` vào `develop` sau mỗi phase đã verify. **Không `git push`, không merge vào `main`, không đổi remote, không deploy** cho đến khi user xác nhận. Việc cho phép ở dự án trước không chuyển sang đây. Deploy production do user làm bằng FileZilla; Agent chỉ chuẩn bị gói và checklist trong `docs/DEPLOY_LOG.md`.
- Gói bàn giao: dùng source cố định, gom dependency, có manifest (inventory, hash), có phương án rollback. Không mang config, dump, session, log, dữ liệu test, seed vào runtime.

## 9. Báo cáo tiến độ (bắt buộc)

Mỗi tin nhắn của Agent mở đầu bằng banner:

```
[ITA] Phase 03/13 — Quản lý tài sản        | Tổng: 27%
Phase: ####----  4/8 chức năng             | Đang làm: F3.5 xóa mềm (FE)
BE-GATE: không | Chờ user: không | Commit gần nhất: a1b2c3 feat(asset): ...
Việc tiếp theo: F3.6 chi tiết thiết bị
```

`docs/PROGRESS.md` là bảng theo dõi: Mã | Chức năng | Vùng (FE/BE) | Trạng thái | Commit | Verify | Ghi chú. Trạng thái: `[ ]` chưa làm, `[~]` đang làm, `[x]` xong, `[!]` kẹt, `[G]` chờ BE-GATE. Cột Verify ghi loại test, lệnh và kết quả thật; phần chưa chạy ghi «chưa chạy».

Khi bị chặn, banner nêu rõ chặn ở đâu và user cần làm gì trong một dòng.

## 10. Bàn giao

Báo cáo cuối: mức hoàn thành của 10 yêu cầu bắt buộc; bảng đối chiếu yêu cầu → chức năng → commit; việc user phải tự làm (chạy migration production, deploy FileZilla); giới hạn và rủi ro còn lại. Tài liệu phản ánh chức năng thật, không hứa hoàn hảo hoặc không tái diễn bug.
