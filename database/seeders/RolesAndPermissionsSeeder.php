<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

final class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define the permissions that should exist based on routes
        $permissions = [
            // =====================
            // DASHBOARD
            // =====================
            'dashboard.view',
            'dashboard.income_widget',
            'dashboard.expense_widget',
            'dashboard.accounts_receivable_amount_widget',
            'dashboard.accounts_payable_amount_widget',
            'dashboard.cashflow_widget',
            'dashboard.top_customers_widget',
            'dashboard.sales_overview_widget',
            'dashboard.sales_value_widget',
            'dashboard.recent_transaction_widget',
            'dashboard.recent_invoices_widget',
            'dashboard.receivables_vs_payables_widget',

            // =====================
            // BUSINESS
            // =====================
            'business.view',
            'business.create',
            'business.update',
            'business.delete',
            'business.restore',
            'business.settings',
            'business.user.view',
            'business.user.create',
            'business.user.update',
            'business.user.delete',
            'business.roles.view',
            'business.roles.create',
            'business.roles.update',
            'business.roles.delete',

            // =====================
            // CURRENCY
            // =====================
            'currency.view',
            'currency.create',
            'currency.update',
            'currency.delete',
            'currency.restore',

            // =====================
            // AUDIT LOGS
            // =====================
            'audit_logs.view',

            // =====================
            // CUSTOMERS
            // =====================
            'customers.view',
            'customers.create',
            'customers.update',
            'customers.delete',
            'customers.restore',
            'customers.csv.import',
            'customers.csv.export',

            // =====================
            // VENDORS
            // =====================
            'vendors.view',
            'vendors.create',
            'vendors.update',
            'vendors.delete',
            'vendors.restore',
            'vendors.csv.import',
            'vendors.csv.export',

            // =====================
            // PRODUCTS
            // =====================
            'products.view',
            'products.create',
            'products.update',
            'products.delete',
            'products.restore',
            'products.csv.import',
            'products.csv.export',

            // =====================
            // PRODUCT UNITS
            // =====================
            'product_units.view',
            'product_units.create',
            'product_units.update',
            'product_units.delete',
            'product_units.restore',

            // =====================
            // MAIN CATEGORIES
            // =====================
            'main_categories.view',
            'main_categories.create',
            'main_categories.update',
            'main_categories.delete',
            'main_categories.restore',

            // =====================
            // SUB CATEGORIES
            // =====================
            'sub_categories.view',
            'sub_categories.create',
            'sub_categories.update',
            'sub_categories.delete',
            'sub_categories.restore',

            // =====================
            // BRANDS
            // =====================
            'brands.view',
            'brands.create',
            'brands.update',
            'brands.delete',
            'brands.restore',

            // =====================
            // INVENTORY ADJUSTMENTS
            // =====================
            'inventory_adjustments.view',
            'inventory_adjustments.create',
            'inventory_adjustments.update',
            'inventory_adjustments.delete',
            'inventory_adjustments.restore',
            'inventory_adjustments.csv.import',
            'inventory_adjustments.csv.export',

            // =====================
            // INVENTORY TRANSFERS
            // =====================
            'inventory_transfers.view',
            'inventory_transfers.create',
            'inventory_transfers.update',
            'inventory_transfers.delete',
            'inventory_transfers.restore',
            'inventory_transfers.send',
            'inventory_transfers.receive',
            'inventory_transfers.reject',
            'inventory_transfers.cancel',
            'inventory_transfers.csv.import',
            'inventory_transfers.csv.export',

            // =====================
            // INVOICES
            // =====================
            'invoices.view',
            'invoices.create',
            'invoices.update',
            'invoices.delete',
            'invoices.restore',
            'invoices.send_email',
            'invoices.duplicate',
            'invoices.pdf',
            'invoices.csv.import',
            'invoices.csv.export',

            // =====================
            // RECEIVE PAYMENTS
            // =====================
            'receive_payments.view',
            'receive_payments.create',
            'receive_payments.update',
            'receive_payments.delete',
            'receive_payments.pdf',

            // =====================
            // SALES RETURNS
            // =====================
            'sales_returns.view',
            'sales_returns.create',
            'sales_returns.update',
            'sales_returns.delete',
            'sales_returns.restore',
            'sales_returns.refund',
            'sales_returns.send_email',
            'sales_returns.pdf',

            // =====================
            // PURCHASE RETURNS
            // =====================
            'purchase_returns.view',
            'purchase_returns.create',
            'purchase_returns.update',
            'purchase_returns.delete',
            'purchase_returns.restore',
            'purchase_returns.approve',
            'purchase_returns.reject',
            'purchase_returns.refund',
            'purchase_returns.send_email',
            'purchase_returns.pdf',

            // =====================
            // JOURNALS
            // =====================
            'journals.view',
            'journals.create',
            'journals.update',
            'journals.delete',
            'journals.restore',
            'journals.approve',
            'journals.reject',
            'journals.csv.import',
            'journals.csv.export',

            // =====================
            // RECEIPTS (SALES RECEIPTS / POS)
            // =====================
            'receipts.view',
            'receipts.create',
            'receipts.update',
            'receipts.delete',
            'receipts.restore',
            'receipts.send_email',
            'receipts.pos',
            'receipts.pdf',
            'receipts.csv.import',
            'receipts.csv.export',

            // =====================
            // HOLD POS INVOICES
            // =====================
            'hold_pos_invoices.view',
            'hold_pos_invoices.create',
            'hold_pos_invoices.update',
            'hold_pos_invoices.delete',

            // =====================
            // DEFERRED INVOICES
            // =====================
            'deffered_invoices.view',
            'deffered_invoices.create',
            'deffered_invoices.update',
            'deffered_invoices.delete',
            'deffered_invoices.restore',
            'deffered_invoices.send_email',
            'deffered_invoices.pdf',

            // =====================
            // DEFERRED PAYMENTS
            // =====================
            'deffered_receive_payments.view',
            'deffered_receive_payments.create',
            'deffered_receive_payments.update',
            'deffered_receive_payments.delete',

            // =====================
            // QUOTATIONS
            // =====================
            'quotations.view',
            'quotations.create',
            'quotations.update',
            'quotations.delete',
            'quotations.restore',
            'quotations.send_email',
            'quotations.pdf',
            'quotations.duplicate',
            'quotations.convert_to_invoice',

            // =====================
            // BILL INVOICES (PURCHASES)
            // =====================
            'bill_invoices.view',
            'bill_invoices.create',
            'bill_invoices.update',
            'bill_invoices.delete',
            'bill_invoices.restore',
            'bill_invoices.approve',
            'bill_invoices.reject',
            'bill_invoices.duplicate',
            'bill_invoices.send_email',
            'bill_invoices.pdf',
            'bill_invoices.csv.import',
            'bill_invoices.csv.export',

            // =====================
            // BILL PAYMENTS
            // =====================
            'bill_payments.view',
            'bill_payments.create',
            'bill_payments.update',
            'bill_payments.delete',
            'bill_payments.restore',
            'bill_payments.pdf',

            // =====================
            // CASH PURCHASES
            // =====================
            'cash_purchases.view',
            'cash_purchases.create',
            'cash_purchases.update',
            'cash_purchases.delete',
            'cash_purchases.restore',
            'cash_purchases.approve',
            'cash_purchases.reject',
            'cash_purchases.send_email',
            'cash_purchases.pdf',
            'cash_purchases.csv.import',
            'cash_purchases.csv.export',

            // =====================
            // PURCHASE ORDERS
            // =====================
            'purchase_orders.view',
            'purchase_orders.create',
            'purchase_orders.update',
            'purchase_orders.delete',
            'purchase_orders.restore',
            'purchase_orders.duplicate',
            'purchase_orders.send_email',
            'purchase_orders.pdf',
            'purchase_orders.convert_to_bill',
            'purchase_orders.convert_to_cash_purchase',
            'purchase_orders.csv.import',
            'purchase_orders.csv.export',

            // =====================
            // MEDICAL RECORDS
            // =====================
            'medical_records.view',
            'medical_records.create',
            'medical_records.update',
            'medical_records.delete',
            'medical_records.restore',

            // =====================
            // PRESCRIPTIONS
            // =====================
            'prescriptions.view',
            'prescriptions.create',
            'prescriptions.update',
            'prescriptions.delete',
            'prescriptions.restore',
            'prescriptions.change_status',

            // =====================
            // ACCOUNTS
            // =====================
            'accounts.view',
            'accounts.create',
            'accounts.update',
            'accounts.delete',
            'accounts.restore',
            'accounts.statement',
            'accounts.csv.import',
            'accounts.csv.export',

            // =====================
            // TRANSACTION METHODS
            // =====================
            'transaction_methods.view',
            'transaction_methods.create',
            'transaction_methods.update',
            'transaction_methods.delete',
            'transaction_methods.restore',

            // =====================
            // HR - DEPARTMENTS
            // =====================
            'departments.view',
            'departments.create',
            'departments.update',
            'departments.delete',
            'departments.restore',

            // =====================
            // HR - DESIGNATIONS
            // =====================
            'designations.view',
            'designations.create',
            'designations.update',
            'designations.delete',
            'designations.restore',

            // =====================
            // CONSTRUCTION - COST CODES
            // =====================
            // 'cost_codes.view',
            // 'cost_codes.create',
            // 'cost_codes.update',
            // 'cost_codes.delete',
            // 'cost_codes.csv.import',
            // 'cost_codes.csv.export',

            // =====================
            // CONSTRUCTION - PROJECTS
            // =====================
            // 'projects.view',
            // 'projects.create',
            // 'projects.update',
            // 'projects.delete',
            // 'projects.csv.import',
            // 'projects.csv.export',

            // =====================
            // CONSTRUCTION - PROJECT TASKS
            // =====================
            // 'project_tasks.view',
            // 'project_tasks.create',
            // 'project_tasks.update',
            // 'project_tasks.delete',
            // 'project_tasks.csv.import',
            // 'project_tasks.csv.export',

            // =====================
            // CONSTRUCTION - PROJECT BUDGETS
            // =====================
            // 'project_budgets.view',
            // 'project_budgets.create',
            // 'project_budgets.update',
            // 'project_budgets.delete',
            // 'project_budgets.csv.import',
            // 'project_budgets.csv.export',

            // =====================
            // CONSTRUCTION - PROJECT GROUPS
            // =====================
            // 'project_groups.view',
            // 'project_groups.create',
            // 'project_groups.update',
            // 'project_groups.delete',
            // 'project_groups.csv.import',
            // 'project_groups.csv.export',

            // =====================
            // CONSTRUCTION - PROJECT SUBCONTRACTS
            // =====================
            // 'project_subcontracts.view',
            // 'project_subcontracts.create',
            // 'project_subcontracts.update',
            // 'project_subcontracts.delete',
            // 'project_subcontracts.send_email',
            // 'project_subcontracts.csv.import',
            // 'project_subcontracts.csv.export',

            // =====================
            // CONSTRUCTION - SUBCONTRACT PAYMENTS
            // =====================
            // 'project_subcontract_payments.view',
            // 'project_subcontract_payments.create',
            // 'project_subcontract_payments.update',
            // 'project_subcontract_payments.delete',

            // =====================
            // CONSTRUCTION - CHANGE ORDERS
            // =====================
            // 'change_orders.create',
            // 'change_orders.update',
            // 'change_orders.delete',

            // =====================
            // HR - STAFF
            // =====================
            'staffs.view',
            'staffs.create',
            'staffs.update',
            'staffs.delete',
            'staffs.restore',
            'staffs.csv.import',
            'staffs.csv.export',

            // =====================
            // HR - STAFF DOCUMENTS
            // =====================
            'staff_documents.view',
            'staff_documents.create',
            'staff_documents.update',
            'staff_documents.delete',

            // =====================
            // HR - HOLIDAYS
            // =====================
            'holidays.view',
            'holidays.create',
            'holidays.update',
            'holidays.delete',
            'holidays.restore',

            // =====================
            // HR - LEAVE TYPES
            // =====================
            'leave_types.view',
            'leave_types.create',
            'leave_types.update',
            'leave_types.delete',

            // =====================
            // HR - LEAVES
            // =====================
            'leaves.view',
            'leaves.create',
            'leaves.update',
            'leaves.delete',
            'leaves.restore',
            'leaves.approve',
            'leaves.reject',

            // =====================
            // HR - ATTENDANCE DEVICES
            // =====================
            'attendance_devices.view',
            'attendance_devices.create',
            'attendance_devices.update',
            'attendance_devices.delete',

            // =====================
            // HR - ATTENDANCE LOGS
            // =====================
            'attendance_logs.view',
            'attendance_logs.create',
            'attendance_logs.csv.import',
            'attendance_logs.csv.export',

            // =====================
            // HR - TIMESHEETS
            // =====================
            'timesheets.view',
            'timesheets.create',
            'timesheets.update',
            'timesheets.delete',

            // =====================
            // HR - AWARDS
            // =====================
            'awards.view',
            'awards.create',
            'awards.update',
            'awards.delete',
            'awards.restore',

            // =====================
            // HR - SALARY ADVANCES
            // =====================
            'salary_advances.view',
            'salary_advances.create',
            'salary_advances.update',
            'salary_advances.delete',
            'salary_advances.restore',

            // =====================
            // HR - PAYSLIPS (PAYROLL)
            // =====================
            'payslips.view',
            'payslips.create',
            'payslips.update',
            'payslips.delete',
            'payslips.restore',
            'payslips.approve',
            'payslips.reject',
            'payslips.accrue',
            'payslips.make_payment',
            'payslips.csv.export',

            // =====================
            // TAXES
            // =====================
            'taxes.view',
            'taxes.create',
            'taxes.update',
            'taxes.delete',
            'taxes.restore',

            // =====================
            // REPORTS
            // =====================
            'reports.account_balances',
            'reports.account_statement',
            'reports.profit_and_loss',
            'reports.profit_and_loss.export',
            'reports.transactions_report',
            'reports.income_by_customer',
            'reports.income_by_customer.export',
            'reports.ledger',
            'reports.ledger.export',
            'reports.journal',
            'reports.journal.export',
            'reports.gen_journal.export',
            'reports.income_statement',
            'reports.income_statement.export',
            'reports.income_statement.pdf',
            'reports.purchase_by_vendor',
            'reports.payroll_report',
            'reports.tax_report',
            'reports.trial_balance',
            'reports.trial_balance.export',
            'reports.balance_sheet',
            'reports.balance_sheet.export',
            'reports.receivables',
            'reports.receivables.export',
            'reports.receivables.pdf',
            'reports.payables',
            'reports.payables.export',
            'reports.payroll_summary',
            'reports.payroll_summary.export',
            'reports.payroll_cost',
            'reports.payroll_cost.export',
            'reports.inventory_details',
            'reports.inventory_details.export',
            'reports.inventory_summary',
            'reports.inventory_summary.export',
            'reports.sales_by_product',
            'reports.sales_by_product.export',
        ];

        // Delete permissions that are not in the seeder list
        $permissionNames = array_map(fn ($name) => $name, $permissions);
        Permission::where('guard_name', 'web')
            ->whereNotIn('name', $permissionNames)
            ->delete();

        // Create or update permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create roles and assign permissions
        $owner = Role::firstOrCreate(['name' => 'Owner', 'guard_name' => 'web']);
        $owner->syncPermissions(Permission::all());

        // Admin Role - Full access to most business operations
        $admin = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $admin->syncPermissions([
            // Dashboard
            'dashboard.view',
            'dashboard.income_widget',
            'dashboard.expense_widget',
            'dashboard.accounts_receivable_amount_widget',
            'dashboard.accounts_payable_amount_widget',
            'dashboard.cashflow_widget',
            'dashboard.top_customers_widget',
            'dashboard.sales_overview_widget',
            'dashboard.sales_value_widget',
            'dashboard.recent_transaction_widget',
            'dashboard.recent_invoices_widget',
            'dashboard.receivables_vs_payables_widget',

            // Business
            'business.view',
            'business.create',
            'business.update',
            'business.delete',
            'business.restore',
            'business.settings',
            'business.user.view',
            'business.user.create',
            'business.user.update',
            'business.user.delete',
            'business.roles.view',
            'business.roles.create',
            'business.roles.update',
            'business.roles.delete',

            // Currency
            'currency.view',
            'currency.create',
            'currency.update',
            'currency.delete',
            'currency.restore',

            // Audit Logs
            'audit_logs.view',

            // Customers
            'customers.view',
            'customers.create',
            'customers.update',
            'customers.delete',
            'customers.restore',
            'customers.csv.import',
            'customers.csv.export',

            // Vendors
            'vendors.view',
            'vendors.create',
            'vendors.update',
            'vendors.delete',
            'vendors.restore',
            'vendors.csv.import',
            'vendors.csv.export',

            // Products
            'products.view',
            'products.create',
            'products.update',
            'products.delete',
            'products.restore',
            'products.csv.import',
            'products.csv.export',

            // Product Units
            'product_units.view',
            'product_units.create',
            'product_units.update',
            'product_units.delete',
            'product_units.restore',

            // Categories
            'main_categories.view',
            'main_categories.create',
            'main_categories.update',
            'main_categories.delete',
            'main_categories.restore',
            'sub_categories.view',
            'sub_categories.create',
            'sub_categories.update',
            'sub_categories.delete',
            'sub_categories.restore',

            // Brands
            'brands.view',
            'brands.create',
            'brands.update',
            'brands.delete',
            'brands.restore',

            // Inventory
            'inventory_adjustments.view',
            'inventory_adjustments.create',
            'inventory_adjustments.update',
            'inventory_adjustments.delete',
            'inventory_adjustments.restore',
            'inventory_adjustments.csv.import',
            'inventory_adjustments.csv.export',
            'inventory_transfers.view',
            'inventory_transfers.create',
            'inventory_transfers.update',
            'inventory_transfers.delete',
            'inventory_transfers.restore',
            'inventory_transfers.send',
            'inventory_transfers.receive',
            'inventory_transfers.reject',
            'inventory_transfers.cancel',
            'inventory_transfers.csv.import',
            'inventory_transfers.csv.export',

            // Invoices
            'invoices.view',
            'invoices.create',
            'invoices.update',
            'invoices.delete',
            'invoices.restore',
            'invoices.send_email',
            'invoices.duplicate',
            'invoices.pdf',
            'invoices.csv.import',
            'invoices.csv.export',

            // Receive Payments
            'receive_payments.view',
            'receive_payments.create',
            'receive_payments.update',
            'receive_payments.delete',
            'receive_payments.pdf',

            // Sales Returns
            'sales_returns.view',
            'sales_returns.create',
            'sales_returns.update',
            'sales_returns.delete',
            'sales_returns.restore',
            'sales_returns.refund',
            'sales_returns.send_email',
            'sales_returns.pdf',

            // Purchase Returns
            'purchase_returns.view',
            'purchase_returns.create',
            'purchase_returns.update',
            'purchase_returns.delete',
            'purchase_returns.restore',
            'purchase_returns.approve',
            'purchase_returns.reject',
            'purchase_returns.refund',
            'purchase_returns.send_email',
            'purchase_returns.pdf',

            // Journals
            'journals.view',
            'journals.create',
            'journals.update',
            'journals.delete',
            'journals.restore',
            'journals.approve',
            'journals.reject',
            'journals.csv.import',
            'journals.csv.export',

            // Receipts / POS
            'receipts.view',
            'receipts.create',
            'receipts.update',
            'receipts.delete',
            'receipts.restore',
            'receipts.send_email',
            'receipts.pos',
            'receipts.pdf',
            'receipts.csv.import',
            'receipts.csv.export',
            'hold_pos_invoices.view',
            'hold_pos_invoices.create',
            'hold_pos_invoices.update',
            'hold_pos_invoices.delete',

            // Deferred Invoices
            'deffered_invoices.view',
            'deffered_invoices.create',
            'deffered_invoices.update',
            'deffered_invoices.delete',
            'deffered_invoices.restore',
            'deffered_invoices.send_email',
            'deffered_invoices.pdf',
            'deffered_receive_payments.view',
            'deffered_receive_payments.create',
            'deffered_receive_payments.update',
            'deffered_receive_payments.delete',

            // Quotations
            'quotations.view',
            'quotations.create',
            'quotations.update',
            'quotations.delete',
            'quotations.restore',
            'quotations.send_email',
            'quotations.pdf',
            'quotations.duplicate',
            'quotations.convert_to_invoice',

            // Bill Invoices
            'bill_invoices.view',
            'bill_invoices.create',
            'bill_invoices.update',
            'bill_invoices.delete',
            'bill_invoices.restore',
            'bill_invoices.approve',
            'bill_invoices.reject',
            'bill_invoices.duplicate',
            'bill_invoices.send_email',
            'bill_invoices.pdf',
            'bill_invoices.csv.import',
            'bill_invoices.csv.export',

            // Bill Payments
            'bill_payments.view',
            'bill_payments.create',
            'bill_payments.update',
            'bill_payments.delete',
            'bill_payments.restore',
            'bill_payments.pdf',

            // Cash Purchases
            'cash_purchases.view',
            'cash_purchases.create',
            'cash_purchases.update',
            'cash_purchases.delete',
            'cash_purchases.restore',
            'cash_purchases.approve',
            'cash_purchases.reject',
            'cash_purchases.send_email',
            'cash_purchases.pdf',
            'cash_purchases.csv.import',
            'cash_purchases.csv.export',

            // Purchase Orders
            'purchase_orders.view',
            'purchase_orders.create',
            'purchase_orders.update',
            'purchase_orders.delete',
            'purchase_orders.restore',
            'purchase_orders.duplicate',
            'purchase_orders.send_email',
            'purchase_orders.pdf',
            'purchase_orders.convert_to_bill',
            'purchase_orders.convert_to_cash_purchase',
            'purchase_orders.csv.import',
            'purchase_orders.csv.export',

            // Accounts
            'accounts.view',
            'accounts.create',
            'accounts.update',
            'accounts.delete',
            'accounts.restore',
            'accounts.statement',
            'accounts.csv.import',
            'accounts.csv.export',

            // Transaction Methods
            'transaction_methods.view',
            'transaction_methods.create',
            'transaction_methods.update',
            'transaction_methods.delete',
            'transaction_methods.restore',

            // HR - Departments & Designations
            'departments.view',
            'departments.create',
            'departments.update',
            'departments.delete',
            'departments.restore',
            'designations.view',
            'designations.create',
            'designations.update',
            'designations.delete',
            'designations.restore',

            // HR - Staff
            'staffs.view',
            'staffs.create',
            'staffs.update',
            'staffs.delete',
            'staffs.restore',
            'staffs.csv.import',
            'staffs.csv.export',
            'staff_documents.view',
            'staff_documents.create',
            'staff_documents.update',
            'staff_documents.delete',

            // HR - Holidays & Leaves
            'holidays.view',
            'holidays.create',
            'holidays.update',
            'holidays.delete',
            'holidays.restore',
            'leave_types.view',
            'leave_types.create',
            'leave_types.update',
            'leave_types.delete',
            'leaves.view',
            'leaves.create',
            'leaves.update',
            'leaves.delete',
            'leaves.restore',
            'leaves.approve',
            'leaves.reject',

            // HR - Attendance Devices & Timesheets
            'attendance_devices.view',
            'attendance_devices.create',
            'attendance_devices.update',
            'attendance_devices.delete',
            'attendance_logs.view',
            'attendance_logs.create',
            'attendance_logs.csv.import',
            'attendance_logs.csv.export',
            'timesheets.view',
            'timesheets.create',
            'timesheets.update',
            'timesheets.delete',

            // HR - Awards & Salary Advances
            'awards.view',
            'awards.create',
            'awards.update',
            'awards.delete',
            'awards.restore',
            'salary_advances.view',
            'salary_advances.create',
            'salary_advances.update',
            'salary_advances.delete',
            'salary_advances.restore',

            // Payroll
            'payslips.view',
            'payslips.create',
            'payslips.update',
            'payslips.delete',
            'payslips.restore',
            'payslips.approve',
            'payslips.reject',
            'payslips.accrue',
            'payslips.make_payment',
            'payslips.csv.export',

            // Taxes
            'taxes.view',
            'taxes.create',
            'taxes.update',
            'taxes.delete',
            'taxes.restore',

            // Reports
            'reports.account_balances',
            'reports.account_statement',
            'reports.profit_and_loss',
            'reports.profit_and_loss.export',
            'reports.transactions_report',
            'reports.income_by_customer',
            'reports.income_by_customer.export',
            'reports.ledger',
            'reports.ledger.export',
            'reports.journal',
            'reports.journal.export',
            'reports.gen_journal.export',
            'reports.income_statement',
            'reports.income_statement.export',
            'reports.income_statement.pdf',
            'reports.purchase_by_vendor',
            'reports.payroll_report',
            'reports.tax_report',
            'reports.trial_balance',
            'reports.trial_balance.export',
            'reports.balance_sheet',
            'reports.balance_sheet.export',
            'reports.receivables',
            'reports.receivables.export',
            'reports.receivables.pdf',
            'reports.payables',
            'reports.payables.export',
            'reports.payroll_summary',
            'reports.payroll_summary.export',
            'reports.payroll_cost',
            'reports.payroll_cost.export',
            'reports.inventory_details',
            'reports.inventory_details.export',
            'reports.inventory_summary',
            'reports.inventory_summary.export',
            'reports.sales_by_product',
            'reports.sales_by_product.export',

            // Medical
            'medical_records.view',
            'medical_records.create',
            'medical_records.update',
            'medical_records.delete',
            'medical_records.restore',
            'prescriptions.view',
            'prescriptions.create',
            'prescriptions.update',
            'prescriptions.delete',
            'prescriptions.restore',
            'prescriptions.change_status',
        ]);

        // Manager Role - Operational access, no delete/restore, limited approvals
        $manager = Role::firstOrCreate(['name' => 'Manager', 'guard_name' => 'web']);
        $manager->syncPermissions([
            // Dashboard
            'dashboard.view',
            'dashboard.income_widget',
            'dashboard.expense_widget',
            'dashboard.accounts_receivable_amount_widget',
            'dashboard.accounts_payable_amount_widget',
            'dashboard.cashflow_widget',
            'dashboard.top_customers_widget',
            'dashboard.sales_overview_widget',
            'dashboard.sales_value_widget',
            'dashboard.recent_transaction_widget',
            'dashboard.recent_invoices_widget',
            'dashboard.receivables_vs_payables_widget',

            // Customers
            'customers.view',
            'customers.create',
            'customers.update',
            'customers.csv.import',
            'customers.csv.export',

            // Vendors
            'vendors.view',
            'vendors.create',
            'vendors.update',
            'vendors.csv.import',
            'vendors.csv.export',

            // Products
            'products.view',
            'products.create',
            'products.update',
            'products.csv.import',
            'products.csv.export',

            // Product Units
            'product_units.view',
            'product_units.create',
            'product_units.update',

            // Categories
            'main_categories.view',
            'main_categories.create',
            'main_categories.update',
            'sub_categories.view',
            'sub_categories.create',
            'sub_categories.update',

            // Brands
            'brands.view',
            'brands.create',
            'brands.update',

            // Inventory
            'inventory_adjustments.view',
            'inventory_adjustments.create',
            'inventory_adjustments.update',
            'inventory_adjustments.csv.import',
            'inventory_adjustments.csv.export',
            'inventory_transfers.view',
            'inventory_transfers.create',
            'inventory_transfers.update',
            'inventory_transfers.send',
            'inventory_transfers.receive',
            'inventory_transfers.reject',
            'inventory_transfers.cancel',
            'inventory_transfers.csv.import',
            'inventory_transfers.csv.export',

            // Invoices
            'invoices.view',
            'invoices.create',
            'invoices.update',
            'invoices.send_email',
            'invoices.duplicate',
            'invoices.pdf',
            'invoices.csv.import',
            'invoices.csv.export',

            // Receive Payments
            'receive_payments.view',
            'receive_payments.create',
            'receive_payments.update',
            'receive_payments.pdf',

            // Sales Returns
            'sales_returns.view',
            'sales_returns.create',
            'sales_returns.update',
            'sales_returns.refund',
            'sales_returns.send_email',
            'sales_returns.pdf',

            // Purchase Returns
            'purchase_returns.view',
            'purchase_returns.create',
            'purchase_returns.update',
            'purchase_returns.approve',
            'purchase_returns.reject',
            'purchase_returns.refund',
            'purchase_returns.send_email',
            'purchase_returns.pdf',

            // Journals
            'journals.view',
            'journals.create',
            'journals.update',
            'journals.approve',
            'journals.reject',
            'journals.csv.import',
            'journals.csv.export',

            // Receipts / POS
            'receipts.view',
            'receipts.create',
            'receipts.update',
            'receipts.send_email',
            'receipts.pos',
            'receipts.pdf',
            'receipts.csv.import',
            'receipts.csv.export',
            'hold_pos_invoices.view',
            'hold_pos_invoices.create',
            'hold_pos_invoices.update',

            // Deferred Invoices
            'deffered_invoices.view',
            'deffered_invoices.create',
            'deffered_invoices.update',
            'deffered_invoices.send_email',
            'deffered_invoices.pdf',
            'deffered_receive_payments.view',
            'deffered_receive_payments.create',
            'deffered_receive_payments.update',

            // Quotations
            'quotations.view',
            'quotations.create',
            'quotations.update',
            'quotations.send_email',
            'quotations.pdf',
            'quotations.duplicate',
            'quotations.convert_to_invoice',

            // Bill Invoices
            'bill_invoices.view',
            'bill_invoices.create',
            'bill_invoices.update',
            'bill_invoices.approve',
            'bill_invoices.reject',
            'bill_invoices.duplicate',
            'bill_invoices.send_email',
            'bill_invoices.pdf',
            'bill_invoices.csv.import',
            'bill_invoices.csv.export',

            // Bill Payments
            'bill_payments.view',
            'bill_payments.create',
            'bill_payments.update',
            'bill_payments.pdf',

            // Cash Purchases
            'cash_purchases.view',
            'cash_purchases.create',
            'cash_purchases.update',
            'cash_purchases.approve',
            'cash_purchases.reject',
            'cash_purchases.send_email',
            'cash_purchases.pdf',
            'cash_purchases.csv.import',
            'cash_purchases.csv.export',

            // Purchase Orders
            'purchase_orders.view',
            'purchase_orders.create',
            'purchase_orders.update',
            'purchase_orders.duplicate',
            'purchase_orders.send_email',
            'purchase_orders.pdf',
            'purchase_orders.convert_to_bill',
            'purchase_orders.convert_to_cash_purchase',
            'purchase_orders.csv.import',
            'purchase_orders.csv.export',

            // Accounts
            'accounts.view',
            'accounts.create',
            'accounts.update',
            'accounts.statement',
            'accounts.csv.import',
            'accounts.csv.export',

            // Transaction Methods
            'transaction_methods.view',
            'transaction_methods.create',
            'transaction_methods.update',

            // HR - Departments & Designations
            'departments.view',
            'departments.create',
            'departments.update',
            'designations.view',
            'designations.create',
            'designations.update',

            // HR - Staff
            'staffs.view',
            'staffs.create',
            'staffs.update',
            'staffs.csv.import',
            'staffs.csv.export',
            'staff_documents.view',
            'staff_documents.create',
            'staff_documents.update',

            // HR - Holidays & Leaves
            'holidays.view',
            'holidays.create',
            'holidays.update',
            'leave_types.view',
            'leave_types.create',
            'leave_types.update',
            'leaves.view',
            'leaves.create',
            'leaves.update',
            'leaves.approve',
            'leaves.reject',

            // HR - Attendance Devices & Timesheets
            'attendance_devices.view',
            'attendance_devices.create',
            'attendance_devices.update',
            'attendance_logs.view',
            'attendance_logs.create',
            'attendance_logs.csv.import',
            'attendance_logs.csv.export',
            'timesheets.view',
            'timesheets.create',
            'timesheets.update',

            // HR - Awards & Salary Advances
            'awards.view',
            'awards.create',
            'awards.update',
            'salary_advances.view',
            'salary_advances.create',
            'salary_advances.update',

            // Payroll
            'payslips.view',
            'payslips.create',
            'payslips.update',
            'payslips.approve',
            'payslips.reject',
            'payslips.accrue',
            'payslips.make_payment',
            'payslips.csv.export',

            // Taxes
            'taxes.view',
            'taxes.create',
            'taxes.update',

            // Reports
            'reports.account_balances',
            'reports.account_statement',
            'reports.profit_and_loss',
            'reports.profit_and_loss.export',
            'reports.transactions_report',
            'reports.income_by_customer',
            'reports.income_by_customer.export',
            'reports.ledger',
            'reports.ledger.export',
            'reports.journal',
            'reports.journal.export',
            'reports.gen_journal.export',
            'reports.income_statement',
            'reports.income_statement.export',
            'reports.income_statement.pdf',
            'reports.purchase_by_vendor',
            'reports.payroll_report',
            'reports.tax_report',
            'reports.trial_balance',
            'reports.trial_balance.export',
            'reports.balance_sheet',
            'reports.balance_sheet.export',
            'reports.receivables',
            'reports.receivables.export',
            'reports.receivables.pdf',
            'reports.payables',
            'reports.payables.export',
            'reports.payroll_summary',
            'reports.payroll_summary.export',
            'reports.payroll_cost',
            'reports.payroll_cost.export',
            'reports.inventory_details',
            'reports.inventory_details.export',
            'reports.inventory_summary',
            'reports.inventory_summary.export',
            'reports.sales_by_product',
            'reports.sales_by_product.export',

            // Medical
            'medical_records.view',
            'medical_records.create',
            'medical_records.update',
            'prescriptions.view',
            'prescriptions.create',
            'prescriptions.update',
            'prescriptions.change_status',
        ]);

        // User Role - Basic operational access
        $user = Role::firstOrCreate(['name' => 'User', 'guard_name' => 'web']);
        $user->syncPermissions([
            // Dashboard
            'dashboard.view',
            'dashboard.income_widget',
            'dashboard.expense_widget',
            'dashboard.accounts_receivable_amount_widget',
            'dashboard.accounts_payable_amount_widget',
            'dashboard.cashflow_widget',
            'dashboard.top_customers_widget',
            'dashboard.sales_overview_widget',
            'dashboard.sales_value_widget',
            'dashboard.recent_transaction_widget',
            'dashboard.recent_invoices_widget',
            'dashboard.receivables_vs_payables_widget',

            // Customers
            'customers.view',
            'customers.create',
            'customers.update',
            'customers.csv.import',
            'customers.csv.export',

            // Vendors
            'vendors.view',
            'vendors.create',
            'vendors.update',
            'vendors.csv.import',
            'vendors.csv.export',

            // Products
            'products.view',
            'products.create',
            'products.update',
            'products.csv.import',
            'products.csv.export',

            // Product Units
            'product_units.view',
            'product_units.create',
            'product_units.update',

            // Categories
            'main_categories.view',
            'main_categories.create',
            'main_categories.update',
            'sub_categories.view',
            'sub_categories.create',
            'sub_categories.update',

            // Brands
            'brands.view',
            'brands.create',
            'brands.update',

            // Inventory
            'inventory_adjustments.view',
            'inventory_adjustments.create',
            'inventory_adjustments.update',
            'inventory_adjustments.csv.import',
            'inventory_adjustments.csv.export',
            'inventory_transfers.view',
            'inventory_transfers.create',
            'inventory_transfers.update',
            'inventory_transfers.send',
            'inventory_transfers.receive',
            'inventory_transfers.reject',
            'inventory_transfers.cancel',
            'inventory_transfers.csv.import',
            'inventory_transfers.csv.export',

            // Invoices
            'invoices.view',
            'invoices.create',
            'invoices.update',
            'invoices.send_email',
            'invoices.duplicate',
            'invoices.pdf',
            'invoices.csv.import',
            'invoices.csv.export',

            // Receive Payments
            'receive_payments.view',
            'receive_payments.create',
            'receive_payments.update',
            'receive_payments.pdf',

            // Sales Returns
            'sales_returns.view',
            'sales_returns.create',
            'sales_returns.update',
            'sales_returns.refund',
            'sales_returns.send_email',
            'sales_returns.pdf',

            // Purchase Returns
            'purchase_returns.view',
            'purchase_returns.create',
            'purchase_returns.update',
            'purchase_returns.approve',
            'purchase_returns.reject',
            'purchase_returns.refund',
            'purchase_returns.send_email',
            'purchase_returns.pdf',

            // Receipts / POS
            'receipts.view',
            'receipts.create',
            'receipts.update',
            'receipts.send_email',
            'receipts.pos',
            'receipts.pdf',
            'receipts.csv.import',
            'receipts.csv.export',
            'hold_pos_invoices.view',
            'hold_pos_invoices.create',
            'hold_pos_invoices.update',

            // Quotations
            'quotations.view',
            'quotations.create',
            'quotations.update',
            'quotations.send_email',
            'quotations.pdf',
            'quotations.duplicate',
            'quotations.convert_to_invoice',

            // Bill Invoices
            'bill_invoices.view',
            'bill_invoices.create',
            'bill_invoices.update',
            'bill_invoices.approve',
            'bill_invoices.reject',
            'bill_invoices.duplicate',
            'bill_invoices.send_email',
            'bill_invoices.pdf',
            'bill_invoices.csv.import',
            'bill_invoices.csv.export',

            // Bill Payments
            'bill_payments.view',
            'bill_payments.create',
            'bill_payments.update',
            'bill_payments.pdf',

            // Cash Purchases
            'cash_purchases.view',
            'cash_purchases.create',
            'cash_purchases.update',
            'cash_purchases.approve',
            'cash_purchases.reject',
            'cash_purchases.send_email',
            'cash_purchases.pdf',
            'cash_purchases.csv.import',
            'cash_purchases.csv.export',

            // Purchase Orders
            'purchase_orders.view',
            'purchase_orders.create',
            'purchase_orders.update',
            'purchase_orders.duplicate',
            'purchase_orders.send_email',
            'purchase_orders.pdf',
            'purchase_orders.convert_to_bill',
            'purchase_orders.convert_to_cash_purchase',
            'purchase_orders.csv.import',
            'purchase_orders.csv.export',

            // Transaction Methods
            'transaction_methods.view',
            'transaction_methods.create',
            'transaction_methods.update',

            // Reports
            'reports.receivables',
            'reports.receivables.export',
            'reports.receivables.pdf',
            'reports.payables',
            'reports.payables.export',
            'reports.inventory_details',
            'reports.inventory_details.export',
            'reports.inventory_summary',
            'reports.inventory_summary.export',
            'reports.sales_by_product',
            'reports.sales_by_product.export',

            // Medical
            'medical_records.view',
            'medical_records.create',
            'medical_records.update',
            'prescriptions.view',
            'prescriptions.create',
            'prescriptions.update',
            'prescriptions.change_status',
        ]);

        // Assign Owner role to existing owner users
        User::where('owner', 1)->each(function (User $user): void {
            if (!$user->hasRole('Owner')) {
                $user->assignRole('Owner');
            }
        });

        // Clear permission cache after all assignments
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
