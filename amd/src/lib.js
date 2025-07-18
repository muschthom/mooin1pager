define(['jquery', 'core/ajax', 'core/notification', 'format_mooin1pager/ildhvp4', 'format_mooin1pager/udateprogressbar'], function ($, Ajax, Notification, ILD, Update) {

    function hvpListener() {
        console.log("HVP Listener gestartet");
        const isEditing = document.body.classList.contains('editing') || 
                     (typeof M !== 'undefined' && M.cfg && M.cfg.editing);
    
        if (isEditing) {
            console.log("editmode"); 
        } else {
            const h5p_contentIds = [];
            const parentIFrames = document.querySelectorAll('iframe');

            parentIFrames.forEach(parentIFrame => {
                try {
                    const doc = parentIFrame.contentDocument || parentIFrame.contentWindow.document;
                    let nestedIFrame = null;

                    // Höhe des H5P-iFrames anpassen
                    const adjustParentIFrameHeight = () => {
                        setTimeout(() => {
                            if (nestedIFrame?.contentWindow?.document?.body) {
                                const nestedHeight = nestedIFrame.contentWindow.document.body.scrollHeight;
                                if (nestedHeight > 1) {
                                    parentIFrame.style.height = nestedHeight + "px";
                                }
                            }
                        }, 100);
                    };

                    // Media-Elemente innerhalb des H5P-iFrames beobachten
                    const monitorElementLoads = () => {
                        ['img', 'video', 'iframe', 'embed', 'object'].forEach(tag => {
                            Array.from(nestedIFrame.contentDocument.getElementsByTagName(tag))
                                .forEach(el => {
                                    el.addEventListener('load', adjustParentIFrameHeight);
                                    el.addEventListener('resize', adjustParentIFrameHeight);
                                });
                        });
                    };

                    // Prüft, ob H5P geladen ist und registriert Listener
                    const checkForH5P = () => {
                        nestedIFrame = nestedIFrame || doc.querySelector('.h5p-iframe');
                        if (nestedIFrame?.contentWindow) {
                            const pw = nestedIFrame.contentWindow;
                            if (pw.H5P && pw.H5P.externalDispatcher) {
                                const contentId = pw.H5P.instances[0]?.contentId;
                                if (!h5p_contentIds.includes(contentId)) {
                                    h5p_contentIds.push(contentId);
                                    // Optionale Initialisierung: ILD.init(...)
                                    var H5PIntegration = nestedIFrame.contentWindow.H5PIntegration;
                                    var H5P = nestedIFrame.contentWindow.H5P;
                                    ILD.init(H5P, H5PIntegration, this.id, this.reactive);
                                }

                                // H5P Interaction abfangen
                                pw.H5P.externalDispatcher.on('xAPI', event => {
                                    //console.log("H5P xAPI Event:", event);
                                    const courseId = M.cfg.courseId || document.body.dataset.courseid;
                                    Update.updateProgressBar(courseId);
                                });

                                adjustParentIFrameHeight();
                                monitorElementLoads();

                                // MutationObserver, um auftretende Änderungen im H5P-Content zu erkennen
                                const observer = new MutationObserver(muts => {
                                    muts.forEach(mut => {
                                        if (mut.addedNodes.length || mut.attributeName === 'src') {
                                            adjustParentIFrameHeight();
                                        }
                                    });
                                });
                                observer.observe(nestedIFrame.contentDocument, {
                                    childList: true,
                                    subtree: true,
                                    attributes: true
                                });
                                return true;
                            }
                        }
                        return false;
                    };

                    // Prüft existierende H5P-iFrames
                    const initOnce = () => {
                        if (!checkForH5P()) {
                            const outerObserver = new MutationObserver(muts => {
                                muts.forEach(mut => {
                                    if (mut.addedNodes.length && checkForH5P()) {
                                        outerObserver.disconnect();
                                    }
                                });
                            });
                            outerObserver.observe(doc.body, { childList: true, subtree: true });
                        }
                    };

                    initOnce();
                } catch (err) {
                    console.error("Fehler in HVP Listener:", err);
                }
            });
        }
    }


    return {
        hvpListener: hvpListener
    };
});