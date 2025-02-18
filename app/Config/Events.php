<?php

namespace Config;

use CodeIgniter\Events\Events;
use CodeIgniter\Exceptions\FrameworkException;

/*
 * --------------------------------------------------------------------
 * Application Events
 * --------------------------------------------------------------------
 * Events allow you to tap into the execution of the program without
 * modifying or extending core files. This file provides a central
 * location to define your events, though they can always be added
 * at run-time, also, if needed.
 *
 * You create code that can execute by subscribing to events with
 * the 'on()' method. This accepts any form of callable, including
 * Closures, that will be executed when the event is triggered.
 *
 * Example:
 *      Events::on('create', [$myInstance, 'myMethod']);
 */

Events::on('signUp', static function ($user_id) {
    $SettingsModel = new \App\Models\SettingsModel();
    $SettingsModel->createUserList($user_id);

    $ResourceModel = new \App\Models\ResourceModel();
    $resources = parse_ini_file(ROOTPATH.'/defaults.ini')['resources'];
    $ResourceModel->saveUserList($user_id, $resources);

    $UserGroupModel = new \App\Models\UserGroupModel();
    $UserGroupModel->createUserItem($user_id, 'registered');

    $CharacterModel = new \App\Models\CharacterModel();
    $character_id = $SettingsModel->where('code', 'characterId')->get()->getRowArray()['default_value'];
    $CharacterModel->linkItemToUser($character_id, $user_id);

    $QuestModel = new \App\Models\QuestModel();
    $initials = parse_ini_file(ROOTPATH.'/defaults.ini')['initials'];
    $QuestModel->linkItemToUser($initials['quest_id'], $user_id);
});

Events::on('resourceEnrolled', static function ($target_id, $progress) {
    $QuestModel = new \App\Models\QuestModel();
    $QuestModel->addActiveProgress('resource', $target_id, $progress);
});
Events::on('lessonFinished', static function ($target_id) {
    $QuestModel = new \App\Models\QuestModel();
    $QuestModel->addActiveProgress('lesson', $target_id, 1);
});
Events::on('skillGained', static function ($target_id) {
    $QuestModel = new \App\Models\QuestModel();
    $QuestModel->addActiveProgress('skill', $target_id, 1);
});

Events::on('pre_system', static function () {
    if (ENVIRONMENT !== 'testing') {
        if (ini_get('zlib.output_compression')) {
            throw FrameworkException::forEnabledZlibOutputCompression();
        }
        while (ob_get_level() > 0) {
            ob_end_flush();
        }
        ob_start(static fn ($buffer) => $buffer);
    }

    /*
     * --------------------------------------------------------------------
     * Debug Toolbar Listeners.
     * --------------------------------------------------------------------
     * If you delete, they will no longer be collected.
     */
    if (CI_DEBUG && ! is_cli()) {
        Events::on('DBQuery', 'CodeIgniter\Debug\Toolbar\Collectors\Database::collect');
        Services::toolbar()->respond();
    }
});
