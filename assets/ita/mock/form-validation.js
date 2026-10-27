/** Local-only inert form demo. No fetch, persistence, or API writes. */
document.querySelectorAll('form').forEach(form=>form.addEventListener('submit',event=>{event.preventDefault();const note=form.querySelector('[role=status]');note.textContent='MOCK · Qua kiểm tra FE. Chưa kiểm mã trùng, danh mục, phạm vi hoặc ghi DB.';note.dataset.state='success';}));
document.querySelectorAll('form').forEach(form=>form.addEventListener('input',()=>{form.querySelector('[role=status]').textContent='';}));
