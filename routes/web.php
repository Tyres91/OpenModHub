<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\EmailTemplateController;
use App\Http\Controllers\Admin\FaqController as AdminFaqController;
use App\Http\Controllers\Admin\ModerationController;
use App\Http\Controllers\Admin\RankController;
use App\Http\Controllers\Admin\RankPointRuleController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ModController;
use App\Http\Controllers\ModVersionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ModController::class, 'index'])->name('home');
Route::get('/mods', [ModController::class, 'index'])->name('mods.index');
Route::get('/faqs', [FaqController::class, 'index'])->name('faqs.index');
Route::get('/impressum', [LegalController::class, 'imprint'])->name('legal.imprint');
Route::get('/datenschutz', [LegalController::class, 'privacy'])->name('legal.privacy');

Route::get('/dashboard', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::patch('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::patch('/users/{user}/block', [UserController::class, 'block'])->name('users.block');
        Route::patch('/users/{user}/unblock', [UserController::class, 'unblock'])->name('users.unblock');

        Route::get('/moderation', [ModerationController::class, 'index'])->name('moderation.index');
        Route::patch('/moderation/{mod:slug}/approve', [ModerationController::class, 'approve'])->name('moderation.approve');
        Route::patch('/moderation/{mod:slug}/reject', [ModerationController::class, 'reject'])->name('moderation.reject');
        Route::delete('/moderation/{mod:slug}/delete', [ModerationController::class, 'destroy'])->name('moderation.delete');
        Route::delete('/moderation/{mod:slug}/force-delete', [ModerationController::class, 'forceDestroy'])->name('moderation.force-delete');
        Route::patch('/moderation/versions/{modVersion}/approve', [ModerationController::class, 'approveVersion'])->name('moderation.versions.approve');
        Route::patch('/moderation/versions/{modVersion}/reject', [ModerationController::class, 'rejectVersion'])->name('moderation.versions.reject');

        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::patch('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

        Route::get('/faqs', [AdminFaqController::class, 'index'])->name('faqs.index');
        Route::post('/faqs', [AdminFaqController::class, 'store'])->name('faqs.store');
        Route::patch('/faqs/reorder', [AdminFaqController::class, 'reorder'])->name('faqs.reorder');
        Route::patch('/faqs/{faq}', [AdminFaqController::class, 'update'])->name('faqs.update');
        Route::delete('/faqs/{faq}', [AdminFaqController::class, 'destroy'])->name('faqs.destroy');

        Route::get('/ranks', [RankController::class, 'index'])->name('ranks.index');
        Route::post('/ranks', [RankController::class, 'store'])->name('ranks.store');
        Route::patch('/ranks/{rank}', [RankController::class, 'update'])->name('ranks.update');
        Route::delete('/ranks/{rank}', [RankController::class, 'destroy'])->name('ranks.destroy');

        Route::get('/rank-point-rules', [RankPointRuleController::class, 'index'])->name('rank-point-rules.index');
        Route::patch('/rank-point-rules', [RankPointRuleController::class, 'update'])->name('rank-point-rules.update');

        Route::get('/reports', [AdminReportController::class, 'index'])->name('reports.index');
        Route::patch('/reports/{report}/resolve', [AdminReportController::class, 'resolve'])->name('reports.resolve');
        Route::patch('/reports/{report}/dismiss', [AdminReportController::class, 'dismiss'])->name('reports.dismiss');

        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::patch('/settings', [SettingsController::class, 'update'])->name('settings.update');
        Route::post('/settings/logo', [SettingsController::class, 'updateLogo'])->name('settings.logo.update');
        Route::delete('/settings/logo', [SettingsController::class, 'destroyLogo'])->name('settings.logo.destroy');

        Route::get('/email-templates', [EmailTemplateController::class, 'index'])->name('email-templates.index');
        Route::patch('/email-templates/{emailTemplate}', [EmailTemplateController::class, 'update'])->name('email-templates.update');
    });

    Route::middleware('verified')->group(function () {
        Route::get('/my-mods', [ModController::class, 'mine'])->name('mods.mine');
        Route::get('/mods/create', [ModController::class, 'create'])->name('mods.create');
        Route::post('/mods', [ModController::class, 'store'])->name('mods.store');
        Route::get('/mods/{mod:slug}/versions/create', [ModVersionController::class, 'create'])->name('mods.versions.create');
        Route::post('/mods/{mod:slug}/versions', [ModVersionController::class, 'store'])->name('mods.versions.store');
        Route::post('/mods/{mod:slug}/ratings', [RatingController::class, 'store'])->name('mods.ratings.store');
        Route::post('/mods/{mod:slug}/comments', [CommentController::class, 'store'])->name('mods.comments.store');
        Route::post('/mods/{mod:slug}/reports', [ReportController::class, 'store'])->name('mods.reports.store');
    });
    Route::patch('/comments/{comment}/hide', [CommentController::class, 'hide'])->name('comments.hide');
    Route::patch('/comments/{comment}/show', [CommentController::class, 'show'])->name('comments.show');
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::post('/locale', [LocaleController::class, 'update'])->name('locale.update');

Route::get('/users/{user}', [UserProfileController::class, 'show'])->name('users.show');
Route::get('/mods/{mod:slug}/versions/{modVersion}/download', [ModVersionController::class, 'download'])->name('mods.versions.download');
Route::get('/mods/{mod:slug}/download', [ModController::class, 'download'])->name('mods.download');
Route::get('/mods/{mod:slug}', [ModController::class, 'show'])->name('mods.show');

require __DIR__.'/auth.php';
