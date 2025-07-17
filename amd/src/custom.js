define(['jquery', 'core/ajax', 'core/notification', 'format_mooin1pager/lib'],
    function ($, Ajax, Notification, lib) {
        return {
            init: function () {
                $(document).ready(function () {

                    //remove the link from the breadcrumb items that are not cours title
                    console.log("replaceBreadcrumbLink");
                    let items = document.querySelectorAll(".breadcrumb-item");
                    if (items.length >= 2) {
                        let link = items[1].querySelector("a");
                        if (link) {
                            let span = document.createElement("span");
                            span.textContent = link.textContent;
                            span.style.cursor = "default";
                            link.parentNode.replaceChild(span, link);
                        }
                    }

                    // 🔥 Listen for toggle-manual-completion button clicks
                    $(document).on('click', '[data-action="toggle-manual-completion"]', function (e) {
                        const courseId = M.cfg.courseId || $('body').data('courseid');
                        if (!courseId) {
                            console.warn("Course ID konnte nicht ermittelt werden.");
                            return;
                        }
                        lib.updateProgressBar(courseId);
                    });

                    //listen to H5P xAPI events
                    lib.hvpListener();
                });

            }
        }
    }
);