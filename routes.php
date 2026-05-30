<?php

declare(strict_types=1);

use App\Controllers\AdminController;
use App\Controllers\AuthController;
use App\Controllers\DocumentController;
use App\Controllers\LandingController;
use App\Controllers\LeaderboardController;
use App\Controllers\QuestionController;
use App\Controllers\QuizController;
use App\Controllers\SubmissionController;
use App\Controllers\UserController;
use App\Controllers\WorkspaceController;

$router->get('/', [LandingController::class, 'index']);
$router->get('/privacy-policy', [LandingController::class, 'privacyPolicy']);
$router->get('/terms-of-use', [LandingController::class, 'termsOfUse']);
$router->get('/help-center', [LandingController::class, 'helpCenter']);
$router->get('/contact', [LandingController::class, 'contact']);

$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/register', [AuthController::class, 'showRegister']);
$router->post('/register', [AuthController::class, 'register']);
$router->post('/logout', [AuthController::class, 'logout']);
$router->get('/workspace', [WorkspaceController::class, 'index']);

$router->get('/dashboard', [WorkspaceController::class, 'index']);

$router->get('/documents', [DocumentController::class, 'index']);
$router->get('/documents/create', [DocumentController::class, 'create']);
$router->post('/documents', [DocumentController::class, 'store']);
$router->post('/documents/{id}/delete', [DocumentController::class, 'delete']);
$router->get('/documents/{id}', [DocumentController::class, 'show']);

$router->get('/quizzes', [QuizController::class, 'index']);
$router->get('/quizzes/create', [QuizController::class, 'create']);
$router->post('/quizzes', [QuizController::class, 'store']);
$router->get('/quizzes/preview', [QuizController::class, 'preview']);
$router->post('/quizzes/preview/save', [QuizController::class, 'savePreview']);
$router->post('/quizzes/preview/suggest-ai', [QuizController::class, 'suggestAiPreview']);
$router->post('/quizzes/preview/discard', [QuizController::class, 'discardPreview']);
$router->post('/quizzes/{id}/delete', [QuizController::class, 'delete']);
$router->get('/quizzes/{id}', [QuizController::class, 'show']);
$router->get('/quizzes/{id}/take', [QuizController::class, 'take']);
$router->post('/quizzes/{id}/submit', [QuizController::class, 'submit']);
$router->get('/quizzes/{id}/export', [QuizController::class, 'export']);
$router->get('/leaderboard', [LeaderboardController::class, 'redirectGone']);

$router->get('/questions', [QuestionController::class, 'index']);
$router->get('/questions/create', [QuestionController::class, 'create']);
$router->post('/questions', [QuestionController::class, 'store']);
$router->get('/questions/{id}/edit', [QuestionController::class, 'edit']);
$router->post('/questions/{id}/update', [QuestionController::class, 'update']);
$router->post('/questions/{id}/correct', [QuestionController::class, 'updateCorrectAnswer']);
$router->post('/questions/{id}/delete', [QuestionController::class, 'delete']);
$router->post('/questions/{id}/report', [QuestionController::class, 'report']);

$router->get('/submissions', [SubmissionController::class, 'index']);
$router->get('/submissions/{id}', [SubmissionController::class, 'show']);

$router->get('/admin', [AdminController::class, 'index']);
$router->get('/admin/dashboard', [AdminController::class, 'dashboard']);
$router->get('/admin/ai', [AdminController::class, 'aiSettings']);
$router->post('/admin/ai', [AdminController::class, 'saveAiSettings']);
$router->get('/admin/questions', [AdminController::class, 'questions']);
$router->get('/admin/reports', [AdminController::class, 'reports']);
$router->post('/admin/reports/{id}/status', [AdminController::class, 'updateReportStatus']);
$router->get('/admin/documents', [AdminController::class, 'documents']);
$router->post('/admin/documents/{id}/delete', [AdminController::class, 'deleteDocument']);
$router->get('/admin/members', [AdminController::class, 'members']);
$router->get('/admin/users', [AdminController::class, 'users']);
$router->post('/admin/users/{id}/role', [AdminController::class, 'updateUserRole']);
$router->post('/admin/users/{id}/lock', [AdminController::class, 'updateUserLock']);

$router->get('/users', [UserController::class, 'index']);
