!function(){var e={n:function(t){var n=t&&t.__esModule?function(){return t.default}:function(){return t};return e.d(n,{a:n}),n},d:function(t,n){for(var a in n)e.o(n,a)&&!e.o(t,a)&&Object.defineProperty(t,a,{enumerable:!0,get:n[a]})},o:function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},r:function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})}},t={};!function(){"use strict";e.r(t),e.d(t,{initPage:function(){return p}});var n=window.wp.element,a=(window.wp.domReady,window.wp.components),i=window.wp.apiFetch,o=e.n(i),l=window.wp.i18n,r=window.nab.components,s=function(){return s=Object.assign||function(e){for(var t,n=1,a=arguments.length;n<a;n++)for(var i in t=arguments[n])Object.prototype.hasOwnProperty.call(t,i)&&(e[i]=t[i]);return e},s.apply(this,arguments)},c=function(e){var t=e.isSubscribed,i=u(e),o=i.isModalOpen,c=i.openModal,d=i.closeModal,v=i.mainActionLabel,g=i.isDeactivating,p=i.deactivate,f=i.cleanAndDeactivate,_=i.reason,x=i.setReason;return n.createElement("div",{className:"nelio-ab-testing-deactivation"},n.createElement(a.Button,{className:"nelio-ab-testing-deactivation__button",onClick:c,isLink:!0},(0,l._x)("Deactivate","command","nelio-ab-testing")),o&&n.createElement(a.Modal,{title:(0,l._x)("Nelio A/B Testing Deactivation","text","nelio-ab-testing"),isDismissable:!g,isDismissible:!g,shouldCloseOnEsc:!g,shouldCloseOnClickOutside:!g,onRequestClose:d},"temporary-deactivation"===_.value?n.createElement(n.Fragment,null,n.createElement(r.RadioControl,{selected:_.value,options:b,onChange:function(e){return x({value:e,details:""})},disabled:g}),n.createElement("br",null)):n.createElement(n.Fragment,null,n.createElement("p",null,(0,l._x)("If you have a moment, please share why you are deactivating Nelio A/B Testing:","user","nelio-ab-testing")),n.createElement(r.RadioControl,{className:"nelio-ab-testing-deactivation__options",selected:_.value,options:m,onChange:function(e){return x({value:e,details:""})},extraValue:_.details,onExtraChange:function(e){return x(s(s({},_),{details:e}))},disabled:g})),t&&"temporary-deactivation"!==_.value&&n.createElement("p",{className:"nelio-ab-testing-deactivation__subscription-warning"},n.createElement(a.Dashicon,{icon:"warning"}),n.createElement("span",null,(0,l._x)("Please keep in mind your subscription to Nelio A/B Testing will remain active after removing the plugin from this site. If you want to unsubscribe from our service, you can do so from the plugin’s Account page before you deactivate the plugin.","user","nelio-ab-testing"))),n.createElement("div",{className:"nelio-ab-testing-deactivation__actions"},"temporary-deactivation"===_.value||g?n.createElement("span",null):n.createElement(a.Button,{isLink:!0,disabled:g,onClick:function(){return f()}},(0,l._x)("Just Delete Data","command","nelio-ab-testing")),n.createElement(a.Button,{isPrimary:!0,disabled:g||"clean-stuff"===_.value,onClick:function(){return"temporary-deactivation"===_.value?p():f(_.details?"".concat(_.value,": ").concat(_.details):_.value)}},v))))},u=function(e){var t=e.cleanNonce,a=e.deactivationUrl,i=(0,n.useState)(g),l=i[0],r=i[1],c=function(){window.location.href=a},u=function(){return r(s(s({},g),{isModalOpen:!1}))},b="temporary-deactivation"===l.reason.value?d(l.isDeactivating):v(l.isDeactivating);return{isModalOpen:l.isModalOpen,openModal:function(){return r(s(s({},g),{isModalOpen:!0}))},closeModal:u,isDeactivating:l.isDeactivating,mainActionLabel:b,deactivate:function(){r(s(s({},l),{reason:g.reason,isDeactivating:!0})),c()},cleanAndDeactivate:function(e){r(s(s({},l),{isDeactivating:!0})),o()({path:"/nab/v1/plugin/clean",method:"POST",data:{reason:e,_nonce:t}}).then(c,u)},reason:l.reason,setReason:function(e){return r(s(s({},l),{reason:e}))}}},d=function(e){return e?(0,l._x)("Deactivating…","text","nelio-ab-testing"):(0,l._x)("Deactivate","command","nelio-ab-testing")},v=function(e){return e?(0,l._x)("Deleting Data…","text","nelio-ab-testing"):(0,l._x)("Submit and Delete Data","command","nelio-ab-testing")},g={isModalOpen:!1,isDeactivating:!1,reason:{value:"temporary-deactivation",details:""}},b=[{value:"temporary-deactivation",label:(0,l._x)("It’s a temporary deactivation","text","nelio-ab-testing")},{value:"clean-stuff",label:(0,l._x)("Delete Nelio A/B Testing’s data and deactivate plugin","text","nelio-ab-testing")}],m=[{value:"plugin-no-longer-needed",label:(0,l._x)("I no longer need the plugin","text","nelio-ab-testing")},{value:"plugin-doesnt-work",label:(0,l._x)("I couldn’t get the plugin to work","text","nelio-ab-testing"),extra:(0,l._x)("What went wrong?","text","nelio-ab-testing")},{value:"better-plugin-found",label:(0,l._x)("I found a better plugin","text","nelio-ab-testing"),extra:(0,l._x)("What’s the plugin’s name?","text","nelio-ab-testing")},{value:"other",label:(0,l._x)("Other","text","nelio-ab-testing"),extra:(0,l._x)("Please share the reason…","user","nelio-ab-testing")}];function p(e){var t=e.isSubscribed,i=e.cleanNonce,o=e.deactivationUrl,l=document.querySelector(".nelio-ab-testing-deactivate-link");l&&(0,n.render)(n.createElement(a.SlotFillProvider,null,n.createElement(c,{isSubscribed:t,deactivationUrl:o,cleanNonce:i}),n.createElement(a.Popover.Slot,null)),l)}}();var n=nab="undefined"==typeof nab?{}:nab;for(var a in t)n[a]=t[a];t.__esModule&&Object.defineProperty(n,"__esModule",{value:!0})}();