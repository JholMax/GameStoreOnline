!function(){var e=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},n={};!function(){"use strict";e(n);var t,o;function r(e){if(e.length){var n=document.cookie.match(/nabAlternative=[0-9]+/)||[""],t=Number.parseInt(n[0].split("=")[1]);if(!isNaN(t)){var o=document.location.protocol+"//"+document.location.hostname,r=Array.from(document.querySelectorAll("a")).filter((function(e){return e.href})).filter((function(e){return 0===e.href.indexOf(o)})).filter((function(e){return-1===e.href.indexOf("#")})).filter((function(e){return-1===e.href.indexOf("/wp-content/")}));(e=function(e,n,t){if(t||2===arguments.length)for(var o,r=0,i=n.length;r<i;r++)!o&&r in n||(o||(o=Array.prototype.slice.call(n,0,r)),o[r]=n[r]);return e.concat(o||Array.prototype.slice.call(n))}([],e,!0).sort((function(e){return"alt-urls"===e.type?-1:1}))).forEach((function(e){var n="alt-urls"===e.type?e.altUrls:e.scope;r.filter((function(e){return-1===e.href.indexOf("nab=")})).filter((function(e){return function(e,n){return n=n.toLowerCase(),e.some((function(e){return(o=e).startsWith("*")&&o.endsWith("*")?n.includes((t=e).substring(1,t.length-1)):n===e;var t,o}))}(n,e.href)})).forEach((function(n){var o=t%e.altCount,r="alt-urls"===e.type?e.altUrls[o]:n.href;n.href=r.includes("?")?"".concat(r,"&nab=").concat(o):"".concat(r,"?nab=").concat(o)}))}))}}}o=function(){var e;(e=window)&&"object"==typeof e&&"nabPreloadConfig"in e&&r(window.nabPreloadConfig)},"undefined"!=typeof document&&("complete"!==document.readyState&&"interactive"!==document.readyState?document.addEventListener("DOMContentLoaded",o):o()),setTimeout((function(){var e;(e=window)&&"object"==typeof e&&"nabDoSingleAction"in e&&window.nabDoSingleAction("valid-content")}),1),(null===(t=window.history)||void 0===t?void 0:t.replaceState)&&window.history.replaceState({},"",document.location.href.replace(/([?&])nab=[0-9]+/,"$1").replace("?&","?").replace("&&","&").replace(/[?&]#/,"#").replace(/[?&]$/,""))}();var t=nab="undefined"==typeof nab?{}:nab;for(var o in n)t[o]=n[o];n.__esModule&&Object.defineProperty(t,"__esModule",{value:!0})}();