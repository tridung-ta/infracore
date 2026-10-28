@echo off
setlocal
cd /d "%~dp0"
where node >nul 2>nul
if errorlevel 1 (
 echo Node.js is required. This launcher never starts PHP or a database.
 pause
 exit /b 1
)
echo LOCAL MOCK ONLY - no PHP, DB or hosting connection.
echo Keep this window open. Press Ctrl+C to stop; all simulated changes are lost.
node assets\ita\preview\serve-review.cjs --open
pause
