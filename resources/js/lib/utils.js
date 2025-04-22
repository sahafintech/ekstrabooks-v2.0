import { clsx } from "clsx";
import { twMerge } from "tailwind-merge"
import { format as formatDateFns } from 'date-fns';
import { usePage } from "@inertiajs/react";

export function cn(...inputs) {
  return twMerge(clsx(inputs));
}

/**
 * Get the Inertia app settings if available
 * Returns default values if not in a React context
 */
function getSettings() {
  try {
    // Try to access Inertia page props
    if (typeof window !== 'undefined' && window.__INERTIA) {
      return window.__INERTIA.page.props;
    }
    return {
      decimalPlace: 2,
      decimalSep: '.',
      thousandSep: ',',
      baseCurrency: 'USD',
      currencyPosition: 'left'
    };
  } catch (e) {
    // Return defaults if Inertia is not available
    return {
      decimalPlace: 2,
      decimalSep: '.',
      thousandSep: ',',
      baseCurrency: 'USD',
      currencyPosition: 'left'
    };
  }
}

// Custom hook for React components
export function useFormatUtils() {
  const props = usePage().props;
  
  return {
    formatCurrency: (amount, currency) => {
      // Pass the amount and currency, using the props for all other settings
      if (typeof amount === 'object') {
        // If using object style, merge with props
        const options = amount;
        return formatCurrency({
          ...options,
          currency: options.currency || props.baseCurrency,
          decimalPlaces: options.decimalPlaces || props.decimalPlace,
          thousandSeparator: options.thousandSeparator || props.thousandSep,
          decimalSeparator: options.decimalSeparator || props.decimalSep,
          codePosition: options.codePosition || props.currencyPosition
        });
      }
      // Using direct parameters style
      return formatCurrency(amount, currency || props.baseCurrency, 
                           props.decimalPlace, props.thousandSep, 
                           props.decimalSep, props.currencyPosition);
    }
  };
}

/**
 * Format a number as currency with the given currency code
 * This function accepts both object parameters and direct parameters for backward compatibility
 * 
 * @param {number|Object} amount - The amount to format or an options object
 * @param {string} [currency] - ISO 4217 currency code (e.g., USD, EUR, GBP)
 * @param {number} [decimalPlaces] - Number of decimal places
 * @param {string} [thousandSeparator] - Thousand separator
 * @param {string} [decimalSeparator] - Decimal separator
 * @param {string} [codePosition] - 'left' or 'right'
 * @returns {string} Formatted currency string
 */
export function formatCurrency(amount, currency, decimalPlaces, 
  thousandSeparator, decimalSeparator, codePosition) {
  // Get default settings from Inertia if available
  const settings = getSettings();
  
  // Set defaults using settings from Inertia
  currency = currency || settings.baseCurrency;
  decimalPlaces = decimalPlaces !== undefined ? decimalPlaces : settings.decimalPlace;
  thousandSeparator = thousandSeparator || settings.thousandSep;
  decimalSeparator = decimalSeparator || settings.decimalSep;
  codePosition = codePosition || settings.currencyPosition;
  
  // Check if first argument is an object (new style)
  if (typeof amount === 'object' && amount !== null) {
    const options = amount;
    return formatCurrency(
      options.amount,
      options.currency || currency,
      options.decimalPlaces !== undefined ? options.decimalPlaces : decimalPlaces,
      options.thousandSeparator || thousandSeparator,
      options.decimalSeparator || decimalSeparator,
      options.codePosition || codePosition
    );
  }

  // Regular parameter processing (old and new style)
  // Prepare the fixed‑decimal string
  const fixed = amount == null
    ? (0).toFixed(decimalPlaces)
    : parseFloat(amount).toFixed(decimalPlaces);

  // Split into integer and decimal parts
  const [intPart, decPart] = fixed.split('.');

  // Insert thousand separators
  const formattedInt = intPart.replace(
    /\B(?=(\d{3})+(?!\d))/g,
    thousandSeparator
  );

  // Build the core number string
  const numberStr = decPart != null
    ? `${formattedInt}${decimalSeparator}${decPart}`
    : formattedInt;

  // Place code left or right
  if (codePosition === 'right') {
    return `${numberStr} ${currency}`;
  } else {
    // default to left
    return `${currency} ${numberStr}`;
  }
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


