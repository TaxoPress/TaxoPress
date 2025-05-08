(function (global) {
    /**
     * PP_TooltipsLibrary - Tooltip system that works with manually defined HTML tooltips.
     * Requires both `data-toggle="tooltip"` and `.pp-tooltips-library` class.
     */
    const PP_TooltipsLibrary = {
        /**
         * Initializes the tooltip system on DOM ready.
         */
        init() {
            this.applyTooltips(document);
            this.bindGlobalClick();
        },

        /**
         * Applies tooltips to a given DOM context.
         * Only initializes tooltips that haven't already been processed.
         *
         * @param {HTMLElement|Document} context - Where to apply tooltips.
         */
        applyTooltips(context = document) {
            const tooltips = context.querySelectorAll('[data-toggle="tooltip"].pp-tooltips-library');

            tooltips.forEach((tooltip) => {
                if (tooltip.dataset.tooltipProcessed === 'true') return;
                tooltip.dataset.tooltipProcessed = 'true';

                // Do nothing if tooltip-text is not found
                const tooltipBox = tooltip.querySelector('.tooltip-text');
                if (!tooltipBox) return;

                // Ensure the tooltip box ends with a pointer arrow
                if (!tooltipBox.querySelector('i')) {
                    const arrow = document.createElement('i');
                    tooltipBox.appendChild(arrow);
                }

                // Handle click-triggered tooltips
                if (tooltip.classList.contains('click')) {
                    tooltip.addEventListener('click', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        tooltip.classList.toggle('is-active');
                    });
                }
            });
        },

        /**
         * Global click handler to toggle/collapse click-based tooltips.
         */
        bindGlobalClick() {
            document.addEventListener('click', function (event) {
                const tooltip = event.target.closest('[data-toggle="tooltip"].pp-tooltips-library.click');
                if (tooltip) {
                    event.preventDefault();
                    event.stopPropagation();
                    tooltip.classList.toggle('is-active');
                }
            });
        },

        /**
         * Refresh tooltips after dynamically adding new DOM content.
         *
         * @param {HTMLElement|Document} context - Where to re-initialize tooltips.
         */
        refresh(context = document) {
            this.applyTooltips(context);
        }
    };

    // Auto-init
    document.addEventListener('DOMContentLoaded', () => PP_TooltipsLibrary.init());

    // Export globally
    global.PP_TooltipsLibrary = PP_TooltipsLibrary;
})(window);