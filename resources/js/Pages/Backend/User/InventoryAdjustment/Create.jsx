import { useForm } from "@inertiajs/react";
import { Label } from "@/Components/ui/label";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset } from "@/Components/ui/sidebar";
import PageHeader from "@/Components/PageHeader";
import { Input } from "@/Components/ui/input";
import InputError from "@/Components/InputError";
import { Button } from "@/Components/ui/button";
import { toast } from "sonner";
import { SearchableCombobox } from "@/Components/ui/searchable-combobox";
import { useEffect, useState } from "react";
import { format } from "date-fns";
import DateTimePicker from "@/Components/DateTimePicker";

export default function Create({ accounts = [], products = [] }) {
  const [quantityOnHand, setQuantityOnHand] = useState(0);
  const [newQuantity, setNewQuantity] = useState(0);

  const { data, setData, post, processing, errors } = useForm({
    adjustment_date: new Date(),
    account_id: "",
    product_id: "",
    quantity_on_hand: "0",
    adjusted_quantity: "0",
    new_quantity: "0",
    description: "",
  });

  // Update quantities when a product is selected
  useEffect(() => {
    if (data.product_id) {
      const product = products.find(p => p.id.toString() === data.product_id);
      if (product) {
        const stock = parseFloat(product.stock);
        setQuantityOnHand(stock);
        setData("quantity_on_hand", stock.toString());
        setData("new_quantity", stock.toString());
      }
    }
  }, [data.product_id]);

  // Calculate new quantity when adjusted quantity changes
  useEffect(() => {
    if (data.adjusted_quantity && data.quantity_on_hand) {
      const adjusted = parseFloat(data.adjusted_quantity);
      const current = parseFloat(data.quantity_on_hand);
      const newQty = current + adjusted;

      // Ensure new quantity is not negative
      const validNewQty = Math.max(0, newQty);
      setData("new_quantity", validNewQty.toString());
      setNewQuantity(validNewQty);
    }
  }, [data.adjusted_quantity, data.quantity_on_hand]);

  // Handle product selection
  const handleProductChange = (value) => {
    setData("product_id", value);
  };

  // Handle adjusted quantity change
  const handleAdjustedQuantityChange = (e) => {
    const value = e.target.value;
    setData("adjusted_quantity", value);
  };

  const submit = (e) => {
    e.preventDefault();
    post(route("inventory_adjustments.store"), {
      preserveScroll: true,
      onSuccess: () => {
        toast.success("Inventory adjustment created successfully");
      },
    });
  };

  return (
    <AuthenticatedLayout>
      <SidebarInset>
        <PageHeader page="Inventory Adjustments" subpage="Add New" url="inventory_adjustments.index" />

        <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
          <form onSubmit={submit}>
            <div className="grid grid-cols-12 mt-2">
              <Label htmlFor="adjustment_date" className="md:col-span-2 col-span-12">
                Adjustment Date *
              </Label>
              <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                <DateTimePicker
                  value={data.adjustment_date}
                  onChange={(date) => setData("adjustment_date", date)}
                  className="md:w-1/2 w-full"
                  required
                />
                <InputError message={errors.adjustment_date} className="text-sm" />
              </div>
            </div>

            <div className="grid grid-cols-12 mt-4">
              <Label htmlFor="account_id" className="md:col-span-2 col-span-12">
                Material Cost Account *
              </Label>
              <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                <div className="md:w-1/2 w-full">
                  <SearchableCombobox
                    options={accounts.map(account => ({
                      id: account.id.toString(),
                      name: account.account_name
                    }))}
                    value={data.account_id}
                    onChange={(value) => setData("account_id", value)}
                    placeholder="Select an account"
                  />
                </div>
                <InputError message={errors.account_id} className="text-sm" />
              </div>
            </div>

            <div className="grid grid-cols-12 mt-4">
              <Label htmlFor="product_id" className="md:col-span-2 col-span-12">
                Product *
              </Label>
              <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                <div className="md:w-1/2 w-full">
                  <SearchableCombobox
                    options={products.map(product => ({
                      id: product.id.toString(),
                      name: `${product.name} (${product.stock} ${product.product_unit?.unit || ''})`
                    }))}
                    value={data.product_id}
                    onChange={handleProductChange}
                    placeholder="Select a product"
                  />
                </div>
                <InputError message={errors.product_id} className="text-sm" />
              </div>
            </div>

            <div className="grid grid-cols-12 mt-4">
              <Label htmlFor="quantity_on_hand" className="md:col-span-2 col-span-12">
                Quantity On Hand *
              </Label>
              <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                <Input
                  id="quantity_on_hand"
                  type="number"
                  value={data.quantity_on_hand}
                  className="md:w-1/2 w-full bg-gray-100"
                  readOnly
                  required
                />
                <InputError message={errors.quantity_on_hand} className="text-sm" />
              </div>
            </div>

            <div className="grid grid-cols-12 mt-4">
              <Label htmlFor="adjusted_quantity" className="md:col-span-2 col-span-12">
                Adjusted Quantity *
              </Label>
              <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                <div className="md:w-1/2 w-full flex items-center">
                  <Input
                    id="adjusted_quantity"
                    type="number"
                    value={data.adjusted_quantity}
                    onChange={handleAdjustedQuantityChange}
                    className="w-full"
                    step="0.01"
                    required
                  />
                  <div className="ml-3 text-sm text-gray-500">
                    {data.adjusted_quantity > 0 ? '(Adding to inventory)' : data.adjusted_quantity < 0 ? '(Removing from inventory)' : ''}
                  </div>
                </div>
                <InputError message={errors.adjusted_quantity} className="text-sm" />
              </div>
            </div>

            <div className="grid grid-cols-12 mt-4">
              <Label htmlFor="new_quantity" className="md:col-span-2 col-span-12">
                New Quantity *
              </Label>
              <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                <Input
                  id="new_quantity"
                  type="number"
                  value={data.new_quantity}
                  className="md:w-1/2 w-full bg-gray-100"
                  readOnly
                  required
                />
                <InputError message={errors.new_quantity} className="text-sm" />
              </div>
            </div>

            <div className="grid grid-cols-12 mt-4">
              <Label htmlFor="description" className="md:col-span-2 col-span-12">
                Description
              </Label>
              <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                <Input
                  id="description"
                  type="text"
                  value={data.description}
                  onChange={(e) => setData("description", e.target.value)}
                  className="md:w-1/2 w-full"
                />
                <InputError message={errors.description} className="text-sm" />
              </div>
            </div>

            <div className="mt-8">
              <Button
                type="submit"
                disabled={processing}
              >
                Create Adjustment
              </Button>
            </div>
          </form>
        </div>
      </SidebarInset>
    </AuthenticatedLayout>
  );
}
