<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->post('/Lesson/(:any)', 'Lesson::$1');
$routes->post('/Exercise/(:any)', 'Exercise::$1');
$routes->post('/User/(:any)', 'User::$1');
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
