import React from 'react';
import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { SidebarInset } from "@/Components/ui/sidebar";
import PageHeader from "@/Components/PageHeader";
import { Label } from "@/Components/ui/label";
import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { Link } from '@inertiajs/react';

const ProductView = ({ auth, product }) => {
  return (
    <AuthenticatedLayout>
      <SidebarInset>
        <PageHeader page="Products" subpage="View" url="products.index" />

        <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
          <div className="flex justify-end gap-2">
            
          </div>

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
                  <p className="text-sm">Price: {product.selling_price}</p>
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
                  <p className="text-sm">Cost: {product.purchase_cost}</p>
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
        </div>
      </SidebarInset>
    </AuthenticatedLayout>
  );
};

export default ProductView;
