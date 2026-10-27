<?php
/** Actual Smarty render, synthetic error/token only; no application bootstrap or DB. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
define('ROOT_PATH',dirname(__DIR__).'/');
require ROOT_PATH.'classes/template/smarty.class.php';
$compile = sys_get_temp_dir().'/ita-login-smarty-'.bin2hex(random_bytes(5));
mkdir($compile,0700);
$template = new Smarty();
$template->setTemplateDir(ROOT_PATH.'templates');
$template->setCompileDir($compile);
$template->assign(['itaError'=>'TEST <script>alert(1)</script>','itaCsrf'=>'test-only-"<&']);
$html = $template->fetch('admin/ita/login.tpl.html');
$checks = 0;
foreach (['id="login-form"','name="username"','name="password"','name="csrf_token"','method="post"',
    'act=login','TEST &lt;script&gt;alert(1)&lt;/script&gt;','test-only-&quot;&lt;&amp;',
    'ita-login.css','ita-appearance.js','login-topology'] as $expected) {
    if (!str_contains($html,$expected)) { throw new RuntimeException('Missing rendered contract: '.$expected); }
    $checks++;
}
foreach (['TEST <script>','{$ita','{include'] as $unsafe) {
    if (str_contains($html,$unsafe)) { throw new RuntimeException('Unsafe or unexpanded output.'); }
    $checks++;
}
echo "$checks Smarty login render/escape checks passed; synthetic data, no DB.\n";
