"use strict";
(function() {
    document.addEventListener('DOMContentLoaded', function(evt) {
        try {
            new LazyLoad({
                skip_invisible: false,
            });
        } catch (e) {
            console.error(e);
        }
    });
})();
