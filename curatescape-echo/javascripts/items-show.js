// MEDIA PLAYERS/BUTTONS
const streamingMediaControls = () => {
  document.querySelectorAll(".media-player").forEach((player) => {
    player.dataset.intrinsicSize = player.offsetHeight  + "px";
    player.style.height = '0px';
    player.setAttribute("tabindex", "-1");
  });
  let mediabuttons = document.querySelectorAll(".media-button");
  mediabuttons.forEach((button) => {
    button.addEventListener(
      "click",
      (e) => {
        let index = e.currentTarget.getAttribute("data-index");
        let type = e.currentTarget.getAttribute("data-type");
        let activeicon = document.querySelector(
          '.media-button[data-type="' + type + '"][data-index="' + index + '"]'
        );
        if (activeicon) {
          activeicon.classList.toggle("alt");
          activeicon.parentNode.setAttribute("title", "play");
        }
        let newicon = document.querySelector(
          '.media-button[data-type="' + type + '"][data-index="' + index + '"]'
        );
        if (activeicon !== newicon) {
          newicon.classList.toggle("alt");
          newicon.parentNode.setAttribute("title", "pause");
        }
        let activeplayer = document.querySelector(
          '.media-player.active[data-type="' + type + '"]'
        );
        if (activeplayer) {
          activeplayer.classList.remove("active");
          activeplayer.style.height = 0;
          activeplayer.children[0].pause();
          activeplayer.children[0].setAttribute("tabindex", "-1");
        }
        let newplayer = document.querySelector(
          '.media-player[data-type="' + type + '"][data-index="' + index + '"]'
        );
        if (activeplayer !== newplayer) {
          newplayer.classList.add("active");
          newplayer.children[0].setAttribute("tabindex", "0");
          newplayer.style.height = newplayer.dataset.intrinsicSize;
          newplayer.children[0].play();
          newplayer.children[0].focus();
        }
      },
      false
    );
  });
};
// IMAGE VIEWER / PHOTOSWIPE
const loadPhotoSwipe = (target) => {
  if (!target) {
    return;
  }
  loadCSS(target.dataset.pswpCss);
  loadCSS(target.dataset.pswpSkinCss);
  loadJS(target.dataset.pswp, () => {
    loadJS(target.dataset.pswpUi, () => {
      // console.log("PhotoSwipe initialized...");
      let html =
        '<div class="pswp" tabindex="-1" role="dialog" aria-hidden="true"><div class="pswp__bg"></div><div class="pswp__scroll-wrap"><div class="pswp__container"><div class="pswp__item"></div><div class="pswp__item"></div><div class="pswp__item"></div></div><div class="pswp__ui pswp__ui--hidden"><div class="pswp__top-bar"><div class="pswp__counter"></div><button class="pswp__button pswp__button--close" title="Close (Esc)"></button><button class="pswp__button pswp__button--share" title="Share"></button><button class="pswp__button pswp__button--fs" title="Toggle fullscreen"></button><button class="pswp__button pswp__button--zoom" title="Zoom in/out"></button><div class="pswp__preloader"><div class="pswp__preloader__icn"><div class="pswp__preloader__cut"><div class="pswp__preloader__donut"></div></div></div></div></div><div class="pswp__share-modal pswp__share-modal--hidden pswp__single-tap"><div class="pswp__share-tooltip"></div></div><button class="pswp__button pswp__button--arrow--left" title="Previous (arrow left)"></button><button class="pswp__button pswp__button--arrow--right" title="Next (arrow right)"></button><div class="pswp__caption"><div class="pswp__caption__center"></div></div></div></div></div>';
      var pswp_controls = document.createElement("div");
      pswp_controls.innerHTML = html;
      document.body.appendChild(pswp_controls);
      var pswpElement = document.querySelectorAll(".pswp")[0];
      var options = {
        index: 0,
      };
      var gallery_items = target.querySelectorAll("a.gallery-image");
      var items = [];
      gallery_items.forEach((item, i) => {
        items.push({
          src: item.href,
          h: item.dataset.pswpHeight,
          w: item.dataset.pswpWidth,
          title: item.nextElementSibling.innerHTML,
        });
        item.onclick = (e) => {
          e.preventDefault();
          let gallery = new PhotoSwipe(
            pswpElement,
            PhotoSwipeUI_Default,
            items,
            {
              showHideOpacity: true,
              bgOpacity: 0.925,
              history: false,
              index: i,
            }
          );
          gallery.init();
        };
      });
    });
  });
};
// ANCHOR LINK SMOOTH SCROLLING
const smoothAnchorLinks = () => {
  let reduced_motion =
    "matchMedia" in window
      ? window.matchMedia("(prefers-reduced-motion: reduce)").matches
      : false;
  let options = reduced_motion ? {} : { behavior: "smooth" };
  let anchors = document.querySelectorAll(
    ".rl-toc [href^='#'],.gallery-image[href^='#']"
  );
  anchors.forEach((a) => {
    a.addEventListener("click", (e) => {
      e.preventDefault();
      if (document.querySelector(e.currentTarget.hash)) {
        document.querySelector(e.currentTarget.hash).scrollIntoView(options);
      }
    });
  });
};
// OBSERVER CALLBACK
// Loads PhotoSwipe on intersection
// Updates Table of Contents on intersection
let pswp = 0;
const scrollEvents = (entries, observer) => {
  entries.forEach((entry) => {
    if (entry.intersectionRatio) {
      if (pswp == 0 && entry.target.parentElement.dataset.toc == "#images") {
        pswp++;
        loadPhotoSwipe(entry.target.parentElement);
      }
      let currents = document.querySelectorAll(
        '.rl-toc ul li a[href="' + entry.target.parentElement.dataset.toc + '"]'
      );
      currents.forEach((current) =>
        current.parentElement.classList.add("current")
      );
    } else {
      if (!checkVisible(entry.target.parentElement)) {
        let removes = document.querySelectorAll(
          '.rl-toc ul li a[href="' +
            entry.target.parentElement.dataset.toc +
            '"]'
        );
        removes.forEach((remove) =>
          remove.parentElement.classList.remove("current")
        );
      }
    }
  });
};
// DISQUS LINKS 
const disqusLinks = ()=>{
  // for links to comments, auto-load comments section
  if(window.location.hash.indexOf("#comment-") != -1){
    let loadComments = document.querySelector('.load-comments.button');
    if(loadComments){
      loadComments.click();
    }
  }
}
// ITEM SHOW / MAIN
const itemShow = () =>{
  streamingMediaControls();
  smoothAnchorLinks();
  disqusLinks();
  if ("IntersectionObserver" in window) {
    let observer = new IntersectionObserver(scrollEvents, {});
    let sections = document.querySelectorAll("[data-toc] > *");
    sections.forEach((section) => observer.observe(section));
  } else {
    loadPhotoSwipe(document.querySelector('[data-toc="#images"]'));
  }
}
// MAIN
let isReady = setInterval(() => {
  if (document.readyState === 'complete') {
    clearInterval(isReady);
    itemShow();
  }
}, 100);
