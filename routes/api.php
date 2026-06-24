<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FundController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\SupplyRequestController;
use App\Http\Controllers\RISController;
use App\Http\Controllers\ProductReportController;
use App\Http\Controllers\StockCardController;
use App\Http\Controllers\AnnualReportController;
use App\Http\Controllers\SignatoryController;
use App\Http\Controllers\DivisionController;
use App\Http\Controllers\TupadAdlDetailsController;
use App\Http\Controllers\AdlBreakdownController;
use App\Http\Controllers\ProductReportAdminController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TupadAdlMasterController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\PerAdlController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\TupadCashAdvanceController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\GipEmployeeController;
use App\Http\Controllers\GipOfficeController;

/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES
|--------------------------------------------------------------------------
*/
Route::post('/login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| PUBLIC / READ-ONLY ROUTES
|--------------------------------------------------------------------------
*/
Route::get('/funds', [FundController::class, 'index']);
Route::get('/supply-requests', [SupplyRequestController::class, 'index']);
Route::get('/supply-requests/{id}/items', [SupplyRequestController::class, 'viewItems']);
Route::get('/export-ris/{id}', [RISController::class, 'exportRIS']);
Route::get('/generate-ris/{id}', [RISController::class, 'generateRIS']);
Route::get('/stock-card/{productId}', [StockCardController::class, 'generate']);
Route::get('/reports/annual/pdf/{year}', [AnnualReportController::class, 'generatePdf']);
Route::get('/reports/annual/excel/{year}', [AnnualReportController::class, 'generateExcel']);
Route::get('/product-report/{productId}/{year}', [ProductReportController::class, 'getAnnualReport']);
Route::get('/product-reports/{productId}', [ProductReportController::class, 'productReports']);
Route::get('/divisions/list', [DivisionController::class, 'list']);
Route::get('/divisions/export', [DivisionController::class, 'exportDivisions']);
Route::get('/divisions-by-year', [DivisionController::class, 'getDivisionsByYear']);
Route::get('/all-divisions-by-year', [DivisionController::class, 'getAllDivisionsByYear']);
Route::get('/divisions-with-breakdown', [DivisionController::class, 'getDivisionsWithBreakdown']);
Route::get('/division-breakdown', [DivisionController::class, 'getDivisionBreakdown']);
Route::get('/divisions', [DivisionController::class, 'index']);
Route::get('/divisions/{id}', [DivisionController::class, 'show']);

/*
|--------------------------------------------------------------------------
| PROTECTED ROUTES (AUTH:SANCTUM)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // AUTH
    Route::get('/session', [AuthController::class, 'session']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // FUNDS
    Route::post('/funds', [FundController::class, 'store']);
    Route::put('/funds/{id}', [FundController::class, 'update']);
    Route::delete('/funds/{id}', [FundController::class, 'destroy']);

    // PRODUCTS
    Route::apiResource('products', ProductController::class);
    Route::get('/product/{id}/reports', [ProductController::class, 'productReport']);

    // CHECKOUT
    Route::post('/store', [CheckoutController::class, 'store']);
    Route::get('/checkouts', [CheckoutController::class, 'index']);

    // SUPPLY REQUESTS
    Route::put('/supply-requests/{id}/status', [SupplyRequestController::class, 'updateStatus']);
    Route::put('/checkout-item/{id}/update', [SupplyRequestController::class, 'updateItemStatus']);
    Route::put('/checkout/{id}/approve-by', [SupplyRequestController::class, 'saveApprovedBy']);
    Route::put('/checkout/{id}/issue-by', [SupplyRequestController::class, 'saveIssuedBy']);

    // USER PROFILES
    Route::get('/profiles/by-division/{division?}', [UserProfileController::class, 'getProfilesByDivision']);
    Route::get('/user/division/{division?}', [UserProfileController::class, 'getProfilesByDivision']);
    Route::get('/profile', [UserProfileController::class, 'showProfile']);
    Route::post('/profile', [UserProfileController::class, 'updateProfile']);
    Route::get('/users/signatories', [UserProfileController::class, 'signatoryList']);

    // SIGNATORIES
    Route::get('/signatories', [SignatoryController::class, 'index']);
    Route::put('/signatories/{id}', [SignatoryController::class, 'update']);

    // EMPLOYEES
    Route::get('/employees', [EmployeeController::class, 'index']);
    Route::post('/employees', [EmployeeController::class, 'store']);
    Route::put('/employees/{user_id}', [EmployeeController::class, 'update']);
    Route::delete('/employees/{user_id}', [EmployeeController::class, 'destroy']);

    // POSITIONS
    Route::get('/positions', [PositionController::class, 'index']);
    Route::post('/positions', [PositionController::class, 'store']);
    Route::put('/positions/{id}', [PositionController::class, 'update']);
    Route::delete('/positions/{id}', [PositionController::class, 'destroy']);

    // DASHBOARD
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // TUPAD
    Route::apiResource('tupad_adl_masters', TupadAdlMasterController::class)->only(['index','store','update','destroy']);
    Route::apiResource('tupad_adl_details', TupadAdlDetailsController::class)->only(['index','store','show','update','destroy']);
    Route::get('/per-adl-breakdown', [PerAdlController::class, 'breakdown']);
    Route::apiResource('tupad_adl_breakdown', AdlBreakdownController::class);
    Route::put('/tupad_adl_breakdown/{id}/mark-as-received', [AdlBreakdownController::class, 'markAsReceived']);
    Route::post('/tupad_adl_breakdown/{id}/import-demographics', [AdlBreakdownController::class, 'importDemographics']);
    Route::get('/tupad_adl_breakdown/{id}/beneficiaries', [AdlBreakdownController::class, 'beneficiaries']);
    Route::patch('/tupad_bens_status/{id}', [AdlBreakdownController::class, 'updateBeneficiaryStatus']);

    // REPORTS
    Route::post('/reports/generate-monthly', [ProductReportAdminController::class, 'generateMonthly']);
    Route::get('/tupad/reports/workbook', [PerAdlController::class, 'workbook']);
    Route::get('/tupad/reports/per-adl', [PerAdlController::class, 'perAdl']);
    Route::get('/tupad/reports/lgu-per-adl', [PerAdlController::class, 'lguPerAdl']);
    Route::get('/tupad/reports/all-adl', [PerAdlController::class, 'allAdl']);

    // CALENDAR
    Route::get('/calendar/divisions', [CalendarController::class, 'divisions']);
    Route::get('/calendar/employees', [CalendarController::class, 'employees']);
    Route::get('/calendar/feed', [CalendarController::class, 'feed']);
    Route::get('/calendar/upcoming', [CalendarController::class, 'upcoming']);
    Route::post('/calendar/events', [CalendarController::class, 'storeEvent']);

    // CASH ADVANCES
    Route::prefix('cash-advances')->group(function () {
        Route::get('/employees', [TupadCashAdvanceController::class, 'employees']);
        Route::get('/adls', [TupadCashAdvanceController::class, 'adls']);
        Route::get('/breakdown', [TupadCashAdvanceController::class, 'breakdown']);
        Route::get('/breakdown/all', [TupadCashAdvanceController::class, 'allBreakdowns']);
        Route::get('/', [TupadCashAdvanceController::class, 'index']);
        Route::post('/', [TupadCashAdvanceController::class, 'store']);
        Route::get('/{id}', [TupadCashAdvanceController::class, 'show']);
        Route::put('/{id}', [TupadCashAdvanceController::class, 'update']);
        Route::delete('/{id}', [TupadCashAdvanceController::class, 'destroy']);
        Route::get('/{id}/export', [TupadCashAdvanceController::class, 'export']);
        Route::get('/{id}/export-excel', [TupadCashAdvanceController::class, 'exportExcel']);
        Route::get('/{id}/voucher', [TupadCashAdvanceController::class, 'voucher']);
    });

    // ===== DOCUMENT TRACKING SYSTEM (NEW) =====
    // Document CRUD
    Route::get('/documents', [DocumentController::class, 'index']);
    Route::post('/documents', [DocumentController::class, 'store']);
    Route::put('/documents/{id}', [DocumentController::class, 'update']);
    Route::delete('/documents/{id}', [DocumentController::class, 'destroy']);

    // Workflow
    Route::post('/documents/{id}/submit', [DocumentController::class, 'submit']);
    Route::post('/documents/{id}/receive', [DocumentController::class, 'receive']);

    // Document types (existing GET + new POST/PUT)
    Route::get('/document-types', [DocumentController::class, 'types']);
    Route::post('/document-types', [DocumentController::class, 'storeType']);
    Route::put('/document-types/{id}', [DocumentController::class, 'updateType']);

    // Users & current user
    Route::get('/users', [DocumentController::class, 'users']);
    Route::get('/me', [DocumentController::class, 'me']);
    Route::post('/documents/{id}/complete', [DocumentController::class, 'complete']);


// GIP OFFICE ASSIGNMENT
Route::get('/gip/offices', [GipOfficeController::class, 'index']);
Route::get('/gip/offices/available-years', [GipOfficeController::class, 'availableYears']);
Route::get('/gip/unassigned', [GipOfficeController::class, 'unassignedGip']);
Route::post('/gip/assign', [GipOfficeController::class, 'assign']);
Route::post('/gip/unassign', [GipOfficeController::class, 'unassign']);
Route::get('/gip/offices/{officeId}/gips', [GipOfficeController::class, 'officeGips']);

// Office CRUD
Route::post('/offices', [GipOfficeController::class, 'storeOffice']);
Route::put('/offices/{id}', [GipOfficeController::class, 'updateOffice']);
Route::delete('/offices/{id}', [GipOfficeController::class, 'destroyOffice']);

// GIP EMPLOYEES (full)
Route::prefix('gip')->group(function () {
    Route::get('/employees/years', [GipEmployeeController::class, 'availableYears']);
    Route::get('/employees/stats', [GipEmployeeController::class, 'stats']);
    Route::get('/employees', [GipEmployeeController::class, 'index']);
    Route::post('/employees/check-duplicate', [GipEmployeeController::class, 'checkDuplicate']);
    Route::post('/employees', [GipEmployeeController::class, 'store']);
    Route::get('/employees/{id}', [GipEmployeeController::class, 'show']);
    Route::put('/employees/{id}', [GipEmployeeController::class, 'update']);
    Route::delete('/employees/{id}', [GipEmployeeController::class, 'destroy']);
    Route::post('/employees/{id}/renew', [GipEmployeeController::class, 'renew']);
    Route::post('/employees/{id}/resign', [GipEmployeeController::class, 'resign']);
    Route::get('/offices', [GipEmployeeController::class, 'offices']);
});

Route::get('/gip/offices/stats', [GipOfficeController::class, 'stats']);
});