// randomDataGenerator.js
// ES module — import with:
//   import { generateRandomData } from './randomDataGenerator.js'
//   or: import generateRandomData from './randomDataGenerator.js'

/* ---------- small helpers ---------- */
function randInt(min, max) {
    return Math.floor(Math.random() * (max - min + 1)) + min;
}
function randFloat(min, max, decimals = 2) {
    return Number((Math.random() * (max - min) + min).toFixed(decimals));
}
function fmtDate(d) {
    return d.toISOString().slice(0, 10);
}
function randomDate(startYear = 2026, endYear = 2026) {
    const start = new Date(`${startYear}-01-01T00:00:00Z`).getTime();
    const end = new Date(`${endYear}-01-31T23:59:59Z`).getTime();
    return fmtDate(new Date(randInt(start, end)));
}
function padded(n, length = 4) {
    return String(n).padStart(length, '0');
}
function generateTrackingNumbers(count = 1, length = 4, minNum = 1, maxNum = 9999) {
    return Array.from({ length: count }, () => padded(randInt(minNum, maxNum), length));
}
function generateTruckId() {
    return `${randInt(10000, 99999)}-A-${randInt(1, 30)}`;
}
function pickRandom(arr) {
    return arr[randInt(0, arr.length - 1)];
}
function pickUnique(arr, n) {
    const copy = [...arr];
    const out = [];
    n = Math.min(n, copy.length);
    while (out.length < n) {
        const idx = randInt(0, copy.length - 1);
        out.push(copy.splice(idx, 1)[0]);
    }
    return out;
}

/* ---------- default pools ---------- */
const DEFAULT_SUPPLIERS = [
    "Microsoft",
    "hp",
    "Dacia",
    "Samsung",
    "LG",
    "Siemens",
    "ABB",
    "Toyota",
    "Nestle",
    "Pepsi"
];
const STOCKABLES = ["MAQ SS", "MAQ FRAIS S", "MAQ FRAIS M", "TUNA", "SALMON"];
const STOCKABLE_TYPES = ["FishType", "FrozenType"];
const CHARGE_CATEGORIES = ["Emballage", "Transport", "Manutention", "Autre"];

/**
 * generateRandomData
 *
 * @param {number} count - number of parent records to generate
 * @param {Object} opts - optional overrides:
 *   - supplierNames: array of supplier name strings (default DEFAULT_SUPPLIERS)
 *   - suppliersPerRecordMax: max number of supplier names per parent (default 4)
 *   - itemMin (default 1), itemMax (default 100)
 *   - weightRange [min,max] default [10,2000]
 *   - unitPriceRange [min,max] default [2,20]
 *   - dateRange [startYear,endYear] default [2024,2026]
 *   - parentTracking: {min,max,length,minNum,maxNum} default {min:2,max:3,length:4,minNum:1,maxNum:9999}
 *   - itemTracking: {min,max,length,minNum,maxNum} default {min:1,max:4,length:6,minNum:10000,maxNum:999999}
 *
 * @returns {Array} dataset matching the structure you provided
 */
export function generateRandomData(count = 5, opts = {}) {
    const {
        supplierNames = DEFAULT_SUPPLIERS,
        suppliersPerRecordMax = 2,
        itemMin = 1,
        itemMax = 100,
        weightRange = [10, 2000],
        unitPriceRange = [2, 20],
        dateRange = [2024, 2026],
        parentTracking = { min: 1, max: 2, length: 4, minNum: 1, maxNum: 9999 },
        itemTracking = { min: 1, max: 4, length: 6, minNum: 10000, maxNum: 999999 }
    } = opts;

    const out = [];

    for (let i = 1; i <= count; i++) {
        const itemsCount = randInt(itemMin, itemMax);
        const items = [];

        for (let k = 0; k < itemsCount; k++) {
            const total_weight = randInt(weightRange[0], weightRange[1]);
            const unit_price = randFloat(unitPriceRange[0], unitPriceRange[1], 2);
            const total_price = Number((total_weight * unit_price).toFixed(2));

            items.push({
                date: randomDate(dateRange[0], dateRange[1]),
                charge_id: randInt(1, 50),
                charge_category_id: pickRandom(CHARGE_CATEGORIES),
                truck_id: generateTruckId(),
                tracking_numbers: generateTrackingNumbers(randInt(itemTracking.min, itemTracking.max), itemTracking.length, itemTracking.minNum, itemTracking.maxNum),
                stockable: pickRandom(STOCKABLES),
                stockable_type: pickRandom(STOCKABLE_TYPES),
                total_weight,
                unit_price,
                total_price
            });
        }

        const total_weight_sum = items.reduce((s, it) => s + Number(it.total_weight || 0), 0);
        const total_price_sum = items.reduce((s, it) => s + Number(it.total_price || 0), 0);

        out.push({
            id: i,
            date: randomDate(dateRange[0], dateRange[1]),
            tracking_numbers: generateTrackingNumbers(randInt(parentTracking.min, parentTracking.max), parentTracking.length, parentTracking.minNum, parentTracking.maxNum),
            supplier_ids: pickUnique(supplierNames, randInt(1, suppliersPerRecordMax)),
            items,
            total_weight: total_weight_sum,
            total_price: Number(total_price_sum.toFixed(2))
        });
    }

    return out;
}

/* default export */
export default generateRandomData;
