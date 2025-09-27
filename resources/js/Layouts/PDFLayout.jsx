import { initSettings } from '@/lib/settings';
import { usePage } from "@inertiajs/react";

export default function PDFLayout({ children }) {
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
        <div className="pdf-layout min-h-screen bg-white">
            <style jsx>{`
                @media print {
                    body {
                        -webkit-print-color-adjust: exact;
                        print-color-adjust: exact;
                    }
                    
                    .pdf-layout {
                        width: 100%;
                        max-width: none;
                        margin: 0;
                        padding: 0;
                    }
                    
                    * {
                        scrollbar-width: none;
                        font-size: 12px;
                    }
                    
                    .no-print {
                        display: none !important;
                    }
                }
                
                @page {
                    size: A4;
                    margin: 20mm;
                }
                
                .pdf-content {
                    font-family: 'Inter', 'Geist', 'Instrument Sans', sans-serif;
                    font-size: 14px;
                    line-height: 1.5;
                    color: #374151;
                }
                
                .pdf-header {
                    margin-bottom: 2rem;
                    border-bottom: 2px solid #e5e7eb;
                    padding-bottom: 1rem;
                }
                
                .pdf-footer {
                    margin-top: 2rem;
                    border-top: 1px solid #e5e7eb;
                    padding-top: 1rem;
                    font-size: 12px;
                    color: #6b7280;
                }
                
                .pdf-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 1rem 0;
                }
                
                .pdf-table th,
                .pdf-table td {
                    border: 1px solid #e5e7eb;
                    padding: 8px 12px;
                    text-align: left;
                }
                
                .pdf-table th {
                    background-color: #f9fafb;
                    font-weight: 600;
                }
                
                .pdf-table tbody tr:nth-child(even) {
                    background-color: #f9fafb;
                }
            `}</style>
            
            <div className="pdf-content">
                {children}
            </div>
        </div>
    );
}
