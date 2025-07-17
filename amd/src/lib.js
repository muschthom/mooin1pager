define(['jquery', 'core/ajax', 'core/notification'], function ($, Ajax, Notification) {
    function updateProgressBar(courseId) {
        setTimeout(() => {
            Ajax.call([{
                methodname: 'format_mooin1pager_execute',
                args: { courseid: courseId }
            }])[0]
                .then(result => {
                    console.log("AJAX Call Result:", result);
                    const curr = result.courseprogress;

                    const container = document.querySelector('.progress-container');
                    if (container) {
                        const innerBar = container.querySelector('.progressbar-inner');
                        const percentSpan = container.querySelector('.font-weight-bold');

                        if (innerBar) {
                            innerBar.style.width = `${curr}%`;
                        }
                        if (percentSpan) {
                            percentSpan.textContent = `${curr}%`;
                        }
                    }
                })
                .catch(error => {
                    Notification.exception(error);
                });
        }, 500); 
    }

    
    function hvpListener() {
        //console.log("HVP Listener started");
        const parentIFrames = document.querySelectorAll('iframe');
        parentIFrames.forEach(parentIFrame => {
            try {
                const doc = parentIFrame.contentDocument || parentIFrame.contentWindow.document;
                let nestedIFrame = null;

                //adjust height of nested H5P iframe
                const adjust = () => setTimeout(() => {
                    nestedIFrame = nestedIFrame || doc.querySelector('.h5p-iframe');
                    if (nestedIFrame?.contentWindow?.document) {
                        const h = nestedIFrame.contentWindow.document.body.scrollHeight;
                        if (h > 1) parentIFrame.style.height = `${h}px`;
                    }
                }, 100);

                //check if H5P is loaded in the nested iframe
                const checkH5P = () => {
                    nestedIFrame = nestedIFrame || doc.querySelector('.h5p-iframe');
                    if (nestedIFrame) {
                        const pw = nestedIFrame.contentWindow;
                        if (pw.H5P?.externalDispatcher) {
                            console.log("H5P gefunden");
                            // Listen for xAPI events from H5P
                            pw.H5P.externalDispatcher.on('xAPI', event => {
                                console.log("H5P xAPI Event:", event);
                                const courseId = M.cfg.courseId || document.body.dataset.courseid;
                                //update progress bar on xAPI event
                                updateProgressBar(courseId);
                            });
                            adjust();
                            return true;
                        }
                    }
                    return false;
                };

                if (!checkH5P()) {
                    const mo = new MutationObserver(() => {
                        if (checkH5P()) mo.disconnect();
                    });
                    mo.observe(doc.body, { childList: true, subtree: true });
                }
            } catch (err) {
                console.error("Fehler in HVP Listener:", err);
            }
        });
    }

    return {
        updateProgressBar: updateProgressBar,
        hvpListener: hvpListener
    };
});