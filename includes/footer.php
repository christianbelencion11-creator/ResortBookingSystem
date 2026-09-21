            </div>
            <!-- PAGE CONTENT END -->
        </div>
    </div>

    <!-- TOAST CONTAINER -->
    <div class="toast-container" id="toastContainer"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // ---- Theme toggle (dark/light mode) ----
        document.getElementById('themeToggle')?.addEventListener('click', function() {
            var html = document.documentElement;
            var current = html.getAttribute('data-theme');
            var next = current === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-theme', next);
            localStorage.setItem('theme', next);
            showToast(next === 'dark' ? 'Dark mode enabled' : 'Light mode enabled', 'info');
        });

        // ---- Sidebar toggle (mobile) ----
        function toggleSidebar() {
            var sb = document.getElementById('sidebar');
            var ov = document.getElementById('sidebarOverlay');
            if (sb && ov) {
                sb.classList.toggle('open');
                ov.classList.toggle('show');
                document.body.classList.toggle('sidebar-open');
            }
        }

        // ---- Toast notification helper ----
        function showToast(message, type) {
            type = type || 'success';
            var icons = {
                success: 'bi-check-circle-fill',
                danger: 'bi-x-circle-fill',
                warning: 'bi-exclamation-triangle-fill',
                info: 'bi-info-circle-fill'
            };
            var container = document.getElementById('toastContainer');
            if (!container) return;
            var toast = document.createElement('div');
            toast.className = 'toast-alert ' + type;
            toast.innerHTML = '<i class="bi ' + (icons[type] || icons.info) + ' toast-icon"></i><span>' + message + '</span><button class="toast-close" onclick="this.parentElement.remove()"><i class="bi bi-x"></i></button>';
            container.appendChild(toast);
            setTimeout(function() { if (toast.parentElement) toast.remove(); }, 4500);
        }

        // Auto dismiss flash toast from PHP
        var flashToast = document.getElementById('sessionFlashToast');
        if (flashToast) {
            setTimeout(function() { if (flashToast.parentElement) flashToast.remove(); }, 4500);
        }

        // ---- Time ago helper ----
        function timeAgo(dateStr) {
            if (!dateStr) return '';
            var diff = (Date.now() - new Date(dateStr).getTime()) / 1000;
            if (diff < 60) return 'Just now';
            if (diff < 3600) return Math.floor(diff / 60) + ' min ago';
            if (diff < 86400) return Math.floor(diff / 3600) + ' hour(s) ago';
            if (diff < 604800) return Math.floor(diff / 86400) + ' day(s) ago';
            return new Date(dateStr).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        }

        // ---- Fetch notifications into dropdown ----
        function loadNotifications() {
            var notifList = document.getElementById('notifList');
            var notifDot = document.getElementById('notifDot');
            if (!notifList) return;

            fetch('<?= url("/api/notifications.php?action=get") ?>')
                .then(function(r) { return r.json(); })
                .then(function(items) {
                    if (!items || items.length === 0) {
                        notifList.innerHTML = '<div style="text-align:center; padding:24px; color:var(--text-muted); font-size:13px;"><i class="bi bi-bell-slash" style="font-size:24px; display:block; margin-bottom:8px;"></i>No notifications</div>';
                        if (notifDot) notifDot.style.display = 'none';
                        return;
                    }
                    var html = '';
                    items.forEach(function(n) {
                        var colorCls = n.Type === 'danger' ? 'red' : (n.Type === 'warning' ? 'orange' : (n.Type === 'success' ? 'green' : 'blue'));
                        var icon = n.Icon || 'bi-bell';
                        html += '<div class="notif-item ' + (n.IsRead == 0 ? 'unread' : '') + '">' +
                            '<div class="notif-icon ' + colorCls + '"><i class="bi ' + icon + '"></i></div>' +
                            '<div><div class="notif-text"><strong>' + (n.Title || '') + '</strong>: ' + (n.Message || '') + '</div>' +
                            '<div class="notif-time">' + timeAgo(n.CreatedAt) + '</div></div></div>';
                    });
                    notifList.innerHTML = html;
                    var unread = items.filter(function(i) { return i.IsRead == 0; });
                    if (notifDot) notifDot.style.display = unread.length > 0 ? '' : 'none';
                })
                .catch(function() {
                    if (notifList) notifList.innerHTML = '<div style="text-align:center; padding:20px; color:var(--text-muted); font-size:13px;">No new alerts</div>';
                });
        }

        function markAllNotificationsRead() {
            fetch('<?= url("/api/notifications.php?action=mark_all_read") ?>', { method: 'POST' })
                .then(function() {
                    document.querySelectorAll('.notif-item.unread').forEach(function(el) {
                        el.classList.remove('unread');
                    });
                    var dot = document.getElementById('notifDot');
                    if (dot) dot.style.display = 'none';
                    showToast('Notifications marked as read', 'info');
                });
        }

        // Notification dropdown click listeners
        document.getElementById('notifBtn')?.addEventListener('click', function(e) {
            e.stopPropagation();
            document.getElementById('notifDropdown')?.classList.toggle('show');
            document.getElementById('profileDropdown')?.classList.remove('show');
        });

        // Profile dropdown click listeners
        document.getElementById('profileBtn')?.addEventListener('click', function(e) {
            e.stopPropagation();
            document.getElementById('profileDropdown')?.classList.toggle('show');
            document.getElementById('notifDropdown')?.classList.remove('show');
        });

        // Close dropdowns on outside click
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.notif-dropdown-wrapper')) {
                document.getElementById('notifDropdown')?.classList.remove('show');
            }
            if (!e.target.closest('.profile-dropdown-wrapper')) {
                document.getElementById('profileDropdown')?.classList.remove('show');
            }
        });

        // Global search navigation
        document.getElementById('globalSearch')?.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                var q = this.value.trim();
                if (q) {
                    window.location.href = '<?= url("/search/index.php") ?>?q=' + encodeURIComponent(q);
                }
            }
        });

        // Preserve sidebar scroll position
        var sidebarNav = document.querySelector('.sidebar-nav');
        if (sidebarNav) {
            var savedScroll = sessionStorage.getItem('sidebarScroll');
            if (savedScroll) sidebarNav.scrollTop = parseInt(savedScroll);
            sidebarNav.addEventListener('scroll', function() {
                sessionStorage.setItem('sidebarScroll', sidebarNav.scrollTop);
            });
        }

        // Load notifications on page load
        loadNotifications();
    </script>
</body>
</html>
