define(['jquery', 'format_mooin1pager/lib', 'format_mooin1pager/udateprogressbar'], function ($, lib, Update) {
    return {
        init: function () {
            // Manueller Toggle
            $(document).on('click', '[data-action="toggle-manual-completion"]', () => {
                const courseId = M.cfg.courseId || document.body.dataset.courseid;
                if (!courseId) return console.warn("Keine courseId gefunden");
                Update.updateProgressBar(courseId);
            });

            // Starte den H5P-Listener
            lib.hvpListener();
        }
    };
});
