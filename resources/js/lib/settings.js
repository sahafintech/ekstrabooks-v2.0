// settings.js
let _settings = {
    decimalPlace:    2,
    decimalSep:      '.',
    thousandSep:     ',',
    baseCurrency:    'USD',
    currencyPosition:'left',
    date_format:     'd/m/Y',
  };
  
  export function initSettings(newSettings) {
    _settings = { ..._settings, ...newSettings };
  }
  
  export function getSettings() {
    return _settings;
  }
  