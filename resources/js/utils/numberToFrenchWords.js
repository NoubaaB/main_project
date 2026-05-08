/**
 * Convert a number to French words.
 * Handles values up to trillions and decimal centimes.
 *
 * @param {number} num — The numeric value
 * @param {string} currency — 'MAD', 'EUR', or 'USD'
 * @returns {string} e.g. "Mille Deux Cents Dirham(s)"
 */
export function numberToFrenchWords(num, currency = 'MAD') {
  if (num === 0) return _zeroWord(currency);

  const isNegative = num < 0;
  num = Math.abs(num);

  const intPart = Math.floor(num);
  const decPart = Math.round((num - intPart) * 100);

  let result = '';
  if (intPart > 0) {
    result = _convert(intPart);
  }

  // Currency word
  const cu = _currencyUnit(currency);
  if (intPart > 0) {
    result += ' ' + cu;
  }

  if (decPart > 0) {
    const cs = _currencySubunit(currency);
    if (intPart > 0) result += ' et ';
    result += _convert(decPart) + ' ' + cs;
  }

  if (intPart === 0 && decPart > 0) {
    // Only subunit, already handled
  } else if (intPart === 0 && decPart === 0) {
    result = _zeroWord(currency);
  }

  if (isNegative) result = 'Moins ' + result;

  return _capitalize(result.trim());
}

function _zeroWord(currency) {
  return 'Zéro ' + _currencyUnit(currency);
}

function _currencyUnit(currency) {
  switch (currency) {
    case 'EUR': return 'Euro(s)';
    case 'USD': return 'Dollar(s)';
    case 'MAD':
    default:    return 'Dirham(s)';
  }
}

function _currencySubunit(currency) {
  switch (currency) {
    case 'EUR': return 'Centime(s)';
    case 'USD': return 'Cent(s)';
    case 'MAD':
    default:    return 'Centime(s)';
  }
}

const UNITS = ['', 'Un', 'Deux', 'Trois', 'Quatre', 'Cinq', 'Six', 'Sept', 'Huit', 'Neuf',
               'Dix', 'Onze', 'Douze', 'Treize', 'Quatorze', 'Quinze', 'Seize', 'Dix-Sept', 'Dix-Huit', 'Dix-Neuf'];
const TENS  = ['', 'Dix', 'Vingt', 'Trente', 'Quarante', 'Cinquante', 'Soixante', 'Soixante', 'Quatre-Vingt', 'Quatre-Vingt'];

function _convert(n) {
  if (n === 0) return '';
  if (n < 0) return 'Moins ' + _convert(-n);

  let result = '';

  if (n >= 1000000000) {
    const billions = Math.floor(n / 1000000000);
    result += (billions === 1 ? 'Un Milliard' : _convert(billions) + ' Milliards');
    n %= 1000000000;
    if (n > 0) result += ' ';
  }

  if (n >= 1000000) {
    const millions = Math.floor(n / 1000000);
    result += (millions === 1 ? 'Un Million' : _convert(millions) + ' Millions');
    n %= 1000000;
    if (n > 0) result += ' ';
  }

  if (n >= 1000) {
    const thousands = Math.floor(n / 1000);
    result += (thousands === 1 ? 'Mille' : _convert(thousands) + ' Mille');
    n %= 1000;
    if (n > 0) result += ' ';
  }

  if (n >= 100) {
    const hundreds = Math.floor(n / 100);
    if (hundreds === 1) {
      result += 'Cent';
    } else {
      result += UNITS[hundreds] + ' Cents';
    }
    n %= 100;
    // If exactly X00, keep "Cents" plural; otherwise singular
    if (n > 0) {
      // Replace trailing "Cents" with "Cent" because there's a remainder
      result = result.replace(/Cents$/, 'Cent');
      result += ' ';
    }
  }

  if (n >= 20) {
    const t = Math.floor(n / 10);
    const u = n % 10;

    if (t === 7 || t === 9) {
      // 70-79: Soixante + (10..19), 90-99: Quatre-Vingt + (10..19)
      result += TENS[t] + '-' + UNITS[10 + u];
    } else {
      result += TENS[t];
      if (u === 1 && t !== 8) {
        result += ' et Un';
      } else if (u > 0) {
        result += '-' + UNITS[u];
      } else if (t === 8) {
        result += 's'; // Quatre-Vingts (with s when exactly 80)
      }
    }
  } else if (n > 0) {
    result += UNITS[n];
  }

  return result;
}

function _capitalize(str) {
  if (!str) return str;
  return str.charAt(0).toUpperCase() + str.slice(1);
}

export default numberToFrenchWords;
