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
    
    // Prevent body scroll when sidebar is open on mobile
    if (window.innerWidth < 992) {
      document.body.style.overflow = 'hidden';
    }
  };

  const close = () => {
    sidebar.classList.remove('is-open');
    overlay.classList.remove('is-open');
    document.body.classList.remove('sidebar-open');
    
    // Restore body scroll
    document.body.style.overflow = '';
  };

  const handleToggle = (e) => {
    e.preventDefault();
    e.stopPropagation();
    const isOpen = sidebar.classList.contains('is-open');
    isOpen ? close() : open();
  };

  toggle.addEventListener('click', handleToggle);
  if (toggleInner) toggleInner.addEventListener('click', handleToggle);
  if (closeBtn) closeBtn.addEventListener('click', close);
  overlay.addEventListener('click', close);

  // close on any sidebar nav click (mobile)
  // NOTE: Do NOT handle/modify navigation itself; only close sidebar.
  sidebar.addEventListener('click', (e) => {
    const link = e.target.closest('a');
    if (!link) return;

    // If click triggers a download (e.g., target=_blank or data attributes), avoid any delay hacks.
    if (link.getAttribute('download')) return;

    if (window.innerWidth < 992) {
      setTimeout(() => close(), 50); // very small delay for navigation
    }
  });


  // Handle escape key
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && sidebar.classList.contains('is-open')) {
      close();
    }
  });

  // Handle window resize
  const handleResize = () => {
    if (window.innerWidth >= 992) {
      close(); // Close mobile sidebar on desktop
      document.body.style.overflow = ''; // Ensure scroll is restored
      applyExpanded(getStoredExpanded());
    } else {
      applyExpanded(false); // Collapse sidebar on mobile
    }
  };

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
      const stored = localStorage.getItem(storageKey);
      if (stored === null) {
        return true;
      }
      return stored === '1';
    } catch {
      return true;
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

  window.addEventListener('resize', handleResize);

  // MOBILE DEFAULT MUST BE HIDDEN ON FIRST LOAD
  if (window.innerWidth < 992) close();
});
