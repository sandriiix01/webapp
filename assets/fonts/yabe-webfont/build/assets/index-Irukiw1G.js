import{U as f}from"./app-B90gRHc9.js";function m(o,u,r,e,a,s){var n=Math.round(Math.abs(o)/u);return s?n<=1?a:"in "+n+" "+r+"s":n<=1?e:n+" "+r+"s ago"}var t=[{max:276e4,value:6e4,name:"minute",past:"a minute ago",future:"in a minute"},{max:72e6,value:36e5,name:"hour",past:"an hour ago",future:"in an hour"},{max:5184e5,value:864e5,name:"day",past:"yesterday",future:"tomorrow"},{max:24192e5,value:6048e5,name:"week",past:"last week",future:"in a week"},{max:28512e6,value:2592e6,name:"month",past:"last month",future:"in a month"}],l=function(u,r){var e=Date.now()-u.getTime();if(Math.abs(e)<6e4)return"just now";for(var a=0;a<t.length;a++)if(Math.abs(e)<t[a].max||r&&t[a].name===r)return m(e,t[a].value,t[a].name,t[a].past,t[a].future,e<0);return m(e,31536e6,"year","last year","in a year",e<0)};const h=f(l);export{h as a};
