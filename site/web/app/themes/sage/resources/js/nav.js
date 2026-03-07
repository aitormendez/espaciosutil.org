import { gsap } from 'gsap';

const particlesContainer = document.getElementById('tsparticles');

function normalizePath(pathname) {
  if (!pathname || pathname === '/') {
    return '/';
  }

  return pathname.endsWith('/') ? pathname : `${pathname}/`;
}

function hrefToPath(href) {
  if (!href) {
    return null;
  }

  const normalizedHref = href.trim();

  if (
    normalizedHref === '' ||
    normalizedHref.startsWith('#') ||
    normalizedHref.startsWith('mailto:') ||
    normalizedHref.startsWith('tel:') ||
    normalizedHref.startsWith('javascript:')
  ) {
    return null;
  }

  try {
    const url = new URL(normalizedHref, window.location.origin);
    if (url.origin !== window.location.origin) {
      return null;
    }

    return normalizePath(url.pathname);
  } catch {
    return null;
  }
}

function isPathMatch(currentPath, targetPath) {
  if (!targetPath) {
    return false;
  }

  if (targetPath === '/') {
    return currentPath === '/';
  }

  return currentPath === targetPath || currentPath.startsWith(targetPath);
}

function topLevelMenuItems() {
  return [...document.querySelectorAll('#nav .my-menu-item')];
}

function topLevelLink(menuItem) {
  return menuItem.querySelector(':scope > a');
}

function childMenuItems(menuItem) {
  return [...menuItem.querySelectorAll('.my-child-item')];
}

function isSwitchMenuItem(menuItem) {
  return Boolean(menuItem?.classList?.contains('switch'));
}

function addListener(cleanups, target, event, handler, options) {
  if (!target?.addEventListener) {
    return;
  }

  target.addEventListener(event, handler, options);
  cleanups.push(() => target.removeEventListener(event, handler, options));
}

export function syncNavLineWithActive() {
  if (!window.matchMedia('(min-width: 1280px)').matches) {
    return;
  }

  const nav = document.querySelector('#nav');
  const line = document.querySelector('#linea');

  if (!nav || !line) {
    return;
  }

  const activeMenu = nav.querySelector(
    '.my-menu-item.active-ancestor, .my-menu-item.active'
  );

  if (!activeMenu) {
    gsap.set(line, { width: 0 });
    return;
  }

  const activeLink = topLevelLink(activeMenu);

  if (!activeLink) {
    return;
  }

  const linkRect = activeLink.getBoundingClientRect();
  const navRect = nav.getBoundingClientRect();

  gsap.set(line, {
    x: linkRect.left - navRect.left,
    width: linkRect.width,
  });
}

export function syncActiveMenuState() {
  const currentPath = normalizePath(window.location.pathname);
  const topItems = topLevelMenuItems();

  if (topItems.length === 0) {
    return;
  }

  topItems.forEach((item) => {
    item.classList.remove('active', 'active-ancestor');
    childMenuItems(item).forEach((child) => child.classList.remove('active'));
  });

  let bestTopItem = null;
  let bestTopScore = -1;

  topItems.forEach((item) => {
    let hasActiveChild = false;
    let childScore = -1;

    childMenuItems(item).forEach((child) => {
      const childPath = hrefToPath(child.querySelector('a')?.getAttribute('href'));
      const childMatches = isPathMatch(currentPath, childPath);

      child.classList.toggle('active', childMatches);

      if (childMatches) {
        hasActiveChild = true;
        childScore = Math.max(childScore, childPath?.length ?? 0);
      }
    });

    const topPath = hrefToPath(topLevelLink(item)?.getAttribute('href'));
    const topMatches = isPathMatch(currentPath, topPath);

    let itemScore = -1;

    if (topMatches) {
      itemScore = Math.max(itemScore, (topPath?.length ?? 0) + 100);
    }

    if (hasActiveChild) {
      itemScore = Math.max(itemScore, childScore + 200);
    }

    if (itemScore > bestTopScore) {
      bestTopScore = itemScore;
      bestTopItem = {
        item,
        hasActiveChild,
      };
    }
  });

  if (bestTopItem) {
    bestTopItem.item.classList.add('active');

    if (bestTopItem.hasActiveChild) {
      bestTopItem.item.classList.add('active-ancestor');
    }
  }

  syncNavLineWithActive();
}

function getPersistentSectionColor() {
  const color = document.body?.dataset.sectionColor;

  return color && color.trim() !== '' ? color : '#000000';
}

function changeBgColor(color, immediate = false) {
  if (!particlesContainer || !color) {
    return;
  }

  const animation = {
    backgroundColor: color,
    overwrite: true,
  };

  if (immediate) {
    gsap.set(particlesContainer, animation);
    return;
  }

  gsap.to(particlesContainer, animation);
}

function previewMenuColor(menu) {
  const link = menu?.querySelector('a');
  const color = link?.dataset?.color;

  if (color) {
    changeBgColor(color);
  }
}

function restorePersistentBgColor() {
  changeBgColor(getPersistentSectionColor());
}

export function navegacion() {
  const cleanups = [];
  const timers = [];
  const desktopMenus = gsap.utils.toArray('.my-menu-item');
  const banner = document.querySelector('#banner');
  const brand = document.getElementById('brand');
  const submenuBg = document.querySelector('#submenu-bg');
  const linea = document.querySelector('#linea');

  if (!banner || !brand || !submenuBg || !linea) {
    return () => {};
  }

  banner.abierto = true;
  let openMenu;
  let isDestroyed = false;

  function escondeBanner() {
    if (isDestroyed) {
      return;
    }

    banner.abierto = false;
    gsap.to(banner, {
      overwrite: true,
      opacity: 0,
      onComplete: () => {
        if (banner.abierto === false) {
          banner.classList.add('xl:hidden');
        }
      },
    });

    openMenu && menuClose(openMenu);
  }

  function muestraBanner() {
    if (isDestroyed) {
      return;
    }

    banner.classList.remove('xl:hidden');
    banner.abierto = true;
    gsap.to(banner, {
      overwrite: true,
      opacity: 1,
    });
  }

  desktopMenus.forEach((menu) => {
    // Prevenir acción por defecto enlaces vacíos
    const enlaces = menu.querySelectorAll('a');
    enlaces.forEach((enlace) => {
      if (enlace.getAttribute('href') === '#') {
        const preventLink = function (event) {
          event.preventDefault();
        };

        addListener(cleanups, enlace, 'click', preventLink);
      }
    });

    if (
      menu.classList.contains('active-ancestor') ||
      menu.classList.contains('active')
    ) {
      const timer = setTimeout(() => {
        setLinea(menu);
      }, 100);
      timers.push(timer);
    }

    const handleMenuClick = () =>
      menu === openMenu ? menuClose(menu) : menuOpen(menu);

    addListener(cleanups, menu, 'click', handleMenuClick);
  });

  const handleBrandClick = () => {
    openMenu && menuClose(openMenu);
  };

  addListener(cleanups, brand, 'click', handleBrandClick);

  function menuOpen(menu) {
    const childMenu = menu.querySelector('.my-child-menu');

    if (childMenu) {
      const items = childMenu.querySelectorAll('li');

      if (openMenu !== menu) {
        childMenu.classList.remove('xl:hidden');
        openMenu && menuClose(openMenu, { restoreColor: false });
        openMenu = menu;
        previewMenuColor(menu);
        gsap.to(items, {
          opacity: 1,
          overwrite: true,
          duration: 1,
          stagger: 0.2,
        });
      }
    } else {
      openMenu && menuClose(openMenu);
      openMenu = undefined;
    }

    if (!isSwitchMenuItem(menu)) {
      moverLinea(menu);
    }
    setSubmenuBg(childMenu);
  }

  function menuClose(menu, { restoreColor = true } = {}) {
    const childMenu = menu.querySelector('.my-child-menu');

    openMenu = undefined;

    if (!childMenu) {
      if (restoreColor) {
        restorePersistentBgColor();
      }

      return;
    }

    const items = childMenu.querySelectorAll('li');

    gsap.to(items, {
      opacity: 0,
      overwrite: true,
      duration: 0.5,
      stagger: 0.1,
      onComplete: () => {
        childMenu.classList.add('xl:hidden');
      },
    });

    setSubmenuBg(childMenu);

    if (restoreColor) {
      restorePersistentBgColor();
    }
  }

  function setSubmenuBg(childMenu) {
    if (openMenu) {
      const calculatedHeight = childMenu.offsetHeight + 70;
      submenuBg.classList.add('border-b');
      gsap.to(submenuBg, {
        delay: 0.5,
        height: calculatedHeight + 'px',
        overwrite: true,
        duration: 0.5,
      });
    } else {
      gsap.to(submenuBg, {
        delay: 0.5,
        height: 0,
        overwrite: true,
        duration: 0.5,
        onComplete: () => {
          submenuBg.classList.remove('border-b');
        },
      });
    }
  }

  function moverLinea(menu) {
    const enlacePrincipalRect = menu.querySelector('a').getBoundingClientRect();
    const navPos = document.querySelector('#nav').getBoundingClientRect().left;

    gsap.to(linea, {
      x: enlacePrincipalRect.left - navPos,
      width: enlacePrincipalRect.width,
    });
  }

  function setLinea(menu) {
    const enlacePrincipalRect = menu.querySelector('a').getBoundingClientRect();
    const navPos = document.querySelector('#nav').getBoundingClientRect().left;

    gsap.set(linea, {
      x: enlacePrincipalRect.left - navPos,
      width: enlacePrincipalRect.width,
    });
  }

  // detectar clic fuera del banner para cerrar el submenú
  // https://www.w3docs.com/snippets/javascript/how-to-detect-a-click-outside-an-element.html

  const handleDocumentClick = (evt) => {
    const flyoutEl = document.getElementById('banner');
    let targetEl = evt.target;

    do {
      if (targetEl == flyoutEl) {
        return;
      }
      targetEl = targetEl.parentNode;
    } while (targetEl);

    openMenu && menuClose(openMenu);
  };

  addListener(cleanups, document, 'click', handleDocumentClick);

  // scroll

  let oldValue = 0;
  let newValue = 0;

  const handleScroll = () => {
    newValue = window.pageYOffset;
    if (oldValue < newValue && banner.abierto === true) {
      escondeBanner();
    } else if (oldValue > newValue && banner.abierto === false) {
      muestraBanner();
    }
    oldValue = newValue;
  };

  addListener(cleanups, window, 'scroll', handleScroll);

  return () => {
    isDestroyed = true;

    timers.forEach((timer) => clearTimeout(timer));
    cleanups.forEach((cleanup) => cleanup());

    gsap.killTweensOf(banner);
    gsap.killTweensOf(submenuBg);
    gsap.killTweensOf(linea);

    banner.abierto = true;
    banner.classList.remove('xl:hidden');
    gsap.set(banner, { clearProps: 'opacity' });

    submenuBg.classList.remove('border-b');
    gsap.set(submenuBg, { height: 0 });

    openMenu = undefined;
    desktopMenus.forEach((menu) => {
      const childMenu = menu.querySelector('.my-child-menu');
      if (childMenu) {
        childMenu.classList.add('xl:hidden');
      }
    });
  };
}

export function navegacionMovil() {
  const cleanups = [];
  const mobileMenus = gsap.utils.toArray('.my-menu-item');
  const burguer = document.getElementById('burguer');
  const nav = document.getElementById('nav');

  if (!burguer || !nav) {
    return () => {};
  }

  let navOpen = false;
  let openMenu;

  gsap.set(nav, {
    x: '-100vw',
  });

  const setBurguerState = (open) => {
    burguer.classList.toggle('is-active', open);
  };

  const handleBurguerClick = () => {
    if (navOpen) {
      cerrarNav();
    } else {
      abrirNav();
    }
  };

  addListener(cleanups, burguer, 'click', handleBurguerClick);

  function cerrarNav() {
    setBurguerState(false);
    gsap.to(nav, {
      x: '-100vw',
    });

    if (openMenu) {
      openMenu.close();
    }

    navOpen = false;
  }

  function abrirNav() {
    setBurguerState(true);
    gsap.to(nav, {
      x: 0,
    });
    navOpen = true;
  }

  mobileMenus.forEach((menu) => {
    const box = menu.querySelector('.my-child-menu');
    let isOpen = false;

    if (box) {
      const items = box.querySelectorAll('li');

      gsap.set(items, { y: -30 });

      menu.open = () => {
        if (!isOpen) {
          isOpen = true;

          if (openMenu && openMenu !== menu) {
            openMenu.close({ restoreColor: false });
          }

          openMenu = menu;
          previewMenuColor(menu);

          gsap.to(box, {
            height: 'auto',
            duration: 1,
            ease: 'elastic',
            overwrite: true,
          });
          gsap.to(items, {
            y: 0,
            overwrite: true,
            duration: 1.5,
            stagger: 0.1,
            ease: 'elastic',
          });
        }
      };

      menu.close = ({ restoreColor = true } = {}) => {
        if (isOpen) {
          isOpen = false;

          if (openMenu === menu) {
            openMenu = null;
          }

          gsap.to(box, {
            height: 0,
            overwrite: true,
            onComplete: () => gsap.set(items, { y: -30, overwrite: true }),
          });

          if (restoreColor) {
            restorePersistentBgColor();
          }
        }
      };

      const handleMenuClick = () =>
        isOpen ? menu.close() : menu.open();

      addListener(cleanups, menu, 'click', handleMenuClick);

      // cerrar nav en movil cuando clicas submenús
      items.forEach((item) => {
        const handleItemClick = () => cerrarNav();
        addListener(cleanups, item, 'click', handleItemClick);
      });
    } else {
      const handleMenuWithoutChildrenClick = () => cerrarNav();
      addListener(cleanups, menu, 'click', handleMenuWithoutChildrenClick);
    }
  });

  return () => {
    cleanups.forEach((cleanup) => cleanup());

    gsap.killTweensOf(nav);
    gsap.set(nav, { clearProps: 'x' });
    setBurguerState(false);
    navOpen = false;

    openMenu = null;
    mobileMenus.forEach((menu) => {
      const box = menu.querySelector('.my-child-menu');
      if (!box) {
        return;
      }

      const items = box.querySelectorAll('li');
      gsap.killTweensOf(box);
      gsap.killTweensOf(items);
      gsap.set(box, { height: 0 });
      gsap.set(items, { y: -30 });
    });

    restorePersistentBgColor();
  };
}

export function particlesBgColor() {
  const menuItems = document.querySelectorAll('#nav .my-menu > li > a');

  menuItems.forEach((item) => {
    const color = item.dataset.color;

    if (!color) {
      return;
    }

    const menu = item.closest('.my-menu-item');
    const hasChildMenu = Boolean(menu?.querySelector('.my-child-menu'));

    if (hasChildMenu) {
      return;
    }

    item.addEventListener('click', () => changeBgColor(color));
  });
}

export function setBgColorAtLoadPage() {
  changeBgColor(getPersistentSectionColor(), true);
}
