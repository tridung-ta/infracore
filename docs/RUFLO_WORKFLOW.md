# Ruflo trong công việc InfraCore

26/10/2026 — User yêu cầu tạm dừng và tổng kết học lỗi. Đã lưu bản làm sạch tại RETROSPECTIVE_20261026.md, liên kết vào LESSONS/PROGRESS/CURRENT_BUILD_STATUS; đây là nguồn đọc lại khi user cho tiếp tục. Không chạy CLI/hook/daemon/swarm/provider, không xuất session/credential hoặc Save-Handoff sang thư mục ngoài workspace. Không diễn giải “học lại” thành tự học nền, quyền resume BUILD hoặc thay trạng thái nghiệm thuDB/host.

User yêu cầu dùng skill Ruflo xuyên suốt quá trình làm việc ngày 21/10/2026. Đã đọc bản gốc C:/Users/PC/.agents/skills/ruflo/SKILL.md và references/installation.md qua điểm tra cứu thư viện local.

- CLI pinned được kiểm bằng scripts/ruflo.ps1 --help: trả v3.59.3. Kết quả này chỉ xác nhận CLI, không xác nhận live LLM, vector memory, MCP hoặc tự học nền.
- Tiến độ, quyết định, bài học đã kiểm chứng trong PROGRESS.md, DECISIONS.md, LESSONS.md là nguồn tiếp tục công việc. Đọc lại trạng thái Git và bằng chứng hiện tại trước khi áp dụng bài học cũ.
- Ghi bài học theo triệu chứng → bằng chứng → sửa → kiểm tra → giới hạn; không chép raw log/session, credential, dump hoặc dữ liệu thật.
- Khi user chọn file tóm tắt để chuyển tài khoản, có thể dùng Save-Handoff.ps1 của skill sau khi kiểm nội dung đã làm sạch. Chưa xuất handoff sang thư mục state của skill trong phiên này.
- Không tự init project, thay AGENTS, cài hooks, bật daemon/swarm, cấu hình provider hoặc chạy doctor --fix. Các BE-GATE và giới hạn DB/deploy vẫn có hiệu lực. Không coi memory là quyền thực hiện thao tác mới.
