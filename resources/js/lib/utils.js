import { clsx } from "clsx";
import { twMerge } from "tailwind-merge"

export function cn(...inputs) {
  return twMerge(clsx(inputs));
}

/**
 * Format a number as currency with the given currency code
 * @param {number} amount - The amount to format
 * @param {string} currencyCode - ISO 4217 currency code (e.g., USD, EUR, GBP)
 * @returns {string} Formatted currency string
 */
export function formatCurrency(amount, currencyCode = 'USD') {
  if (amount === null || amount === undefined) return `${currencyCode} 0.00`;
  
  // Format with 2 decimal places
  const formattedAmount = parseFloat(amount).toFixed(2);
  
  // Return formatted string with currency code
  return `${currencyCode} ${formattedAmount}`;
}

/**
 * Format a date string into a readable format
 * @param {string} dateString - Date string in any valid format
 * @param {object} options - Date formatting options
 * @returns {string} Formatted date string
 */
export function formatDate(dateString, options = {}) {
  if (!dateString) return '';
  
  try {
    const date = new Date(dateString);
    
    // Default formatting options
    const defaultOptions = {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      ...options
    };
    
    return date.toLocaleDateString(undefined, defaultOptions);
  } catch (error) {
    console.error('Error formatting date:', error);
    return dateString; // Return the original string if parsing fails
  }
}
