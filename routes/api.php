<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CoachController;
use App\Http\Controllers\Debate\ApplicationController;
use App\Http\Controllers\Debate\TeamController;
use App\Http\Controllers\DebateController;
use App\Http\Controllers\DebaterController;
use App\Http\Controllers\FacultyController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\JudgeController;
use App\Http\Controllers\MotionController;
use App\Http\Controllers\SubClassificationController;
use App\Http\Middleware\JwtMiddleware;
use App\Http\Controllers\LiveController;
use App\Http\Controllers\RateController;
use App\Http\Controllers\UniversityController;
use App\Http\Middleware\Auth\AuthenticateAdmin;
use App\Http\Middleware\Auth\AuthenticateDebater;
use App\Http\Middleware\Auth\AuthenticateJudge;
use Illuminate\Support\Facades\Route;

Route::controller(AuthController::class)->group(function () {
    Route::post('register/{actor}', 'register')->where('actor', 'user|debater|judge|coach')->middleware(AuthenticateAdmin::class);
    Route::post('login', 'login');
    Route::get('logout', 'logout');
    Route::get('refresh', 'refresh');
    Route::get('profile', 'profile')->middleware(JwtMiddleware::class);
    Route::post('upload/image', 'uploadImage');
    Route::delete('destroy/image', 'destroyImage');
    Route::get('user/ban/{userId}', 'ban'); // Consider moving to admin middleware
    Route::get('user/index', 'index'); // Consider moving to admin middleware
    Route::get('user/{userId}', 'show')->middleware(AuthenticateAdmin::class);
});

Route::get('universities/list', [UniversityController::class, 'list']);
Route::post('documentation')->withoutMiddleware(''); // Verify this route's purpose
Route::get('motion/classification', [SubClassificationController::class, 'index']);
Route::get('motion/get', [MotionController::class, 'index']);
Route::post('motion/create', [MotionController::class, 'create']);
Route::delete('motion/delete/{motion}', [MotionController::class, 'delete']);
Route::get('data/education', [FacultyController::class, 'index']);
Route::post('register-application/debates/{debate}', [ApplicationController::class, 'request']);
Route::get('coach/index', [CoachController::class, 'index']);
Route::post('debates/{debate}/assign-teams', [AdminController::class, 'assignTeams']);
Route::get('debate/apply/{debateId}', [ApplicationController::class, 'apply']);
Route::post('test', [AuthController::class, 'test']);


// Admin routes
Route::middleware([AuthenticateAdmin::class])->group(function () {
    Route::get('debates/applications', [ApplicationController::class, 'index']); // Changed to GET and moved here
    Route::post('debates/applications/respond', [ApplicationController::class, 'respond']);
    Route::post('debates/applications/teams', [TeamController::class, 'selectTeams']);
    Route::get('debates/{debate}/teams/index', [TeamController::class, 'listTeams']);
    Route::get('getJudgeRates/{judge_id}', [JudgeController::class, 'getJudgeRates']);
    Route::get('getJudgeRatesByDebate/{debateId}', [JudgeController::class, 'getJudgeRatesByDebate']);
});

// Debate-related routes
Route::prefix('debates')->group(function () {
    Route::get('/', [DebateController::class, 'indexForAdmin'])->middleware(AuthenticateAdmin::class);
    Route::get('/', [DebateController::class, 'index'])->middleware(JwtMiddleware::class);
    Route::post('/', [DebateController::class, 'create'])->middleware(AuthenticateAdmin::class);
    Route::get('{debate}', [DebateController::class, 'show']);
    Route::post('{debate}/applications/apply-judge', [ApplicationController::class, 'applyJudge'])->middleware(AuthenticateJudge::class);
    Route::post('{debate}/applications/apply-debater', [ApplicationController::class, 'applyDebater'])->middleware(AuthenticateDebater::class);
    Route::put('{debate}/preparation', [DebateController::class, 'preparationStatus']);
    Route::post('{debate}/result', [DebateController::class, 'result']); // Fixed from previous issue
    // Uncomment and adjust these routes as needed
    // Route::patch('{debate}/status', [DebateController::class, 'updateStatus'])->middleware('auth.admin');
    // Route::patch('{debate}/cancel', [DebateController::class, 'cancel'])->middleware('auth.admin');
    // Route::patch('{debate}/bugged', [DebateController::class, 'markAsBugged'])->middleware('auth.admin');
    // Route::patch('{debate}/finish', [DebateController::class, 'finish'])->middleware('auth.admin');
});

// Route::prefix("article")->group(function (){
//     Route::post('/',)
// });

// Zoom Integration Routes
Route::prefix('zoom')->middleware(JwtMiddleware::class)->group(function () {
    Route::post('link-judge', [App\Http\Controllers\ZoomController::class, 'linkJudgeToZoom']);
    Route::post('debates/{debate}/create-meeting', [App\Http\Controllers\ZoomController::class, 'createMeeting']);
    Route::get('debates/{debate}/start-url', [App\Http\Controllers\ZoomController::class, 'getStartUrl']);
    Route::get('debates/{debate}/join-url', [App\Http\Controllers\ZoomController::class, 'getJoinUrl']);
    Route::post('debates/{debate}/start', [App\Http\Controllers\ZoomController::class, 'startDebate']);
    Route::get('debates/{debate}/recordings', [App\Http\Controllers\ZoomController::class, 'getRecordings']);
    Route::post('check-upcoming-meetings', [App\Http\Controllers\ZoomController::class, 'checkUpcomingMeetings'])->middleware(AuthenticateAdmin::class);
});

// Recording Management Routes
Route::prefix('recordings')->group(function () {
    Route::post('debates/{debate}/upload', [App\Http\Controllers\RecordingController::class, 'uploadRecording'])->middleware(JwtMiddleware::class);
    Route::post('debates/{debate}/zoom-link', [App\Http\Controllers\RecordingController::class, 'storeZoomLink'])->middleware(JwtMiddleware::class);
    Route::get('debates/{debate}', [App\Http\Controllers\RecordingController::class, 'getRecording'])->middleware(AuthenticateAdmin::class);
    Route::delete('debates/{debate}', [App\Http\Controllers\RecordingController::class, 'deleteRecording'])->middleware(AuthenticateAdmin::class);
});

// Report System Routes
Route::prefix('reports')->group(function () {
    Route::post('/', [App\Http\Controllers\ReportController::class, 'store'])->middleware(JwtMiddleware::class);
    Route::get('/my-reports', [App\Http\Controllers\ReportController::class, 'myReports'])->middleware(JwtMiddleware::class);

    // Admin only routes
    Route::middleware(AuthenticateAdmin::class)->group(function () {
        Route::get('/', [App\Http\Controllers\ReportController::class, 'index']);
        Route::get('/statistics', [App\Http\Controllers\ReportController::class, 'statistics']);
        Route::get('/{report}', [App\Http\Controllers\ReportController::class, 'show']);
        Route::post('/{report}/handle', [App\Http\Controllers\ReportController::class, 'handle']);
    });
});

// Enhanced Debate Routes
Route::prefix('debates')->group(function () {
    // Existing routes remain the same, adding new ones:
    Route::post('{debate}/assign-teams', [DebateController::class, 'assignTeams'])->middleware(AuthenticateAdmin::class);
    Route::post('{debate}/add-panelist-judge', [DebateController::class, 'addPanelistJudge'])->middleware(AuthenticateAdmin::class);
    Route::post('{debate}/submit-results', [DebateController::class, 'submitResults'])->middleware(JwtMiddleware::class);
    Route::get('{debate}/participants', [DebateController::class, 'getParticipants']);
    Route::get('{debate}/judges', [DebateController::class, 'getJudges']);
});

// System Maintenance Routes
Route::prefix('system')->middleware(AuthenticateAdmin::class)->group(function () {
    Route::post('check-preparation-phase', [DebateController::class, 'checkPreparationPhase']);
});

///from leen - Updated Rating Routes
Route::post('rate_judge', [RateController::class, 'rateJudge'])->middleware(JwtMiddleware::class);
Route::post('addfeedback', [FeedbackController::class, 'addFeedback']);
Route::get('getFeedbacks/{debate}', [FeedbackController::class, 'getFeedbacks']);
Route::get('getFeedbacksByDebater', [FeedbackController::class, 'getFeedbacksByDebater']);

Route::controller(LiveController::class)->group(function () {
    Route::get('live/test', 'testing');
    Route::post('webhook', 'webhook');
});

Route::get('this/test', function () {
    return 'this is test';
});
