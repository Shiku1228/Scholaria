document.addEventListener('DOMContentLoaded', () => {
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sidebarOverlay');
  const toggle = document.getElementById('sidebarToggle');
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

  toggle.addEventListener('click', (e) => {
    e.preventDefault();
    const isOpen = sidebar.classList.contains('is-open');
    isOpen ? close() : open();
  });

  if (closeBtn) closeBtn.addEventListener('click', close);
  overlay.addEventListener('click', close);

  // close on any sidebar nav click (mobile)
  sidebar.addEventListener('click', (e) => {
    const link = e.target.closest('a');
    if (!link) return;
    if (window.innerWidth < 992) close();
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') close();
  });

  // when resizing to desktop, ensure overlay removed and scrolling restored
  window.addEventListener('resize', () => {
    if (window.innerWidth >= 992) {
      close();
    } else {
      close();
    }
  });

  // MOBILE DEFAULT MUST BE HIDDEN ON FIRST LOAD
  if (window.innerWidth < 992) close();
});
