const themeToggle = document.getElementById('theme-toggle');
const html = document.documentElement;

let currentPreference = html.getAttribute('data-theme') || 'system';
const savedTheme = localStorage.getItem('theme') || 'light';
html.setAttribute('data-theme', savedTheme);
updateThemeIcon(savedTheme);

if (themeToggle) {
    themeToggle.addEventListener('click', async () => {
        const cycle = { 'light': 'dark', 'dark': 'system', 'system': 'light' };
        currentPreference = cycle[currentPreference] || 'light';
        applyTheme(currentPreference);
    
        try {
            const formData = new FormData();
            formData.append('theme', currentPreference);
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            formData.append('csrf_token', csrfToken);
            await fetch('/preferences/update-theme', {
                method: 'POST',
                body: formData
            });
        } catch (err) {
            console.error('Failed to save theme preference:', err);
        }
    });
}

function applyTheme(preference) {
    let actualTheme = preference;
    if (preference === 'system') {
        actualTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }
    html.setAttribute('data-theme', actualTheme);
    updateThemeIcon(preference);
}

function updateThemeIcon(preference) {
    if (!themeToggle) return;
    const icon = themeToggle.querySelector('i');
    if (preference === 'system') {
        icon.className = 'fas fa-desktop';
    } else if (preference === 'dark') {
        icon.className = 'fas fa-sun';
    } else {
        icon.className = 'fas fa-moon';
    }
}
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('open');
}
document.addEventListener('click', (e) => {
    const sidebar = document.getElementById('sidebar');
    const menuBtn = document.querySelector('.btn-menu');
    if (sidebar && sidebar.classList.contains('open') &&
        !sidebar.contains(e.target) && !menuBtn.contains(e.target)) {
        sidebar.classList.remove('open');
    }
});

applyTheme(currentPreference);

window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
    if (currentPreference === 'system') {
        applyTheme('system');
    }
});
const searchModal = document.getElementById('searchModal');
const searchInput = document.getElementById('globalSearchInput');
const searchResults = document.getElementById('searchResults');

document.addEventListener('keydown', (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        if (searchModal) {
            searchModal.style.display = 'flex';
            searchInput?.focus();
        }
    }
    if (e.key === 'Escape' && searchModal) {
        searchModal.style.display = 'none';
    }
});

if (searchInput) {
    let debounceTimer;
    searchInput.addEventListener('input', (e) => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(async () => {
            const query = e.target.value.trim();
            if (query.length < 2) {
                searchResults.innerHTML = '';
                return;
            }
            searchResults.innerHTML = '<div style="padding:1rem; text-align:center; color:var(--text-secondary);">Searching...</div>';

            try {
                const res = await fetch(`<?= url('/api/search?q=') ?>` + encodeURIComponent(query));
                const data = await res.json();

                if (data.results.length === 0) {
                    searchResults.innerHTML = '<div style="padding:1rem; text-align:center; color:var(--text-secondary);">No results found.</div>';
                    return;
                }

                let html = '';
                let currentCategory = '';
                data.results.forEach(item => {
                    if (item.category !== currentCategory) {
                        currentCategory = item.category;
                        html += `<div class="search-category">${currentCategory}</div>`;
                    }
                    html += `<a href="${item.url}" class="search-item" onclick="document.getElementById('searchModal').style.display='none'">
                        <i class="${item.icon}"></i>
                        <div><strong>${item.title}</strong><br><small style="color:var(--text-secondary)">${item.subtitle}</small></div>
                    </a>`;
                });
                searchResults.innerHTML = html;
            } catch (err) {
                searchResults.innerHTML = '<div style="padding:1rem; text-align:center; color:var(--danger);">Search failed.</div>';
            }
        }, 300);
    });
}
function showToast(message, type = 'success', duration = 4000) {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
        <span>${message}</span>
        <button class="toast-close" onclick="this.parentElement.remove()">&times;</button>
    `;
    
    container.appendChild(toast);
    setTimeout(() => toast.classList.add('show'), 10);
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, duration);
}
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.alert-success, .alert-danger').forEach(alert => {
        const isDanger = alert.classList.contains('alert-danger');
        showToast(alert.textContent.trim(), isDanger ? 'error' : 'success');
        alert.remove(); 
    });
});
const privacyToggle = document.getElementById('privacy-toggle');
let isBlurred = document.body.classList.contains('privacy-blur');

function updatePrivacyIcon() {
    if (!privacyToggle) return;
    const icon = privacyToggle.querySelector('i');
    icon.className = isBlurred ? 'fas fa-eye-slash' : 'fas fa-eye';
}
updatePrivacyIcon(); 

privacyToggle?.addEventListener('click', async () => {
    isBlurred = !isBlurred;
    document.body.classList.toggle('privacy-blur', isBlurred);
    updatePrivacyIcon();
    try {
        const formData = new FormData();
        formData.append('privacy_blur', isBlurred ? '1' : '0');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        formData.append('csrf_token', csrfToken);
        await fetch('/preferences/update-privacy', { method: 'POST', body: formData });
    } catch (err) { console.error('Failed to save privacy preference:', err); }
});

document.addEventListener('click', (e) => {
    const eyeIcon = e.target.closest('.widget-eye-toggle');
    if (eyeIcon) {
        e.stopPropagation();
        const targetSelector = eyeIcon.dataset.target;
        const target = document.querySelector(targetSelector);
        if (target) {
            target.classList.toggle('revealed');
            eyeIcon.classList.toggle('fa-eye');
            eyeIcon.classList.toggle('fa-eye-slash');
        }
    }
});

const compactToggle = document.getElementById('compact-toggle');
let isCompact = document.body.classList.contains('compact-mode');

function updateCompactIcon() {
    if (!compactToggle) return;
    const icon = compactToggle.querySelector('i');
    icon.className = isCompact ? 'fas fa-expand-alt' : 'fas fa-compress-alt';
}
updateCompactIcon();

compactToggle?.addEventListener('click', async () => {
    isCompact = !isCompact;
    document.body.classList.toggle('compact-mode', isCompact);
    updateCompactIcon();
    try {
        const formData = new FormData();
        formData.append('compact_mode', isCompact ? '1' : '0');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        formData.append('csrf_token', csrfToken);
        await fetch('/preferences/update-compact', { method: 'POST', body: formData });
    } catch (err) { console.error('Failed to save compact preference:', err); }
});
const zenToggle = document.getElementById('zen-toggle');
const zenExitBtn = document.getElementById('zen-exit-btn');
let isZen = document.body.classList.contains('zen-mode');

function updateZenIcon() {
    if (!zenToggle) return;
    const icon = zenToggle.querySelector('i');
    icon.className = isZen ? 'fas fa-expand-alt' : 'fas fa-bullseye';
}
updateZenIcon();

function toggleZenMode() {
    isZen = !isZen;
    document.body.classList.toggle('zen-mode', isZen);
    updateZenIcon();
    try {
        const formData = new FormData();
        formData.append('zen_mode', isZen ? '1' : '0');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        formData.append('csrf_token', csrfToken);
        fetch('/preferences/update-zen', { method: 'POST', body: formData });
    } catch (err) { console.error('Failed to save zen preference:', err); }
}

zenToggle?.addEventListener('click', toggleZenMode);
zenExitBtn?.addEventListener('click', toggleZenMode);

// --- Auth Page Theme Toggle ---
const authThemeToggle = document.getElementById('auth-theme-toggle');
if (authThemeToggle) {
    let currentAuthTheme = document.documentElement.getAttribute('data-theme') || 'system';
    
    function updateAuthIcon(theme) {
        const icon = authThemeToggle.querySelector('i');
        if (theme === 'system') icon.className = 'fas fa-desktop';
        else if (theme === 'dark') icon.className = 'fas fa-sun';
        else icon.className = 'fas fa-moon';
    }
    
    updateAuthIcon(currentAuthTheme);

    authThemeToggle.addEventListener('click', () => {
        const cycle = { 'system': 'light', 'light': 'dark', 'dark': 'system' };
        currentAuthTheme = cycle[currentAuthTheme] || 'system';
        
        // Apply instantly
        let actualTheme = currentAuthTheme;
        if (currentAuthTheme === 'system') {
            actualTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }
        document.documentElement.setAttribute('data-theme', actualTheme);
        updateAuthIcon(currentAuthTheme);
        
        // Save to cookie for 30 days
        document.cookie = `theme_preference=${currentAuthTheme}; path=/; max-age=2592000; SameSite=Lax`;
    });
}