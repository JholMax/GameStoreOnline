!function(){var e=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},n={};!function(){"use strict";e(n);var t,o,a,i={},d=[];function r(e){var n=function(){};window.nabAddSingleAction="fake"===e?n:c,window.nabDoSingleAction="fake"===e?n:u}function c(e,n){try{if(d.includes(e))return void n();var t=i[e]||[];t.push(n),i[e]=t}catch(e){}}function u(e){if(!d.includes(e)){d.push(e);var n=i[e]||[];for(n.reverse();n.length;){var t=n.pop();null==t||t()}}}function l(){var e=document.getElementById("nab-alternative-loader-style");(null==e?void 0:e.parentNode)&&e.parentNode.removeChild(e);try{var n=document.createEvent("HTMLEvents");n.initEvent("resize",!0,!1),document.dispatchEvent(n)}catch(e){}}-1===(o=window.navigator.userAgent||"").indexOf("MSIE ")&&-1===o.indexOf("Trident/")?(function(){var e;if(/\bnabstaging\b/.test(document.location.search)&&(document.cookie="nabIsStagingSite=true; path=/",null===(e=window.history)||void 0===e?void 0:e.replaceState)){var n=document.location.href;n=/\bnabstaging&/.test(n)?n.replace("nabstaging&",""):/&nabstaging\b/.test(n)?n.replace("&nabstaging",""):n.replace("?nabstaging",""),window.history.replaceState({},"",n)}}(),(t=document.getElementById("nab-alternative-loader-style"))&&(t.innerHTML="",t.appendChild(document.createTextNode('html::before, body::before { background:#fff; content:""; width:100vw; height:100vh; position:fixed; z-index:999999999; }'))),c("valid-content",l),a=function(){return setTimeout((function(){return u("valid-content")}),2500)},"undefined"!=typeof document&&("complete"!==document.readyState&&"interactive"!==document.readyState?document.addEventListener("DOMContentLoaded",a):a()),r()):r("fake")}();var t=nab="undefined"==typeof nab?{}:nab;for(var o in n)t[o]=n[o];n.__esModule&&Object.defineProperty(t,"__esModule",{value:!0})}();