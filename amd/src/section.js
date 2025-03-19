// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Format mooin1pager section extra logic component.
 *
 * @module     format_mooin1pager/section
 * @copyright  2022 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import { BaseComponent } from 'core/reactive';
import { getCurrentCourseEditor } from 'core_courseformat/courseeditor';
import Templates from 'core/templates';
import ILD from "format_mooin1pager/ildhvp4";


class HighlightSection extends BaseComponent {

    /**
     * Constructor hook.
     */
    create() {
        // Optional component name for debugging.
        this.name = 'format_mooin1pager_section';
        // Default query selectors.
        this.selectors = {
            SETMARKER: `[data-action="sectionHighlight"]`,
            REMOVEMARKER: `[data-action="sectionUnhighlight"]`,
            ACTIONTEXT: `.menu-action-text`,
            ICON: `.icon`,
        };
        // Default classes to toggle on refresh.
        this.classes = {
            HIDE: 'd-none',
        };
        // The mooin1pager format section specific actions.
        this.formatActions = {
            HIGHLIGHT: 'sectionHighlight',
            UNHIGHLIGHT: 'sectionUnhighlight',
        };
    }

    /**
     * Component watchers.
     *
     * @returns {Array} of watchers
     */
    getWatchers() {
        return [
            { watch: `section.current:updated`, handler: this._refreshHighlight },
        ];
    }

    /**
     * Update a content section using the state information.
     *
     * @param {object} param
     * @param {Object} param.element details the update details.
     */
    async _refreshHighlight({ element }) {
        let selector;
        let newAction;
        if (element.current) {
            selector = this.selectors.SETMARKER;
            newAction = this.formatActions.UNHIGHLIGHT;
        } else {
            selector = this.selectors.REMOVEMARKER;
            newAction = this.formatActions.HIGHLIGHT;
        }
        // Find the affected action.
        const affectedAction = this.getElement(`${selector}`, element.id);
        if (!affectedAction) {
            return;
        }
        // Change action, text and icon.
        affectedAction.dataset.action = newAction;
        const actionText = affectedAction.querySelector(this.selectors.ACTIONTEXT);
        if (affectedAction.dataset?.swapname && actionText) {
            const oldText = actionText?.innerText;
            actionText.innerText = affectedAction.dataset.swapname;
            affectedAction.dataset.swapname = oldText;
        }
        const icon = affectedAction.querySelector(this.selectors.ICON);
        if (affectedAction.dataset?.swapicon && icon) {
            const newIcon = affectedAction.dataset.swapicon;
            if (newIcon) {
                const pixHtml = await Templates.renderPix(newIcon, 'core');
                Templates.replaceNode(icon, pixHtml, '');
                affectedAction.dataset.swapicon = affectedAction.dataset.icon;
                affectedAction.dataset.icon = newIcon;
            }
        }
    }

    /**
     * Initial state ready method.
     *
     * @param {Object} state the initial state
 */
    stateReady(state) {
        this._hvpListener();
    }

    _hvpListener() {
        var h5p_contentIds = [];
        var parentIFrames = this.getElements(this.selectors.H5P);
        //console.log("Anzahl der parentIFrames gefunden:", parentIFrames.length);
        if (parentIFrames.length > 0) {
            parentIFrames.forEach((parentIFrame) => {
                if (parentIFrame.contentDocument) {
                    var parentIFrameContent =
                        parentIFrame.contentDocument || parentIFrame.contentWindow.document;
                    //console.log("parentIFrameContent gefunden:", parentIFrameContent);

                    let nestedIFrame = null;

                    const adjustParentIFrameHeight = () => {
                        setTimeout(() => {
                            console.log("adjustParentIFrameHeight");

                            if (nestedIFrame && nestedIFrame.contentWindow.document.body) {
                                const nestedIFrameHeight =
                                    nestedIFrame.contentWindow.document.body.scrollHeight;
                                if (nestedIFrameHeight > 1) {
                                    parentIFrame.style.height = nestedIFrameHeight + "px";
                                    // console.log(
                                    //     "ParentIFrame-Höhe angepasst:",
                                    //     nestedIFrameHeight + "px"
                                    // );
                                } else {
                                    //console.log("Inhalt noch nicht vollständig gerendert, Höhe nicht angepasst.");
                                }
                            } else {
                                //console.log("Body ist noch nicht verfügbar.");
                            }
                        }, 100);
                    };

                    const monitorElementLoads = () => {
                        console.log("monitorElementLoads");

                        // Überwache das Laden von Bildern, Videos und anderen Medien im iframe
                        const elementsToWatch = ['img', 'video', 'iframe', 'embed', 'object'];
                        elementsToWatch.forEach(tag => {
                            const elements = nestedIFrame.contentDocument.getElementsByTagName(tag);
                            for (let element of elements) {
                                element.addEventListener('load', adjustParentIFrameHeight);
                                element.addEventListener('resize', adjustParentIFrameHeight);
                            }
                        });
                    };

                    const checkForH5P = () => {
                        console.log("checkForH5P");
                        if (nestedIFrame) {
                            var H5PIntegration = nestedIFrame.contentWindow.H5PIntegration;
                            var H5P = nestedIFrame.contentWindow.H5P;
                            if (H5P && H5P.externalDispatcher) {
                                //console.log("H5P-Objekt gefunden.");

                                //workaround for problem, that several observer regard the same object
                                function addUniqueH5PcontentId(array, element) {
                                    if (!array.includes(element)) {
                                        array.push(element);
                                    }
                                    return array;
                                }

                                //array of h5p contentId

                                H5P.setFinished = function (contentId, score, maxScore, time) {
                                    // H5P-Funktion hijacken, damit die Grade nicht doppelt eingetragen wird
                                };

                                //H5P.externalDispatcher.on("xAPI", this._hvpprogress.bind(this));
                                //ILD.checkLibrary();
                                //H5P.externalDispatcher.on("xAPI", ILD.xAPIAnsweredListener);
                                if (!h5p_contentIds.includes(H5P.instances[0].contentId)) {
                                    var current_array = addUniqueH5PcontentId(h5p_contentIds, H5P.instances[0].contentId);
                                    ILD.init(H5P, H5PIntegration, this.id, this.reactive);
                                }
                                //window.console.log(H5P);

                                adjustParentIFrameHeight(); // Höhe sofort anpassen, wenn H5P gefunden wird

                                // Starte den MutationObserver
                                var observer = new MutationObserver(function (mutations) {
                                    mutations.forEach(function (mutation) {
                                        if (mutation.addedNodes.length > 0 || mutation.attributeName === 'src') {
                                            // console.log(
                                            //     "DOM-Änderung oder Attributänderung erkannt im .h5p-iframe: ",
                                            //     mutation
                                            // );
                                            adjustParentIFrameHeight(); // Passe die Höhe nach der Mutation oder Attributänderung an
                                        }
                                    });
                                });

                                observer.observe(nestedIFrame.contentDocument, {
                                    childList: true,
                                    subtree: true,
                                    attributes: true, // Überwacht Änderungen an Attributen wie `src`
                                });
                                // console.log(
                                //     "MutationObserver wurde gestartet, um Änderungen im .h5p-iframe zu überwachen."
                                // );

                                return true; // H5P wurde gefunden und alles eingerichtet
                            }
                        }
                        return false; // H5P wurde noch nicht gefunden oder nestedIFrame ist nicht verfügbar
                    };

                    const checkForNestedIFrame = () => {
                        nestedIFrame = parentIFrameContent.querySelector(".h5p-iframe");
                        //console.log("nestedIFrame gefunden:", nestedIFrame);

                        if (nestedIFrame) {
                            // Füge ein 'load' Event hinzu
                            nestedIFrame.addEventListener('load', function () {
                                //console.log('.h5p-iframe vollständig geladen.');
                                adjustParentIFrameHeight(); // Passe die Höhe an, wenn das iframe vollständig geladen ist
                                checkForH5P(); // Prüfe H5P erneut nach dem Laden
                                monitorElementLoads(); // Überwache das Laden von Elementen
                            });

                            // Fallback: Sofortiger Versuch, H5P zu finden
                            if (!checkForH5P()) {
                                //console.log("H5P wurde nicht gefunden, starte Überwachung.");

                                // Fallback: Regelmäßige Überprüfung des Inhalts (Polling) für H5P
                                var h5pCheckInterval = setInterval(function () {
                                    if (checkForH5P()) {
                                        clearInterval(h5pCheckInterval); // Stoppe das Intervall, wenn H5P gefunden wurde
                                    }
                                }, 500); // Überprüft alle 500ms
                            }

                            return true; // nestedIFrame wurde gefunden, keine weitere Aktion erforderlich
                        }
                        return false; // nestedIFrame wurde noch nicht gefunden
                    };

                    // Initialer Versuch, nestedIFrame zu finden
                    if (!checkForNestedIFrame()) {
                        // console.log(
                        //     "nestedIFrame wurde nicht gefunden, starte Beobachtung des parentIFrame."
                        // );

                        // Beobachte den parentIFrame für das Erscheinen des nestedIFrame
                        var observer = new MutationObserver(function (mutations) {
                            console.log("_hvpListener 7");

                            mutations.forEach(function (mutation) {
                                if (mutation.addedNodes.length > 0) {
                                    // console.log(
                                    //     "Eine neue Node wurde hinzugefügt:",
                                    //     mutation.addedNodes
                                    // );
                                    if (checkForNestedIFrame()) {
                                        observer.disconnect(); // Stoppe das Beobachten, nachdem nestedIFrame gefunden wurde
                                    }
                                }
                            });
                        });

                        observer.observe(parentIFrameContent, {
                            childList: true,
                            subtree: true,
                        });
                    }
                } else {
                    //console.error("Kein Dokument im parentIFrame gefunden.");
                }
            });
        } else {
            //console.error("Keine parentIFrames gefunden.");
        }
    }
}

export const init = () => {
    // Add component to the section.
    const courseEditor = getCurrentCourseEditor();
    if (courseEditor.supportComponents && courseEditor.isEditing) {
        new HighlightSection({
            element: document.getElementById('page'),
            reactive: courseEditor,
        });
    }
};

