/* =========================================================
   Startup Idea Collaboration Portal - Main JS
   ========================================================= */
document.addEventListener('DOMContentLoaded', function () {

  /* ---------- Dark / Light Theme Toggle ---------- */
  const themeToggle = document.getElementById('themeToggle');
  const themeIcon = document.getElementById('themeIcon');
  const html = document.documentElement;

  function applyIcon() {
    const theme = html.getAttribute('data-bs-theme');
    if (themeIcon) {
      themeIcon.className = theme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
    }
  }
  applyIcon();

  if (themeToggle) {
    themeToggle.addEventListener('click', function () {
      const current = html.getAttribute('data-bs-theme');
      const next = current === 'dark' ? 'light' : 'dark';
      html.setAttribute('data-bs-theme', next);
      localStorage.setItem('theme', next);
      applyIcon();
    });
  }

  /* ---------- Mobile Sidebar Toggle ---------- */
  const sidebarToggle = document.getElementById('sidebarToggle');
  const sidebar = document.getElementById('appSidebar');
  const backdrop = document.getElementById('sidebarBackdrop');

  function closeSidebar() {
    if (sidebar) sidebar.classList.remove('show');
    if (backdrop) backdrop.classList.remove('show');
  }
  if (sidebarToggle && sidebar) {
    sidebarToggle.addEventListener('click', function () {
      sidebar.classList.toggle('show');
      backdrop.classList.toggle('show');
    });
  }
  if (backdrop) backdrop.addEventListener('click', closeSidebar);

  /* ---------- CSRF token for AJAX calls ---------- */
  const csrfMeta = document.querySelector('meta[name="csrf-token"]');
  const csrfToken = csrfMeta ? csrfMeta.content : '';

  /* ---------- Like Idea (AJAX) ---------- */
  document.querySelectorAll('.like-btn').forEach(btn => {
    btn.addEventListener('click', function () {
      const ideaId = this.dataset.ideaId;
      fetch(`${window.APP_BASE_URL}/ajax/like_idea.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `idea_id=${ideaId}&csrf_token=${encodeURIComponent(csrfToken)}`
      })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          this.classList.toggle('liked', data.liked);
          this.querySelector('.like-count').textContent = data.count;
        } else if (data.login_required) {
          window.location.href = `${window.APP_BASE_URL}/login.php`;
        }
      });
    });
  });

  /* ---------- Bookmark Idea (AJAX) ---------- */
  document.querySelectorAll('.bookmark-btn').forEach(btn => {
    btn.addEventListener('click', function () {
      const ideaId = this.dataset.ideaId;
      fetch(`${window.APP_BASE_URL}/ajax/bookmark_idea.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `idea_id=${ideaId}&csrf_token=${encodeURIComponent(csrfToken)}`
      })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          this.classList.toggle('saved', data.bookmarked);
          const label = this.querySelector('.bookmark-label');
          if (label) label.textContent = data.bookmarked ? 'Saved' : 'Save';
        } else if (data.login_required) {
          window.location.href = `${window.APP_BASE_URL}/login.php`;
        }
      });
    });
  });

  /* ---------- Share Idea ---------- */
  document.querySelectorAll('.share-btn').forEach(btn => {
    btn.addEventListener('click', function () {
      const url = this.dataset.url;
      if (navigator.share) {
        navigator.share({ title: 'Check out this startup idea', url });
      } else {
        navigator.clipboard.writeText(url).then(() => {
          this.innerHTML = '<i class="bi bi-check2"></i> Copied!';
          setTimeout(() => { this.innerHTML = '<i class="bi bi-share"></i> Share'; }, 1500);
        });
      }
    });
  });

  /* ---------- Submit Comment (AJAX) ---------- */
  const commentForm = document.getElementById('commentForm');
  if (commentForm) {
    commentForm.addEventListener('submit', function (e) {
      e.preventDefault();
      const formData = new FormData(commentForm);
      fetch(`${window.APP_BASE_URL}/ajax/add_comment.php`, { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
          if (data.success) {
            window.location.reload();
          } else if (data.login_required) {
            window.location.href = `${window.APP_BASE_URL}/login.php`;
          } else {
            alert(data.message || 'Could not post comment.');
          }
        });
    });
  }

  /* ---------- Reply toggle ---------- */
  document.querySelectorAll('.reply-toggle').forEach(btn => {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      const target = document.getElementById('reply-form-' + this.dataset.commentId);
      if (target) target.classList.toggle('d-none');
    });
  });

  /* ---------- Reply forms (AJAX) ---------- */
  document.querySelectorAll('.comment-reply-form').forEach(form => {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      const formData = new FormData(form);
      fetch(`${window.APP_BASE_URL}/ajax/add_comment.php`, { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
          if (data.success) {
            window.location.reload();
          } else if (data.login_required) {
            window.location.href = `${window.APP_BASE_URL}/login.php`;
          } else {
            alert(data.message || 'Could not post reply.');
          }
        });
    });
  });

  /* ---------- Mark all notifications read ---------- */
  const markAllBtn = document.getElementById('markAllReadBtn');
  if (markAllBtn) {
    markAllBtn.addEventListener('click', function () {
      fetch(`${window.APP_BASE_URL}/ajax/mark_notifications_read.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `csrf_token=${encodeURIComponent(csrfToken)}`
      }).then(() => window.location.reload());
    });
  }

  /* ---------- Bootstrap form validation ---------- */
  document.querySelectorAll('.needs-validation').forEach(form => {
    form.addEventListener('submit', function (e) {
      if (!form.checkValidity()) {
        e.preventDefault();
        e.stopPropagation();
      }
      form.classList.add('was-validated');
    });
  });

  /* ---------- Password strength / confirm match ---------- */
  const pw = document.getElementById('password');
  const pwConfirm = document.getElementById('confirm_password');
  if (pw && pwConfirm) {
    function checkMatch() {
      pwConfirm.setCustomValidity(pw.value !== pwConfirm.value ? 'Passwords do not match' : '');
    }
    pw.addEventListener('input', checkMatch);
    pwConfirm.addEventListener('input', checkMatch);
  }

  /* ---------- Live image preview for uploads ---------- */
  const imgInput = document.getElementById('image');
  const imgPreview = document.getElementById('imagePreview');
  if (imgInput && imgPreview) {
    imgInput.addEventListener('change', function () {
      if (this.files && this.files[0]) {
        const reader = new FileReader();
        reader.onload = e => { imgPreview.src = e.target.result; imgPreview.classList.remove('d-none'); };
        reader.readAsDataURL(this.files[0]);
      }
    });
  }

  /* ---------- Collaboration request accept/reject (AJAX) ---------- */
  document.querySelectorAll('.collab-action-btn').forEach(btn => {
    btn.addEventListener('click', function () {
      const reqId = this.dataset.requestId;
      const action = this.dataset.action;
      fetch(`${window.APP_BASE_URL}/ajax/handle_collab_request.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `request_id=${reqId}&action=${action}&csrf_token=${encodeURIComponent(csrfToken)}`
      })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          const row = document.getElementById('collab-row-' + reqId);
          if (row) row.remove();
        } else {
          alert(data.message || 'Action failed.');
        }
      });
    });
  });

});
