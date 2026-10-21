<?php
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__) . '/');
}
foreach (['database/itadb','security/itahttperror','security/itainput','security/itasession',
    'dao/itauserobjectinfo','dao/itauserobjects','security/italoginlimiter','security/pmauth',
    'services/itaauditservice','services/itaauthservice','services/itaaccessservice',
    'dao/itaassetobjectinfo','dao/itaassetobjects','services/itaassetvalidation','services/itaassetservice',
    'services/itacategoryservice','services/itaexcel','services/itaimportstorage','services/itaimportrows',
    'services/itaimportservice','services/itaimportcontroller','services/itaassignmentrules','dao/itaassignmentobjects',
    'services/itaassignmentservice','services/itahandoverpdf','services/itahandoverservice',
    'services/italicensecrypto','services/italicenserules','dao/italicenseobjectinfo','dao/italicenseobjects',
    'services/italicenseservice','services/italicensereveal','services/itamaintenanceschedule',
    'services/itaticketrules','services/itaticketguard','dao/itaticketobjectinfo','dao/itaticketobjects','services/itaticketservice',
    'services/italifecyclerules','dao/italifecycleobjectinfo','dao/italifecycleobjects','services/itaapplication'] as $itaClassFile) {
    require_once ROOT_PATH . 'classes/' . $itaClassFile . '.class.php';
}
