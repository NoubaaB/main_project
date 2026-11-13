import { useAIPrompt } from "@/stores/AIPrompt";

/**
 * Format markdown plain text into clean, accessible HTML.
 */
export function formatMarkdown(text) {
  if (!text || typeof text !== 'string') return '';
  const trimmed = text.trim();
  // If it's already an HTML structure (starts with a tag like <div, <table, <p, etc.)
  if (/^<[a-z][\s\S]*>/i.test(trimmed)) {
    return text;
  }

  let out = trimmed
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;');

  // Headings
  out = out.replace(/^### (.*$)/gim, '<h4 style="margin: 12px 0 6px; font-weight: 700; color: inherit;">$1</h4>');
  out = out.replace(/^## (.*$)/gim, '<h3 style="margin: 14px 0 8px; font-weight: 800; color: inherit;">$1</h3>');
  out = out.replace(/^# (.*$)/gim, '<h2 style="margin: 16px 0 10px; font-weight: 900; color: inherit;">$1</h2>');

  // Bold (**text**) -> blue bold
  out = out.replace(/\*\*(.*?)\*\*/g, '<strong style="color: #42A5F5; font-weight: 700;">$1</strong>');

  // Italic (*text*)
  out = out.replace(/\*(.*?)\*/g, '<em style="opacity: 0.9;">$1</em>');

  // Inline code (`code`)
  out = out.replace(/`([^`]+)`/g, '<code style="background: rgba(66, 165, 245, 0.15); color: #42A5F5; padding: 2px 6px; border-radius: 4px; font-size: 13px; font-family: monospace;">$1</code>');

  // Lists (- item or * item)
  out = out.replace(/^\s*[-*]\s+(.*)$/gim, '<li style="margin-left: 18px; margin-bottom: 6px;">$1</li>');

  // Paragraph breaks
  out = out.replace(/\n\n+/g, '<div style="height: 12px;"></div>');
  out = out.replace(/\n/g, '<br/>');

  return `<div class="ai-plain-text-bubble" style="font-size: 14.5px; line-height: 1.7; word-break: break-word; color: inherit;">${out}</div>`;
}

/**
 * Dynamically load AI-generated HTML/Vue/ApexCharts or plain text content into a container.
 * Handles script execution safely with retry logic.
 */
export function loadExternalContent(htmlString, containerId, retries = 0) {
  const maxRetries = 5;
  try {
    const container = document.getElementById(containerId);
    if (!container) {
      if (retries < maxRetries) {
        setTimeout(() => loadExternalContent(htmlString, containerId, retries + 1), 200);
      }
      return;
    }

    // Clear previous content
    container.innerHTML = '';

    if (!htmlString || typeof htmlString !== 'string') {
      window.dispatchEvent(new CustomEvent('ai:content-rendered', { detail: { containerId } }));
      return;
    }

    const trimmed = htmlString.trim();

    // Check if plain text / markdown (not starting with HTML tag)
    if (!/^<[a-z][\s\S]*>/i.test(trimmed)) {
      container.innerHTML = formatMarkdown(trimmed);
      window.dispatchEvent(new CustomEvent('ai:content-rendered', { detail: { containerId } }));
      return;
    }

    // Sanitize: strip Vue event bindings (@click, v-on:click, etc.) that can't work via innerHTML
    let sanitized = trimmed.replace(/\s(@|v-on:)[a-z.-]+(?:\.[\w.]+)*\s*=\s*"[^"]*"/gi, '');
    sanitized = sanitized.replace(/\s(@|v-on:)[a-z.-]+(?:\.[\w.]+)*\s*=\s*'[^']*'/gi, '');

    // Create a temporary DOM element
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = sanitized;

    // Ensure all mdi icon elements have the core 'mdi' class so @mdi/font ::before applies properly
    tempDiv.querySelectorAll('[class*="mdi-"]').forEach(el => {
      if (!el.classList.contains('mdi')) {
        el.classList.add('mdi');
      }
    });

    // Move childNodes (both elements and text nodes) except scripts
    const fragment = document.createDocumentFragment();
    Array.from(tempDiv.childNodes).forEach(node => {
      if (node.nodeType === Node.ELEMENT_NODE && node.tagName === 'SCRIPT') {
        return;
      }
      fragment.appendChild(node.cloneNode(true));
    });
    container.appendChild(fragment);

    // Execute all <script> tags with a small delay for DOM readiness
    const scripts = tempDiv.querySelectorAll('script');
    let delay = 50;
    scripts.forEach((script, idx) => {
      setTimeout(() => {
        try {
          const newScript = document.createElement('script');
          for (let attr of script.attributes) {
            newScript.setAttribute(attr.name, attr.value);
          }
          if (script.src) {
            newScript.src = script.src;
          } else {
            newScript.textContent = `(function(){${script.textContent}})();`;
          }
          document.body.appendChild(newScript);
        } catch (e) {
          console.warn('Script execution error:', e);
        } finally {
          if (idx === scripts.length - 1) {
            window.dispatchEvent(new CustomEvent('ai:content-rendered', { detail: { containerId } }));
          }
        }
      }, delay * (idx + 1));
    });

    if (scripts.length === 0) {
      window.dispatchEvent(new CustomEvent('ai:content-rendered', { detail: { containerId } }));
    }
  } catch (error) {
    console.error('loadExternalContent error:', error);
    const ai_prompt = useAIPrompt();
    ai_prompt.error();
  }
}
