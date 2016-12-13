"use strict";
(function() {
    document.addEventListener('DOMContentLoaded', function(evt) {
        try {
            new LazyLoad();
        } catch (e) {
            console.error(e);
        }
    });
})();
