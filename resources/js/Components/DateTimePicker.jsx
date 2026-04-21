import React from "react";
import Flatpickr from "react-flatpickr";
import "flatpickr/dist/flatpickr.min.css";
import { cn } from "@/lib/utils";
import { usePage } from "@inertiajs/react";
import { Input } from "@/Components/ui/input";

export default function DateTimePicker({
    value,
    onChange,
    enableTime = false,
    options = {},
    className,
    isRange = false,
    ...rest
}) {
    const { date_format } = usePage().props;

    return (
        <>
            <style>
                {`
          .flatpickr-input[readonly] {
            width: 100%;
          }
          .flatpickr-wrapper {
            width: 100%;
          }
        `}
            </style>
            <div className={cn("relative", className)}>
                <Flatpickr
                    {...rest}
                    className="cursor-pointer"
                    value={value}
                    options={{
                        enableTime,
                        mode: isRange ? "range" : "single",
                        dateFormat: date_format,
                        appendTo: document.body,
                        static: true,
                        position: "auto",
                        ...options,
                    }}
                    onChange={(selectedDates) => {
                        if (isRange) {
                            if (selectedDates.length === 0) {
                                // Range was cleared/deselected
                                return onChange(null);
                            }
                            if (selectedDates.length !== 2) return; // wait until both dates are selected
                            if (selectedDates.some((d) => !d || isNaN(d))) {
                                return onChange(null);
                            }
                            const zonelessDates = selectedDates.map(
                                (d) =>
                                    new Date(
                                        d.getTime() -
                                            d.getTimezoneOffset() * 60000
                                    )
                            );
                            onChange(zonelessDates);
                        } else {
                            const [d] = selectedDates;
                            if (!d || isNaN(d)) return onChange(null);
                            const zoneless = new Date(
                                d.getTime() - d.getTimezoneOffset() * 60000
                            );
                            onChange(zoneless);
                        }
                    }}
                    placeholder={isRange ? "pick a date range" : "pick a date"}
                    render={(
                        {
                            defaultValue,
                            value: _value,
                            className: inputClassName,
                            ...inputProps
                        },
                        ref
                    ) => (
                        <Input
                            {...inputProps}
                            ref={ref}
                            defaultValue={defaultValue}
                            className={cn("cursor-pointer", inputClassName)}
                        />
                    )}
                />
            </div>
        </>
    );
}
