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
});
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
