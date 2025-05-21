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
  const [log, setLog] = (0,react__WEBPACK_IMPORTED_MODULE_0__.useState)('');
  const handleRunScraper = async () => {
    setIsRunning(true);
    setLog('Running scraper...');
    try {
      const response = await fetch(calendarScraperAjax.ajax_url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams({
          action: 'run_calendar_scraper',
          _ajax_nonce: calendarScraperAjax.nonce
        })
      });
      const data = await response.json();
      if (data.success) {
        setLog(data.data.message);
      } else {
        setLog(data.data?.message || 'Something went wrong.');
      }
    } catch (error) {
      setLog('Error: ' + error.message);
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
              children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("label", {
                htmlFor: "season-select",
                className: "form-label",
                children: "Select Season:"
              }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)("select", {
                id: "season-select",
                name: "season",
                className: "form-select",
                children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("option", {
                  value: "",
                  children: "-- Select Season --"
                }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("option", {
                  value: "2025",
                  children: "2025"
                }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("option", {
                  value: "2024",
                  children: "2024"
                }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("option", {
                  value: "2023",
                  children: "2023"
                }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("option", {
                  value: "2022-2024",
                  children: "2022\u20132024"
                }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("option", {
                  value: "2020-2024",
                  children: "2020\u20132024"
                })]
              })]
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)("div", {
              className: "form-section",
              children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("label", {
                htmlFor: "venue",
                className: "form-label",
                children: "Link Structure:"
              }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("input", {
                type: "text",
                id: "link-structure",
                name: "link-structure",
                className: "form-input",
                placeholder: "Enter link structure name"
              })]
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)("div", {
              className: "form-section",
              children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("label", {
                htmlFor: "venue",
                className: "form-label",
                children: "Venue:"
              }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("input", {
                type: "text",
                id: "venue",
                name: "venue",
                className: "form-input",
                placeholder: "Enter venue name"
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
                value: "0",
                max: "100"
              })
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("div", {
              id: "scraper-log",
              style: {
                marginTop: '1rem'
              },
              children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("pre", {
                children: log
              })
            })]
          })
        }), activeTab === 'sheet-colors' && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("div", {
          id: "sheet-colors",
          className: "tab-section"
        }), activeTab === 'log-state' && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("div", {
          id: "log-state",
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