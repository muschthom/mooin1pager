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
 * Course section format component.
 *
 * @module     core_courseformat/local/content/section
 * @class      core_courseformat/local/content/section
 * @copyright  2021 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import ILD from "format_mooin1pager/ildhvp4";
import Header from 'core_courseformat/local/content/section/header';
import DndSection from 'core_courseformat/local/courseeditor/dndsection';
import Templates from 'core/templates';
import Pending from "core/pending";

export default class extends DndSection {

    /**
     * Constructor hook.
     */
    create() {
        // Optional component name for debugging.
        this.name = 'content_section';
        // Default query selectors.
        this.selectors = {
            ACTIONMENU: '.section-actions',
            SECTION_ITEM: `[data-for='section_title']`,
            CM: `[data-for="cmitem"]`,
            SECTIONINFO: `[data-for="sectioninfo"]`,
            SECTIONBADGES: `[data-region="sectionbadges"]`,
            SHOWSECTION: `[data-action="sectionShow"]`,
            HIDESECTION: `[data-action="sectionHide"]`,
            ACTIONTEXT: `.menu-action-text`,
            ICON: `.icon`,
        };
        // Most classes will be loaded later by DndCmItem.
        this.classes = {
            LOCKED: 'editinprogress',
            HASDESCRIPTION: 'description',
            HIDE: 'd-none',
            HIDDEN: 'hidden',
            CURRENT: 'current',
        };

        // We need our id to watch specific events.
        this.id = this.element.dataset.id;
    }

    /**
     * Initial state ready method.
     *
     * @param {Object} state the initial state
     */
    stateReady(state) {
        this.configState(state);
        // Drag and drop is only available for components compatible course formats.
        if (this.reactive.isEditing && this.reactive.supportComponents) {
            // Section zero and other formats sections may not have a title to drag.
            const sectionItem = this.getElement(this.selectors.SECTION_ITEM);
            if (sectionItem) {
                // Init the inner dragable element.
                const headerComponent = new Header({
                    ...this,
                    element: sectionItem,
                    fullregion: this.element,
                });
                this.configDragDrop(headerComponent);
            }
        }
        this._openSectionIfNecessary();
    }

    /**
     * Open the section if the anchored activity is inside.
     */
    async _openSectionIfNecessary() {
        const pageCmInfo = this.reactive.getPageAnchorCmInfo();
        if (!pageCmInfo || pageCmInfo.sectionid !== this.id) {
            return;
        }
        await this.reactive.dispatch('sectionContentCollapsed', [this.id], false);
        const pendingOpen = new Pending(`courseformat/section:openSectionIfNecessary`);
        this.element.scrollIntoView({block: "center"});
        setTimeout(() => {
            this.reactive.dispatch('setPageItem', 'cm', pageCmInfo.id);
            pendingOpen.resolve();
        }, 250);
    }

    /**
     * Component watchers.
     *
     * @returns {Array} of watchers
     */
    getWatchers() {
        return [
            {watch: `section[${this.id}]:updated`, handler: this._refreshSection},
        ];
    }

    /**
     * Validate if the drop data can be dropped over the component.
     *
     * @param {Object} dropdata the exported drop data.
     * @returns {boolean}
     */
    validateDropData(dropdata) {
        // If the format uses one section per page sections dropping in the content is ignored.
        if (dropdata?.type === 'section' && this.reactive.sectionReturn !== null) {
            return false;
        }
        return super.validateDropData(dropdata);
    }

    /**
     * Get the last CM element of that section.
     *
     * @returns {element|null}
     */
    getLastCm() {
        const cms = this.getElements(this.selectors.CM);
        // DndUpload may add extra elements so :last-child selector cannot be used.
        if (!cms || cms.length === 0) {
            return null;
        }
        const lastCm = cms[cms.length - 1];
        // If it is a delegated section return the last item overall.
        if (this.section.component !== null) {
            return lastCm;
        }
        // If it is a regular section and the last item overall has a parent cm, return the parent instead.
        const parentSection = lastCm.parentNode.closest(this.selectors.CM);
        return parentSection ?? lastCm;
    }

    /**
     * Get a fallback element when there is no CM in the section.
     *
     * @returns {element|null} the las course module element of the section.
     */
    getLastCmFallback() {
        // The sectioninfo is always present, even when the section is empty.
        return this.getElement(this.selectors.SECTIONINFO);
    }

    /**
     * Update a content section using the state information.
     *
     * @param {object} param
     * @param {Object} param.element details the update details.
     */
    _refreshSection({element}) {
        // Update classes.
        this.element.classList.toggle(this.classes.DRAGGING, element.dragging ?? false);
        this.element.classList.toggle(this.classes.LOCKED, element.locked ?? false);
        this.element.classList.toggle(this.classes.HIDDEN, !element.visible ?? false);
        this.element.classList.toggle(this.classes.CURRENT, element.current ?? false);
        this.locked = element.locked;
        // The description box classes depends on the section state.
        const sectioninfo = this.getElement(this.selectors.SECTIONINFO);
        if (sectioninfo) {
            sectioninfo.classList.toggle(this.classes.HASDESCRIPTION, element.hasrestrictions);
        }
        // Update section badges and menus.
        this._updateBadges(element);
        this._updateActionsMenu(element);
    }

    /**
     * Update a section badges using the state information.
     *
     * @param {object} section the section state.
     */
    _updateBadges(section) {
        const current = this.getElement(`${this.selectors.SECTIONBADGES} [data-type='iscurrent']`);
        current?.classList.toggle(this.classes.HIDE, !section.current);

        const hiddenFromStudents = this.getElement(`${this.selectors.SECTIONBADGES} [data-type='hiddenfromstudents']`);
        hiddenFromStudents?.classList.toggle(this.classes.HIDE, section.visible);
    }

    /**
     * Update a section action menus.
     *
     * @param {object} section the section state.
     */
    async _updateActionsMenu(section) {
        let selector;
        let newAction;
        if (section.visible) {
            selector = this.selectors.SHOWSECTION;
            newAction = 'sectionHide';
        } else {
            selector = this.selectors.HIDESECTION;
            newAction = 'sectionShow';
        }
        // Find the affected action.
        const affectedAction = this._getActionMenu(selector);
        if (!affectedAction) {
            return;
        }
        // Change action.
        affectedAction.dataset.action = newAction;
        // Change text.
        const actionText = affectedAction.querySelector(this.selectors.ACTIONTEXT);
        if (affectedAction.dataset?.swapname && actionText) {
            const oldText = actionText?.innerText;
            actionText.innerText = affectedAction.dataset.swapname;
            affectedAction.dataset.swapname = oldText;
        }
        // Change icon.
        const icon = affectedAction.querySelector(this.selectors.ICON);
        if (affectedAction.dataset?.swapicon && icon) {
            const newIcon = affectedAction.dataset.swapicon;
            affectedAction.dataset.swapicon = affectedAction.dataset.icon;
            affectedAction.dataset.icon = newIcon;
            if (newIcon) {
                const pixHtml = await Templates.renderPix(newIcon, 'core');
                Templates.replaceNode(icon, pixHtml, '');
            }
        }
    }

    /**
     * Get the action menu element from the selector.
     *
     * @param {string} selector The selector to find the action menu.
     * @returns The action menu element.
     */
    _getActionMenu(selector) {
        return document.querySelector(`${this.selectors.ACTIONMENU}[data-sectionid='${this.id}'] ${selector}`);
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






  // _hvpListener() {
  //   var parentIFrames = this.getElements(this.selectors.H5P);
  //   if (parentIFrames.length > 0) {
  //     parentIFrames.forEach((parentIFrame) => {
  //       if (parentIFrame.contentDocument) {
  //         var parentIFrameContent =
  //           parentIFrame.contentDocument || parentIFrame.contentWindow.document;

  //         var nestedIFrame = parentIFrameContent.querySelector(".h5p-iframe");

  //         if (nestedIFrame) {
  //           var H5P = nestedIFrame.contentWindow.H5P;
  //           if (H5P && H5P.externalDispatcher) {

  //             // var nestedIFrameHeight =
  //             // nestedIFrame.contentWindow.document.body.scrollHeight;
  //             // parentIFrame.style.height = nestedIFrameHeight + "px";
  //             //ILD.init(H5P);
  //             window.console.log(H5P);

  //             H5P.setFinished = function (contentId, score, maxScore, time) {
  //              //hvp Funktion hijacken, damit die Grade nicht doppelt eingetragen wird
  //             };
  //             H5P.externalDispatcher.on("xAPI", this._hvpprogress.bind(this));
  //             var instance = H5P.instances[0];
  //             H5P.trigger(instance, 'resize');
  // var nestedIFrameHeight =
  // nestedIFrame.contentWindow.document.body.scrollHeight;
  // parentIFrame.style.height = nestedIFrameHeight + "px";
  //           } else {
  //             setTimeout(this._hvpListener.bind(this), 50);
  //           }
  //         } else {
  //           setTimeout(this._hvpListener.bind(this), 50);
  //         }
  //       } else {
  //         setTimeout(this._hvpListener.bind(this), 50);
  //       }
  //     });
  //   }
  // }

  _hvpprogress(event) {
    window.console.log(event);

    if (event.getVerb() === "completed" || event.getVerb() === "answered") {
      var contentId = event.getVerifiedStatementValue([
        "object",
        "definition",
        "extensions",
        "http://h5p.org/x-api/h5p-local-content-id",
      ]);
      var score = event.getScore();
      var maxScore = event.getMaxScore();
      var statement = event.data.statement;
      var isChild =
        statement.context &&
        statement.context.contextActivities &&
        statement.context.contextActivities.parent &&
        statement.context.contextActivities.parent[0] &&
        statement.context.contextActivities.parent[0].id;


      this.reactive.dispatch(
        "updateSectionprogress",
        this.id,
        contentId,
        score,
        maxScore
      );

    }
  }
}
