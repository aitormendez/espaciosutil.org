import { gsap } from 'gsap';

const particlesContainer = document.getElementById('tsparticles');
const menus = gsap.utils.toArray('.my-menu-item');

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
  const banner = document.querySelector('#banner');
  const brand = document.getElementById('brand');
  banner.abierto = true;
  let openMenu;
  const submenuBg = document.querySelector('#submenu-bg');
  const linea = document.querySelector('#linea');

  function escondeBanner() {
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
    banner.classList.remove('xl:hidden');
    banner.abierto = true;
    gsap.to(banner, {
      overwrite: true,
      opacity: 1,
    });
  }

  menus.forEach((menu) => {
    // Prevenir acción por defecto enlaces vacíos
    const enlaces = menu.querySelectorAll('a');
    enlaces.forEach((enlace) => {
      if (enlace.getAttribute('href') === '#') {
        enlace.addEventListener('click', function (event) {
          event.preventDefault();
        });
      }
    });

    if (
      menu.classList.contains('active-ancestor') ||
      menu.classList.contains('active')
    ) {
      setTimeout(() => {
        setLinea(menu);
      }, 100);
    }

    menu.addEventListener('click', () =>
      menu === openMenu ? menuClose(menu) : menuOpen(menu)
    );
  });

  brand.addEventListener('click', () => {
    openMenu && menuClose(openMenu);
  });

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

    moverLinea(menu);
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

  document.addEventListener('click', (evt) => {
    const flyoutEl = document.getElementById('banner');
    let targetEl = evt.target;

    do {
      if (targetEl == flyoutEl) {
        return;
      }
      targetEl = targetEl.parentNode;
    } while (targetEl);

    openMenu && menuClose(openMenu);
  });

  // scroll

  let oldValue = 0;
  let newValue = 0;

  window.addEventListener('scroll', () => {
    newValue = window.pageYOffset;
    if (oldValue < newValue && banner.abierto === true) {
      escondeBanner();
    } else if (oldValue > newValue && banner.abierto === false) {
      muestraBanner();
    }
    oldValue = newValue;
  });
}

export function navegacionMovil() {
  const menus = gsap.utils.toArray('.my-menu-item');
  const burguer = document.getElementById('burguer');
  const nav = document.getElementById('nav');
  let navOpen = false;
  let openMenu;

  gsap.set(nav, {
    x: '-100vw',
  });

  burguer.addEventListener('click', () => {
    if (navOpen) {
      cerrarNav();
    } else {
      abrirNav();
    }
  });

  function cerrarNav() {
    burguer.classList.toggle('is-active');
    gsap.to(nav, {
      x: '-100vw',
    });

    if (openMenu) {
      openMenu.close();
    }

    navOpen = false;
  }

  function abrirNav() {
    burguer.classList.toggle('is-active');
    gsap.to(nav, {
      x: 0,
    });
    navOpen = true;
  }

  menus.forEach((menu) => {
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

      menu.addEventListener('click', () =>
        isOpen ? menu.close() : menu.open()
      );

      // cerrar nav en movil cuando clicas submenús
      items.forEach((item) => {
        item.addEventListener('click', () => cerrarNav());
      });
    } else {
      menu.addEventListener('click', () => cerrarNav());
    }
  });
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
