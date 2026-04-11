import { useAIPrompt } from "@/stores/AIPrompt";

/**
 * Dynamically load AI-generated HTML/Vue/ApexCharts content into a container.
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

    // Create a temporary DOM element
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = htmlString;

    // Move everything except scripts
    const fragment = document.createDocumentFragment();
    Array.from(tempDiv.children).forEach(child => {
      if (child.tagName !== 'SCRIPT') {
        fragment.appendChild(child.cloneNode(true));
      }
    });
    container.appendChild(fragment);

    // Execute all <script> tags with a small delay for DOM readiness
    const scripts = tempDiv.querySelectorAll('script');
    let delay = 50;
    scripts.forEach((script, idx) => {
      setTimeout(() => {
        try {
          const newScript = document.createElement('script');
          // Copy attributes (like src or type)
          for (let attr of script.attributes) {
            newScript.setAttribute(attr.name, attr.value);
          }
          if (script.src) {
            newScript.src = script.src;
          } else {
            // Wrap in IIFE to avoid 'already declared' errors across messages
            newScript.textContent = `(function(){${script.textContent}})();`;
          }
          document.body.appendChild(newScript);
        } catch (e) {
          console.warn('Script execution error:', e);
        }
      }, delay * (idx + 1));
    });
  } catch (error) {
    console.error('loadExternalContent error:', error);
    const ai_prompt = useAIPrompt();
    ai_prompt.error();
  }
}
