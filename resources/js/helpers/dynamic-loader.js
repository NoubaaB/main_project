import { useAIPrompt } from "@/stores/AIPrompt";
const ai_prompt = useAIPrompt();
export function loadExternalContent(htmlString, containerId) {
  try {
    const container = document.getElementById(containerId);
  
    // Create a temporary DOM element
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = htmlString;
    // Move everything except scripts
    Array.from(tempDiv.children).forEach(child => {
      if (child.tagName !== 'SCRIPT') {
        container.appendChild(child);
      }
    });
  
    // Execute all <script> tags manually
    const scripts = tempDiv.querySelectorAll('script');
    scripts.forEach(script => {
      const newScript = document.createElement('script');
  
      // Copy attributes (like src or type)
      for (let attr of script.attributes) {
        newScript.setAttribute(attr.name, attr.value);
      }
  
      if (script.src) {
        // External script
        newScript.src = script.src;
      } else {
        // Inline script
        newScript.textContent = script.textContent;
      }
  
      document.body.appendChild(newScript); // Run in document context
    });
  }
  catch (error) {
    ai_prompt.error()
  }
  
};
