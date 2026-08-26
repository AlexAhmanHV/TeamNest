<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\ApiTokenController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\TaskAttachmentController;
use App\Http\Controllers\TaskBulkActionController;
use App\Http\Controllers\TaskCommentController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskSavedViewController;
use App\Http\Controllers\WorkspaceController;
use App\Http\Controllers\WorkspaceSettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/invites/{token}/accept', [InvitationController::class, 'acceptShow'])->name('invites.accept.show');
Route::post('/invites/{token}/accept', [InvitationController::class, 'accept'])->middleware('auth')->name('invites.accept');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/workspaces', [WorkspaceController::class, 'index'])->name('workspaces.index');
    Route::post('/workspaces', [WorkspaceController::class, 'store'])->name('workspaces.store');
    Route::post('/workspaces/switch', [WorkspaceController::class, 'switch'])->name('workspaces.switch');

    Route::middleware('workspace')->group(function () {
        Route::get('/members', [MemberController::class, 'index'])->name('members.index');
        Route::patch('/members/{user}/role', [MemberController::class, 'updateRole'])->name('members.role.update');
        Route::delete('/members/{user}', [MemberController::class, 'destroy'])->name('members.destroy');
        Route::get('/search', [SearchController::class, 'search'])->name('search');

        Route::post('/invites', [InvitationController::class, 'store'])->name('invites.store');
        Route::post('/invites/{invitation}/resend', [InvitationController::class, 'resend'])->name('invites.resend');
        Route::delete('/invites/{invitation}', [InvitationController::class, 'revoke'])->name('invites.revoke');

        Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
        Route::get('/projects/trash', [ProjectController::class, 'trash'])->name('projects.trash');
        Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
        Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
        Route::patch('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
        Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');
        Route::post('/projects/{project}/restore', [ProjectController::class, 'restore'])->name('projects.restore');
        Route::delete('/projects/{project}/force', [ProjectController::class, 'forceDelete'])->name('projects.forceDelete');

        Route::get('/projects/{project}/tasks', [TaskController::class, 'index'])->name('tasks.index');
        Route::get('/projects/{project}/tasks/trash', [TaskController::class, 'trash'])->name('tasks.trash');
        Route::post('/projects/{project}/tasks', [TaskController::class, 'store'])->name('tasks.store');
        Route::post('/projects/{project}/tasks/bulk', [TaskBulkActionController::class, 'store'])->name('tasks.bulk');
        Route::post('/projects/{project}/task-views', [TaskSavedViewController::class, 'store'])->name('taskViews.store');

        Route::get('/tasks/{task}', [TaskController::class, 'show'])->name('tasks.show');
        Route::patch('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
        Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
        Route::post('/tasks/{task}/restore', [TaskController::class, 'restore'])->name('tasks.restore');
        Route::delete('/tasks/{task}/force', [TaskController::class, 'forceDelete'])->name('tasks.forceDelete');
        Route::post('/tasks/{task}/complete', [TaskController::class, 'complete'])->name('tasks.complete');
        Route::post('/tasks/{task}/assign', [TaskController::class, 'assign'])->name('tasks.assign');
        Route::post('/tasks/{task}/move', [TaskController::class, 'move'])->name('tasks.move');
        Route::post('/tasks/{task}/comments', [TaskCommentController::class, 'store'])->name('tasks.comments.store');
        Route::post('/tasks/{task}/attachments', [TaskAttachmentController::class, 'store'])->name('tasks.attachments.store');
        Route::delete('/task-views/{savedView}', [TaskSavedViewController::class, 'destroy'])->name('taskViews.destroy');
        Route::get('/attachments/{attachment}/download', [TaskAttachmentController::class, 'download'])->name('attachments.download');
        Route::delete('/attachments/{attachment}', [TaskAttachmentController::class, 'destroy'])->name('attachments.destroy');

        Route::get('/activity', [ActivityController::class, 'index'])->name('activity.index');
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.readAll');
        Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
        Route::get('/workspace/settings', [WorkspaceSettingsController::class, 'edit'])->name('workspaces.settings.edit');
        Route::patch('/workspace/settings', [WorkspaceSettingsController::class, 'update'])->name('workspaces.settings.update');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/api-tokens', [ApiTokenController::class, 'index'])->name('tokens.index');
    Route::post('/api-tokens', [ApiTokenController::class, 'store'])->name('tokens.store');
    Route::delete('/api-tokens/{tokenId}', [ApiTokenController::class, 'destroy'])->name('tokens.destroy');
});

require __DIR__.'/auth.php';
