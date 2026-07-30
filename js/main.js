/* ==========================================================================
   Fernanda Vieira Psicologia — Scripts gerais do site
   ========================================================================== */

document.addEventListener("DOMContentLoaded", function () {
  var WHATSAPP_NUMBER = "5531988671693"; // WhatsApp com DDI 55 + DDD 31

  /* ---------- Splash screen ---------- */
  var splash = document.querySelector(".splash-screen");
  if (splash) {
    var alreadySeen = false;
    try {
      alreadySeen = sessionStorage.getItem("fv-splash-seen") === "1";
    } catch (e) {}

    if (alreadySeen) {
      splash.remove();
      document.body.classList.remove("splash-lock");
    } else {
      document.body.classList.add("splash-lock");
      var splashHidden = false;
      var splashStarted = Date.now();

      var hideSplash = function () {
        if (splashHidden) return;
        splashHidden = true;
        splash.classList.add("is-done");
        document.body.classList.remove("splash-lock");
        try {
          sessionStorage.setItem("fv-splash-seen", "1");
        } catch (e) {}
        setTimeout(function () {
          if (splash && splash.parentNode) splash.remove();
        }, 750);
      };

      var scheduleHide = function () {
        var elapsed = Date.now() - splashStarted;
        var wait = Math.max(400, 2000 - elapsed);
        setTimeout(hideSplash, wait);
      };

      if (document.readyState === "complete") {
        scheduleHide();
      } else {
        window.addEventListener("load", scheduleHide, { once: true });
      }
      // Fallback de segurança
      setTimeout(hideSplash, 4000);
    }
  }

  /* ---------- Ano dinâmico no rodapé ---------- */
  document.querySelectorAll("[data-current-year]").forEach(function (el) {
    el.textContent = new Date().getFullYear();
  });

  /* ---------- Header: sombra/fundo ao rolar ---------- */
  var header = document.querySelector(".site-header");
  function handleHeaderScroll() {
    if (!header) return;
    if (window.scrollY > 12) {
      header.classList.add("is-scrolled");
    } else {
      header.classList.remove("is-scrolled");
    }
  }
  handleHeaderScroll();
  window.addEventListener("scroll", handleHeaderScroll, { passive: true });

  /* ---------- Menu mobile ---------- */
  var navToggle = document.querySelector(".nav-toggle");
  var mainNav = document.querySelector(".main-nav");

  function closeMobileNav() {
    if (!navToggle || !mainNav) return;
    mainNav.classList.remove("is-open");
    navToggle.classList.remove("is-active");
    navToggle.setAttribute("aria-expanded", "false");
    document.body.style.overflow = "";
  }

  function openMobileNav() {
    if (!navToggle || !mainNav) return;
    mainNav.classList.add("is-open");
    navToggle.classList.add("is-active");
    navToggle.setAttribute("aria-expanded", "true");
    document.body.style.overflow = "hidden";
  }

  if (navToggle && mainNav) {
    // Garante estado limpo ao carregar qualquer página
    closeMobileNav();

    navToggle.addEventListener("click", function () {
      if (mainNav.classList.contains("is-open")) closeMobileNav();
      else openMobileNav();
    });

    mainNav.querySelectorAll("a").forEach(function (link) {
      link.addEventListener("click", closeMobileNav);
    });

    window.addEventListener(
      "resize",
      function () {
        if (window.innerWidth > 1080) closeMobileNav();
      },
      { passive: true }
    );

    document.addEventListener("keydown", function (event) {
      if (event.key === "Escape") closeMobileNav();
    });
  }

  /* ---------- Botão "voltar ao topo" ---------- */
  var backToTop = document.querySelector(".back-to-top");
  if (backToTop) {
    window.addEventListener(
      "scroll",
      function () {
        backToTop.classList.toggle("is-visible", window.scrollY > 500);
      },
      { passive: true }
    );
    backToTop.addEventListener("click", function () {
      window.scrollTo({ top: 0, behavior: "smooth" });
    });
  }

  /* ---------- Revelar elementos ao rolar ---------- */
  var revealEls = document.querySelectorAll(".reveal");
  if ("IntersectionObserver" in window && revealEls.length) {
    var observer = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add("is-visible");
            observer.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.12, rootMargin: "0px 0px -50px 0px" }
    );

    // Atraso em cascata para itens irmãos em grids
    var staggerGroups = document.querySelectorAll(
      ".impact-grid, .approach-grid, .benefits-grid, .steps-grid, .faq-list, .testimonial-carousel"
    );
    staggerGroups.forEach(function (group) {
      var items = group.querySelectorAll(".reveal");
      items.forEach(function (item, index) {
        item.style.setProperty("--reveal-delay", index * 110 + "ms");
      });
    });

    revealEls.forEach(function (el) {
      observer.observe(el);
    });
  } else {
    revealEls.forEach(function (el) {
      el.classList.add("is-visible");
    });
  }

  /* ---------- Parallax suave no hero ---------- */
  var parallaxEl = document.querySelector("[data-parallax]");
  if (parallaxEl && window.matchMedia("(pointer: fine)").matches) {
    var reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    if (!reduceMotion) {
      window.addEventListener(
        "scroll",
        function () {
          var rect = parallaxEl.getBoundingClientRect();
          if (rect.bottom < 0 || rect.top > window.innerHeight) return;
          var offset = (window.innerHeight / 2 - (rect.top + rect.height / 2)) * 0.04;
          parallaxEl.style.transform = "translateY(" + offset + "px)";
        },
        { passive: true }
      );
    }
  }

  /* ---------- Classes de entrada no hero após splash ---------- */
  var hero = document.querySelector(".hero");
  if (hero) {
    var markHeroReady = function () {
      hero.classList.add("is-ready");
    };
    if (!document.body.classList.contains("splash-lock")) {
      markHeroReady();
    } else {
      var splashWatch = setInterval(function () {
        if (!document.body.classList.contains("splash-lock")) {
          clearInterval(splashWatch);
          markHeroReady();
        }
      }, 100);
      setTimeout(function () {
        clearInterval(splashWatch);
        markHeroReady();
      }, 4500);
    }
  }

  /* ---------- Accordion de Perguntas Frequentes ---------- */
  document.querySelectorAll(".faq-item").forEach(function (item) {
    var question = item.querySelector(".faq-question");
    var answer = item.querySelector(".faq-answer");
    if (!question || !answer) return;

    question.addEventListener("click", function () {
      var isOpen = item.classList.contains("is-open");

      item.parentElement.querySelectorAll(".faq-item").forEach(function (other) {
        other.classList.remove("is-open");
        other.querySelector(".faq-question").setAttribute("aria-expanded", "false");
        other.querySelector(".faq-answer").style.maxHeight = null;
      });

      if (!isOpen) {
        item.classList.add("is-open");
        question.setAttribute("aria-expanded", "true");
        answer.style.maxHeight = answer.scrollHeight + 24 + "px";
      }
    });
  });

  /* ---------- Formulário de contato -> WhatsApp ---------- */
  var contactForm = document.querySelector("#contact-form");
  if (contactForm) {
    contactForm.addEventListener("submit", function (event) {
      event.preventDefault();

      var name = contactForm.querySelector("#name").value.trim();
      var phone = contactForm.querySelector("#phone").value.trim();
      var message = contactForm.querySelector("#message").value.trim();

      var lines = [
        "Olá, Fernanda! Vim do site e gostaria de saber como funciona o processo terapêutico?",
        "",
        "Nome: " + (name || "-"),
      ];
      if (phone) lines.push("Telefone/e-mail para retorno: " + phone);
      lines.push("");
      lines.push(message || "Gostaria de mais informações sobre os atendimentos.");

      var text = encodeURIComponent(lines.join("\n"));
      var url = "https://wa.me/" + WHATSAPP_NUMBER + "?text=" + text;

      if (typeof gtag === "function") {
        gtag("event", "whatsapp_click", {
          event_category: "engagement",
          event_label: "contact_form",
        });
      }

      window.open(url, "_blank", "noopener");
    });
  }

  /* ---------- Tracking de cliques WhatsApp (GA4) ---------- */
  document.querySelectorAll('a[href*="wa.me"], a[href*="api.whatsapp.com"]').forEach(function (link) {
    link.addEventListener("click", function () {
      if (typeof gtag === "function") {
        gtag("event", "whatsapp_click", {
          event_category: "engagement",
          event_label: link.getAttribute("href") || "whatsapp",
        });
      }
    });
  });
  /* ---------- Carrossel de depoimentos ---------- */
  var carousel = document.querySelector("[data-testimonial-carousel]");
  if (carousel) {
    var track = carousel.querySelector(".carousel-track");
    var cards = Array.prototype.slice.call(carousel.querySelectorAll(".testimonial-card"));
    var prevBtn = carousel.querySelector(".carousel-prev");
    var nextBtn = carousel.querySelector(".carousel-next");
    var dotsWrap = carousel.querySelector(".carousel-dots");
    var index = 0;
    var timer = null;
    var touchStartX = 0;
    var touchDeltaX = 0;

    function goTo(i) {
      index = (i + cards.length) % cards.length;
      track.style.transform = "translateX(" + (-index * 100) + "%)";
      cards.forEach(function (card, idx) {
        card.classList.toggle("is-active", idx === index);
      });
      Array.prototype.forEach.call(dotsWrap.children, function (dot, idx) {
        dot.classList.toggle("is-active", idx === index);
        dot.setAttribute("aria-selected", idx === index ? "true" : "false");
      });
    }

    function next() { goTo(index + 1); }
    function prev() { goTo(index - 1); }

    function startAutoplay() {
      stopAutoplay();
      timer = setInterval(next, 7000);
    }

    function stopAutoplay() {
      if (timer) clearInterval(timer);
      timer = null;
    }

    cards.forEach(function (_, idx) {
      var dot = document.createElement("button");
      dot.type = "button";
      dot.className = "carousel-dot" + (idx === 0 ? " is-active" : "");
      dot.setAttribute("aria-label", "Ir para depoimento " + (idx + 1));
      dot.setAttribute("role", "tab");
      dot.setAttribute("aria-selected", idx === 0 ? "true" : "false");
      dot.addEventListener("click", function () {
        goTo(idx);
        startAutoplay();
      });
      dotsWrap.appendChild(dot);
    });

    if (prevBtn) prevBtn.addEventListener("click", function () { prev(); startAutoplay(); });
    if (nextBtn) nextBtn.addEventListener("click", function () { next(); startAutoplay(); });

    carousel.addEventListener("mouseenter", stopAutoplay);
    carousel.addEventListener("mouseleave", startAutoplay);
    carousel.addEventListener("focusin", stopAutoplay);
    carousel.addEventListener("focusout", startAutoplay);

    var viewport = carousel.querySelector(".carousel-viewport");
    if (viewport) {
      viewport.addEventListener("touchstart", function (e) {
        touchStartX = e.changedTouches[0].screenX;
        touchDeltaX = 0;
        stopAutoplay();
      }, { passive: true });

      viewport.addEventListener("touchmove", function (e) {
        touchDeltaX = e.changedTouches[0].screenX - touchStartX;
      }, { passive: true });

      viewport.addEventListener("touchend", function () {
        if (Math.abs(touchDeltaX) > 50) {
          if (touchDeltaX < 0) next();
          else prev();
        }
        startAutoplay();
      });
    }

    goTo(0);
    startAutoplay();
  }

});
