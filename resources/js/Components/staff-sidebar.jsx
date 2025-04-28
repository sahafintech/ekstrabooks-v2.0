"use client";

import * as React from "react";
import {
  PieChart,
  Users,
  Package,
  GroupIcon,
  ChartPieIcon,
  Building2Icon,
} from "lucide-react";
import { usePage } from "@inertiajs/react";

import { NavOperations } from "@/components/nav-operations";
import { NavManagement } from "@/components/nav-management";
import { NavUser } from "@/components/nav-user";
import { BusinessSwitcher } from "@/components/business-switcher";
import { NavUserDashboard } from "@/components/nav-user-dashboard";
import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarHeader,
  SidebarRail,
} from "@/components/ui/sidebar";

export function StaffSidebar(props) {
  const { auth, activeBusiness, permissionList } = usePage().props;

  // helper to check current route + wildcard params
  const isRoute = React.useCallback(
    (name) => {
      if (route().current(name)) return true;
      const currentPath = window.location.pathname;
      const base = route(name).split("?")[0];
      return new RegExp(`^${base}(\\/|$)`).test(currentPath);
    },
    []
  );

  // Generic filter: only keep sections/items the user has permission for,
  // and compute isActive on each.
  const filterSections = React.useCallback(
    (sections) =>
      sections
        .map((section) => {
          // does user have *any* of the section's routes?
          const canViewSection = section.permissions.some((perm) =>
            permissionList.some((p) => p.permission === perm)
          );
          if (!canViewSection) return null;

          // filter and annotate child items
          const items = section.items
            .filter((item) => {
              const perms = Array.isArray(item.permission)
                ? item.permission
                : [item.permission];
              return perms.some((perm) =>
                permissionList.some((p) => p.permission === perm)
              );
            })
            .map((item) => ({
              ...item,
              isActive: (Array.isArray(item.permission)
                ? item.permission
                : [item.permission]
              ).some(isRoute),
            }));

          if (items.length === 0) return null;

          return {
            title: section.title,
            url: section.url || "#",
            icon: section.icon,
            isActive: section.permissions.some(isRoute),
            items,
          };
        })
        .filter(Boolean),
    [permissionList, isRoute]
  );

  const dashboardItems = React.useMemo(
    () => [
      {
        title: "Dashboard",
        url: route("dashboard.index"),
        icon: PieChart,
        isActive: isRoute("dashboard.index"),
      },
    ],
    [isRoute]
  );  

  // 2. Operations sections
  const navOperationsItems = React.useMemo(
    () =>
      filterSections([
        {
          title: "Products",
          icon: Package,
          permissions: [
            "products.index",
            "products.create",
            "sub_categories.index",
            "main_categories.index",
            "brands.index",
            "product_units.index",
            "inventory_adjustments.index",
          ],
          items: [
            {
              title: "All Products",
              url: route("products.index"),
              permission: ["products.index", "products.create"],
            },
            {
              title: "Main Categories",
              url: route("main_categories.index"),
              permission: "main_categories.index",
            },
            {
              title: "Sub Categories",
              url: route("sub_categories.index"),
              permission: "sub_categories.index",
            },
            {
              title: "Brands",
              url: route("brands.index"),
              permission: "brands.index",
            },
            {
              title: "Units",
              url: route("product_units.index"),
              permission: "product_units.index",
            },
            {
              title: "Inventory Adjustment",
              url: route("inventory_adjustments.index"),
              permission: "inventory_adjustments.index",
            },
          ],
        },
        {
          title: "Suppliers",
          icon: Users,
          permissions: [
            "vendors.index",
            "purchase_orders.index",
            "cash_purchases.index",
            "bill_invoices.index",
            "bill_payments.index",
            "purchase_returns.index",
          ],
          items: [
            {
              title: "All Suppliers",
              url: route("vendors.index"),
              permission: "vendors.index",
            },
            {
              title: "Purchase Order",
              url: route("purchase_orders.index"),
              permission: "purchase_orders.index",
            },
            {
              title: "Cash Purchase",
              url: route("cash_purchases.index"),
              permission: "cash_purchases.index",
            },
            {
              title: "Bill Invoice",
              url: route("bill_invoices.index"),
              permission: "bill_invoices.index",
            },
            {
              title: "Pay Bills",
              url: route("bill_payments.index"),
              permission: "bill_payments.index",
            },
            {
              title: "Purchase Return",
              url: route("purchase_returns.index"),
              permission: "purchase_returns.index",
            },
          ],
        },
        {
          title: "Customers",
          icon: Users,
          permissions: [
            "customers.index",
            "receipts.index",
            "invoices.index",
            "medical_records.index",
            "quotations.index",
            "deffered_invoices.index",
            "sales_returns.index",
            "prescriptions.index",
            "receive_payments.index",
          ],
          items: [
            {
              title: "All Customers",
              url: route("customers.index"),
              permission: "customers.index",
            },
            {
              title: "Cash Invoice",
              url: route("receipts.index"),
              permission: "receipts.index",
            },
            {
              title: "Credit Invoice",
              url: route("invoices.index"),
              permission: "invoices.index",
            },
            {
              title: "Medical Records",
              url: route("medical_records.index"),
              permission: "medical_records.index",
            },
            {
              title: "Prescriptions",
              url: route("prescriptions.index"),
              permission: "prescriptions.index",
            },
            {
              title: "Deffered Invoice",
              url: route("deffered_invoices.index"),
              permission: "deffered_invoices.index",
            },
            {
              title: "Received Payment",
              url: route("receive_payments.index"),
              permission: "receive_payments.index",
            },
            {
              title: "Sales Return",
              url: route("sales_returns.index"),
              permission: "sales_returns.index",
            },
            {
              title: "Quotations",
              url: route("quotations.index"),
              permission: "quotations.index",
            },
          ],
        },
      ]),
    [filterSections]
  );

  // 3. Management sections
  const navManagementItems = React.useMemo(
    () =>
      filterSections([
        {
          title: "HR & Payroll",
          icon: GroupIcon,
          permissions: [
            "staffs.index",
            "attendance.index",
            "departments.index",
            "designations.index",
            "payslips.index",
            "holidays.index",
            "leaves.index",
            "awards.index",
          ],
          items: [
            { title: "Staff Management", url: route("staffs.index"), permission: "staffs.index" },
            { title: "Attendance", url: route("attendance.index"), permission: "attendance.index" },
            { title: "Departments", url: route("departments.index"), permission: "departments.index" },
            { title: "Designations", url: route("designations.index"), permission: "designations.index" },
            { title: "Manage Payroll", url: route("payslips.index"), permission: "payslips.index" },
            { title: "Holidays", url: route("holidays.index"), permission: "holidays.index" },
            { title: "Leave Management", url: route("leaves.index"), permission: "leaves.index" },
            { title: "Awards", url: route("awards.index"), permission: "awards.index" },
          ],
        },
        {
          title: "Accounting",
          icon: ChartPieIcon,
          permissions: ["accounts.index", "journals.index", "transaction_methods.index"],
          items: [
            { title: "Chart of Accounts", url: route("accounts.index"), permission: "accounts.index" },
            { title: "Journal Entry", url: route("journals.index"), permission: "journals.index" },
            { title: "Transaction Methods", url: route("transaction_methods.index"), permission: "transaction_methods.index" },
          ],
        },
        {
          title: "Business",
          icon: Building2Icon,
          permissions: [
            "business.index",
            "roles.index",
            "business.settings",
            "taxes.index",
            "currency.index",
            "audit_logs.index",
          ],
          items: [
            { title: "Manage Businesses", url: route("business.index"), permission: "business.index" },
            { title: "Roles & Permissions", url: route("roles.index"), permission: "roles.index" },
            {
              title: "Business Settings",
              url: route("business.settings", activeBusiness.id),
              permission: "business.settings",
            },
            { title: "Tax Settings", url: route("taxes.index"), permission: "taxes.index" },
            { title: "Currency Settings", url: route("currency.index"), permission: "currency.index" },
            { title: "Audit Logs", url: route("audit_logs.index"), permission: "audit_logs.index" },
          ],
        },
        {
          title: "Reports",
          icon: Building2Icon,
          permissions: [
            "reports.journal",
            "reports.ledger",
            "reports.income_statement",
            "reports.trial_balance",
            "reports.balance_sheet",
            "reports.receivables",
            "reports.payables",
            "reports.payroll_summary",
            "reports.payroll_cost",
            "reports.income_by_customer",
          ],
          items: [
            { title: "General Journal", url: route("reports.journal"), permission: "reports.journal" },
            { title: "General Ledger", url: route("reports.ledger"), permission: "reports.ledger" },
            { title: "Income Statement", url: route("reports.income_statement"), permission: "reports.income_statement" },
            { title: "Trial Balance", url: route("reports.trial_balance"), permission: "reports.trial_balance" },
            { title: "Balance Sheet", url: route("reports.balance_sheet"), permission: "reports.balance_sheet" },
            { title: "Income By Customer", url: route("reports.income_by_customer"), permission: "reports.income_by_customer" },
            { title: "Receivables", url: route("reports.receivables"), permission: "reports.receivables" },
            { title: "Payables", url: route("reports.payables"), permission: "reports.payables" },
            { title: "Payroll Summary", url: route("reports.payroll_summary"), permission: "reports.payroll_summary" },
            { title: "Monthly Payroll Cost", url: route("reports.payroll_cost"), permission: "reports.payroll_cost" },
          ],
        },
      ]),
    [filterSections, activeBusiness]
  );

  // user data for footer
  const user = {
    id: auth.user.id,
    name: auth.user.name,
    email: auth.user.email,
    avatar: `/uploads/media/${auth.user.profile_picture}`,
  };

  return (
    <Sidebar collapsible="icon" {...props}>
      <SidebarHeader>
        <BusinessSwitcher />
      </SidebarHeader>

      <SidebarContent>
        {dashboardItems.length > 0 && (
          <NavUserDashboard items={dashboardItems} />
        )}

        {navOperationsItems.length > 0 && (
          <NavOperations items={navOperationsItems} />
        )}

        {navManagementItems.length > 0 && (
          <NavManagement items={navManagementItems} />
        )}
      </SidebarContent>

      <SidebarFooter>
        <NavUser user={user} />
      </SidebarFooter>

      <SidebarRail />
    </Sidebar>
  );
}
