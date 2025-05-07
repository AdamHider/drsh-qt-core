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

Events::on('signUp', static function ($user_id, $data) {
    $SettingsModel = new \App\Models\SettingsModel();
    $SettingsModel->createUserList($user_id);

    $initials = parse_ini_file(ROOTPATH.'/defaults.ini')['initials'];
    $resources = parse_ini_file(ROOTPATH.'/defaults.ini')['resources'];

    $ResourceModel = new \App\Models\ResourceModel();
    $ResourceModel->saveUserList($user_id, $resources);

    $UserGroupModel = new \App\Models\UserGroupModel();
    $UserGroupModel->createUserItem($user_id, 'registered');

    $CharacterModel = new \App\Models\CharacterModel();
    if($data['gender'] == 'male'){
        $CharacterModel->linkItem($initials['male_character_id'], $user_id);
    } else {
        $CharacterModel->linkItem($initials['female_character_id'], $user_id);
    }

    $QuestModel = new \App\Models\QuestModel();
    $QuestModel->linkItem($initials['quest_id'], $user_id);
    if(!empty($data['invited_by'])){
        $QuestModel->linkItem($initials['invitation_quest_id'], $user_id);
    }
    $LessonUnblockUsermapModel = new \App\Models\LessonUnblockUsermapModel();
    $LessonUnblockUsermapModel->linkItem(['item_id' => $initials['lesson_id'], 'user_id' => $user_id]);

});

Events::on('resourceEnrolled', static function ($target_id, $code, $progress) {
    $QuestModel = new \App\Models\QuestModel();
    $QuestModel->addActiveProgress('resource', $target_id, $progress);

    $QuestModel->addActiveProgress('resource_invitation', $target_id, $progress);

    $UserLevelModel = new \App\Models\UserLevelModel();
    if($code == 'experience'){
        $UserLevelModel->checkIfCurrentItemChanged($progress);
    }
    $quests = $QuestModel->getCompletedList('resource');
    if(!empty($quests)){
        $NotificationModel = new \App\Models\NotificationModel();
        foreach($quests as $quest){
            $NotificationModel->notifyQuest($quest);
        }
    }
});
Events::on('lessonFinished', static function ($target_id) {
    $QuestModel = new \App\Models\QuestModel();
    $QuestModel->addActiveProgress('lesson', $target_id, 1);
    $QuestModel->addActiveProgress('total_lessons', $target_id, 1);

    $AchievementModel = new \App\Models\AchievementModel();
    $NotificationModel = new \App\Models\NotificationModel();

    $achievementsTotalLessons = $AchievementModel->getListToLink('total_lessons');
    if(!empty($achievementsTotalLessons)){
        foreach($achievementsTotalLessons as $achievement){
            $AchievementModel->linkItem($achievement);
            $NotificationModel->notifyAchievement($achievement);
        }
    }
    $achievementsTotalPoints = $AchievementModel->getListToLink('total_points');
    if(!empty($achievementsTotalPoints)){
        foreach($achievementsTotalPoints as $achievement){
            $AchievementModel->linkItem($achievement);
            $NotificationModel->notifyAchievement($achievement);
        }
    }
    $quests = $QuestModel->getCompletedList('lesson,total_lessons');
    if(!empty($quests)){
        foreach($quests as $quest){
            $NotificationModel->notifyQuest($quest);
        }
    }
});
Events::on('skillGained', static function ($target_id) {
    $QuestModel = new \App\Models\QuestModel();
    $QuestModel->addActiveProgress('skill', $target_id, 1);
    $QuestModel->addActiveProgress('total_skills', $target_id, 1);
    
    $LessonUnblockUsermapModel = new \App\Models\LessonUnblockUsermapModel();
    $LessonUnblockUsermapModel->unblockNext('skills', $target_id);

    $SkillModel = new \App\Models\SkillModel();
    $SkillModel->linkItem($target_id, session()->get('user_id'), 'next');
    $quests = $QuestModel->getCompletedList('skill,total_skills');
    if(!empty($quests)){
        $NotificationModel = new \App\Models\NotificationModel();
        foreach($quests as $quest){
            $NotificationModel->notifyQuest($quest);
        }
    }
});
Events::on('levelUp', static function ($level_data) {
    $NotificationModel = new \App\Models\NotificationModel();
    $NotificationModel->notifyLevel($level_data);
    
    $AchievementModel = new \App\Models\AchievementModel();
    $achievements = $AchievementModel->getListToLink('total_level');
    if(!empty($achievements)){
        $NotificationModel = new \App\Models\NotificationModel();
        foreach($achievements as $achievement){
            $AchievementModel->linkItem($achievement);
            $NotificationModel->notifyAchievement($achievement);
        }
    }
});
Events::on('achievementGained', static function ($target_id) {
    $AchievementModel = new \App\Models\AchievementModel();
    $NotificationModel = new \App\Models\NotificationModel();
    $achievementsTotalAchievements = $AchievementModel->getListToLink('total_achievements');
    if(!empty($achievementsTotalAchievements)){
        foreach($achievementsTotalAchievements as $achievement){
            $AchievementModel->linkItem($achievement);
            $NotificationModel->notifyAchievement($achievement);
        }
    }
});
Events::on('invitationQuestClaimed', static function ($user_id) {
    $UserInvitationModel = new \App\Models\UserInvitationModel();
    $NotificationModel = new \App\Models\NotificationModel();
    $invited_user = $UserInvitationModel->rewardItem($user_id);
    if($invited_user){
        $NotificationModel->notifyInvitation($invited_user);
    }
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
