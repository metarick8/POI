<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CoachController;
use App\Http\Controllers\Debate\ApplicationController;
use App\Http\Controllers\Debate\TeamController;
use App\Http\Controllers\DebateController;
use App\Http\Controllers\FacultyController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\JudgeController;
use App\Http\Controllers\MotionController;
use App\Http\Controllers\SubClassificationController;
use App\Http\Middleware\JwtMiddleware;
use App\Http\Controllers\LiveController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\RateController;
use App\Http\Controllers\UniversityController;
use App\Http\Controllers\ZoomController;
use App\Http\Middleware\Auth\AuthenticateAdmin;
use App\Http\Middleware\Auth\AuthenticateDebater;
use App\Http\Middleware\Auth\AuthenticateJudge;
use App\Models\Admin;
use App\Services\FirebaseService;
use Illuminate\Support\Facades\Route;

Route::controller(AuthController::class)->group(function () {
    Route::post('register/{actor}', 'register')->where('actor', 'debater|judge|coach')->middleware(AuthenticateAdmin::class);
    Route::post('login', 'login');
    Route::get('logout', 'logout');
    Route::get('refresh', 'refresh');
    Route::get('profile', 'profile')->middleware(JwtMiddleware::class);
    Route::post('upload/image', 'uploadImage');
    Route::delete('destroy/image', 'destroyImage');
    Route::get('user/ban/{userId}', 'ban')->middleware(AuthenticateAdmin::class);
    Route::get('user/index', 'index')->middleware(AuthenticateAdmin::class);
    Route::get('user/{userId}', 'show')->middleware(AuthenticateAdmin::class);
});

Route::get('universities/list', [UniversityController::class, 'list']);
Route::post('documentation')->withoutMiddleware('');
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
    Route::get('debates/applications', [ApplicationController::class, 'index']);
    Route::post('debates/applications/respond', [ApplicationController::class, 'respond']);
    Route::post('debates/applications/teams', [TeamController::class, 'selectTeams']);
    Route::get('debates/{debate}/teams/index', [TeamController::class, 'listTeams']);
    Route::get('getJudgeRates/{judge_id}', [JudgeController::class, 'getJudgeRates']);
    Route::get('getJudgeRatesByDebate/{debateId}', [JudgeController::class, 'getJudgeRatesByDebate']);
});

// Debate-related routes (consolidated)
Route::prefix('debates')->group(function () {
    Route::get('/list', [DebateController::class, 'index'])->middleware(JwtMiddleware::class);
    Route::get('/', [DebateController::class, 'indexForAdmin'])->middleware(AuthenticateAdmin::class);
    Route::post('/', [DebateController::class, 'create'])->middleware(AuthenticateAdmin::class);
    Route::get('{debate}', [DebateController::class, 'show']);
    Route::post('{debate}/applications/apply-judge', [ApplicationController::class, 'applyJudge'])->middleware(AuthenticateJudge::class);
    Route::post('{debate}/applications/apply-debater', [ApplicationController::class, 'applyDebater'])->middleware(AuthenticateDebater::class);
    Route::put('{debate}/preparation', [DebateController::class, 'preparationStatus']);
    Route::post('{debate}/result', [DebateController::class, 'result']);
    Route::get('{debate}/debaters', [DebateController::class, 'getDebaters'])->middleware(AuthenticateJudge::class);
    Route::patch('{debate}/finish', [DebateController::class, 'finish'])->middleware(AuthenticateJudge::class);
    Route::post('{debate}/assign-teams', [DebateController::class, 'assignTeams'])->middleware(AuthenticateAdmin::class);
    Route::post('{debate}/add-panelist-judge', [DebateController::class, 'addPanelistJudge'])->middleware(AuthenticateAdmin::class);
    Route::post('{debate}/submit-results', [DebateController::class, 'submitResults'])->middleware(JwtMiddleware::class);
    Route::get('{debate}/participants', [DebateController::class, 'getParticipants']);
    Route::get('{debate}/judges', [DebateController::class, 'getJudges']);
});

// Zoom Integration Routes
Route::prefix('zoom')->middleware(JwtMiddleware::class)->group(function () {
    Route::post('link-judge', [ZoomController::class, 'linkJudgeToZoom']);
    Route::post('debates/{debate}/create-meeting', [ZoomController::class, 'createMeeting']);
    Route::get('debates/{debate}/start-url', [ZoomController::class, 'getStartUrl']);
    Route::get('debates/{debate}/join-url', [ZoomController::class, 'getJoinUrl']);
    Route::post('debates/{debate}/start', [ZoomController::class, 'startDebate']);
    Route::get('debates/{debate}/recordings', [ZoomController::class, 'getRecordings']);
    Route::post('check-upcoming-meetings', [ZoomController::class, 'checkUpcomingMeetings'])->middleware(AuthenticateAdmin::class);
});

// Zoom Webhook Route
Route::post('zoom/webhook', [ZoomController::class, 'handleWebhook']);

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
    Route::middleware(AuthenticateAdmin::class)->group(function () {
        Route::get('/', [App\Http\Controllers\ReportController::class, 'index']);
        Route::get('/statistics', [App\Http\Controllers\ReportController::class, 'statistics']);
        Route::get('/{report}', [App\Http\Controllers\ReportController::class, 'show']);
        Route::post('/{report}/handle', [App\Http\Controllers\ReportController::class, 'handle']);
    });
});

// System Maintenance Routes
Route::prefix('system')->middleware(AuthenticateAdmin::class)->group(function () {
    Route::post('check-preparation-phase', [DebateController::class, 'checkPreparationPhase']);
});

// Updated Rating Routes
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

Route::post('notify-test', function (FirebaseService $firebaseService) {
    $firebaseService->sendNotification(Admin::first()->fcm_token, 'Debater Application', 'You have a new debater application');
});

// Testing notification
Route::post('/send-notification', [NotificationController::class, 'sendPushNotification']);
Route::post('/admin/notification-setup', [NotificationController::class, 'adminSetup'])->middleware(AuthenticateAdmin::class);
Route::post('/notification-setup', [NotificationController::class, 'setup'])->middleware(JwtMiddleware::class);

// Zoom token test
Route::get('zoom/test', function () {
    $client = new \GuzzleHttp\Client();
    $response = $client->post('https://zoom.us/oauth/token', [
        'headers' => [
            'Authorization' => 'Basic ' . base64_encode(env('ZOOM_CLIENT_ID') . ':' . env('ZOOM_CLIENT_SECRET')),
        ],
        'form_params' => [
            'grant_type' => 'account_credentials',
            'account_id' => env('ZOOM_ACCOUNT_ID'),
        ],
    ]);
    $token = json_decode($response->getBody(), true)['access_token'];
    return $token;
});
