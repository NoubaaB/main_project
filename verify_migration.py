import pymysql

conn = pymysql.connect(host='127.0.0.1', user='root', password='', database='main_project')
c = conn.cursor(pymysql.cursors.DictCursor)

tables = [
    'origins', 'fisheries', 'fish_families', 'fish_process_types', 'fish_types',
    'package_types', 'guano_types', 'warehouses', 'suppliers', 'clients', 'ships', 'trucks',
    'sections', 'corporals', 'section_corporals', 'workforces', 'fortnight_activities',
    'charges', 'detail_charges', 'charge_truck',
    'productions', 'detail_productions',
    'shippings', 'detail_shippings', 'shipping_truck',
    'client_invoices', 'client_payments',
    'supplier_invoices', 'supplier_payments',
    'warehouse_balances', 'warehouse_balance_operations'
]

print("=== TABLE ROW COUNTS ===")
for t in tables:
    c.execute(f"SELECT COUNT(*) as cnt FROM `{t}`")
    print(f"{t:30}: {c.fetchone()['cnt']}")

print("\n=== SAMPLE INVOICE DETAILS & ARTICLE DESIGNATION ===")
c.execute("""
    SELECT ds.id, ds.article_description, ds.weight, ds.unit_price, ds.total_price, 
           s.invoice_id, ci.date, ci.type_invoice, ci.total_price as inv_total_price
    FROM detail_shippings ds
    JOIN shippings s ON ds.shipping_id = s.id
    JOIN client_invoices ci ON s.invoice_id = ci.id
    WHERE ds.article_description IS NOT NULL AND s.invoice_id IS NOT NULL
    LIMIT 5
""")
for r in c.fetchall():
    print("Client Invoice Line:", r)

c.execute("""
    SELECT dc.id, dc.article_description, dc.weight, dc.unit_price, dc.total_price, 
           c.invoice_id, si.date, si.type_invoice, si.total_price as inv_total_price
    FROM detail_charges dc
    JOIN charges c ON dc.charge_id = c.id
    JOIN supplier_invoices si ON c.invoice_id = si.id
    WHERE dc.article_description IS NOT NULL AND c.invoice_id IS NOT NULL
    LIMIT 5
""")
for r in c.fetchall():
    print("Supplier Invoice Line:", r)

print("\n=== WORKFORCE ACTIVITIES VERIFICATION ===")
c.execute("""
    SELECT COUNT(*) as total_acts,
           COUNT(section_id) as with_section,
           COUNT(corporal_id) as with_corporal,
           SUM(CASE WHEN price > 0 THEN 1 ELSE 0 END) as with_price
    FROM fortnight_activities
""")
print("Workforce Stats:", c.fetchone())

print("\n=== WAREHOUSES CATEGORIZATION (Company vs Third-Party) ===")
c.execute("""
    SELECT stock_type, COUNT(*) as count, SUM(total_weight) as sum_weight
    FROM warehouses
    GROUP BY stock_type
""")
for r in c.fetchall():
    print(r)

print("\n=== WAREHOUSE BALANCES & OPERATIONS ===")
c.execute("""
    SELECT warehouse_operation, COUNT(*) as cnt, SUM(total_weight) as sum_tot, SUM(remaining_weight) as sum_rem
    FROM warehouse_balances
    GROUP BY warehouse_operation
""")
for r in c.fetchall():
    print("Balances:", r)

c.execute("""
    SELECT COUNT(*) as total_ops, SUM(weight) as total_deducted_weight, SUM(total_price) as total_val
    FROM warehouse_balance_operations
""")
print("Operations:", c.fetchone())

