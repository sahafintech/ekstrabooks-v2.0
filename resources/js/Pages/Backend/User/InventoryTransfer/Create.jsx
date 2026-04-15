import { useForm } from "@inertiajs/react";
import { Label } from "@/Components/ui/label";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset, SidebarSeparator } from "@/Components/ui/sidebar";
import PageHeader from "@/Components/PageHeader";
import { Input } from "@/Components/ui/input";
import InputError from "@/Components/InputError";
import { Button } from "@/Components/ui/button";
import { toast } from "sonner";
import { SearchableCombobox } from "@/Components/ui/searchable-combobox";
import { Textarea } from "@/Components/ui/textarea";
import { Plus, Trash2 } from "lucide-react";
import { useState, useEffect } from "react";
import DateTimePicker from "@/Components/DateTimePicker";

export default function Create({ businesses = [], products = [] }) {
    const [transferItems, setTransferItems] = useState([{
        product_id: "",
        product_name: "",
        notes: "",
        requested_quantity: 1,
    }]);

    const { data, setData, post, processing, errors, reset } = useForm({
        transfer_date: new Date().toISOString().split("T")[0],
        to_entity_id: "",
        remarks: "",
        product_id: [],
        product_name: [],
        notes: [],
        requested_quantity: [],
    });

    const addTransferItem = () => {
        setTransferItems([...transferItems, {
            product_id: "",
            product_name: "",
            notes: "",
            requested_quantity: 1,
        }]);
        setData("product_id", [...data.product_id, ""]);
        setData("product_name", [...data.product_name, ""]);
        setData("notes", [...data.notes, ""]);
        setData("requested_quantity", [...data.requested_quantity, 1]);
    };

    const removeTransferItem = (index) => {
        const updatedItems = transferItems.filter((_, i) => i !== index);
        setTransferItems(updatedItems);
        setData("product_id", updatedItems.map(item => item.product_id));
        setData("product_name", updatedItems.map(item => item.product_name));
        setData("notes", updatedItems.map(item => item.notes));
        setData("requested_quantity", updatedItems.map(item => item.requested_quantity));
    };

    const updateTransferItem = (index, field, value) => {
        const updatedItems = [...transferItems];
        updatedItems[index][field] = value;

        if (field === "product_id") {
            const product = products.find(p => p.id === parseInt(value, 10));
            if (product) {
                updatedItems[index].product_name = product.name;
                
                // Also update the notes if it's empty
                if (!updatedItems[index].notes) {
                    updatedItems[index].notes = product.description || "";
                }
            }
        }

        setTransferItems(updatedItems);
        setData("product_id", updatedItems.map(item => item.product_id));
        setData("product_name", updatedItems.map(item => item.product_name));
        setData("notes", updatedItems.map(item => item.notes));
        setData("requested_quantity", updatedItems.map(item => item.requested_quantity));
    };

    const submit = (e) => {
        e.preventDefault();

        // Create a new data object with all the required fields
        const formData = {
            ...data,
            product_id: transferItems.map(item => item.product_id),
            product_name: transferItems.map(item => item.product_name),
            notes: transferItems.map(item => item.notes),
            requested_quantity: transferItems.map(item => item.requested_quantity),
        };

        // Post the form data
        post(route("inventory_transfers.store"), formData, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success("Transfer created successfully");
                reset();
                setTransferItems([{
                    product_id: "",
                    product_name: "",
                    notes: "",
                    requested_quantity: 1,
                }]);
            },
        });
    };

    return (
        <AuthenticatedLayout>
            <SidebarInset>
                <PageHeader page="Inventory Transfers" subpage="Create New" url="inventory_transfers.index" />

                <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
                    <form onSubmit={submit}>
                        <div className="grid grid-cols-12 mt-2">
                            <Label htmlFor="transfer_date" className="md:col-span-2 col-span-12">
                                Transfer Date *
                                        </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <DateTimePicker
                                            value={data.transfer_date}
                                    onChange={(date) => setData("transfer_date", date)}
                                    className="md:w-1/2 w-full"
                                            required
                                        />
                                <InputError message={errors.transfer_date} className="text-sm" />
                                    </div>
                                </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label htmlFor="to_entity_id" className="md:col-span-2 col-span-12">
                                Destination Business *
                                    </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <div className="md:w-1/2 w-full">
                                    <SearchableCombobox
                                        options={businesses.map(business => ({
                                            id: business.id,
                                            name: business.name
                                        }))}
                                        value={data.to_entity_id}
                                        onChange={(value) => setData("to_entity_id", value)}
                                        placeholder="Select destination business"
                                    />
                                </div>
                                <InputError message={errors.to_entity_id} className="text-sm" />
                            </div>
                                    </div>

                        <div className="grid grid-cols-12 mt-2">
                            <Label htmlFor="remarks" className="md:col-span-2 col-span-12">
                                Remarks
                                        </Label>
                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                <Textarea
                                    id="remarks"
                                    value={data.remarks}
                                    onChange={(e) => setData("remarks", e.target.value)}
                                    className="md:w-1/2 w-full"
                                    rows={4}
                                />
                                <InputError message={errors.remarks} className="text-sm" />
                                    </div>
                                    </div>

                        <SidebarSeparator className="my-4" />

                        <div className="space-y-4">
                            <div className="flex justify-between items-center">
                                <h3 className="text-lg font-medium">Transfer Items</h3>
                                <Button variant="secondary" type="button" onClick={addTransferItem}>
                                    <Plus className="w-4 h-4 mr-2" />
                                        Add Item
                                    </Button>
                                </div>

                            {transferItems.map((item, index) => (
                                <div key={index} className="border rounded-lg p-4 space-y-4 bg-gray-50">
                                    <div className="grid grid-cols-1 md:grid-cols-5 gap-2">
                                        <div>
                                            <Label>Product *</Label>
                                            <SearchableCombobox
                                                options={products.map(product => ({
                                                    id: product.id,
                                                    name: `${product.name} (Stock: ${product.stock})`
                                                }))}
                                                value={item.product_id}
                                                onChange={(value) => updateTransferItem(index, "product_id", value)}
                                                placeholder="Select product"
                                            />
                                        </div>

                                        <div>
                                            <Label>Quantity *</Label>
                                                                <Input
                                                                    type="number"
                                                                    step="0.01"
                                                value={item.requested_quantity}
                                                onChange={(e) => updateTransferItem(index, "requested_quantity", parseFloat(e.target.value))}
                                            />
                                        </div>

                                        <div>
                                            <Label>Notes</Label>
                                            <Textarea
                                                value={item.notes}
                                                onChange={(e) => {
                                                    updateTransferItem(index, "notes", e.target.value);
                                                    e.target.style.height = 'auto';
                                                    e.target.style.height = e.target.scrollHeight + 'px';
                                                }}
                                                className="min-h-[30px] resize-none overflow-hidden"
                                                rows={1}
                                            />
                                        </div>

                                        <div className="md:col-span-1 flex items-center justify-end">
                                            {transferItems.length > 1 && (
                                                                <Button
                                                                    type="button"
                                                                    variant="ghost"
                                                    size="icon"
                                                    className="text-red-500"
                                                    onClick={() => removeTransferItem(index)}
                                                >
                                                    <Trash2 className="w-4 h-4" />
                                                                </Button>
                                                )}
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>

                        <div className="mt-6 space-y-2">
                            <div className="space-x-2">
                                <Button
                                    type="button"
                                    variant="secondary"
                                    onClick={() => {
                                        reset();
                                        setTransferItems([{
                                            product_id: "",
                                            product_name: "",
                                            notes: "",
                                            requested_quantity: 1,
                                        }]);
                                    }}
                                >
                                    Reset
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    Create Transfer
                            </Button>
                            </div>
                        </div>
                    </form>
                </div>
            </SidebarInset>
        </AuthenticatedLayout>
    );
}
