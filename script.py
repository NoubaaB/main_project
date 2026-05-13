import re

file_path = r'c:\xampp\htdocs\main_project\resources\js\components\ClientInvoice\ViewClientInvoice\DisplayClientInvoice.vue'

with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Add computed properties
computed_target = r"            // displayedHeaders: respects visibility and columnOrder (uses this.clientInvoice.getHeaders)"
computed_replacement = r"""            isExport() {
                return this.client_invoice_detail?.type_invoice === 'export';
            },
            exportSubTotal() {
                return (this.client_invoice_detail?.export_details || []).reduce((s, l) => s + Number(l.total_price || 0), 0);
            },
            exportGrandTotal() {
                const sub = this.exportSubTotal;
                const tax = Number(this.client_invoice_detail?.tax_percentage || 0);
                return Number((sub + sub * (tax / 100)).toFixed(2));
            },
            // displayedHeaders: respects visibility and columnOrder (uses this.clientInvoice.getHeaders)"""
content = content.replace(computed_target, computed_replacement)

# 2. Add Export CSS
css_target = r""".rounded-bs-lg[data-v-043dbba6] {
    border-bottom-left-radius: 8px !important;
}
</style>"""
css_replacement = r""".rounded-bs-lg[data-v-043dbba6] {
    border-bottom-left-radius: 8px !important;
}

/* --- Export Invoice Custom Styles --- */
.exp-header {
    height: 100px;
    position: relative;
    overflow: hidden;
}
.exp-header-curve {
    width: 250px;
    height: 100px;
    position: absolute;
    left: 45%;
    transform: skewX(-40deg);
    z-index: 1;
}
.exp-meta-box {
    border: 1px solid #B2EBF2;
}
.exp-party-box {
    height: 100%;
}
.exp-table {
    width: 100%;
    border-collapse: collapse;
}
.exp-table th {
    padding: 6px 12px;
    font-size: 13px;
    font-weight: 800;
    text-align: center;
    border-bottom: 2px solid #80DEEA;
}
.exp-table td {
    padding: 8px 12px;
    font-size: 12px;
    border-bottom: 1px solid #E0F7FA;
}
.exp-checkbox {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 16px;
    height: 16px;
    border: 1.5px solid #00838F;
    border-radius: 2px;
    background: white;
}
.exp-checkbox.checked {
    border-color: #00838F;
}
.exp-footer-curve {
    position: absolute;
    top: 0;
    left: -50px;
    width: 150px;
    height: 100%;
    transform: skewX(-30deg);
    z-index: 1;
}
.export-invoice-wrap {
    position: relative;
    z-index: 1;
}
.export-watermark {
    position: absolute;
    top: 40%;
    left: 50%;
    transform: translate(-50%, -50%);
    opacity: 0.05;
    z-index: 0;
    pointer-events: none;
    width: 60%;
}
</style>"""
content = content.replace(css_target, css_replacement)

# 3. Replace the template wrapper
template_start = r'    :class="{\'border-dashed border-md border-cyan-darken-3\':is_show}"\n    >'
template_end = r'        </div>\n    </div>\n</template>'

start_idx = content.find(template_start) + len(template_start)
end_idx = content.find(template_end)

if start_idx != -1 and end_idx != -1:
    old_content = content[start_idx:end_idx]
    
    new_template = f'''
        <img class="export-watermark" :src="\'/storage/images/invoice_title.png\'" v-if="isExport" />
        <template v-if="!isExport">
            {{old_content}}
        </template>
        <template v-else>
             <!-- New Header -->
             <div class="exp-header d-flex bg-cyan-lighten-4">
                 <div class="bg-cyan-lighten-4 d-flex align-center pl-5" style="width: 50%; z-index: 2;">
                     <img :src="\'/storage/images/invoice_title.png\'" style="height: 60px;" />
                 </div>
                 <div class="exp-header-curve bg-cyan-darken-4"></div>
                 <div class="bg-white d-flex align-center justify-end pr-10" style="width: 50%; z-index: 0;">
                     <h1 class="text-cyan-lighten-3" style="font-size: 40px; font-weight: 900; letter-spacing: 2px;">INVOICE</h1>
                 </div>
             </div>
             
             <!-- Middle Bar (Invoice N + Date) -->
             <div class="exp-middle-bar d-flex align-center justify-space-between mt-4 px-10" style="position: relative; z-index: 2;">
                  <div class="d-flex ga-2 text-cyan-lighten-2">
                       <v-icon size="18" v-for="i in 7" :key="i">mdi-play-outline</v-icon>
                  </div>
                  <div class="exp-meta-box bg-cyan-lighten-4 px-4 py-2 rounded-lg d-flex align-center ga-4 text-black font-weight-bold" style="font-size: 14px;">
                       <span>{{{{ ('invoice_n°') }}}} : {{{{ client_invoice_detail.tracking_numbers }}}}</span>
                       <v-divider vertical class="border-opacity-100" color="black" thickness="2" style="height: 16px;"></v-divider>
                       <span>{{{{ ('date') }}}} : {{{{ main.formateDate(client_invoice_detail.date) }}}}</span>
                  </div>
             </div>

             <!-- Parties Blocks -->
             <v-row class="px-5 mt-4" no-gutters style="position: relative; z-index: 2;">
                 <v-col cols="6" class="pr-2">
                     <div class="exp-party-box">
                         <div class="exp-party-title bg-cyan-lighten-3 text-black text-center py-2 font-weight-bold rounded-t-lg" style="border: 2px solid #80DEEA;">
                             {{{{ ('shipper_exporter') }}}}
                         </div>
                         <div class="exp-party-content text-center py-4 font-weight-bold" style="font-size: 13px; border: 2px solid #80DEEA; border-top: none; min-height: 120px; color: #1a1a1a;">
                              <div class="mb-2">{{{{ ('company') }}}} : {{{{ main.company?.name || 'Pescacis' }}}}</div>
                              <div class="mb-2">{{{{ ('address') }}}} : {{{{ main.company?.address || 'ZI ESSALAM DAKHLA' }}}}</div>
                              <div class="mb-2">{{{{ ('country') }}}} : {{{{ client_invoice_detail.shipment_country || 'MOROCCO' }}}}</div>
                              <div>{{{{ ('shipment_country') }}}} : {{{{ client_invoice_detail.shipment_country || 'DAKHLA -MOROCCO' }}}}</div>
                         </div>
                     </div>
                 </v-col>
                 <v-col cols="6" class="pl-2" style="border-left: 2px solid #006064;">
                     <div class="exp-party-box pl-3">
                         <div class="exp-party-title bg-cyan-darken-4 text-white text-center py-2 font-weight-bold rounded-t-lg" style="border: 2px solid #006064;">
                             {{{{ ('recipient_importer') }}}}
                         </div>
                         <div class="exp-party-content text-center py-4 font-weight-bold" style="font-size: 13px; border: 2px solid #006064; border-top: none; min-height: 120px; color: #1a1a1a;">
                              <div class="mb-2">{{{{ ('company') }}}} : {{{{ client_invoice_detail.destination_company || client_invoice_detail.client_name }}}}</div>
                              <div class="mb-2">{{{{ ('address') }}}} : {{{{ client_invoice_detail.destination_address || client_invoice_detail.address }}}}</div>
                              <div class="mb-2">{{{{ ('country') }}}} : {{{{ client_invoice_detail.destination_country || '' }}}}</div>
                              <div v-if="client_invoice_detail.intermediary_company">{{{{ ('intermediary') }}}} : {{{{ client_invoice_detail.intermediary_company }}}}, {{{{ client_invoice_detail.intermediary_country }}}}</div>
                              <div v-else>{{{{ ('importer_country') }}}} : {{{{ client_invoice_detail.destination_country || '' }}}}</div>
                         </div>
                     </div>
                 </v-col>
             </v-row>

             <!-- Table 1: Containers/Trucks -->
             <div class="px-5 mt-6" style="position: relative; z-index: 2;" v-if="(client_invoice_detail.export_details||[]).length">
                  <table class="exp-table">
                       <thead class="bg-cyan-lighten-3 text-black">
                           <tr>
                               <th>Producer</th>
                               <th>Country Code</th>
                               <th>Ship Via</th>
                               <th>Qté Unitaire (Kg)</th>
                           </tr>
                       </thead>
                       <tbody>
                           <tr v-for="(line, li) in client_invoice_detail.export_details" :key="'t1-'+li">
                               <td class="font-weight-bold text-center">VILLAMAR CONGELATION</td>
                               <td class="font-weight-bold text-center">Euro (€)</td>
                               <td class="font-weight-bold text-center">{{{{ line.container_or_truck_id }}}}</td>
                               <td class="font-weight-bold text-right">{{{{ formatNumber(line.weight_mt) }}}}</td>
                           </tr>
                       </tbody>
                  </table>
             </div>

             <!-- Table 2: Details -->
             <div class="px-5 mt-4" style="position: relative; z-index: 2;" v-if="(client_invoice_detail.export_details||[]).length">
                  <table class="exp-table">
                       <thead class="bg-cyan-lighten-3 text-black">
                           <tr>
                               <th>Units</th>
                               <th>Description</th>
                               <th>Quantity (Kg)</th>
                               <th>Unit Price ({{{{ client_invoice_detail.currency || '€' }}}})</th>
                               <th>Total ({{{{ client_invoice_detail.currency || '€' }}}})</th>
                           </tr>
                       </thead>
                       <tbody>
                           <tr v-for="(line, li) in client_invoice_detail.export_details" :key="'t2-'+li">
                               <td class="font-weight-bold text-center">1</td>
                               <td class="font-weight-bold text-center">{{{{ line.description }}}}</td>
                               <td class="font-weight-bold text-center">{{{{ formatNumber(line.weight_mt) }}}}</td>
                               <td class="font-weight-bold text-center">{{{{ formatNumber(line.unit_price_per_ton) }}}}</td>
                               <td class="font-weight-bold text-center">{{{{ formatNumber(line.total_price) }}}}</td>
                           </tr>
                       </tbody>
                  </table>
             </div>

             <!-- Summary & Footer -->
             <div class="exp-bottom mt-8 px-5" v-if="allow_summary" style="position: relative; z-index: 2; padding-bottom: 20px;">
                  <!-- Incoterm Checkbox -->
                  <div class="exp-incoterm bg-cyan-lighten-4 rounded-lg px-2 py-1" style="width: 150px; float: left; border: 1px solid #80DEEA;">
                       <div class="text-center font-weight-bold mb-1" style="font-size: 12px; border-bottom: 1px solid #80DEEA;">Check ONE</div>
                       <div class="d-flex align-center ga-2 mb-1" style="font-size: 13px;" v-for="t in ['FOB', 'C&F', 'CIF']" :key="t">
                            <span class="exp-checkbox" :class="{{'checked': client_invoice_detail.incoterm === t}}">
                                 <v-icon size="14" v-if="client_invoice_detail.incoterm === t" color="blue">mdi-check</v-icon>
                            </span>
                            <span class="text-grey-darken-2 font-weight-bold">{{{{ t }}}}</span>
                       </div>
                  </div>

                  <!-- Amount in words -->
                  <div class="exp-words text-center" style="margin-left: 170px; margin-right: 250px; padding-top: 10px;">
                       <div style="font-size: 13px;">Arrêtée la present facture à la somme :</div>
                       <div class="font-weight-bold mt-2 text-cyan-darken-4" style="font-size: 14px;">{{{{ convertSumPriceToWord }}}}</div>
                  </div>

                  <!-- Totals Box -->
                  <div class="exp-totals bg-white" style="width: 240px; float: right; margin-top: -60px; font-size: 13px;">
                       <table style="width: 100%;">
                           <tr>
                               <td class="py-1 text-grey-darken-3">Sous-Total En {{{{ client_invoice_detail.currency || 'Euro' }}}} :</td>
                               <td class="text-right py-1 font-weight-bold">{{{{ formatNumber(exportSubTotal) }}}} {{{{ client_invoice_detail.currency || '€' }}}}</td>
                           </tr>
                           <tr>
                               <td class="py-1 text-grey-darken-3">Tax :</td>
                               <td class="text-right py-1">{{{{ client_invoice_detail.tax_percentage || 0 }}}}%</td>
                           </tr>
                           <tr>
                               <td class="py-2 font-weight-bold text-black" style="border-top: 1px solid #E0E0E0;">Total En {{{{ client_invoice_detail.currency || 'Euro' }}}} :</td>
                               <td class="text-right py-2 font-weight-bold text-black" style="border-top: 1px solid #E0E0E0;">{{{{ formatNumber(exportGrandTotal) }}}} {{{{ client_invoice_detail.currency || '€' }}}}</td>
                           </tr>
                           <tr>
                               <td class="py-1 font-weight-bold text-black">Total En Kg:</td>
                               <td class="text-right py-1 font-weight-bold text-black">{{{{ formatNumber(client_invoice_detail.total_gross_weight) }}}} Kg</td>
                           </tr>
                       </table>
                  </div>
                  <div style="clear: both;"></div>

                  <!-- Very Bottom Info -->
                  <div class="exp-footer d-flex justify-space-between align-end mt-16" style="margin-bottom: 20px;">
                       <div class="text-center" style="width: 250px;">
                            <div class="text-blue-darken-3 font-weight-bold mb-1" style="font-size: 20px;">Mohammed</div>
                            <div class="text-blue-lighten-2 mb-1" style="font-size: 14px;">Zlouzlou</div>
                            <v-divider color="blue-darken-2" thickness="2" class="mx-8 mb-1" style="opacity: 1;"></v-divider>
                            <div class="text-blue-lighten-2" style="font-size: 14px;">Le Directeur</div>
                       </div>
                       
                       <div class="exp-footer-bg bg-cyan-lighten-2 rounded-ts-xl d-flex flex-column align-end justify-center pr-5 pb-3 pt-4" style="width: 350px; position: relative;">
                            <div class="exp-footer-curve bg-cyan-darken-4"></div>
                            <div class="text-right text-black font-weight-bold" style="font-size: 11px; z-index: 2; line-height: 1.6;">
                                 <div>Address : ZI ESSALAM DAKHLA</div>
                                 <div>BANQUE POPULAIRE N°:</div>
                                 <div>143 53021211 7175902 0000 44</div>
                                 <div>Phone. : +212 6 68 58 55 83</div>
                                 <div>Code Swift : BCMAMAMC</div>
                            </div>
                       </div>
                  </div>
             </div>
        </template>
'''
    content = content[:start_idx] + new_template + content[end_idx:]

with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("Done")
