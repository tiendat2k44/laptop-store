/**
 * Prevent Double-Click Script
 * Prevents double-clicking on links and buttons marked with data-prevent-double-click="true"
 * Also prevents double-submit on forms
 */
(function() {
    function disableAfterClick(e) {
        var el = e.currentTarget;
        if (el.hasAttribute('data-clicked')) {
            e.preventDefault();
            e.stopPropagation();
            return false;
        }
        el.setAttribute('data-clicked', 'true');
        if (el.tagName === 'BUTTON' || el.tagName === 'INPUT') {
            el.disabled = true;
        }
        // Re-enable after navigation or short timeout (fallback)
        setTimeout(function() {
            el.removeAttribute('data-clicked');
            if (el.tagName === 'BUTTON' || el.tagName === 'INPUT') {
                el.disabled = false;
            }
        }, 3000);
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        // Prevent double-click on marked elements
        var elements = document.querySelectorAll('[data-prevent-double-click="true"]');
        elements.forEach(function(el) {
            el.addEventListener('click', disableAfterClick);
        });
        
        // Also prevent double-submit on search forms
        var searchForms = document.querySelectorAll('form.search-form');
        searchForms.forEach(function(form) {
            form.addEventListener('submit', function(e) {
                var btn = form.querySelector('button[type="submit"]');
                if (btn && btn.hasAttribute('data-submitted')) {
                    e.preventDefault();
                    return false;
                }
                if (btn) {
                    btn.setAttribute('data-submitted', 'true');
                    btn.disabled = true;
                }
            });
        });
    });
})();
