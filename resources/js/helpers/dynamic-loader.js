import { useAIPrompt } from "@/stores/AIPrompt";
import ApexCharts from "apexcharts";

// Guarantee ApexCharts is available globally on window as a constructor
if (typeof window !== 'undefined') {
  const resolvedApex = ApexCharts?.default || ApexCharts;
  window.ApexCharts = resolvedApex;
  window.__ai_charts = window.__ai_charts || {};
}

/**
 * Dynamically load AI-generated HTML/Vue/ApexCharts content into a container.
 * Handles script execution safely with retry logic and robust ApexCharts mounting.
 */
export function loadExternalContent(htmlString, containerId, retries = 0) {
  const maxRetries = 10;
  try {
    const container = document.getElementById(containerId);
    if (!container) {
      if (retries < maxRetries) {
        setTimeout(() => loadExternalContent(htmlString, containerId, retries + 1), 100);
      }
      return;
    }

    if (!htmlString) {
      container.innerHTML = '';
      return;
    }

    // Sanitize: strip Vue event bindings (@click, v-on:click, etc.) that can't work via innerHTML
    let sanitized = htmlString
      .replace(/\s(@|v-on:)[a-z.-]+(?:\.[\w.]+)*\s*=\s*"[^"]*"/gi, '')
      .replace(/\s(@|v-on:)[a-z.-]+(?:\.[\w.]+)*\s*=\s*'[^']*'/gi, '');

    // Create a temporary DOM element to parse HTML safely
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = sanitized;

    // Ensure all mdi icon elements have the core 'mdi' class so @mdi/font ::before applies properly
    tempDiv.querySelectorAll('[class*="mdi-"]').forEach(el => {
      if (!el.classList.contains('mdi')) {
        el.classList.add('mdi');
      }
    });

    // Extract all <script> tags before moving DOM nodes
    const scripts = Array.from(tempDiv.querySelectorAll('script'));
    // Remove scripts from tempDiv so they don't get inserted inertly
    scripts.forEach(s => s.remove());

    // Clear previous content of container
    container.innerHTML = '';

    // Move all child nodes (preserving HTML structure, tables, KPI cards, chart divs)
    while (tempDiv.firstChild) {
      container.appendChild(tempDiv.firstChild);
    }

    // Ensure ApexCharts reference is ready
    const Apex = window.ApexCharts?.default || window.ApexCharts || ApexCharts?.default || ApexCharts;
    window.ApexCharts = Apex;

    // Execute scripts with DOM-readiness polling for #aichart_ containers
    scripts.forEach((script, idx) => {
      const code = (script.textContent || '').trim();
      if (!code && !script.src) return;

      if (script.src) {
        const s = document.createElement('script');
        for (let attr of script.attributes) {
          s.setAttribute(attr.name, attr.value);
        }
        document.body.appendChild(s);
        return;
      }

      // Check if code contains chart container ID
      const chartMatch = code.match(/['"](#?aichart_[a-zA-Z0-9_-]+)['"]/);
      const targetChartId = chartMatch ? chartMatch[1].replace(/^#/, '') : null;

      const runScriptWithPolling = (attempt = 0) => {
        // If script targets a specific chart element, wait until it is in the active DOM
        if (targetChartId) {
          const targetEl = document.getElementById(targetChartId) || container.querySelector('#' + targetChartId);
          if (!targetEl && attempt < 25) {
            setTimeout(() => runScriptWithPolling(attempt + 1), 60);
            return;
          }
        }

        try {
          // Execute code with ApexCharts constructor, window, and document in direct scope
          const fn = new Function('ApexCharts', 'window', 'document', code);
          fn(Apex, window, document);
          // Dispatch resize events so responsive ApexCharts redraw with container dimensions
          setTimeout(() => { window.dispatchEvent(new Event('resize')); }, 80);
          setTimeout(() => { window.dispatchEvent(new Event('resize')); }, 300);
        } catch (scriptErr) {
          console.warn('Direct dynamic script execution failed, falling back to script tag:', scriptErr);
          try {
            const newScript = document.createElement('script');
            newScript.textContent = code;
            document.body.appendChild(newScript);
            setTimeout(() => {
              try { document.body.removeChild(newScript); } catch (e) {}
            }, 1000);
          } catch (fallbackScriptErr) {
            console.error('Script injection fallback error:', fallbackScriptErr);
          }
        } finally {
          if (idx === scripts.length - 1) {
            window.dispatchEvent(new CustomEvent('ai:content-rendered', { detail: { containerId } }));
          }
        }
      };

      // Small initial delay so DOM tree is fully painted
      setTimeout(runScriptWithPolling, 50 * (idx + 1));
    });

    if (scripts.length === 0) {
      window.dispatchEvent(new CustomEvent('ai:content-rendered', { detail: { containerId } }));
    }
  } catch (error) {
    console.error('loadExternalContent error:', error);
    try {
      const container = document.getElementById(containerId);
      if (container && (!container.innerHTML || container.innerHTML.trim() === '')) {
        container.innerHTML = htmlString;
      }
    } catch (fallbackErr) {
      console.warn('Fallback HTML set error:', fallbackErr);
    }
  }
}
