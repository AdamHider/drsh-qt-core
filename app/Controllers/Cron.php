<?php

namespace App\Controllers;

class Cron extends BaseController
{
    public function init()
    {
        $LessonDailyModel = model('LessonDailyModel');
        $LessonDailyModel->createItem('daily_lexis');
        $LessonDailyModel->createItem('daily_chat');

        $QuestModel = model('QuestModel');
        $QuestModel->linkDailyList();
    }
    
}
