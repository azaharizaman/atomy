<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Backoffice API Routes
|--------------------------------------------------------------------------
|
| RESTful API endpoints for organizational structure management.
|
*/

Route::prefix('api/backoffice')->middleware(['api'])->group(function () {
    
    // Companies
    Route::apiResource('companies', 'App\Http\Controllers\CompanyController');
    Route::get('companies/{id}/subsidiaries', 'App\Http\Controllers\CompanyController@subsidiaries');
    Route::get('companies/{id}/parent-chain', 'App\Http\Controllers\CompanyController@parentChain');
    
    // Offices
    Route::apiResource('offices', 'App\Http\Controllers\OfficeController');
    Route::get('companies/{companyId}/offices', 'App\Http\Controllers\OfficeController@byCompany');
    Route::get('offices/{id}/staff', 'App\Http\Controllers\OfficeController@staff');
    
    // Departments
    Route::apiResource('departments', 'App\Http\Controllers\DepartmentController');
    Route::get('companies/{companyId}/departments', 'App\Http\Controllers\DepartmentController@byCompany');
    Route::get('departments/{id}/sub-departments', 'App\Http\Controllers\DepartmentController@subDepartments');
    Route::get('departments/{id}/staff', 'App\Http\Controllers\DepartmentController@staff');
    
    // Staff
    Route::apiResource('staff', 'App\Http\Controllers\StaffController');
    Route::post('staff/{id}/assign-department', 'App\Http\Controllers\StaffController@assignDepartment');
    Route::post('staff/{id}/assign-office', 'App\Http\Controllers\StaffController@assignOffice');
    Route::post('staff/{id}/set-supervisor', 'App\Http\Controllers\StaffController@setSupervisor');
    Route::get('staff/{id}/assignments', 'App\Http\Controllers\StaffController@assignments');
    Route::get('staff/{id}/direct-reports', 'App\Http\Controllers\StaffController@directReports');
    Route::get('staff/{id}/all-reports', 'App\Http\Controllers\StaffController@allReports');
    Route::get('staff/{id}/supervisor-chain', 'App\Http\Controllers\StaffController@supervisorChain');
    
    // Units
    Route::apiResource('units', 'App\Http\Controllers\UnitController');
    Route::post('units/{id}/add-member', 'App\Http\Controllers\UnitController@addMember');
    Route::delete('units/{id}/remove-member/{staffId}', 'App\Http\Controllers\UnitController@removeMember');
    Route::get('units/{id}/members', 'App\Http\Controllers\UnitController@members');
    
    // Transfers
    Route::apiResource('transfers', 'App\Http\Controllers\TransferController');
    Route::post('transfers/{id}/approve', 'App\Http\Controllers\TransferController@approve');
    Route::post('transfers/{id}/reject', 'App\Http\Controllers\TransferController@reject');
    Route::post('transfers/{id}/complete', 'App\Http\Controllers\TransferController@complete');
    Route::post('transfers/{id}/rollback', 'App\Http\Controllers\TransferController@rollback');
    Route::get('transfers/pending', 'App\Http\Controllers\TransferController@pending');
    Route::get('staff/{staffId}/transfers', 'App\Http\Controllers\TransferController@byStaff');
    
    // Organizational Charts
    Route::get('companies/{id}/org-chart', 'App\Http\Controllers\OrgChartController@generate');
    Route::get('departments/{id}/org-chart', 'App\Http\Controllers\OrgChartController@department');
    Route::post('org-chart/export', 'App\Http\Controllers\OrgChartController@export');
});
