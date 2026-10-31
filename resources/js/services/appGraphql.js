import axios from 'axios';

/**
 * Universal GraphQL Request Handler with Error Classification and Automatic Fallback Support
 */
export async function graphqlRequest(query, variables = {}, operationName = null) {
  try {
    const payload = {
      query,
      variables,
    };
    if (operationName) {
      payload.operationName = operationName;
    }

    const response = await axios.post('/api/graphql', payload);

    if (response.data.errors && response.data.errors.length) {
      const firstError = response.data.errors[0];
      const errorMsg = firstError.message || 'GraphQL Execution Error';
      console.error('[GraphQL Error]:', response.data.errors);
      const err = new Error(errorMsg);
      err.graphQLErrors = response.data.errors;
      throw err;
    }

    return response.data.data;
  } catch (error) {
    console.error('[GraphQL Request Failed]:', error);
    throw error;
  }
}

/**
 * 1. Productions
 */
export async function gqlGetProductions({ date_start = null, date_end = null, per_page = null, page = null, all = false } = {}) {
  const query = `
    query GetProductions($date_start: String, $date_end: String, $per_page: Int, $page: Int, $all: Boolean) {
      productions(date_start: $date_start, date_end: $date_end, per_page: $per_page, page: $page, all: $all) {
        id
        date
        production_category_id
        production_category { id name description }
        reception_tracking_number
        tracking_onp_numbers
        production_tracking_number
        production_type
        fishery_id
        ship_id
        origin_id
        charge_id
        supplier_id
        client_id
        supplier_invoice_id
        client_invoice_id
        supplier { id name }
        client { id name }
        charge { id date reception_tracking_number }
        supplier_invoice { id date tracking_numbers }
        client_invoice { id date tracking_numbers }
        observation
        total_weight
        total_price
        total_secondary_packaging_cost
        detail_productions {
          id
          production_id
          detail_production_category_id
          detail_production_category { id name }
          stockable_id
          stockable_type
          stockable { id name name_fr name_en name_es name_in_invoice type }
          warehouse_id
          warehouse { id name }
          package_type_id
          package_type { id name weight }
          weight
          package_count
          unit_price
          total_price
          temperature
          is_rejected
        }
        created_at
        updated_at
      }
    }
  `;
  const data = await graphqlRequest(query, { date_start, date_end, per_page, page, all });
  return data?.productions || [];
}

/**
 * 1b. Production Analytics
 */
export async function gqlGetProductionAnalytics({ date_start = null, date_end = null, mode = 'daily' } = {}) {
  const query = `
    query GetProductionAnalytics($date_start: String, $date_end: String, $mode: String) {
      productionAnalytics(date_start: $date_start, date_end: $date_end, mode: $mode) {
        fresh_reception { x y }
        frozen_production { x y }
        guano_production { x y }
        equilibrium { x y label }
        yield_series { name color data { x y } }
      }
    }
  `;
  const data = await graphqlRequest(query, { date_start, date_end, mode });
  return data?.productionAnalytics || null;
}

/**
 * 2. Charges
 */
export async function gqlGetCharges({ date_start = null, date_end = null, per_page = null, page = null, all = false } = {}) {
  const query = `
    query GetCharges($date_start: String, $date_end: String, $per_page: Int, $page: Int, $all: Boolean) {
      charges(date_start: $date_start, date_end: $date_end, per_page: $per_page, page: $page, all: $all) {
        id
        date
        charge_category_id
        charge_category { id name name_fr name_en name_es is_production description }
        supplier_id
        supplier { id name phone email balance }
        fishery_id
        fishery { id name name_fr name_en name_es }
        ship_id
        ship { id name name_fr name_en name_es }
        origin_id
        origin { id name name_fr name_en name_es }
        production_id
        invoice_id
        supplier_invoice_id
        supplier_invoice { id date tracking_numbers }
        reception_tracking_number
        tracking_onp_numbers
        trucks { id name name_fr name_en name_es }
        quality_control { id status overall_score }
        observation
        detail_charges {
          id
          charge_id
          detail_charge_category_id
          detail_charge_category { id name name_fr name_en name_es }
          stockable_id
          stockable_type
          stockable { id name name_fr name_en name_es name_in_invoice type }
          warehouse_id
          warehouse { id name }
          package_type_id
          package_type { id name weight }
          weight
          package_count
          unit_price
          total_price
          temperature
          is_rejected
        }
        created_at
        updated_at
      }
    }
  `;
  const data = await graphqlRequest(query, { date_start, date_end, per_page, page, all });
  return data?.charges || [];
}

/**
 * 3. Shippings
 */
export async function gqlGetShippings({ date_start = null, date_end = null, per_page = null, page = null, all = false } = {}) {
  const query = `
    query GetShippings($date_start: String, $date_end: String, $per_page: Int, $page: Int, $all: Boolean) {
      shippings(date_start: $date_start, date_end: $date_end, per_page: $per_page, page: $page, all: $all) {
        id
        date
        shipping_category_id
        shipping_category { id name name_fr name_en name_es description }
        client_id
        client { id name phone email balance }
        origin_id
        origin { id name name_fr name_en name_es }
        invoice_id
        client_invoice_id
        client_invoice { id date tracking_numbers }
        shipping_tracking_number
        tracking_onp_numbers
        trucks { id name name_fr name_en name_es }
        observation
        detail_shippings {
          id
          shipping_id
          detail_shipping_category_id
          detail_shipping_category { id name name_fr name_en name_es }
          stockable_id
          stockable_type
          stockable { id name name_fr name_en name_es name_in_invoice type }
          warehouse_id
          warehouse { id name }
          package_type_id
          package_type { id name weight }
          weight
          package_count
          unit_price
          total_price
          temperature
        }
        created_at
        updated_at
      }
    }
  `;
  const data = await graphqlRequest(query, { date_start, date_end, per_page, page, all });
  return data?.shippings || [];
}

/**
 * 4. Supplier Invoices
 */
export async function gqlGetSupplierInvoices({ date_start = null, date_end = null } = {}) {
  const query = `
    query GetSupplierInvoices($date_start: String, $date_end: String) {
      supplierInvoices(date_start: $date_start, date_end: $date_end) {
        id
        date
        type_invoice
        tracking_numbers
        supplier_id
        supplier { id name phone email }
        total_price
        charges {
          id
          date
          reception_tracking_number
          charge_category { id name name_fr name_en name_es }
          supplier { id name }
          fishery { id name name_fr name_en name_es }
          ship { id name name_fr name_en name_es }
          origin { id name name_fr name_en name_es }
          trucks { id name name_fr name_en name_es }
          detail_charges { weight unit_price total_price }
        }
        productions {
          id
          date
          production_tracking_number
          production_category { id name name_fr name_en name_es }
          supplier { id name }
          total_weight
          total_price
        }
        created_at
        updated_at
      }
    }
  `;
  const data = await graphqlRequest(query, { date_start, date_end });
  return data?.supplierInvoices || [];
}

/**
 * 5. Supplier Settlement (Payments, Balance, Year Summary)
 */
export async function gqlGetSupplierPayments(year) {
  const query = `
    query GetSupplierPayments($year: Int) {
      supplierPayments(year: $year) {
        id
        date
        supplier_id
        supplier { id name }
        supplier_invoice_id
        supplier_invoice { id date tracking_numbers total_price }
        payment_type
        amount
        reference
        bank_name
        due_date
        notes
        created_at
      }
    }
  `;
  const data = await graphqlRequest(query, { year: year ? Number(year) : null });
  return data?.supplierPayments || [];
}

export async function gqlGetSupplierBalance(year) {
  const query = `
    query GetSupplierBalance($year: Int) {
      supplierBalance(year: $year) {
        carry_forward_total
        balance {
          id
          supplier_id
          name
          carry_forward
          months {
            month
            invoices
            payments
            balance
          }
        }
      }
    }
  `;
  const data = await graphqlRequest(query, { year: year ? Number(year) : null });
  const rawBalance = data?.supplierBalance?.balance || [];
  const suppliers = {};
  const formattedBalance = rawBalance.map(row => {
    if (row.id && row.name) {
      suppliers[row.id] = row.name;
    }
    const monthsObj = {};
    (row.months || []).forEach(m => {
      monthsObj[m.month] = {
        invoices: m.invoices,
        payments: m.payments,
        balance: m.balance,
      };
    });
    return {
      supplier_id: row.supplier_id || row.id,
      carry_forward: row.carry_forward,
      months: monthsObj,
    };
  });

  return {
    balance: formattedBalance,
    suppliers,
    carry_forward_total: data?.supplierBalance?.carry_forward_total || 0,
  };
}

export async function gqlGetSupplierYearSummary(year) {
  const query = `
    query GetSupplierYearSummary($year: Int) {
      supplierYearSummary(year: $year) {
        summary {
          id
          supplier_id
          name
          supplier_name
          carry_forward
          total_invoices
          total_payments
          balance
          invoice_count
          payment_count
        }
        closing {
          id
          year
          is_closed
          closed_at
          closed_by
          notes
        }
      }
    }
  `;
  const data = await graphqlRequest(query, { year: year ? Number(year) : null });
  return {
    summary: data?.supplierYearSummary?.summary || [],
    closing: data?.supplierYearSummary?.closing || null,
  };
}

/**
 * 6. Client Invoices
 */
export async function gqlGetClientInvoices({ date_start = null, date_end = null } = {}) {
  const query = `
    query GetClientInvoices($date_start: String, $date_end: String) {
      clientInvoices(date_start: $date_start, date_end: $date_end) {
        id
        date
        type_invoice
        tracking_numbers
        client_id
        client { id name phone email }
        total_price
        incoterm
        currency
        shippings {
          id
          date
          shipping_tracking_number
          shipping_category { id name name_fr name_en name_es }
          client { id name }
          detail_shippings { weight unit_price total_price }
        }
        productions {
          id
          date
          production_tracking_number
          production_category { id name name_fr name_en name_es }
          client { id name }
          total_weight
          total_price
        }
        created_at
        updated_at
      }
    }
  `;
  const data = await graphqlRequest(query, { date_start, date_end });
  return data?.clientInvoices || [];
}

/**
 * 7. Client Settlement (Payments, Balance, Year Summary)
 */
export async function gqlGetClientPayments(year) {
  const query = `
    query GetClientPayments($year: Int) {
      clientPayments(year: $year) {
        id
        date
        client_id
        client { id name }
        client_invoice_id
        client_invoice { id date tracking_numbers total_price }
        payment_type
        amount
        reference
        bank_name
        due_date
        notes
        created_at
      }
    }
  `;
  const data = await graphqlRequest(query, { year: year ? Number(year) : null });
  return data?.clientPayments || [];
}

export async function gqlGetClientBalance(year) {
  const query = `
    query GetClientBalance($year: Int) {
      clientBalance(year: $year) {
        carry_forward_total
        balance {
          id
          client_id
          name
          carry_forward
          months {
            month
            invoices
            payments
            balance
          }
        }
      }
    }
  `;
  const data = await graphqlRequest(query, { year: year ? Number(year) : null });
  const rawBalance = data?.clientBalance?.balance || [];
  const clients = {};
  const formattedBalance = rawBalance.map(row => {
    if (row.id && row.name) {
      clients[row.id] = row.name;
    }
    const monthsObj = {};
    (row.months || []).forEach(m => {
      monthsObj[m.month] = {
        invoices: m.invoices,
        payments: m.payments,
        balance: m.balance,
      };
    });
    return {
      client_id: row.client_id || row.id,
      carry_forward: row.carry_forward,
      months: monthsObj,
    };
  });

  return {
    balance: formattedBalance,
    clients,
    carry_forward_total: data?.clientBalance?.carry_forward_total || 0,
  };
}

export async function gqlGetClientYearSummary(year) {
  const query = `
    query GetClientYearSummary($year: Int) {
      clientYearSummary(year: $year) {
        summary {
          id
          client_id
          name
          client_name
          carry_forward
          total_invoices
          total_payments
          balance
          invoice_count
          payment_count
        }
        closing {
          id
          year
          is_closed
          closed_at
          closed_by
          notes
        }
      }
    }
  `;
  const data = await graphqlRequest(query, { year: year ? Number(year) : null });
  return {
    summary: data?.clientYearSummary?.summary || [],
    closing: data?.clientYearSummary?.closing || null,
  };
}

/**
 * 8. Order Transfers
 */
export async function gqlGetOrderTransfers({ date_start = null, date_end = null } = {}) {
  const query = `
    query GetOrderTransfers($date_start: String, $date_end: String) {
      orderTransfers(date_start: $date_start, date_end: $date_end) {
        id
        date
        transfer_number
        amount
        currency
        motif
        notes
        created_by
        created_by_user { id name email }
        beneficiaries {
          id
          order_transfer_id
          beneficiary_type
          beneficiary_id
          amount
          rib
          bank_name
        }
        beneficiary_names {
          id
          name
          type
          amount
        }
        created_at
        updated_at
      }
    }
  `;
  const data = await graphqlRequest(query, { date_start, date_end });
  return data?.orderTransfers || [];
}

/**
 * 9. Inventories
 */
export async function gqlGetInventories({ date_start = null, date_end = null, per_page = null, page = null, all = false } = {}) {
  const query = `
    query GetInventories($date_start: String, $date_end: String, $per_page: Int, $page: Int, $all: Boolean) {
      inventories(date_start: $date_start, date_end: $date_end, per_page: $per_page, page: $page, all: $all) {
        id
        date
        inventory_category_id
        inventory_tracking_numbers
        observation
        detail_inventories {
          id
          inventory_id
          detail_inventory_category_id
          detail_inventory_category { id name }
          stockable_id
          stockable_type
          stockable { id name name_fr name_en name_es name_in_invoice type }
          warehouse_id
          warehouse { id name }
          package_type_id
          package_type { id name weight }
          weight
          package_count
          unit_price
          total_price
          operation_type
        }
        created_at
        updated_at
      }
    }
  `;
  const data = await graphqlRequest(query, { date_start, date_end, per_page, page, all });
  return data?.inventories || [];
}

/**
 * 10. User Statistics
 */
export async function gqlGetUserStatistics({ date_start = null, date_end = null, user_id = null, module = null } = {}) {
  const query = `
    query GetUserStatistics($date_start: String, $date_end: String, $user_id: Int, $module: String) {
      userStatistics(date_start: $date_start, date_end: $date_end, user_id: $user_id, module: $module) {
        user_stats {
          user_id
          name
          email
          role
          total_actions
          creates
          updates
          deletes
          first_activity
          last_activity
          active_days
          actions_per_active_day
          sparkline
          daily { date count }
          modules { module auditable_type count }
        }
        global_daily { date count }
        hourly { hour count }
        module_breakdown { auditable_type module count }
        recent_activity {
          id
          user_id
          user_name
          user_email
          user_role
          auditable_type
          auditable_id
          action
          created_at
        }
        today_stats {
          total
          active_users
          creates
          updates
          deletes
        }
        period_summary {
          total_actions
          active_users
          creates
          updates
          deletes
        }
        date_range {
          start
          end
        }
      }
    }
  `;
  const data = await graphqlRequest(query, { date_start, date_end, user_id, module });
  return data?.userStatistics || null;
}

/**
 * =========================================================================
 * 10. WORKFORCE MODULE GRAPHQL API
 * Tailored for Workforce Statistics, Absence & Movement, and Fortnight
 * =========================================================================
 */

/**
 * 10A. Workforce Statistics (Multi-Month Aggregation & League Table)
 */
export async function gqlGetWorkforceStatistics({ date_start, date_end, workforce_ids = null, section_ids = null, corporal_ids = null }) {
  const query = `
    query GetWorkforceStatistics($date_start: String!, $date_end: String!, $workforce_ids: [Int], $section_ids: [Int], $corporal_ids: [Int]) {
      workforceStatistics(date_start: $date_start, date_end: $date_end, workforce_ids: $workforce_ids, section_ids: $section_ids, corporal_ids: $corporal_ids) {
        daily {
          date
          total
          count
        }
        sections {
          id
          name
          total
          count
        }
        corporals {
          id
          name
          total
          count
        }
        workforces {
          id
          name
          matricule
          cin
          section
          corporal
          total
          count
          work_days
        }
        grand_total
        total_activities
      }
    }
  `;
  const data = await graphqlRequest(query, { date_start, date_end, workforce_ids, section_ids, corporal_ids });
  return data?.workforceStatistics || null;
}

/**
 * 10B. Workforce Movement Statistics (Timeline & Assignments Tracker)
 */
export async function gqlGetWorkforceMovementStatistics({ start_date = null, end_date = null, granularity = 'daily', workforce_ids = null } = {}) {
  const query = `
    query GetWorkforceMovementStatistics($start_date: String, $end_date: String, $granularity: String, $workforce_ids: [Int]) {
      workforceMovementStatistics(start_date: $start_date, end_date: $end_date, granularity: $granularity, workforce_ids: $workforce_ids) {
        granularity
        start_date
        end_date
        summary {
          total_activities
          total_amount
          distinct_workforces_count
          distinct_corporals_count
          distinct_sections_count
          distinct_teams_count
          all_corporals
          all_sections
          all_teams
        }
        workforce_summaries {
          id
          full_name
          matricule
          cni
          corporals_count
          corporals_list
          sections_count
          sections_list
          teams_count
          teams_list
          work_days_count
          activities_count
          total_amount
        }
        timeline {
          workforce_id
          workforce_name
          matricule
          period_key
          period_label
          corporal_id
          corporal_name
          section_id
          section_name
          team_names
          team_label
          activities_count
          days_count
          total_amount
        }
      }
    }
  `;
  const data = await graphqlRequest(query, { start_date, end_date, granularity, workforce_ids });
  return data?.workforceMovementStatistics || null;
}

/**
 * 10C. Workforce Absences & Movement Management
 */
export async function gqlGetWorkforceAbsences({ threshold = null, reference_date = null, status = null, search = null, team_id = null, corporal_id = null, section_id = null } = {}) {
  const query = `
    query GetWorkforceAbsences($threshold: Int, $reference_date: String, $status: String, $search: String, $team_id: Int, $corporal_id: Int, $section_id: Int) {
      workforceAbsences(threshold: $threshold, reference_date: $reference_date, status: $status, search: $search, team_id: $team_id, corporal_id: $corporal_id, section_id: $section_id) {
        threshold_days
        reference_date
        summary {
          total_workforces
          active_count
          absent_count
          left_company_count
          total_absence_incidents
          total_absence_days
        }
        workforces {
          id
          full_name
          matricule
          cni
          sex
          nationality
          status
          db_status
          is_absent_by_threshold
          last_activity_date
          days_since_last_activity
          departure_date
          departure_reason
          return_date
          previous_team_ids
          teams {
            id
            name
            corporal_id
          }
          absence_count
          total_absence_days
          latest_motif
          latest_movement {
            id
            type
            start_date
            end_date
            days_count
            reason
            notes
            restored_teams
          }
        }
      }
    }
  `;
  const data = await graphqlRequest(query, { threshold, reference_date, status, search, team_id, corporal_id, section_id });
  return data?.workforceAbsences || null;
}

/**
 * 10D. Workforce Movement History (Worker Audit Trail)
 */
export async function gqlGetWorkforceMovementHistory(workforce_id) {
  const query = `
    query GetWorkforceMovementHistory($workforce_id: ID!) {
      workforceMovementHistory(workforce_id: $workforce_id) {
        workforce {
          id
          full_name
          matricule
          status
          teams {
            id
            name
            corporal_id
          }
        }
        movements {
          id
          type
          start_date
          end_date
          days_count
          reason
          notes
          restored_teams
          previous_team_names
          created_by_name
          created_at
        }
      }
    }
  `;
  const data = await graphqlRequest(query, { workforce_id });
  return data?.workforceMovementHistory || null;
}

/**
 * 10E. Absence Settings (Threshold)
 */
export async function gqlGetWorkforceAbsenceSettings() {
  const query = `
    query GetWorkforceAbsenceSettings {
      workforceAbsenceSettings {
        threshold_days
        message
      }
    }
  `;
  const data = await graphqlRequest(query);
  return data?.workforceAbsenceSettings || null;
}

export async function gqlSaveWorkforceAbsenceSetting(threshold_days) {
  const query = `
    mutation SaveWorkforceAbsenceSetting($threshold_days: Int!) {
      saveWorkforceAbsenceSetting(threshold_days: $threshold_days) {
        threshold_days
        message
      }
    }
  `;
  const data = await graphqlRequest(query, { threshold_days });
  return data?.saveWorkforceAbsenceSetting || null;
}

/**
 * 10F. Absence Lifecycle Mutations
 */
export async function gqlRecordWorkforceMotif({ workforce_id, motif_date = null, reason, notes = null, start_date = null, end_date = null, days_count = null, is_justified = false }) {
  const query = `
    mutation RecordWorkforceMotif($workforce_id: ID!, $motif_date: String, $reason: String!, $notes: String, $start_date: String, $end_date: String, $days_count: Int, $is_justified: Boolean) {
      recordWorkforceMotif(workforce_id: $workforce_id, motif_date: $motif_date, reason: $reason, notes: $notes, start_date: $start_date, end_date: $end_date, days_count: $days_count, is_justified: $is_justified) {
        message
        movement {
          id
          type
          start_date
          end_date
          days_count
          reason
          notes
          restored_teams
        }
        workforce {
          id
          full_name
          status
        }
      }
    }
  `;
  const data = await graphqlRequest(query, { workforce_id, motif_date, reason, notes, start_date, end_date, days_count, is_justified });
  return data?.recordWorkforceMotif || null;
}

export async function gqlWorkforceLeaveCompany({ workforce_id, departure_date, departure_reason = null, notes = null }) {
  const query = `
    mutation WorkforceLeaveCompany($workforce_id: ID!, $departure_date: String!, $departure_reason: String, $notes: String) {
      workforceLeaveCompany(workforce_id: $workforce_id, departure_date: $departure_date, departure_reason: $departure_reason, notes: $notes) {
        message
        movement {
          id
          type
          start_date
          reason
          notes
        }
        workforce {
          id
          full_name
          status
          departure_date
        }
      }
    }
  `;
  const data = await graphqlRequest(query, { workforce_id, departure_date, departure_reason, notes });
  return data?.workforceLeaveCompany || null;
}

export async function gqlWorkforceReturnToWork({ workforce_id, return_date, restore_teams = true, notes = null }) {
  const query = `
    mutation WorkforceReturnToWork($workforce_id: ID!, $return_date: String!, $restore_teams: Boolean, $notes: String) {
      workforceReturnToWork(workforce_id: $workforce_id, return_date: $return_date, restore_teams: $restore_teams, notes: $notes) {
        message
        movement {
          id
          type
          start_date
          reason
          notes
          restored_teams
        }
        workforce {
          id
          full_name
          status
          return_date
        }
      }
    }
  `;
  const data = await graphqlRequest(query, { workforce_id, return_date, restore_teams, notes });
  return data?.workforceReturnToWork || null;
}

/**
 * 10G. Fortnight Summary (Q1 / Q2 / Month Totals)
 */
export async function gqlGetFortnightSummary({ year, month, workforce_ids = null, workforce_id = null }) {
  const query = `
    query GetFortnightSummary($year: Int!, $month: Int!, $workforce_ids: [Int], $workforce_id: Int) {
      fortnightSummary(year: $year, month: $month, workforce_ids: $workforce_ids, workforce_id: $workforce_id) {
        first_fortnight {
          total
          fixed_total
          non_fixed_total
          working_days
        }
        second_fortnight {
          total
          fixed_total
          non_fixed_total
          working_days
        }
        month_total
        month_fixed_total
        month_non_fixed_total
      }
    }
  `;
  const data = await graphqlRequest(query, { year, month, workforce_ids, workforce_id });
  return data?.fortnightSummary || null;
}

/**
 * 10H. Fortnight Daily Totals (Overview Calendar Mode)
 */
export async function gqlGetFortnightDailyTotals({ year, month }) {
  const query = `
    query GetFortnightDailyTotals($year: Int!, $month: Int!) {
      fortnightDailyTotals(year: $year, month: $month) {
        date
        total_price
        count
      }
    }
  `;
  const data = await graphqlRequest(query, { year, month });
  return data?.fortnightDailyTotals || [];
}

/**
 * 10I. Fortnight Activities List
 */
export async function gqlGetFortnightActivities({ year = null, month = null, workforce_ids = null, workforce_id = null, fortnight_period = null, date = null } = {}) {
  const query = `
    query GetFortnightActivities($year: Int, $month: Int, $workforce_ids: [Int], $workforce_id: Int, $fortnight_period: String, $date: String) {
      fortnightActivities(year: $year, month: $month, workforce_ids: $workforce_ids, workforce_id: $workforce_id, fortnight_period: $fortnight_period, date: $date) {
        id
        workforce_id
        date
        fortnight_period
        section_id
        corporal_id
        price
        is_fixed_price
        workforce {
          id
          full_name
          matricule
        }
        section {
          id
          name
        }
        corporal {
          id
          full_name
        }
        created_at
        updated_at
      }
    }
  `;
  const data = await graphqlRequest(query, { year, month, workforce_ids, workforce_id, fortnight_period, date });
  return data?.fortnightActivities || [];
}

/**
 * 10J. Fortnight Activities By Date (Day Activities Popup)
 */
export async function gqlGetFortnightActivitiesByDate(date) {
  const query = `
    query GetFortnightActivitiesByDate($date: String!) {
      fortnightActivitiesByDate(date: $date) {
        id
        workforce_id
        date
        fortnight_period
        section_id
        corporal_id
        price
        is_fixed_price
        workforce {
          id
          full_name
          matricule
        }
        section {
          id
          name
        }
        corporal {
          id
          full_name
        }
        created_at
        updated_at
      }
    }
  `;
  const data = await graphqlRequest(query, { date });
  return data?.fortnightActivitiesByDate || [];
}

/**
 * 10K. Fortnight Multi-Dimensional Breakdown (Sections / Corporals / Workforces)
 */
export async function gqlGetFortnightBreakdown({ year, month, workforce_ids = null }) {
  const query = `
    query GetFortnightBreakdown($year: Int!, $month: Int!, $workforce_ids: [Int]) {
      fortnightBreakdown(year: $year, month: $month, workforce_ids: $workforce_ids) {
        sections {
          id
          name
          q1_count
          q1_total
          q1_fixed_total
          q1_non_fixed_total
          q2_count
          q2_total
          q2_fixed_total
          q2_non_fixed_total
          month_total
          month_fixed_total
          month_non_fixed_total
          month_count
          workforces {
            id
            name
            matricule
            cin
            cnss_id
            nationality
            sex
            is_migrant
            salary_statement
            fixed_daily_pay
            q1_count
            q1_total
            q1_fixed_total
            q1_non_fixed_total
            q2_count
            q2_total
            q2_fixed_total
            q2_non_fixed_total
            month_total
            month_fixed_total
            month_non_fixed_total
            month_count
          }
        }
        corporals {
          id
          name
          q1_count
          q1_total
          q1_fixed_total
          q1_non_fixed_total
          q2_count
          q2_total
          q2_fixed_total
          q2_non_fixed_total
          month_total
          month_fixed_total
          month_non_fixed_total
          month_count
          workforces {
            id
            name
            matricule
            cin
            cnss_id
            nationality
            sex
            is_migrant
            salary_statement
            fixed_daily_pay
            q1_count
            q1_total
            q1_fixed_total
            q1_non_fixed_total
            q2_count
            q2_total
            q2_fixed_total
            q2_non_fixed_total
            month_total
            month_fixed_total
            month_non_fixed_total
            month_count
          }
        }
        workforces {
          id
          name
          matricule
          nationality
          sex
          is_migrant
          salary_statement
          cnss_id
          cin
          fixed_daily_pay
          daily_price
          q1_count
          q1_total
          q1_fixed_total
          q1_non_fixed_total
          q2_count
          q2_total
          q2_fixed_total
          q2_non_fixed_total
          month_total
          month_fixed_total
          month_non_fixed_total
          month_count
          is_paid_q1
          is_paid_q2
          q1_work_days
          q2_work_days
          q1_non_work_days
          q2_non_work_days
          activities {
            id
            date
            section_name
            corporal_name
            price
            is_fixed_price
          }
        }
      }
    }
  `;
  const data = await graphqlRequest(query, { year, month, workforce_ids });
  return data?.fortnightBreakdown || null;
}

/**
 * 10L. Fortnight Activity CRUD Mutations
 */
export async function gqlCreateFortnightActivity({ workforce_id, date, price, section_id = null, corporal_id = null, is_fixed_price = false, fortnight_period = null }) {
  const query = `
    mutation CreateFortnightActivity($workforce_id: Int!, $date: String!, $price: Float!, $section_id: Int, $corporal_id: Int, $is_fixed_price: Boolean, $fortnight_period: String) {
      createFortnightActivity(workforce_id: $workforce_id, date: $date, price: $price, section_id: $section_id, corporal_id: $corporal_id, is_fixed_price: $is_fixed_price, fortnight_period: $fortnight_period) {
        message
        activity {
          id
          workforce_id
          date
          fortnight_period
          section_id
          corporal_id
          price
          is_fixed_price
          workforce { id full_name matricule }
          section { id name }
          corporal { id full_name }
        }
      }
    }
  `;
  const data = await graphqlRequest(query, { workforce_id, date, price, section_id, corporal_id, is_fixed_price, fortnight_period });
  return data?.createFortnightActivity?.activity || null;
}

export async function gqlUpdateFortnightActivity({ id, workforce_id = null, date = null, price = null, section_id = null, corporal_id = null, is_fixed_price = null, fortnight_period = null }) {
  const query = `
    mutation UpdateFortnightActivity($id: ID!, $workforce_id: Int, $date: String, $price: Float, $section_id: Int, $corporal_id: Int, $is_fixed_price: Boolean, $fortnight_period: String) {
      updateFortnightActivity(id: $id, workforce_id: $workforce_id, date: $date, price: $price, section_id: $section_id, corporal_id: $corporal_id, is_fixed_price: $is_fixed_price, fortnight_period: $fortnight_period) {
        message
        activity {
          id
          workforce_id
          date
          fortnight_period
          section_id
          corporal_id
          price
          is_fixed_price
          workforce { id full_name matricule }
          section { id name }
          corporal { id full_name }
        }
      }
    }
  `;
  const data = await graphqlRequest(query, { id, workforce_id, date, price, section_id, corporal_id, is_fixed_price, fortnight_period });
  return data?.updateFortnightActivity?.activity || null;
}

export async function gqlDeleteFortnightActivity(id) {
  const query = `
    mutation DeleteFortnightActivity($id: ID!) {
      deleteFortnightActivity(id: $id)
    }
  `;
  const data = await graphqlRequest(query, { id });
  return data?.deleteFortnightActivity || false;
}

export async function gqlBulkDeleteFortnightActivities({ date = null, dates = null, workforce_ids = null } = {}) {
  const query = `
    mutation BulkDeleteFortnightActivities($date: String, $dates: [String], $workforce_ids: [Int]) {
      bulkDeleteFortnightActivities(date: $date, dates: $dates, workforce_ids: $workforce_ids) {
        deleted_count
      }
    }
  `;
  const data = await graphqlRequest(query, { date, dates, workforce_ids });
  return data?.bulkDeleteFortnightActivities?.deleted_count || 0;
}

export async function gqlToggleWorkforcePayment({ workforce_id, year, month, fortnight_period, is_paid }) {
  const query = `
    mutation ToggleWorkforcePayment($workforce_id: Int!, $year: Int!, $month: Int!, $fortnight_period: String!, $is_paid: Boolean!) {
      toggleWorkforcePayment(workforce_id: $workforce_id, year: $year, month: $month, fortnight_period: $fortnight_period, is_paid: $is_paid) {
        message
        payment {
          id
          workforce_id
          year
          month
          fortnight_period
          is_paid
        }
      }
    }
  `;
  const data = await graphqlRequest(query, { workforce_id, year, month, fortnight_period, is_paid });
  return data?.toggleWorkforcePayment || null;
}

export async function gqlBulkToggleWorkforcePayments({ workforce_ids, year, month, fortnight_period, is_paid }) {
  const query = `
    mutation BulkToggleWorkforcePayments($workforce_ids: [Int]!, $year: Int!, $month: Int!, $fortnight_period: String!, $is_paid: Boolean!) {
      bulkToggleWorkforcePayments(workforce_ids: $workforce_ids, year: $year, month: $month, fortnight_period: $fortnight_period, is_paid: $is_paid) {
        message
        count
      }
    }
  `;
  const data = await graphqlRequest(query, { workforce_ids, year, month, fortnight_period, is_paid });
  return data?.bulkToggleWorkforcePayments || null;
}


