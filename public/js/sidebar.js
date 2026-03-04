document.addEventListener('DOMContentLoaded', () => {
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sidebarOverlay');
  const toggle = document.getElementById('sidebarToggle');
  const toggleInner = document.getElementById('sidebarToggleInner');
  const expandToggle = document.getElementById('sidebarExpandToggle');
  const closeBtn = document.getElementById('sidebarClose');

  if (!sidebar || !overlay || !toggle) return;

  const open = () => {
    sidebar.classList.add('is-open');
    overlay.classList.add('is-open');
    document.body.classList.add('sidebar-open');
  };

  const close = () => {
    sidebar.classList.remove('is-open');
    overlay.classList.remove('is-open');
    document.body.classList.remove('sidebar-open');
  };

  const handleToggle = (e) => {
    e.preventDefault();
    const isOpen = sidebar.classList.contains('is-open');
    isOpen ? close() : open();
  };

  toggle.addEventListener('click', handleToggle);
  if (toggleInner) toggleInner.addEventListener('click', handleToggle);
  if (closeBtn) closeBtn.addEventListener('click', close);
  overlay.addEventListener('click', close);

  // close on any sidebar nav click (mobile)
  sidebar.addEventListener('click', (e) => {
    const link = e.target.closest('a');
    if (!link) return;
    if (window.innerWidth < 992) close();
  });

  const storageKey = 'slms.sidebar.expanded';
  const applyExpanded = (expanded) => {
    if (!sidebar) return;
    if (expanded) {
      sidebar.classList.add('is-expanded');
    } else {
      sidebar.classList.remove('is-expanded');
    }

    document.body.classList.toggle('slms-sidebar-expanded', expanded);

    if (expandToggle) {
      expandToggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
    }

    if (window.lucide && typeof window.lucide.createIcons === 'function') {
      window.lucide.createIcons();
    }
  };

  const getStoredExpanded = () => {
    try {
      return localStorage.getItem(storageKey) === '1';
    } catch {
      return false;
    }
  };

  const setStoredExpanded = (expanded) => {
    try {
      localStorage.setItem(storageKey, expanded ? '1' : '0');
    } catch {
    }
  };

  if (window.innerWidth >= 992) {
    applyExpanded(getStoredExpanded());
  }

  if (expandToggle) {
    expandToggle.addEventListener('click', (e) => {
      e.preventDefault();
      const next = !sidebar.classList.contains('is-expanded');
      applyExpanded(next);
      setStoredExpanded(next);
    });
  }

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') close();
  });

  // when resizing to desktop, ensure overlay removed and scrolling restored
  window.addEventListener('resize', () => {
    close();
    if (window.innerWidth >= 992) {
      applyExpanded(getStoredExpanded());
    } else {
      applyExpanded(false);
    }
  });

  // MOBILE DEFAULT MUST BE HIDDEN ON FIRST LOAD
  if (window.innerWidth < 992) close();
});
