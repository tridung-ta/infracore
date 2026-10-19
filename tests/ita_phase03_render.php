<?php
/** Render actual Smarty with synthetic values only; never reads a private configuration. */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
define('ROOT_PATH',dirname(__DIR__).'/');
require ROOT_PATH.'classes/template/smarty.class.php';
$compile = sys_get_temp_dir().'/ita-phase03-smarty-'.bin2hex(random_bytes(5));
mkdir($compile,0700);
$template = new Smarty();
$template->setTemplateDir(ROOT_PATH.'templates');
$template->setCompileDir($compile);
$template->assign(['itaMode'=>'live','itaCsrf'=>'qa-not-a-session','itaUserName'=>'QA <script>','itaRoleLabel'=>'Admin IT','itaRenderer'=>'smarty']);
$html = $template->fetch('admin/ita/layout.tpl.html');
foreach (['id="asset-editor"','id="category-form"','ita-assets.js','QA &lt;script&gt;'] as $expected) {
    if (!str_contains($html,$expected)) {
        throw new RuntimeException('Missing rendered content: '.$expected);
    }
}
if (str_contains($html,'{include') || str_contains($html,'{$ita')) {
    throw new RuntimeException('Unexpanded template');
}
echo "Smarty asset/category templates render; escaping checked; no DB.\n";
