/*! Timeline Drop - by kickdrop.me */
(function(){jQuery(document).ready(function(i){var t;return t=function(t){return i(".sboard.timeline.animated .timeline-row").each(function(t){var n,o;return n=i(this).position().top+i(this).outerHeight(),o=i(window).scrollTop()+i(window).height(),o>.7*n?i(this).addClass("active"):void 0})},t(),i(window).scroll(function(){return t()})})}).call(this);
