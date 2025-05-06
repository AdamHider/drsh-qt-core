<?php

namespace App\Models;

use CodeIgniter\Model;

class LessonDailyModel extends LessonModel
{
    private $currentPage = 0;
    private $titles = [
        'Sultrix', 'Silvaris', 'Velonith', 'Axenor', 'Luminara', 'Karion', 'Zenorath', 'Hydroxis', 'Gravionis', 'Ignirum',
    ];
    private $daily_course = [
        'title' => '', 
        'background_image' => '', 
        'language_id' => 1, 
        'published' => 1, 
        'is_private' => 1
    ];
    private $daily_course_section = [
        'course_id' => 0,
        'title' => '', 
        'background_image' => '', 
        'language_id' => 1, 
        'published' => 1, 
        'is_private' => 1
    ];
    private $daily_lesson = [
        'course_id' => 4,
        'course_section_id' => 22,
        'language_id' => 1, 
        'title' => 'Daily', 
        'description' => 'Daily', 
        'type' => '', 
        'pages' => '[]',
        'cost_config' => '[]', 
        'reward_config' => '[]', 
        'image' => '', 
        'published' => 1, 
        'order' => 0, 
        'owner_id' => 0, 
        'is_private' => 1
    ];
    private $daily_lesson_pages = [
        'daily_lexis' => [
            'template' => [
                'quiz_page' => [
                    'title' => 'Где здесь "%s"?',
                    'form_template' => 'image',
                    'page_template' => 'imageSelection'
                ],
                'read_page' => [
                    'title' => 'Выучите слово "%s"',
                    'page_template' => 'imageSelection'
                ]
            ],
            'pattern' => 'quiz_only',
            'word_list' => []
        ],
        'daily_chat' => [
            'index' => 1,
            'title' => 'Ответьте собеседнику',
            'subtitle' => '',
            'form_template' => 'chatPuzzle',
            'page_template' => 'chat',
            'form_stepper' => true,
            'template_config' => []
        ]
    ];
    
    public function getList()
    {
        $ExerciseModel = model('ExerciseModel');
        $ResourceModel = model('ResourceModel');
        $CourseSectionModel = model('CourseSectionModel');

        $lessons = $this->join('exercises', 'exercises.lesson_id = lessons.id AND exercises.user_id ='.session()->get('user_id'), 'left')
        ->select('lessons.*, exercises.id as exercise_id')
        ->like('type', 'daily_%')->where('published', 1)->where('is_private', 0)->get()->getResultArray();
        foreach($lessons as $key => &$lesson){
            $lesson['course_section']   = $CourseSectionModel->getItem($lesson['course_section_id']);
            $lesson['satellites']       = $this->getSatellites($lesson['id'], 'lite');
            $lesson['image']            = base_url('image/index.php'.$lesson['image']);
            $lesson['exercise']         = $ExerciseModel->getItem($lesson['exercise_id']);
            $lesson['progress']         = $this->getOverallProgress($lesson['id']);
            $lesson['is_explored']      = isset($lesson['exercise']['id']);
            
            $lesson['cost']             = $ResourceModel->proccessItemCost(json_decode($lesson['cost_config'], true));
            $lesson['reward']           = $ResourceModel->proccessItemGroupReward(json_decode($lesson['reward_config'], true));
            unset($lesson['unblock_config']);
            unset($lesson['cost_config']);
            unset($lesson['reward_config']);
            unset($lesson['pages']);
        }
        return $lessons;

    }
    public function createItem($type)
    {
        $this->daily_lesson['course_id'] = $this->createCourseItem($type);
        $this->daily_lesson['course_section_id'] = $this->createCourseSectionItem($this->daily_lesson['course_id'], $type);

        $this->daily_lesson['title'] = $this->generateTitle($type);

        $this->daily_lesson['pages'] = json_encode($this->compileItemPages($type), JSON_UNESCAPED_SLASHES);

        $this->daily_lesson['type'] = $type;
        $this->daily_lesson['image'] = '/planets/'.$type.'_'.rand(1,1).'.png';

        $this->where('type', $type)->delete();
        $this->insert($this->daily_lesson);
    }
    private function generateTitle($type)
    {
        $integer = rand(0,3);
        srand((double)microtime()*1000000);
        $result = $this->titles[rand(0, count($this->titles)-1)];
        if($integer > 1){
            $result .= ' '.$integer;
        }
        return $result;
    }
    private function compileItemPages($type)
    {
        $LessonWordsModel = model('LessonWordsModel');

        $result = $this->daily_lesson_pages[$type];
        $LessonWordsModel->where('language_id', 1)->where('type', $type)->orderBy('id', 'RANDOM');
        if($type == 'daily_lexis'){
            $item_list = $LessonWordsModel->limit(12)->get()->getResultArray();
            foreach($item_list as $key => $item){
                $word['index'] = $key+1;
                $result['word_list'][] = $word;
            }
        } else 
        if($type == 'daily_chat'){
            $item_list = $LessonWordsModel->limit(1)->get()->getRowArray();
            return $result['template_config'] = $item_list; 
        }
        return $result;
    }
    private function createCourseItem($type)
    {
        $CourseModel = model('CourseModel');
        $course = $CourseModel->where('title', $type)->get()->getRowArray();
        $this->daily_course['background_image'] = '/backgrounds/'.$type.'_'.rand(1,1).'.jpg';
        $this->daily_course['title'] = $type;
        if(empty($course)){
            $CourseModel->insert($this->daily_course);
            return $CourseModel->getInsertID();
        } else {
            $CourseModel->where('id', $course['id'])->set($this->daily_course)->update();
        }
        return $course['id'];
    }
    private function createCourseSectionItem($course_id, $type)
    {
        $CourseSectionModel = model('CourseSectionModel');

        $course_section = $CourseSectionModel->where('title', $type)->where('course_id', $course_id)->get()->getRowArray();
        $this->daily_course_section['title'] = $type;
        $this->daily_course_section['background_image'] = '/backgrounds/'.$type.'_'.rand(1,1).'.jpg';
        $this->daily_course_section['course_id'] = $course_id;
        if(empty($course_section)){
            $CourseSectionModel->insert($this->daily_course_section);
            return $CourseSectionModel->getInsertID();
        } else {
            $CourseSectionModel->where('id', $course_section['id'])->set($this->daily_course_section)->update();
        }
        return $course_section['id'];
    }
}