<?php

use App\Http\Controllers\AnswerController;
use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\AttemptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminBackupController;
use App\Http\Controllers\PresentationController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\UserController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:web')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('api')->prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::get('me', [AuthController::class, 'me'])->middleware('auth:web');
    Route::post('password/email', PasswordResetLinkController::class)->name('password.email');
    Route::post('password/reset', NewPasswordController::class);
});

Route::middleware('api')->group(function () {
    Route::apiResource('assessments', AssessmentController::class)->middleware('auth:web');
    Route::put('assessments/{assessment}/bulk', [AssessmentController::class, 'bulkUpdate'])->middleware('auth:web');
    Route::apiResource('questions', QuestionController::class)->middleware('auth:web');
    Route::apiResource('answers', AnswerController::class)->middleware('auth:web');
    // Attempts are created by unauthenticated participants via the client app.
    Route::apiResource('attempts', AttemptController::class)
        ->only(['store'])
        ->middleware('throttle:attempts');
    Route::apiResource('attempts', AttemptController::class)
        ->only(['index', 'show', 'update', 'destroy'])
        ->middleware('auth:web');
});

Route::middleware(['api', 'auth:web'])->prefix('user-management')->group(function () {
    Route::get('users', [UserController::class, 'index']);
    Route::get('users/{user}', [UserController::class, 'show']);
    Route::patch('users/{user}', [UserController::class, 'update']);
    Route::delete('users/{user}', [UserController::class, 'destroy']);
    Route::post('/users/register-user', [UserController::class, 'registerUser']);
    Route::post('users/admin-change-password/', [UserController::class, 'adminChangePassword'])->name('user.adminchangepassword');
});

Route::middleware(['api', 'auth:web'])->group(function () {
    Route::post('/questions/promote', [QuestionController::class, 'promote'])->name('questions.promote');
    Route::post('/questions/demote', [QuestionController::class, 'demote'])->name('questions.demote');
    Route::post('/questions/upload', [QuestionController::class, 'storeUpload'])->name('storeUpload');
    Route::post('/answers/promote', [AnswerController::class, 'promote'])->name('answers.promote');
    Route::post('/answers/demote', [AnswerController::class, 'demote'])->name('answers.demote');
    Route::get('/list-assessments-by-user/{user_id}', [AssessmentController::class, 'listAssessmentsByUser']);
});

Route::middleware('api')->group(function () {
    // Public presentation endpoints used by external participants.
    Route::get('/presentations/store/{password}/{user_id}', [PresentationController::class, 'store'])
        ->middleware('throttle:presentations')
        ->name('presentations.store');
    Route::get('/presentations/getAssessment/{presentation_id}', [PresentationController::class, 'getAssessment'])->name('presentations.getassessment');
    Route::get('/presentations/score-by-credentials/{password}/{user_id}', [PresentationController::class, 'scoreByCredentials'])->name('presentations.scorebycredentials');

    // Public info banner used on login/home to detect seeded admin/demo users and Mailpit
    Route::get('/demo-warning', function () {
        // Default seeded password hash for admin@example.com / admin
        $defaultAdminHash = '$2y$12$Lar5T5y8docuOFsdx98FRevUlRMZRP/40zpowaLJHz2ZtN9b/pww2';
        $hasDefaultAdmin = User::where('email', 'admin@example.com')
            ->where('password', $defaultAdminHash)
            ->exists();

        $hasDemoUsers = User::whereIn('email', ['demo1@example.com', 'demo2@example.com'])->exists();

        $mailConfig = config('mail');
        $defaultMailer = $mailConfig['default'] ?? null;
        $mailerConfig = $defaultMailer ? ($mailConfig['mailers'][$defaultMailer] ?? []) : [];
        $transport = $mailerConfig['transport'] ?? $defaultMailer;
        $smtpConfig = $mailConfig['mailers']['smtp'] ?? [];
        $smtpHost = $smtpConfig['host'] ?? '';
        $smtpPort = $smtpConfig['port'] ?? '';
        $showMailpit = $defaultMailer === 'smtp'
            && (strtolower($smtpHost) === 'mailpit' || (in_array($smtpHost, ['localhost', '127.0.0.1']) && (int) $smtpPort === 1025));
        $mailEnabledFlag = (bool) config('mail.enabled', true);
        $mailConfigured = $mailEnabledFlag
            && (bool) $defaultMailer
            && $transport !== 'log'
            && ($transport !== 'smtp' || ! empty($mailerConfig['host']));

        return response()->json([
            'showWarning' => $hasDefaultAdmin,
            'showDemoUsers' => $hasDemoUsers,
            'showMailpit' => $showMailpit,
            'mailConfigured' => $mailConfigured,
            'mailEnabled' => $mailEnabledFlag,
        ]);
    });
});

Route::middleware(['api', 'auth:web'])->group(function () {
    Route::get('/shortlink-providers', function () {
        return response()->json([
            'bitly' => (bool) config('bitly.accesstoken'),
            'tinyurl' => (bool) config('services.tinyurl.token'),
        ]);
    });
    Route::get('/presentations/completed', [PresentationController::class, 'completed'])->name('presentations.completed');
    Route::get('/assessment/attempts/{assessment_id}', [AssessmentController::class, 'assessmentAttempts'])->name('assessment.attempts');
    Route::get('/assessments/{assessment}/edit', [AssessmentController::class, 'edit']);
    Route::get('/presentations/score-by-assessment-id/{assessment_id}', [PresentationController::class, 'scoreByAssessmentId'])->name('presentations.scorebyassessmentid');
    Route::post('/change-password/', [UserController::class, 'changePassword'])->name('user.changepassword');
    Route::get('/admin/backup/download', [AdminBackupController::class, 'download'])->name('admin.backup.download');
});
