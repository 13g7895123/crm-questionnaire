<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

// API v1 Routes
$routes->group('api/v1', ['namespace' => 'App\Controllers\Api\V1'], function ($routes) {
    
    // Auth routes (public)
    $routes->post('auth/login', 'AuthController::login');
    $routes->post('auth/verify', 'AuthController::verify');
    $routes->post('auth/refresh', 'AuthController::refresh');
    
    // Auth routes (authenticated)
    $routes->group('auth', ['filter' => 'jwt'], function ($routes) {
        $routes->post('logout', 'AuthController::logout');
        $routes->get('me', 'AuthController::me');
    });

    // User routes
    $routes->group('users', ['filter' => 'jwt'], function ($routes) {
        $routes->put('me', 'UserController::updateMe');
        $routes->put('me/password', 'UserController::updatePassword');
        $routes->get('/', 'UserController::index');
        $routes->post('/', 'UserController::create');
        $routes->put('(:segment)', 'UserController::update/$1');
        $routes->delete('(:segment)', 'UserController::delete/$1');
    });

    // Organization routes
    $routes->group('organizations', ['filter' => 'jwt'], function ($routes) {
        $routes->get('/', 'OrganizationController::index');
        $routes->get('(:segment)', 'OrganizationController::show/$1');
        $routes->post('/', 'OrganizationController::create');
        $routes->put('(:segment)', 'OrganizationController::update/$1');
        $routes->delete('(:segment)', 'OrganizationController::delete/$1');
    });

    // Suppliers route (alias for organizations with type=SUPPLIER)
    $routes->get('suppliers', 'OrganizationController::suppliers', ['filter' => 'jwt']);

    // Department routes
    $routes->group('departments', ['filter' => 'jwt'], function ($routes) {
        $routes->get('/', 'DepartmentController::index');
        $routes->get('(:segment)', 'DepartmentController::show/$1');
        $routes->post('/', 'DepartmentController::create');
        $routes->put('(:segment)', 'DepartmentController::update/$1');
        $routes->delete('(:segment)', 'DepartmentController::delete/$1');
    });

    // Project routes
    $routes->group('projects', ['filter' => 'jwt'], function ($routes) {
        $routes->get('/', 'ProjectController::index');
        $routes->get('stats', 'ProjectController::stats');
        $routes->get('(:segment)', 'ProjectController::show/$1');
        $routes->post('/', 'ProjectController::create');
        $routes->put('(:segment)', 'ProjectController::update/$1');
        $routes->delete('(:segment)', 'ProjectController::delete/$1');
    });

    // Project-Supplier routes (for supplier-specific operations)
    $routes->group('project-suppliers', ['filter' => 'jwt'], function ($routes) {
        // Answers
        $routes->get('(:segment)/answers', 'AnswerController::index/$1');
        $routes->put('(:segment)/answers', 'AnswerController::update/$1');
        $routes->post('(:segment)/submit', 'AnswerController::submit/$1');
        
        // Basic Info (for SAQ templates)
        $routes->get('(:segment)/basic-info', 'AnswerController::getBasicInfo/$1');
        $routes->put('(:segment)/basic-info', 'AnswerController::saveBasicInfo/$1');
        
        // Scoring & Validation
        $routes->post('(:segment)/calculate-score', 'AnswerController::calculateScore/$1');
        $routes->get('(:segment)/visible-questions', 'AnswerController::getVisibleQuestions/$1');
        $routes->post('(:segment)/validate', 'AnswerController::validateAnswers/$1');
        
        // Reviews
        $routes->post('(:segment)/review', 'ReviewController::review/$1');
        $routes->get('(:segment)/reviews', 'ReviewController::history/$1');
    });

    // Template routes
    $routes->group('templates', ['filter' => 'jwt'], function ($routes) {
        $routes->get('/', 'TemplateController::index');
        $routes->get('(:segment)', 'TemplateController::show/$1');
        $routes->post('/', 'TemplateController::create');
        $routes->put('(:segment)', 'TemplateController::update/$1');
        $routes->delete('(:segment)', 'TemplateController::delete/$1');
        $routes->post('(:segment)/versions', 'TemplateController::createVersion/$1');
        $routes->get('(:segment)/versions/(:segment)', 'TemplateController::showVersion/$1/$2');
        
        // v2.0 Structure API
        $routes->get('(:segment)/structure', 'TemplateController::getStructure/$1');
        $routes->put('(:segment)/structure', 'TemplateController::saveStructure/$1');
        
        // Test Excel Import
        $routes->post('test-excel', 'TemplateController::testExcel');
    });

    // Review routes
    $routes->group('reviews', ['filter' => 'jwt'], function ($routes) {
        $routes->get('pending', 'ReviewController::pending');
        $routes->get('stats', 'ReviewController::stats');
    });

    // File upload
    $routes->post('files/upload', 'FileController::upload', ['filter' => 'jwt']);
});
