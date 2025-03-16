import React from 'react';
import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { SidebarInset } from "@/Components/ui/sidebar";
import PageHeader from "@/Components/PageHeader";
import { Label } from "@/Components/ui/label";
import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { Link } from '@inertiajs/react';
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from "@/Components/ui/table";
import { Card, CardContent, CardHeader, CardTitle } from "@/Components/ui/card";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/Components/ui/tabs";

const ProductView = ({ auth, product, transactions, suppliers, id }) => {
  // Format currency with proper ISO 4217 code
  const formatCurrency = (amount, currencyCode = 'USD') => {
    return new Intl.NumberFormat('en-US', { 
      style: 'currency', 
      currency: currencyCode 
    }).format(amount);
  };

  return (
    <AuthenticatedLayout>
      <Head title={`View Product - ${product.name}`} />
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

          <Tabs defaultValue="details" className="w-full">
            <TabsList className="mb-4">
              <TabsTrigger value="details">Product Details</TabsTrigger>
              <TabsTrigger value="transactions">Transactions</TabsTrigger>
              <TabsTrigger value="suppliers">Suppliers</TabsTrigger>
            </TabsList>

            <TabsContent value="details" className="space-y-4">
              <Card>
                <CardContent className="pt-6">
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
                          <p className="text-sm">Price: {formatCurrency(product.selling_price, product.currency || 'USD')}</p>
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
                          <p className="text-sm">Cost: {formatCurrency(product.purchase_cost, product.currency || 'USD')}</p>
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
                      <Badge variant={product.status === 'active' ? 'success' : 'destructive'}>
                        {product.status}
                      </Badge>
                    </div>
                  </div>

                  {/* Sales & Purchase Summary */}
                  <div className="grid grid-cols-12 mt-4">
                    <Label className="md:col-span-2 col-span-12">Sales Summary</Label>
                    <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                      <div className="grid grid-cols-2 gap-4">
                        <div className="border rounded-md p-3">
                          <p className="text-xs text-gray-500">Units Sold</p>
                          <p className="text-lg font-semibold">{product.invoice_items_sum_quantity || 0}</p>
                        </div>
                        <div className="border rounded-md p-3">
                          <p className="text-xs text-gray-500">Total Revenue</p>
                          <p className="text-lg font-semibold">{formatCurrency(product.invoice_items_sum_sub_total || 0, product.currency || 'USD')}</p>
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
                          <p className="text-lg font-semibold">{formatCurrency(product.purchase_items_sum_sub_total || 0, product.currency || 'USD')}</p>
                        </div>
                      </div>
                    </div>
                  </div>
                </CardContent>
              </Card>
            </TabsContent>

            <TabsContent value="transactions">
              <Card>
                <CardHeader>
                  <CardTitle>Transaction History</CardTitle>
                </CardHeader>
                <CardContent>
                  {transactions && transactions.length > 0 ? (
                    <Table>
                      <TableHeader>
                        <TableRow>
                          <TableHead>Date</TableHead>
                          <TableHead>Description</TableHead>
                          <TableHead>Type</TableHead>
                          <TableHead>Amount</TableHead>
                        </TableRow>
                      </TableHeader>
                      <TableBody>
                        {transactions.map((transaction, index) => (
                          <TableRow key={index}>
                            <TableCell>{transaction.trans_date}</TableCell>
                            <TableCell>{transaction.description}</TableCell>
                            <TableCell>
                              <Badge variant={transaction.dr_cr === 'dr' ? 'default' : 'outline'}>
                                {transaction.dr_cr === 'dr' ? 'Debit' : 'Credit'}
                              </Badge>
                            </TableCell>
                            <TableCell>
                              {formatCurrency(transaction.transaction_amount, transaction.transaction_currency)}
                              {transaction.transaction_currency !== transaction.base_currency && (
                                <div className="text-xs text-gray-500">
                                  {formatCurrency(transaction.base_currency_amount, 'USD')} (Base)
                                </div>
                              )}
                            </TableCell>
                          </TableRow>
                        ))}
                      </TableBody>
                    </Table>
                  ) : (
                    <p className="text-center py-4">No transactions found for this product.</p>
                  )}
                </CardContent>
              </Card>
            </TabsContent>

            <TabsContent value="suppliers">
              <Card>
                <CardHeader>
                  <CardTitle>Suppliers</CardTitle>
                </CardHeader>
                <CardContent>
                  {suppliers && suppliers.length > 0 ? (
                    <Table>
                      <TableHeader>
                        <TableRow>
                          <TableHead>Supplier Name</TableHead>
                          <TableHead>Contact</TableHead>
                          <TableHead>Last Supply Date</TableHead>
                          <TableHead>Quantity</TableHead>
                          <TableHead>Price</TableHead>
                        </TableRow>
                      </TableHeader>
                      <TableBody>
                        {suppliers.map((supplier, index) => (
                          <TableRow key={index}>
                            <TableCell>{supplier.name}</TableCell>
                            <TableCell>{supplier.contact || 'N/A'}</TableCell>
                            <TableCell>{supplier.last_supply_date || 'N/A'}</TableCell>
                            <TableCell>{supplier.quantity || 0}</TableCell>
                            <TableCell>
                              {formatCurrency(supplier.price || 0, supplier.currency || 'USD')}
                            </TableCell>
                          </TableRow>
                        ))}
                      </TableBody>
                    </Table>
                  ) : (
                    <p className="text-center py-4">No suppliers found for this product.</p>
                  )}
                </CardContent>
              </Card>
            </TabsContent>
          </Tabs>
        </div>
      </SidebarInset>
    </AuthenticatedLayout>
  );
};

export default ProductView;
