<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Workflow API Routes
|--------------------------------------------------------------------------
|
| RESTful API endpoints for workflow management.
|
*/

Route::prefix('workflows')->group(function () {
    // Workflow Definitions
    Route::get('definitions', 'WorkflowDefinitionController@index');
    Route::post('definitions', 'WorkflowDefinitionController@store');
    Route::get('definitions/{id}', 'WorkflowDefinitionController@show');
    Route::put('definitions/{id}', 'WorkflowDefinitionController@update');
    Route::delete('definitions/{id}', 'WorkflowDefinitionController@destroy');

    // Workflow Instances
    Route::get('instances', 'WorkflowInstanceController@index');
    Route::post('instances', 'WorkflowInstanceController@store');
    Route::get('instances/{id}', 'WorkflowInstanceController@show');
    Route::post('instances/{id}/transitions', 'WorkflowInstanceController@applyTransition');
    Route::get('instances/{id}/history', 'WorkflowInstanceController@history');
    Route::post('instances/{id}/lock', 'WorkflowInstanceController@lock');
    Route::post('instances/{id}/unlock', 'WorkflowInstanceController@unlock');

    // Tasks
    Route::get('tasks', 'WorkflowTaskController@index');
    Route::get('tasks/inbox', 'WorkflowTaskController@inbox');
    Route::get('tasks/overdue', 'WorkflowTaskController@overdue');
    Route::get('tasks/{id}', 'WorkflowTaskController@show');
    Route::post('tasks/{id}/complete', 'WorkflowTaskController@complete');
    Route::post('tasks/{id}/delegate', 'WorkflowTaskController@delegate');

    // Delegations
    Route::get('delegations', 'WorkflowDelegationController@index');
    Route::post('delegations', 'WorkflowDelegationController@store');
    Route::delete('delegations/{id}', 'WorkflowDelegationController@destroy');

    // SLA & Monitoring
    Route::get('sla/breaches', 'WorkflowSlaController@breaches');
    Route::get('instances/{id}/sla', 'WorkflowSlaController@status');

    // Approval Matrices
    Route::get('approval-matrices', 'ApprovalMatrixController@index');
    Route::post('approval-matrices', 'ApprovalMatrixController@store');
    Route::put('approval-matrices/{id}', 'ApprovalMatrixController@update');
    Route::delete('approval-matrices/{id}', 'ApprovalMatrixController@destroy');
});
