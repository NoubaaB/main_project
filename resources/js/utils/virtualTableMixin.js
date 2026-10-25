/**
 * Mixin providing high-performance virtual windowing for standard HTML tables
 * without pagination. Renders only visible rows + buffer, with zero DOM lag.
 */
export default {
  data() {
    return {
      tableScrollTop: 0,
      tableContainerHeight: 600,
      rowHeight: 44, // standard row height in px
      virtualBuffer: 25, // buffer rows above and below viewport
      _scrollRaf: null,
      _resizeObserver: null,
    };
  },
  computed: {
    virtualTotalCount() {
      return this.colFilteredItems ? this.colFilteredItems.length : 0;
    },
    virtualStartIndex() {
      if (this.virtualTotalCount <= 50) return 0;
      const idx = Math.floor(this.tableScrollTop / this.rowHeight) - this.virtualBuffer;
      return Math.max(0, idx);
    },
    virtualEndIndex() {
      if (this.virtualTotalCount <= 50) return this.virtualTotalCount;
      const inView = Math.ceil(this.tableContainerHeight / this.rowHeight);
      const idx = Math.floor(this.tableScrollTop / this.rowHeight) + inView + this.virtualBuffer;
      return Math.min(this.virtualTotalCount, idx);
    },
    virtualTopHeight() {
      if (this.virtualTotalCount <= 50) return 0;
      return this.virtualStartIndex * this.rowHeight;
    },
    virtualBottomHeight() {
      if (this.virtualTotalCount <= 50) return 0;
      return Math.max(0, (this.virtualTotalCount - this.virtualEndIndex) * this.rowHeight);
    },
    visibleItems() {
      if (this.virtualTotalCount <= 50) return this.colFilteredItems || [];
      return (this.colFilteredItems || []).slice(this.virtualStartIndex, this.virtualEndIndex);
    },
  },
  methods: {
    onTableScroll(e) {
      if (this._scrollRaf) cancelAnimationFrame(this._scrollRaf);
      this._scrollRaf = requestAnimationFrame(() => {
        if (e && e.target) {
          this.tableScrollTop = e.target.scrollTop;
          this.tableContainerHeight = e.target.clientHeight || 600;
        }
      });
    },
    resetTableScroll() {
      this.tableScrollTop = 0;
      if (this.$refs.tableWrap) {
        this.$refs.tableWrap.scrollTop = 0;
      }
    },
  },
  mounted() {
    this.$nextTick(() => {
      if (this.$refs.tableWrap) {
        this.tableContainerHeight = this.$refs.tableWrap.clientHeight || 600;
        if (typeof window !== 'undefined' && window.ResizeObserver) {
          this._resizeObserver = new ResizeObserver((entries) => {
            for (const entry of entries) {
              if (entry.target === this.$refs.tableWrap) {
                this.tableContainerHeight = entry.contentRect.height || this.tableContainerHeight;
              }
            }
          });
          this._resizeObserver.observe(this.$refs.tableWrap);
        }
      }
    });
  },
  beforeUnmount() {
    if (this._scrollRaf) cancelAnimationFrame(this._scrollRaf);
    if (this._resizeObserver) {
      this._resizeObserver.disconnect();
      this._resizeObserver = null;
    }
  },
};
