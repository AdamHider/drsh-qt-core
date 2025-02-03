<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->group('auth', [], function($routes) {
    $routes->get('login', 'Admin\Auth::login');
    $routes->post('authenticate', 'Admin\Auth::authenticate');
    $routes->get('logout', 'Admin\Auth::logout');
    $routes->get('register', 'Admin\Auth::register');
    $routes->post('store', 'Admin\Auth::store');
});

  /*--------------*/
 /* ADMIN ROUTES */
/*--------------*/

$routes->group('admin', ['filter' => 'auth'], function($routes) {
    $routes->get('', 'Admin\Dashboard::index');

    $routes->group('lessons', [], function($routes) {
        $routes->get('', 'Admin\Lessons::index');
        $routes->get('form/(:num)', 'Admin\Lessons::form/$1');
        $routes->get('form', 'Admin\Lessons::form');
        $routes->post('save/(:num)', 'Admin\Lessons::save/$1');
        $routes->post('save', 'Admin\Lessons::save');
        $routes->get('delete/(:num)', 'Admin\Lessons::delete/$1');
    });
    $routes->group('courses', [], function($routes) {
        $routes->get('', 'Admin\Courses::index');
        $routes->get('form/(:num)', 'Admin\Courses::form/$1');
        $routes->get('form', 'Admin\Courses::form');
        $routes->post('save/(:num)', 'Admin\Courses::save/$1');
        $routes->post('save', 'Admin\Courses::save');
        $routes->get('delete/(:num)', 'Admin\Courses::delete/$1');
    });
    $routes->group('course_sections', [], function($routes) {
        $routes->get('form/(:num)/(:num)', 'Admin\CourseSections::form/$1/$2');
        $routes->get('form/(:num)', 'Admin\CourseSections::form/$1');
        $routes->post('create/(:num)', 'Admin\CourseSections::save/$1');
        $routes->post('save/(:num)/(:num)', 'Admin\CourseSections::save/$1/$2');
        $routes->get('delete/(:num)/(:num)', 'Admin\CourseSections::delete/$1/$2');
    });
    $routes->group('quest_groups', [], function($routes) {
        $routes->get('', 'Admin\QuestGroups::index');
        $routes->get('form/(:num)', 'Admin\QuestGroups::form/$1');
        $routes->get('form', 'Admin\QuestGroups::form');
        $routes->post('save/(:num)', 'Admin\QuestGroups::save/$1');
        $routes->post('save', 'Admin\QuestGroups::save');
        $routes->get('delete/(:num)', 'Admin\QuestGroups::delete/$1');
    });
    $routes->group('quests', [], function($routes) {
        $routes->get('form/(:num)/(:num)', 'Admin\Quests::form/$1/$2');
        $routes->get('form', 'Admin\Quests::form');
        $routes->post('save/(:num)', 'Admin\Quests::save/$1');
        $routes->post('save/(:num)/(:num)', 'Admin\Quests::save/$1/$2');
        $routes->get('delete/(:num)/(:num)', 'Admin\Quests::delete/$1/$2');
    });
    $routes->get('image/(:any)', 'Image::index/$1');    $routes->group('languages', function($routes) {
        $routes->get('', 'Admin\Language::index');
        $routes->get('create', 'Admin\Language::create');
        $routes->post('store', 'Admin\Language::store');
        $routes->get('edit/(:num)', 'Admin\Language::edit/$1');
        $routes->post('update/(:num)', 'Admin\Language::update/$1');
        $routes->get('delete/(:num)', 'Admin\Language::delete/$1');
    });
    $routes->group('media', function($routes) {
        $routes->get('', 'Admin\Media::index');
        $routes->get('list', 'Admin\Media::getData');
        $routes->post('upload', 'Admin\Media::upload');
        $routes->post('create-directory', 'Admin\Media::createDirectory');
        $routes->post('rename', 'Admin\Media::rename');
        $routes->post('delete', 'Admin\Media::delete');
    });
});

$routes->get('/image/(:any)', 'Image::index/$1');

  /*---------------*/
 /* CLIENT ROUTES */
/*---------------*/

$routes->post('/Lesson/(:any)', 'Lesson::$1');
$routes->post('/Exercise/(:any)', 'Exercise::$1');

$routes->post('/User/(:any)', 'User::$1');
$routes->post('/Auth/(:any)', 'Auth::$1');

$routes->post('/Achievement/(:any)', 'Achievement::$1');
$routes->post('/Quest/(:any)', 'Quest::$1');
$routes->post('/Classroom/(:any)', 'Classroom::$1');
$routes->post('/Course/(:any)', 'Course::$1');
$routes->post('/CourseSection/(:any)', 'CourseSection::$1');
$routes->post('/Image/(:any)', 'Image::$1');
$routes->post('/Skill/(:any)', 'Skill::$1');
$routes->post('/Character/(:any)', 'Character::$1');

$routes->post('/Admin/Lesson/(:any)', 'Admin\Lesson::$1');
$routes->post('/Admin/Course/(:any)', 'Admin\Course::$1');
$routes->post('/Admin/CourseSection/(:any)', 'Admin\CourseSection::$1');
