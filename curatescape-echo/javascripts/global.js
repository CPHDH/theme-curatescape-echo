const search_btn = document.querySelector("header.primary #search-button");
const search_container = document.querySelector("#header-search-container");
const menu_btn = document.querySelector("header.primary #menu-button");
const menu_container = document.querySelector("#header-menu-container");
const body = document.querySelector("body");
const map_container = document.querySelector("#curatescape-map-canvas");
const multimap_button = document.querySelector("#show-multi-map");
const main = document.querySelector("[role=main]");
const secondary_nav_actives = document.querySelectorAll(".secondary-nav li.active");
const actionButtons = document.querySelector('#social-actions .link-icons');
// APPEND LIVE REGION FOR SCREEN READER ANNOUNCEMENTS
let liveRegion = document.createElement('div');
liveRegion.classList.add('screen-reader');
liveRegion.setAttribute('aria-live','polite');
body.appendChild(liveRegion);
// SCREEN READER ANNOUNCE
const liveRegionAnnounce = (text, region = liveRegion)=>{
  if(!text) return;
  region.textContent = text;
}
// GLOBAL HELPERS
const getCookie = (cookie_name) => {
  let c_name = cookie_name + "=";
  let cookie_decoded = decodeURIComponent(document.cookie);
  let cookie_parts = cookie_decoded.split(";");
  for (let i = 0; i < cookie_parts.length; i++) {
    let c = cookie_parts[i];
    while (c.charAt(0) == " ") {
      c = c.substring(1);
    }
    if (c.indexOf(c_name) == 0) {
      return c.substring(c_name.length, c.length);
    }
  }
  return null;
};
const setCookie = (name, value, exdays = 365) => {
  const d = new Date();
  d.setTime(d.getTime() + exdays * 24 * 60 * 60 * 1000);
  let expires = "expires=" + d.toUTCString();
  document.cookie =
    name + "=" + value + "; " + expires + "; Path=/;SameSite=Strict";
};
const deleteCookie = (name) => {
  document.cookie =
    name + "=; Path=/; Expires=Thu, 01 Jan 1970 00:00:01 GMT;SameSite=Strict;";
};
const checkVisible = (elm, threshold, mode) => {
  threshold = threshold || 0;
  mode = mode || "visible";
  var rect = elm.getBoundingClientRect();
  var viewHeight = Math.max(
    document.documentElement.clientHeight,
    window.innerHeight
  );
  var above = rect.bottom - threshold < 0;
  var below = rect.top - viewHeight + threshold >= 0;
  return mode === "above" ? above : mode === "below" ? below : !above && !below;
};
const safeText = (value) => {
  var d = document.createElement("div");
  d.innerHTML = value;
  return d.innerText;
};
const isEmpty = (value) =>{
  switch (value) {
    case undefined:
      return true;
      break;
    case null:
      return true;
      break;
    case '':
      return true;
      break;
    default:
      return false;
  }
}
// MAP PAUSE/RESUME FUNCTIONS
const pauseInteraction = (map, ignore = false) => {
  if (map && !ignore) {
    map.dragging.disable();
    map.doubleClickZoom.disable();
    map.scrollWheelZoom.disable();
  }
};

const resumeInteraction = (map, scrollable = true, ignore = false) => {
  if (map && !ignore) {
    map.dragging.enable();
    map.doubleClickZoom.enable();
    if (scrollable) map.scrollWheelZoom.enable();
  }
};
// CLOSE FUNCTIONS
const closeMapSingle = () => {
  if (map_container && map_container.classList.contains("fullscreen")) {
    let control_icon = document.querySelector(".leaflet-control-fullscreen a");
    if (control_icon) {
      control_icon.click();
    }
  }
};
const closeMapMulti = () => {
  if (multimap_button && multimap_button.classList.contains("open")) {
    multimap_button.click();
  }
};
const closeSearch = () => {
  if (search_container.classList.contains("active")) {
    search_container.classList.remove("active");
    body.classList.remove("overlay-active");
  }
};
const closeMenu = () => {
  if (menu_container.classList.contains("active")) {
    menu_container.classList.remove("active");
    body.classList.remove("overlay-active");
  }
};
const overlayClick = () => {
  closeSearch();
  closeMenu();
  body.classList.remove("overlay-active");
};
// ESC KEY
document.onkeydown = (e) => {
  e = e || window.event;
  var isEscape = false;
  if ("key" in e) {
    isEscape = e.key === "Escape" || e.key === "Esc";
  } else {
    isEscape = e.keyCode === 27;
  }
  if (isEscape) {
    closeMenu();
    closeSearch();
    closeMapSingle();
    closeMapMulti();
  }
};
// SEARCH BUTTON
search_btn.addEventListener("click", (e) => {
  e.preventDefault();
  body.classList.remove("overlay-active");
  setTimeout(() => {
    closeMenu();
  }, 300);
  search_container.classList.toggle("active");
  let activeSearch = document.querySelector(
    "#header-search-container.active input#header-search"
  );
  setTimeout(() => {
    if (activeSearch) {
      activeSearch.focus();
      body.classList.add("overlay-active");
    }
  }, 300);
});
// MENU BUTTON
menu_btn.addEventListener("click", (e) => {
  e.preventDefault();
  let sublists = document.querySelectorAll("#header-menu-inner li a + ul");
  if (sublists) {
    sublists.forEach((ul) => {
      let link = ul.previousElementSibling;
      if (link) {
        let arrow_btn = document.createElement("span");
        arrow_btn.innerHTML = "&#9656;";
        arrow_btn.addEventListener("click", (e) => {
          ul.classList.toggle("reveal");
          e.currentTarget.classList.toggle("open");
        });
        link.after(arrow_btn);
      }
    });
  }
  body.classList.remove("overlay-active");
  setTimeout(() => {
    closeSearch();
  }, 300);
  menu_container.classList.toggle("active");
  let activeMenu = document.querySelector(
    "#header-menu-container.active #header-menu-inner"
  );
  setTimeout(() => {
    if (activeMenu) {
      activeMenu.focus();
      body.classList.add("overlay-active");
    }
  }, 300);
});
// SMOOTH SCROLL PAST NAV ON SEARCH/QUERY RESULTS
document.onreadystatechange = () => {
  let reduced_motion =
    "matchMedia" in window
      ? window.matchMedia("(prefers-reduced-motion: reduce)").matches
      : false;
  let referrer = document.referrer;
  if (
    "scrollBehavior" in document.documentElement.style &&
    !reduced_motion &&
    !(referrer && !referrer.includes(window.location.origin))
  ) {
    let reduced_motion =
      "matchMedia" in window
        ? window.matchMedia("(prefers-reduced-motion: reduce)").matches
        : false;
    if (main) {
      if ((data = main.dataset) && data.scrollto) {
        let target = document.getElementById(data.scrollto);
        if (target) {
          target.scrollIntoView({ behavior: "smooth" });
          target.focus();
        }
      }
    }
  }
};
// FIX DOUBLE ACTIVE CLASS (https://github.com/omeka/Omeka/issues/952)
if (secondary_nav_actives.length) {
  if (secondary_nav_actives.length > 1) {
    secondary_nav_actives[0].classList.remove("active");
  }
}
// DARK MODE USER SETTINGS MANAGEMENT
const dark_browsercompatible = CSS.supports("color-scheme", "dark"); // @todo: browser compat!!!
const html = document.querySelector("html");
if (dark_browsercompatible) {
  // set initial cookie?
  if (getCookie("neverdarkmode") == null) {
    document.querySelector("html").classList.add("darkallowed");
    setCookie("neverdarkmode", "0");
  }
  // manage user prefs cookie and html class
  document.querySelector("input#dm").addEventListener("change", (e) => {
    if (e.target.checked) {
      html.classList.add("darkallowed");
      html.classList.remove("darkdisabled_user");
      setCookie("neverdarkmode", "0");
    } else {
      html.classList.remove("darkallowed");
      html.classList.add("darkdisabled_user");
      setCookie("neverdarkmode", "1");
    }
  });
} else {
  document.querySelector(".menu-darkmode-container").remove();
}
// VISUAL CONFIRMATION
const visualConfirmation = (buttonobj, icon, icon_alt, duration = 2000)=>{
  // icon swap
  if(icon && icon_alt){
    icon.hidden=true;
    icon_alt.hidden=false;
  }
  // display tooltip
  if(buttonobj.dataset.confirmation){
    buttonobj.setAttribute('data-tooltip',true);
  }
  setTimeout(()=>{
    if(icon && icon_alt){
      icon.hidden=false;
      icon_alt.hidden=true;
    }
    if(buttonobj.dataset.confirmation){
      buttonobj.setAttribute('data-tooltip',false);
    }
    buttonobj.blur();
  }, duration);
}
// SHARE BUTTON (Web Share API)
const doShareButton = (share_btn)=>{
  share_btn.addEventListener('click',()=>{
      let icon = share_btn.querySelector('.default');
      let icon_alt = share_btn.querySelector('.confirmation');
      navigator.share({
        title: share_btn.dataset.title,
        url: window.location.toString(),
      }).then(()=> {
        // visual confirmation
        visualConfirmation(share_btn, icon, icon_alt);
        // screen reader confirmation
        liveRegionAnnounce(share_btn.dataset.confirmation);
      }, 
      (error)=>{
        console.error(error);
      });
  });
}
// COPY BUTTON
const doCopyButton = (copy_btn)=>{
  copy_btn.addEventListener('click',()=>{
    let icon = copy_btn.querySelector('.default');
    let icon_alt = copy_btn.querySelector('.confirmation');
    navigator.clipboard.writeText(window.location.toString()).then(()=>{
        // visual confirmation
        visualConfirmation(copy_btn, icon, icon_alt);
        // screen reader confirmation
        liveRegionAnnounce(copy_btn.dataset.confirmation);
      },
      (error)=>{
        console.error(error);
    });
  });
}
// PRINT BUTTON
const doBeforePrint = (pressed)=>{
  if(pressed !== 0) return;
  console.log(pressed)
  let lazyImages = document.querySelectorAll('img[loading="lazy"]');
  lazyImages.forEach(img => {
    img.setAttribute('loading','eager');
  });
}
// ACTION BUTTONS INIT
if(actionButtons){
  let share_btn = actionButtons.querySelector('.button.share-js');
  let copy_btn = actionButtons.querySelector('.button.copy-js');
  let print_btn = actionButtons.querySelector('.button.print-js');
  if(navigator.share && window.location.protocol == "https:"){
    doShareButton(share_btn);
  }else{
    share_btn.hidden=true;
  }
  if(navigator.clipboard){
    doCopyButton(copy_btn);
  }else{
    copy_btn.hidden=true;
  }
  if(print_btn){
    let pressed = 0;
    print_btn.addEventListener('touchstart',()=>{
      doBeforePrint(pressed);
      pressed++;
    });
    print_btn.addEventListener('mousedown',()=>{
      doBeforePrint(pressed);
      pressed++;
    });
    window.addEventListener('beforeprint', () => {
      // this typically fires too late to be of use 
      // but we'll leave it here for printing sans button, e.g. command+P
      doBeforePrint(pressed);
      pressed++;
    });
  }
  actionButtons.setAttribute('data-loading', false); // fade-in
}
