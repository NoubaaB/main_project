import axios from 'axios';
import { useMain } from '@/stores/Main';
import { useAuth } from '@/stores/Auth';
import mitt from '@/plugins/mitt.js';
import * as XLSX from 'xlsx';

const C='#6D4C41';
export default {
  name:'ListInventory',
  data(){
    const n=new Date(),sm=new Date(n.getFullYear(),n.getMonth(),1);
    return {
      inventories:[],loading:false,selected:[],
      dateRange:[sm,n],
      colFilters:{},colSorts:[],pinState:{},
      expanded:{},
      deleteDialog:false,deleting:false,deleteId:null,deleteBulk:false,
      filterMenus:{},
      columnOrder:null,
      dragCol:null,dragOverCol:null,
      colWidths:{},
      chartMode:'daily',
    };
  },
  computed:{
    main(){return useMain()},
    auth(){return useAuth()},
    lang(){return this.main.lang?.code||'en'},
    today(){return new Date().toISOString().slice(0,10)},
    dateRangeVC:{get(){return{start:this.dateRange[0],end:this.dateRange[1]||this.dateRange[0]}},set(v){if(v&&v.start&&v.end)this.dateRange=[v.start,v.end]}},
    detailCats(){return this.auth.detail_inventory_category.list},
    allHeaders(){return[
      {title:'',key:'expand',sortable:false,width:40,fixed:true},
      {title:this.$t('date'),key:'date',align:'center'},
      {title:this.$t('inventory_tracking_number'),key:'_tracking',align:'center'},
      {title:this.$t('description'),key:'description',align:'center'},
      {title:this.$t('total_weight'),key:'total_weight',align:'center'},
      {title:this.$t('total_price'),key:'total_price',align:'center'},
      {title:this.$t('details'),key:'_details_count',align:'center'},
      {title:this.$t('actions'),key:'actions',align:'center',sortable:false,fixed:true},
    ]},
    headers(){
      if(!this.columnOrder)return this.allHeaders;
      const map=Object.fromEntries(this.allHeaders.map(h=>[h.key,h]));
      const fixed=this.allHeaders.filter(h=>h.fixed);
      const ordered=this.columnOrder.filter(k=>map[k]&&!map[k].fixed).map(k=>map[k]);
      return [fixed[0],...ordered.filter(h=>this.pinState[h.key]==='left'),...ordered.filter(h=>!this.pinState[h.key]),...ordered.filter(h=>this.pinState[h.key]==='right'),fixed[fixed.length-1]];
    },
    colFilteredItems(){
      let items=this.inventories;
      Object.entries(this.colFilters).forEach(([k,vals])=>{
        if(!vals||!vals.length)return;
        items=items.filter(it=>vals.includes(this.cellText(k,it)));
      });
      return this.sortItems(items);
    },
    totalWeight(){return this.colFilteredItems.reduce((s,c)=>s+Number(c.total_weight||0),0)},
    totalPrice(){return this.colFilteredItems.reduce((s,c)=>s+Number(c.total_price||0),0)},
    categoryStats(){
      const map={};
      this.colFilteredItems.forEach(inv=>{
        (inv.detail_inventories||[]).forEach(di=>{
          const catId=di.detail_inventory_category_id;
          if(!map[catId]){
            const cat=this.detailCats.find(x=>x.id===catId);
            map[catId]={name:cat?(cat[`name_${this.lang}`]||cat.name_fr||cat.name_en||`#${catId}`):`#${catId}`,weight:0,price:0,count:0};
          }
          map[catId].weight+=Number(di.weight||0);
          map[catId].price+=Number(di.total_price||0);
          map[catId].count++;
        });
      });
      return Object.values(map);
    },
    chartSeries(){
      const items=this.colFilteredItems;
      const buckets={};
      items.forEach(c=>{
        const d=new Date(c.date);
        const dateStr=typeof c.date==='string'?c.date.slice(0,10):d.toISOString().slice(0,10);
        let key;
        if(this.chartMode==='daily')key=dateStr;
        else if(this.chartMode==='monthly')key=d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0');
        else key=String(d.getFullYear());
        if(!buckets[key])buckets[key]={w:0,p:0};
        buckets[key].w+=Number(c.total_weight||0);
        buckets[key].p+=Number(c.total_price||0);
      });
      const sorted=Object.keys(buckets).sort();
      return[{name:this.$t('total_weight')+' (Kg)',type:'area',data:sorted.map(k=>({x:k,y:Math.round(buckets[k].w*100)/100}))},{name:this.$t('total_price')+' ('+this.main.currency+')',type:'line',data:sorted.map(k=>({x:k,y:Math.round(buckets[k].p*100)/100}))}];
    },
    chartOptions(){
      return{
        chart:{type:'line',height:260,toolbar:{show:false},fontFamily:'Inter, sans-serif',background:'transparent',animations:{enabled:true,easing:'easeinout',speed:600}},
        stroke:{width:[2,3],curve:'smooth',dashArray:[0,0]},
        fill:{type:['gradient','solid'],gradient:{shadeIntensity:1,opacityFrom:.4,opacityTo:.05,stops:[0,90,100]}},
        colors:[C,'#FF7043'],
        xaxis:{type:'category',labels:{style:{fontSize:'10px',fontWeight:600},rotate:-45,rotateAlways:this.chartMode==='daily'},tooltip:{enabled:false}},
        yaxis:[{title:{text:this.$t('total_weight')+' (Kg)',style:{fontSize:'11px',fontWeight:700,color:C}},labels:{style:{fontSize:'10px'},formatter:v=>this.main.formatDisplayNumber(v)}},{opposite:true,title:{text:this.$t('total_price')+' ('+this.main.currency+')',style:{fontSize:'11px',fontWeight:700,color:'#FF7043'}},labels:{style:{fontSize:'10px'},formatter:v=>this.main.formatDisplayNumber(v)}}],
        tooltip:{shared:true,intersect:false,y:{formatter:v=>this.main.formatDisplayNumber(v)}},
        grid:{borderColor:'rgba(0,0,0,.06)',strokeDashArray:4},
        legend:{position:'top',fontSize:'11px',fontWeight:600},
        dataLabels:{enabled:false},
        theme:{mode:this.main.is_dark?'dark':'light'},
      };
    },
  },
  mounted(){
    this.columnOrder=this.allHeaders.filter(h=>!h.fixed).map(h=>h.key);
    this.fetchInventories();mitt.on('model-changed',this.onMC);
  },
  beforeUnmount(){mitt.off('model-changed',this.onMC)},
  methods:{
    onMC(d){if(d.model==='Inventory')this.fetchInventories()},
    onDateRangeChange(val){if(val&&val.start&&val.end){this.dateRange=[val.start,val.end];this.fetchInventories()}},
    async fetchInventories(){
      this.loading=true;
      try{
        const ds=this.dateRange[0],de=this.dateRange[1]||this.dateRange[0];
        const p={date_start:this.fmtDate(ds),date_end:this.fmtDate(de)};
        const r=await axios.get('/api/inventories/get_inventories',{params:p});
        this.inventories=(r.data.inventories||[]).map(c=>({...c,
          _tracking:(c.inventory_tracking_numbers||[]).join(', ')||'-',
          _details_count:(c.detail_inventories||[]).length,
        }));
      }catch(e){console.error(e)}
      this.loading=false;
    },
    fmtDate(d){if(!d)return'';if(typeof d==='string')return d;return d.toISOString().slice(0,10)},
    cellText(key,item){
      if(key==='date')return this.main.formateDate?this.main.formateDate(item.date):(item.date||'').slice(0,10);
      const v=item[key];return v==null?'-':String(v);
    },
    filterOpts(key){
      const other=Object.entries(this.colFilters).filter(([k])=>k!==key);
      let items=this.inventories;
      other.forEach(([k,vals])=>{if(vals&&vals.length)items=items.filter(it=>vals.includes(this.cellText(k,it)))});
      const s=new Set();items.forEach(i=>s.add(this.cellText(key,i)));
      return[...s].sort();
    },
    toggleSort(key){const i=this.colSorts.findIndex(s=>s.key===key);if(i===-1)this.colSorts.push({key,dir:'asc'});else if(this.colSorts[i].dir==='asc')this.colSorts[i].dir='desc';else this.colSorts.splice(i,1)},
    sortDir(key){const s=this.colSorts.find(x=>x.key===key);return s?s.dir:null},
    sortIdx(key){return this.colSorts.findIndex(x=>x.key===key)+1},
    sortItems(items){
      if(!this.colSorts.length)return items;
      return[...items].sort((a,b)=>{for(const{key,dir}of this.colSorts){let va=a[key],vb=b[key];if(va==null)va='';if(vb==null)vb='';const n=typeof va==='number'&&typeof vb==='number';if(n){if(va!==vb)return dir==='asc'?va-vb:vb-va}else{const c=String(va).localeCompare(String(vb));if(c)return dir==='asc'?c:-c}}return 0});
    },
    togglePin(k){const cur=this.pinState[k];if(!cur)this.pinState[k]='left';else if(cur==='left')this.pinState[k]='right';else delete this.pinState[k];this.pinState={...this.pinState}},
    isPinned(k){return!!this.pinState[k]},
    pinSide(k){return this.pinState[k]||null},
    getPinLeft(key){let o=this.colWidths['__checkbox']||50;for(const h of this.headers){if(h.key===key)break;if(this.pinState[h.key]==='left')o+=(this.colWidths[h.key]||150)}return o},
    getPinRight(key){let o=0;const rev=[...this.headers].reverse();for(const h of rev){if(h.key===key)break;if(this.pinState[h.key]==='right')o+=(this.colWidths[h.key]||150)}return o},
    toggleExpand(id){this.expanded[id]=!this.expanded[id];this.expanded={...this.expanded}},
    onDragStart(key){this.dragCol=key},
    onDragOver(key,e){e.preventDefault();this.dragOverCol=key},
    onDragEnd(){
      if(this.dragCol&&this.dragOverCol&&this.dragCol!==this.dragOverCol){
        const order=[...this.columnOrder];
        const fromIdx=order.indexOf(this.dragCol);
        const toIdx=order.indexOf(this.dragOverCol);
        if(fromIdx>-1&&toIdx>-1){order.splice(fromIdx,1);order.splice(toIdx,0,this.dragCol);this.columnOrder=order}
      }
      this.dragCol=null;this.dragOverCol=null;
    },
    groupDetails(inv){
      const groups={};
      (inv.detail_inventories||[]).forEach(di=>{
        const catId=di.detail_inventory_category_id||'uncategorized';
        if(!groups[catId]){
          const cat=catId!=='uncategorized'?this.detailCats.find(x=>x.id===catId):null;
          groups[catId]={category:cat||{name_en:catId==='uncategorized'?'Warehouse Stock':'#'+catId},items:[],totalW:0,totalP:0};
        }
        groups[catId].items.push(di);
        groups[catId].totalW+=Number(di.weight||0);
        groups[catId].totalP+=Number(di.total_price||0);
      });
      return Object.values(groups);
    },
    articleName(di){
      if(!di.stockable_type||di.stockable_type==='text')return di.article_description||'-';
      const s=di.stockable;return s?(s.name||s[`name_${this.lang}`]||'-'):'-';
    },
    confirmDelete(id){this.deleteId=id;this.deleteBulk=false;this.deleteDialog=true},
    confirmBulkDelete(){this.deleteBulk=true;this.deleteDialog=true},
    async execDelete(){
      this.deleting=true;
      try{
        const ids=this.deleteBulk?this.selected:[this.deleteId];
        await axios.delete('/api/inventory',{data:{ids}});
        this.main.add_snackbar({show:true,message:this.$t('deleted_successfully'),color:'success'});
        this.selected=[];this.deleteDialog=false;this.fetchInventories();
      }catch(e){console.error(e)}
      this.deleting=false;
    },
    exportExcel(){
      const data=this.colFilteredItems.map(c=>{const row={};this.headers.filter(h=>h.key!=='actions'&&h.key!=='expand').forEach(h=>{row[h.title]=this.cellText(h.key,c)});return row});
      const ws=XLSX.utils.json_to_sheet(data);const wb=XLSX.utils.book_new();
      XLSX.utils.book_append_sheet(wb,ws,'Inventories');XLSX.writeFile(wb,`inventories_${this.today}.xlsx`);
    },
    editInventory(item){
      this.$emit('edit-inventory', item);
    },
  },
};
