<?php

use App\Domain\Board\Controllers\BoardController;
use App\Domain\Board\Controllers\BoardListController;
use App\Domain\Board\Controllers\CardController;
use App\Domain\Board\Controllers\CardMemberController;
use App\Domain\Board\Controllers\LabelController;
use App\Domain\Board\Controllers\CardLabelController;
use App\Domain\Board\Controllers\ChecklistController;
use App\Domain\Board\Controllers\ChecklistItemController;
use App\Domain\Board\Controllers\CardAttachmentController;
use App\Domain\Board\Controllers\CardCommentController;
use App\Domain\Board\Controllers\CardActivityController;
use App\Domain\Board\Controllers\CustomFieldController;
use App\Domain\Board\Controllers\BoardAutomationController;
use App\Domain\Board\Controllers\BoardTemplateController;
use Illuminate\Support\Facades\Route;

Route::middleware(['jwt', 'workspace'])->group(function () {

    // Boards
    Route::get('boards', [BoardController::class, 'index']);
    Route::post('boards', [BoardController::class, 'store']);
    Route::get('boards/{board}', [BoardController::class, 'show']);
    Route::get('boards/{board}/archived', [BoardController::class, 'archived']);
    Route::put('boards/{board}', [BoardController::class, 'update']);
    Route::delete('boards/{board}', [BoardController::class, 'destroy'])
        ->middleware('workspace.role:owner,admin');

    // Board Lists
    Route::post('boards/{board}/lists', [BoardListController::class, 'store']);
    Route::put('boards/{board}/lists/{list}', [BoardListController::class, 'update']);
    Route::post('boards/{board}/lists/reorder', [BoardListController::class, 'reorder']);
    Route::delete('boards/{board}/lists/{list}', [BoardListController::class, 'destroy']);

    // Cards
    Route::post('boards/{board}/lists/{list}/cards', [CardController::class, 'store']);
    Route::post('boards/{board}/cards/reorder', [CardController::class, 'reorder']);
    Route::get('cards/{card}', [CardController::class, 'show']);
    Route::put('cards/{card}', [CardController::class, 'update']);
    Route::patch('cards/{card}/move', [CardController::class, 'move']);
    Route::patch('cards/{card}/archive', [CardController::class, 'archive']);
    Route::patch('cards/{card}/restore', [CardController::class, 'restore']);
    Route::post('cards/{card}/subcards', [CardController::class, 'storeSubCard']);
    Route::post('cards/{card}/duplicate', [CardController::class, 'duplicate']);
    Route::delete('cards/{card}', [CardController::class, 'destroy']);

    // Card Members
    Route::post('cards/{card}/members', [CardMemberController::class, 'store']);
    Route::delete('cards/{card}/members/{user}', [CardMemberController::class, 'destroy']);

    // Labels
    Route::get('boards/{board}/labels', [LabelController::class, 'index']);
    Route::post('boards/{board}/labels', [LabelController::class, 'store']);
    Route::put('boards/{board}/labels/{label}', [LabelController::class, 'update']);
    Route::delete('boards/{board}/labels/{label}', [LabelController::class, 'destroy']);

    // Card Labels
    Route::post('cards/{card}/labels', [CardLabelController::class, 'store']);
    Route::delete('cards/{card}/labels/{label}', [CardLabelController::class, 'destroy']);

    // Checklists
    Route::post('cards/{card}/checklists', [ChecklistController::class, 'store']);
    Route::put('checklists/{checklist}', [ChecklistController::class, 'update']);
    Route::delete('checklists/{checklist}', [ChecklistController::class, 'destroy']);

    // Checklist Items
    Route::post('checklists/{checklist}/items', [ChecklistItemController::class, 'store']);
    Route::put('checklist-items/{item}', [ChecklistItemController::class, 'update']);
    Route::patch('checklist-items/{item}/toggle', [ChecklistItemController::class, 'toggle']);
    Route::delete('checklist-items/{item}', [ChecklistItemController::class, 'destroy']);

    // Attachments
    Route::post('cards/{card}/attachments', [CardAttachmentController::class, 'store']);
    Route::patch('attachments/{attachment}/cover', [CardAttachmentController::class, 'setCover']);
    Route::delete('attachments/{attachment}', [CardAttachmentController::class, 'destroy']);

    // Comments
    Route::get('cards/{card}/comments', [CardCommentController::class, 'index']);
    Route::post('cards/{card}/comments', [CardCommentController::class, 'store'])
        ->middleware('throttle:chat-message');
    Route::put('comments/{comment}', [CardCommentController::class, 'update']);
    Route::delete('comments/{comment}', [CardCommentController::class, 'destroy']);

    // Activities
    Route::get('cards/{card}/activities', [CardActivityController::class, 'index']);

    // Custom Fields
    Route::get('boards/{board}/custom-fields', [CustomFieldController::class, 'index']);
    Route::post('boards/{board}/custom-fields', [CustomFieldController::class, 'store']);
    Route::put('boards/{board}/custom-fields/{customField}', [CustomFieldController::class, 'update']);
    Route::delete('boards/{board}/custom-fields/{customField}', [CustomFieldController::class, 'destroy']);
    Route::get('cards/{card}/custom-field-values', [CustomFieldController::class, 'cardValues']);
    Route::put('cards/{card}/custom-field-values/{customField}', [CustomFieldController::class, 'updateValue']);

    // Automations
    Route::get('boards/{board}/automations', [BoardAutomationController::class, 'index']);
    Route::get('boards/{board}/automations/presets', [BoardAutomationController::class, 'presets']);
    Route::post('boards/{board}/automations', [BoardAutomationController::class, 'store']);
    Route::put('automations/{automation}', [BoardAutomationController::class, 'update']);
    Route::patch('automations/{automation}/toggle', [BoardAutomationController::class, 'toggle']);
    Route::delete('automations/{automation}', [BoardAutomationController::class, 'destroy']);

    // Templates
    Route::get('templates', [BoardTemplateController::class, 'index']);
    Route::post('templates', [BoardTemplateController::class, 'store']);
    Route::put('templates/{template}', [BoardTemplateController::class, 'update']);
    Route::delete('templates/{template}', [BoardTemplateController::class, 'destroy']);
    Route::post('boards/{board}/save-as-template', [BoardTemplateController::class, 'createFromBoard']);
    Route::post('templates/{template}/apply/{board}', [BoardTemplateController::class, 'applyToBoard']);
});
