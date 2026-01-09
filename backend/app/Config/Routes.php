<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

/**
 * Helper function to send CORS headers for all OPTIONS requests
 */
function sendCorsPreflightResponse()
{
    $response = service('response');

    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    $allowedOrigins = ['http://localhost:8104', 'http://127.0.0.1:8104', 'http://localhost:3000', 'http://localhost:9104'];

    if (in_array($origin, $allowedOrigins, true)) {
        $response->setHeader('Access-Control-Allow-Origin', $origin);
        $response->setHeader('Access-Control-Allow-Credentials', 'true');
    }

    $response->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
    $response->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Accept-Language');
    $response->setHeader('Access-Control-Max-Age', '7200');
    $response->setHeader('Content-Length', '0');
    $response->setStatusCode(204);
    $response->send();
    exit;
}

/**
 * Handle all OPTIONS requests for CORS preflight at different path depths
 */
$routes->options('(:any)', function () {
    sendCorsPreflightResponse();
});
$routes->options('(:any)/(:any)', function () {
    sendCorsPreflightResponse();
});
$routes->options('(:any)/(:any)/(:any)', function () {
    sendCorsPreflightResponse();
});
$routes->options('(:any)/(:any)/(:any)/(:any)', function () {
    sendCorsPreflightResponse();
});

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

        // Question Reviews
        $routes->get('(:segment)/question-reviews', 'ReviewController::getQuestionReviews/$1');
        $routes->put('(:segment)/question-reviews', 'ReviewController::saveQuestionReviews/$1');
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

        // Import Excel to template
        $routes->post('(:segment)/import-excel', 'TemplateController::importExcel/$1');
    });

    // Review routes
    $routes->group('reviews', ['filter' => 'jwt'], function ($routes) {
        $routes->get('pending', 'ReviewController::pending');
        $routes->get('stats', 'ReviewController::stats');
    });

    // File upload
    $routes->post('files/upload', 'FileController::upload', ['filter' => 'jwt']);

    // Debug routes
    $routes->post('debug/log-match', 'DebugController::logMatch');

    // Responsible Minerals (RM) Routes
    $routes->group('rm', ['filter' => 'jwt'], function ($routes) {
        // Template Sets
        $routes->get('template-sets', 'TemplateSets::index');
        $routes->post('template-sets', 'TemplateSets::create');
        $routes->get('template-sets/(:num)', 'TemplateSets::show/$1');
        $routes->put('template-sets/(:num)', 'TemplateSets::update/$1');
        $routes->delete('template-sets/(:num)', 'TemplateSets::delete/$1');

        // 專案
        $routes->group('projects', function ($routes) {
            $routes->get('/', 'RmProjects::index');
            $routes->post('/', 'RmProjects::create');
            $routes->get('(:num)', 'RmProjects::show/$1');
            $routes->put('(:num)', 'RmProjects::update/$1');
            $routes->delete('(:num)', 'RmProjects::delete/$1');
            $routes->get('(:num)/progress', 'RmProjects::progress/$1');
            $routes->get('(:num)/export', 'RmProjects::export/$1');
            $routes->get('(:num)/consolidated-report', 'RmProjects::consolidatedReport/$1');
            $routes->post('(:num)/suppliers/import', 'RmProjects::importSuppliers/$1');
        });

        // Supplier Assignments
        $routes->get('projects/(:num)/suppliers', 'RmSupplierAssignments::index/$1');
        $routes->put('projects/(:num)/suppliers/(:num)/templates', 'RmSupplierAssignments::assignTemplate/$1/$2');
        $routes->post('projects/(:num)/suppliers/batch-assign-templates', 'RmSupplierAssignments::batchAssign/$1');
        $routes->get('projects/(:num)/suppliers/template-assignment-template', 'RmSupplierAssignments::downloadTemplateAssignmentTemplate/$1');
        $routes->post('projects/(:num)/suppliers/(:num)/notify', 'RmSupplierAssignments::notify/$1/$2');
        $routes->post('projects/(:num)/suppliers/notify-all', 'RmSupplierAssignments::notifyAll/$1');

        // Questionnaire Portal (Supplier Filling)
        $routes->get('questionnaires/(:num)', 'RmQuestionnaires::show/$1');
        $routes->post('questionnaires/(:num)/import', 'RmQuestionnaires::import/$1');
        $routes->post('questionnaires/(:num)/save', 'RmQuestionnaires::saveManual/$1');
        $routes->post('questionnaires/(:num)/submit', 'RmQuestionnaires::submit/$1');

        // Review Management
        $routes->get('reviews/pending', 'RmReviews::pending');
        $routes->post('reviews/(:num)', 'RmReviews::review/$1');
        $routes->get('reviews/(:num)/history', 'RmReviews::history/$1');
    });
});
