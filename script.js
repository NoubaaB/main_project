const fs = require('fs');
const file_path = 'c:\\xampp\\htdocs\\main_project\\resources\\js\\components\\ClientInvoice\\ViewClientInvoice\\DisplayClientInvoice.vue';
let content = fs.readFileSync(file_path, 'utf8');

// Fix isExport and totals
content = content.replace(/isExport\(\) \{[\s\S]*?\}/, isExport() { return !!this.client_invoice_detail?.invoice; });
content = content.replace(/this\.client_invoice_detail\?\.export_details/g, 'this.client_invoice_detail?.invoice?.export_details');
content = content.replace(/this\.client_invoice_detail\?\.tax_percentage/g, 'this.client_invoice_detail?.invoice?.tax_percentage');

// Fix references in template
content = content.replace(/client_invoice_detail\.incoterm/g, 'client_invoice_detail.invoice?.incoterm');
content = content.replace(/client_invoice_detail\.currency/g, 'client_invoice_detail.invoice?.currency');
content = content.replace(/client_invoice_detail\.total_gross_weight/g, 'client_invoice_detail.invoice?.total_gross_weight');
content = content.replace(/client_invoice_detail\.export_details/g, 'client_invoice_detail.invoice?.export_details');

fs.writeFileSync(file_path, content, 'utf8');
console.log("Done");
