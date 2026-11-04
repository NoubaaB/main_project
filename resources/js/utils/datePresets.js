/**
 * Date Preset Utilities for ListCharge, ListProduction, ListShipping
 * Provides helper functions to calculate date ranges for presets:
 * - today
 * - this_week
 * - this_month
 * - last_month
 * - this_year
 */

export function fmtDate(d) {
  if (!d) return '';
  if (typeof d === 'string') return d.slice(0, 10);
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${y}-${m}-${day}`;
}

export function getDatePresetRange(preset) {
  const now = new Date();
  const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
  let start, end;

  switch (preset) {
    case 'today':
      start = new Date(today);
      end = new Date(today);
      break;

    case 'this_week': {
      const day = today.getDay(); // 0 is Sunday, 1 is Monday...
      const diffToMonday = (day === 0 ? -6 : 1) - day;
      start = new Date(today.getFullYear(), today.getMonth(), today.getDate() + diffToMonday);
      end = new Date(today);
      break;
    }

    case 'last_week': {
      const day = today.getDay(); // 0 is Sunday, 1 is Monday...
      const diffToMonday = (day === 0 ? -6 : 1) - day;
      const thisMonday = new Date(today.getFullYear(), today.getMonth(), today.getDate() + diffToMonday);
      start = new Date(thisMonday.getFullYear(), thisMonday.getMonth(), thisMonday.getDate() - 7);
      end = new Date(thisMonday.getFullYear(), thisMonday.getMonth(), thisMonday.getDate() - 1);
      break;
    }

    case 'this_month':
      start = new Date(today.getFullYear(), today.getMonth(), 1);
      end = new Date(today);
      break;

    case 'last_month':
      start = new Date(today.getFullYear(), today.getMonth() - 1, 1);
      end = new Date(today.getFullYear(), today.getMonth(), 0);
      break;

    case 'this_year':
      start = new Date(today.getFullYear(), 0, 1);
      end = new Date(today);
      break;

    default:
      return null;
  }

  return { start, end };
}

export function detectMatchingPreset(startDate, endDate) {
  if (!startDate || !endDate) return null;
  const sStr = fmtDate(startDate);
  const eStr = fmtDate(endDate);

  const presets = ['today', 'this_week', 'last_week', 'this_month', 'last_month', 'this_year'];
  for (const p of presets) {
    const r = getDatePresetRange(p);
    if (r && fmtDate(r.start) === sStr && fmtDate(r.end) === eStr) {
      return p;
    }
  }
  return null;
}

export function getDatePresetOptions(t) {
  const translate = typeof t === 'function' ? t : (k => k);
  return [
    { value: 'today', label: translate('today'), icon: 'mdi-calendar-today' },
    { value: 'this_week', label: translate('this_week'), icon: 'mdi-calendar-week' },
    { value: 'last_week', label: translate('last_week'), icon: 'mdi-calendar-arrow-left' },
  ];
}

