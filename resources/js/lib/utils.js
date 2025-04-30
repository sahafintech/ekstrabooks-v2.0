import { clsx } from "clsx";
import { twMerge } from "tailwind-merge"
import { format as formatDateFns } from 'date-fns';
import * as chrono from 'chrono-node';
import { getSettings } from './settings';

export function cn(...inputs) {
  return twMerge(clsx(inputs));
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
  const settings = getSettings();
  
  currency = currency || settings.baseCurrency;
  decimalPlaces = decimalPlaces !== undefined ? decimalPlaces : settings.decimalPlace;
  thousandSeparator = thousandSeparator || settings.thousandSep;
  decimalSeparator = decimalSeparator || settings.decimalSep;
  codePosition = codePosition || settings.currencyPosition;
  
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

export function formatAmount(amount, decimalPlaces, 
  thousandSeparator, decimalSeparator) {
  const settings = getSettings();
  
  decimalPlaces = decimalPlaces !== undefined ? decimalPlaces : settings.decimalPlace;
  thousandSeparator = thousandSeparator || settings.thousandSep;
  decimalSeparator = decimalSeparator || settings.decimalSep;
  
  if (typeof amount === 'object' && amount !== null) {
    const options = amount;
    return formatCurrency(
      options.amount,
      options.decimalPlaces !== undefined ? options.decimalPlaces : decimalPlaces,
      options.thousandSeparator || thousandSeparator,
      options.decimalSeparator || decimalSeparator,
    );
  }

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

  return `${numberStr}`;
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

export function parseDateObject(dateString) {
  if (!dateString) return null;

  // Try chrono first (it returns a JS Date or null)
  let date = chrono.parseDate(dateString);

  // Fallback to native Date if chrono couldn't figure it out
  if (!date || isNaN(date)) {
    date = new Date(dateString);
  }

  // If still invalid, log and return null
  if (isNaN(date)) {
    console.error('Invalid date:', dateString);
    return null;
  }

  return date;
}
