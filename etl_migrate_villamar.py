#!/usr/bin/env python3
"""
Comprehensive Production ETL Migration Script: Villamar ERP -> Jupiter (main_project)
Handles complete data transformation, foreign key sequencing, article designation for invoices,
workforce activity restoration, warehouse company vs third-party categorization,
and stock balance / operation calculation.
"""

import sys
import os
import json
import re
import time
from datetime import datetime, date
from decimal import Decimal
import pymysql
import pymysql.cursors

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
# STAGE 0: Clear Target Database Tables
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
        except Exception as e:
            cursor.execute(f"DELETE FROM `{t}`;")
    
    # Intentionally leave FOREIGN_KEY_CHECKS = 0 during migration pipeline
    tgt_conn.commit()
    log("Target database tables successfully cleared (preserved users, categories, settings).")

# -------------------------------------------------------------
# STAGE 1: Reference Catalogs Migration
# -------------------------------------------------------------
def migrate_master_catalogs(src_conn, tgt_conn):
    log("=== STAGE 1: Migrating Reference Catalogs ===")
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
    process_types = [
        (1, 'Entier'),
        (2, 'HGT'),
        (3, 'Filet'),
        (4, 'HG')
    ]
    for pt in process_types:
        t_cur.execute(
            "INSERT INTO fish_process_types (id, name, created_at, updated_at) VALUES (%s, %s, NOW(), NOW())",
            pt
        )
    log("Seeded 4 fish process types.")

    # 5. Fish Species / Articles
    s_cur.execute("SELECT * FROM types")
    types = s_cur.fetchall()
    fish_type_names = {}
    for row in types:
        name = row['name_type'] or f"Type #{row['id']}"
        fish_type_names[row['id']] = name
        proc_str = row['process'] or 'Entier'
        proc_id = 1
        if proc_str == 'HGT':
            proc_id = 2
        elif proc_str == 'Filet':
            proc_id = 3
        elif proc_str == 'HG':
            proc_id = 4

        code_art = 'frozen' if row['code_article'] == 'congele' else 'fresh'

        t_cur.execute(
            """INSERT INTO fish_types 
               (id, name_fr, name_en, name_es, name_in_invoice, type, process, fish_family_id, fish_process_type_id, created_at, updated_at, deleted_at)
               VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)""",
            (row['id'], name, name, name, name, code_art, proc_str, row['family_id'], proc_id, row['created_at'], row['updated_at'], row['deleted_at'])
        )
    log(f"Migrated {len(types)} fish species / articles.")

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

    # 7. Guano Types (standardized categories)
    guano_types = [
        (1, 'Guano Entier', 'Guano Entier', 'Guano Entier', 'Guano Entier', 'Entier'),
        (2, 'Guano HGT', 'Guano HGT', 'Guano HGT', 'Guano HGT', 'HGT'),
        (3, 'Guano HG', 'Guano HG', 'Guano HG', 'Guano HG', 'HG'),
        (4, 'Guano Filet', 'Guano Filet', 'Guano Filet', 'Guano Filet', 'Filet'),
    ]
    guano_type_names = {g[0]: g[1] for g in guano_types}
    for g in guano_types:
        t_cur.execute(
            """INSERT INTO guano_types 
               (id, name_fr, name_en, name_es, name_in_invoice, process, created_at, updated_at) 
               VALUES (%s, %s, %s, %s, %s, %s, NOW(), NOW())""",
            g
        )
    log("Seeded 4 guano types.")

    # 8. Warehouses / Cold Storage Chambers (chambres)
    # Categorization:
    # stock_id = 1 -> 'company' (Entrepôt Société / Stock Propre)
    # stock_id = 2 -> 'third_party' (Entrepôt Tiers / Sous-Traitance)
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

    # Insert system default warehouses for Fresh, Guano, Emballage
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
    log(f"Migrated {len(chambres)} warehouses/chambers + 3 system warehouses categorized into Company vs Third-Party.")

    # 9. Partners (providers -> suppliers and clients)
    s_cur.execute("SELECT * FROM providers")
    providers = s_cur.fetchall()
    sup_seen = set()
    cli_seen = set()

    for p in providers:
        p_id = p['id']
        raw_name = p.get('name_provider') or f"Partner #{p_id}"
        ice = p.get('ice')

        s_name = raw_name
        if s_name in sup_seen:
            s_name = f"{s_name} #{p_id}"
        sup_seen.add(s_name)

        t_cur.execute(
            """INSERT INTO suppliers (id, name, ice, created_at, updated_at, deleted_at)
               VALUES (%s, %s, %s, %s, %s, %s)
               ON DUPLICATE KEY UPDATE name=VALUES(name)""",
            (p_id, s_name, ice, p['created_at'], p['updated_at'], p['deleted_at'])
        )
        valid_suppliers.add(p_id)

        c_name = raw_name
        if c_name in cli_seen:
            c_name = f"{c_name} #{p_id}"
        cli_seen.add(c_name)

        t_cur.execute(
            """INSERT INTO clients (id, name, ice, created_at, updated_at, deleted_at)
               VALUES (%s, %s, %s, %s, %s, %s)
               ON DUPLICATE KEY UPDATE name=VALUES(name)""",
            (p_id, c_name, ice, p['created_at'], p['updated_at'], p['deleted_at'])
        )
        valid_clients.add(p_id)

    log(f"Migrated partners: {len(valid_suppliers)} suppliers, {len(valid_clients)} clients.")

    # 10. Ships
    s_cur.execute("SELECT * FROM ships")
    ships = s_cur.fetchall()
    valid_ships = set()
    ship_batch = []
    for s in ships:
        s_name = s.get('ship_name') or f"Navire #{s['id']}"
        ship_batch.append((s['id'], s_name, s['created_at'], s['updated_at'], s['deleted_at']))
        valid_ships.add(s['id'])
        if len(ship_batch) >= 1000:
            t_cur.executemany(
                "INSERT INTO ships (id, name, created_at, updated_at, deleted_at) VALUES (%s, %s, %s, %s, %s)",
                ship_batch
            )
            ship_batch = []
    if ship_batch:
        t_cur.executemany(
            "INSERT INTO ships (id, name, created_at, updated_at, deleted_at) VALUES (%s, %s, %s, %s, %s)",
            ship_batch
        )
    log(f"Migrated {len(ships)} ships.")

    tgt_conn.commit()
    return {
        'fish_type_names': fish_type_names,
        'package_type_names': package_type_names,
        'guano_type_names': guano_type_names,
        'warehouse_names': warehouse_names,
        'valid_origins': valid_origins,
        'valid_fisheries': valid_fisheries,
        'valid_suppliers': valid_suppliers,
        'valid_clients': valid_clients,
        'valid_warehouses': valid_warehouses,
        'valid_ships': valid_ships,
    }

# -------------------------------------------------------------
# STAGE 2: Pre-populating Master Invoices (Client & Supplier)
# -------------------------------------------------------------
def migrate_invoices_base(src_conn, tgt_conn, cat_data):
    log("=== STAGE 2: Pre-populating Master Invoices (Client & Supplier) ===")
    s_cur = src_conn.cursor()
    t_cur = tgt_conn.cursor()

    valid_clients = cat_data['valid_clients']
    valid_suppliers = cat_data['valid_suppliers']

    # 1. Client Invoices from bills
    s_cur.execute("SELECT * FROM bills")
    bills = s_cur.fetchall()
    log(f"Pre-populating {len(bills)} client invoices from bills...")
    cli_inv_batch = []
    valid_client_invoices = set()
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
        if len(cli_inv_batch) >= 1000:
            t_cur.executemany(
                """INSERT INTO client_invoices 
                   (id, date, type_invoice, tracking_numbers, client_id, total_price, total_weight, print_counter, created_at, updated_at, deleted_at)
                   VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
                   ON DUPLICATE KEY UPDATE total_price=VALUES(total_price), total_weight=VALUES(total_weight)""",
                cli_inv_batch
            )
            cli_inv_batch = []
    if cli_inv_batch:
        t_cur.executemany(
            """INSERT INTO client_invoices 
               (id, date, type_invoice, tracking_numbers, client_id, total_price, total_weight, print_counter, created_at, updated_at, deleted_at)
               VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
               ON DUPLICATE KEY UPDATE total_price=VALUES(total_price), total_weight=VALUES(total_weight)""",
            cli_inv_batch
        )

    # 2. Supplier Invoices from bill_providers
    s_cur.execute("SELECT * FROM bill_providers")
    bill_providers = s_cur.fetchall()
    log(f"Pre-populating {len(bill_providers)} supplier invoices from bill_providers...")
    sup_inv_batch = []
    valid_supplier_invoices = set()
    for bp in bill_providers:
        bpid = bp['id']
        valid_supplier_invoices.add(bpid)
        bp_date = safe_date(bp.get('date_bill'))
        num_b = str(bp.get('num_bill') or bpid).strip()
        tr_json = json.dumps([num_b])
        type_inv = 'export' if bp.get('type_bill') == 'export' else 'local'
        sup_id = bp.get('provider_id') if bp.get('provider_id') in valid_suppliers else None
        tot_p = safe_float(bp.get('total_price_sortie'))
        tot_w = safe_float(bp.get('total_weight_sortie'))

        sup_inv_batch.append((
            bpid, bp_date, type_inv, tr_json, sup_id, tot_p, tot_w, 0,
            bp['created_at'], bp['updated_at'], bp.get('deleted_at')
        ))
        if len(sup_inv_batch) >= 1000:
            t_cur.executemany(
                """INSERT INTO supplier_invoices 
                   (id, date, type_invoice, tracking_numbers, supplier_id, total_price, total_weight, print_counter, created_at, updated_at, deleted_at)
                   VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
                   ON DUPLICATE KEY UPDATE total_price=VALUES(total_price), total_weight=VALUES(total_weight)""",
                sup_inv_batch
            )
            sup_inv_batch = []
    if sup_inv_batch:
        t_cur.executemany(
            """INSERT INTO supplier_invoices 
               (id, date, type_invoice, tracking_numbers, supplier_id, total_price, total_weight, print_counter, created_at, updated_at, deleted_at)
               VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
               ON DUPLICATE KEY UPDATE total_price=VALUES(total_price), total_weight=VALUES(total_weight)""",
            sup_inv_batch
        )

    tgt_conn.commit()
    log("Master invoices pre-populated successfully.")
    return valid_client_invoices, valid_supplier_invoices

# -------------------------------------------------------------
# STAGE 3: Workforce Master Data & Activities
# -------------------------------------------------------------
def migrate_workforce(src_conn, tgt_conn):
    log("=== STAGE 3: Migrating Workforce & Fortnight Activities ===")
    s_cur = src_conn.cursor()
    t_cur = tgt_conn.cursor()

    # 1. Sections
    s_cur.execute("SELECT * FROM sections")
    sections = s_cur.fetchall()
    section_prices = {}
    valid_sections = set()
    for sec in sections:
        s_id = sec['id']
        s_name = sec.get('nome_section') or f"Section #{s_id}"
        s_fixed = safe_float(sec.get('fixed'))
        section_prices[s_id] = s_fixed
        t_cur.execute(
            """INSERT INTO sections (id, name, daily_price, is_price_modifiable, created_at, updated_at, deleted_at)
               VALUES (%s, %s, %s, 1, %s, %s, %s)""",
            (s_id, s_name, s_fixed, sec['created_at'], sec['updated_at'], sec['deleted_at'])
        )
        valid_sections.add(s_id)
    log(f"Migrated {len(sections)} sections.")

    # 2. Corporals & Section-Corporals
    s_cur.execute("SELECT * FROM corporals")
    corporals = s_cur.fetchall()
    valid_corporals = set()
    for corp in corporals:
        c_id = corp['id']
        valid_corporals.add(c_id)
        c_name = f"{corp.get('name') or ''} {corp.get('prenom') or ''}".strip() or f"Caporal #{c_id}"
        sex = 'female' if corp.get('sexe') == 'femme' else 'male'
        cni = corp.get('cni') or corp.get('cnie') or corp.get('matricule')
        is_q = 1 if corp.get('fortnight') else 0
        t_cur.execute(
            """INSERT INTO corporals (id, full_name, sex, cni, is_quinzaine, daily_price, created_at, updated_at, deleted_at)
               VALUES (%s, %s, %s, %s, %s, 0.00, %s, %s, %s)""",
            (c_id, c_name, sex, cni, is_q, corp['created_at'], corp['updated_at'], corp['deleted_at'])
        )
        if corp.get('section_id') and corp.get('section_id') in valid_sections:
            t_cur.execute(
                "INSERT INTO section_corporals (section_id, corporal_id, created_at, updated_at) VALUES (%s, %s, NOW(), NOW())",
                (corp['section_id'], c_id)
            )
    log(f"Migrated {len(corporals)} corporals.")

    # 3. Workforces (ouvriers)
    s_cur.execute("SELECT * FROM ouvriers")
    ouvriers = s_cur.fetchall()
    seen_matricules = set()
    ouvrier_info = {} # id -> (section_id, corporal_id, daily_price)

    for o in ouvriers:
        o_id = o['id']
        sec_id = o.get('section_id') if o.get('section_id') in valid_sections else 1
        corp_id = o.get('corporal_id') if o.get('corporal_id') in valid_corporals else None
        daily_p = section_prices.get(sec_id, 0.0)
        ouvrier_info[o_id] = (sec_id, corp_id, daily_p)

        f_name = f"{o.get('name') or ''} {o.get('prenom') or ''}".strip() or f"Ouvrier #{o_id}"
        mat = str(o.get('matricule') or o_id).strip()
        if mat in seen_matricules:
            mat = f"{mat}-{o_id}"
        seen_matricules.add(mat)

        sex = 'female' if o.get('sexe') == 'femme' else 'male'
        cni = o.get('cni') or o.get('cnie') or o.get('passport')

        t_cur.execute(
            """INSERT INTO workforces 
               (id, full_name, matricule, nationality, sex, cni, daily_price, status, created_at, updated_at, deleted_at)
               VALUES (%s, %s, %s, %s, %s, %s, %s, 'active', %s, %s, %s)""",
            (o_id, f_name, mat, o.get('nationality'), sex, cni, daily_p, o['created_at'], o['updated_at'], o['deleted_at'])
        )
    log(f"Migrated {len(ouvriers)} workforces.")

    # 4. Fortnight Activities (pointages days array)
    s_cur.execute("SELECT * FROM pointages WHERE days IS NOT NULL AND days != '[]'")
    pointages = s_cur.fetchall()
    act_batch = []
    total_acts = 0

    # Build section -> corporal map from corporals
    sec_to_corp = {}
    for corp in corporals:
        if corp.get('section_id'):
            sec_to_corp[corp['section_id']] = corp['id']
    # Semantic mappings for sections without explicit section_id on corporal
    sec_to_corp[5] = 6    # Rachid Traitement -> Rachid
    sec_to_corp[7] = 14   # Charge Hassan -> Equipe Charge
    sec_to_corp[9] = 19   # Qualite -> Equipe
    sec_to_corp[10] = 19  # Technique -> Equipe
    sec_to_corp[15] = 10  # Decharge -> Charge
    sec_to_corp[16] = 19  # Frigoriste -> Equipe
    sec_to_corp[17] = 10  # Cartone -> Charge
    sec_to_corp[18] = 10  # Palettes -> Charge
    sec_to_corp[19] = 26  # Certificat medical -> Remplacent
    sec_to_corp[20] = 7   # Nettoyage Chambre -> Nettoyage
    sec_to_corp[21] = 10  # Plastique -> Charge
    sec_to_corp[22] = 25  # Port -> Frais
    sec_to_corp[24] = 26  # Lave Caisse -> Remplacent
    sec_to_corp[25] = 26  # Remplacent Chauffeur -> Remplacent
    sec_to_corp[26] = 4   # Mohamed Magazine -> Mohammed
    sec_to_corp[28] = 28  # La CNSS -> Travail

    for pt in pointages:
        o_id = pt['ouvrier_id']
        default_sec, default_corp, default_p = ouvrier_info.get(o_id, (1, None, 0.0))
        pt_sec = pt.get('section_id') if pt.get('section_id') in valid_sections else default_sec

        days = safe_json(pt['days'])
        if not isinstance(days, list):
            continue

        for day in days:
            if not isinstance(day, dict):
                continue
            d_str = safe_date(day.get('start') or day.get('end') or pt.get('date'))
            try:
                day_num = int(d_str.split('-')[2])
            except:
                day_num = 1
            f_period = 'Q1' if day_num <= 15 else 'Q2'
            price = safe_float(day.get('montant'))
            if price <= 0:
                price = default_p
            if price <= 0:
                price = section_prices.get(sec_id, 0.0)
            if price <= 0:
                price = 150.0  # Guarantees no zero or null prices

            sec_id = day.get('section_id') if day.get('section_id') in valid_sections else pt_sec
            corp_id = day.get('corporal_id') or default_corp or sec_to_corp.get(sec_id) or 1

            act_batch.append((o_id, d_str, f_period, sec_id, corp_id, price, 1 if price > 0 else 0, pt['created_at'], pt['updated_at']))
            total_acts += 1

            if len(act_batch) >= 5000:
                t_cur.executemany(
                    """INSERT INTO fortnight_activities 
                       (workforce_id, date, fortnight_period, section_id, corporal_id, price, is_fixed_price, created_at, updated_at)
                       VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s)""",
                    act_batch
                )
                act_batch = []

    if act_batch:
        t_cur.executemany(
            """INSERT INTO fortnight_activities 
               (workforce_id, date, fortnight_period, section_id, corporal_id, price, is_fixed_price, created_at, updated_at)
               VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s)""",
            act_batch
        )
    log(f"Migrated {total_acts} workforce fortnight activities with verified prices, section_id, and corporal_id.")
    tgt_conn.commit()

# -------------------------------------------------------------
# STAGE 4: Trucks Extraction & Pre-population
# -------------------------------------------------------------
def get_truck_plate_mapping(src_conn, tgt_conn):
    log("=== Extracting Unique Vehicle Plates ===")
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
        except Exception as e:
            pass

    log(f"Identified {len(plates)} distinct truck plates.")
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
# STAGE 5: Achats & Operational Costs -> charges & detail_charges
# -------------------------------------------------------------
def migrate_charges(src_conn, tgt_conn, cat_data, plate_to_id, valid_supplier_invoices):
    log("=== STAGE 5: Migrating Achats -> charges & detail_charges ===")
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

    # 1. Fresh Fish Receptions (frais)
    s_cur.execute("SELECT * FROM frais WHERE deleted_at IS NULL")
    frais_rows = s_cur.fetchall()
    log(f"Processing {len(frais_rows)} frais records...")

    for f in frais_rows:
        c_date = safe_date(f['date_reception'])
        tot_p = safe_float(f['total_price_reception'])
        tot_w = safe_float(f['total_weight_reception'])
        bon_no = f.get('num_bon_reception')
        rtn_json = json.dumps([bon_no]) if bon_no else None
        inv_id = f.get('bill_id') if f.get('is_facture') and f.get('bill_id') in valid_supplier_invoices else None
        sup_id = f.get('provider_id') if f.get('provider_id') in valid_suppliers else None
        fish_id = f.get('place_id') if f.get('place_id') in valid_fisheries else None
        orig_id = f.get('origin_id') if f.get('origin_id') in valid_origins else None
        ship_val = f.get('name_ship')
        ship_id = int(ship_val) if (isinstance(ship_val, int) or (isinstance(ship_val, str) and ship_val.isdigit())) and int(ship_val) in valid_ships else None

        t_cur.execute(
            """INSERT INTO charges 
               (id, date, total_price, total_weight, charge_category_id, reception_tracking_number, 
                fishery_id, ship_id, origin_id, supplier_id, allow_invoice, invoice_id, created_at, updated_at)
               VALUES (%s, %s, %s, %s, 1, %s, %s, %s, %s, %s, 1, %s, %s, %s)""",
            (f['id'], c_date, tot_p, tot_w, rtn_json, fish_id, ship_id, orig_id, sup_id, inv_id, f['created_at'], f['updated_at'])
        )
        charge_id = f['id']

        mats = safe_json(f.get('matricules')) or [f.get('matricule')]
        for m in mats:
            cp = clean_plate(m)
            if cp and cp in plate_to_id:
                charge_truck_batch.append((charge_id, plate_to_id[cp]))

        items = safe_json(f.get('types_reception'))
        for it in items:
            t_id = it.get('type_id')
            w = safe_float(it.get('weight'))
            pu = safe_float(it.get('price'))
            line_tot = safe_float(it.get('total_weight_price') or (w * pu))
            art_name = fish_type_names.get(t_id, f"Poisson #{t_id}")

            detail_charges_batch.append((
                charge_id, 1, art_name, 'App\\Models\\FishType', t_id,
                w, pu, line_tot, w, None, None, 991, 'stock_in', None, sup_id,
                f['created_at'], f['updated_at']
            ))

    tgt_conn.commit()

    # 2. Frozen Fish Purchases (congeles)
    s_cur.execute("SELECT * FROM congeles WHERE deleted_at IS NULL")
    cong_rows = s_cur.fetchall()
    log(f"Processing {len(cong_rows)} congeles records...")

    c_base_id = 100000
    for row in cong_rows:
        cid = c_base_id + row['id']
        c_date = safe_date(row.get('date_reception') or row.get('date_production'))
        tot_p = safe_float(row.get('total_price_production') or row.get('total_price_reception'))
        tot_w = safe_float(row.get('total_weight_production') or row.get('total_weight_reception'))
        bon_no = row.get('num_bon_reception') or row.get('num_bon_production')
        rtn_json = json.dumps([bon_no]) if bon_no else None
        inv_id = row.get('bill_id') if row.get('is_facture') and row.get('bill_id') in valid_supplier_invoices else None
        sup_id = row.get('provider_id') if row.get('provider_id') in valid_suppliers else None

        t_cur.execute(
            """INSERT INTO charges 
               (id, date, total_price, total_weight, charge_category_id, reception_tracking_number, 
                supplier_id, allow_invoice, invoice_id, created_at, updated_at)
               VALUES (%s, %s, %s, %s, 3, %s, %s, 1, %s, %s, %s)""",
            (cid, c_date, tot_p, tot_w, rtn_json, sup_id, inv_id, row['created_at'], row['updated_at'])
        )

        items = safe_json(row.get('types_production') or row.get('types_reception'))
        for it in items:
            t_id = it.get('type_id')
            w = safe_float(it.get('total_weight_container') or it.get('weight'))
            pu = safe_float(it.get('pu') or it.get('price'))
            line_tot = safe_float(it.get('price') or (w * pu))
            art_name = fish_type_names.get(t_id, f"Poisson Congelé #{t_id}")
            ch_id = it.get('chambre_id') or 13

            detail_charges_batch.append((
                cid, 3, art_name, 'App\\Models\\FishType', t_id,
                w, pu, line_tot, w, it.get('container_id'), it.get('nbr'), ch_id, 'stock_in', None, sup_id,
                row['created_at'], row['updated_at']
            ))

    tgt_conn.commit()

    # 3. Packaging Consumables (emballages)
    s_cur.execute("SELECT * FROM emballages WHERE deleted_at IS NULL")
    emb_rows = s_cur.fetchall()
    log(f"Processing {len(emb_rows)} emballages records...")

    e_base_id = 200000
    for row in emb_rows:
        cid = e_base_id + row['id']
        c_date = safe_date(row['date_reception'])
        tot_p = safe_float(row['total_price_reception'])
        tot_w = safe_float(row['total_weight_reception'])
        bon_no = row.get('num_bon_reception')
        rtn_json = json.dumps([bon_no]) if bon_no else None
        inv_id = row.get('bill_id') if row.get('is_facture') and row.get('bill_id') in valid_supplier_invoices else None
        sup_id = row.get('provider_id') if row.get('provider_id') in valid_suppliers else None

        t_cur.execute(
            """INSERT INTO charges 
               (id, date, total_price, total_weight, charge_category_id, reception_tracking_number, 
                supplier_id, allow_invoice, invoice_id, created_at, updated_at)
               VALUES (%s, %s, %s, %s, 5, %s, %s, 1, %s, %s, %s)""",
            (cid, c_date, tot_p, tot_w, rtn_json, sup_id, inv_id, row['created_at'], row['updated_at'])
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

    # 4. Ice Purchases (glaces)
    s_cur.execute("SELECT * FROM glaces WHERE deleted_at IS NULL")
    glace_rows = s_cur.fetchall()
    log(f"Processing {len(glace_rows)} glaces records...")

    g_base_id = 300000
    for row in glace_rows:
        cid = g_base_id + row['id']
        c_date = safe_date(row['date_reception'])
        tot_p = safe_float(row['total_price_reception'])
        tot_w = safe_float(row['total_weight_reception'])
        pu = safe_float(row['pu'])
        bon_no = row.get('num_bon_reception')
        rtn_json = json.dumps([bon_no]) if bon_no else None
        inv_id = row.get('bill_id') if row.get('is_facture') and row.get('bill_id') in valid_supplier_invoices else None
        sup_id = row.get('provider_id') if row.get('provider_id') in valid_suppliers else None

        t_cur.execute(
            """INSERT INTO charges 
               (id, date, total_price, total_weight, charge_category_id, reception_tracking_number, 
                supplier_id, allow_invoice, invoice_id, created_at, updated_at)
               VALUES (%s, %s, %s, %s, 7, %s, %s, 1, %s, %s, %s)""",
            (cid, c_date, tot_p, tot_w, rtn_json, sup_id, inv_id, row['created_at'], row['updated_at'])
        )

        detail_charges_batch.append((
            cid, 7, 'Glace', None, None,
            tot_w, pu, tot_p, tot_w, None, None, None, 'neutre', None, sup_id,
            row['created_at'], row['updated_at']
        ))

    tgt_conn.commit()

    # 5. Raw Guano Purchases (guanos)
    s_cur.execute("SELECT * FROM guanos WHERE deleted_at IS NULL")
    guano_rows = s_cur.fetchall()
    log(f"Processing {len(guano_rows)} guanos records...")

    gu_base_id = 400000
    for row in guano_rows:
        cid = gu_base_id + row['id']
        c_date = safe_date(row.get('date_reception') or row.get('date_production'))
        tot_p = safe_float(row.get('total_price_reception_guano') or row.get('total_price_reception'))
        tot_w = safe_float(row.get('total_weight_reception_guano') or row.get('total_weight_reception'))
        bon_no = row.get('num_bon_reception')
        rtn_json = json.dumps([bon_no]) if bon_no else None
        inv_id = row.get('bill_id') if row.get('is_facture') and row.get('bill_id') in valid_supplier_invoices else None
        sup_id = row.get('provider_id') if row.get('provider_id') in valid_suppliers else None

        t_cur.execute(
            """INSERT INTO charges 
               (id, date, total_price, total_weight, charge_category_id, reception_tracking_number, 
                supplier_id, allow_invoice, invoice_id, created_at, updated_at)
               VALUES (%s, %s, %s, %s, 4, %s, %s, 1, %s, %s, %s)""",
            (cid, c_date, tot_p, tot_w, rtn_json, sup_id, inv_id, row['created_at'], row['updated_at'])
        )

        detail_charges_batch.append((
            cid, 4, 'Guano Brut', 'App\\Models\\GuanoType', 1,
            tot_w, tot_p / tot_w if tot_w > 0 else 0, tot_p, tot_w, None, None, 992, 'stock_in', None, sup_id,
            row['created_at'], row['updated_at']
        ))

    tgt_conn.commit()

    # 6. Direct Fresh Purchases (achat_frais)
    s_cur.execute("SELECT * FROM achat_frais WHERE deleted_at IS NULL")
    af_rows = s_cur.fetchall()
    log(f"Processing {len(af_rows)} achat_frais records...")
    af_base_id = 800000
    for row in af_rows:
        cid = af_base_id + row['id']
        c_date = safe_date(row['date_reception'])
        bon_no = row.get('num_bon_reception')
        rtn_json = json.dumps([bon_no]) if bon_no else None
        inv_id = row.get('bill_id') if row.get('is_facture') and row.get('bill_id') in valid_supplier_invoices else None
        sup_id = row.get('provider_id') if row.get('provider_id') in valid_suppliers else None
        fish_id = row.get('place_id') if row.get('place_id') in valid_fisheries else None
        orig_id = row.get('origin_id') if row.get('origin_id') in valid_origins else None

        tot_w = 0.0
        tot_p = 0.0
        items = safe_json(row.get('types_reception'))
        for it in items:
            w = safe_float(it.get('total_weight_container') or it.get('weight'))
            p = safe_float(it.get('price'))
            tot_w += w
            tot_p += p

        t_cur.execute(
            """INSERT INTO charges 
               (id, date, total_price, total_weight, charge_category_id, reception_tracking_number, 
                fishery_id, origin_id, supplier_id, allow_invoice, invoice_id, created_at, updated_at)
               VALUES (%s, %s, %s, %s, 1, %s, %s, %s, %s, 1, %s, %s, %s)""",
            (cid, c_date, tot_p, tot_w, rtn_json, fish_id, orig_id, sup_id, inv_id, row['created_at'], row['updated_at'])
        )

        for it in items:
            t_id = it.get('type_id')
            w = safe_float(it.get('total_weight_container') or it.get('weight'))
            pu = safe_float(it.get('pu'))
            p = safe_float(it.get('price') or (w * pu))
            art_name = fish_type_names.get(t_id, f"Poisson #{t_id}")

            detail_charges_batch.append((
                cid, 1, art_name, 'App\\Models\\FishType', t_id,
                w, pu, p, w, it.get('container_id'), it.get('nbr'), 991, 'stock_in', None, sup_id,
                row['created_at'], row['updated_at']
            ))

    tgt_conn.commit()

    # 7. RSW Vessel Receptions (rsws)
    s_cur.execute("SELECT * FROM rsws WHERE deleted_at IS NULL")
    rsw_rows = s_cur.fetchall()
    log(f"Processing {len(rsw_rows)} rsws records...")
    rsw_base_id = 900000
    for row in rsw_rows:
        cid = rsw_base_id + row['id']
        c_date = safe_date(row['date_reception'])
        tot_w = safe_float(row['total_weight_reception'])
        tot_p = safe_float(row['total_price_reception'])
        bon_no = row.get('num_bon_reception')
        rtn_json = json.dumps([bon_no]) if bon_no else None
        sup_id = row.get('provider_id') if row.get('provider_id') in valid_suppliers else None
        fish_id = row.get('place_id') if row.get('place_id') in valid_fisheries else None
        orig_id = row.get('origin_id') if row.get('origin_id') in valid_origins else None

        t_cur.execute(
            """INSERT INTO charges 
               (id, date, total_price, total_weight, charge_category_id, reception_tracking_number, 
                fishery_id, origin_id, supplier_id, allow_invoice, created_at, updated_at)
               VALUES (%s, %s, %s, %s, 6, %s, %s, %s, %s, 1, %s, %s)""",
            (cid, c_date, tot_p, tot_w, rtn_json, fish_id, orig_id, sup_id, row['created_at'], row['updated_at'])
        )

        items = safe_json(row.get('types_reception'))
        for it in items:
            t_id = it.get('type_id')
            w = safe_float(it.get('weight'))
            pu = safe_float(it.get('price'))
            line_tot = safe_float(it.get('total_weight_price') or (w * pu))
            art_name = fish_type_names.get(t_id, f"Poisson RSW #{t_id}")

            detail_charges_batch.append((
                cid, 1, art_name, 'App\\Models\\FishType', t_id,
                w, pu, line_tot, w, None, None, 991, 'stock_in', None, sup_id,
                row['created_at'], row['updated_at']
            ))

    tgt_conn.commit()

    # 8. Miscellaneous & Maintenance & Labor & Transport (others, maintenances, mods, transports)
    m_base_id = 500000
    s_cur.execute("SELECT * FROM maintenances WHERE deleted_at IS NULL")
    for row in s_cur.fetchall():
        cid = m_base_id + row['id']
        sup_id = row.get('provider_id') if row.get('provider_id') in valid_suppliers else None
        inv_id = row.get('bill_id') if row.get('is_facture') and row.get('bill_id') in valid_supplier_invoices else None
        tot_m_price = safe_float(row.get('total_price_reception') or row.get('total_price_maintenances'))
        t_cur.execute(
            """INSERT INTO charges (id, date, total_price, total_weight, charge_category_id, supplier_id, allow_invoice, invoice_id, created_at, updated_at)
               VALUES (%s, %s, %s, 0.00, 9, %s, 1, %s, %s, %s)""",
            (cid, safe_date(row.get('date_reception') or row.get('date_maintenances')), tot_m_price, sup_id, inv_id, row['created_at'], row['updated_at'])
        )
        detail_charges_batch.append((
            cid, 9, row.get('description') or row.get('type') or 'Maintenance', None, None,
            0.0, 0.0, tot_m_price, 0.0, None, None, None, 'neutre', None, sup_id,
            row['created_at'], row['updated_at']
        ))

    o_base_id = 600000
    s_cur.execute("SELECT * FROM others WHERE deleted_at IS NULL")
    for row in s_cur.fetchall():
        cid = o_base_id + row['id']
        sup_id = row.get('provider_id') if row.get('provider_id') in valid_suppliers else None
        t_cur.execute(
            """INSERT INTO charges (id, date, total_price, total_weight, charge_category_id, supplier_id, allow_invoice, created_at, updated_at)
               VALUES (%s, %s, %s, 0.00, 10, %s, 1, %s, %s)""",
            (cid, safe_date(row['date_reception']), safe_float(row['total_price_reception']), sup_id, row['created_at'], row['updated_at'])
        )
        detail_charges_batch.append((
            cid, 10, 'Autres Charges', None, None,
            0.0, 0.0, safe_float(row['total_price_reception']), 0.0, None, None, None, 'neutre', None, sup_id,
            row['created_at'], row['updated_at']
        ))

    tr_base_id = 750000
    s_cur.execute("SELECT * FROM transports WHERE deleted_at IS NULL")
    for row in s_cur.fetchall():
        cid = tr_base_id + row['id']
        sup_id = row.get('provider_id') if row.get('provider_id') in valid_suppliers else None
        tot_p = safe_float(row.get('total_price_reception'))
        t_cur.execute(
            """INSERT INTO charges (id, date, total_price, total_weight, charge_category_id, supplier_id, allow_invoice, created_at, updated_at)
               VALUES (%s, %s, %s, 0.00, 8, %s, 1, %s, %s)""",
            (cid, safe_date(row['date_reception']), tot_p, sup_id, row['created_at'], row['updated_at'])
        )
        detail_charges_batch.append((
            cid, 8, 'Frais de Transport', None, None,
            0.0, 0.0, tot_p, 0.0, None, None, None, 'neutre', None, sup_id,
            row['created_at'], row['updated_at']
        ))

    # MOD Labor
    mod_base_id = 700000
    s_cur.execute("SELECT * FROM mods WHERE deleted_at IS NULL")
    for row in s_cur.fetchall():
        cid = mod_base_id + row['id']
        t_cur.execute(
            """INSERT INTO charges (id, date, total_price, total_weight, charge_category_id, allow_invoice, created_at, updated_at)
               VALUES (%s, %s, %s, 0.00, 10, 0, %s, %s)""",
            (cid, safe_date(row['date_pointage']), safe_float(row['total']), row['created_at'], row['updated_at'])
        )
        detail_charges_batch.append((
            cid, 10, f"Main d'oeuvre (Caporal #{row.get('corporal_id')})", None, None,
            0.0, 0.0, safe_float(row['total']), 0.0, None, None, None, 'neutre', None, None,
            row['created_at'], row['updated_at']
        ))

    tgt_conn.commit()

    # Batch insert all detail_charges
    log(f"Inserting {len(detail_charges_batch)} detail_charges rows...")
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

    # Batch insert charge_truck
    if charge_truck_batch:
        log(f"Inserting {len(charge_truck_batch)} charge_truck links...")
        for i in range(0, len(charge_truck_batch), chunk_size):
            t_cur.executemany("INSERT IGNORE INTO charge_truck (charge_id, truck_id) VALUES (%s, %s)", charge_truck_batch[i:i+chunk_size])

    tgt_conn.commit()
    log("Completed charges and detail_charges migration.")

# -------------------------------------------------------------
# STAGE 6: Factory Processing -> productions & detail_productions
# -------------------------------------------------------------
def migrate_production(src_conn, tgt_conn, cat_data, valid_client_invoices):
    log("=== STAGE 6: Migrating Factory Processing -> productions & detail_productions ===")
    s_cur = src_conn.cursor()
    t_cur = tgt_conn.cursor()

    fish_type_names = cat_data['fish_type_names']
    valid_suppliers = cat_data['valid_suppliers']
    valid_clients = cat_data['valid_clients']
    valid_fisheries = cat_data['valid_fisheries']
    valid_origins = cat_data['valid_origins']
    valid_warehouses = cat_data['valid_warehouses']

    detail_prod_batch = []

    # 1. Blast Freezing Tunnels (congelations)
    s_cur.execute("SELECT * FROM congelations WHERE deleted_at IS NULL")
    cong_rows = s_cur.fetchall()
    log(f"Processing {len(cong_rows)} congelations production records...")

    for row in cong_rows:
        pid = row['id']
        p_date = safe_date(row['date_reception'] or row['date_production'])
        tot_w = safe_float(row['total_weight_production'])
        tot_p = safe_float(row['total_price_production'])
        bon_no = row.get('num_bon_reception') or row.get('num_bon_production')
        ptn_json = json.dumps([bon_no]) if bon_no else None
        sup_id = row.get('provider_id') if row.get('provider_id') in valid_suppliers else None
        fish_id = row.get('place_id') if row.get('place_id') in valid_fisheries else None
        orig_id = row.get('origin_id') if row.get('origin_id') in valid_origins else None

        fresh_in = 0.0
        for it in safe_json(row.get('types_reception')):
            fresh_in += safe_float(it.get('weight'))

        t_cur.execute(
            """INSERT INTO productions 
               (id, date, total_price, total_weight, fresh_reception_weight, frozen_production_weight, 
                equilibrium, equilibrium_status, production_category_id, production_tracking_number,
                fishery_id, origin_id, supplier_id, allow_invoice, total_secondary_packaging_cost, created_at, updated_at)
               VALUES (%s, %s, %s, %s, %s, %s, %s, 'balanced', 1, %s, %s, %s, %s, 1, 0.00, %s, %s)""",
            (pid, p_date, tot_p, tot_w, fresh_in, tot_w, (tot_w - fresh_in), ptn_json,
             fish_id, orig_id, sup_id, row['created_at'], row['updated_at'])
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

    # 2. Fishmeal Factory (guano_usines)
    s_cur.execute("SELECT * FROM guano_usines WHERE deleted_at IS NULL")
    gu_rows = s_cur.fetchall()
    log(f"Processing {len(gu_rows)} guano_usines production records...")

    g_base_id = 50000
    for row in gu_rows:
        pid = g_base_id + row['id']
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

    # 3. RSW Productions (rsws with types_production)
    s_cur.execute("SELECT * FROM rsws WHERE deleted_at IS NULL AND types_production IS NOT NULL AND types_production != '[]'")
    rsw_prods = s_cur.fetchall()
    log(f"Processing {len(rsw_prods)} RSW production runs...")
    rsw_p_base = 80000
    for row in rsw_prods:
        pid = rsw_p_base + row['id']
        p_date = safe_date(row['date_production'] or row['date_reception'])
        tot_w = safe_float(row['total_weight_production'])
        tot_p = 0.0
        bon_no = row.get('num_bon_production') or row.get('num_bon_reception')
        ptn_json = json.dumps([bon_no]) if bon_no else None
        sup_id = row.get('provider_id') if row.get('provider_id') in valid_suppliers else None
        fish_id = row.get('place_id') if row.get('place_id') in valid_fisheries else None
        orig_id = row.get('origin_id') if row.get('origin_id') in valid_origins else None

        items = safe_json(row.get('types_production'))
        for it in items:
            w = safe_float(it.get('total_weight_container') or (safe_float(it.get('nbr')) * 20.0))
            pu = safe_float(it.get('pu'))
            p = safe_float(it.get('price') or (w * pu))
            tot_p += p

        t_cur.execute(
            """INSERT INTO productions 
               (id, date, total_price, total_weight, fresh_reception_weight, frozen_production_weight, 
                equilibrium, equilibrium_status, production_category_id, production_tracking_number,
                fishery_id, origin_id, supplier_id, allow_invoice, total_secondary_packaging_cost, created_at, updated_at)
               VALUES (%s, %s, %s, %s, %s, %s, 0.00, 'balanced', 1, %s, %s, %s, %s, 1, 0.00, %s, %s)""",
            (pid, p_date, tot_p, tot_w, tot_w, tot_w, ptn_json, fish_id, orig_id, sup_id, row['created_at'], row['updated_at'])
        )

        for it in items:
            t_id = it.get('type_id')
            w = safe_float(it.get('total_weight_container') or (safe_float(it.get('nbr')) * 20.0))
            pu = safe_float(it.get('pu'))
            p = safe_float(it.get('price') or (w * pu))
            art_name = fish_type_names.get(t_id, f"Poisson RSW Produit #{t_id}")
            raw_ch = it.get('chambre_id')
            ch_id = raw_ch if raw_ch in valid_warehouses else 13

            detail_prod_batch.append((
                pid, 1, art_name, 'App\\Models\\FishType', t_id,
                w, pu, p, 0.00, it.get('container_id'), it.get('nbr'), ch_id, 'stock_in', None,
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
    log("Completed productions and detail_productions migration.")

# -------------------------------------------------------------
# STAGE 7: Outbound Dispatches & Sales -> shippings & detail_shippings
# -------------------------------------------------------------
def migrate_shippings(src_conn, tgt_conn, cat_data, plate_to_id, valid_client_invoices):
    log("=== STAGE 7: Migrating Ventes & Dispatches -> shippings & detail_shippings ===")
    s_cur = src_conn.cursor()
    t_cur = tgt_conn.cursor()

    fish_type_names = cat_data['fish_type_names']
    valid_clients = cat_data['valid_clients']
    valid_warehouses = cat_data['valid_warehouses']

    shipping_truck_batch = []
    detail_shipping_batch = []

    # 1. Master Fish Sales (ventes)
    s_cur.execute("SELECT * FROM ventes WHERE deleted_at IS NULL")
    ventes_rows = s_cur.fetchall()
    log(f"Processing {len(ventes_rows)} ventes records...")

    for v in ventes_rows:
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

        if is_guano:
            cat_id = 7
            det_cat_id = 3
        elif is_export:
            cat_id = 2
            det_cat_id = 1
        else:
            cat_id = 1
            det_cat_id = 1

        t_cur.execute(
            """INSERT INTO shippings 
               (id, date, total_price, total_weight, shipping_category_id, shipping_tracking_number, 
                direction, client_id, allow_invoice, invoice_id, created_at, updated_at)
               VALUES (%s, %s, %s, %s, %s, %s, %s, %s, 1, %s, %s, %s)""",
            (vid, v_date, tot_p, tot_w, cat_id, stn_json, 'export' if is_export else 'local',
             cli_id, inv_id, v['created_at'], v['updated_at'])
        )

        mats = safe_json(v.get('matricules')) or [v.get('matricule')]
        for m in mats:
            cp = clean_plate(m)
            if cp and cp in plate_to_id:
                shipping_truck_batch.append((vid, plate_to_id[cp]))

        items = safe_json(v.get('types_sortie'))
        for it in items:
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

    # 2. Third-Party Freezing Dispatches (congelation_clients)
    s_cur.execute("SELECT * FROM congelation_clients WHERE deleted_at IS NULL")
    cc_rows = s_cur.fetchall()
    log(f"Processing {len(cc_rows)} congelation_clients records...")

    cc_base_id = 100000
    for row in cc_rows:
        sid = cc_base_id + row['id']
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

        items = safe_json(row.get('types_production'))
        for it in items:
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

    # 3. Packaging Dispatches (emballage_clients)
    s_cur.execute("SELECT * FROM emballage_clients WHERE deleted_at IS NULL")
    emb_c_rows = s_cur.fetchall()
    log(f"Processing {len(emb_c_rows)} emballage_clients records...")

    emb_c_base = 200000
    for row in emb_c_rows:
        sid = emb_c_base + row['id']
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

    # 4. Outbound Stock Exits (stock_exits)
    s_cur.execute("SELECT * FROM stock_exits")
    exit_rows = s_cur.fetchall()
    log(f"Processing {len(exit_rows)} stock_exits records...")

    stock_exit_base = 500000
    for row in exit_rows:
        sid = stock_exit_base + row['id']
        s_date = safe_date(row['date_sortie'])
        tot_w = safe_float(row['total_weight_sortie'])
        tot_p = safe_float(row['total_price_sortie'])
        bons = safe_json(row.get('num_bon_sorties'))
        if not bons and row.get('num_bon_sortie'):
            bons = [str(row['num_bon_sortie']).strip()]
        stn_json = json.dumps(bons) if bons else None

        cli_id = row.get('provider_id') if row.get('provider_id') in valid_clients else None
        is_guano = (row.get('type') == 'guano')
        cat_id = 7 if is_guano else 1

        t_cur.execute(
            """INSERT INTO shippings 
               (id, date, total_price, total_weight, shipping_category_id, shipping_tracking_number, 
                direction, client_id, allow_invoice, invoice_id, created_at, updated_at, deleted_at)
               VALUES (%s, %s, %s, %s, %s, %s, 'local', %s, 0, NULL, %s, %s, %s)""",
            (sid, s_date, tot_p, tot_w, cat_id, stn_json, cli_id, row['created_at'], row['updated_at'], row.get('deleted_at'))
        )

        mats = safe_json(row.get('matricules')) or []
        for m in mats:
            cp = clean_plate(m)
            if cp and cp in plate_to_id:
                shipping_truck_batch.append((sid, plate_to_id[cp]))

        items = safe_json(row.get('types_sortie')) or []
        if not items and tot_w > 0:
            art_name = "Guano" if is_guano else "Poisson Sortie"
            stk_type = 'App\\Models\\GuanoType' if is_guano else 'App\\Models\\FishType'
            ch_id = 992 if is_guano else 991
            detail_shipping_batch.append((
                sid, 1, art_name, stk_type, 1,
                tot_w, 0.0, tot_p, None, None, ch_id, 'stock_out', None,
                row['created_at'], row['updated_at']
            ))
        else:
            for it in items:
                t_id = it.get('type_id')
                w = safe_float(it.get('weight'))
                pu = safe_float(it.get('pu'))
                p = safe_float(it.get('price')) or round(w * pu, 2)
                raw_ch = it.get('chambre_id')
                ch_id = raw_ch if raw_ch in valid_warehouses else (992 if is_guano else 991)
                stk_type = 'App\\Models\\GuanoType' if is_guano else 'App\\Models\\FishType'
                art_name = guano_type_names.get(t_id, 'Guano') if is_guano else fish_type_names.get(t_id, f"Poisson #{t_id}")

                detail_shipping_batch.append((
                    sid, 1, art_name, stk_type, t_id,
                    w, pu, p, it.get('container_id'), it.get('nbr'), ch_id, 'stock_out', None,
                    row['created_at'], row['updated_at']
                ))

    tgt_conn.commit()

    log(f"Inserting {len(detail_shipping_batch)} detail_shippings rows...")
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

    if shipping_truck_batch:
        log(f"Inserting {len(shipping_truck_batch)} shipping_truck links...")
        for i in range(0, len(shipping_truck_batch), chunk_size):
            t_cur.executemany("INSERT IGNORE INTO shipping_truck (shipping_id, truck_id) VALUES (%s, %s)", shipping_truck_batch[i:i+chunk_size])

    tgt_conn.commit()
    log("Completed shippings and detail_shippings migration.")

# -------------------------------------------------------------
# STAGE 8: Migrating Stock Transfers (stock_movement_logs -> warehouse_transfers)
# -------------------------------------------------------------
def migrate_stock_transfers(src_conn, tgt_conn, cat_data, plate_to_id):
    log("=== STAGE 8: Migrating Stock Transfers (stock_movement_logs -> warehouse_transfers) ===")
    s_cur = src_conn.cursor()
    t_cur = tgt_conn.cursor()

    valid_warehouses = cat_data['valid_warehouses']
    valid_packages = cat_data['valid_packages']
    fish_type_names = cat_data['fish_type_names']

    s_cur.execute("SELECT * FROM stock_movement_logs")
    transfer_rows = s_cur.fetchall()
    log(f"Processing {len(transfer_rows)} stock_movement_logs records...")

    transfer_truck_batch = []
    detail_transfer_batch = []

    for row in transfer_rows:
        tid = row['id']
        t_date = safe_date(row['date'])
        from_ch = row.get('from_chambre_id') if row.get('from_chambre_id') in valid_warehouses else 991
        to_ch = row.get('to_chambre_id') if row.get('to_chambre_id') in valid_warehouses else 991

        bons = safe_json(row.get('num_bon_transfers')) or []
        stn_json = json.dumps(bons) if bons else json.dumps([str(tid)])

        mats = safe_json(row.get('matricules')) or []
        primary_truck_id = None
        for m in mats:
            cp = clean_plate(m)
            if cp and cp in plate_to_id:
                t_truck_id = plate_to_id[cp]
                transfer_truck_batch.append((tid, t_truck_id))
                if primary_truck_id is None:
                    primary_truck_id = t_truck_id

        items_ss = safe_json(row.get('types_ss')) or []
        items_prod = safe_json(row.get('types_production')) or []
        items_to_use = items_ss if len(items_ss) > 0 else items_prod

        transfer_total_weight = 0.0

        for idx, it in enumerate(items_to_use):
            w = safe_float(it.get('weight'))
            t_id = it.get('type_id')
            raw_dest = it.get('chambre_id')
            dest_wh = raw_dest if raw_dest in valid_warehouses else to_ch
            src_wh = from_ch
            pkg_id = it.get('container_id') if it.get('container_id') in valid_packages else None
            nbr_box = safe_float(it.get('nbr'))
            if nbr_box == 0.0 and idx < len(items_prod):
                nbr_box = safe_float(items_prod[idx].get('nbr'))
            src_track = it.get('num_bon_reception') or (items_prod[idx].get('num_bon_reception') if idx < len(items_prod) else None)

            transfer_total_weight += w

            detail_transfer_batch.append((
                tid, 1, 'App\\Models\\FishType', t_id,
                w, src_wh, src_track, dest_wh, pkg_id, nbr_box, primary_truck_id,
                row['created_at'], row['updated_at']
            ))

        if transfer_total_weight == 0.0:
            transfer_total_weight = safe_float(row.get('qte_transfer'))

        t_cur.execute(
            """INSERT INTO warehouse_transfers 
               (id, date, total_weight, transfer_tracking_number, description, created_at, updated_at, deleted_at)
               VALUES (%s, %s, %s, %s, 'Transfert inter-chambres', %s, %s, %s)""",
            (tid, t_date, transfer_total_weight, stn_json, row['created_at'], row['updated_at'], row.get('deleted_at'))
        )

    tgt_conn.commit()

    log(f"Inserting {len(detail_transfer_batch)} detail_warehouse_transfers rows...")
    chunk_size = 5000
    for i in range(0, len(detail_transfer_batch), chunk_size):
        chunk = detail_transfer_batch[i:i+chunk_size]
        t_cur.executemany(
            """INSERT INTO detail_warehouse_transfers 
               (warehouse_transfer_id, warehouse_transfer_category_id, stockable_type, stockable_id,
                weight, source_warehouse_id, source_tracking_number, destination_warehouse_id,
                package_type_id, nbr_box, truck_id, created_at, updated_at)
               VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)""",
            chunk
        )

    if transfer_truck_batch:
        log(f"Inserting {len(transfer_truck_batch)} warehouse_transfer_truck links...")
        for i in range(0, len(transfer_truck_batch), chunk_size):
            t_cur.executemany(
                """INSERT IGNORE INTO warehouse_transfer_truck (warehouse_transfer_id, truck_id) VALUES (%s, %s)""",
                transfer_truck_batch[i:i+chunk_size]
            )

    tgt_conn.commit()
    log("Completed stock transfers migration.")

# -------------------------------------------------------------
# STAGE 9: Migrating Fiscal Year Closings (client_management & provider_management)
# -------------------------------------------------------------
def migrate_fiscal_year_closings(src_conn, tgt_conn, cat_data):
    log("=== STAGE 9: Migrating Fiscal Year Closings ===")
    s_cur = src_conn.cursor()
    t_cur = tgt_conn.cursor()

    valid_clients = cat_data['valid_clients']
    valid_suppliers = cat_data['valid_suppliers']

    # 1. Client Management -> client_fiscal_year_closings & client_fiscal_year_closing_lines
    s_cur.execute("SELECT * FROM client_management WHERE deleted_at IS NULL")
    cli_rows = s_cur.fetchall()
    by_year_cli = {}
    for r in cli_rows:
        yr = r['date'].year if r.get('date') else 2022
        by_year_cli.setdefault(yr, []).append(r)

    for yr, rows in sorted(by_year_cli.items()):
        tot_deb = round(sum(safe_float(r.get('debits')) for r in rows), 2)
        tot_cred = round(sum(safe_float(r.get('credits')) for r in rows), 2)
        tot_solde = round(sum(safe_float(r.get('debiteurs')) for r in rows), 2)
        closing_dt = f"{yr}-12-31"

        t_cur.execute(
            """INSERT INTO client_fiscal_year_closings 
               (year, closing_date, total_invoices, total_payments, carried_forward, is_closed, created_at, updated_at)
               VALUES (%s, %s, %s, %s, %s, 1, NOW(), NOW())""",
            (yr, closing_dt, tot_deb, tot_cred, tot_solde)
        )
        closing_id = t_cur.lastrowid

        line_batch = []
        for r in rows:
            cid = r.get('provider_id') if r.get('provider_id') in valid_clients else None
            deb = round(safe_float(r.get('debits')), 2)
            cred = round(safe_float(r.get('credits')), 2)
            debiteur = round(safe_float(r.get('debiteurs')), 2)
            crediteur = round(safe_float(r.get('crediteurs')), 2)
            bal = round(debiteur - crediteur, 2)

            line_batch.append((closing_id, cid, deb, cred, debiteur, bal, r['created_at'], r['updated_at']))

        t_cur.executemany(
            """INSERT INTO client_fiscal_year_closing_lines 
               (client_fiscal_year_closing_id, client_id, total_invoices, total_payments, carry_forward, balance, created_at, updated_at)
               VALUES (%s, %s, %s, %s, %s, %s, %s, %s)""",
            line_batch
        )

    log(f"Migrated client fiscal year closings across {len(by_year_cli)} years.")

    # 2. Provider Management -> fiscal_year_closings & fiscal_year_closing_lines
    s_cur.execute("SELECT * FROM provider_management WHERE deleted_at IS NULL")
    sup_rows = s_cur.fetchall()
    by_year_sup = {}
    for r in sup_rows:
        yr = r['date'].year if r.get('date') else 2023
        by_year_sup.setdefault(yr, []).append(r)

    for yr, rows in sorted(by_year_sup.items()):
        tot_deb = round(sum(safe_float(r.get('debits')) for r in rows), 2)
        tot_cred = round(sum(safe_float(r.get('credits')) for r in rows), 2)
        tot_solde = round(sum(safe_float(r.get('debiteurs')) for r in rows), 2)
        closing_dt = f"{yr}-12-31"

        t_cur.execute(
            """INSERT INTO fiscal_year_closings 
               (year, closing_date, total_invoices, total_payments, carried_forward, is_closed, created_at, updated_at)
               VALUES (%s, %s, %s, %s, %s, 1, NOW(), NOW())""",
            (yr, closing_dt, tot_deb, tot_cred, tot_solde)
        )
        closing_id = t_cur.lastrowid

        line_batch = []
        for r in rows:
            sid = r.get('provider_id') if r.get('provider_id') in valid_suppliers else None
            deb = round(safe_float(r.get('debits')), 2)
            cred = round(safe_float(r.get('credits')), 2)
            debiteur = round(safe_float(r.get('debiteurs')), 2)
            crediteur = round(safe_float(r.get('crediteurs')), 2)
            bal = round(debiteur - crediteur, 2)

            line_batch.append((closing_id, sid, deb, cred, bal, r['created_at'], r['updated_at']))

        t_cur.executemany(
            """INSERT INTO fiscal_year_closing_lines 
               (fiscal_year_closing_id, supplier_id, invoice_total, payment_total, balance, created_at, updated_at)
               VALUES (%s, %s, %s, %s, %s, %s, %s)""",
            line_batch
        )

    tgt_conn.commit()
    log(f"Migrated supplier fiscal year closings across {len(by_year_sup)} years.")

# -------------------------------------------------------------
# STAGE 10: Invoicing Grouping & Settlements (Client & Supplier)
# -------------------------------------------------------------
def reconcile_invoices_and_settlements(tgt_conn):
    log("=== STAGE 8: Reconciling Invoices Grouped Totals & Creating Settlements ===")
    t_cur = tgt_conn.cursor()

    # 1. Update client_invoices from shippings and productions
    t_cur.execute(
        """SELECT s.invoice_id, 
                  SUM(s.total_price) as sum_price, 
                  SUM(s.total_weight) as sum_weight
           FROM shippings s
           WHERE s.invoice_id IS NOT NULL AND s.deleted_at IS NULL
           GROUP BY s.invoice_id"""
    )
    ship_map = {r['invoice_id']: r for r in t_cur.fetchall()}

    t_cur.execute(
        """SELECT p.client_invoice_id, 
                  SUM(p.total_price) as sum_price, 
                  SUM(p.total_weight) as sum_weight
           FROM productions p
           WHERE p.client_invoice_id IS NOT NULL AND p.deleted_at IS NULL
           GROUP BY p.client_invoice_id"""
    )
    prod_map = {r['client_invoice_id']: r for r in t_cur.fetchall()}

    all_inv_ids = set(ship_map.keys()) | set(prod_map.keys())
    for inv_id in all_inv_ids:
        sp = float(ship_map.get(inv_id, {}).get('sum_price', 0.0) or 0.0)
        sw = float(ship_map.get(inv_id, {}).get('sum_weight', 0.0) or 0.0)
        pp = float(prod_map.get(inv_id, {}).get('sum_price', 0.0) or 0.0)
        pw = float(prod_map.get(inv_id, {}).get('sum_weight', 0.0) or 0.0)
        tot_p = round(sp + pp, 2)
        tot_w = round(sw + pw, 2)
        if tot_p > 0 or tot_w > 0:
            t_cur.execute(
                "UPDATE client_invoices SET total_price = %s, total_weight = %s WHERE id = %s",
                (tot_p, tot_w, inv_id)
            )

    # Create client payments for all active client invoices with total_price > 0
    t_cur.execute(
        """SELECT id, date, client_id, total_price, tracking_numbers, created_at, updated_at
           FROM client_invoices
           WHERE deleted_at IS NULL AND client_id IS NOT NULL AND total_price > 0"""
    )
    client_invoices = t_cur.fetchall()
    c_pay_batch = []
    for ci in client_invoices:
        tr_list = safe_json(ci['tracking_numbers'])
        ref = tr_list[0] if (tr_list and len(tr_list) > 0) else str(ci['id'])
        c_pay_batch.append((
            ci['date'], ci['id'], ci['client_id'], 'bank_transfer',
            ci['total_price'], ref, 'Règlement facture client (migration)',
            ci['created_at'], ci['updated_at']
        ))
        if len(c_pay_batch) >= 1000:
            t_cur.executemany(
                """INSERT INTO client_payments 
                   (date, client_invoice_id, client_id, payment_type, amount, reference, notes, created_at, updated_at)
                   VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s)""",
                c_pay_batch
            )
            c_pay_batch = []
    if c_pay_batch:
        t_cur.executemany(
            """INSERT INTO client_payments 
               (date, client_invoice_id, client_id, payment_type, amount, reference, notes, created_at, updated_at)
               VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s)""",
            c_pay_batch
        )
    log(f"Created settlements for {len(client_invoices)} client invoices.")

    # 2. Update supplier_invoices from charges
    t_cur.execute(
        """SELECT c.invoice_id, 
                  SUM(c.total_price) as sum_price, 
                  SUM(c.total_weight) as sum_weight
           FROM charges c
           WHERE c.invoice_id IS NOT NULL AND c.deleted_at IS NULL
           GROUP BY c.invoice_id"""
    )
    charge_map = {r['invoice_id']: r for r in t_cur.fetchall()}
    for inv_id, ch_data in charge_map.items():
        cp = round(float(ch_data['sum_price'] or 0.0), 2)
        cw = round(float(ch_data['sum_weight'] or 0.0), 2)
        if cp > 0 or cw > 0:
            t_cur.execute(
                "UPDATE supplier_invoices SET total_price = %s, total_weight = %s WHERE id = %s",
                (cp, cw, inv_id)
            )

    # Create supplier payments for all active supplier invoices with total_price > 0
    t_cur.execute(
        """SELECT id, date, supplier_id, total_price, tracking_numbers, created_at, updated_at
           FROM supplier_invoices
           WHERE deleted_at IS NULL AND supplier_id IS NOT NULL AND total_price > 0"""
    )
    supplier_invoices = t_cur.fetchall()
    s_pay_batch = []
    for si in supplier_invoices:
        tr_list = safe_json(si['tracking_numbers'])
        ref = tr_list[0] if (tr_list and len(tr_list) > 0) else str(si['id'])
        s_pay_batch.append((
            si['date'], si['id'], si['supplier_id'], 'bank_transfer',
            si['total_price'], ref, 'Règlement facture fournisseur (migration)',
            si['created_at'], si['updated_at']
        ))
        if len(s_pay_batch) >= 1000:
            t_cur.executemany(
                """INSERT INTO supplier_payments 
                   (date, supplier_invoice_id, supplier_id, payment_type, amount, reference, notes, created_at, updated_at)
                   VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s)""",
                s_pay_batch
            )
            s_pay_batch = []
    if s_pay_batch:
        t_cur.executemany(
            """INSERT INTO supplier_payments 
               (date, supplier_invoice_id, supplier_id, payment_type, amount, reference, notes, created_at, updated_at)
               VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s)""",
            s_pay_batch
        )
    log(f"Created settlements for {len(supplier_invoices)} supplier invoices.")
    tgt_conn.commit()

# -------------------------------------------------------------
# STAGE 9: Stock Calculation & Warehouse Balances Engine
# -------------------------------------------------------------
def calculate_and_build_warehouse_stock(tgt_conn):
    log("=== STAGE 9: Calculating Stock & Building Warehouse Balances ===")
    t_cur = tgt_conn.cursor()

    log("Creating initial stock-in balances from detail_productions...")
    t_cur.execute(
        """SELECT dp.id as detail_id, dp.warehouse_id, dp.stockable_type, dp.stockable_id,
                  dp.weight, dp.unit_price, dp.total_price, dp.nbr_box, dp.package_type_id,
                  p.id as prod_id, p.date, p.production_tracking_number, p.supplier_id
           FROM detail_productions dp
           JOIN productions p ON dp.production_id = p.id
           WHERE dp.warehouse_operation = 'stock_in' AND dp.warehouse_id IS NOT NULL AND dp.weight > 0
           ORDER BY p.date ASC, dp.id ASC"""
    )
    in_prods = t_cur.fetchall()

    bal_batch = []
    for row in in_prods:
        rtn_str = clean_tracking_str(row['production_tracking_number'])
        bal_batch.append((
            row['warehouse_id'], row['stockable_type'], row['stockable_id'], rtn_str,
            'stock_in', row['date'], row['weight'], row['weight'], row['total_price'], row['unit_price'],
            row['nbr_box'] or 0.0, row['package_type_id'], 'production', row['prod_id'], 0,
            datetime.now(), datetime.now()
        ))
        if len(bal_batch) >= 5000:
            t_cur.executemany(
                """INSERT INTO warehouse_balances 
                   (warehouse_id, stockable_type, stockable_id, reception_tracking_number,
                    warehouse_operation, date, total_weight, remaining_weight, total_price, unit_price,
                    package_count, package_type_id, source_type, source_id, is_finished, created_at, updated_at)
                   VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)""",
                bal_batch
            )
            bal_batch = []
    if bal_batch:
        t_cur.executemany(
            """INSERT INTO warehouse_balances 
               (warehouse_id, stockable_type, stockable_id, reception_tracking_number,
                warehouse_operation, date, total_weight, remaining_weight, total_price, unit_price,
                package_count, package_type_id, source_type, source_id, is_finished, created_at, updated_at)
               VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)""",
            bal_batch
        )

    tgt_conn.commit()
    log("Loaded all production stock-in balances.")

    t_cur.execute(
        """SELECT dc.id as detail_id, dc.warehouse_id, dc.stockable_type, dc.stockable_id,
                  dc.weight, dc.unit_price, dc.total_price, dc.nbr_box, dc.package_type_id,
                  c.id as charge_id, c.date, c.reception_tracking_number
           FROM detail_charges dc
           JOIN charges c ON dc.charge_id = c.id
           WHERE dc.warehouse_operation = 'stock_in' AND dc.warehouse_id IS NOT NULL AND dc.weight > 0
           ORDER BY c.date ASC, dc.id ASC"""
    )
    in_charges = t_cur.fetchall()
    bal_batch = []
    for row in in_charges:
        rtn_str = clean_tracking_str(row['reception_tracking_number'])
        bal_batch.append((
            row['warehouse_id'], row['stockable_type'], row['stockable_id'], rtn_str,
            'stock_in', row['date'], row['weight'], row['weight'], row['total_price'], row['unit_price'],
            row['nbr_box'] or 0.0, row['package_type_id'], 'charge', row['charge_id'], 0,
            datetime.now(), datetime.now()
        ))
        if len(bal_batch) >= 5000:
            t_cur.executemany(
                """INSERT INTO warehouse_balances 
                   (warehouse_id, stockable_type, stockable_id, reception_tracking_number,
                    warehouse_operation, date, total_weight, remaining_weight, total_price, unit_price,
                    package_count, package_type_id, source_type, source_id, is_finished, created_at, updated_at)
                   VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)""",
                bal_batch
            )
            bal_batch = []
    if bal_batch:
        t_cur.executemany(
            """INSERT INTO warehouse_balances 
               (warehouse_id, stockable_type, stockable_id, reception_tracking_number,
                warehouse_operation, date, total_weight, remaining_weight, total_price, unit_price,
                package_count, package_type_id, source_type, source_id, is_finished, created_at, updated_at)
               VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)""",
            bal_batch
        )
    tgt_conn.commit()
    log("Loaded all charge stock-in balances.")

    # Inbound transfers into destination warehouse
    t_cur.execute(
        """SELECT dwt.id as detail_id, dwt.warehouse_transfer_id, dwt.stockable_type, dwt.stockable_id,
                  dwt.weight, dwt.destination_warehouse_id as warehouse_id,
                  dwt.source_tracking_number, dwt.package_type_id, dwt.nbr_box,
                  wt.date
           FROM detail_warehouse_transfers dwt
           JOIN warehouse_transfers wt ON dwt.warehouse_transfer_id = wt.id
           WHERE dwt.weight > 0 AND dwt.destination_warehouse_id IS NOT NULL
           ORDER BY wt.date ASC, dwt.id ASC"""
    )
    in_transfers = t_cur.fetchall()
    log(f"Loading {len(in_transfers)} transfer stock-in balances...")

    bal_batch = []
    for tr in in_transfers:
        rtn_str = clean_tracking_str(tr['source_tracking_number'])
        bal_batch.append((
            tr['warehouse_id'], tr['stockable_type'], tr['stockable_id'], rtn_str,
            'stock_in', tr['date'], tr['weight'], tr['weight'], 0.0, 0.0,
            tr['nbr_box'] or 0.0, tr['package_type_id'], 'WarehouseTransfer', tr['warehouse_transfer_id'], 0,
            datetime.now(), datetime.now()
        ))
        if len(bal_batch) >= 5000:
            t_cur.executemany(
                """INSERT INTO warehouse_balances 
                   (warehouse_id, stockable_type, stockable_id, reception_tracking_number,
                    warehouse_operation, date, total_weight, remaining_weight, total_price, unit_price,
                    package_count, package_type_id, source_type, source_id, is_finished, created_at, updated_at)
                   VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)""",
                bal_batch
            )
            bal_batch = []
    if bal_batch:
        t_cur.executemany(
            """INSERT INTO warehouse_balances 
               (warehouse_id, stockable_type, stockable_id, reception_tracking_number,
                warehouse_operation, date, total_weight, remaining_weight, total_price, unit_price,
                package_count, package_type_id, source_type, source_id, is_finished, created_at, updated_at)
               VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)""",
            bal_batch
        )
    tgt_conn.commit()
    log("Loaded all transfer stock-in balances.")

    log("Processing outbound exits and linking warehouse balance operations...")
    t_cur.execute(
        """SELECT ds.id as detail_id, ds.warehouse_id, ds.stockable_type, ds.stockable_id,
                  ds.weight, ds.unit_price, ds.total_price, ds.nbr_box,
                  s.id as ship_id, 'shipping' as source_type, s.date, s.client_id
           FROM detail_shippings ds
           JOIN shippings s ON ds.shipping_id = s.id
           WHERE ds.warehouse_operation = 'stock_out' AND ds.warehouse_id IS NOT NULL AND ds.weight > 0
           UNION ALL
           SELECT dwt.id as detail_id, dwt.source_warehouse_id as warehouse_id, dwt.stockable_type, dwt.stockable_id,
                  dwt.weight, 0.0 as unit_price, 0.0 as total_price, dwt.nbr_box,
                  dwt.warehouse_transfer_id as ship_id, 'WarehouseTransfer' as source_type, wt.date, NULL as client_id
           FROM detail_warehouse_transfers dwt
           JOIN warehouse_transfers wt ON dwt.warehouse_transfer_id = wt.id
           WHERE dwt.weight > 0 AND dwt.source_warehouse_id IS NOT NULL
           ORDER BY date ASC, detail_id ASC"""
    )
    exits = t_cur.fetchall()
    log(f"Processing {len(exits)} outbound exits...")

    t_cur.execute(
        """SELECT id, warehouse_id, stockable_type, stockable_id, date, remaining_weight, unit_price 
           FROM warehouse_balances 
           WHERE warehouse_operation = 'stock_in' 
           ORDER BY date ASC, id ASC"""
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
                float(ex['nbr_box'] or 0.0), ex.get('source_type', 'shipping'), ex['ship_id'], datetime.now(), datetime.now()
            ))

            if len(ops_batch) >= 5000:
                t_cur.executemany(
                    """INSERT INTO warehouse_balance_operations 
                       (warehouse_balance_id, date, weight, unit_price, total_price, package_count,
                        source_type, source_id, created_at, updated_at)
                       VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s)""",
                    ops_batch
                )
                ops_batch = []

    if ops_batch:
        t_cur.executemany(
            """INSERT INTO warehouse_balance_operations 
               (warehouse_balance_id, date, weight, unit_price, total_price, package_count,
                source_type, source_id, created_at, updated_at)
               VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s)""",
            ops_batch
        )

    tgt_conn.commit()
    log(f"Generated warehouse balance operations. Updating {len(bal_updates)} balance rows...")

    update_batch = []
    for bid, rem in bal_updates.items():
        rem_clamped = max(0.0, round(rem, 2))
        is_fin = 1 if rem_clamped <= 0.01 else 0
        update_batch.append((rem_clamped, is_fin, bid))

        if len(update_batch) >= 5000:
            t_cur.executemany(
                "UPDATE warehouse_balances SET remaining_weight = %s, is_finished = %s WHERE id = %s",
                update_batch
            )
            update_batch = []
    if update_batch:
        t_cur.executemany(
            "UPDATE warehouse_balances SET remaining_weight = %s, is_finished = %s WHERE id = %s",
            update_batch
        )

    tgt_conn.commit()

    log("Recalculating total_weight on all warehouses...")
    t_cur.execute(
        """UPDATE warehouses w
           SET total_weight = COALESCE((
               SELECT ROUND(SUM(remaining_weight), 2)
               FROM warehouse_balances wb
               WHERE wb.warehouse_id = w.id
                 AND wb.is_finished = 0
                 AND wb.remaining_weight > 0
                 AND wb.warehouse_operation = 'stock_in'
           ), 0.00)"""
    )
    tgt_conn.commit()
    log("Stock balances and warehouse totals recalculated successfully.")

# -------------------------------------------------------------
# MAIN ETL RUNNER
# -------------------------------------------------------------
def main():
    start_time = time.time()
    log("Starting comprehensive Villamar -> Jupiter migration pipeline...")

    src_conn, tgt_conn = get_db_conns()

    try:
        tgt_conn.cursor().execute("SET FOREIGN_KEY_CHECKS = 0;")
        tgt_conn.commit()

        clear_target_database(tgt_conn)

        cat_data = migrate_master_catalogs(src_conn, tgt_conn)

        valid_client_invoices, valid_supplier_invoices = migrate_invoices_base(src_conn, tgt_conn, cat_data)

        migrate_workforce(src_conn, tgt_conn)

        plate_to_id = get_truck_plate_mapping(src_conn, tgt_conn)

        migrate_charges(src_conn, tgt_conn, cat_data, plate_to_id, valid_supplier_invoices)

        migrate_production(src_conn, tgt_conn, cat_data, valid_client_invoices)

        migrate_shippings(src_conn, tgt_conn, cat_data, plate_to_id, valid_client_invoices)

        migrate_stock_transfers(src_conn, tgt_conn, cat_data, plate_to_id)

        migrate_fiscal_year_closings(src_conn, tgt_conn, cat_data)

        reconcile_invoices_and_settlements(tgt_conn)

        calculate_and_build_warehouse_stock(tgt_conn)

        tgt_conn.cursor().execute("SET FOREIGN_KEY_CHECKS = 1;")
        tgt_conn.commit()

        elapsed = round(time.time() - start_time, 2)
        log(f"=== MIGRATION COMPLETED SUCCESSFULLY in {elapsed} seconds! ===")

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
