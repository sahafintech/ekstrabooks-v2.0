<?php

use App\Http\Controllers\AccountTypeController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\BillPaymentsController;
use App\Http\Controllers\BrandsController;
use App\Http\Controllers\BusinessTypeController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DefferedInvoiceController;
use App\Http\Controllers\DefferedPaymentController;
use App\Http\Controllers\EmailSubscriberController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\FeatureController;
use App\Http\Controllers\HoldPosInvoiceController;
use App\Http\Controllers\User\ImportController;
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
use App\Http\Controllers\User\AttendanceController;
use App\Http\Controllers\User\AwardController;
use App\Http\Controllers\User\BusinessController;
use App\Http\Controllers\User\BusinessSettingsController;
use App\Http\Controllers\User\CashPurchaseController;
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
		Route::get('users/get_table_data', [UserController::class, 'get_table_data']);
		Route::resource('users', UserController::class)->middleware("demo:PUT|PATCH|DELETE");

		//Subscription Payments
		Route::get('subscription_payments/get_table_data', [SubscriptionPaymentController::class, 'get_table_data']);
		Route::resource('subscription_payments', SubscriptionPaymentController::class)->middleware("demo:PUT|PATCH|DELETE");

		Route::group(['middleware' => 'demo'], function () {
			//Package Controller
			Route::resource('packages', PackageController::class);

			// user packages
			Route::resource('user_packages', UserPackageController::class);

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
			Route::get('email_subscribers/get_table_data', [EmailSubscriberController::class, 'get_table_data']);
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

		// Business Settings Route (with tab parameter)
		Route::get('business/{id}/settings/{tab?}', [BusinessSettingsController::class, 'settings'])->name('business.settings');

		Route::get('business/{id}/pos_settings', [BusinessSettingsController::class, 'pos_settings'])->name('business.pos_settings');
		Route::post('business/{id}/pos_settings', [BusinessSettingsController::class, 'store_pos_settings'])->name('business.store_pos_settings');

		//Currency List
		Route::resource('currency', CurrencyController::class);
		Route::post('currency/bulk_destroy', [CurrencyController::class, 'bulk_destroy'])->name('currency.bulk_destroy');

		// audit logs
		Route::get('audit_logs', [AuditLogController::class, 'index'])->name('audit_logs.index');
	});

	/** Dynamic Permission **/
	Route::group(['middleware' => ['permission'], 'prefix' => 'user'], function () {

		//Dashboard Widget
		Route::get('dashboard/income_widget', 'DashboardController@income_widget')->name('dashboard.income_widget');
		Route::get('dashboard/expense_widget', 'DashboardController@expense_widget')->name('dashboard.expense_widget');
		Route::get('dashboard/accounts_receivable_amount_widget', 'DashboardController@accounts_receivable_amount_widget')->name('dashboard.accounts_receivable_amount_widget');
		Route::get('dashboard/accounts_payable_amount_widget', 'DashboardController@accounts_payable_amount_widget')->name('dashboard.accounts_payable_amount_widget');
		Route::get('dashboard/cashflow_widget', 'DashboardController@cashflow_widget')->name('dashboard.cashflow_widget');
		Route::get('dashboard/top_customers_widget', 'DashboardController@top_customers_widget')->name('dashboard.top_customers_widget');
		Route::get('dashboard/sales_overview_widget', 'DashboardController@sales_overview_widget')->name('dashboard.sales_overview_widget');
		Route::get('dashboard/sales_value_widget', 'DashboardController@sales_value_widget')->name('dashboard.sales_value_widget');
		Route::get('dashboard/recent_transaction_widget', 'DashboardController@recent_transaction_widget')->name('dashboard.recent_transaction_widget');
		Route::get('dashboard/recent_invoices_widget', 'DashboardController@recent_invoices_widget')->name('dashboard.recent_invoices_widget');
		Route::get('dashboard/receivables_vs_payables_widget', 'DashboardController@receivables_vs_payables_widget')->name('dashboard.receivables_vs_payables_widget');

		//Customers
		Route::resource('customers', CustomerController::class);
		Route::post('import_customers', [CustomerController::class, 'import_customers'])->name('customers.import');
		Route::post('customers/bulk_destroy', [CustomerController::class, 'bulk_destroy'])->name('customers.bulk_destroy');
		Route::get('export_customers', [CustomerController::class, 'export_customers'])->name('customers.export');

		//Vendors
		Route::get('vendors/get_table_data', [VendorController::class, 'get_table_data']);
		Route::resource('vendors', VendorController::class);
		Route::post('import_suppliers', [VendorController::class, 'import_vendors'])->name('vendors.import');
		Route::get('export_suppliers', [VendorController::class, 'export_vendors'])->name('vendors.export');
		Route::post('vendors/bulk_destroy', [VendorController::class, 'bulk_destroy'])->name('vendors.bulk_destroy');

		//Product Controller
		Route::resource('product_units', ProductUnitController::class)->except('show');
		Route::post('product_units/bulk_destroy', [ProductUnitController::class, 'bulk_destroy'])->name('product_units.bulk_destroy');
		Route::get('products/get_table_data', [ProductController::class, 'get_table_data']);
		Route::get('products/export', [ProductController::class, 'product_export'])->name('products.export');
		Route::resource('products', ProductController::class);
		Route::post('import_products', [ProductController::class, 'import_products'])->name('products.import');
		Route::post('products/bulk_destroy', [ProductController::class, 'bulk_destroy'])->name('products.bulk_destroy');

		// categories
		Route::resource('sub_categories', SubCategoryController::class);
		Route::post('sub_categories/bulk_destroy', [SubCategoryController::class, 'bulk_destroy'])->name('sub_categories.bulk_destroy');
		Route::resource('main_categories', MainCategoryController::class);
		Route::post('main_categories/bulk_destroy', [MainCategoryController::class, 'bulk_destroy'])->name('main_categories.bulk_destroy');

		// brands
		Route::resource('brands', BrandsController::class);
		Route::post('brands/bulk_destroy', [BrandsController::class, 'bulk_destroy'])->name('brands.bulk_destroy');

		// Inventory Adjustment Import (MUST BE BEFORE RESOURCE ROUTE)
		Route::get('inventory_adjustments/import', [InventoryAdjustmentController::class, 'import'])->name('inventory_adjustments.import');
		Route::post('inventory_adjustments/import/store', [InventoryAdjustmentController::class, 'importStore'])->name('inventory_adjustments.import.store');
		Route::get('inventory_adjustments/import/progress', [InventoryAdjustmentController::class, 'importProgress'])->name('inventory_adjustments.import.progress');
		Route::get('inventory_adjustments/import/status', [InventoryAdjustmentController::class, 'importStatus'])->name('inventory_adjustments.import.status');
		Route::post('inventory_adjustments/import/process', [InventoryAdjustmentController::class, 'importProcess'])->name('inventory_adjustments.import.process');
		Route::get('inventory_adjustments/export', [InventoryAdjustmentController::class, 'export'])->name('inventory_adjustments.export');

		// inventory adjustments resource route
		Route::resource('inventory_adjustments', InventoryAdjustmentController::class);
		Route::post('inventory_adjustments/bulk_destroy', [InventoryAdjustmentController::class, 'bulk_destroy'])->name('inventory_adjustments.bulk_destroy');

		//Invoices
		Route::match(['get', 'post'], 'invoices/{id}/send_email', [InvoiceController::class, 'send_email'])->name('invoices.send_email');
		Route::match(['get', 'post'], 'invoices/receive_payment', [InvoiceController::class, 'receive_payment'])->name('invoices.receive_payment');
		Route::get('invoices/{id}/duplicate', [InvoiceController::class, 'duplicate'])->name('invoices.duplicate');
		Route::get('invoices/{id}/get_invoice_link', [InvoiceController::class, 'get_invoice_link'])->name('invoices.get_invoice_link');
		Route::post('invoices/get_table_data', [InvoiceController::class, 'get_table_data']);
		Route::resource('invoices', InvoiceController::class);
		Route::resource('receive_payments', ReceivePaymentsController::class);
		Route::post('import_invoices', [InvoiceController::class, 'import_invoices'])->name('invoices.import');
		Route::post('invoices/filter', [InvoiceController::class, 'invoices_filter'])->name('invoices.filter');
		Route::post('invoices/bulk_destroy', [InvoiceController::class, 'bulk_destroy'])->name('invoices.bulk_destroy');
		Route::get('export_invoices', [InvoiceController::class, 'export_invoices'])->name('invoices.export');

		// Sales Return
		Route::resource('sales_returns', SalesReturnController::class);
		Route::post('sales_returns/refund/store/{id}', [SalesReturnController::class, 'refund_store'])->name('sales_returns.refund.store');
		Route::post('sales_returns/bulk_destroy', [SalesReturnController::class, 'bulk_destroy'])->name('sales_returns.bulk_destroy');
		Route::match(['get', 'post'], 'sales_returns/{id}/send_email', [SalesReturnController::class, 'send_email'])->name('sales_returns.send_email');

		// purchase return
		Route::resource('purchase_returns', PurchaseReturnController::class);
		Route::get('purchase_returns/refund/{id}', [PurchaseReturnController::class, 'refund'])->name('purchase_returns.refund');
		Route::post('purchase_returns/refund/store/{id}', [PurchaseReturnController::class, 'refund_store'])->name('purchase_returns.refund.store');
		Route::post('purchase_returns/bulk_destroy', [PurchaseReturnController::class, 'bulk_destroy'])->name('purchase_returns.bulk_destroy');
		Route::match(['get', 'post'], 'purchase_returns/{id}/send_email', [PurchaseReturnController::class, 'send_email'])->name('purchase_returns.send_email');

		// Journal Entry
		Route::resource('journals', JournalController::class);
		Route::post('import_journal', [JournalController::class, 'import_journal'])->name('journals.import');
		Route::get('journal/export/{id}', [JournalController::class, 'export_journal'])->name('journals.export');
		Route::post('journal/bulk_destroy', [JournalController::class, 'bulk_destroy'])->name('journals.bulk_destroy');
		Route::post('journal/bulk_approve', [JournalController::class, 'bulk_approve'])->name('journals.bulk_approve');
		Route::post('journal/bulk_reject', [JournalController::class, 'bulk_reject'])->name('journals.bulk_reject');

		// Sales Receipts
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
		Route::post('recurring_invoices/get_table_data', [RecurringInvoiceController::class, 'get_table_data']);
		Route::resource('recurring_invoices', RecurringInvoiceController::class);

		// Deffered Invoices
		Route::resource('deffered_invoices', DefferedInvoiceController::class);
		Route::get('deffered_invoices/payments/{id}', [DefferedInvoiceController::class, 'payments'])->name('deffered_invoices.payments');
		Route::get('deffered_invoices/earnings/{id}', [DefferedInvoiceController::class, 'earnings'])->name('deffered_invoices.earnings');
		Route::post('deffered_invoices/filter', [DefferedInvoiceController::class, 'deffered_invoices_filter'])->name('deffered_invoices.filter');
		Route::post('deffered_invoices/bulk_destroy', [DefferedInvoiceController::class, 'bulk_destroy'])->name('deffered_invoices.bulk_destroy');
		Route::post('deffered_invoices/send_email/{id}', [DefferedInvoiceController::class, 'send_email'])->name('deffered_invoices.send_email');
		Route::get('deffered_invoices/{id}/', [DefferedInvoiceController::class, 'show_public_deffered_invoice'])->name('deffered_invoices.show_public_deffered_invoice');

		// deffered payments
		Route::resource('deffered_receive_payments', DefferedPaymentController::class);

		// insurance benefits
		Route::resource('insurance_benefits', InsuranceBenefitController::class);

		// insurance family sizes
		Route::resource('insurance_family_sizes', InsuranceFamilySizeController::class);

		//Quotation
		Route::match(['get', 'post'], 'quotations/{id}/send_email', [QuotationController::class, 'send_email'])->name('quotations.send_email');
		Route::get('quotations/{id}/convert_to_invoice', [QuotationController::class, 'convert_to_invoice'])->name('quotations.convert_to_invoice');
		Route::get('quotations/{id}/duplicate', [QuotationController::class, 'duplicate'])->name('quotations.duplicate');
		Route::post('quotations/bulk_destroy', [QuotationController::class, 'bulk_destroy'])->name('quotations.bulk_destroy');
		Route::resource('quotations', QuotationController::class);

		//Bills
		Route::match(['get', 'post'], 'bill_invoices/pay_bill', [PurchaseController::class, 'pay_bill'])->name('billI_invoices.pay_bill');
		Route::get('purchases/{id}/duplicate', [PurchaseController::class, 'duplicate'])->name('bill_invoices.duplicate');
		Route::resource('bill_invoices', PurchaseController::class);
		Route::get('vendor/get_bills/{id}', [PurchaseController::class, 'get_bills'])->name('vendor.get_bills');
		Route::post('import_bills', [PurchaseController::class, 'import_bills'])->name('bill_invoices.import');
		Route::post('bill_invoices/filter', [PurchaseController::class, 'bill_invoices_filter'])->name('bill_invoices.filter');
		Route::resource('bill_payments', BillPaymentsController::class);
		Route::post('bill_payments/bulk_destroy', [BillPaymentsController::class, 'bulk_destroy'])->name('bill_payments.bulk_destroy');
		Route::get('export_bill_invoices', [PurchaseController::class, 'export_bill_invoices'])->name('bill_invoices.export');
		Route::post('bill_invoices/bulk_approve', [PurchaseController::class, 'bulk_approve'])->name('bill_invoices.bulk_approve');
		Route::post('bill_invoices/bulk_reject', [PurchaseController::class, 'bulk_reject'])->name('bill_invoices.bulk_reject');
		Route::post('bill_invoices/bulk_destroy', [PurchaseController::class, 'bulk_destroy'])->name('bill_invoices.bulk_destroy');
		Route::get('bill_invoices/{id}/', [PurchaseController::class, 'show_public_bill_invoice'])->name('bill_invoices.show_public_bill_invoice');
		Route::post('bill_invoices/send_email/{id}', [PurchaseController::class, 'send_email'])->name('bill_invoices.send_email');

		//Cash Purchases
		Route::resource('cash_purchases', CashPurchaseController::class);
		Route::post('cash_purchases/filter', [CashPurchaseController::class, 'cash_purchases_filter'])->name('cash_purchases.filter');
		Route::post('import_cash_purchases', [CashPurchaseController::class, 'import_cash_purchases'])->name('cash_purchases.import');
		Route::post('all_bills', [CashPurchaseController::class, 'bills_all'])->name('cash_purchases.all');
		Route::get('export_bills', [CashPurchaseController::class, 'export_bills'])->name('cash_purchases.export');
		Route::get('cash_purchases/voucher/{id}', [CashPurchaseController::class, 'voucher'])->name('cash_purchases.voucher');
		Route::post('cash_purchases/bulk_approve', [CashPurchaseController::class, 'bulk_approve'])->name('cash_purchases.bulk_approve');
		Route::post('cash_purchases/bulk_reject', [CashPurchaseController::class, 'bulk_reject'])->name('cash_purchases.bulk_reject');
		Route::post('cash_purchases/bulk_destroy', [CashPurchaseController::class, 'bulk_destroy'])->name('cash_purchases.bulk_destroy');
		Route::post('cash_purchases/{id}/send_email', [CashPurchaseController::class, 'send_email'])->name('cash_purchases.send_email');
		Route::get('cash_purchases/{id}/', [CashPurchaseController::class, 'show_public_cash_purchase'])->name('cash_purchases.show_public_cash_purchase');

		// purchase orders
		Route::get('purchase_orders/{id}/duplicate', [PurchaseOrderController::class, 'duplicate'])->name('purchase_orders.duplicate');
		Route::resource('purchase_orders', PurchaseOrderController::class);
		Route::post('import_purchase_orders', [PurchaseOrderController::class, 'import_purchase_orders'])->name('purchase_orders.import');
		Route::post('purchase_orders/filter', [PurchaseOrderController::class, 'purchase_orders_filter'])->name('purchase_orders.filter');
		Route::post('purchase_oders/bulk_destroy', [PurchaseOrderController::class, 'bulk_destroy'])->name('purchase_orders.bulk_destroy');
		Route::get('export_purchase_orders', [PurchaseOrderController::class, 'export_purchase_orders'])->name('purchase_orders.export');
		Route::post('purchase_orders/{id}/convert_to_bill', [PurchaseOrderController::class, 'convert_to_bill'])->name('purchase_orders.convert_to_bill');
		Route::post('purchase_orders/{id}/convert_to_cash', [PurchaseOrderController::class, 'convert_to_cash_purchase'])->name('purchase_orders.convert_to_cash_purchase');
		Route::post('purchase_orders/{id}/send_email', [PurchaseOrderController::class, 'send_email'])->name('purchase_orders.send_email');
		Route::get('purchase_orders/{id}/', [PurchaseOrderController::class, 'show_public_purchase_order'])->name('purchase_orders.show_public_purchase_order');

		// medical records
		Route::resource('medical_records', MedicalRecordController::class);

		// prescriptions
		Route::resource('prescriptions', PrescriptionController::class);
		Route::post('prescriptions/{id}', [PrescriptionController::class, 'change_status'])->name('prescriptions.change_status');
		Route::get('prescriptions/{id}/edit_status', [PrescriptionController::class, 'edit_status'])->name('prescriptions.edit_status');

		//Accounts
		Route::get('accounts/{accountId}/{amount}/convert_due_amount', [AccountController::class, 'convert_due_amount']);
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
		Route::get('transactions/get_table_data', [TransactionController::class, 'get_table_data']);
		Route::resource('transactions', TransactionController::class);

		//Transaction Methods
		Route::resource('transaction_methods', TransactionMethodController::class)->except('view');
		Route::post('transaction_methods/bulk_destroy', [TransactionMethodController::class, 'bulk_destroy'])->name('transaction_methods.bulk_destroy');

		//HR Module
		Route::resource('departments', DepartmentController::class);
		Route::post('departments/bulk_destroy', [DepartmentController::class, 'bulk_destroy'])->name('departments.bulk_destroy');
		Route::get('designations/get_designations/{deaprtment_id}', [DesignationController::class, 'get_designations']);
		Route::resource('designations', DesignationController::class)->except('show');
		Route::post('designations/bulk_destroy', [DesignationController::class, 'bulk_destroy'])->name('designations.bulk_destroy');
		// Route::get('salary_scales/get_saylary_scales/{designation_id}', [SalaryScaleController::class, 'get_salary_scales']);
		// Route::get('salary_scales/filter_by_department/{department_id}', [SalaryScaleController::class, 'index'])->name('salary_scales.filter_by_department');
		// Route::resource('salary_scales', SalaryScaleController::class);
		// Route::resource('benefits', BenefitController::class);

		//Staff Controller
		Route::post('staffs/bulk_destroy', [StaffController::class, 'bulk_destroy'])->name('staffs.bulk_destroy');
		Route::resource('staffs', StaffController::class);
		Route::post('import_staffs', [StaffController::class, 'import_staffs'])->name('staffs.import');
		Route::get('export_staffs', [StaffController::class, 'export_staffs'])->name('staffs.export');

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
		Route::get('holidays/get_table_data', [HolidayController::class, 'get_table_data']);
		Route::match(['get', 'post'], 'holidays/weekends', [HolidayController::class, 'weekends'])->name('holidays.weekends');
		Route::resource('holidays', HolidayController::class)->except('show');

		//Leave Application
		Route::resource('leave_types', LeaveTypeController::class)->except('show');
		Route::post('leaves/bulk_destroy', [LeaveController::class, 'bulk_destroy'])->name('leaves.bulk_destroy');
		Route::resource('leaves', LeaveController::class);

		//Attendance Controller
		Route::get('attendance/get_table_data', [AttendanceController::class, 'get_table_data']);
		Route::post('attendance/create', [AttendanceController::class, 'create'])->name('attendance.create');
		Route::resource('attendance', AttendanceController::class)->except('show', 'edit', 'update', 'destroy');
		Route::post('import_attendance', [AttendanceController::class, 'import_attendance'])->name('attendances.import');
		Route::get('absent_fine', [AttendanceController::class, 'absent_fine'])->name('attendances.absent_fine');
		Route::post('absent_fine', [AttendanceController::class, 'store_absent_fine'])->name('attendances.store_absent_fine');

		//Award Controller
		Route::post('awards/bulk_destroy', [AwardController::class, 'bulk_destroy'])->name('awards.bulk_destroy');
		Route::resource('awards', AwardController::class);

		//Payslip Controller
		Route::post('payslips/store_payment', [PayrollController::class, 'store_payment'])->name('payslips.store_payment');
		Route::post('payslips/store_accrual', [PayrollController::class, 'store_accrual'])->name('payslips.store_accrual');
		Route::match(['get', 'post'], 'payslips/accrue', [PayrollController::class, 'accrue_payroll'])->name('payslips.accrue');
		Route::match(['get', 'post'], 'payslips/make_payment', [PayrollController::class, 'make_payment'])->name('payslips.make_payment');
		Route::get('payslips/get_table_data', [PayrollController::class, 'get_table_data']);
		Route::resource('payslips', PayrollController::class);
		Route::get('payslips_export', [PayrollController::class, 'export_payslips'])->name('payslips.export');
		Route::post('payslips/bulk_approve', [PayrollController::class, 'bulk_approve'])->name('payslips.bulk_approve');
		Route::post('payslips/bulk_reject', [PayrollController::class, 'bulk_reject'])->name('payslips.bulk_reject');
		Route::post('payslips/bulk_delete', [PayrollController::class, 'bulk_delete'])->name('payslips.bulk_delete');
		Route::post('payslips/bulk_accrue', [PayrollController::class, 'bulk_accrue'])->name('payslips.bulk_accrue');
		Route::post('payslips/bulk_payment', [PayrollController::class, 'bulk_payment'])->name('payslips.bulk_payment');

		//Taxes
		Route::resource('taxes', TaxController::class);
		Route::post('taxes/bulk_destroy', [TaxController::class, 'bulk_destroy'])->name('taxes.bulk_destroy');

		//Report Controller
		Route::get('reports/account_balances', [ReportController::class, 'account_balances'])->name('reports.account_balances');
		Route::match(['get', 'post'], 'reports/account_statement', [ReportController::class, 'account_statement'])->name('reports.account_statement');
		Route::match(['get', 'post'], 'reports/profit_and_loss', [ReportController::class, 'profit_and_loss'])->name('reports.profit_and_loss');
		Route::match(['get', 'post'], 'reports/transactions_report', [ReportController::class, 'transactions_report'])->name('reports.transactions_report');
		Route::match(['get', 'post'], 'reports/income_by_customer', [ReportController::class, 'income_by_customer'])->name('reports.income_by_customer');
		Route::match(['get', 'post'], 'reports/ledger', [ReportController::class, 'ledger'])->name('reports.ledger');
		Route::match(['get', 'post'], 'reports/journal', [ReportController::class, 'journal'])->name('reports.journal');
		Route::match(['get', 'post'], 'reports/income_statement', [ReportController::class, 'income_statement'])->name('reports.income_statement');
		Route::match(['get', 'post'], 'reports/purchase_by_vendor', [ReportController::class, 'purchase_by_vendor'])->name('reports.purchase_by_vendor');
		Route::match(['get', 'post'], 'reports/attendance_report', [ReportController::class, 'attendance_report'])->name('reports.attendance_report');
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
		Route::match(['get', 'post'], 'reports/payroll_summary', [ReportController::class, 'payroll_summary'])->name('reports.payroll_summary');
		Route::match(['get', 'post'], 'reports/payroll_cost', [ReportController::class, 'payroll_cost'])->name('reports.payroll_cost');
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
		Route::get('prescription_products/{id}', [PrescriptionController::class, 'find_prescription_products'])->name('prescriptions.find_prescription_products');
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

	//Ajax Select2 Controller
	Route::get('ajax/get_table_data', 'Select2Controller@get_table_data');
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

//Public Invoice VIew
Route::get('invoice/make_payment/{short_code}/{gateway}', [OnlinePaymentController::class, 'make_payment'])->name('invoices.make_payment');
Route::get('invoice/payment_methods/{short_code}', [OnlinePaymentController::class, 'payment_methods'])->name('invoices.payment_methods');
Route::get('invoice/{short_code}/{export?}', [InvoiceController::class, 'show_public_invoice'])->name('invoices.show_public_invoice');

//Public Purchase Order VIew
Route::get('purchase_order/{short_code}/{export?}', [PurchaseOrderController::class, 'show_public_purchase_order'])->name('purchase_orders.show_public_purchase_order');

//Public Quotation VIew
Route::get('quotations/{short_code}/{export?}', [QuotationController::class, 'show_public_quotation'])->name('quotations.show_public_quotation');

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
