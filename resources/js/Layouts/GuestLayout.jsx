import { initSettings } from '@/lib/settings';
import { usePage } from "@inertiajs/react";

export default function GuestLayout({ children }) {
    const { decimalPlace, decimalSep, thousandSep, baseCurrency, currencyPosition, date_format } = usePage().props;

    initSettings({
        decimalPlace: decimalPlace,
        decimalSep: decimalSep,
        thousandSep: thousandSep,
        baseCurrency: baseCurrency,
        currencyPosition: currencyPosition,
        date_format: date_format,
    });

    return (
        <div>
            {children}
        </div>
    );
}
