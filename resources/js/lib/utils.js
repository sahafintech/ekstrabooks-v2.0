import { clsx } from "clsx";
import { twMerge } from "tailwind-merge"
import { format as formatDateFns } from 'date-fns';

export function cn(...inputs) {
  return twMerge(clsx(inputs));
}

/**
 * Format a number as currency with the given currency code
 * @param {number} amount - The amount to format
 * @param {string} currencyCode - ISO 4217 currency code (e.g., USD, EUR, GBP)
 * @param {number} decimalPlaces - Number of decimal places
 * @returns {string} Formatted currency string
 */
export function formatCurrency(amount, currencyCode = 'USD', decimalPlaces = 2) {
  if (amount === null || amount === undefined) return `${currencyCode} 0.00`;
  
  // Format with 2 decimal places
  const formattedAmount = parseFloat(amount).toFixed(decimalPlaces);
  
  // Return formatted string with currency code
  return `${currencyCode} ${formattedAmount}`;
}

export function convertCurrency(amount, exchangeRate) {
  if (!exchangeRate || exchangeRate === 0) return amount;

  return parseFloat((amount * parseFloat(exchangeRate)));
}

/**
 * Map a PHP‐date format string to a date‑fns format string.
 * Extend this map if you need more PHP tokens.
 */
const phpToDateFnsFormat = (phpFmt) => {
  const tokenMap = {
    // PHP  →  date‑fns
    Y: 'yyyy',  // full year
    y: 'yy',    // two‑digit year
    m: 'MM',    // zero‑padded month
    n: 'M',     // month (1–12)
    d: 'dd',    // zero‑padded day
    j: 'd',     // day (1–31)
    M: 'MMM',   // short month name
    F: 'MMMM',  // full month name
    // add more mappings if needed…
  };

  // replace each token in the string
  return phpFmt.replace(/Y|y|m|n|d|j|M|F/g, t => tokenMap[t] || t);
};

/**
 * formatDate now accepts PHP‐style formats:
 *
 *   formatDate('2025-04-17', 'd/m/Y')  → "17/04/2025"
 *   formatDate('2025-12-01', 'M d, Y') → "Dec 01, 2025"
 */
export function formatDate(dateString, phpFormat = 'Y-m-d') {
  if (!dateString) return '';

  try {
    const date = new Date(dateString);
    const dfnsFormat = phpToDateFnsFormat(phpFormat);
    return formatDateFns(date, dfnsFormat);
  } catch (error) {
    console.error('Error formatting date:', error);
    return dateString;
  }
}


