import _ from "lodash";
import moment from "moment";

/* ============================================
   Base structure
   ============================================ */
const baseRecord = {
    date: "2025-10-01",
    truck_id: 5,
    tracking_numbers: ["100001", "10002"],
    stockable: "MAQ SS",
    stockable_type: "FishType",
    total_weight: 200,
    unit_price: 5.2,
    total_price: 1000
};

/*
invoice : {
    id: 1,
    date: "2025-02-05",
    supplier_ids: [8,5],
    itemes : [{
        date: "2025-10-01",
        truck_id: 5,
        tracking_numbers: ["100001", "10002"],
        stockable: "MAQ SS",
        stockable_type: "FishType",
        total_weight: 200,
        unit_price: 5,
        total_price: 1000
    }]
}
*/
/* ============================================
   Value pools (used for random generation)
   ============================================ */
const pools = {
    date: (i) =>
        moment(baseRecord.date).add(i, "days").format("YYYY-MM-DD"),
    truck_id: [5, 7, 9],
    stockable: ["MAQ SS", "SARDINE", "ANCHOVY"],
    stockable_type: ["FishType"],
    total_weight: [100, 150, 200, 250],
    unit_price: [4.4, 5.3, 6.05,7.1]
};

/* ============================================
   Generate N records
   ============================================ */
function generateRecords(base, pools, count = 10, expandTracking = true) {
    let records = _.times(count, (i) => {
        const r = { ...base };

        _.forEach(pools, (pool, key) => {
            r[key] =
                typeof pool === "function"
                    ? pool(i)
                    : _.sample(pool);
        });

        r.total_price = _.round(r.total_weight * r.unit_price, 2);

        return r;
    });

    // Expand tracking_numbers into individual rows
    if (expandTracking) {
        records = _.flatMap(records, (r) =>
            _.map(r.tracking_numbers, (tn) => ({
                ...r,
                tracking_numbers: tn
            }))
        );
    }

    return records;
}

/* ============================================
   Convert to array of arrays
   ============================================ */
function toArrayOfArrays(records, columns) {
    return _.map(records, (r) =>
        _.map(columns, (c) => r[c])
    );
}

/* ============================================
   Columns order
   ============================================ */
const columns = [
    "date",
    "truck_id",
    "tracking_numbers",
    "stockable",
    "stockable_type",
    "total_weight",
    "unit_price",
    "total_price"
];

/* ============================================
   Exports (Vue / Node)
   ============================================ */
export {
    generateRecords,
    toArrayOfArrays,
    baseRecord,
    pools,
    columns
};
