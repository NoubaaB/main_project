#!/usr/bin/env python3
"""
Production Database Migration Runner: villamar.sql -> main_project
Author: Senior AI Database Engineer
Repository: https://github.com/NoubaaB/main_project
"""

import sys
import os
import json
import re
import time
from datetime import datetime, date
import pymysql
import pymysql.cursors

# Database Connection Settings
SRC_DB = {
    'host': '127.0.0.1',
    'port': 3306,
    'user': 'root',
    'password': '',
    'database': 'villamar',
    'charset': 'utf8mb4'
}

TGT_DB = {
    'host': '127.0.0.1',
    'port': 3306,
    'user': 'root',
    'password': '',
    'database': 'main_project',
    'charset': 'utf8mb4'
}

def log(msg):
    now = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    print(f"[{now}] {msg}", flush=True)

def safe_json(val):
    if not val:
        return []
    if isinstance(val, (list, dict)):
        return val
    try:
        return json.loads(val)
    except:
        return []

def safe_float(val, default=0.0):
    if val is None:
        return default
    try:
        if isinstance(val, str):
            val = val.replace(',', '').strip()
        f = float(val)
        return 0.0 if (f != f) else round(f, 2)
    except:
        return default

def safe_date(val, default='2021-01-01'):
    if not val:
        return default
    if isinstance(val, (datetime, date)):
        return val.strftime('%Y-%m-%d')
    s = str(val).strip()
    m = re.search(r'(\d{4}-\d{2}-\d{2})', s)
    if m:
        return m.group(1)
    m2 = re.search(r'(\d{4}-\d{2})', s)
    if m2:
        return f"{m2.group(1)}-01"
    return default

def clean_plate(plate):
    if not plate:
        return None
    s = str(plate).strip().strip('"[]\'').upper()
    s = re.sub(r'[\s/]+', '-', s)
    s = re.sub(r'[^A-Z0-9\-]', '', s)
    return s if len(s) >= 3 else None

def clean_tracking_str(val):
    if not val:
        return None
    j = safe_json(val)
    if isinstance(j, list) and len(j) > 0:
        return str(j[0])[:255]
    s = str(val).strip('[]"\' ')
    return s[:255] if s else None

def get_db_conns():
    src_conn = pymysql.connect(**SRC_DB, cursorclass=pymysql.cursors.DictCursor)
    tgt_conn = pymysql.connect(**TGT_DB, cursorclass=pymysql.cursors.DictCursor)
    return src_conn, tgt_conn

# -------------------------------------------------------------
# STAGE 0: Clear Target Tables
# -------------------------------------------------------------
def clear_target_database(tgt_conn):
    log("=== STAGE 0: Clearing Target Database Tables ===")
    cursor = tgt_conn.cursor()
    cursor.execute("SET FOREIGN_KEY_CHECKS = 0;")
    
    tables_to_clear = [
        'warehouse_balance_operations',
        'warehouse_balances',
        'warehouse_stock_resets',
        'detail_warehouse_transfers',
        'warehouse_transfers',
        'warehouse_transfer_truck',
        'detail_inventories',
        'inventories',
        'shipping_truck',
        'detail_shippings',
        'shippings',
        'client_payments',
        'client_invoices',
        'client_fiscal_year_closing_lines',
        'client_fiscal_year_closings',
        'storage_invoice_lines',
        'storage_invoices',
        'detail_productions',
        'productions',
        'charge_truck',
        'detail_charges',
        'charges',
        'supplier_payments',
        'supplier_invoices',
        'fiscal_year_closing_lines',
        'fiscal_year_closings',
        'fortnight_activities',
        'workforce_fortnight_payments',
        'workforce_movements',
        'team_workforce',
        'teams',
        'workforces',
        'section_corporals',
        'corporals',
        'sections',
        'warehouses',
        'trucks',
        'ships',
        'suppliers',
        'clients',
        'guano_types',
        'package_type_components',
        'package_type_stocks',
        'package_types',
        'fish_types',
        'fish_families',
        'fish_process_types',
        'origins',
        'fisheries',
    ]

    for t in tables_to_clear:
        try:
            cursor.execute(f"TRUNCATE TABLE `{t}`;")
        except Exception:
            cursor.execute(f"DELETE FROM `{t}`;")
    
    tgt_conn.commit()
    log("Target database tables successfully cleared (preserved users, categories, configuration).")

# -------------------------------------------------------------
# STAGE 1: Reference Catalogs & Master Data
# -------------------------------------------------------------
def migrate_master_catalogs(src_conn, tgt_conn):
    log("=== STAGE 1: Migrating Master Reference Catalogs ===")
    s_cur = src_conn.cursor()
    t_cur = tgt_conn.cursor()

    valid_origins = set()
    valid_fisheries = set()
    valid_suppliers = set()
    valid_clients = set()
    valid_warehouses = set()

    # 1. Origins
    s_cur.execute("SELECT * FROM origins")
    origins = s_cur.fetchall()
    for o in origins:
        o_name = o.get('name_origin') or o.get('name') or f"Origine #{o['id']}"
        t_cur.execute(
            "INSERT INTO origins (id, name, created_at, updated_at, deleted_at) VALUES (%s, %s, %s, %s, %s)",
            (o['id'], o_name, o['created_at'], o['updated_at'], o['deleted_at'])
        )
        valid_origins.add(o['id'])
    log(f"Migrated {len(origins)} origins.")

    # 2. Fisheries (places)
    s_cur.execute("SELECT * FROM places")
    places = s_cur.fetchall()
    for p in places:
        p_name = p.get('name_place') or p.get('name') or f"Port #{p['id']}"
        t_cur.execute(
            "INSERT INTO fisheries (id, name, name_fr, name_en, name_es, created_at, updated_at, deleted_at) VALUES (%s, %s, %s, %s, %s, %s, %s, %s)",
            (p['id'], p_name, p_name, p_name, p_name, p['created_at'], p['updated_at'], p['deleted_at'])
        )
        valid_fisheries.add(p['id'])
    log(f"Migrated {len(places)} fisheries.")

    # 3. Fish Families
    s_cur.execute("SELECT * FROM families")
    families = s_cur.fetchall()
    for f in families:
        f_name = f.get('name') or f"Famille #{f['id']}"
        t_cur.execute(
            "INSERT INTO fish_families (id, name_fr, name_en, name_es, created_at, updated_at, deleted_at) VALUES (%s, %s, %s, %s, %s, %s, %s)",
            (f['id'], f_name, f_name, f_name, f['created_at'], f['updated_at'], f['deleted_at'])
        )
    log(f"Migrated {len(families)} fish families.")

    # 4. Fish Process Types
    for pt in [(1, 'Entier'), (2, 'HGT'), (3, 'Filet'), (4, 'HG')]:
        t_cur.execute(
            "INSERT INTO fish_process_types (id, name, created_at, updated_at) VALUES (%s, %s, NOW(), NOW())",
            pt
        )

    # 5. Fish Species (types)
    s_cur.execute("SELECT * FROM types")
    types = s_cur.fetchall()
    fish_type_names = {}
    for row in types:
        name = row['name_type'] or f"Type #{row['id']}"
        fish_type_names[row['id']] = name
        proc_str = row['process'] or 'Entier'
        proc_id = {'HGT': 2, 'Filet': 3, 'HG': 4}.get(proc_str, 1)
        code_art = 'frozen' if row['code_article'] == 'congele' else 'fresh'

        t_cur.execute(
            """INSERT INTO fish_types 
               (id, name_fr, name_en, name_es, name_in_invoice, type, process, fish_family_id, fish_process_type_id, created_at, updated_at, deleted_at)
               VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)""",
            (row['id'], name, name, name, name, code_art, proc_str, row['family_id'], proc_id, row['created_at'], row['updated_at'], row['deleted_at'])
        )
    log(f"Migrated {len(types)} fish species.")

    # 6. Packaging Types (containers)
    s_cur.execute("SELECT * FROM containers")
    containers = s_cur.fetchall()
    package_type_names = {}
    for row in containers:
        name = row['name_container'] or f"Carton #{row['id']}"
        package_type_names[row['id']] = name
        size = int(safe_float(row.get('size') or 0))
        t_cur.execute(
            """INSERT INTO package_types 
               (id, name_fr, name_en, name_es, name_in_invoice, size, description, type_emballage, created_at, updated_at, deleted_at)
               VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)""",
            (row['id'], name, name, name, name, size, row.get('type'), row.get('type'), row['created_at'], row['updated_at'], row['deleted_at'])
        )
    log(f"Migrated {len(containers)} packaging types.")

    # 7. Guano Types
    guano_types = [
        (1, 'Guano Entier', 'Entier'),
        (2, 'Guano HGT', 'HGT'),
        (3, 'Guano HG', 'HG'),
        (4, 'Guano Filet', 'Filet'),
    ]
    for gid, gname, gproc in guano_types:
        t_cur.execute(
            """INSERT INTO guano_types 
               (id, name_fr, name_en, name_es, name_in_invoice, process, created_at, updated_at) 
               VALUES (%s, %s, %s, %s, %s, %s, NOW(), NOW())""",
            (gid, gname, gname, gname, gname, gproc)
        )

    # 8. Warehouses (chambres)
    s_cur.execute("SELECT * FROM chambres")
    chambres = s_cur.fetchall()
    warehouse_names = {}
    for ch in chambres:
        c_id = ch['id']
        c_name = ch['name_chambre'] or f"Chambre #{c_id}"
        warehouse_names[c_id] = c_name
        u_name = c_name.upper()

        if 'FRAIS' in u_name:
            wh_cat_id = 1
        elif 'EMBALLAGE' in u_name or 'MAGASIN' in u_name:
            wh_cat_id = 4
        elif 'GUANO' in u_name or 'DECHET' in u_name:
            wh_cat_id = 3
        else:
            wh_cat_id = 2

        stock_type = 'company' if ch['stock_id'] == 1 else 'third_party'
        has_sejour = 1 if stock_type == 'third_party' else 0

        t_cur.execute(
            """INSERT INTO warehouses 
               (id, name, warehouse_category_id, warehouse_type, stock_type, has_sejour, operation_service_fee, is_allow_price, allow_production_effect, total_weight, created_at, updated_at, deleted_at)
               VALUES (%s, %s, %s, %s, %s, %s, 0.00, 1, 0, 0.00, %s, %s, %s)""",
            (c_id, c_name, wh_cat_id, 'FishType', stock_type, has_sejour, ch['created_at'], ch['updated_at'], ch['deleted_at'])
        )
        valid_warehouses.add(c_id)

    # Insert system default warehouses
    system_whs = [
        (991, 'Entrepôt Frais Principal', 1, 'FishType', 'company'),
        (992, 'Entrepôt Guano', 3, 'GuanoType', 'company'),
        (993, 'Entrepôt Emballage', 4, 'PackageType', 'company'),
    ]
    for s_wh in system_whs:
        warehouse_names[s_wh[0]] = s_wh[1]
        t_cur.execute(
            """INSERT INTO warehouses 
               (id, name, warehouse_category_id, warehouse_type, stock_type, has_sejour, operation_service_fee, is_allow_price, allow_production_effect, total_weight, created_at, updated_at)
               VALUES (%s, %s, %s, %s, %s, 0, 0.00, 1, 0, 0.00, NOW(), NOW())
               ON DUPLICATE KEY UPDATE name=VALUES(name)""",
            s_wh
        )
        valid_warehouses.add(s_wh[0])
    log(f"Migrated {len(chambres)} warehouses + 3 default system warehouses.")

    # 9. Partners (providers -> suppliers and clients)
    s_cur.execute("SELECT * FROM providers")
    providers = s_cur.fetchall()
    sup_seen = set()
    cli_seen = set()

    for p in providers:
        p_id = p['id']
        raw_name = p.get('name_provider') or f"Partner #{p_id}"
        ice = p.get('ice')

        s_name = f"{raw_name} #{p_id}" if raw_name in sup_seen else raw_name
        sup_seen.add(s_name)
        t_cur.execute(
            """INSERT INTO suppliers (id, name, ice, created_at, updated_at, deleted_at)
               VALUES (%s, %s, %s, %s, %s, %s)
               ON DUPLICATE KEY UPDATE name=VALUES(name)""",
            (p_id, s_name, ice, p['created_at'], p['updated_at'], p['deleted_at'])
        )
        valid_suppliers.add(p_id)

        c_name = f"{raw_name} #{p_id}" if raw_name in cli_seen else raw_name
        cli_seen.add(c_name)
        t_cur.execute(
            """INSERT INTO clients (id, name, ice, created_at, updated_at, deleted_at)
               VALUES (%s, %s, %s, %s, %s, %s)
               ON DUPLICATE KEY UPDATE name=VALUES(name)""",
            (p_id, c_name, ice, p['created_at'], p['updated_at'], p['deleted_at'])
        )
        valid_clients.add(p_id)

    log(f"Migrated {len(valid_suppliers)} suppliers and {len(valid_clients)} clients.")

    # 10. Ships (ships)
    s_cur.execute("SELECT * FROM ships")
    ships = s_cur.fetchall()
    valid_ships = set()
    ship_batch = []
    for s in ships:
        s_name = s.get('ship_name') or f"Navire #{s['id']}"
        ship_batch.append((s['id'], s_name, s['created_at'], s['updated_at'], s['deleted_at']))
        valid_ships.add(s['id'])
        if len(ship_batch) >= 1000:
            t_cur.executemany("INSERT INTO ships (id, name, created_at, updated_at, deleted_at) VALUES (%s, %s, %s, %s, %s)", ship_batch)
            ship_batch = []
    if ship_batch:
        t_cur.executemany("INSERT INTO ships (id, name, created_at, updated_at, deleted_at) VALUES (%s, %s, %s, %s, %s)", ship_batch)
    log(f"Migrated {len(ships)} ships.")

    tgt_conn.commit()
    return {
        'fish_type_names': fish_type_names,
        'package_type_names': package_type_names,
        'warehouse_names': warehouse_names,
        'valid_origins': valid_origins,
        'valid_fisheries': valid_fisheries,
        'valid_suppliers': valid_suppliers,
        'valid_clients': valid_clients,
        'valid_warehouses': valid_warehouses,
        'valid_ships': valid_ships,
    }

# -------------------------------------------------------------
# STAGE 2: Client & Supplier Invoices Pre-population
# -------------------------------------------------------------
def migrate_invoices_base(src_conn, tgt_conn, cat_data):
    log("=== STAGE 2: Migrating Client Invoices (bills) ===")
    s_cur = src_conn.cursor()
    t_cur = tgt_conn.cursor()

    valid_clients = cat_data['valid_clients']
    s_cur.execute("SELECT * FROM bills")
    bills = s_cur.fetchall()
    valid_client_invoices = set()
    cli_inv_batch = []

    for b in bills:
        bid = b['id']
        valid_client_invoices.add(bid)
        b_date = safe_date(b.get('date_bill'))
        num_b = str(b.get('num_bill') or bid).strip()
        tr_json = json.dumps([num_b])
        type_inv = 'export' if b.get('type_bill') == 'export' else 'local'
        cli_id = b.get('provider_id') if b.get('provider_id') in valid_clients else None
        tot_p = safe_float(b.get('total_price_sortie'))
        tot_w = safe_float(b.get('total_weight_sortie'))

        cli_inv_batch.append((
            bid, b_date, type_inv, tr_json, cli_id, tot_p, tot_w, 0,
            b['created_at'], b['updated_at'], b.get('deleted_at')
        ))

    t_cur.executemany(
        """INSERT INTO client_invoices 
           (id, date, type_invoice, tracking_numbers, client_id, total_price, total_weight, print_counter, created_at, updated_at, deleted_at)
           VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
           ON DUPLICATE KEY UPDATE total_price=VALUES(total_price), total_weight=VALUES(total_weight)""",
        cli_inv_batch
    )
    tgt_conn.commit()
    log(f"Migrated {len(bills)} client invoices.")
    return valid_client_invoices

# -------------------------------------------------------------
# STAGE 3: Vehicle Plates Pre-population
# -------------------------------------------------------------
def get_truck_plate_mapping(src_conn, tgt_conn):
    log("=== STAGE 3: Extracting Unique Vehicle Registration Plates ===")
    s_cur = src_conn.cursor()
    t_cur = tgt_conn.cursor()

    plates = set()
    sources = [
        ("SELECT matricules, matricule FROM ventes", ['matricules', 'matricule']),
        ("SELECT matricules, matricule FROM frais", ['matricules', 'matricule']),
        ("SELECT matricules, matricule FROM congelation_clients", ['matricules', 'matricule']),
        ("SELECT matricules, matricule FROM guano_usines", ['matricules', 'matricule']),
        ("SELECT matricules, matricule FROM glaces", ['matricules', 'matricule']),
        ("SELECT matricules, matricule FROM emballages", ['matricules', 'matricule']),
        ("SELECT matricules, matricule FROM congeles", ['matricules', 'matricule']),
        ("SELECT matricules FROM stock_movement_logs", ['matricules']),
        ("SELECT matricules FROM stock_exits", ['matricules']),
        ("SELECT matricules FROM glace_clients", ['matricules']),
    ]

    for q, cols in sources:
        try:
            s_cur.execute(q)
            for r in s_cur.fetchall():
                for c in cols:
                    val = r.get(c)
                    if not val:
                        continue
                    if isinstance(val, str) and (val.startswith('[') or val.startswith('{')):
                        for p in safe_json(val):
                            cp = clean_plate(p)
                            if cp:
                                plates.add(cp)
                    else:
                        for part in str(val).split(','):
                            cp = clean_plate(part)
                            if cp:
                                plates.add(cp)
        except Exception:
            pass

    log(f"Identified {len(plates)} distinct truck registration plates.")
    plate_to_id = {}
    for p in sorted(list(plates)):
        t_cur.execute(
            "INSERT INTO trucks (registration_plate, created_at, updated_at) VALUES (%s, NOW(), NOW())",
            (p,)
        )
        plate_to_id[p] = t_cur.lastrowid

    tgt_conn.commit()
    return plate_to_id

# -------------------------------------------------------------
# STAGE 4: Inbound Operational Charges (charges & detail_charges)
# -------------------------------------------------------------
def migrate_charges(src_conn, tgt_conn, cat_data, plate_to_id):
    log("=== STAGE 4: Migrating Achats -> charges & detail_charges ===")
    s_cur = src_conn.cursor()
    t_cur = tgt_conn.cursor()

    fish_type_names = cat_data['fish_type_names']
    package_type_names = cat_data['package_type_names']
    valid_suppliers = cat_data['valid_suppliers']
    valid_fisheries = cat_data['valid_fisheries']
    valid_origins = cat_data['valid_origins']
    valid_ships = cat_data['valid_ships']

    charge_truck_batch = []
    detail_charges_batch = []

    # 1. Fresh Fish Receptions (frais, 895 rows)
    s_cur.execute("SELECT * FROM frais WHERE deleted_at IS NULL")
    frais_rows = s_cur.fetchall()
    log(f"Processing {len(frais_rows)} fresh fish purchase vouchers...")

    for f in frais_rows:
        cid = f['id']
        c_date = safe_date(f['date_reception'])
        tot_p = safe_float(f['total_price_reception'])
        tot_w = safe_float(f['total_weight_reception'])
        bon_no = f.get('num_bon_reception')
        rtn_json = json.dumps([bon_no]) if bon_no else None
        sup_id = f.get('provider_id') if f.get('provider_id') in valid_suppliers else None
        fish_id = f.get('place_id') if f.get('place_id') in valid_fisheries else None
        orig_id = f.get('origin_id') if f.get('origin_id') in valid_origins else None
        ship_val = f.get('name_ship')
        ship_id = int(ship_val) if (isinstance(ship_val, int) or (isinstance(ship_val, str) and ship_val.isdigit())) and int(ship_val) in valid_ships else None

        t_cur.execute(
            """INSERT INTO charges 
               (id, date, total_price, total_weight, charge_category_id, reception_tracking_number, 
                fishery_id, ship_id, origin_id, supplier_id, allow_invoice, created_at, updated_at)
               VALUES (%s, %s, %s, %s, 1, %s, %s, %s, %s, %s, 1, %s, %s)""",
            (cid, c_date, tot_p, tot_w, rtn_json, fish_id, ship_id, orig_id, sup_id, f['created_at'], f['updated_at'])
        )

        mats = safe_json(f.get('matricules')) or [f.get('matricule')]
        for m in mats:
            cp = clean_plate(m)
            if cp and cp in plate_to_id:
                charge_truck_batch.append((cid, plate_to_id[cp]))

        items = safe_json(f.get('types_reception'))
        for it in items:
            t_id = it.get('type_id')
            w = safe_float(it.get('weight'))
            pu = safe_float(it.get('price'))
            line_tot = safe_float(it.get('total_weight_price') or (w * pu))
            art_name = fish_type_names.get(t_id, f"Poisson #{t_id}")

            detail_charges_batch.append((
                cid, 1, art_name, 'App\\Models\\FishType', t_id,
                w, pu, line_tot, w, None, None, 991, 'stock_in', None, sup_id,
                f['created_at'], f['updated_at']
            ))

    tgt_conn.commit()

    # 2. Frozen Fish Purchases (congeles, 15 rows)
    s_cur.execute("SELECT * FROM congeles WHERE deleted_at IS NULL")
    for row in s_cur.fetchall():
        cid = 100000 + row['id']
        c_date = safe_date(row.get('date_reception') or row.get('date_production'))
        tot_p = safe_float(row.get('total_price_production') or row.get('total_price_reception'))
        tot_w = safe_float(row.get('total_weight_production') or row.get('total_weight_reception'))
        bon_no = row.get('num_bon_reception') or row.get('num_bon_production')
        rtn_json = json.dumps([bon_no]) if bon_no else None
        sup_id = row.get('provider_id') if row.get('provider_id') in valid_suppliers else None

        t_cur.execute(
            """INSERT INTO charges 
               (id, date, total_price, total_weight, charge_category_id, reception_tracking_number, 
                supplier_id, allow_invoice, created_at, updated_at)
               VALUES (%s, %s, %s, %s, 3, %s, %s, 1, %s, %s)""",
            (cid, c_date, tot_p, tot_w, rtn_json, sup_id, row['created_at'], row['updated_at'])
        )

        items = safe_json(row.get('types_production') or row.get('types_reception'))
        for it in items:
            t_id = it.get('type_id')
            w = safe_float(it.get('total_weight_container') or it.get('weight'))
            pu = safe_float(it.get('pu') or it.get('price'))
            line_tot = safe_float(it.get('price') or (w * pu))
            art_name = fish_type_names.get(t_id, f"Poisson Congelé #{t_id}")

            detail_charges_batch.append((
                cid, 3, art_name, 'App\\Models\\FishType', t_id,
                w, pu, line_tot, w, it.get('container_id'), it.get('nbr'), 13, 'stock_in', None, sup_id,
                row['created_at'], row['updated_at']
            ))

    tgt_conn.commit()

    # 3. Packaging Consumables (emballages, 435 rows)
    s_cur.execute("SELECT * FROM emballages WHERE deleted_at IS NULL")
    for row in s_cur.fetchall():
        cid = 200000 + row['id']
        c_date = safe_date(row['date_reception'])
        tot_p = safe_float(row['total_price_reception'])
        tot_w = safe_float(row['total_weight_reception'])
        bon_no = row.get('num_bon_reception')
        rtn_json = json.dumps([bon_no]) if bon_no else None
        sup_id = row.get('provider_id') if row.get('provider_id') in valid_suppliers else None

        t_cur.execute(
            """INSERT INTO charges 
               (id, date, total_price, total_weight, charge_category_id, reception_tracking_number, 
                supplier_id, allow_invoice, created_at, updated_at)
               VALUES (%s, %s, %s, %s, 5, %s, %s, 1, %s, %s)""",
            (cid, c_date, tot_p, tot_w, rtn_json, sup_id, row['created_at'], row['updated_at'])
        )

        items = safe_json(row.get('types_reception'))
        for it in items:
            cont_id = it.get('container_id')
            w = safe_float(it.get('qte'))
            pu = safe_float(it.get('price'))
            line_tot = safe_float(it.get('qte_price') or (w * pu))
            art_name = package_type_names.get(cont_id, f"Emballage #{cont_id}")

            detail_charges_batch.append((
                cid, 5, art_name, 'App\\Models\\PackageType', cont_id,
                w, pu, line_tot, w, cont_id, None, 993, 'stock_in', None, sup_id,
                row['created_at'], row['updated_at']
            ))

    tgt_conn.commit()

    # 4. Plant Ice Purchases (glaces, 526 rows)
    s_cur.execute("SELECT * FROM glaces WHERE deleted_at IS NULL")
    for row in s_cur.fetchall():
        cid = 300000 + row['id']
        c_date = safe_date(row['date_reception'])
        tot_p = safe_float(row['total_price_reception'])
        tot_w = safe_float(row['total_weight_reception'])
        pu = safe_float(row['pu'])
        bon_no = row.get('num_bon_reception')
        rtn_json = json.dumps([bon_no]) if bon_no else None
        sup_id = row.get('provider_id') if row.get('provider_id') in valid_suppliers else None

        t_cur.execute(
            """INSERT INTO charges 
               (id, date, total_price, total_weight, charge_category_id, reception_tracking_number, 
                supplier_id, allow_invoice, created_at, updated_at)
               VALUES (%s, %s, %s, %s, 7, %s, %s, 1, %s, %s)""",
            (cid, c_date, tot_p, tot_w, rtn_json, sup_id, row['created_at'], row['updated_at'])
        )

        detail_charges_batch.append((
            cid, 7, 'Glace', None, None,
            tot_w, pu, tot_p, tot_w, None, None, None, 'neutre', None, sup_id,
            row['created_at'], row['updated_at']
        ))

    tgt_conn.commit()

    # 5. Raw Guano Purchases (guanos, 114 rows)
    s_cur.execute("SELECT * FROM guanos WHERE deleted_at IS NULL")
    for row in s_cur.fetchall():
        cid = 400000 + row['id']
        c_date = safe_date(row.get('date_reception') or row.get('date_production'))
        tot_p = safe_float(row.get('total_price_reception_guano') or row.get('total_price_reception'))
        tot_w = safe_float(row.get('total_weight_reception_guano') or row.get('total_weight_reception'))
        bon_no = row.get('num_bon_reception')
        rtn_json = json.dumps([bon_no]) if bon_no else None
        sup_id = row.get('provider_id') if row.get('provider_id') in valid_suppliers else None

        t_cur.execute(
            """INSERT INTO charges 
               (id, date, total_price, total_weight, charge_category_id, reception_tracking_number, 
                supplier_id, allow_invoice, created_at, updated_at)
               VALUES (%s, %s, %s, %s, 4, %s, %s, 1, %s, %s)""",
            (cid, c_date, tot_p, tot_w, rtn_json, sup_id, row['created_at'], row['updated_at'])
        )

        detail_charges_batch.append((
            cid, 4, 'Guano Brut', 'App\\Models\\GuanoType', 1,
            tot_w, tot_p / tot_w if tot_w > 0 else 0, tot_p, tot_w, None, None, 992, 'stock_in', None, sup_id,
            row['created_at'], row['updated_at']
        ))

    tgt_conn.commit()

    # 6. Workforce Labor Shifts (mods, 7,959 rows)
    s_cur.execute("SELECT * FROM mods WHERE deleted_at IS NULL")
    for row in s_cur.fetchall():
        cid = 700000 + row['id']
        tot_p = safe_float(row['total'])
        t_cur.execute(
            """INSERT INTO charges (id, date, total_price, total_weight, charge_category_id, allow_invoice, created_at, updated_at)
               VALUES (%s, %s, %s, 0.00, 10, 0, %s, %s)""",
            (cid, safe_date(row['date_pointage']), tot_p, row['created_at'], row['updated_at'])
        )
        detail_charges_batch.append((
            cid, 10, f"Main d'oeuvre (Caporal #{row.get('corporal_id')})", None, None,
            0.0, 0.0, tot_p, 0.0, None, None, None, 'neutre', None, None,
            row['created_at'], row['updated_at']
        ))

    tgt_conn.commit()

    # 7. Miscellaneous Supplies, Maintenance, Transports
    s_cur.execute("SELECT * FROM others WHERE deleted_at IS NULL")
    for row in s_cur.fetchall():
        cid = 600000 + row['id']
        sup_id = row.get('provider_id') if row.get('provider_id') in valid_suppliers else None
        tot_p = safe_float(row['total_price_reception'])
        t_cur.execute(
            """INSERT INTO charges (id, date, total_price, total_weight, charge_category_id, supplier_id, allow_invoice, created_at, updated_at)
               VALUES (%s, %s, %s, 0.00, 10, %s, 1, %s, %s)""",
            (cid, safe_date(row['date_reception']), tot_p, sup_id, row['created_at'], row['updated_at'])
        )
        detail_charges_batch.append((
            cid, 10, 'Autres Charges', None, None,
            0.0, 0.0, tot_p, 0.0, None, None, None, 'neutre', None, sup_id,
            row['created_at'], row['updated_at']
        ))

    tgt_conn.commit()

    # Batch Insert detail_charges
    log(f"Inserting {len(detail_charges_batch)} detail_charges rows in chunks...")
    chunk_size = 5000
    for i in range(0, len(detail_charges_batch), chunk_size):
        chunk = detail_charges_batch[i:i+chunk_size]
        t_cur.executemany(
            """INSERT INTO detail_charges 
               (charge_id, detail_charge_category_id, article_description, stockable_type, stockable_id,
                weight, unit_price, total_price, weight_invoice, package_type_id, nbr_box, warehouse_id, warehouse_operation,
                truck_id, supplier_id, created_at, updated_at)
               VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)""",
            chunk
        )

    if charge_truck_batch:
        for i in range(0, len(charge_truck_batch), chunk_size):
            t_cur.executemany("INSERT IGNORE INTO charge_truck (charge_id, truck_id) VALUES (%s, %s)", charge_truck_batch[i:i+chunk_size])

    tgt_conn.commit()
    log("Charges and detail_charges migration completed.")

# -------------------------------------------------------------
# STAGE 5: Factory Processing (productions & detail_productions)
# -------------------------------------------------------------
def migrate_production(src_conn, tgt_conn, cat_data, valid_client_invoices):
    log("=== STAGE 5: Migrating Factory Processing -> productions & detail_productions ===")
    s_cur = src_conn.cursor()
    t_cur = tgt_conn.cursor()

    fish_type_names = cat_data['fish_type_names']
    valid_suppliers = cat_data['valid_suppliers']
    valid_clients = cat_data['valid_clients']
    valid_warehouses = cat_data['valid_warehouses']

    detail_prod_batch = []

    # 1. Blast Freezing Tunnels (congelations, 4 rows)
    s_cur.execute("SELECT * FROM congelations WHERE deleted_at IS NULL")
    for row in s_cur.fetchall():
        pid = row['id']
        p_date = safe_date(row['date_reception'] or row['date_production'])
        tot_w = safe_float(row['total_weight_production'])
        tot_p = safe_float(row['total_price_production'])
        bon_no = row.get('num_bon_reception') or row.get('num_bon_production')
        ptn_json = json.dumps([bon_no]) if bon_no else None
        sup_id = row.get('provider_id') if row.get('provider_id') in valid_suppliers else None

        fresh_in = 0.0
        for it in safe_json(row.get('types_reception')):
            fresh_in += safe_float(it.get('weight'))

        t_cur.execute(
            """INSERT INTO productions 
               (id, date, total_price, total_weight, fresh_reception_weight, frozen_production_weight, 
                equilibrium, equilibrium_status, production_category_id, production_tracking_number,
                supplier_id, allow_invoice, total_secondary_packaging_cost, created_at, updated_at)
               VALUES (%s, %s, %s, %s, %s, %s, %s, 'balanced', 1, %s, %s, 1, 0.00, %s, %s)""",
            (pid, p_date, tot_p, tot_w, fresh_in, tot_w, (tot_w - fresh_in), ptn_json, sup_id, row['created_at'], row['updated_at'])
        )

        for it in safe_json(row.get('types_production')):
            t_id = it.get('type_id')
            w = safe_float(it.get('total_weight_container') or (safe_float(it.get('nbr')) * 20.0))
            pu = safe_float(it.get('pu'))
            p = safe_float(it.get('price') or (w * pu))
            art_name = fish_type_names.get(t_id, f"Poisson Produit #{t_id}")
            raw_ch = it.get('chambre_id')
            ch_id = raw_ch if raw_ch in valid_warehouses else 13

            detail_prod_batch.append((
                pid, 1, art_name, 'App\\Models\\FishType', t_id,
                w, pu, p, 0.00, it.get('container_id'), it.get('nbr'), ch_id, 'stock_in', None,
                row['created_at'], row['updated_at']
            ))

    tgt_conn.commit()

    # 2. Fishmeal Factory Production (guano_usines, 806 rows)
    s_cur.execute("SELECT * FROM guano_usines WHERE deleted_at IS NULL")
    for row in s_cur.fetchall():
        pid = 50000 + row['id']
        p_date = safe_date(row['date_sortie'])
        bon_no = row.get('num_bon_sortie')
        ptn_json = json.dumps([bon_no]) if bon_no else None
        inv_id = row.get('bill_id') if row.get('is_facture') and row.get('bill_id') in valid_client_invoices else None
        cli_id = row.get('provider_id') if row.get('provider_id') in valid_clients else None

        tot_w = safe_float(row.get('total_weight_sortie_guano_entier')) + \
                safe_float(row.get('total_weight_sortie_guano_hgt')) + \
                safe_float(row.get('total_weight_sortie_guano_hg')) + \
                safe_float(row.get('total_weight_sortie_guano_filet'))
        tot_p = safe_float(row.get('total_price_sortie_guano_entier')) + \
                safe_float(row.get('total_price_sortie_guano_hgt')) + \
                safe_float(row.get('total_price_sortie_guano_hg')) + \
                safe_float(row.get('total_price_sortie_guano_filet'))

        t_cur.execute(
            """INSERT INTO productions 
               (id, date, total_price, total_weight, guano_production_weight, 
                production_category_id, production_tracking_number, client_id, allow_invoice, client_invoice_id, 
                total_secondary_packaging_cost, created_at, updated_at)
               VALUES (%s, %s, %s, %s, %s, 3, %s, %s, 1, %s, 0.00, %s, %s)""",
            (pid, p_date, tot_p, tot_w, tot_w, ptn_json, cli_id, inv_id, row['created_at'], row['updated_at'])
        )

        outputs = [
            ('Guano Entier', 1, safe_float(row.get('total_weight_sortie_guano_entier')), safe_float(row.get('pu_entier')), safe_float(row.get('total_price_sortie_guano_entier'))),
            ('Guano HGT', 2, safe_float(row.get('total_weight_sortie_guano_hgt')), safe_float(row.get('pu_hgt')), safe_float(row.get('total_price_sortie_guano_hgt'))),
            ('Guano HG', 3, safe_float(row.get('total_weight_sortie_guano_hg')), safe_float(row.get('pu_hg')), safe_float(row.get('total_price_sortie_guano_hg'))),
            ('Guano Filet', 4, safe_float(row.get('total_weight_sortie_guano_filet')), safe_float(row.get('pu_filet')), safe_float(row.get('total_price_sortie_guano_filet'))),
        ]
        for name, gid, w, pu, p in outputs:
            if w > 0 or p > 0:
                detail_prod_batch.append((
                    pid, 2, name, 'App\\Models\\GuanoType', gid,
                    w, pu if pu > 0 else (p/w if w>0 else 0), p, 0.00, None, None, 992, 'stock_in', None,
                    row['created_at'], row['updated_at']
                ))

    tgt_conn.commit()

    log(f"Inserting {len(detail_prod_batch)} detail_productions rows...")
    chunk_size = 5000
    for i in range(0, len(detail_prod_batch), chunk_size):
        chunk = detail_prod_batch[i:i+chunk_size]
        t_cur.executemany(
            """INSERT INTO detail_productions 
               (production_id, detail_production_category_id, article_description, stockable_type, stockable_id,
                weight, unit_price, total_price, secondary_packaging_cost, package_type_id, nbr_box, warehouse_id, warehouse_operation,
                truck_id, created_at, updated_at)
               VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)""",
            chunk
        )

    tgt_conn.commit()
    log("Production and detail_productions migration completed.")

# -------------------------------------------------------------
# STAGE 6: Outbound Dispatches & High-Volume Glace/Tare (16,625 rows)
# -------------------------------------------------------------
def migrate_shippings_complete(src_conn, tgt_conn, cat_data, plate_to_id, valid_client_invoices):
    log("=== STAGE 6: Migrating Outbound Dispatches & Commercial Sales ===")
    s_cur = src_conn.cursor()
    t_cur = tgt_conn.cursor()

    fish_type_names = cat_data['fish_type_names']
    valid_clients = cat_data['valid_clients']
    valid_warehouses = cat_data['valid_warehouses']

    shipping_truck_batch = []
    detail_shipping_batch = []

    # 1. Master Fish Sales (ventes, 1,301 rows)
    s_cur.execute("SELECT * FROM ventes WHERE deleted_at IS NULL")
    for v in s_cur.fetchall():
        vid = v['id']
        v_date = safe_date(v['date_sortie'])
        tot_w = safe_float(v['total_weight_sortie'])
        tot_p = safe_float(v['total_price_sortie'])
        bon_no = v.get('num_bon_sortie')
        stn_json = json.dumps([bon_no]) if bon_no else None
        inv_id = v.get('bill_id') if v.get('is_facture') and v.get('bill_id') in valid_client_invoices else None
        cli_id = v.get('provider_id') if v.get('provider_id') in valid_clients else None
        is_guano = v.get('type') == 'guano'
        is_export = v.get('type_vente') == 'export'

        cat_id = 7 if is_guano else (2 if is_export else 1)
        det_cat_id = 3 if is_guano else 1

        t_cur.execute(
            """INSERT INTO shippings 
               (id, date, total_price, total_weight, shipping_category_id, shipping_tracking_number, 
                direction, client_id, allow_invoice, invoice_id, created_at, updated_at)
               VALUES (%s, %s, %s, %s, %s, %s, %s, %s, 1, %s, %s, %s)""",
            (vid, v_date, tot_p, tot_w, cat_id, stn_json, 'export' if is_export else 'local', cli_id, inv_id, v['created_at'], v['updated_at'])
        )

        mats = safe_json(v.get('matricules')) or [v.get('matricule')]
        for m in mats:
            cp = clean_plate(m)
            if cp and cp in plate_to_id:
                shipping_truck_batch.append((vid, plate_to_id[cp]))

        for it in safe_json(v.get('types_sortie')):
            t_id = it.get('type_id')
            w = safe_float(it.get('weight'))
            pu = safe_float(it.get('pu'))
            p = safe_float(it.get('price') or (w * pu))
            art_name = fish_type_names.get(t_id, f"Poisson #{t_id}" if not is_guano else "Farine Guano")
            raw_ch = it.get('chambre_id')
            ch_id = raw_ch if raw_ch in valid_warehouses else 13

            detail_shipping_batch.append((
                vid, det_cat_id, art_name, 'App\\Models\\GuanoType' if is_guano else 'App\\Models\\FishType', t_id if not is_guano else 1,
                w, pu, p, it.get('container_id'), it.get('nbr'), ch_id, 'stock_out', None,
                v['created_at'], v['updated_at']
            ))

    tgt_conn.commit()

    # 2. Third-Party Freezing Dispatches (congelation_clients, 1,603 rows)
    s_cur.execute("SELECT * FROM congelation_clients WHERE deleted_at IS NULL")
    for row in s_cur.fetchall():
        sid = 100000 + row['id']
        s_date = safe_date(row['date_reception'] or row['date_production'])
        tot_w = safe_float(row['total_weight_production'])
        tot_p = safe_float(row['total_price_production'])
        bon_no = row.get('num_bon_production') or row.get('num_bon_reception')
        stn_json = json.dumps([bon_no]) if bon_no else None
        inv_id = row.get('bill_id') if row.get('is_facture') and row.get('bill_id') in valid_client_invoices else None
        cli_id = row.get('provider_id') if row.get('provider_id') in valid_clients else None

        t_cur.execute(
            """INSERT INTO shippings 
               (id, date, total_price, total_weight, shipping_category_id, shipping_tracking_number, 
                direction, client_id, allow_invoice, invoice_id, created_at, updated_at)
               VALUES (%s, %s, %s, %s, 3, %s, 'local', %s, 1, %s, %s, %s)""",
            (sid, s_date, tot_p, tot_w, stn_json, cli_id, inv_id, row['created_at'], row['updated_at'])
        )

        for it in safe_json(row.get('types_production')):
            t_id = it.get('type_id')
            w = safe_float(it.get('total_weight_container') or (safe_float(it.get('nbr')) * 20.0))
            pu = safe_float(it.get('pu'))
            p = safe_float(it.get('price') or (w * pu))
            art_name = fish_type_names.get(t_id, f"Poisson Sous-Traitance #{t_id}")
            raw_ch = it.get('chambre_id')
            ch_id = raw_ch if raw_ch in valid_warehouses else 15

            detail_shipping_batch.append((
                sid, 1, art_name, 'App\\Models\\FishType', t_id,
                w, pu, p, it.get('container_id'), it.get('nbr'), ch_id, 'stock_out', None,
                row['created_at'], row['updated_at']
            ))

    tgt_conn.commit()

    # 3. Packaging Sales (emballage_clients, 9 rows)
    s_cur.execute("SELECT * FROM emballage_clients WHERE deleted_at IS NULL")
    for row in s_cur.fetchall():
        sid = 200000 + row['id']
        s_date = safe_date(row['date_sortie'])
        tot_w = safe_float(row['total_weight_sortie'])
        tot_p = safe_float(row['total_price_sortie'])
        bon_no = row.get('num_bon_sortie')
        stn_json = json.dumps([bon_no]) if bon_no else None
        inv_id = row.get('bill_id') if row.get('is_facture') and row.get('bill_id') in valid_client_invoices else None
        cli_id = row.get('provider_id') if row.get('provider_id') in valid_clients else None

        t_cur.execute(
            """INSERT INTO shippings 
               (id, date, total_price, total_weight, shipping_category_id, shipping_tracking_number, 
                direction, client_id, allow_invoice, invoice_id, created_at, updated_at)
               VALUES (%s, %s, %s, %s, 4, %s, 'local', %s, 1, %s, %s, %s)""",
            (sid, s_date, tot_p, tot_w, stn_json, cli_id, inv_id, row['created_at'], row['updated_at'])
        )

        detail_shipping_batch.append((
            sid, 1, row.get('description') or 'Emballage Vendu', 'App\\Models\\PackageType', 1,
            tot_w, safe_float(row.get('pu')), tot_p, None, None, 993, 'stock_out', None,
            row['created_at'], row['updated_at']
        ))

    tgt_conn.commit()

    # Batch Insert base detail_shippings
    chunk_size = 5000
    for i in range(0, len(detail_shipping_batch), chunk_size):
        chunk = detail_shipping_batch[i:i+chunk_size]
        t_cur.executemany(
            """INSERT INTO detail_shippings 
               (shipping_id, detail_shipping_category_id, article_description, stockable_type, stockable_id,
                weight, unit_price, total_price, package_type_id, nbr_box, warehouse_id, warehouse_operation,
                truck_id, created_at, updated_at)
               VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)""",
            chunk
        )

    # 4. High-Volume Glace & Tare Pesage (glace_clients, 16,625 rows chunked)
    log("Processing 16,625 glace_clients records in chunks...")
    s_cur.execute("SELECT * FROM glace_clients WHERE deleted_at IS NULL ORDER BY id ASC")
    gc_base_id = 300000
    chunk_rows = s_cur.fetchmany(chunk_size)

    while chunk_rows:
        gc_ship_batch = []
        gc_det_batch = []
        gc_truck_batch = []

        for r in chunk_rows:
            sid = gc_base_id + r['id']
            s_date = safe_date(r['date_reception'])
            bon_no = r.get('num_bon_reception')
            stn_json = json.dumps([str(bon_no)]) if bon_no else None
            pu = safe_float(r['pu'])
            w = safe_float(r['total_weight_reception'])
            tot_p = safe_float(r['total_price_reception'])
            cli_id = r.get('provider_id') if r.get('provider_id') in valid_clients else None
            inv_id = r.get('bill_id') if r.get('is_facture') and r.get('bill_id') in valid_client_invoices else None
            is_tare = (r.get('type_bon') == 'Tare')

            if is_tare:
                cat_id = 6
                art_name = "Prestation Pesage / Tare"
                item_w = 0.00
            else:
                cat_id = 5
                art_name = "Vente Glace"
                item_w = w

            gc_ship_batch.append((
                sid, s_date, tot_p, item_w, cat_id, stn_json, 'local',
                cli_id, 1, inv_id, r['created_at'], r['updated_at']
            ))

            gc_det_batch.append((
                sid, 1, art_name, None, None,
                item_w, pu, tot_p, None, None, None, 'neutre', None,
                r['created_at'], r['updated_at']
            ))

            mats = safe_json(r.get('matricules')) or [r.get('matricule')]
            for m in mats:
                cp = clean_plate(m)
                if cp and cp in plate_to_id:
                    gc_truck_batch.append((sid, plate_to_id[cp]))

        t_cur.executemany(
            """INSERT INTO shippings 
               (id, date, total_price, total_weight, shipping_category_id, shipping_tracking_number, 
                direction, client_id, allow_invoice, invoice_id, created_at, updated_at)
               VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)""",
            gc_ship_batch
        )

        t_cur.executemany(
            """INSERT INTO detail_shippings 
               (shipping_id, detail_shipping_category_id, article_description, stockable_type, stockable_id,
                weight, unit_price, total_price, package_type_id, nbr_box, warehouse_id, warehouse_operation,
                truck_id, created_at, updated_at)
               VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)""",
            gc_det_batch
        )

        if gc_truck_batch:
            t_cur.executemany("INSERT IGNORE INTO shipping_truck (shipping_id, truck_id) VALUES (%s, %s)", gc_truck_batch)

        tgt_conn.commit()
        chunk_rows = s_cur.fetchmany(chunk_size)

    if shipping_truck_batch:
        for i in range(0, len(shipping_truck_batch), chunk_size):
            t_cur.executemany("INSERT IGNORE INTO shipping_truck (shipping_id, truck_id) VALUES (%s, %s)", shipping_truck_batch[i:i+chunk_size])

    tgt_conn.commit()
    log("Dispatches and Shippings migration completed.")

# -------------------------------------------------------------
# STAGE 7: Inter-Chamber Stock Transfers (stock_movement_logs)
# -------------------------------------------------------------
def migrate_stock_transfers(src_conn, tgt_conn, cat_data, plate_to_id):
    log("=== STAGE 7: Migrating Inter-Chamber Stock Transfers ===")
    s_cur = src_conn.cursor()
    t_cur = tgt_conn.cursor()

    valid_warehouses = cat_data['valid_warehouses']
    s_cur.execute("SELECT * FROM stock_movement_logs ORDER BY id ASC")
    logs = s_cur.fetchall()

    for row in logs:
        tid = row['id']
        t_date = safe_date(row['date'])
        tot_w = safe_float(row['total_weight'])
        bon_no = row.get('num_bon_transfers')
        stn_json = json.dumps([str(bon_no)]) if bon_no else None

        t_cur.execute(
            """INSERT INTO warehouse_transfers (id, date, total_weight, transfer_tracking_number, created_at, updated_at)
               VALUES (%s, %s, %s, %s, %s, %s)""",
            (tid, t_date, tot_w, stn_json, row['created_at'], row['updated_at'])
        )

        mats = safe_json(row.get('matricules')) or [row.get('matricule')]
        for m in mats:
            cp = clean_plate(m)
            if cp and cp in plate_to_id:
                t_cur.execute(
                    "INSERT IGNORE INTO warehouse_transfer_truck (warehouse_transfer_id, truck_id) VALUES (%s, %s)",
                    (tid, plate_to_id[cp])
                )

        src_wh = row.get('chambre_source_id') if row.get('chambre_source_id') in valid_warehouses else None
        dest_wh = row.get('chambre_destination_id') if row.get('chambre_destination_id') in valid_warehouses else None

        for it in safe_json(row.get('types_ss')):
            t_cur.execute(
                """INSERT INTO detail_warehouse_transfers 
                   (warehouse_transfer_id, stockable_type, stockable_id, weight, source_warehouse_id, 
                    source_tracking_number, destination_warehouse_id, package_type_id, created_at, updated_at)
                   VALUES (%s, 'App\\Models\\FishType', %s, %s, %s, %s, %s, %s, %s, %s)""",
                (tid, it.get('type_id'), safe_float(it.get('weight')), src_wh, it.get('num_bon_reception'), dest_wh, it.get('container_id'), row['created_at'], row['updated_at'])
            )

    tgt_conn.commit()
    log(f"Migrated {len(logs)} stock transfers.")

# -------------------------------------------------------------
# STAGE 8: Fiscal Year Closings & Invoice Financial Reconciliation
# -------------------------------------------------------------
def reconcile_financials_and_closings(src_conn, tgt_conn, cat_data):
    log("=== STAGE 8: Reconciling Financials & Migrating Fiscal Year Closings ===")
    s_cur = src_conn.cursor()
    t_cur = tgt_conn.cursor()

    valid_clients = cat_data['valid_clients']

    # 1. Update client_invoices totals and generate client_payments
    t_cur.execute(
        """SELECT s.invoice_id, SUM(s.total_price) as sum_price, SUM(s.total_weight) as sum_weight
           FROM shippings s WHERE s.invoice_id IS NOT NULL AND s.deleted_at IS NULL GROUP BY s.invoice_id"""
    )
    for r in t_cur.fetchall():
        inv_id = r['invoice_id']
        sp = safe_float(r['sum_price'])
        sw = safe_float(r['sum_weight'])
        t_cur.execute(
            "UPDATE client_invoices SET total_price = %s, total_weight = %s WHERE id = %s",
            (sp, sw, inv_id)
        )

    t_cur.execute(
        """SELECT id, date, client_id, total_price, tracking_numbers, created_at, updated_at
           FROM client_invoices WHERE deleted_at IS NULL AND client_id IS NOT NULL AND total_price > 0"""
    )
    for ci in t_cur.fetchall():
        tr_list = safe_json(ci['tracking_numbers'])
        ref = tr_list[0] if (tr_list and len(tr_list) > 0) else str(ci['id'])
        t_cur.execute(
            """INSERT INTO client_payments 
               (date, client_invoice_id, client_id, payment_type, amount, reference, notes, created_at, updated_at)
               VALUES (%s, %s, %s, 'bank_transfer', %s, %s, 'Règlement facture client (migration)', %s, %s)""",
            (ci['date'], ci['id'], ci['client_id'], ci['total_price'], ref, ci['created_at'], ci['updated_at'])
        )

    # 2. Client Management Closings (2022, 2023)
    s_cur.execute("""
        SELECT YEAR(date) as yr, SUM(debits) as tot_inv, SUM(credits) as tot_pay, SUM(debiteurs) as tot_cf
        FROM client_management WHERE deleted_at IS NULL GROUP BY YEAR(date)
    """)
    for cy in s_cur.fetchall():
        yr = cy['yr']
        t_cur.execute(
            """INSERT INTO client_fiscal_year_closings (year, closing_date, total_invoices, total_payments, carried_forward, is_closed, created_at, updated_at)
               VALUES (%s, %s, %s, %s, %s, 1, NOW(), NOW())
               ON DUPLICATE KEY UPDATE total_invoices=VALUES(total_invoices)""",
            (yr, f"{yr}-12-31", safe_float(cy['tot_inv']), safe_float(cy['tot_pay']), safe_float(cy['tot_cf']))
        )
        closing_id = t_cur.lastrowid or yr

        s_cur.execute("SELECT * FROM client_management WHERE YEAR(date) = %s AND deleted_at IS NULL", (yr,))
        for cm in s_cur.fetchall():
            c_id = cm.get('provider_id')
            if c_id in valid_clients:
                t_cur.execute(
                    """INSERT INTO client_fiscal_year_closing_lines 
                       (client_fiscal_year_closing_id, client_id, total_invoices, total_payments, carry_forward, balance, created_at, updated_at)
                       VALUES (%s, %s, %s, %s, %s, %s, NOW(), NOW())
                       ON DUPLICATE KEY UPDATE balance=VALUES(balance)""",
                    (closing_id, c_id, safe_float(cm['debits']), safe_float(cm['credits']), safe_float(cm['debiteurs']), safe_float(cm['debiteurs']))
                )

    tgt_conn.commit()
    log("Financials and Fiscal Year Closings migrated successfully.")

# -------------------------------------------------------------
# STAGE 9: FIFO Inventory Balances Calculation Engine
# -------------------------------------------------------------
def calculate_and_build_warehouse_stock(tgt_conn):
    log("=== STAGE 9: Running FIFO Stock Balance Engine ===")
    t_cur = tgt_conn.cursor()

    # 1. Ingest production stock_in balances
    t_cur.execute(
        """SELECT dp.warehouse_id, dp.stockable_type, dp.stockable_id,
                  dp.weight, dp.unit_price, dp.total_price, dp.nbr_box, dp.package_type_id,
                  p.id as prod_id, p.date, p.production_tracking_number
           FROM detail_productions dp
           JOIN productions p ON dp.production_id = p.id
           WHERE dp.warehouse_operation = 'stock_in' AND dp.warehouse_id IS NOT NULL AND dp.weight > 0
           ORDER BY p.date ASC, dp.id ASC"""
    )
    for row in t_cur.fetchall():
        rtn_str = clean_tracking_str(row['production_tracking_number'])
        t_cur.execute(
            """INSERT INTO warehouse_balances 
               (warehouse_id, stockable_type, stockable_id, reception_tracking_number,
                warehouse_operation, date, total_weight, remaining_weight, total_price, unit_price,
                package_count, package_type_id, source_type, source_id, is_finished, created_at, updated_at)
               VALUES (%s, %s, %s, %s, 'stock_in', %s, %s, %s, %s, %s, %s, %s, 'production', %s, 0, NOW(), NOW())""",
            (row['warehouse_id'], row['stockable_type'], row['stockable_id'], rtn_str,
             row['date'], row['weight'], row['weight'], row['total_price'], row['unit_price'],
             row['nbr_box'] or 0.0, row['package_type_id'], row['prod_id'])
        )

    # 2. Ingest purchase stock_in balances
    t_cur.execute(
        """SELECT dc.warehouse_id, dc.stockable_type, dc.stockable_id,
                  dc.weight, dc.unit_price, dc.total_price, dc.nbr_box, dc.package_type_id,
                  c.id as charge_id, c.date, c.reception_tracking_number
           FROM detail_charges dc
           JOIN charges c ON dc.charge_id = c.id
           WHERE dc.warehouse_operation = 'stock_in' AND dc.warehouse_id IS NOT NULL AND dc.weight > 0
           ORDER BY c.date ASC, dc.id ASC"""
    )
    for row in t_cur.fetchall():
        rtn_str = clean_tracking_str(row['reception_tracking_number'])
        t_cur.execute(
            """INSERT INTO warehouse_balances 
               (warehouse_id, stockable_type, stockable_id, reception_tracking_number,
                warehouse_operation, date, total_weight, remaining_weight, total_price, unit_price,
                package_count, package_type_id, source_type, source_id, is_finished, created_at, updated_at)
               VALUES (%s, %s, %s, %s, 'stock_in', %s, %s, %s, %s, %s, %s, %s, 'charge', %s, 0, NOW(), NOW())""",
            (row['warehouse_id'], row['stockable_type'], row['stockable_id'], rtn_str,
             row['date'], row['weight'], row['weight'], row['total_price'], row['unit_price'],
             row['nbr_box'] or 0.0, row['package_type_id'], row['charge_id'])
        )

    tgt_conn.commit()

    # 3. Deduct outbound exits (FIFO)
    t_cur.execute(
        """SELECT ds.warehouse_id, ds.stockable_type, ds.stockable_id,
                  ds.weight, ds.unit_price, ds.total_price, ds.nbr_box,
                  s.id as ship_id, s.date
           FROM detail_shippings ds
           JOIN shippings s ON ds.shipping_id = s.id
           WHERE ds.warehouse_operation = 'stock_out' AND ds.warehouse_id IS NOT NULL AND ds.weight > 0
           ORDER BY s.date ASC, ds.id ASC"""
    )
    exits = t_cur.fetchall()
    log(f"Processing FIFO deductions for {len(exits)} outbound warehouse dispatches...")

    t_cur.execute(
        """SELECT id, warehouse_id, stockable_type, stockable_id, date, remaining_weight, unit_price 
           FROM warehouse_balances WHERE warehouse_operation = 'stock_in' ORDER BY date ASC, id ASC"""
    )
    all_bals = t_cur.fetchall()
    bal_map = {}
    for b in all_bals:
        k = (b['warehouse_id'], b['stockable_id'])
        if k not in bal_map:
            bal_map[k] = []
        bal_map[k].append([b['id'], b['date'], float(b['remaining_weight']), float(b['unit_price']), b['stockable_type']])

    ops_batch = []
    bal_updates = {}

    for ex in exits:
        wh_id = ex['warehouse_id']
        st_id = ex['stockable_id']
        st_type = ex['stockable_type'] or 'App\\Models\\FishType'
        needed = float(ex['weight'])
        k = (wh_id, st_id)

        candidates = bal_map.get(k, [])
        if not candidates:
            t_cur.execute(
                """INSERT INTO warehouse_balances 
                   (warehouse_id, stockable_type, stockable_id, reception_tracking_number,
                    warehouse_operation, date, total_weight, remaining_weight, total_price, unit_price,
                    package_count, source_type, source_id, is_finished, created_at, updated_at)
                   VALUES (%s, %s, %s, 'INITIAL-STOCK', 'stock_in', '2021-01-01', %s, %s, %s, %s, 0, 'initial', 1, 0, NOW(), NOW())""",
                (wh_id, st_type, st_id, needed, needed, needed * float(ex['unit_price']), float(ex['unit_price']))
            )
            new_bal_id = t_cur.lastrowid
            candidates = [[new_bal_id, '2021-01-01', needed, float(ex['unit_price']), st_type]]
            bal_map[k] = candidates

        for cand in candidates:
            if needed <= 0.001:
                break
            b_id, b_date, b_rem, b_pu, b_type = cand
            if b_rem <= 0.001:
                continue

            deduct = min(b_rem, needed)
            cand[2] -= deduct
            needed -= deduct
            bal_updates[b_id] = cand[2]

            ops_batch.append((
                b_id, ex['date'], deduct, float(ex['unit_price']), deduct * float(ex['unit_price']),
                float(ex['nbr_box'] or 0.0), 'shipping', ex['ship_id'], datetime.now(), datetime.now()
            ))

    if ops_batch:
        t_cur.executemany(
            """INSERT INTO warehouse_balance_operations 
               (warehouse_balance_id, date, weight, unit_price, total_price, package_count,
                source_type, source_id, created_at, updated_at)
               VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s)""",
            ops_batch
        )

    for bid, rem in bal_updates.items():
        rem_clamped = max(0.0, round(rem, 2))
        is_fin = 1 if rem_clamped <= 0.01 else 0
        t_cur.execute(
            "UPDATE warehouse_balances SET remaining_weight = %s, is_finished = %s WHERE id = %s",
            (rem_clamped, is_fin, bid)
        )

    # Recalculate live total_weight on all warehouses
    t_cur.execute(
        """UPDATE warehouses w
           SET total_weight = COALESCE((
               SELECT ROUND(SUM(remaining_weight), 2)
               FROM warehouse_balances wb
               WHERE wb.warehouse_id = w.id AND wb.is_finished = 0 AND wb.remaining_weight > 0 AND wb.warehouse_operation = 'stock_in'
           ), 0.00)"""
    )
    tgt_conn.commit()
    log("Stock balances and live warehouse room totals recalculated successfully.")

# -------------------------------------------------------------
# MAIN RUNNER
# -------------------------------------------------------------
def main():
    start_time = time.time()
    log("Starting Villamar -> Jupiter Migration Pipeline...")
    src_conn, tgt_conn = get_db_conns()

    try:
        tgt_conn.cursor().execute("SET FOREIGN_KEY_CHECKS = 0;")
        tgt_conn.commit()

        clear_target_database(tgt_conn)
        cat_data = migrate_master_catalogs(src_conn, tgt_conn)
        valid_client_invoices = migrate_invoices_base(src_conn, tgt_conn, cat_data)
        plate_to_id = get_truck_plate_mapping(src_conn, tgt_conn)

        migrate_charges(src_conn, tgt_conn, cat_data, plate_to_id)
        migrate_production(src_conn, tgt_conn, cat_data, valid_client_invoices)
        migrate_shippings_complete(src_conn, tgt_conn, cat_data, plate_to_id, valid_client_invoices)
        migrate_stock_transfers(src_conn, tgt_conn, cat_data, plate_to_id)
        reconcile_financials_and_closings(src_conn, tgt_conn, cat_data)
        calculate_and_build_warehouse_stock(tgt_conn)

        tgt_conn.cursor().execute("SET FOREIGN_KEY_CHECKS = 1;")
        tgt_conn.commit()

        elapsed = round(time.time() - start_time, 2)
        log(f"=== MIGRATION COMPLETED SUCCESSFULLY in {elapsed}s! ===")

    except Exception as e:
        tgt_conn.cursor().execute("SET FOREIGN_KEY_CHECKS = 1;")
        tgt_conn.commit()
        log(f"FATAL ERROR during migration: {e}")
        import traceback
        traceback.print_exc()
        sys.exit(1)
    finally:
        src_conn.close()
        tgt_conn.close()

if __name__ == '__main__':
    main()
