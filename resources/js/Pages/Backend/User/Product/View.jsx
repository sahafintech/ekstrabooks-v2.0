import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { SidebarInset } from "@/Components/ui/sidebar";
import PageHeader from "@/Components/PageHeader";
import { Label } from "@/Components/ui/label";
import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { Link } from '@inertiajs/react';
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from "@/Components/ui/table";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/Components/ui/tabs";
import { formatCurrency } from '@/lib/utils';

const ProductView = ({ product, transactions, suppliers, id, activeTab }) => {

  const [currentTab, setCurrentTab] = useState(activeTab);

  const ProductStatusBadge = ({ status }) => {
    const statusMap = {
      1: { label: "Active", className: "text-green-600 bg-green-200 px-3 py-1 rounded text-sm" },
      0: { label: "Disabled", className: "text-red-600 bg-red-200 px-3 py-1 rounded text-sm" },
    };

    return (
      <span className={statusMap[status].className}>
        {statusMap[status].label}
      </span>
    );
  };
  

  // Handle tab change
  const handleTabChange = (value) => {
    setCurrentTab(value);
    router.get(
        route("products.show", id),
        { tab: value },
        { preserveState: true }
    );
};

  return (
    <AuthenticatedLayout>
      <SidebarInset>
        <PageHeader page="Products" subpage="View" url="products.index" />

        <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
          <div className="flex justify-end gap-2">
            <Link href={route('products.edit', id)}>
              <Button variant="outline">Edit Product</Button>
            </Link>
            <Link href={route('products.index')}>
              <Button>Back to Products</Button>
            </Link>
          </div>

          <Tabs defaultValue={currentTab} onValueChange={handleTabChange} className="w-full">
            <TabsList className="mb-4">
              <TabsTrigger value="details">Product Details</TabsTrigger>
              <TabsTrigger value="transactions">Transactions</TabsTrigger>
              <TabsTrigger value="suppliers">Suppliers</TabsTrigger>
            </TabsList>

            <TabsContent value="details" className="space-y-4">
              <div>
                <div className="pt-6">
                  <div className="grid grid-cols-12 mt-2">
                    <Label className="md:col-span-2 col-span-12">Image</Label>
                    <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                      <div className="md:w-1/2 w-full">
                        <img
                          src={`/uploads/media/${product.image}`}
                          alt={product.name}
                          className="w-full h-48 object-cover rounded-lg border"
                        />
                      </div>
                    </div>
                  </div>

                  <div className="grid grid-cols-12 mt-2">
                    <Label className="md:col-span-2 col-span-12">Name</Label>
                    <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                      <p className="text-sm">{product.name}</p>
                    </div>
                  </div>

                  <div className="grid grid-cols-12 mt-2">
                    <Label className="md:col-span-2 col-span-12">Type</Label>
                    <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                      <p className="text-sm capitalize">{product.type}</p>
                    </div>
                  </div>

                  <div className="grid grid-cols-12 mt-2">
                    <Label className="md:col-span-2 col-span-12">Unit</Label>
                    <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                      <p className="text-sm">{product.product_unit?.unit || 'N/A'}</p>
                    </div>
                  </div>

                  <div className="grid grid-cols-12 mt-2">
                    <Label className="md:col-span-2 col-span-12">Code</Label>
                    <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                      <p className="text-sm">{product.code || 'N/A'}</p>
                    </div>
                  </div>

                  <div className="grid grid-cols-12 mt-2">
                    <Label className="md:col-span-2 col-span-12">Description</Label>
                    <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                      <p className="text-sm">{product.descriptions || 'N/A'}</p>
                    </div>
                  </div>

                  <div className="grid grid-cols-12 mt-2">
                    <Label className="md:col-span-2 col-span-12">Category</Label>
                    <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                      <p className="text-sm">{product.category?.name || 'N/A'}</p>
                    </div>
                  </div>

                  <div className="grid grid-cols-12 mt-2">
                    <Label className="md:col-span-2 col-span-12">Brand</Label>
                    <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                      <p className="text-sm">{product.brand?.name || 'N/A'}</p>
                    </div>
                  </div>

                  <div className="grid grid-cols-12 mt-2">
                    <Label className="md:col-span-2 col-span-12">Expiry Date</Label>
                    <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                      <p className="text-sm">{product.expiry_date || 'N/A'}</p>
                    </div>
                  </div>

                  <div className="grid grid-cols-12 mt-2">
                    <Label className="md:col-span-2 col-span-12">Stock Management</Label>
                    <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                      <Badge variant={product.stock_management ? 'success' : 'secondary'}>
                        {product.stock_management ? 'Enabled' : 'Disabled'}
                      </Badge>
                    </div>
                  </div>

                  <div className="grid grid-cols-12 mt-2">
                    <Label className="md:col-span-2 col-span-12">Current Stock</Label>
                    <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                      <p className="text-sm">{product.stock}</p>
                    </div>
                  </div>

                  <div className="grid grid-cols-12 mt-2">
                    <Label className="md:col-span-2 col-span-12">Initial Stock</Label>
                    <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                      <p className="text-sm">{product.initial_stock}</p>
                    </div>
                  </div>

                  <div className="grid grid-cols-12 mt-2">
                    <Label className="md:col-span-2 col-span-12">Reorder Point</Label>
                    <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                      <p className="text-sm">{product.reorder_point || 'Not set'}</p>
                    </div>
                  </div>

                  <div className="grid grid-cols-12 mt-2">
                    <Label className="md:col-span-2 col-span-12">Selling</Label>
                    <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                      <div className="flex items-center gap-4">
                        <Badge variant={product.allow_for_selling ? 'success' : 'secondary'}>
                          {product.allow_for_selling ? 'Enabled' : 'Disabled'}
                        </Badge>
                        {product.allow_for_selling && (
                          <p className="text-sm">Price: {formatCurrency({ amount: product.selling_price })}</p>
                        )}
                      </div>
                    </div>
                  </div>

                  <div className="grid grid-cols-12 mt-2">
                    <Label className="md:col-span-2 col-span-12">Purchasing</Label>
                    <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                      <div className="flex items-center gap-4">
                        <Badge variant={product.allow_for_purchasing ? 'success' : 'secondary'}>
                          {product.allow_for_purchasing ? 'Enabled' : 'Disabled'}
                        </Badge>
                        {product.allow_for_purchasing && (
                          <p className="text-sm">Cost: {formatCurrency({ amount: product.purchase_cost })}</p>
                        )}
                      </div>
                    </div>
                  </div>

                  <div className="grid grid-cols-12 mt-2">
                    <Label className="md:col-span-2 col-span-12">Income Account</Label>
                    <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                      <p className="text-sm">{product.income_account?.account_name || 'N/A'}</p>
                    </div>
                  </div>

                  <div className="grid grid-cols-12 mt-2">
                    <Label className="md:col-span-2 col-span-12">Expense Account</Label>
                    <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                      <p className="text-sm">{product.expense_account?.account_name || 'N/A'}</p>
                    </div>
                  </div>

                  <div className="grid grid-cols-12 mt-2">
                    <Label className="md:col-span-2 col-span-12">Status</Label>
                    <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                      <ProductStatusBadge status={product.status} />
                    </div>
                  </div>

                  {/* Sales & Purchase Summary */}
                  <div className="grid grid-cols-12 mt-4">
                    <Label className="md:col-span-2 col-span-12">Sales Summary</Label>
                    <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                      <div className="grid grid-cols-2 gap-4">
                        <div className="border rounded-md p-3">
                          <p className="text-xs text-gray-500">Units Sold</p>
                          <p className="text-lg font-semibold">{Number(product.invoice_items_sum_quantity || 0) + Number(product.receipt_items_sum_quantity || 0)}</p>
                        </div>
                        <div className="border rounded-md p-3">
                          <p className="text-xs text-gray-500">Total Revenue</p>
                          <p className="text-lg font-semibold">{formatCurrency({ amount: Number(product.invoice_items_sum_sub_total || 0) + Number(product.receipt_items_sum_sub_total || 0) })}</p>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div className="grid grid-cols-12 mt-4">
                    <Label className="md:col-span-2 col-span-12">Purchase Summary</Label>
                    <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                      <div className="grid grid-cols-2 gap-4">
                        <div className="border rounded-md p-3">
                          <p className="text-xs text-gray-500">Units Purchased</p>
                          <p className="text-lg font-semibold">{product.purchase_items_sum_quantity || 0}</p>
                        </div>
                        <div className="border rounded-md p-3">
                          <p className="text-xs text-gray-500">Total Cost</p>
                          <p className="text-lg font-semibold">{formatCurrency({ amount: product.purchase_items_sum_sub_total || 0 })}</p>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </TabsContent>

            <TabsContent value="transactions">
              <div>
                <div className="pt-6">
                  {transactions && transactions.length > 0 ? (
                    <Table>
                      <TableHeader>
                        <TableRow>
                          <TableHead>Date</TableHead>
                          <TableHead>Reference</TableHead>
                          <TableHead>Quantity</TableHead>
                          <TableHead>Unit Price</TableHead>
                          <TableHead>Total Price</TableHead>
                        </TableRow>
                      </TableHeader>
                      <TableBody>
                        {transactions.map((transaction, index) => (
                          <TableRow key={index}>
                            <TableCell>{transaction.date}</TableCell>
                            <Link href={transaction.reference_url} className="underline text-blue-500">
                              <TableCell>{transaction.type} - {transaction.reference}</TableCell>
                            </Link>
                            <TableCell>
                              {transaction.quantity}
                            </TableCell>
                            <TableCell>
                              {formatCurrency({ amount: transaction.unit_price })}
                            </TableCell>
                            <TableCell>
                              {formatCurrency({ amount: transaction.total })}
                            </TableCell>
                          </TableRow>
                        ))}
                      </TableBody>
                    </Table>
                  ) : (
                    <p className="text-center py-4">No transactions found for this product.</p>
                  )}
                </div>
              </div>
            </TabsContent>

            <TabsContent value="suppliers">
              <div>
                <div className="pt-6">
                  {suppliers && suppliers.length > 0 ? (
                    <Table>
                      <TableHeader>
                        <TableRow>
                          <TableHead>Supplier Name</TableHead>
                          <TableHead>Purchase Count</TableHead>
                          <TableHead>Total Quantity</TableHead>
                          <TableHead>Average Price</TableHead>
                          <TableHead>Last Supply Date</TableHead>
                        </TableRow>
                      </TableHeader>
                      <TableBody>
                        {suppliers.map((supplier, index) => (
                          <TableRow key={index}>
                            <TableCell>{supplier.name}</TableCell>
                            <TableCell>{supplier.purchase_count || 0}</TableCell>
                            <TableCell>{supplier.total_quantity || 0}</TableCell>
                            <TableCell>
                              {formatCurrency({ amount: supplier.avg_unit_cost || 0 })}
                            </TableCell>
                            <TableCell>{supplier.last_purchase_date || 'N/A'}</TableCell>
                          </TableRow>
                        ))}
                      </TableBody>
                    </Table>
                  ) : (
                    <p className="text-center py-4">No suppliers found for this product.</p>
                  )}
                </div>
              </div>
            </TabsContent>
          </Tabs>
        </div>
      </SidebarInset>
    </AuthenticatedLayout>
  );
};

export default ProductView;
