/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./node_modules/react-dom/client.js":
/*!******************************************!*\
  !*** ./node_modules/react-dom/client.js ***!
  \******************************************/
/***/ ((__unused_webpack_module, exports, __webpack_require__) => {



var m = __webpack_require__(/*! react-dom */ "react-dom");
if (false) // removed by dead control flow
{} else {
  var i = m.__SECRET_INTERNALS_DO_NOT_USE_OR_YOU_WILL_BE_FIRED;
  exports.createRoot = function(c, o) {
    i.usingClientEntryPoint = true;
    try {
      return m.createRoot(c, o);
    } finally {
      i.usingClientEntryPoint = false;
    }
  };
  exports.hydrateRoot = function(c, h, o) {
    i.usingClientEntryPoint = true;
    try {
      return m.hydrateRoot(c, h, o);
    } finally {
      i.usingClientEntryPoint = false;
    }
  };
}


/***/ }),

/***/ "./src/App.js":
/*!********************!*\
  !*** ./src/App.js ***!
  \********************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _App_scss__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./App.scss */ "./src/App.scss");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__);



const App = () => {
  const [activeTab, setActiveTab] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)('main');
  const [isRunning, setIsRunning] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const [progress, setProgress] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(0);
  const [log, setLog] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)({
    message: '',
    matches: []
  });
  const [formData, setFormData] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)({
    season: '42024',
    linkStructure: 'https://www.bordtennisportalen.dk/DBTU/HoldTurnering/Stilling/#4,{season},{pool},{group},{region},,,,',
    venue: '',
    region: '4004',
    // Default to ØST (Sjælland, Lolland F.)
    ageGroup: '4006',
    // Default to Senior
    pool: '14822' // Default to Pool
  });
  const [errors, setErrors] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)({});
  const [showDropdowns, setShowDropdowns] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const [pools, setPools] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)([]);
  const seasons = window.calendarScraperAjax?.seasons || [];
  const regions = window.calendarScraperAjax?.regions || [];
  const ageGroups = window.calendarScraperAjax?.age_groups || [];

  // Validation function
  const validateForm = () => {
    const newErrors = {};

    // Validate Season
    if (!formData.season) {
      newErrors.season = 'Please select a season.';
    }

    // Validate Link Structure
    const linkStructureRegex = /^https:\/\/www\.bordtennisportalen\.dk\/DBTU\/HoldTurnering\/Stilling\/#4,\{season\},\{pool\},\{group\},\{region\},,,,$/;
    if (!formData.linkStructure) {
      newErrors.linkStructure = 'Link structure is required.';
    } else if (!linkStructureRegex.test(formData.linkStructure)) {
      newErrors.linkStructure = 'Link structure must match the expected format.';
    }

    // Validate Venue
    if (!formData.venue.trim()) {
      newErrors.venue = 'Venue name is required.';
    }
    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  // Handle input changes
  const handleInputChange = e => {
    const {
      name,
      value
    } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
    if (errors[name]) {
      setErrors(prev => ({
        ...prev,
        [name]: ''
      }));
    }
  };

  // Fetch dropdown options from DB when season, region, or age group changes
  (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    if (formData.season) {
      fetch(`${calendarScraperAjax.ajax_url}?action=get_tournament_options&season=${formData.season}&region=${formData.region}&age_group=${formData.ageGroup}&_ajax_nonce=${calendarScraperAjax.nonce}`, {
        method: 'GET',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        }
      }).then(response => response.json()).then(data => {
        if (data.success) {
          setPools(data.data.pools || []);
        }
      }).catch(error => console.error('Error fetching pools:', error));
    } else {
      setPools([]);
    }
  }, [formData.season, formData.region, formData.ageGroup]);

  // Simulate progress while scraping
  (0,react__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    let interval;
    if (isRunning) {
      setProgress(0);
      interval = setInterval(() => {
        setProgress(prev => {
          if (prev >= 90) {
            return 90;
          }
          return prev + 10;
        });
      }, 500);
    }
    return () => clearInterval(interval);
  }, [isRunning]);
  const handleRunScraper = async () => {
    if (!validateForm()) {
      setLog({
        message: 'Please fix the errors in the form.',
        matches: []
      });
      return;
    }
    setIsRunning(true);
    setLog({
      message: 'Running scraper...',
      matches: []
    });
    try {
      const response = await fetch(calendarScraperAjax.ajax_url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams({
          action: 'run_calendar_scraper',
          _ajax_nonce: calendarScraperAjax.nonce,
          season: formData.season,
          link_structure: formData.linkStructure,
          venue: formData.venue,
          region: formData.region,
          age_group: formData.ageGroup,
          pool: formData.pool
        })
      });
      const data = await response.json();
      if (data.success) {
        if (data.data.error) {
          setLog({
            message: data.data.error,
            matches: []
          });
          setProgress(0);
        } else if (Array.isArray(data.data.message) && data.data.message.length === 0) {
          setLog({
            message: 'No matches found for venue ' + formData.venue,
            matches: []
          });
          setProgress(100);
        } else {
          setLog({
            message: '',
            matches: data.data.message
          });
          setProgress(100);
        }
      } else {
        setLog({
          message: data.data?.message || 'Something went wrong.',
          matches: []
        });
        setProgress(0);
      }
    } catch (error) {
      setLog({
        message: 'Error: ' + error.message,
        matches: []
      });
      setProgress(0);
    } finally {
      setIsRunning(false);
    }
  };
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)("div", {
    className: "wrap",
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("h1", {
      children: "Calendar Sync Scraper"
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)("div", {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)("h2", {
        className: "nav-tab-wrapper",
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("a", {
          href: "#main",
          className: `nav-tab ${activeTab === 'main' ? 'nav-tab-active' : ''}`,
          onClick: e => {
            e.preventDefault();
            setActiveTab('main');
          },
          children: "Main"
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("a", {
          href: "#sheet-colors",
          className: `nav-tab ${activeTab === 'sheet-colors' ? 'nav-tab-active' : ''}`,
          onClick: e => {
            e.preventDefault();
            setActiveTab('sheet-colors');
          },
          children: "Sheet Colors"
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("a", {
          href: "#log-state",
          className: `nav-tab ${activeTab === 'log-state' ? 'nav-tab-active' : ''}`,
          onClick: e => {
            e.preventDefault();
            setActiveTab('log-state');
          },
          children: "Log State"
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("a", {
          href: "#settings",
          className: `nav-tab ${activeTab === 'settings' ? 'nav-tab-active' : ''}`,
          onClick: e => {
            e.preventDefault();
            setActiveTab('settings');
          },
          children: "Settings"
        })]
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)("div", {
        id: "tab-content",
        children: [activeTab === 'main' && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("div", {
          id: "main",
          className: "tab-section active",
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)("form", {
            id: "calendar-scraper-form",
            onSubmit: e => e.preventDefault(),
            children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)("div", {
              className: "form-section",
              children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)("div", {
                className: "form-control-group",
                children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("label", {
                  htmlFor: "season-select",
                  className: "form-label",
                  children: "Select Season:"
                }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)("select", {
                  id: "season-select",
                  name: "season",
                  className: `form-select ${errors.season ? 'has-error' : ''}`,
                  value: formData.season,
                  onChange: handleInputChange,
                  children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("option", {
                    value: "",
                    children: "-- Select Season --"
                  }), seasons.map(season => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("option", {
                    value: season.season_value,
                    children: season.season_name
                  }, season.season_value))]
                })]
              }), errors.season && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("span", {
                className: "error-message",
                children: errors.season
              }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("a", {
                href: "#",
                className: "advanced-link",
                onClick: e => {
                  e.preventDefault();
                  setShowDropdowns(!showDropdowns);
                },
                children: "Advanced Settings"
              })]
            }), showDropdowns && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)("div", {
              className: "form-section dropdown-row",
              children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("label", {
                htmlFor: "region-select",
                className: "form-label"
              }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)("select", {
                id: "region-select",
                name: "region",
                className: `form-select ${errors.region ? 'has-error' : ''}`,
                value: formData.region,
                onChange: handleInputChange,
                children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("option", {
                  value: "",
                  children: "-- Select Region --"
                }), regions.map(region => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("option", {
                  value: region.region_value,
                  children: region.region_name
                }, region.region_value))]
              }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)("select", {
                id: "age-group-select",
                name: "ageGroup",
                className: `form-select ${errors.ageGroup ? 'has-error' : ''}`,
                value: formData.ageGroup,
                onChange: handleInputChange,
                children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("option", {
                  value: "",
                  children: "-- Select Age Group --"
                }), ageGroups.map(ageGroup => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("option", {
                  value: ageGroup.age_group_value,
                  children: ageGroup.age_group_name
                }, ageGroup.age_group_value))]
              }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)("select", {
                id: "pool-select",
                name: "pool",
                className: `form-select ${errors.pool ? 'has-error' : ''}`,
                value: formData.pool,
                onChange: handleInputChange,
                children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("option", {
                  value: "",
                  children: "-- Select Pool --"
                }), pools.map(pool => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)("option", {
                  value: pool.pool_value,
                  children: [pool.tournament_level, " - ", pool.pool_name]
                }, pool.pool_value))]
              })]
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)("div", {
              className: "form-section",
              children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)("div", {
                className: "form-control-group",
                children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("label", {
                  htmlFor: "link-structure",
                  className: "form-label",
                  children: "Link Structure:"
                }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("input", {
                  type: "text",
                  id: "link-structure",
                  name: "linkStructure",
                  className: `form-input ${errors.linkStructure ? 'has-error' : ''}`,
                  value: formData.linkStructure,
                  onChange: handleInputChange,
                  placeholder: "Enter link structure name"
                })]
              }), errors.linkStructure && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("span", {
                className: "error-message",
                children: errors.linkStructure
              })]
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)("div", {
              className: "form-section",
              children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)("div", {
                className: "form-control-group",
                children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("label", {
                  htmlFor: "venue",
                  className: "form-label",
                  children: "Venue:"
                }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("input", {
                  type: "text",
                  id: "venue",
                  name: "venue",
                  className: `form-input ${errors.venue ? 'has-error' : ''}`,
                  value: formData.venue,
                  onChange: handleInputChange,
                  placeholder: "Enter venue name"
                })]
              }), errors.venue && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("span", {
                className: "error-message",
                children: errors.venue
              })]
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("button", {
              type: "button",
              id: "run-scraper-now",
              className: "button button-primary",
              onClick: handleRunScraper,
              disabled: isRunning,
              children: isRunning ? 'Running...' : 'Run Scraper Now'
            }), isRunning && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("div", {
              id: "scraper-progress",
              children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("progress", {
                value: progress,
                max: "100"
              })
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("div", {
              id: "scraper-log",
              className: "scraper-log",
              children: log.message ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("pre", {
                children: log.message
              }) : /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("ul", {
                children: log.matches.map((match, index) => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)("li", {
                  children: ["Match ", match.tid, ": ", match.hjemmehold, " vs ", match.udehold, " at ", match.spillested, " - ", match.resultat]
                }, index))
              })
            })]
          })
        }), activeTab === 'sheet-colors' && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("div", {
          id: "sheet-colors",
          className: "tab-section"
        }), activeTab === 'log-state' && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("div", {
          id: "log-state",
          className: "tab-section"
        }), activeTab === 'settings' && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("div", {
          id: "settings",
          className: "tab-section"
        })]
      })]
    })]
  });
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (App);

/***/ }),

/***/ "./src/App.scss":
/*!**********************!*\
  !*** ./src/App.scss ***!
  \**********************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "react":
/*!************************!*\
  !*** external "React" ***!
  \************************/
/***/ ((module) => {

module.exports = window["React"];

/***/ }),

/***/ "react-dom":
/*!***************************!*\
  !*** external "ReactDOM" ***!
  \***************************/
/***/ ((module) => {

module.exports = window["ReactDOM"];

/***/ }),

/***/ "react/jsx-runtime":
/*!**********************************!*\
  !*** external "ReactJSXRuntime" ***!
  \**********************************/
/***/ ((module) => {

module.exports = window["ReactJSXRuntime"];

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry needs to be wrapped in an IIFE because it needs to be isolated against other modules in the chunk.
(() => {
/*!**********************!*\
  !*** ./src/index.js ***!
  \**********************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var react_dom_client__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! react-dom/client */ "./node_modules/react-dom/client.js");
/* harmony import */ var _App__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./App */ "./src/App.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__);




const rootElement = document.getElementById("calendar-sync-scraper-root");
if (rootElement) {
  const root = (0,react_dom_client__WEBPACK_IMPORTED_MODULE_1__.createRoot)(rootElement);
  root.render(/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_App__WEBPACK_IMPORTED_MODULE_2__["default"], {}));
}
})();

/******/ })()
;
//# sourceMappingURL=index.js.map