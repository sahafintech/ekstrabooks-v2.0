<?php

use App\Http\Controllers\AccountTypeController;
use App\Http\Controllers\AIChatController;
use App\Http\Controllers\AttendanceLogController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\BillPaymentsController;
use App\Http\Controllers\BrandsController;
use App\Http\Controllers\BusinessTypeController;
use App\Http\Controllers\ChangeOrderController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DefferedInvoiceController;
use App\Http\Controllers\DefferedPaymentController;
use App\Http\Controllers\EmailSubscriberController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\FeatureController;
use App\Http\Controllers\HoldPosInvoiceController;
use App\Http\Controllers\User\InventoryAdjustmentController;
use App\Http\Controllers\InsuranceBenefitController;
use App\Http\Controllers\InsuranceFamilySizeController;
use App\Http\Controllers\JournalController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\MainCategoryController;
use App\Http\Controllers\MedicalRecordController;
use App\Http\Controllers\MembershipController;
use App\Http\Controllers\NotificationTemplateController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PaymentGatewayController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\PrescriptionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectGroupController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\PurchaseReturnController;
use App\Http\Controllers\ReceivePaymentsController;
use App\Http\Controllers\ServerErrorController;
use App\Http\Controllers\SubCategoryController;
use App\Http\Controllers\SubscriptionPaymentController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TestimonialController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\User\AccountController;
use App\Http\Controllers\User\AwardController;
use App\Http\Controllers\User\BusinessController;
use App\Http\Controllers\User\BusinessSettingsController;
use App\Http\Controllers\User\CashPurchaseController;
use App\Http\Controllers\CostCodeController;
use App\Http\Controllers\ProjectBudgetController;
use App\Http\Controllers\ProjectSubcontractController;
use App\Http\Controllers\ProjectSubcontractPaymentController;
use App\Http\Controllers\ProjectTaskController;
use App\Http\Controllers\SalaryAdvanceController;
use App\Http\Controllers\TimesheetController;
use App\Http\Controllers\User\CustomerController;
use App\Http\Controllers\User\CustomerDocumentController;
use App\Http\Controllers\User\DepartmentController;
use App\Http\Controllers\User\DesignationController;
use App\Http\Controllers\User\HolidayController;
use App\Http\Controllers\User\InvoiceController;
use App\Http\Controllers\User\InvoiceTemplateController;
use App\Http\Controllers\User\LeaveController;
use App\Http\Controllers\User\LeaveTypeController;
use App\Http\Controllers\User\OnlinePaymentController;
use App\Http\Controllers\User\PayrollController;
use App\Http\Controllers\User\PermissionController;
use App\Http\Controllers\User\ProductController;
use App\Http\Controllers\User\ProductUnitController;
use App\Http\Controllers\User\PurchaseController;
use App\Http\Controllers\User\QuotationController;
use App\Http\Controllers\User\ReceiptController;
use App\Http\Controllers\User\RecurringInvoiceController;
use App\Http\Controllers\User\ReportController;
use App\Http\Controllers\User\RoleController;
use App\Http\Controllers\User\SalesReturnController;
use App\Http\Controllers\User\StaffController;
use App\Http\Controllers\User\StaffDocumentController;
use App\Http\Controllers\User\SystemUserController;
use App\Http\Controllers\User\TaxController;
use App\Http\Controllers\User\TransactionController;
use App\Http\Controllers\User\TransactionMethodController;
use App\Http\Controllers\User\VendorController;
use App\Http\Controllers\User\VendorDocumentController;
use App\Http\Controllers\UserPackageController;
use App\Http\Controllers\UtilityController;
use App\Http\Controllers\Website\WebsiteController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
 */

$ev = get_option('email_verification', 0);

Auth::routes(['verify' => $ev == 1 ? true : false]);
// Route::get('/logout', 'Auth\AuthenticatedSessionController@destory');

$initialMiddleware = ['auth', 'saas'];
if ($ev == 1) {
	array_push($initialMiddleware, 'verified');
}

Route::get('server-error', [ServerErrorController::class, 'index'])->name('500.server-error');

Route::group(['middleware' => $initialMiddleware], function () {

	Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
	Route::post('dashboard', [DashboardController::class, 'index'])->name('dashboard.filter');
	Route::get('dashboard/inventory', [DashboardController::class, 'inventory'])->name('dashboard.inventory');

	//Profile Controller
	Route::get('profile', [ProfileController::class, 'index'])->name('profile.index');
	Route::get('profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
	Route::post('profile/update', [ProfileController::class, 'update'])->name('profile.update')->middleware('demo');
	Route::get('profile/change_password', [ProfileController::class, 'change_password'])->name('profile.change_password');
	Route::post('profile/update_password', [ProfileController::class, 'update_password'])->name('profile.update_password')->middleware('demo');
	Route::get('profile/notification_mark_as_read/{id}', [ProfileController::class, 'notification_mark_as_read'])->name('profile.notification_mark_as_read');
	Route::get('profile/show_notification/{id}', [ProfileController::class, 'show_notification'])->name('profile.show_notification');
	Route::get('membership/active_subscription', [MembershipController::class, 'index'])->name('membership.index');

	/** Admin Only Route **/
	Route::group(['middleware' => ['admin'], 'prefix' => 'admin'], function () {

		//User Management
		Route::get('users/{id}/login_as_user', [UserController::class, 'login_as_user'])->name('users.login_as_user');
		Route::resource('users', UserController::class)->middleware("demo:PUT|PATCH|DELETE");

		//Subscription Payments
		Route::resource('subscription_payments', SubscriptionPaymentController::class)->middleware("demo:PUT|PATCH|DELETE");

		Route::group(['middleware' => 'demo'], function () {
			//Package Controller
			Route::resource('packages', PackageController::class);

			// user packages
			Route::resource('user_packages', UserPackageController::class);
			Route::post('user_packages/bulk_destroy', [UserPackageController::class, 'bulkDestroy'])->name('user_packages.bulk_destroy');

			//Payment Gateways
			Route::resource('payment_gateways', PaymentGatewayController::class)->except([
				'create',
				'store',
				'show',
				'destroy',
			]);

			//Email Subscribers
			Route::match(['get', 'post'], 'email_subscribers/send_email', [EmailSubscriberController::class, 'send_email'])->name('email_subscribers.send_email');
			Route::get('email_subscribers/export', [EmailSubscriberController::class, 'export'])->name('email_subscribers.export');
			Route::get('email_subscribers', [EmailSubscriberController::class, 'index'])->name('email_subscribers.index');
			Route::delete('email_subscribers/{id}/destroy', [EmailSubscriberController::class, 'destroy'])->name('email_subscribers.destroy');

			//Page Controller
			Route::post('pages/store_default_pages/{slug?}', [PageController::class, 'store_default_pages'])->name('pages.default_pages.store');
			Route::get('pages/default_pages/{slug?}', [PageController::class, 'default_pages'])->name('pages.default_pages');
			Route::resource('pages', PageController::class)->except('show');

			//FAQ Controller
			Route::resource('faqs', FaqController::class)->except('show');

			//Features Controller
			Route::resource('features', FeatureController::class)->except('show');

			//Testimonial Controller
			Route::resource('testimonials', TestimonialController::class)->except('show');

			//Team Controller
			Route::resource('posts', PostController::class)->except('show');

			//Team Controller
			Route::resource('teams', TeamController::class)->except('show');

			//Business Type Controller
			Route::resource('business_types', BusinessTypeController::class)->except('show');

			// account types
			Route::resource('account_types', AccountTypeController::class)->except('show');

			//Language Controller
			Route::get('languages/{lang}/edit_website_language', [LanguageController::class, 'edit_website_language'])->name('languages.edit_website_language');
			Route::resource('languages', LanguageController::class);

			//Utility Controller
			Route::match(['get', 'post'], 'administration/general_settings/{store?}', [UtilityController::class, 'settings'])->name('settings.update_settings');
			Route::post('administration/upload_logo', [UtilityController::class, 'upload_logo'])->name('settings.uplaod_logo');
			Route::get('administration/database_backup_list', [UtilityController::class, 'database_backup_list'])->name('database_backups.list');
			Route::get('administration/create_database_backup', [UtilityController::class, 'create_database_backup'])->name('database_backups.create');
			Route::delete('administration/destroy_database_backup/{id}', [UtilityController::class, 'destroy_database_backup'])->name('database_backups.destroy');
			Route::get('administration/download_database_backup/{id}', [UtilityController::class, 'download_database_backup'])->name('database_backups.download');
			Route::post('administration/remove_cache', [UtilityController::class, 'remove_cache'])->name('settings.remove_cache');
			Route::post('administration/send_test_email', [UtilityController::class, 'send_test_email'])->name('settings.send_test_email');

			//Notification Template
			Route::resource('notification_templates', NotificationTemplateController::class)->only([
				'index',
				'edit',
				'update',
			]);
		});
	});

	/** Subscriber Login **/
	Route::group(['middleware' => ['business'], 'prefix' => 'user'], function () {

		//Business Controller
		Route::get('business/{id}/users', [BusinessController::class, 'users'])->name('business.users');
		Route::resource('business', BusinessController::class)->except('show');
		Route::post('business/bulk_destroy', [BusinessController::class, 'bulk_destroy'])->name('business.bulk_destroy');

		//Permission Controller
		Route::get('roles/{role_id?}/access_control', [PermissionController::class, 'show'])->name('permission.show');
		Route::post('permission/store', [PermissionController::class, 'store'])->name('permission.store');
		Route::resource('roles', RoleController::class)->except('show');
		Route::post('roles/bulk_destroy', [RoleController::class, 'bulk_destroy'])->name('roles.bulk_destroy');
		Route::get('roles/trash', [RoleController::class, 'trash'])->name('roles.trash');
		Route::post('roles/{id}/restore', [RoleController::class, 'restore'])->name('roles.restore');
		Route::post('roles/bulk_restore', [RoleController::class, 'bulk_restore'])->name('roles.bulk_restore');
		Route::post('roles/bulk_permanent_destroy', [RoleController::class, 'bulk_permanent_destroy'])->name('roles.bulk_permanent_destroy');
		Route::delete('roles/{id}/permanent_destroy', [RoleController::class, 'permanent_destroy'])->name('roles.permanent_destroy');

		//User Controller
		Route::match(['get', 'post'], 'system_users/{userId}/{businessId}/change_role', [SystemUserController::class, 'change_role'])->name('system_users.change_role');
		Route::post('system_users/send_invitation', [SystemUserController::class, 'send_invitation'])->name('system_users.send_invitation');
		Route::delete('system_users/{id}/destroy', [SystemUserController::class, 'destroy'])->name('system_users.destroy');
		Route::get('system_users/invite/{businessId}', [SystemUserController::class, 'invite'])->name('system_users.invite');
		Route::get('system_users/{businessId}/invitation_history', [SystemUserController::class, 'invitation_history'])->name('invitation_history.index');
		Route::delete('invitation_history/{businessId}/destroy_invitation', [SystemUserController::class, 'destroy_invitation'])->name('invitation_history.destroy_invitation');

		//Business Settings Controller
		Route::post('business/{id}/send_test_email', [BusinessSettingsController::class, 'send_test_email'])->name('business.send_test_email');
		Route::post('business/{id}/store_email_settings', [BusinessSettingsController::class, 'store_email_settings'])->name('business.store_email_settings');
		Route::post('business/{id}/store_payment_gateway_settings', [BusinessSettingsController::class, 'store_payment_gateway_settings'])->name('business.store_payment_gateway_settings');
		Route::post('business/{id}/store_invoice_settings', [BusinessSettingsController::class, 'store_invoice_settings'])->name('business.store_invoice_settings');
		Route::post('business/{id}/store_receipt_settings', [BusinessSettingsController::class, 'store_receipt_settings'])->name('business.store_receipt_settings');
		Route::post('business/{id}/store_purchase_settings', [BusinessSettingsController::class, 'store_purchase_settings'])->name('business.store_purchase_settings');
		Route::post('business/{id}/store_sales_return_settings', [BusinessSettingsController::class, 'store_sales_return_settings'])->name('business.store_sales_return_settings');
		Route::post('business/{id}/store_purchase_return_settings', [BusinessSettingsController::class, 'store_purchase_return_settings'])->name('business.store_purchase_return_settings');
		Route::post('business/{id}/store_currency_settings', [BusinessSettingsController::class, 'store_currency_settings'])->name('business.store_currency_settings');
		Route::post('business/{id}/store_general_settings', [BusinessSettingsController::class, 'store_general_settings'])->name('business.store_general_settings');
		Route::post('business/{id}/store_payroll_settings', [BusinessSettingsController::class, 'store_payroll_settings'])->name('business.store_payroll_settings');
		// Business Settings Route (with tab parameter)
		Route::get('business/{id}/settings/{tab?}', [BusinessSettingsController::class, 'settings'])->name('business.settings');

		Route::get('business/{id}/pos_settings', [BusinessSettingsController::class, 'pos_settings'])->name('business.pos_settings');
		Route::post('business/{id}/pos_settings', [BusinessSettingsController::class, 'store_pos_settings'])->name('business.store_pos_settings');

		//Currency List
		Route::get('currency/trash', [CurrencyController::class, 'trash'])->name('currency.trash');
		Route::post('currency/{id}/restore', [CurrencyController::class, 'restore'])->name('currency.restore');
		Route::post('currency/bulk_restore', [CurrencyController::class, 'bulk_restore'])->name('currency.bulk_restore');
		Route::post('currency/bulk_permanent_destroy', [CurrencyController::class, 'bulk_permanent_destroy'])->name('currency.bulk_permanent_destroy');
		Route::delete('currency/{id}/permanent_destroy', [CurrencyController::class, 'permanent_destroy'])->name('currency.permanent_destroy');
		Route::resource('currency', CurrencyController::class);
		Route::post('currency/bulk_destroy', [CurrencyController::class, 'bulk_destroy'])->name('currency.bulk_destroy');

		// audit logs
		Route::get('audit_logs', [AuditLogController::class, 'index'])->name('audit_logs.index');
	});

	/** Dynamic Permission **/
	Route::group(['middleware' => ['permission'], 'prefix' => 'user'], function () {
		//Business Trash/Restore
		Route::get('business/trash', [BusinessController::class, 'trash'])->name('business.trash');
		Route::post('business/{id}/restore', [BusinessController::class, 'restore'])->name('business.restore');
		Route::post('business/bulk_restore', [BusinessController::class, 'bulk_restore'])->name('business.bulk_restore');
		Route::post('business/bulk_permanent_destroy', [BusinessController::class, 'bulk_permanent_destroy'])->name('business.bulk_permanent_destroy');
		Route::delete('business/{id}/permanent_destroy', [BusinessController::class, 'permanent_destroy'])->name('business.permanent_destroy');


		//Dashboard Widget
		Route::get('dashboard/income_widget', [DashboardController::class, 'income_widget'])->name('dashboard.income_widget');
		Route::get('dashboard/expense_widget', [DashboardController::class, 'expense_widget'])->name('dashboard.expense_widget');
		Route::get('dashboard/accounts_receivable_amount_widget', [DashboardController::class, 'accounts_receivable_amount_widget'])->name('dashboard.accounts_receivable_amount_widget');
		Route::get('dashboard/accounts_payable_amount_widget', [DashboardController::class, 'accounts_payable_amount_widget'])->name('dashboard.accounts_payable_amount_widget');
		Route::get('dashboard/cashflow_widget', [DashboardController::class, 'cashflow_widget'])->name('dashboard.cashflow_widget');
		Route::get('dashboard/top_customers_widget', [DashboardController::class, 'top_customers_widget'])->name('dashboard.top_customers_widget');
		Route::get('dashboard/sales_overview_widget', [DashboardController::class, 'sales_overview_widget'])->name('dashboard.sales_overview_widget');
		Route::get('dashboard/sales_value_widget', [DashboardController::class, 'sales_value_widget'])->name('dashboard.sales_value_widget');
		Route::get('dashboard/recent_transaction_widget', [DashboardController::class, 'recent_transaction_widget'])->name('dashboard.recent_transaction_widget');
		Route::get('dashboard/recent_invoices_widget', [DashboardController::class, 'recent_invoices_widget'])->name('dashboard.recent_invoices_widget');
		Route::get('dashboard/receivables_vs_payables_widget', [DashboardController::class, 'receivables_vs_payables_widget'])->name('dashboard.receivables_vs_payables_widget');

		//Customers
		Route::get('customers/trash', [CustomerController::class, 'trash'])->name('customers.trash');
		Route::post('customers/{id}/restore', [CustomerController::class, 'restore'])->name('customers.restore');
		Route::post('customers/bulk_restore', [CustomerController::class, 'bulk_restore'])->name('customers.bulk_restore');
		Route::post('customers/bulk_permanent_destroy', [CustomerController::class, 'bulk_permanent_destroy'])->name('customers.bulk_permanent_destroy');
		Route::delete('customers/{id}/permanent_destroy', [CustomerController::class, 'permanent_destroy'])->name('customers.permanent_destroy');
		Route::resource('customers', CustomerController::class);
		Route::post('import_customers', [CustomerController::class, 'import_customers'])->name('customers.import');
		Route::post('customers/bulk_destroy', [CustomerController::class, 'bulk_destroy'])->name('customers.bulk_destroy');
		Route::get('export_customers', [CustomerController::class, 'export_customers'])->name('customers.export');

		//Vendors
		Route::get('vendors/trash', [VendorController::class, 'trash'])->name('vendors.trash');
		Route::post('vendors/{id}/restore', [VendorController::class, 'restore'])->name('vendors.restore');
		Route::post('vendors/bulk_restore', [VendorController::class, 'bulk_restore'])->name('vendors.bulk_restore');
		Route::post('vendors/bulk_permanent_destroy', [VendorController::class, 'bulk_permanent_destroy'])->name('vendors.bulk_permanent_destroy');
		Route::delete('vendors/{id}/permanent_destroy', [VendorController::class, 'permanent_destroy'])->name('vendors.permanent_destroy');
		Route::resource('vendors', VendorController::class);
		Route::post('import_suppliers', [VendorController::class, 'import_vendors'])->name('vendors.import');
		Route::get('export_suppliers', [VendorController::class, 'export_vendors'])->name('vendors.export');
		Route::post('vendors/bulk_destroy', [VendorController::class, 'bulk_destroy'])->name('vendors.bulk_destroy');

		//Product Controller
		Route::get('product_units/trash', [ProductUnitController::class, 'trash'])->name('product_units.trash');
		Route::post('product_units/{id}/restore', [ProductUnitController::class, 'restore'])->name('product_units.restore');
		Route::post('product_units/bulk_restore', [ProductUnitController::class, 'bulk_restore'])->name('product_units.bulk_restore');
		Route::post('product_units/bulk_permanent_destroy', [ProductUnitController::class, 'bulk_permanent_destroy'])->name('product_units.bulk_permanent_destroy');
		Route::delete('product_units/{id}/permanent_destroy', [ProductUnitController::class, 'permanent_destroy'])->name('product_units.permanent_destroy');
		Route::resource('product_units', ProductUnitController::class);
		
		Route::post('product_units/bulk_destroy', [ProductUnitController::class, 'bulk_destroy'])->name('product_units.bulk_destroy');
		Route::get('products/export', [ProductController::class, 'product_export'])->name('products.export');
		Route::post('import_products', [ProductController::class, 'import_products'])->name('products.import');
		Route::post('products/bulk_destroy', [ProductController::class, 'bulk_destroy'])->name('products.bulk_destroy');
		Route::get('products/trash', [ProductController::class, 'trash'])->name('products.trash');
		Route::post('products/{id}/restore', [ProductController::class, 'restore'])->name('products.restore');
		Route::post('products/bulk_restore', [ProductController::class, 'bulk_restore'])->name('products.bulk_restore');
		Route::post('products/bulk_permanent_destroy', [ProductController::class, 'bulk_permanent_destroy'])->name('products.bulk_permanent_destroy');
		Route::delete('products/{id}/permanent_destroy', [ProductController::class, 'permanent_destroy'])->name('products.permanent_destroy');
		Route::resource('products', ProductController::class);

		// categories
		Route::get('sub_categories/trash', [SubCategoryController::class, 'trash'])->name('sub_categories.trash');
		Route::post('sub_categories/{id}/restore', [SubCategoryController::class, 'restore'])->name('sub_categories.restore');
		Route::post('sub_categories/bulk_restore', [SubCategoryController::class, 'bulk_restore'])->name('sub_categories.bulk_restore');
		Route::post('sub_categories/bulk_permanent_destroy', [SubCategoryController::class, 'bulk_permanent_destroy'])->name('sub_categories.bulk_permanent_destroy');
		Route::delete('sub_categories/{id}/permanent_destroy', [SubCategoryController::class, 'permanent_destroy'])->name('sub_categories.permanent_destroy');
		Route::resource('sub_categories', SubCategoryController::class);

		Route::get('main_categories/trash', [MainCategoryController::class, 'trash'])->name('main_categories.trash');
		Route::post('main_categories/{id}/restore', [MainCategoryController::class, 'restore'])->name('main_categories.restore');
		Route::post('main_categories/bulk_restore', [MainCategoryController::class, 'bulk_restore'])->name('main_categories.bulk_restore');
		Route::post('main_categories/bulk_permanent_destroy', [MainCategoryController::class, 'bulk_permanent_destroy'])->name('main_categories.bulk_permanent_destroy');
		Route::delete('main_categories/{id}/permanent_destroy', [MainCategoryController::class, 'permanent_destroy'])->name('main_categories.permanent_destroy');
		Route::resource('main_categories', MainCategoryController::class);
		Route::post('main_categories/bulk_destroy', [MainCategoryController::class, 'bulk_destroy'])->name('main_categories.bulk_destroy');

		// brands
		Route::post('brands/bulk_destroy', [BrandsController::class, 'bulk_destroy'])->name('brands.bulk_destroy');
		Route::get('brands/trash', [BrandsController::class, 'trash'])->name('brands.trash');
		Route::post('brands/{id}/restore', [BrandsController::class, 'restore'])->name('brands.restore');
		Route::post('brands/bulk_restore', [BrandsController::class, 'bulk_restore'])->name('brands.bulk_restore');
		Route::post('brands/bulk_permanent_destroy', [BrandsController::class, 'bulk_permanent_destroy'])->name('brands.bulk_permanent_destroy');
		Route::delete('brands/{id}/permanent_destroy', [BrandsController::class, 'permanent_destroy'])->name('brands.permanent_destroy');
		Route::resource('brands', BrandsController::class);

		// Inventory Adjustment Import (MUST BE BEFORE RESOURCE ROUTE)
		Route::post('import_adjustments', [InventoryAdjustmentController::class, 'import_adjustments'])->name('inventory_adjustments.import');
		Route::get('export_adjustments', [InventoryAdjustmentController::class, 'export_adjustments'])->name('inventory_adjustments.export');

		// inventory adjustments resource route
		Route::resource('inventory_transfers', InventoryTransferController::class);
		Route::post('inventory_transfers/bulk_destroy', [InventoryTransferController::class, 'bulk_destroy'])->name('inventory_transfers.bulk_destroy');

		// Inventory Adjustment Import (MUST BE BEFORE RESOURCE ROUTE)
		Route::post('import_transfers', [InventoryTransferController::class, 'import_transfers'])->name('inventory_transfers.import');
		Route::get('export_transfers', [InventoryTransferController::class, 'export_transfers'])->name('inventory_transfers.export');

		// inventory adjustments resource route
		Route::get('inventory_adjustments/trash', [InventoryAdjustmentController::class, 'trash'])->name('inventory_adjustments.trash');
		Route::post('inventory_adjustments/{id}/restore', [InventoryAdjustmentController::class, 'restore'])->name('inventory_adjustments.restore');
		Route::post('inventory_adjustments/bulk_restore', [InventoryAdjustmentController::class, 'bulk_restore'])->name('inventory_adjustments.bulk_restore');
		Route::post('inventory_adjustments/bulk_permanent_destroy', [InventoryAdjustmentController::class, 'bulk_permanent_destroy'])->name('inventory_adjustments.bulk_permanent_destroy');
		Route::delete('inventory_adjustments/{id}/permanent_destroy', [InventoryAdjustmentController::class, 'permanent_destroy'])->name('inventory_adjustments.permanent_destroy');
		Route::resource('inventory_adjustments', InventoryAdjustmentController::class);
		Route::post('inventory_adjustments/bulk_destroy', [InventoryAdjustmentController::class, 'bulk_destroy'])->name('inventory_adjustments.bulk_destroy');

		//Invoices
		Route::match(['get', 'post'], 'invoices/{id}/send_email', [InvoiceController::class, 'send_email'])->name('invoices.send_email');
		Route::match(['get', 'post'], 'invoices/receive_payment', [InvoiceController::class, 'receive_payment'])->name('invoices.receive_payment');
		Route::get('invoices/{id}/duplicate', [InvoiceController::class, 'duplicate'])->name('invoices.duplicate');
		Route::get('invoices/{id}/get_invoice_link', [InvoiceController::class, 'get_invoice_link'])->name('invoices.get_invoice_link');
		Route::get('invoices/trash', [InvoiceController::class, 'trash'])->name('invoices.trash');
		Route::post('invoices/{id}/restore', [InvoiceController::class, 'restore'])->name('invoices.restore');
		Route::post('invoices/bulk_restore', [InvoiceController::class, 'bulk_restore'])->name('invoices.bulk_restore');
		Route::post('invoices/bulk_permanent_destroy', [InvoiceController::class, 'bulk_permanent_destroy'])->name('invoices.bulk_permanent_destroy');
		Route::delete('invoices/{id}/permanent_destroy', [InvoiceController::class, 'permanent_destroy'])->name('invoices.permanent_destroy');
		Route::resource('invoices', InvoiceController::class);
		Route::resource('receive_payments', ReceivePaymentsController::class);
		Route::post('import_invoices', [InvoiceController::class, 'import_invoices'])->name('invoices.import');
		Route::post('invoices/filter', [InvoiceController::class, 'invoices_filter'])->name('invoices.filter');
		Route::post('invoices/bulk_destroy', [InvoiceController::class, 'bulk_destroy'])->name('invoices.bulk_destroy');
		Route::get('export_invoices', [InvoiceController::class, 'export_invoices'])->name('invoices.export');

		// Sales Return
		Route::get('sales_returns/trash', [SalesReturnController::class, 'trash'])->name('sales_returns.trash');
		Route::post('sales_returns/{id}/restore', [SalesReturnController::class, 'restore'])->name('sales_returns.restore');
		Route::post('sales_returns/bulk_restore', [SalesReturnController::class, 'bulk_restore'])->name('sales_returns.bulk_restore');
		Route::post('sales_returns/bulk_permanent_destroy', [SalesReturnController::class, 'bulk_permanent_destroy'])->name('sales_returns.bulk_permanent_destroy');
		Route::delete('sales_returns/{id}/permanent_destroy', [SalesReturnController::class, 'permanent_destroy'])->name('sales_returns.permanent_destroy');
		Route::resource('sales_returns', SalesReturnController::class);
		Route::post('sales_returns/refund/store/{id}', [SalesReturnController::class, 'refund_store'])->name('sales_returns.refund.store');
		Route::post('sales_returns/bulk_destroy', [SalesReturnController::class, 'bulk_destroy'])->name('sales_returns.bulk_destroy');
		Route::match(['get', 'post'], 'sales_returns/{id}/send_email', [SalesReturnController::class, 'send_email'])->name('sales_returns.send_email');

		// purchase return
		Route::get('purchase_returns/trash', [PurchaseReturnController::class, 'trash'])->name('purchase_returns.trash');
		Route::post('purchase_returns/{id}/restore', [PurchaseReturnController::class, 'restore'])->name('purchase_returns.restore');
		Route::post('purchase_returns/bulk_restore', [PurchaseReturnController::class, 'bulk_restore'])->name('purchase_returns.bulk_restore');
		Route::post('purchase_returns/bulk_permanent_destroy', [PurchaseReturnController::class, 'bulk_permanent_destroy'])->name('purchase_returns.bulk_permanent_destroy');
		Route::delete('purchase_returns/{id}/permanent_destroy', [PurchaseReturnController::class, 'permanent_destroy'])->name('purchase_returns.permanent_destroy');
		Route::resource('purchase_returns', PurchaseReturnController::class);
		Route::get('purchase_returns/refund/{id}', [PurchaseReturnController::class, 'refund'])->name('purchase_returns.refund');
		Route::post('purchase_returns/refund/store/{id}', [PurchaseReturnController::class, 'refund_store'])->name('purchase_returns.refund.store');
		Route::post('purchase_returns/bulk_destroy', [PurchaseReturnController::class, 'bulk_destroy'])->name('purchase_returns.bulk_destroy');
		Route::match(['get', 'post'], 'purchase_returns/{id}/send_email', [PurchaseReturnController::class, 'send_email'])->name('purchase_returns.send_email');
		Route::post('purchase_returns/bulk_approve', [PurchaseReturnController::class, 'bulk_approve'])->name('purchase_returns.bulk_approve');
		Route::post('purchase_returns/bulk_reject', [PurchaseReturnController::class, 'bulk_reject'])->name('purchase_returns.bulk_reject');

		// Journal Entry
		Route::get('journals/trash', [JournalController::class, 'trash'])->name('journals.trash');
		Route::post('journals/{id}/restore', [JournalController::class, 'restore'])->name('journals.restore');
		Route::post('journals/bulk_restore', [JournalController::class, 'bulk_restore'])->name('journals.bulk_restore');
		Route::post('journals/bulk_permanent_destroy', [JournalController::class, 'bulk_permanent_destroy'])->name('journals.bulk_permanent_destroy');
		Route::delete('journals/{id}/permanent_destroy', [JournalController::class, 'permanent_destroy'])->name('journals.permanent_destroy');
		Route::resource('journals', JournalController::class);
		Route::post('import_journal', [JournalController::class, 'import_journal'])->name('journals.import');
		Route::get('journal/export/{id}', [JournalController::class, 'export_journal'])->name('journals.export');
		Route::post('journal/bulk_destroy', [JournalController::class, 'bulk_destroy'])->name('journals.bulk_destroy');
		Route::post('journal/bulk_approve', [JournalController::class, 'bulk_approve'])->name('journals.bulk_approve');
		Route::post('journal/bulk_reject', [JournalController::class, 'bulk_reject'])->name('journals.bulk_reject');

		// Sales Receipts
		Route::get('receipts/trash', [ReceiptController::class, 'trash'])->name('receipts.trash');
		Route::post('receipts/{id}/restore', [ReceiptController::class, 'restore'])->name('receipts.restore');
		Route::post('receipts/bulk_restore', [ReceiptController::class, 'bulk_restore'])->name('receipts.bulk_restore');
		Route::post('receipts/bulk_permanent_destroy', [ReceiptController::class, 'bulk_permanent_destroy'])->name('receipts.bulk_permanent_destroy');
		Route::delete('receipts/{id}/permanent_destroy', [ReceiptController::class, 'permanent_destroy'])->name('receipts.permanent_destroy');
		Route::resource('receipts', ReceiptController::class);
		Route::match(['get', 'post'], 'receipts/{id}/send_email', [ReceiptController::class, 'send_email'])->name('receipts.send_email');
		Route::match(['get', 'post'], 'receipts/receive_payment', [ReceiptController::class, 'receive_payment'])->name('receipts.receive_payment');
		Route::get('pos', [ReceiptController::class, 'pos'])->name('receipts.pos');
		Route::post('import_receipts', [ReceiptController::class, 'import_receipts'])->name('receipts.import');
		Route::post('receipts/filter', [ReceiptController::class, 'receipts_filter'])->name('receipts.filter');
		Route::post('pos/store', [ReceiptController::class, 'pos_store'])->name('receipts.pos_store');
		Route::post('receipts/bulk_destroy', [ReceiptController::class, 'bulk_destroy'])->name('receipts.bulk_destroy');
		Route::get('export_receipts', [ReceiptController::class, 'export_receipts'])->name('receipts.export');

		// hold pos invoices
		Route::resource('hold_pos_invoices', HoldPosInvoiceController::class);

		//Recurring Invoice
		Route::get('recurring_invoices/{id}/convert_recurring', [RecurringInvoiceController::class, 'convert_recurring'])->name('recurring_invoices.convert_recurring');
		Route::get('recurring_invoices/{id}/end_recurring', [RecurringInvoiceController::class, 'end_recurring'])->name('recurring_invoices.end_recurring');
		Route::get('recurring_invoices/{id}/duplicate', [RecurringInvoiceController::class, 'duplicate'])->name('recurring_invoices.duplicate');
		Route::get('recurring_invoices/{id}/approve', [RecurringInvoiceController::class, 'approve'])->name('recurring_invoices.approve');
		Route::resource('recurring_invoices', RecurringInvoiceController::class);

		// Deffered Invoices
		Route::get('deffered_invoices/trash', [DefferedInvoiceController::class, 'trash'])->name('deffered_invoices.trash');
		Route::post('deffered_invoices/{id}/restore', [DefferedInvoiceController::class, 'restore'])->name('deffered_invoices.restore');
		Route::post('deffered_invoices/bulk_restore', [DefferedInvoiceController::class, 'bulk_restore'])->name('deffered_invoices.bulk_restore');
		Route::post('deffered_invoices/bulk_permanent_destroy', [DefferedInvoiceController::class, 'bulk_permanent_destroy'])->name('deffered_invoices.bulk_permanent_destroy');
		Route::delete('deffered_invoices/{id}/permanent_destroy', [DefferedInvoiceController::class, 'permanent_destroy'])->name('deffered_invoices.permanent_destroy');
		Route::resource('deffered_invoices', DefferedInvoiceController::class);
		Route::get('deffered_invoices/payments/{id}', [DefferedInvoiceController::class, 'payments'])->name('deffered_invoices.payments');
		Route::get('deffered_invoices/earnings/{id}', [DefferedInvoiceController::class, 'earnings'])->name('deffered_invoices.earnings');
		Route::post('deffered_invoices/filter', [DefferedInvoiceController::class, 'deffered_invoices_filter'])->name('deffered_invoices.filter');
		Route::post('deffered_invoices/bulk_destroy', [DefferedInvoiceController::class, 'bulk_destroy'])->name('deffered_invoices.bulk_destroy');
		Route::post('deffered_invoices/send_email/{id}', [DefferedInvoiceController::class, 'send_email'])->name('deffered_invoices.send_email');

		// deffered payments
		Route::resource('deffered_receive_payments', DefferedPaymentController::class);

		// insurance benefits
		Route::resource('insurance_benefits', InsuranceBenefitController::class);

		// insurance family sizes
		Route::resource('insurance_family_sizes', InsuranceFamilySizeController::class);

		//Quotation
		Route::get('quotations/trash', [QuotationController::class, 'trash'])->name('quotations.trash');
		Route::post('quotations/{id}/restore', [QuotationController::class, 'restore'])->name('quotations.restore');
		Route::post('quotations/bulk_restore', [QuotationController::class, 'bulk_restore'])->name('quotations.bulk_restore');
		Route::post('quotations/bulk_permanent_destroy', [QuotationController::class, 'bulk_permanent_destroy'])->name('quotations.bulk_permanent_destroy');
		Route::delete('quotations/{id}/permanent_destroy', [QuotationController::class, 'permanent_destroy'])->name('quotations.permanent_destroy');
		Route::match(['get', 'post'], 'quotations/{id}/send_email', [QuotationController::class, 'send_email'])->name('quotations.send_email');
		Route::get('quotations/{id}/convert_to_invoice', [QuotationController::class, 'convert_to_invoice'])->name('quotations.convert_to_invoice');
		Route::get('quotations/{id}/duplicate', [QuotationController::class, 'duplicate'])->name('quotations.duplicate');
		Route::post('quotations/bulk_destroy', [QuotationController::class, 'bulk_destroy'])->name('quotations.bulk_destroy');
		Route::resource('quotations', QuotationController::class);

		Route::get('/ai/chat', [AIChatController::class, 'index'])->name('ai.chat');
		Route::post('/ai/chat/generate', [AIChatController::class, 'generate'])->name('ai.chat.generate')->middleware('throttle:30,1'); // 30 requests per minute

		//Bills
		Route::match(['get', 'post'], 'bill_invoices/pay_bill', [PurchaseController::class, 'pay_bill'])->name('billI_invoices.pay_bill');
		Route::get('purchases/{id}/duplicate', [PurchaseController::class, 'duplicate'])->name('bill_invoices.duplicate');
		Route::get('bill_invoices/trash', [PurchaseController::class, 'trash'])->name('bill_invoices.trash');
		Route::post('bill_invoices/{id}/restore', [PurchaseController::class, 'restore'])->name('bill_invoices.restore');
		Route::post('bill_invoices/bulk_restore', [PurchaseController::class, 'bulk_restore'])->name('bill_invoices.bulk_restore');
		Route::post('bill_invoices/bulk_permanent_destroy', [PurchaseController::class, 'bulk_permanent_destroy'])->name('bill_invoices.bulk_permanent_destroy');
		Route::delete('bill_invoices/{id}/permanent_destroy', [PurchaseController::class, 'permanent_destroy'])->name('bill_invoices.permanent_destroy');
		Route::resource('bill_invoices', PurchaseController::class);
		Route::post('import_bills', [PurchaseController::class, 'import_bills'])->name('bill_invoices.import');
		Route::post('bill_invoices/filter', [PurchaseController::class, 'bill_invoices_filter'])->name('bill_invoices.filter');

		//Bill Payments
		Route::get('bill_payments/trash', [BillPaymentsController::class, 'trash'])->name('bill_payments.trash');
		Route::post('bill_payments/{id}/restore', [BillPaymentsController::class, 'restore'])->name('bill_payments.restore');
		Route::post('bill_payments/bulk_restore', [BillPaymentsController::class, 'bulk_restore'])->name('bill_payments.bulk_restore');
		Route::post('bill_payments/bulk_permanent_destroy', [BillPaymentsController::class, 'bulk_permanent_destroy'])->name('bill_payments.bulk_permanent_destroy');
		Route::delete('bill_payments/{id}/permanent_destroy', [BillPaymentsController::class, 'permanent_destroy'])->name('bill_payments.permanent_destroy');
		Route::resource('bill_payments', BillPaymentsController::class);
		Route::post('bill_payments/bulk_destroy', [BillPaymentsController::class, 'bulk_destroy'])->name('bill_payments.bulk_destroy');
		Route::get('export_bill_invoices', [PurchaseController::class, 'export_bill_invoices'])->name('bill_invoices.export');
		Route::post('bill_invoices/bulk_approve', [PurchaseController::class, 'bulk_approve'])->name('bill_invoices.bulk_approve');
		Route::post('bill_invoices/bulk_reject', [PurchaseController::class, 'bulk_reject'])->name('bill_invoices.bulk_reject');
		Route::post('bill_invoices/bulk_destroy', [PurchaseController::class, 'bulk_destroy'])->name('bill_invoices.bulk_destroy');
		Route::post('bill_invoices/send_email/{id}', [PurchaseController::class, 'send_email'])->name('bill_invoices.send_email');
		Route::get('bill_invoices/{id}/pdf', [PurchaseController::class, 'pdf'])->name('bill_invoices.pdf');

		//Cash Purchases
		Route::get('cash_purchases/trash', [CashPurchaseController::class, 'trash'])->name('cash_purchases.trash');
		Route::post('cash_purchases/{id}/restore', [CashPurchaseController::class, 'restore'])->name('cash_purchases.restore');
		Route::post('cash_purchases/bulk_restore', [CashPurchaseController::class, 'bulk_restore'])->name('cash_purchases.bulk_restore');
		Route::post('cash_purchases/bulk_permanent_destroy', [CashPurchaseController::class, 'bulk_permanent_destroy'])->name('cash_purchases.bulk_permanent_destroy');
		Route::delete('cash_purchases/{id}/permanent_destroy', [CashPurchaseController::class, 'permanent_destroy'])->name('cash_purchases.permanent_destroy');
		Route::resource('cash_purchases', CashPurchaseController::class);
		Route::post('import_cash_purchases', [CashPurchaseController::class, 'import_cash_purchases'])->name('cash_purchases.import');
		Route::post('all_bills', [CashPurchaseController::class, 'bills_all'])->name('cash_purchases.all');
		Route::get('export_cash_purchases', [CashPurchaseController::class, 'export_cash_purchases'])->name('cash_purchases.export');
		Route::get('cash_purchases/voucher/{id}', [CashPurchaseController::class, 'voucher'])->name('cash_purchases.voucher');
		Route::post('cash_purchases/bulk_approve', [CashPurchaseController::class, 'bulk_approve'])->name('cash_purchases.bulk_approve');
		Route::post('cash_purchases/bulk_reject', [CashPurchaseController::class, 'bulk_reject'])->name('cash_purchases.bulk_reject');
		Route::post('cash_purchases/bulk_destroy', [CashPurchaseController::class, 'bulk_destroy'])->name('cash_purchases.bulk_destroy');
		Route::post('cash_purchases/{id}/send_email', [CashPurchaseController::class, 'send_email'])->name('cash_purchases.send_email');
		Route::get('cash_purchases/{id}/pdf', [CashPurchaseController::class, 'pdf'])->name('cash_purchases.pdf');

		// purchase orders
		Route::get('purchase_orders/{id}/pdf', [PurchaseOrderController::class, 'pdf'])->name('purchase_orders.pdf');
		Route::get('purchase_orders/{id}/duplicate', [PurchaseOrderController::class, 'duplicate'])->name('purchase_orders.duplicate');
		Route::get('purchase_orders/trash', [PurchaseOrderController::class, 'trash'])->name('purchase_orders.trash');
		Route::post('purchase_orders/{id}/restore', [PurchaseOrderController::class, 'restore'])->name('purchase_orders.restore');
		Route::post('purchase_orders/bulk_restore', [PurchaseOrderController::class, 'bulk_restore'])->name('purchase_orders.bulk_restore');
		Route::post('purchase_orders/bulk_permanent_destroy', [PurchaseOrderController::class, 'bulk_permanent_destroy'])->name('purchase_orders.bulk_permanent_destroy');
		Route::delete('purchase_orders/{id}/permanent_destroy', [PurchaseOrderController::class, 'permanent_destroy'])->name('purchase_orders.permanent_destroy');
		Route::resource('purchase_orders', PurchaseOrderController::class);
		Route::post('import_purchase_orders', [PurchaseOrderController::class, 'import_purchase_orders'])->name('purchase_orders.import');
		Route::post('purchase_orders/filter', [PurchaseOrderController::class, 'purchase_orders_filter'])->name('purchase_orders.filter');
		Route::post('purchase_oders/bulk_destroy', [PurchaseOrderController::class, 'bulk_destroy'])->name('purchase_orders.bulk_destroy');
		Route::get('export_purchase_orders', [PurchaseOrderController::class, 'export_purchase_orders'])->name('purchase_orders.export');
		Route::post('purchase_orders/{id}/convert_to_bill', [PurchaseOrderController::class, 'convert_to_bill'])->name('purchase_orders.convert_to_bill');
		Route::post('purchase_orders/{id}/convert_to_cash', [PurchaseOrderController::class, 'convert_to_cash_purchase'])->name('purchase_orders.convert_to_cash_purchase');
		Route::post('purchase_orders/{id}/send_email', [PurchaseOrderController::class, 'send_email'])->name('purchase_orders.send_email');

		// medical records
		Route::get('medical_records/trash', [MedicalRecordController::class, 'trash'])->name('medical_records.trash');
		Route::post('medical_records/{id}/restore', [MedicalRecordController::class, 'restore'])->name('medical_records.restore');
		Route::post('medical_records/bulk_restore', [MedicalRecordController::class, 'bulk_restore'])->name('medical_records.bulk_restore');
		Route::post('medical_records/bulk_permanent_destroy', [MedicalRecordController::class, 'bulk_permanent_destroy'])->name('medical_records.bulk_permanent_destroy');
		Route::delete('medical_records/{id}/permanent_destroy', [MedicalRecordController::class, 'permanent_destroy'])->name('medical_records.permanent_destroy');
		Route::resource('medical_records', MedicalRecordController::class);
		Route::post('medical_records/bulk_destroy', [MedicalRecordController::class, 'bulk_destroy'])->name('medical_records.bulk_destroy');

		// prescriptions
		Route::get('prescriptions/trash', [PrescriptionController::class, 'trash'])->name('prescriptions.trash');
		Route::post('prescriptions/{id}/restore', [PrescriptionController::class, 'restore'])->name('prescriptions.restore');
		Route::post('prescriptions/bulk_restore', [PrescriptionController::class, 'bulk_restore'])->name('prescriptions.bulk_restore');
		Route::post('prescriptions/bulk_permanent_destroy', [PrescriptionController::class, 'bulk_permanent_destroy'])->name('prescriptions.bulk_permanent_destroy');
		Route::delete('prescriptions/{id}/permanent_destroy', [PrescriptionController::class, 'permanent_destroy'])->name('prescriptions.permanent_destroy');
		Route::resource('prescriptions', PrescriptionController::class);
		Route::post('prescriptions/bulk_destroy', [PrescriptionController::class, 'bulk_destroy'])->name('prescriptions.bulk_destroy');
		Route::post('prescriptions/{id}', [PrescriptionController::class, 'change_status'])->name('prescriptions.change_status');
		Route::get('prescriptions/{id}/edit_status', [PrescriptionController::class, 'edit_status'])->name('prescriptions.edit_status');

		//Accounts
		Route::get('accounts/{accountId}/{amount}/convert_due_amount', [AccountController::class, 'convert_due_amount']);
		Route::get('accounts/trash', [AccountController::class, 'trash'])->name('accounts.trash');
		Route::post('accounts/{id}/restore', [AccountController::class, 'restore'])->name('accounts.restore');
		Route::post('accounts/bulk_restore', [AccountController::class, 'bulk_restore'])->name('accounts.bulk_restore');
		Route::post('accounts/bulk_permanent_destroy', [AccountController::class, 'bulk_permanent_destroy'])->name('accounts.bulk_permanent_destroy');
		Route::delete('accounts/{id}/permanent_destroy', [AccountController::class, 'permanent_destroy'])->name('accounts.permanent_destroy');
		Route::resource('accounts', AccountController::class);
		Route::post('account/import_statement/{id}', [AccountController::class, 'importStatement'])->name('accounts.import_statement');
		Route::post('import_accounts', [AccountController::class, 'import_accounts'])->name('accounts.import');
		Route::get('export_accounts', [AccountController::class, 'export_accounts'])->name('accounts.export');
		Route::post('accounts/bulk_destroy', [AccountController::class, 'bulk_destroy'])->name('accounts.bulk_destroy');
		Route::match(['get', 'post'], 'account/account_statement/{id}', [AccountController::class, 'account_statement'])->name('accounts.account_statement');
		Route::get('export_account_statement/{id}', [AccountController::class, 'export_account_statement'])->name('accounts.export_account_statement');

		//Custom Invoice Template
		Route::get('invoice_templates/{id}/clone', [InvoiceTemplateController::class, 'clone'])->name('invoice_templates.clone');
		Route::get('invoice_templates/element/{element}', [InvoiceTemplateController::class, 'get_element']);
		Route::resource('invoice_templates', InvoiceTemplateController::class);

		//Transaction
		Route::resource('transactions', TransactionController::class);

		//Transaction Methods
		Route::get('transaction_methods/trash', [TransactionMethodController::class, 'trash'])->name('transaction_methods.trash');
		Route::post('transaction_methods/{id}/restore', [TransactionMethodController::class, 'restore'])->name('transaction_methods.restore');
		Route::post('transaction_methods/bulk_restore', [TransactionMethodController::class, 'bulk_restore'])->name('transaction_methods.bulk_restore');
		Route::post('transaction_methods/bulk_permanent_destroy', [TransactionMethodController::class, 'bulk_permanent_destroy'])->name('transaction_methods.bulk_permanent_destroy');
		Route::delete('transaction_methods/{id}/permanent_destroy', [TransactionMethodController::class, 'permanent_destroy'])->name('transaction_methods.permanent_destroy');
		Route::resource('transaction_methods', TransactionMethodController::class)->except('view');
		Route::post('transaction_methods/bulk_destroy', [TransactionMethodController::class, 'bulk_destroy'])->name('transaction_methods.bulk_destroy');

		//HR Module
		Route::get('departments/trash', [DepartmentController::class, 'trash'])->name('departments.trash');
		Route::post('departments/{id}/restore', [DepartmentController::class, 'restore'])->name('departments.restore');
		Route::post('departments/bulk_restore', [DepartmentController::class, 'bulk_restore'])->name('departments.bulk_restore');
		Route::post('departments/bulk_permanent_destroy', [DepartmentController::class, 'bulk_permanent_destroy'])->name('departments.bulk_permanent_destroy');
		Route::delete('departments/{id}/permanent_destroy', [DepartmentController::class, 'permanent_destroy'])->name('departments.permanent_destroy');
		Route::resource('departments', DepartmentController::class);
		Route::post('departments/bulk_destroy', [DepartmentController::class, 'bulk_destroy'])->name('departments.bulk_destroy');
		Route::get('designations/trash', [DesignationController::class, 'trash'])->name('designations.trash');
		Route::post('designations/{id}/restore', [DesignationController::class, 'restore'])->name('designations.restore');
		Route::post('designations/bulk_restore', [DesignationController::class, 'bulk_restore'])->name('designations.bulk_restore');
		Route::post('designations/bulk_permanent_destroy', [DesignationController::class, 'bulk_permanent_destroy'])->name('designations.bulk_permanent_destroy');
		Route::delete('designations/{id}/permanent_destroy', [DesignationController::class, 'permanent_destroy'])->name('designations.permanent_destroy');
		Route::get('designations/get_designations/{deaprtment_id}', [DesignationController::class, 'get_designations']);
		Route::resource('designations', DesignationController::class)->except('show');
		Route::post('designations/bulk_destroy', [DesignationController::class, 'bulk_destroy'])->name('designations.bulk_destroy');
		// Route::get('salary_scales/get_saylary_scales/{designation_id}', [SalaryScaleController::class, 'get_salary_scales']);
		// Route::get('salary_scales/filter_by_department/{department_id}', [SalaryScaleController::class, 'index'])->name('salary_scales.filter_by_department');
		// Route::resource('salary_scales', SalaryScaleController::class);
		// Route::resource('benefits', BenefitController::class);

		// construction
		Route::resource('cost_codes', CostCodeController::class);
		Route::post('import_cost_codes', [CostCodeController::class, 'import_cost_codes'])->name('cost_codes.import');
		Route::get('export_cost_codes', [CostCodeController::class, 'export_cost_codes'])->name('cost_codes.export');
		Route::post('bulk_destroy_cost_codes', [CostCodeController::class, 'bulk_destroy'])->name('cost_codes.bulk_destroy');

		Route::resource('projects', ProjectController::class);
		Route::post('import_projects', [ProjectController::class, 'import_projects'])->name('projects.import');
		Route::get('export_projects', [ProjectController::class, 'export_projects'])->name('projects.export');
		Route::post('bulk_destroy_projects', [ProjectController::class, 'bulk_destroy'])->name('projects.bulk_destroy');

		Route::resource('project_tasks', ProjectTaskController::class);
		Route::post('import_project_tasks', [ProjectTaskController::class, 'import_project_tasks'])->name('project_tasks.import');
		Route::get('export_project_tasks', [ProjectTaskController::class, 'export_project_tasks'])->name('project_tasks.export');
		Route::post('bulk_destroy_project_tasks', [ProjectTaskController::class, 'bulk_destroy'])->name('project_tasks.bulk_destroy');

		Route::resource('project_budgets', ProjectBudgetController::class);
		Route::post('import_project_budgets', [ProjectBudgetController::class, 'import_project_budgets'])->name('project_budgets.import');
		Route::get('export_project_budgets', [ProjectBudgetController::class, 'export_project_budgets'])->name('project_budgets.export');
		Route::post('bulk_destroy_project_budgets', [ProjectBudgetController::class, 'bulk_destroy'])->name('project_budgets.bulk_destroy');
			
		Route::resource('project_groups', ProjectGroupController::class);
		Route::post('import_project_groups', [ProjectGroupController::class, 'import_project_groups'])->name('project_groups.import');
		Route::get('export_project_groups', [ProjectGroupController::class, 'export_project_groups'])->name('project_groups.export');
		Route::post('bulk_destroy_project_groups', [ProjectGroupController::class, 'bulk_destroy'])->name('project_groups.bulk_destroy');

		Route::resource('project_subcontracts', ProjectSubcontractController::class);
		Route::post('import_project_subcontracts', [ProjectSubcontractController::class, 'import_project_subcontracts'])->name('project_subcontracts.import');
		Route::get('export_project_subcontracts', [ProjectSubcontractController::class, 'export_project_subcontracts'])->name('project_subcontracts.export');
		Route::post('bulk_destroy_project_subcontracts', [ProjectSubcontractController::class, 'bulk_destroy'])->name('project_subcontracts.bulk_destroy');
		Route::match(['get', 'post'], 'project_subcontracts/{id}/send_email', [ProjectSubcontractController::class, 'send_email'])->name('project_subcontracts.send_email');
		Route::resource('project_subcontract_payments', ProjectSubcontractPaymentController::class)->except('edit', 'create');
		Route::post('project_subcontract_payments/bulk_destroy', [ProjectSubcontractPaymentController::class, 'bulk_destroy'])->name('project_subcontract_payments.bulk_destroy');

		Route::resource('change_orders', ChangeOrderController::class)->only('store', 'update', 'destroy');
		Route::post('bulk_destroy_change_orders', [ChangeOrderController::class, 'bulk_destroy'])->name('change_orders.bulk_destroy');
		
		//Staff Controller
		Route::get('staffs/trash', [StaffController::class, 'trash'])->name('staffs.trash');
		Route::post('staffs/{id}/restore', [StaffController::class, 'restore'])->name('staffs.restore');
		Route::post('staffs/bulk_restore', [StaffController::class, 'bulk_restore'])->name('staffs.bulk_restore');
		Route::post('staffs/bulk_permanent_destroy', [StaffController::class, 'bulk_permanent_destroy'])->name('staffs.bulk_permanent_destroy');
		Route::delete('staffs/{id}/permanent_destroy', [StaffController::class, 'permanent_destroy'])->name('staffs.permanent_destroy');
		Route::post('staffs/bulk_destroy', [StaffController::class, 'bulk_destroy'])->name('staffs.bulk_destroy');
		Route::resource('staffs', StaffController::class);
		Route::post('import_staffs', [StaffController::class, 'import_staffs'])->name('staffs.import');
		Route::get('export_staffs', [StaffController::class, 'export_staffs'])->name('staffs.export');
		Route::post('staffs/{id}', [StaffController::class, 'change_status'])->name('staffs.change_status');

		//Staff Documents
		Route::get('staff_documents/{employee_id}', [StaffDocumentController::class, 'index'])->name('staff_documents.index');
		Route::get('staff_documents/create/{employee_id}', [StaffDocumentController::class, 'create'])->name('staff_documents.create');
		Route::resource('staff_documents', StaffDocumentController::class)->except(['index', 'create', 'show']);
		Route::post('staff_documents/store', [StaffDocumentController::class, 'store'])->name('staff_documents.store');
		Route::delete('staff_documents/{id}', [StaffDocumentController::class, 'destroy'])->name('staff_documents.destroy');

		//Customer Documents
		Route::post('customer/documents/store', [CustomerDocumentController::class, 'store'])->name('customer.documents.store');
		Route::delete('customer/documents/{id}', [CustomerDocumentController::class, 'destroy'])->name('customer.documents.destroy');
		Route::get('customer/documents/create/{id}', [CustomerDocumentController::class, 'create'])->name('customer.documents.create');

		//Vendor Documents
		Route::post('vendor/documents/store', [VendorDocumentController::class, 'store'])->name('vendor.documents.store');
		Route::delete('vendor/documents/{id}', [VendorDocumentController::class, 'destroy'])->name('vendor.documents.destroy');
		Route::get('vendor/documents/create/{id}', [VendorDocumentController::class, 'create'])->name('vendor.documents.create');

		//Holiday Controller
		Route::match(['get', 'post'], 'holidays/weekends', [HolidayController::class, 'weekends'])->name('holidays.weekends');
		Route::get('holidays/trash', [HolidayController::class, 'trash'])->name('holidays.trash');
		Route::post('holidays/{id}/restore', [HolidayController::class, 'restore'])->name('holidays.restore');
		Route::post('holidays/bulk_restore', [HolidayController::class, 'bulk_restore'])->name('holidays.bulk_restore');
		Route::delete('holidays/{id}/permanent_destroy', [HolidayController::class, 'permanent_destroy'])->name('holidays.permanent_destroy');
		Route::post('holidays/bulk_permanent_destroy', [HolidayController::class, 'bulk_permanent_destroy'])->name('holidays.bulk_permanent_destroy');
		Route::post('holidays/bulk_destroy', [HolidayController::class, 'bulk_delete'])->name('holidays.bulk_destroy');
		Route::resource('holidays', HolidayController::class)->except('show');

		//Leave Application
		Route::resource('leave_types', LeaveTypeController::class)->except('show');
		Route::get('leaves/trash', [LeaveController::class, 'trash'])->name('leaves.trash');
		Route::post('leaves/{id}/restore', [LeaveController::class, 'restore'])->name('leaves.restore');
		Route::post('leaves/bulk_restore', [LeaveController::class, 'bulk_restore'])->name('leaves.bulk_restore');
		Route::post('leaves/bulk_permanent_destroy', [LeaveController::class, 'bulk_permanent_destroy'])->name('leaves.bulk_permanent_destroy');
		Route::delete('leaves/{id}/permanent_destroy', [LeaveController::class, 'permanent_destroy'])->name('leaves.permanent_destroy');
		Route::post('leaves/bulk_destroy', [LeaveController::class, 'bulk_destroy'])->name('leaves.bulk_destroy');
		Route::post('leaves/bulk_approve', [LeaveController::class, 'bulk_approve'])->name('leaves.bulk_approve');
		Route::post('leaves/bulk_reject', [LeaveController::class, 'bulk_reject'])->name('leaves.bulk_reject');
		Route::resource('leaves', LeaveController::class);

		//Attendance Controller
		Route::post('attendance_logs/create', [AttendanceLogController::class, 'create'])->name('attendance_logs.create');
		Route::resource('attendance_logs', AttendanceLogController::class)->except('show', 'edit', 'update', 'destroy');
		Route::post('import_attendance_logs', [AttendanceLogController::class, 'import_attendance_logs'])->name('attendance_logs.import');
		Route::get('export_attendance_logs', [AttendanceLogController::class, 'export_attendance_logs'])->name('attendance_logs.export');
		Route::get('fetch_attendance_logs', [AttendanceLogController::class, 'fetch'])->name('attendance_logs.fetch');

		//Time Sheet Controller
		Route::resource('timesheets', TimesheetController::class);


		//Award Controller
		Route::get('awards/trash', [AwardController::class, 'trash'])->name('awards.trash');
		Route::post('awards/{id}/restore', [AwardController::class, 'restore'])->name('awards.restore');
		Route::post('awards/bulk_restore', [AwardController::class, 'bulk_restore'])->name('awards.bulk_restore');
		Route::post('awards/bulk_permanent_destroy', [AwardController::class, 'bulk_permanent_destroy'])->name('awards.bulk_permanent_destroy');
		Route::delete('awards/{id}/permanent_destroy', [AwardController::class, 'permanent_destroy'])->name('awards.permanent_destroy');
		Route::post('awards/bulk_destroy', [AwardController::class, 'bulk_destroy'])->name('awards.bulk_destroy');
		Route::resource('awards', AwardController::class);

		// salary advance
		Route::get('salary_advances/trash', [SalaryAdvanceController::class, 'trash'])->name('salary_advances.trash');
		Route::post('salary_advances/{id}/restore', [SalaryAdvanceController::class, 'restore'])->name('salary_advances.restore');
		Route::post('salary_advances/bulk_restore', [SalaryAdvanceController::class, 'bulk_restore'])->name('salary_advances.bulk_restore');
		Route::post('salary_advances/bulk_permanent_destroy', [SalaryAdvanceController::class, 'bulk_permanent_destroy'])->name('salary_advances.bulk_permanent_destroy');
		Route::delete('salary_advances/{id}/permanent_destroy', [SalaryAdvanceController::class, 'permanent_destroy'])->name('salary_advances.permanent_destroy');
		Route::resource('salary_advances', SalaryAdvanceController::class);
		Route::post('salary_advances/bulk_destroy', [SalaryAdvanceController::class, 'bulk_destroy'])->name('salary_advances.bulk_destroy');

		//Payslip Controller
		Route::get('payslips/trash', [PayrollController::class, 'trash'])->name('payslips.trash');
		Route::post('payslips/{id}/restore', [PayrollController::class, 'restore'])->name('payslips.restore');
		Route::post('payslips/bulk_restore', [PayrollController::class, 'bulk_restore'])->name('payslips.bulk_restore');
		Route::delete('payslips/{id}/permanent_destroy', [PayrollController::class, 'permanent_destroy'])->name('payslips.permanent_destroy');
		Route::post('payslips/bulk_permanent_destroy', [PayrollController::class, 'bulk_permanent_destroy'])->name('payslips.bulk_permanent_destroy');
		Route::post('payslips/store_payment', [PayrollController::class, 'store_payment'])->name('payslips.store_payment');
		Route::post('payslips/store_accrual', [PayrollController::class, 'store_accrual'])->name('payslips.store_accrual');
		Route::match(['get', 'post'], 'payslips/accrue', [PayrollController::class, 'accrue_payroll'])->name('payslips.accrue');
		Route::match(['get', 'post'], 'payslips/make_payment', [PayrollController::class, 'make_payment'])->name('payslips.make_payment');
		Route::resource('payslips', PayrollController::class);
		Route::get('payslips_export', [PayrollController::class, 'export_payslips'])->name('payslips.export');
		Route::post('payslips/bulk_approve', [PayrollController::class, 'bulk_approve'])->name('payslips.bulk_approve');
		Route::post('payslips/bulk_reject', [PayrollController::class, 'bulk_reject'])->name('payslips.bulk_reject');
		Route::post('payslips/bulk_delete', [PayrollController::class, 'bulk_delete'])->name('payslips.bulk_delete');
		Route::post('payslips/bulk_accrue', [PayrollController::class, 'bulk_accrue'])->name('payslips.bulk_accrue');
		Route::post('payslips/bulk_payment', [PayrollController::class, 'bulk_payment'])->name('payslips.bulk_payment');

		//Taxes
		Route::get('taxes/trash', [TaxController::class, 'trash'])->name('taxes.trash');
		Route::post('taxes/{id}/restore', [TaxController::class, 'restore'])->name('taxes.restore');
		Route::post('taxes/bulk_restore', [TaxController::class, 'bulk_restore'])->name('taxes.bulk_restore');
		Route::post('taxes/bulk_permanent_destroy', [TaxController::class, 'bulk_permanent_destroy'])->name('taxes.bulk_permanent_destroy');
		Route::delete('taxes/{id}/permanent_destroy', [TaxController::class, 'permanent_destroy'])->name('taxes.permanent_destroy');
		Route::resource('taxes', TaxController::class);
		Route::post('taxes/bulk_destroy', [TaxController::class, 'bulk_destroy'])->name('taxes.bulk_destroy');

		//Report Controller
		Route::get('reports/account_balances', [ReportController::class, 'account_balances'])->name('reports.account_balances');
		Route::match(['get', 'post'], 'reports/account_statement', [ReportController::class, 'account_statement'])->name('reports.account_statement');
		Route::match(['get', 'post'], 'reports/profit_and_loss', [ReportController::class, 'profit_and_loss'])->name('reports.profit_and_loss');
		Route::match(['get', 'post'], 'reports/transactions_report', [ReportController::class, 'transactions_report'])->name('reports.transactions_report');
		Route::match(['get', 'post'], 'reports/income_by_customer', [ReportController::class, 'income_by_customer'])->name('reports.income_by_customer');
		Route::get('reports/income_by_customer_export', [ReportController::class, 'income_by_customer_export'])->name('reports.income_by_customer_export');
		Route::match(['get', 'post'], 'reports/ledger', [ReportController::class, 'ledger'])->name('reports.ledger');
		Route::match(['get', 'post'], 'reports/journal', [ReportController::class, 'journal'])->name('reports.journal');
		Route::match(['get', 'post'], 'reports/income_statement', [ReportController::class, 'income_statement'])->name('reports.income_statement');
		Route::match(['get', 'post'], 'reports/purchase_by_vendor', [ReportController::class, 'purchase_by_vendor'])->name('reports.purchase_by_vendor');
		Route::match(['get', 'post'], 'reports/payroll_report', [ReportController::class, 'payroll_report'])->name('reports.payroll_report');
		Route::match(['get', 'post'], 'reports/tax_report', [ReportController::class, 'tax_report'])->name('reports.tax_report');
		Route::get('reports/ledger_export', [ReportController::class, 'ledger_export'])->name('reports.ledger_export');
		Route::get('reports/gen_journal_export', [ReportController::class, 'gen_journal_export'])->name('reports.gen_journal_export');
		Route::get('reports/journal_export', [ReportController::class, 'journal_export'])->name('reports.journal_export');
		Route::get('reports/income_statement_export', [ReportController::class, 'income_statement_export'])->name('reports.income_statement_export');
		Route::get('reports/trial_balance_export', [ReportController::class, 'trial_balance_export'])->name('reports.trial_balance_export');
		Route::get('reports/balance_sheet_export', [ReportController::class, 'balance_sheet_export'])->name('reports.balance_sheet_export');
		Route::match(['get', 'post'], 'reports/trial_balance', [ReportController::class, 'trial_balance'])->name('reports.trial_balance');
		Route::match(['get', 'post'], 'reports/balance_sheet', [ReportController::class, 'balance_sheet'])->name('reports.balance_sheet');
		Route::match(['get', 'post'], 'reports/receivables', [ReportController::class, 'receivables'])->name('reports.receivables');
		Route::get('reports/receivables_export', [ReportController::class, 'receivables_export'])->name('reports.receivables_export');
		Route::match(['get', 'post'], 'reports/payables', [ReportController::class, 'payables'])->name('reports.payables');
		Route::get('reports/payables_export', [ReportController::class, 'payables_export'])->name('reports.payables_export');
		Route::match(['get', 'post'], 'reports/payroll_summary', [ReportController::class, 'payroll_summary'])->name('reports.payroll_summary');
		Route::get('reports/payroll_summary_export', [ReportController::class, 'payroll_summary_export'])->name('reports.payroll_summary_export');
		Route::match(['get', 'post'], 'reports/payroll_cost', [ReportController::class, 'payroll_cost'])->name('reports.payroll_cost');
		Route::get('reports/payroll_cost_export', [ReportController::class, 'payroll_cost_export'])->name('reports.payroll_cost_export');
		Route::match(['get', 'post'], 'reports/inventory_details', [ReportController::class, 'inventory_details'])->name('reports.inventory_details');
		Route::match(['get', 'post'], 'reports/inventory_summary', [ReportController::class, 'inventory_summary'])->name('reports.inventory_summary');
		Route::match(['get', 'post'], 'reports/sales_by_product', [ReportController::class, 'sales_by_product'])->name('reports.sales_by_product');
		Route::match(['get', 'post'], 'reports/sales_by_product_export', [ReportController::class, 'sales_by_product_export'])->name('reports.sales_by_product_export');
		Route::get('reports/inventory_summary_export', [ReportController::class, 'inventory_summary_export', 'inventory_summary_export'])->name('reports.inventory_summary_export');
		Route::get('reports/inventory_details_export', [ReportController::class, 'inventory_details_export'])->name('reports.inventory_details_export');
	});

	Route::group(['prefix' => 'user'], function () {
		Route::get('find_taxes', [InvoiceController::class, 'find_taxes'])->name('invoices.find_taxes');
		Route::get('find_currency/{name}', [InvoiceController::class, 'find_currency'])->name("invoices.find_currency");
		Route::get('customer/get_invoices/{id}', [InvoiceController::class, 'get_invoices'])->name('customer.get_invoices');
		Route::get('vendor/get_bills/{id}', [PurchaseController::class, 'get_bills'])->name('vendor.get_bills');
		Route::get('prescription_products/{id}', [PrescriptionController::class, 'find_prescription_products'])->name('prescriptions.find_prescription_products');
		Route::post('prescription_products/{id}/cancel', [PrescriptionController::class, 'cancel_prescription_products'])->name('prescriptions.cancel_prescription_products');
		Route::post('prescription_products/{id}/send_to_pos', [PrescriptionController::class, 'send_to_pos'])->name('prescriptions.send_to_pos');
		Route::get('products/findProduct/{id}', [ProductController::class, 'findProduct'])->name("products.find_product");
		Route::get('products/getProducts/{type}', [ProductController::class, 'getProducts']);
		Route::get('products/search_product/{search_term}', [ProductController::class, 'search_product'])->name('products.search_product');
		Route::get('products/getProductByBarcode/{barcode}', [ProductController::class, 'getProductByBarcode']);
		Route::get('customer/get_returns/{id}', [SalesReturnController::class, 'get_returns']);
		Route::get('customer/get_returns/{id}', [PurchaseReturnController::class, 'get_returns']);
		Route::get('receipts/{id}/get_invoice_link', [ReceiptController::class, 'get_receipt_link'])->name('receipts.get_receipt_link');
		Route::get('invoice_pos/{id}', [ReceiptController::class, 'invoice_pos'])->name('receipts.invoice_pos');
		Route::get('credit_invoice_pos/{id}', [ReceiptController::class, 'credit_invoice_pos'])->name('receipts.credit_invoice_pos');
		Route::get('pos/products', [ReceiptController::class, 'pos_products'])->name('receipts.pos_products');
		Route::get('pos/currency', [ReceiptController::class, 'pos_currency'])->name('receipts.pos_currency');
		Route::get('pos/tax', [ReceiptController::class, 'pos_tax'])->name('receipts.pos_tax');
		Route::get('pos/products/category/{id}', [ReceiptController::class, 'pos_products_category'])->name('receipts.pos.category');
		Route::get('customer/get_deffered_invoices/{id}', [DefferedInvoiceController::class, 'get_invoices']);
		Route::get('quotations/{id}/get_quotation_link', [QuotationController::class, 'get_quotation_link'])->name('quotations.get_quotation_link');
		Route::get('/get-subcategories/{main_category_id}', [MainCategoryController::class, 'getSubCategories'])->name('get.subcategories');
	});


	//Switch Business
	Route::get('business/switch_business/{id}', [BusinessController::class, 'switch_business'])->name('business.switch_business');
});

Route::get('users/back_to_admin', [UserController::class, 'back_to_admin'])->name('users.back_to_admin')->middleware('auth');

Route::get('switch_language/', function () {
	if (isset($_GET['language'])) {
		session(['language' => $_GET['language']]);
		return back();
	}
})->name('switch_language');

//Frontend Website
Route::get('/about', [WebsiteController::class, 'about']);
Route::get('/features', [WebsiteController::class, 'features']);
Route::get('/pricing', [WebsiteController::class, 'pricing']);
Route::get('/faq', [WebsiteController::class, 'faq']);
Route::get('/privacy-policy', [WebsiteController::class, 'privacy'])->name('privacy-policy');
Route::get('/terms-condition', [WebsiteController::class, 'terms'])->name('terms-condition');
Route::get('/blogs/{slug?}', [WebsiteController::class, 'blogs']);
Route::get('/contact', [WebsiteController::class, 'contact']);
Route::post('/send_message', 'Website\WebsiteController@send_message');
Route::post('/post_comment', 'Website\WebsiteController@post_comment');
Route::post('/email_subscription', 'Website\WebsiteController@email_subscription');

Route::get('/{slug?}', [WebsiteController::class, 'index']);


//Online Invoice Payment
Route::group(['prefix' => 'callback', 'namespace' => 'User\Gateway'], function () {
	Route::get('paypal', 'PayPal\ProcessController@callback')->name('callback.PayPal');
	Route::post('stripe', 'Stripe\ProcessController@callback')->name('callback.Stripe');
	Route::post('zaad', 'Zaad\ProcessController@callback')->name('callback.Zaad');
	Route::post('edahab', 'Edahab\ProcessController@callback')->name('callback.Edahab');
	Route::post('PremierWaller', 'PremierWaller\ProcessController@callback')->name('callback.PremierWaller');
	Route::post('razorpay', 'Razorpay\ProcessController@callback')->name('callback.Razorpay');
	Route::get('paystack', 'Paystack\ProcessController@callback')->name('callback.Paystack');
	Route::get('flutterwave', 'Flutterwave\ProcessController@callback')->name('callback.Flutterwave');
	Route::get('mollie', 'Mollie\ProcessController@callback')->name('callback.Mollie');
	Route::match(['get', 'post'], 'instamojo', 'Instamojo\ProcessController@callback')->name('callback.Instamojo');
});

//Subscription Payment
Route::group(['prefix' => 'subscription_callback', 'namespace' => 'User\SubscriptionGateway'], function () {
	Route::get('paypal', 'PayPal\ProcessController@callback')->name('subscription_callback.PayPal');
	Route::post('stripe', 'Stripe\ProcessController@callback')->name('subscription_callback.Stripe');
	Route::post('razorpay', 'Razorpay\ProcessController@callback')->name('subscription_callback.Razorpay');
	Route::get('paystack', 'Paystack\ProcessController@callback')->name('subscription_callback.Paystack');
	Route::get('flutterwave', 'Flutterwave\ProcessController@callback')->name('subscription_callback.Flutterwave');
	Route::get('mollie', 'Mollie\ProcessController@callback')->name('subscription_callback.Mollie');
	Route::match(['get', 'post'], 'instamojo', 'Instamojo\ProcessController@callback')->name('subscription_callback.Instamojo');
});

//Accept Invitation
Route::get('system_users/accept_invitation/{id}', [SystemUserController::class, 'accept_invitation'])->name('system_users.accept_invitation');

//Membership Subscription
Route::get('membership/packages', [MembershipController::class, 'packages'])->name('membership.packages')->middleware('auth');
Route::post('membership/choose_package', [MembershipController::class, 'choose_package'])->name('membership.choose_package')->middleware('auth');
Route::get('membership/payment_gateways', [MembershipController::class, 'payment_gateways'])->name('membership.payment_gateways')->middleware('auth');
Route::get('membership/make_payment/{gateway}', [MembershipController::class, 'make_payment'])->name('membership.make_payment')->middleware('auth');

//Public Invoices VIew
Route::get('invoice/make_payment/{short_code}/{gateway}', [OnlinePaymentController::class, 'make_payment'])->name('invoices.make_payment');
Route::get('invoice/payment_methods/{short_code}', [OnlinePaymentController::class, 'payment_methods'])->name('invoices.payment_methods');
Route::get('invoice/{short_code}', [InvoiceController::class, 'show_public_invoice'])->name('invoices.show_public_invoice');
Route::get('cash_purchase/{short_code}', [CashPurchaseController::class, 'show_public_cash_purchase'])->name('cash_purchases.show_public_cash_purchase');
Route::get('purchase_order/{short_code}', [PurchaseOrderController::class, 'show_public_purchase_order'])->name('purchase_orders.show_public_purchase_order');
Route::get('quotation/{short_code}', [QuotationController::class, 'show_public_quotation'])->name('quotations.show_public_quotation');
Route::get('bill_invoice/{short_code}', [PurchaseController::class, 'show_public_bill_invoice'])->name('bill_invoices.show_public_bill_invoice');
Route::get('bill_payment/{id}', [BillPaymentsController::class, 'show_public_bill_payment'])->name('bill_payments.show_public_bill_payment');
Route::get('purchase_return/{short_code}', [PurchaseReturnController::class, 'show_public_purchase_return'])->name('purchase_returns.show_public_purchase_return');
Route::get('cash_invoice/{short_code}', [ReceiptController::class, 'show_public_cash_invoice'])->name('cash_invoices.show_public_cash_invoice');
Route::get('deffered_invoice/{short_code}', [DefferedInvoiceController::class, 'show_public_deffered_invoice'])->name('deffered_invoices.show_public_deffered_invoice');
Route::get('receive_payment/{id}', [ReceivePaymentsController::class, 'show_public_receive_payment'])->name('receive_payments.show_public_receive_payment');
Route::get('sales_return/{short_code}', [SalesReturnController::class, 'show_public_sales_return'])->name('sales_returns.show_public_sales_return');
Route::get('project_subcontract/{short_code}', [ProjectSubcontractController::class, 'show_public_project_subcontract'])->name('project_subcontracts.show_public_project_subcontract');

Route::get('dashboard/json_income_by_category', 'DashboardController@json_income_by_category')->middleware('auth');
Route::get('dashboard/json_expense_by_category', 'DashboardController@json_expense_by_category')->middleware('auth');
Route::get('dashboard/json_cashflow', 'DashboardController@json_cashflow')->middleware('auth');

Route::get('dashboard/json_package_wise_subscription', 'DashboardController@json_package_wise_subscription')->middleware('auth');
Route::get('dashboard/json_yearly_reveneu', 'DashboardController@json_yearly_reveneu')->middleware('auth');

//Social Login
Route::get('/login/{provider}', 'Auth\SocialController@redirect');
Route::get('/login/{provider}/callback', 'Auth\SocialController@callback');

Route::get('/login/{provider}', 'Auth\SocialController@redirect');
Route::get('/login/{provider}/callback', 'Auth\SocialController@callback');
