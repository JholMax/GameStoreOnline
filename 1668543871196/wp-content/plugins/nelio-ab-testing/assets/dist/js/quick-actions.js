!function(){var e={n:function(t){var n=t&&t.__esModule?function(){return t.default}:function(){return t};return e.d(n,{a:n}),n},d:function(t,n){for(var o in n)e.o(n,o)&&!e.o(t,o)&&Object.defineProperty(t,o,{enumerable:!0,get:n[o]})},o:function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},r:function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})}},t={};!function(){"use strict";e.r(t),window.wp.domReady;var n,o=window.wp.apiFetch,a=e.n(o),r=!1;function i(e){e.preventDefault();var t=n.postId,o=n.postType,i=n.experimentType;t&&o&&i&&(r||(r=!0,a()({path:"/nab/v1/experiment",method:"post",data:{type:i,addTestedPostScopeRule:!0}}).then((function(e){var n={id:e.id,type:i,alternatives:[{id:"control",attributes:{postId:t,postType:o}}]};a()({path:"/nab/v1/experiment/".concat(n.id),method:"PUT",data:n}).then((function(e){window.location.href=e.experimentUrl}))}))))}function d(e){if(e.preventDefault(),!r){r=!0;var t=n.postId,o=n.postType,i=n.currentUrl;a()({path:"/nab/v1/experiment",method:"post",data:{type:"nab/heatmap",addTestedPostScopeRule:!1}}).then((function(e){var n={id:e.id,type:"nab/heatmap",trackingMode:t?"post":"url",trackedPostId:t||void 0,trackedPostType:t?o:void 0,trackedUrl:t?void 0:i};a()({path:"/nab/v1/experiment/".concat(n.id),method:"PUT",data:n}).then((function(e){window.location.href=e.experimentUrl}))}))}}window.initNabQuickActions=function(e){n=e;var t=document.querySelector("#wp-admin-bar-nelio-ab-testing-experiment-create a"),o=document.querySelector("#wp-admin-bar-nelio-ab-testing-heatmap-create a");t&&t.addEventListener("click",i),o&&o.addEventListener("click",d)}}();var n=nab="undefined"==typeof nab?{}:nab;for(var o in t)n[o]=t[o];t.__esModule&&Object.defineProperty(n,"__esModule",{value:!0})}();