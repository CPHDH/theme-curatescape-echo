// ported 2.0
const curatescapeMap = document.querySelector('curatescape-map');
const overlay = document.querySelector("#multi-map-overlay");
const container = document.querySelector("#multi-map-container") || false;
const showmap = document.querySelector("#show-multi-map") || false;
const showmap_with_marker = document.querySelectorAll("[data-item-id]");
let requested_marker_id = null;

// OPEN MAP
const openMultiMap = (requested_id = null) => {
  requested_marker_id = requested_id;
  if (!showmap.classList.contains("open")) {
    showmap.classList.remove("pulse");
    showmap.classList.add("open");
  }
  if (!container.classList.contains("open")) {
    container.classList.add("open");
  }
  if (!overlay.classList.contains("open")) {
    overlay.classList.add("open");
  }
  if(requested_marker_id){
    let markerRequest = new CustomEvent("markerRequest", { "detail": requested_marker_id });
    document.dispatchEvent(markerRequest);
  }
};
// CLOSE MAP
const closeMultiMap = () => {
  if (showmap.classList.contains("open")) {
    showmap.classList.remove("open");
  }
  if (container.classList.contains("open")) {
    container.classList.remove("open");
  }
  if (overlay.classList.contains("open")) {
    overlay.classList.remove("open");
  }
  if (requested_marker_id) {
    let open_origin = document.querySelector(
      '[data-item-id="' + requested_marker_id + '"]'
    );
    open_origin.focus();
  } else {
    showmap.focus();
  }
};
// MAIN
const multiMap = ()=>{
  overlay.addEventListener("click", (e) => {
    if (e.target.classList.contains("open")) {
      closeMultiMap();
    }
  });
  showmap.addEventListener("click", (e) => {
    if (e.target.classList.contains("open")) {
      closeMultiMap();
    } else {
      openMultiMap();
    }
  });
  showmap_with_marker.forEach((link) => {
    // ensure map markers are available
    let retry = 0;
    const waitInterval = 100;
    const maxAttempts = 30;
    link.addEventListener("click", (e) => {
      e.preventDefault();
      if(
        curatescapeMap && 
        curatescapeMap.dataset.status && 
        curatescapeMap.dataset.status == 'listening' &&
        curatescapeMap.dataset.markers && 
        curatescapeMap.dataset.markers == 'loaded'
      ){
        openMultiMap(link.dataset.itemId);
        // console.log( 'Successful attempt: ' + retry );
        retry = 0;
      } else {
        if( retry < maxAttempts ){
          retry++;
          // console.log( 'Retry attempt: ' + retry );
          setTimeout(() => link.click(), waitInterval);
        } else {
          // console.log( 'Unable to open to specific marker' );
          openMultiMap();
        }
      }
    });
  });
}
// INIT
let isReady = setInterval(() => {
  if (document.readyState === 'complete') {
    clearInterval(isReady);
    multiMap();
  }
}, 100);
