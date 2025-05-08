import { useState } from "react";
import { useForm, Link } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset } from "@/Components/ui/sidebar";
import PageHeader from "@/Components/PageHeader";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import { Checkbox } from "@/Components/ui/checkbox";
import { SearchableCombobox } from "@/Components/ui/searchable-combobox";
import DateTimePicker from "@/Components/DateTimePicker";

export default function Create({ customers = [] }) {
    const { data, setData, post, processing, errors } = useForm({
        customer_id: "",
        patient_id: "",
        date: "",
        ocular_history: "",
        occupation: "",
        va_unaided_re: "",
        va_unaided_le: "",
        va_aided_re: "",
        va_aided_le: "",
        va_pinhole_re: "",
        va_pinhole_le: "",
        rf_unaided_d_re: "",
        rf_unaided_d_le: "",
        rf_unaided_n_re: "",
        rf_unaided_n_le: "",
        rf_aided_d_re: "",
        rf_aided_d_le: "",
        rf_aided_n_re: "",
        rf_aided_n_le: "",
        rf_best_corrected_le: "",
        rf_best_corrected_re: "",
        rf_test_type_used_re: "",
        rf_test_type_used_le: "",
        rf_lensometer_le: "",
        rf_lensometer_re: "",
        rf_autorefraction_re: "",
        rf_autorefraction_le: "",
        rf_dry_retinoscopy_re: "",
        rf_dry_retinoscopy_le: "",
        rf_wet_retinoscopy_re: "",
        rf_wet_retinoscopy_le: "",
        rf_subjective_re: "",
        rf_subjective_le: "",
        rf_near_re: "",
        rf_near_le: "",
        rf_final_prescription_re: "",
        rf_final_prescription_le: "",
        eso: 0,
        exo: 0,
        hypo: 0,
        hyper: 0,
        tropia: 0,
        phoria: 0,
        eso_distance_5m_6m: "",
        eso_near_30cm_50cm: "",
        exo_distance_5m_6m: "",
        exo_near_30cm_50cm: "",
        hypo_distance_5m_6m: "",
        hypo_near_30cm_50cm: "",
        hyper_distance_5m_6m: "",
        hyper_near_30cm_50cm: "",
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route("medical_records.store"));
    };

    // Helper for boolean fields to store 0/1
    const handleBooleanChange = (field, checked) => {
        setData(field, checked ? 1 : 0);
    };

    return (
        <AuthenticatedLayout>
            <SidebarInset>
                <PageHeader page="Medical Records" subpage="Add New" url="medical_records.index" />
                <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
                    <h2 className="text-center text-xl font-semibold mb-6">Add New Medical Record</h2>
                    <form onSubmit={handleSubmit}>
                        <div className="max-w-4xl mx-auto">
                            {/* Basic Information */}
                            <div className="mb-6">
                                <div className="grid grid-cols-12 gap-4 mb-4">
                                    <div className="col-span-3 flex items-center justify-end">
                                        <Label htmlFor="customer_id" className="text-right">Customer</Label>
                                    </div>
                                    <div className="col-span-9">
                                        <SearchableCombobox
                                            options={customers.map(customer => ({
                                                id: customer.id.toString(),
                                                name: customer.name
                                            }))}
                                            value={data.customer_id}
                                            onChange={(value) => setData("customer_id", value)}
                                            placeholder="Select a customer"
                                        />
                                        {errors.customer_id && <p className="text-sm text-red-600 mt-1">{errors.customer_id}</p>}
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 gap-4 mb-4">
                                    <div className="col-span-3 flex items-center justify-end">
                                        <Label htmlFor="patient_id" className="text-right">Patient ID</Label>
                                    </div>
                                    <div className="col-span-9">
                                        <Input
                                            id="patient_id"
                                            value={data.patient_id}
                                            onChange={(e) => setData("patient_id", e.target.value)}
                                        />
                                        {errors.patient_id && <p className="text-sm text-red-600 mt-1">{errors.patient_id}</p>}
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 gap-4 mb-4">
                                    <div className="col-span-3 flex items-center justify-end">
                                        <Label htmlFor="date" className="text-right">Date</Label>
                                    </div>
                                    <div className="col-span-9">
                                        <DateTimePicker
                                            value={data.date}
                                            onChange={(date) => setData("date", date)}
                                            required
                                        />
                                        {errors.date && <p className="text-sm text-red-600 mt-1">{errors.date}</p>}
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 gap-4 mb-4">
                                    <div className="col-span-3 flex items-center justify-end">
                                        <Label htmlFor="ocular_history" className="text-right">Ocular History</Label>
                                    </div>
                                    <div className="col-span-9">
                                        <Input
                                            id="ocular_history"
                                            value={data.ocular_history}
                                            onChange={(e) => setData("ocular_history", e.target.value)}
                                        />
                                        {errors.ocular_history && <p className="text-sm text-red-600 mt-1">{errors.ocular_history}</p>}
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 gap-4 mb-4">
                                    <div className="col-span-3 flex items-center justify-end">
                                        <Label htmlFor="occupation" className="text-right">Occupation</Label>
                                    </div>
                                    <div className="col-span-9">
                                        <Input
                                            id="occupation"
                                            value={data.occupation}
                                            onChange={(e) => setData("occupation", e.target.value)}
                                        />
                                        {errors.occupation && <p className="text-sm text-red-600 mt-1">{errors.occupation}</p>}
                                    </div>
                                </div>
                            </div>

                            {/* Clinical Examination */}
                            <div className="mb-6">
                                <h3 className="text-center text-lg font-medium mb-4 border-b pb-2">Clinical Examination</h3>

                                <div className="grid grid-cols-12 mb-4">
                                    <div className="col-span-3"></div>
                                    <div className="col-span-4 text-center font-medium">RIGHT EYE</div>
                                    <div className="col-span-5 text-center font-medium">LEFT EYE</div>
                                </div>

                                <div className="grid grid-cols-12 gap-4 mb-4">
                                    <div className="col-span-3 flex items-center">
                                        <Label className="text-right w-full">VA unaided</Label>
                                    </div>
                                    <div className="col-span-4">
                                        <Input
                                            value={data.va_unaided_re}
                                            onChange={(e) => setData("va_unaided_re", e.target.value)}
                                        />
                                    </div>
                                    <div className="col-span-5">
                                        <Input
                                            value={data.va_unaided_le}
                                            onChange={(e) => setData("va_unaided_le", e.target.value)}
                                        />
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 gap-4 mb-4">
                                    <div className="col-span-3 flex items-center">
                                        <Label className="text-right w-full">VA AIDED (GLASSES)</Label>
                                    </div>
                                    <div className="col-span-4">
                                        <Input
                                            value={data.va_aided_re}
                                            onChange={(e) => setData("va_aided_re", e.target.value)}
                                        />
                                    </div>
                                    <div className="col-span-5">
                                        <Input
                                            value={data.va_aided_le}
                                            onChange={(e) => setData("va_aided_le", e.target.value)}
                                        />
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 gap-4 mb-4">
                                    <div className="col-span-3 flex items-center">
                                        <Label className="text-right w-full">VA PINHOLE</Label>
                                    </div>
                                    <div className="col-span-4">
                                        <Input
                                            value={data.va_pinhole_re}
                                            onChange={(e) => setData("va_pinhole_re", e.target.value)}
                                        />
                                    </div>
                                    <div className="col-span-5">
                                        <Input
                                            value={data.va_pinhole_le}
                                            onChange={(e) => setData("va_pinhole_le", e.target.value)}
                                        />
                                    </div>
                                </div>
                            </div>

                            {/* Refraction Note */}
                            <div className="mb-6">
                                <h3 className="text-center text-lg font-medium mb-4 border-b pb-2">Refraction Note</h3>

                                {/* First section with D/N columns for VA unaided and Best Corrected VA */}
                                <div className="grid grid-cols-5 gap-2 mb-6">
                                    <div></div>
                                    <div className="text-center font-medium">Right Eye(D)</div>
                                    <div className="text-center font-medium">Right Eye(N)</div>
                                    <div className="text-center font-medium">Left Eye(D)</div>
                                    <div className="text-center font-medium">Left Eye(N)</div>
                                </div>

                                <div className="grid grid-cols-5 gap-2 mb-3">
                                    <div className="py-2">VA unaided</div>
                                    <div>
                                        <Input
                                            value={data.va_unaided_re}
                                            onChange={(e) => setData("va_unaided_re", e.target.value)}
                                            className="w-full"
                                        />
                                    </div>
                                    <div>
                                        <Input
                                            value={data.va_unaided_re_n}
                                            onChange={(e) => setData("va_unaided_re_n", e.target.value)}
                                            className="w-full"
                                        />
                                    </div>
                                    <div>
                                        <Input
                                            value={data.va_unaided_le}
                                            onChange={(e) => setData("va_unaided_le", e.target.value)}
                                            className="w-full"
                                        />
                                    </div>
                                    <div>
                                        <Input
                                            value={data.va_unaided_le_n}
                                            onChange={(e) => setData("va_unaided_le_n", e.target.value)}
                                            className="w-full"
                                        />
                                    </div>
                                </div>

                                <div className="grid grid-cols-5 gap-2 mb-3">
                                    <div className="py-2">VA aided (glasses)</div>
                                    <div>
                                        <Input
                                            value={data.va_aided_re}
                                            onChange={(e) => setData("va_aided_re", e.target.value)}
                                            className="w-full"
                                        />
                                    </div>
                                    <div>
                                        <Input
                                            value={data.va_aided_re_n}
                                            onChange={(e) => setData("va_aided_re_n", e.target.value)}
                                            className="w-full"
                                        />
                                    </div>
                                    <div>
                                        <Input
                                            value={data.va_aided_le}
                                            onChange={(e) => setData("va_aided_le", e.target.value)}
                                            className="w-full"
                                        />
                                    </div>
                                    <div>
                                        <Input
                                            value={data.va_aided_le_n}
                                            onChange={(e) => setData("va_aided_le_n", e.target.value)}
                                            className="w-full"
                                        />
                                    </div>
                                </div>

                                {/* Switch to 3-column layout for the rest of the fields */}
                                <div className="grid grid-cols-3 gap-2 mt-6 mb-3">
                                    <div></div>
                                    <div className="text-center font-medium">RIGHT EYE</div>
                                    <div className="text-center font-medium">LEFT EYE</div>
                                </div>

                                <div className="grid grid-cols-3 gap-2 mb-3">
                                    <div className="py-2">Best Corrected VA</div>
                                    <div>
                                        <Input
                                            value={data.rf_best_corrected_re}
                                            onChange={(e) => setData("rf_best_corrected_re", e.target.value)}
                                            className="w-full"
                                        />
                                    </div>
                                    <div>
                                        <Input
                                            value={data.rf_best_corrected_le}
                                            onChange={(e) => setData("rf_best_corrected_le", e.target.value)}
                                            className="w-full"
                                        />
                                    </div>
                                </div>

                                <div className="grid grid-cols-3 gap-2 mb-3">
                                    <div className="py-2">VA Test Type Used</div>
                                    <div>
                                        <Input
                                            value={data.va_test_used_re}
                                            onChange={(e) => setData("va_test_used_re", e.target.value)}
                                            className="w-full"
                                        />
                                    </div>
                                    <div>
                                        <Input
                                            value={data.va_test_used_le}
                                            onChange={(e) => setData("va_test_used_le", e.target.value)}
                                            className="w-full"
                                        />
                                    </div>
                                </div>

                                <div className="grid grid-cols-3 gap-2 mb-3">
                                    <div className="py-2">Lensometer</div>
                                    <div>
                                        <Input
                                            value={data.rf_lensometer_re}
                                            onChange={(e) => setData("rf_lensometer_re", e.target.value)}
                                            className="w-full"
                                        />
                                    </div>
                                    <div>
                                        <Input
                                            value={data.rf_lensometer_le}
                                            onChange={(e) => setData("rf_lensometer_le", e.target.value)}
                                            className="w-full"
                                        />
                                    </div>
                                </div>

                                <div className="grid grid-cols-3 gap-2 mb-3">
                                    <div className="py-2">Keratometry</div>
                                    <div>
                                        <Input
                                            value={data.keratometry_re}
                                            onChange={(e) => setData("keratometry_re", e.target.value)}
                                            className="w-full"
                                        />
                                    </div>
                                    <div>
                                        <Input
                                            value={data.keratometry_le}
                                            onChange={(e) => setData("keratometry_le", e.target.value)}
                                            className="w-full"
                                        />
                                    </div>
                                </div>

                                <div className="grid grid-cols-3 gap-2 mb-3">
                                    <div className="py-2">Autorefraction</div>
                                    <div>
                                        <Input
                                            value={data.rf_autorefraction_re}
                                            onChange={(e) => setData("rf_autorefraction_re", e.target.value)}
                                            className="w-full"
                                        />
                                    </div>
                                    <div>
                                        <Input
                                            value={data.rf_autorefraction_le}
                                            onChange={(e) => setData("rf_autorefraction_le", e.target.value)}
                                            className="w-full"
                                        />
                                    </div>
                                </div>

                                <div className="grid grid-cols-3 gap-2 mb-3">
                                    <div className="py-2">Dry Retinoscopy</div>
                                    <div>
                                        <Input
                                            value={data.rf_dry_retinoscopy_re}
                                            onChange={(e) => setData("rf_dry_retinoscopy_re", e.target.value)}
                                            className="w-full"
                                        />
                                    </div>
                                    <div>
                                        <Input
                                            value={data.rf_dry_retinoscopy_le}
                                            onChange={(e) => setData("rf_dry_retinoscopy_le", e.target.value)}
                                            className="w-full"
                                        />
                                    </div>
                                </div>

                                <div className="grid grid-cols-3 gap-2 mb-3">
                                    <div className="py-2">Wet Retinoscopy (cyclo)</div>
                                    <div>
                                        <Input
                                            value={data.rf_wet_retinoscopy_re}
                                            onChange={(e) => setData("rf_wet_retinoscopy_re", e.target.value)}
                                            className="w-full"
                                        />
                                    </div>
                                    <div>
                                        <Input
                                            value={data.rf_wet_retinoscopy_le}
                                            onChange={(e) => setData("rf_wet_retinoscopy_le", e.target.value)}
                                            className="w-full"
                                        />
                                    </div>
                                </div>

                                <div className="grid grid-cols-3 gap-2 mb-3">
                                    <div className="py-2">Subjective Refraction BCVA (distance)</div>
                                    <div>
                                        <Input
                                            value={data.rf_subjective_re}
                                            onChange={(e) => setData("rf_subjective_re", e.target.value)}
                                            className="w-full"
                                        />
                                    </div>
                                    <div>
                                        <Input
                                            value={data.rf_subjective_le}
                                            onChange={(e) => setData("rf_subjective_le", e.target.value)}
                                            className="w-full"
                                        />
                                    </div>
                                </div>

                                <div className="grid grid-cols-3 gap-2 mb-3">
                                    <div className="py-2">Near (reading)</div>
                                    <div>
                                        <Input
                                            value={data.rf_near_re}
                                            onChange={(e) => setData("rf_near_re", e.target.value)}
                                            className="w-full"
                                        />
                                    </div>
                                    <div>
                                        <Input
                                            value={data.rf_near_le}
                                            onChange={(e) => setData("rf_near_le", e.target.value)}
                                            className="w-full"
                                        />
                                    </div>
                                </div>

                                <div className="grid grid-cols-3 gap-2 mb-3">
                                    <div className="py-2">Final Prescription</div>
                                    <div>
                                        <Input
                                            value={data.rf_final_prescription_re}
                                            onChange={(e) => setData("rf_final_prescription_re", e.target.value)}
                                            className="w-full"
                                        />
                                    </div>
                                    <div>
                                        <Input
                                            value={data.rf_final_prescription_le}
                                            onChange={(e) => setData("rf_final_prescription_le", e.target.value)}
                                            className="w-full"
                                        />
                                    </div>
                                </div>
                            </div>

                            {/* Ocular Muscle Balance */}
                            <div className="mb-6">
                                <h3 className="text-center text-lg font-medium mb-4 border-b pb-2">Ocular Muscle Balance</h3>
                                <div className="w-full">
                                    {/* Headers */}
                                    <div className="grid grid-cols-3 mb-6">
                                        <div className="p-2"></div>
                                        <div className="p-2 text-center font-medium">DISTANCE AT 5 M/ 6 M</div>
                                        <div className="p-2 text-center font-medium">NEAR AT 30-50 CM</div>
                                    </div>
                                    {/* ESO row */}
                                    <div className="grid grid-cols-3 mb-3">
                                        <div className="py-2 flex items-center">
                                            <Checkbox
                                                id="eso"
                                                checked={!!data.eso}
                                                onCheckedChange={(checked) => handleBooleanChange("eso", checked)}
                                            />
                                            <Label htmlFor="eso" className="ml-2">ESO</Label>
                                        </div>
                                        <div className="p-2">
                                            <Input
                                                value={data.eso_distance_5m_6m}
                                                onChange={(e) => setData("eso_distance_5m_6m", e.target.value)}
                                                className="w-full"
                                            />
                                        </div>
                                        <div className="p-2">
                                            <Input
                                                value={data.eso_near_30cm_50cm}
                                                onChange={(e) => setData("eso_near_30cm_50cm", e.target.value)}
                                                className="w-full"
                                            />
                                        </div>
                                    </div>
                                    {/* EXO row */}
                                    <div className="grid grid-cols-3 mb-3">
                                        <div className="py-2 flex items-center">
                                            <Checkbox
                                                id="exo"
                                                checked={!!data.exo}
                                                onCheckedChange={(checked) => handleBooleanChange("exo", checked)}
                                            />
                                            <Label htmlFor="exo" className="ml-2">EXO</Label>
                                        </div>
                                        <div className="p-2">
                                            <Input
                                                value={data.exo_distance_5m_6m}
                                                onChange={(e) => setData("exo_distance_5m_6m", e.target.value)}
                                                className="w-full"
                                            />
                                        </div>
                                        <div className="p-2">
                                            <Input
                                                value={data.exo_near_30cm_50cm}
                                                onChange={(e) => setData("exo_near_30cm_50cm", e.target.value)}
                                                className="w-full"
                                            />
                                        </div>
                                    </div>
                                    {/* HYPO row */}
                                    <div className="grid grid-cols-3 mb-3">
                                        <div className="py-2 flex items-center">
                                            <Checkbox
                                                id="hypo"
                                                checked={!!data.hypo}
                                                onCheckedChange={(checked) => handleBooleanChange("hypo", checked)}
                                            />
                                            <Label htmlFor="hypo" className="ml-2">HYPO</Label>
                                        </div>
                                        <div className="p-2">
                                            <Input
                                                value={data.hypo_distance_5m_6m}
                                                onChange={(e) => setData("hypo_distance_5m_6m", e.target.value)}
                                                className="w-full"
                                            />
                                        </div>
                                        <div className="p-2">
                                            <Input
                                                value={data.hypo_near_30cm_50cm}
                                                onChange={(e) => setData("hypo_near_30cm_50cm", e.target.value)}
                                                className="w-full"
                                            />
                                        </div>
                                    </div>
                                    {/* HYPER row */}
                                    <div className="grid grid-cols-3 mb-3">
                                        <div className="py-2 flex items-center">
                                            <Checkbox
                                                id="hyper"
                                                checked={!!data.hyper}
                                                onCheckedChange={(checked) => handleBooleanChange("hyper", checked)}
                                            />
                                            <Label htmlFor="hyper" className="ml-2">HYPER</Label>
                                        </div>
                                        <div className="p-2">
                                            <Input
                                                value={data.hyper_distance_5m_6m}
                                                onChange={(e) => setData("hyper_distance_5m_6m", e.target.value)}
                                                className="w-full"
                                            />
                                        </div>
                                        <div className="p-2">
                                            <Input
                                                value={data.hyper_near_30cm_50cm}
                                                onChange={(e) => setData("hyper_near_30cm_50cm", e.target.value)}
                                                className="w-full"
                                            />
                                        </div>
                                    </div>
                                    {/* Tropia row */}
                                    <div className="grid grid-cols-3 mb-3">
                                        <div className="py-2">Tropia</div>
                                        <div className="col-span-2 p-2 grid grid-cols-2">
                                            <div className="flex items-center justify-center">
                                                <input
                                                    type="radio"
                                                    id="tropia-yes"
                                                    name="tropia"
                                                    className="mr-2"
                                                    checked={data.tropia === 1}
                                                    onChange={() => setData("tropia", 1)}
                                                />
                                                <label htmlFor="tropia-yes">Yes</label>
                                            </div>
                                            <div className="flex items-center justify-center">
                                                <input
                                                    type="radio"
                                                    id="tropia-no"
                                                    name="tropia"
                                                    className="mr-2"
                                                    checked={data.tropia === 0}
                                                    onChange={() => setData("tropia", 0)}
                                                />
                                                <label htmlFor="tropia-no">No</label>
                                            </div>
                                        </div>
                                    </div>
                                    {/* Phoria row */}
                                    <div className="grid grid-cols-3 mb-3">
                                        <div className="py-2">Phoria</div>
                                        <div className="col-span-2 p-2 grid grid-cols-2">
                                            <div className="flex items-center justify-center">
                                                <input
                                                    type="radio"
                                                    id="phoria-yes"
                                                    name="phoria"
                                                    className="mr-2"
                                                    checked={data.phoria === 1}
                                                    onChange={() => setData("phoria", 1)}
                                                />
                                                <label htmlFor="phoria-yes">Yes</label>
                                            </div>
                                            <div className="flex items-center justify-center">
                                                <input
                                                    type="radio"
                                                    id="phoria-no"
                                                    name="phoria"
                                                    className="mr-2"
                                                    checked={data.phoria === 0}
                                                    onChange={() => setData("phoria", 0)}
                                                />
                                                <label htmlFor="phoria-no">No</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Submit Button */}
                            <div>
                                <Button
                                    type="submit"
                                    disabled={processing}
                                >
                                    Save Changes
                                </Button>
                            </div>
                        </div>
                    </form>
                </div>
            </SidebarInset>
        </AuthenticatedLayout>
    );
}
