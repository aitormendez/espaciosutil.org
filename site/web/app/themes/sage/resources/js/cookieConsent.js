const DEFAULT_STORAGE_KEY = 'es_cookie_consent_v1';
const DEFAULT_COOKIE_NAME = 'es_cookie_consent';
const DEFAULT_VERSION = 'v1';
const MATOMO_COOKIE_PREFIX = '_pk_';

let consentState = null;
let elements = null;
let matomoConfigured = false;
let matomoEnabled = false;
let matomoScriptLoading = false;
let matomoScriptLoaded = false;

function getConfig() {
  const consent = window.jsData?.cookieConsent ?? {};
  const matomo = consent.matomo ?? {};

  return {
    storageKey: consent.storageKey || DEFAULT_STORAGE_KEY,
    cookieName: consent.cookieName || DEFAULT_COOKIE_NAME,
    version: consent.version || DEFAULT_VERSION,
    matomoUrl: normalizeMatomoUrl(matomo.url || ''),
    matomoSiteId: Number(matomo.siteId || 0),
  };
}

function normalizeMatomoUrl(url) {
  if (!url) {
    return '';
  }

  return `${url}`.replace(/\/+$/, '');
}

function getRoot() {
  return document.querySelector('[data-cookie-consent-root]');
}

function getElements() {
  if (elements) {
    return elements;
  }

  const root = getRoot();

  if (!root) {
    return null;
  }

  elements = {
    root,
    banner: root.querySelector('[data-cookie-banner]'),
    panel: root.querySelector('[data-cookie-panel]'),
    analyticsToggle: root.querySelector('[data-cookie-analytics]'),
    openButtons: [
      ...root.querySelectorAll('[data-cookie-open]'),
      ...document.querySelectorAll('[data-cookie-preferences-trigger]'),
    ],
    acceptButtons: root.querySelectorAll('[data-cookie-accept]'),
    rejectButtons: root.querySelectorAll('[data-cookie-reject]'),
    closeButtons: root.querySelectorAll('[data-cookie-close]'),
    saveButton: root.querySelector('[data-cookie-save]'),
  };

  return elements;
}

function readConsent() {
  const config = getConfig();

  const fromCookie = readConsentFromCookie(config.cookieName);
  if (fromCookie) {
    syncConsentToLocalStorage(fromCookie, config.storageKey);
    return fromCookie;
  }

  clearConsentFromLocalStorage(config.storageKey);

  return null;
}

function readConsentFromCookie(cookieName) {
  const value = document.cookie
    .split('; ')
    .find((chunk) => chunk.startsWith(`${cookieName}=`))
    ?.split('=')
    .slice(1)
    .join('=');

  if (!value) {
    return null;
  }

  try {
    return normalizeConsentPayload(JSON.parse(decodeURIComponent(value)));
  } catch (error) {
    return null;
  }
}

function normalizeConsentPayload(payload) {
  if (!payload || typeof payload !== 'object') {
    return null;
  }

  const version = `${payload.version || ''}`;
  const analytics = Boolean(payload.analytics);
  const updatedAt = `${payload.updatedAt || ''}`;

  if (!version || !updatedAt) {
    return null;
  }

  return {
    version,
    analytics,
    updatedAt,
  };
}

function persistConsent(nextConsent) {
  const config = getConfig();
  const serialized = JSON.stringify(nextConsent);

  syncConsentToLocalStorage(nextConsent, config.storageKey);

  document.cookie = [
    `${config.cookieName}=${encodeURIComponent(serialized)}`,
    'path=/',
    'max-age=31536000',
    'SameSite=Lax',
    window.location.protocol === 'https:' ? 'Secure' : '',
  ]
    .filter(Boolean)
    .join('; ');
}

function syncConsentToLocalStorage(nextConsent, storageKey) {
  try {
    window.localStorage.setItem(storageKey, JSON.stringify(nextConsent));
  } catch (error) {
    // Ignore storage failures.
  }
}

function clearConsentFromLocalStorage(storageKey) {
  try {
    window.localStorage.removeItem(storageKey);
  } catch (error) {
    // Ignore storage failures.
  }
}

function applyConsent(nextConsent, { trackPageView = true } = {}) {
  consentState = nextConsent;
  persistConsent(nextConsent);

  const ui = getElements();
  if (ui?.analyticsToggle) {
    ui.analyticsToggle.checked = Boolean(nextConsent.analytics);
  }

  hideBanner();
  closePanel();

  if (nextConsent.analytics) {
    enableMatomo({ trackPageView });
  } else {
    disableMatomo();
  }

  window.dispatchEvent(
    new CustomEvent('es:cookie-consent-changed', {
      detail: { ...nextConsent },
    }),
  );
}

function showBanner() {
  const ui = getElements();
  if (!ui?.banner || !ui.root) {
    return;
  }

  ui.root.hidden = false;
  ui.root.classList.remove('hidden');
  ui.banner.hidden = false;
}

function hideBanner() {
  const ui = getElements();
  if (!ui?.banner) {
    return;
  }

  ui.banner.hidden = true;

  if (ui.panel?.hidden !== false) {
    ui.root.hidden = true;
    ui.root.classList.add('hidden');
  }
}

function openPanel() {
  const ui = getElements();
  if (!ui?.panel || !ui.root) {
    return;
  }

  ui.root.hidden = false;
  ui.root.classList.remove('hidden');
  ui.panel.classList.remove('hidden');
  ui.panel.hidden = false;
  ui.panel.setAttribute('aria-hidden', 'false');

  if (ui.analyticsToggle) {
    ui.analyticsToggle.checked = Boolean(consentState?.analytics);
  }
}

function closePanel() {
  const ui = getElements();
  if (!ui?.panel) {
    return;
  }

  ui.panel.hidden = true;
  ui.panel.classList.add('hidden');
  ui.panel.setAttribute('aria-hidden', 'true');

  if (ui.banner?.hidden !== false) {
    ui.root.hidden = true;
    ui.root.classList.add('hidden');
  }
}

function bindConsentUi() {
  const ui = getElements();
  if (!ui) {
    return;
  }

  ui.openButtons.forEach((button) => {
    button.addEventListener('click', () => {
      openPanel();
    });
  });

  ui.acceptButtons.forEach((button) => {
    button.addEventListener('click', () => {
      applyConsent(buildConsent(true));
    });
  });

  ui.rejectButtons.forEach((button) => {
    button.addEventListener('click', () => {
      applyConsent(buildConsent(false), { trackPageView: false });
    });
  });

  ui.closeButtons.forEach((button) => {
    button.addEventListener('click', () => {
      closePanel();
    });
  });

  ui.saveButton?.addEventListener('click', () => {
    const analyticsAccepted = Boolean(ui.analyticsToggle?.checked);
    applyConsent(buildConsent(analyticsAccepted), {
      trackPageView: analyticsAccepted,
    });
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      closePanel();
    }
  });
}

function buildConsent(analytics) {
  return {
    version: getConfig().version,
    analytics,
    updatedAt: new Date().toISOString(),
  };
}

function configureMatomo() {
  const { matomoUrl, matomoSiteId } = getConfig();

  if (!matomoUrl || !matomoSiteId) {
    return false;
  }

  if (!matomoConfigured) {
    window._paq = window._paq || [];
    window._paq.push(['setTrackerUrl', `${matomoUrl}/matomo.php`]);
    window._paq.push(['setSiteId', `${matomoSiteId}`]);
    window._paq.push(['enableLinkTracking']);
    window._paq.push(['setCookieSameSite', 'Lax']);

    if (window.location.protocol === 'https:') {
      window._paq.push(['setSecureCookie', true]);
    }

    matomoConfigured = true;
  }

  return true;
}

function loadMatomoScript() {
  const { matomoUrl } = getConfig();

  if (!matomoUrl || matomoScriptLoaded || matomoScriptLoading) {
    return;
  }

  matomoScriptLoading = true;

  const script = document.createElement('script');
  script.async = true;
  script.src = `${matomoUrl}/matomo.js`;
  script.onload = () => {
    matomoScriptLoaded = true;
    matomoScriptLoading = false;
  };
  script.onerror = () => {
    matomoScriptLoading = false;
  };

  document.head.appendChild(script);
}

function enableMatomo({ trackPageView = true } = {}) {
  if (!configureMatomo()) {
    return;
  }

  matomoEnabled = true;
  loadMatomoScript();

  if (trackPageView) {
    trackAnalyticsPageView();
  }
}

function disableMatomo() {
  matomoEnabled = false;

  if (window._paq) {
    window._paq.push(['disableCookies']);
    window._paq.push(['deleteCookies']);
  }

  clearMatomoCookies();
}

function clearMatomoCookies() {
  const cookieNames = document.cookie
    .split('; ')
    .map((chunk) => chunk.split('=')[0])
    .filter((name) => name.startsWith(MATOMO_COOKIE_PREFIX));

  cookieNames.forEach((cookieName) => {
    expireCookie(cookieName);
  });
}

function expireCookie(cookieName) {
  const hostname = window.location.hostname;
  const domains = new Set([hostname, `.${hostname}`]);
  const parts = hostname.split('.');

  if (parts.length > 1) {
    domains.add(`.${parts.slice(-2).join('.')}`);
  }

  domains.forEach((domain) => {
    document.cookie = [
      `${cookieName}=`,
      'path=/',
      `domain=${domain}`,
      'expires=Thu, 01 Jan 1970 00:00:00 GMT',
      'SameSite=Lax',
      window.location.protocol === 'https:' ? 'Secure' : '',
    ]
      .filter(Boolean)
      .join('; ');
  });

  document.cookie = [
    `${cookieName}=`,
    'path=/',
    'expires=Thu, 01 Jan 1970 00:00:00 GMT',
    'SameSite=Lax',
    window.location.protocol === 'https:' ? 'Secure' : '',
  ]
    .filter(Boolean)
    .join('; ');
}

export function trackAnalyticsPageView() {
  if (!matomoEnabled || !matomoConfigured || !window._paq) {
    return;
  }

  window._paq.push(['setCustomUrl', window.location.href]);
  window._paq.push(['setDocumentTitle', document.title]);
  window._paq.push(['trackPageView']);
}

export function openCookiePreferences() {
  openPanel();
}

export function initCookieConsent() {
  bindConsentUi();

  const storedConsent = readConsent();
  if (!storedConsent || storedConsent.version !== getConfig().version) {
    consentState = buildConsent(false);
    showBanner();
    return;
  }

  consentState = storedConsent;

  if (consentState.analytics) {
    enableMatomo();
  }
}
