!function(){var e={n:function(t){var n=t&&t.__esModule?function(){return t.default}:function(){return t};return e.d(n,{a:n}),n},d:function(t,n){for(var r in n)e.o(n,r)&&!e.o(t,r)&&Object.defineProperty(t,r,{enumerable:!0,get:n[r]})},o:function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},r:function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})}},t={};!function(){"use strict";e.r(t),e.d(t,{heatmapEditor:function(){return n}});var n={};e.r(n),e.d(n,{initializeExperimentEditor:function(){return U}});var r=window.wp.element,a=window.wp.data,o=window.wp.domReady,i=e.n(o),l=window.lodash,s=window.nab.editor,c=window.nab.conversionActionLibrary,u=window.nab.data,d=window.nab.experimentLibrary,m=window.wp.components,p=window.wp.i18n,f=window.wp.notices,g=window.nab.utils,v=window.nab.date;function E(e,t){return void 0===e&&(e=""),!!t&&(0!==e.indexOf(t)?(0,p.sprintf)(
/* translators: a URL */
(0,p._x)("URL doesn’t start with your WordPress’ home URL (i.e. “%s”)","text","nelio-ab-testing"),t):!!(0,g.isUrlFragmentInvalid)(e.replace(t,""))&&(0,p._x)("Please type in a valid URL to track","user","nelio-ab-testing"))}var b=function(){var e=w(),t=y();return(0,r.useEffect)((function(){var n=e.homeUrl,r=e.mode,a=e.name,o=e.postId,i=e.postType,l=e.startDate,s=e.status,c=e.url;if("running"!==s)if((0,g.isEmpty)(a))t("draft",(0,p._x)("Please name your Heatmap test","user","nelio-ab-testing"));else{if("post"===r&&!o)switch(i){case"product":return void t("draft",(0,p._x)("Please select the product to track","user","nelio-ab-testing"));case"post":return void t("draft",(0,p._x)("Please select the post to track","user","nelio-ab-testing"));case"page":return void t("draft",(0,p._x)("Please select the page to track","user","nelio-ab-testing"));default:return void t("draft",(0,p._x)("Please select a page to track","user","nelio-ab-testing"))}"url"===r&&E(c,n)?t("draft",E(c,n)||""):"ready"!==s&&("scheduled"!==s||(0,v.isInTheFuture)(l)?"scheduled"!==s&&t("ready"):t("ready"))}}),[JSON.stringify(e)]),null},w=function(){return(0,a.useSelect)((function(e){var t,n,r,a,o=e(u.STORE_NAME).getPluginSetting,i=e(s.STORE_NAME),l=i.getExperimentAttribute,c=i.getHeatmapAttribute;return{homeUrl:o("homeUrl"),mode:null!==(t=c("trackingMode"))&&void 0!==t?t:"post",name:l("name"),postId:null!==(n=c("trackedPostId"))&&void 0!==n?n:0,postType:null!==(r=c("trackedPostType"))&&void 0!==r?r:"page",startDate:l("startDate"),status:l("status"),url:null!==(a=c("trackedUrl"))&&void 0!==a?a:""}}))},y=function(){var e=(0,a.useDispatch)(s.STORE_NAME),t=e.setDraftStatusRationale,n=e.setExperimentData;return function(e,r){n({status:e}),t(null!=r?r:"")}},_=window.React;function h(e){return e.startsWith("{{/")?{type:"componentClose",value:e.replace(/\W/g,"")}:e.endsWith("/}}")?{type:"componentSelfClosing",value:e.replace(/\W/g,"")}:e.startsWith("{{")?{type:"componentOpen",value:e.replace(/\W/g,"")}:{type:"string",value:e}}function x(e,t){let n,r,a=[];for(let o=0;o<e.length;o++){const i=e[o];if("string"!==i.type){if(void 0===t[i.value])throw new Error(`Invalid interpolation, missing component node: \`${i.value}\``);if("object"!=typeof t[i.value])throw new Error(`Invalid interpolation, component node must be a ReactElement or null: \`${i.value}\``);if("componentClose"===i.type)throw new Error(`Missing opening component token: \`${i.value}\``);if("componentOpen"===i.type){n=t[i.value],r=o;break}a.push(t[i.value])}else a.push(i.value)}if(n){const o=function(e,t){const n=t[e];let r=0;for(let a=e+1;a<t.length;a++){const e=t[a];if(e.value===n.value){if("componentOpen"===e.type){r++;continue}if("componentClose"===e.type){if(0===r)return a;r--}}}throw new Error("Missing closing component token `"+n.value+"`")}(r,e),i=x(e.slice(r+1,o),t),l=(0,_.cloneElement)(n,{},i);if(a.push(l),o<e.length-1){const n=x(e.slice(o+1),t);a=a.concat(n)}}return a=a.filter(Boolean),0===a.length?null:1===a.length?a[0]:(0,_.createElement)(_.Fragment,null,...a)}function k(e){const{mixedString:t,components:n,throwErrors:r}=e;if(!n)return t;if("object"!=typeof n){if(r)throw new Error(`Interpolation Error: unable to process \`${t}\` because components is not an object`);return t}const a=function(e){return e.split(/(\{\{\/?\s*\w+\s*\/?\}\})/g).map(h)}(t);try{return x(a,n)}catch(e){if(r)throw new Error(`Interpolation Error: unable to process \`${t}\` because of error \`${e.message}\``);return t}}var N,S=window.nab.components,T=function(e){var t=e.className,n=e.postId,a=e.postType,o=e.setAttributes,i=P().map((function(e){return{value:e.name,label:e.labels.singular_name}})),l=a||"page";return r.createElement("div",{className:t},r.createElement(m.SelectControl,{className:"".concat(t,"-type-selector"),value:l,options:i,onChange:function(e){return o({postId:0,postType:e})}}),r.createElement(S.PostSearcher,{className:"".concat(t,"-selector"),type:l,value:n,perPage:10,onChange:function(e){return o({postId:e,postType:l})}}))},P=function(){return(0,a.useSelect)((function(e){var t=e(u.STORE_NAME).getKindEntities,n=[{kind:"entity",name:"page",labels:{singular_name:(0,p._x)("Page","text","nelio-ab-testing")}},{kind:"entity",name:"post",labels:{singular_name:(0,p._x)("Post","text","nelio-ab-testing")}}],r=t();return(r=(r=(0,g.isEmpty)(r)?n:r).filter((function(e){return"entity"===e.kind}))).filter((function(e){return"attachment"!==e.name}))}))},I=function(){var e=R(),t=e[0],n=e[1],a=t.mode,o=t.postId,i=t.postType,l=t.url,s=t.urlError;return r.createElement("div",{className:"nab-edit-experiment__alternative-section"},r.createElement("h2",null,k({mixedString:(0,p.sprintf)(
/* translators: dashicon */
(0,p._x)("%s WordPress Page","text","nelio-ab-testing"),"{{icon}}{{/icon}}"),components:{icon:r.createElement(m.Dashicon,{className:"nab-alternative-section__title-icon",icon:"welcome-view-site"})}})),r.createElement("div",{className:"nab-tracked-element"},"url"!==a?r.createElement(r.Fragment,null,r.createElement("div",{className:"nab-tracked-element__p"},(0,p._x)("Select the page you want to generate a heatmap for:","user","nelio-ab-testing")),r.createElement(T,{className:"nab-tracked-element__post",postId:null!=o?o:0,postType:i,setAttributes:n})):r.createElement(r.Fragment,null,r.createElement("div",{className:"nab-tracked-element__p"},(0,p._x)("Type in the URL of the page you want to generate a heatmap for:","user","nelio-ab-testing")),r.createElement("div",{className:"nab-tracked-element__url"},r.createElement(m.TextControl,{className:"nab-tracked-element__url-value",value:l,onChange:function(e){return n({url:e})},placeholder:(0,p._x)("URL…","text","nelio-ab-testing")}),r.createElement("div",{className:"nab-tracked-element__url-preview"},r.createElement(m.ExternalLink,{className:"components-button is-secondary",href:s?void 0:l,disabled:!!s},(0,p._x)("View","command","nelio-ab-testing")))),!!s&&r.createElement("div",{className:"nab-tracked-element__p"},r.createElement(S.ErrorText,{value:s}))),r.createElement("div",{className:"nab-tracked-element__p"},r.createElement(m.CheckboxControl,{className:"nab-tracked-element__mode",label:(0,p._x)("Track heatmap of a page specified using its URL.","command","nelio-ab-testing"),checked:"url"===a,onChange:function(e){return n({mode:e?"url":"post"})}}))))},R=function(){var e=(0,a.useSelect)((function(e){var t=e(u.STORE_NAME).getPluginSetting,n=e(s.STORE_NAME).getHeatmapAttribute,r=n("trackedUrl")||"",a=t("homeUrl");return{mode:n("trackingMode")||"post",postId:n("trackedPostId")||void 0,postType:n("trackedPostType")||"page",url:r,urlError:E(r,a)}})),t=(0,a.useDispatch)(s.STORE_NAME).setHeatmapData;return[e,function(n){var r,a,o,i;return t({trackingMode:null!==(r=n.mode)&&void 0!==r?r:e.mode,trackedPostId:null!==(a=n.postId)&&void 0!==a?a:e.postId,trackedPostType:null!==(o=n.postType)&&void 0!==o?o:e.postType,trackedUrl:null!==(i=n.url)&&void 0!==i?i:e.url})}]},M=null!==(N=null===f.store||void 0===f.store?void 0:f.store.name)&&void 0!==N?N:"core/notices",O=function(){var e=A(),t=(0,a.useDispatch)(M).removeNotice;return r.createElement("div",{className:"nab-edit-experiment-layout"},r.createElement(b,null),r.createElement(s.Header,null),r.createElement("div",{className:"nab-edit-experiment-layout__body"},r.createElement("div",{className:"nab-edit-experiment-layout__content",role:"region","aria-label":(0,p._x)("Editor content","text","nelio-ab-testing"),tabIndex:-1},!(0,g.isEmpty)(e)&&r.createElement(m.NoticeList,{className:"nab-edit-experiment-layout__notices",notices:e,onRemove:t}),r.createElement(s.ExperimentName,null),r.createElement(I,null)),r.createElement(s.Sidebar,null)))},A=function(){return(0,a.useSelect)((function(e){return e(M).getNotices()}))},C=function(e){var t=e.experimentId,n=(0,a.useSelect)((function(e){return e(u.STORE_NAME).getExperiment(t)}));return n?r.createElement(r.StrictMode,null,r.createElement(s.EditorProvider,{experiment:n},r.createElement(O,null))):null};function U(e,t){var n=document.getElementById(e);(0,d.registerCoreExperiments)(),(0,c.registerCoreConversionActions)(),(0,r.render)(r.createElement(C,{experimentId:t}),n)}function D(){var e=window.innerHeight,t=document.getElementById("wpadminbar"),n=t?t.getBoundingClientRect().height:0;(0,(0,a.dispatch)(u.STORE_NAME).setPageAttribute)("sidebarDimensions",{top:n,height:"".concat(e-n,"px"),applyFix:782<=window.innerWidth})}window.addEventListener("resize",(0,l.debounce)(D,100)),i()(D)}();var n=nab="undefined"==typeof nab?{}:nab;for(var r in t)n[r]=t[r];t.__esModule&&Object.defineProperty(n,"__esModule",{value:!0})}();