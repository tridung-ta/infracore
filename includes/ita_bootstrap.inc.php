<?php
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__) . '/');
}
foreach (['database/itadb','security/itahttperror','security/itainput','security/itasession',
    'dao/itauserobjectinfo','dao/itauserobjects','security/italoginlimiter','security/pmauth',
    'services/itaauditservice','services/itaauthservice','services/itaaccessservice',
    'dao/itaassetobjectinfo','dao/itaassetobjects','services/itaapplication'] as $itaClassFile) {
    require_once ROOT_PATH . 'classes/' . $itaClassFile . '.class.php';
}
