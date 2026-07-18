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

window.addEventListener('beforeunload', () => {
    if (typeof allocationChart !== 'undefined' && allocationChart) allocationChart.destroy();
    if (typeof projectionChart !== 'undefined' && projectionChart) projectionChart.destroy();
    if (typeof growthChart !== 'undefined' && growthChart) growthChart.destroy();
    if (typeof balanceChart !== 'undefined' && balanceChart) balanceChart.destroy();
    if (typeof cumulativeChart !== 'undefined' && cumulativeChart) cumulativeChart.destroy();
});

// --- Dashboard Builder Logic ---
const customizeBtn = document.getElementById('customizeDashboardBtn');
const saveBtn = document.getElementById('saveDashboardBtn');
const resetBtn = document.getElementById('resetDashboardBtn');
const dashboardGrid = document.getElementById('dashboardGrid');

let editMode = false;
let draggedItem = null;

if (customizeBtn && dashboardGrid) {
    // Toggle Edit Mode
    customizeBtn.addEventListener('click', () => {
        editMode = true;
        document.body.classList.add('dashboard-editing');
        customizeBtn.style.display = 'none';
        saveBtn.style.display = 'inline-flex';
        resetBtn.style.display = 'inline-flex';

        document.querySelectorAll('.dashboard-widget').forEach(w => {
            w.draggable = true;
            w.style.display = w.dataset.visible === '0' ? 'none' : 'block';
        });
    });

    saveBtn.addEventListener('click', () => {
        editMode = false;
        document.body.classList.remove('dashboard-editing');
        saveBtn.style.display = 'none';
        resetBtn.style.display = 'none';
        customizeBtn.style.display = 'inline-flex';

        document.querySelectorAll('.dashboard-widget').forEach(w => {
            w.draggable = false;
        });

        saveLayout();
    });

    resetBtn.addEventListener('click', async () => {
        if (!confirm('Reset dashboard to default layout? All hidden widgets will reappear.')) return;

        const formData = new FormData();
        formData.append('reset', '1');
        formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content);

        try {
            await fetch('/dashboard/save-layout', { method: 'POST', body: formData });
            location.reload();
        } catch (err) {
            console.error('Failed to reset layout', err);
        }
    });

    // Drag and Drop Events
    dashboardGrid.addEventListener('dragstart', (e) => {
        if (!e.target.classList.contains('dashboard-widget')) return;
        draggedItem = e.target;
        setTimeout(() => e.target.classList.add('dragging'), 0);
    });

    dashboardGrid.addEventListener('dragend', (e) => {
        if (draggedItem) {
            draggedItem.classList.remove('dragging');
            draggedItem = null;
        }
    });

    dashboardGrid.addEventListener('dragover', (e) => {
        e.preventDefault();
        if (!draggedItem) return;

        const afterElement = getDragAfterElement(e.clientY);
        if (afterElement == null) {
            dashboardGrid.appendChild(draggedItem);
        } else {
            dashboardGrid.insertBefore(draggedItem, afterElement);
        }
    });

    function getDragAfterElement(y) {
        const draggableElements = [...document.querySelectorAll('.dashboard-widget:not(.dragging)')];

        return draggableElements.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;
            if (offset < 0 && offset > closest.offset) {
                return { offset: offset, element: child };
            } else {
                return closest;
            }
        }, { offset: Number.NEGATIVE_INFINITY }).element;
    }

    // Hide Widget
    dashboardGrid.addEventListener('click', (e) => {
        const hideBtn = e.target.closest('.widget-hide-btn');
        if (hideBtn) {
            e.stopPropagation();
            const widget = hideBtn.closest('.dashboard-widget');
            widget.style.display = 'none';
            widget.dataset.visible = '0';
        }
    });

    // Save Layout via AJAX
    async function saveLayout() {
        const widgets = [];
        document.querySelectorAll('.dashboard-widget').forEach(w => {
            widgets.push({
                id: w.dataset.id,
                visible: w.dataset.visible !== '0',
                size: w.dataset.size || 'normal'
            });
        });

        const formData = new FormData();
        formData.append('layout', JSON.stringify({ widgets }));
        formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content);

        try {
            const res = await fetch('/dashboard/save-layout', { method: 'POST', body: formData });
            if (res.ok) {
                // Optional: Show a quick toast notification
                if (typeof showToast === 'function') {
                    showToast('Dashboard layout saved!', 'success', 2000);
                }
            }
        } catch (err) {
            console.error('Failed to save layout', err);
        }
    }
}

// --- Sidebar Scroll Position Preservation ---
document.addEventListener('DOMContentLoaded', () => {
    const sidebarNav = document.querySelector('.sidebar-nav');

    if (sidebarNav) {
        // 1. Restore scroll position on page load
        const savedScroll = sessionStorage.getItem('sidebarScrollPosition');
        if (savedScroll) {
            sidebarNav.scrollTop = parseInt(savedScroll, 10);
        }

        // 2. Save scroll position right before navigating to a new page
        const navLinks = sidebarNav.querySelectorAll('a.nav-item');
        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                sessionStorage.setItem('sidebarScrollPosition', sidebarNav.scrollTop);
            });
        });
    }
});

// ==========================================
// Financial Planning Studio Logic
// ==========================================
function initFinancialPlanningStudio() {
    const grossIncomeInput = document.getElementById('grossIncome');
    if (!grossIncomeInput) return; // Exit safely if not on the studio page

    const sym = window.budgetSym || '$';
    const activeWorkspace = window.activeWorkspace || null;

    let allocationChart = null;
    let projectionChart = null;

    const buckets = [
        { id: 'needs', name: 'Needs (Rent, Food, Utils)', percent: activeWorkspace?.buckets?.needs ?? 50, color: '#3b82f6' },
        { id: 'wants', name: 'Wants (Entertainment, Dining)', percent: activeWorkspace?.buckets?.wants ?? 30, color: '#8b5cf6' },
        { id: 'savings', name: 'Savings & Debt', percent: activeWorkspace?.buckets?.savings ?? 20, color: '#10b981' }
    ];

    function initSliders() {
        const container = document.getElementById('allocationList');
        if (!container) return;
        container.innerHTML = '';
        buckets.forEach(b => {
            const div = document.createElement('div');
            div.innerHTML = `
                <div class="flex-between" style="margin-bottom: 0.25rem;">
                    <label style="margin:0; font-weight:600; color: ${b.color}">${b.name}</label>
                    <span class="sensitive-data" id="val_${b.id}" style="font-weight:bold;">0.00</span>
                </div>
                <input type="range" min="0" max="100" value="${b.percent}" id="slider_${b.id}" 
                       style="width:100%; accent-color: ${b.color};" oninput="window.updateBudgetBucket('${b.id}', this.value)">
            `;
            container.appendChild(div);
        });
    }

    window.updateBudgetBucket = function (id, val) {
        const b = buckets.find(x => x.id === id);
        if (b) {
            b.percent = parseInt(val) || 0;
            recalculate();
        }
    };

    function recalculate() {
        let gross = parseFloat(grossIncomeInput.value) || 0;
        if (gross < 0) gross = 0;
        grossIncomeInput.value = gross;

        const taxInput = document.getElementById('taxRate');
        let tax = parseFloat(taxInput?.value) || 0;
        if (tax < 0) tax = 0;
        if (tax > 100) tax = 100;
        if (taxInput) taxInput.value = tax;

        const net = gross * (1 - (tax / 100));
        const netDisplay = document.getElementById('netIncomeDisplay');
        if (netDisplay) netDisplay.textContent = sym + net.toFixed(2);

        // Update hidden inputs for scenario saving
        const saveGross = document.getElementById('save_gross_income');
        const saveTax = document.getElementById('save_tax_rate');
        if (saveGross) saveGross.value = gross;
        if (saveTax) saveTax.value = tax;

        let allocatedPct = 0;
        let savingsAmount = 0;

        buckets.forEach(b => {
            const amount = net * (b.percent / 100);
            const valEl = document.getElementById('val_' + b.id);
            if (valEl) valEl.textContent = sym + amount.toFixed(2);
            allocatedPct += b.percent;
            if (b.id === 'savings') savingsAmount = amount;

            const saveBucket = document.getElementById('save_bucket_' + b.id);
            if (saveBucket) saveBucket.value = b.percent;
        });

        const remainingPct = 100 - allocatedPct;
        const remainingAmt = net * (remainingPct / 100);
        const remDisplay = document.getElementById('remainingDisplay');
        const remLabel = document.getElementById('remainingLabel');

        if (remDisplay && remLabel) {
            if (remainingPct < 0) {
                remLabel.textContent = 'Budget Deficit:';
                remDisplay.textContent = '-' + sym + Math.abs(remainingAmt).toFixed(2) + ' (' + Math.abs(remainingPct) + '%)';
                remDisplay.style.color = 'var(--danger)';
            } else {
                remLabel.textContent = 'Unallocated / Remaining:';
                remDisplay.textContent = sym + remainingAmt.toFixed(2) + ' (' + remainingPct + '%)';
                remDisplay.style.color = remainingPct === 0 ? 'var(--success)' : 'var(--accent)';
            }
        }

        updateCharts(savingsAmount);
        updateApplyButton(savingsAmount);
    }

    function updateCharts(savings) {
        const canvas1 = document.getElementById('allocationChart');
        if (canvas1) {
            const ctx1 = canvas1.getContext('2d');
            const chartData = buckets.map(b => Math.max(0, b.percent));

            // Safely destroy existing chart if it exists on this canvas to prevent "Canvas is already in use" error
            const existingChart1 = Chart.getChart(canvas1);
            if (existingChart1) existingChart1.destroy();

            allocationChart = new Chart(ctx1, {
                type: 'doughnut',
                data: {
                    labels: buckets.map(b => b.name),
                    datasets: [{ data: chartData, backgroundColor: buckets.map(b => b.color), borderWidth: 0 }]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
            });
        }

        const canvas2 = document.getElementById('projectionChart');
        if (canvas2) {
            const ctx2 = canvas2.getContext('2d');
            const labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            const data = [];
            let cumulative = 0;
            for (let i = 0; i < 12; i++) {
                cumulative += savings;
                data.push(Math.max(0, cumulative));
            }

            const existingChart2 = Chart.getChart(canvas2);
            if (existingChart2) existingChart2.destroy();

            projectionChart = new Chart(ctx2, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Cumulative Savings',
                        data: data,
                        backgroundColor: savings >= 0 ? '#10b981' : '#ef4444',
                        borderRadius: 4
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
            });
        }
    }

    function updateApplyButton(monthlySavings) {
        const annual = monthlySavings * 12;
        const hiddenTarget = document.getElementById('hiddenTarget');
        const hiddenMonthly = document.getElementById('hiddenMonthly');
        const btn = document.getElementById('applyBtn');

        if (hiddenTarget) hiddenTarget.value = annual;
        if (hiddenMonthly) hiddenMonthly.value = monthlySavings;

        if (btn) {
            btn.disabled = (annual <= 0);
            if (annual <= 0) {
                btn.innerHTML = '<i class="fas fa-ban"></i> Increase Savings to Apply';
                btn.style.opacity = '0.6';
                btn.style.cursor = 'not-allowed';
            } else {
                btn.innerHTML = '<i class="fas fa-rocket"></i> Create Savings Goal (<span id="projectedTotal">' + sym + annual.toFixed(2) + '</span>)';
                btn.style.opacity = '1';
                btn.style.cursor = 'pointer';
            }
        }
    }

    window.applyWhatIf = function (type, value) {
        const incomeInput = document.getElementById('grossIncome');
        if (!incomeInput) return;
        let current = parseFloat(incomeInput.value) || 0;

        if (type === 'raise') {
            incomeInput.value = (current * 1.10).toFixed(2);
            if (typeof showToast === 'function') showToast('Simulated 10% Raise!', 'success');
        } else if (type === 'cut') {
            incomeInput.value = (current * 0.85).toFixed(2);
            if (typeof showToast === 'function') showToast('Simulated 15% Pay Cut', 'error');
        } else if (type === 'inflation') {
            const needsSlider = document.getElementById('slider_needs');
            if (needsSlider) {
                needsSlider.value = Math.min(100, parseInt(needsSlider.value) + 5);
                window.updateBudgetBucket('needs', needsSlider.value);
                if (typeof showToast === 'function') showToast('Simulated 5% Inflation Impact', 'error');
            }
        } else if (type === 'bonus') {
            incomeInput.value = (current + (value || 5000)).toFixed(2);
            if (typeof showToast === 'function') showToast(`Simulated $${value || 5000} Bonus`, 'success');
        } else if (type === 'newbaby') {
            const needsSlider = document.getElementById('slider_needs');
            const savingsSlider = document.getElementById('slider_savings');
            if (needsSlider && savingsSlider) {
                needsSlider.value = Math.min(100, parseInt(needsSlider.value) + 10);
                savingsSlider.value = Math.max(0, parseInt(savingsSlider.value) - 10);
                window.updateBudgetBucket('needs', needsSlider.value);
                window.updateBudgetBucket('savings', savingsSlider.value);
                if (typeof showToast === 'function') showToast('Simulated New Baby Expenses', 'error');
            }
        } else if (type === 'reset') {
            if (activeWorkspace) {
                incomeInput.value = activeWorkspace.gross_income || 3000;
                const taxInput = document.getElementById('taxRate');
                if (taxInput) taxInput.value = activeWorkspace.tax_rate || 20;
                buckets.forEach(b => {
                    b.percent = activeWorkspace.buckets?.[b.id] ?? (b.id === 'needs' ? 50 : b.id === 'wants' ? 30 : 20);
                    const slider = document.getElementById('slider_' + b.id);
                    if (slider) slider.value = b.percent;
                });
            } else {
                incomeInput.value = 3000;
                const taxInput = document.getElementById('taxRate');
                if (taxInput) taxInput.value = 20;
                buckets[0].percent = 50; buckets[1].percent = 30; buckets[2].percent = 20;
                buckets.forEach(b => {
                    const slider = document.getElementById('slider_' + b.id);
                    if (slider) slider.value = b.percent;
                });
            }
            if (typeof showToast === 'function') showToast('Workspace Reset', 'success');
        }
        recalculate();
    };

    window.applyTemplate = function (jsonString) {
        if (!jsonString) return;
        try {
            const allocs = JSON.parse(jsonString);
            if (allocs.needs !== undefined && allocs.wants !== undefined && allocs.savings !== undefined) {
                buckets.forEach(b => {
                    b.percent = allocs[b.id] || 0;
                    const slider = document.getElementById('slider_' + b.id);
                    if (slider) slider.value = b.percent;
                });
                recalculate();
                if (typeof showToast === 'function') showToast('Template Applied!', 'success');
            }
        } catch (e) {
            console.error('Invalid template data', e);
        }
        const templateSelect = document.getElementById('templateSelect');
        if (templateSelect) templateSelect.value = '';
    };

    window.handleSaveTemplate = function (e) {
        e.preventDefault();
        const tmplNeeds = document.getElementById('tmpl_needs');
        const tmplWants = document.getElementById('tmpl_wants');
        const tmplSavings = document.getElementById('tmpl_savings');

        if (tmplNeeds) tmplNeeds.value = buckets.find(b => b.id === 'needs').percent;
        if (tmplWants) tmplWants.value = buckets.find(b => b.id === 'wants').percent;
        if (tmplSavings) tmplSavings.value = buckets.find(b => b.id === 'savings').percent;

        const form = document.getElementById('saveTemplateForm');
        if (!form) return;

        const formData = new FormData(form);
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        if (csrfToken) formData.append('csrf_token', csrfToken);

        fetch('template/save', {
            method: 'POST',
            body: formData
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (typeof showToast === 'function') showToast(data.message, 'success');
                    const modal = document.getElementById('saveTemplateModal');
                    if (modal) modal.style.display = 'none';
                    setTimeout(() => location.reload(), 1000);
                } else {
                    if (typeof showToast === 'function') showToast(data.message || 'Failed to save template', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                if (typeof showToast === 'function') showToast('Failed to save template', 'error');
            });
    };

    // Initialize
    initSliders();
    const taxInput = document.getElementById('taxRate');
    if (taxInput) taxInput.addEventListener('input', recalculate);
    recalculate();
}


// ==========================================
// Loan & Debt Simulator Logic
// ==========================================
const loanSym = window.budgetSym || '$';

window.handleAddLoan = async function (e) {
    e.preventDefault();
    const form = document.getElementById('loanForm');
    if (!form) return;

    const formData = new FormData(form);
    formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content);

    try {
        const res = await fetch('loan/add', { method: 'POST', body: formData });
        const contentType = res.headers.get('content-type');

        if (contentType && contentType.includes('application/json')) {
            const data = await res.json();
            if (data.success) {
                if (typeof showToast === 'function') showToast('Simulated loan added!', 'success');
                form.reset();
                const addLoanForm = document.getElementById('addLoanForm');
                if (addLoanForm) addLoanForm.style.display = 'none';
                loadSimulatedLoans();
            } else {
                console.error('Server returned error:', data);
                if (typeof showToast === 'function') showToast(data.message || 'Failed to add loan', 'error');
            }
        } else {
            const text = await res.text();
            console.error('Expected JSON but received HTML. Server response snippet:', text.substring(0, 200));
            if (typeof showToast === 'function') showToast('Server error. Check browser console (F12) for details.', 'error');
        }
    } catch (err) {
        console.error('Fetch error:', err);
        if (typeof showToast === 'function') showToast('Failed to add loan', 'error');
    }
};

window.loadSimulatedLoans = async function () {
    const container = document.getElementById('loanListContainer');
    if (!container) return;

    const scenarioId = new URLSearchParams(window.location.search).get('scenario');

    let url = 'loan/list';
    if (scenarioId) {
        url += `?scenario_id=${encodeURIComponent(scenarioId)}`;
    }


    try {
        const res = await fetch(url);


        if (!res.ok) {
            const errorText = await res.text();
            throw new Error(`HTTP error! status: ${res.status}. Check if route exists in public/index.php`);
        }

        const contentType = res.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('Server returned HTML instead of JSON. This usually means the URL is pointing outside your app directory.');
        }

        const data = await res.json();

        if (data.success) {
            renderLoans(data.loans || []);
        } else {
            console.error('❌ DEBUG: API returned success=false:', data);
            container.innerHTML = `<p class="text-secondary text-center" style="padding: 2rem; color: var(--danger);">${data.message || 'Failed to load loans.'}</p>`;
        }
    } catch (e) {
        console.error('❌ DEBUG: Loan list fetch failed:', e);
        container.innerHTML = '<p class="text-secondary text-center" style="padding: 2rem; color: var(--danger);">Failed to load loans. Check browser console (F12) for details.</p>';
    }
};

window.renderLoans = function (loans) {
    const container = document.getElementById('loanListContainer');
    if (!container) return;

    if (!loans || loans.length === 0) {
        container.innerHTML = '<p class="text-secondary text-center" style="padding: 2rem;">No simulated loans added yet.</p>';
        return;
    }

    let html = '<div class="grid grid-2" style="gap: 1rem;">';
    loans.forEach(loan => {
        html += `
            <div class="glass" style="padding: 1rem; border-radius: 8px; border-left: 4px solid #ef4444;">
                <div class="flex-between">
                    <h4 style="margin:0;">${loan.name}</h4>
                    <button class="btn-icon" style="color: var(--danger);" onclick="deleteSimulatedLoan(${loan.id})"><i class="fas fa-trash"></i></button>
                </div>
                <div class="grid grid-2 mt-2" style="font-size: 0.9rem; color: var(--text-secondary);">
                    <div>Principal: <strong style="color: var(--text-primary);">${loanSym}${parseFloat(loan.principal).toFixed(2)}</strong></div>
                    <div>Rate: <strong style="color: var(--text-primary);">${loan.annual_interest_rate}%</strong></div>
                    <div>Term: <strong style="color: var(--text-primary);">${loan.term_months} mos</strong></div>
                    <div>Extra Pmt: <strong style="color: var(--success);">${loanSym}${parseFloat(loan.extra_monthly_payment).toFixed(2)}</strong></div>
                </div>
                <div id="amortization-${loan.id}" style="margin-top: 1rem; font-size: 0.85rem; color: var(--accent);">Calculating projection...</div>
            </div>`;

        calculateAndRenderAmortization(loan.id, loan.principal, loan.annual_interest_rate, loan.term_months, loan.extra_monthly_payment);
    });
    html += '</div>';
    container.innerHTML = html;
};

window.calculateAndRenderAmortization = async function (loanId, principal, rate, term, extra) {
    try {
        const res = await fetch(`loan/amortization?principal=${principal}&rate=${rate}&term=${term}&extra=${extra}`);
        const data = await res.json();
        if (data.success && document.getElementById(`amortization-${loanId}`)) {
            const el = document.getElementById(`amortization-${loanId}`);
            const d = data.data;
            el.innerHTML = `
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                    <span>Monthly Payment: <strong>${loanSym}${d.monthly_payment}</strong></span>
                    <span>Total Interest: <strong style="color: var(--danger);">${loanSym}${d.total_interest}</strong></span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span>Payoff Time: <strong>${d.actual_months} months</strong></span>
                    ${d.months_saved > 0 ? `<span style="color: var(--success);">You save <strong>${d.months_saved} months</strong>!</span>` : ''}
                </div>
            `;
        }
    } catch (e) {
        console.error('Amortization calc failed', e);
    }
};

window.deleteSimulatedLoan = async function (id) {
    if (!confirm('Remove this simulated loan?')) return;
    try {
        const formData = new FormData();
        formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content);
        await fetch(`loan/delete/${id}`, { method: 'POST', body: formData });
        loadSimulatedLoans();
        if (typeof showToast === 'function') showToast('Loan removed', 'success');
    } catch (e) {
        console.error(e);
    }
};

document.addEventListener('click', (e) => {
    const dropdown = document.getElementById('scenarioDropdown');
    if (!dropdown) return;

    const isDropdown = dropdown.contains(e.target);
    const isButton = e.target.closest('button')?.getAttribute('onclick')?.includes('scenarioDropdown');

    if (!isDropdown && !isButton) {
        dropdown.style.display = 'none';
    }
});

document.addEventListener('DOMContentLoaded', () => {
    initFinancialPlanningStudio();
    if (document.getElementById('loanListContainer')) {
        loadSimulatedLoans();
    }
});

// ==========================================
// Investment Simulator Logic
// ==========================================
window.handleAddInvestment = async function (e) {
    e.preventDefault();
    const form = document.getElementById('investmentForm');
    if (!form) return;

    const formData = new FormData(form);
    formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content);

    try {
        const res = await fetch('investment/add', { method: 'POST', body: formData });

        const contentType = res.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const errorText = await res.text();
            console.error('❌ Server returned HTML instead of JSON. Snippet:', errorText.substring(0, 300));
            if (typeof showToast === 'function') {
                showToast('Server error. Check browser console (F12) for details.', 'error');
            }
            return;
        }
        const data = await res.json();
        if (data.success) {
            if (typeof showToast === 'function') showToast('Simulated asset added!', 'success');
            form.reset();
            document.getElementById('addInvestmentForm').style.display = 'none';
            loadSimulatedInvestments();
        } else {
            if (typeof showToast === 'function') showToast(data.message || 'Failed to add asset', 'error');
        }
    } catch (err) {
        console.error('Investment add fetch error:', err);
        if (typeof showToast === 'function') showToast('Failed to add asset', 'error');
    }
};

window.loadSimulatedInvestments = async function () {
    const scenarioId = new URLSearchParams(window.location.search).get('scenario') || '';
    try {
        const url = scenarioId ? `investment/list?scenario_id=${encodeURIComponent(scenarioId)}` : 'investment/list';
        const res = await fetch(url);

        if (!res.ok) throw new Error(`HTTP ${res.status}`);

        const data = await res.json();
        renderInvestments(data.investments || []);
        loadUnifiedCashFlow();
    } catch (e) {
        console.error('Investment list fetch failed:', e);
    }
};

window.renderInvestments = function (investments) {
    const container = document.getElementById('investmentListContainer');
    if (!container) return;

    if (!investments || investments.length === 0) {
        container.innerHTML = '<p class="text-secondary text-center" style="padding: 2rem;">No simulated investments added yet.</p>';
        return;
    }

    let html = '<div class="grid grid-2" style="gap: 1rem;">';
    investments.forEach(inv => {
        html += `
            <div class="glass" style="padding: 1rem; border-radius: 8px; border-left: 4px solid #10b981;">
                <div class="flex-between">
                    <h4 style="margin:0;">${inv.name} <span class="badge" style="background:var(--border-color); font-size:0.7rem;">${inv.asset_type}</span></h4>
                    <button class="btn-icon" style="color: var(--danger);" onclick="deleteSimulatedInvestment(${inv.id})"><i class="fas fa-trash"></i></button>
                </div>
                <div class="grid grid-2 mt-2" style="font-size: 0.9rem; color: var(--text-secondary);">
                    <div>Initial: <strong style="color: var(--text-primary);">${window.budgetSym || '$'}${parseFloat(inv.initial_investment).toFixed(2)}</strong></div>
                    <div>Monthly: <strong style="color: var(--success);">${window.budgetSym || '$'}${parseFloat(inv.monthly_contribution).toFixed(2)}</strong></div>
                    <div>Return: <strong style="color: var(--accent);">${inv.annual_return_rate}%</strong></div>
                    <div>Term: <strong style="color: var(--text-primary);">${inv.term_months} mos</strong></div>
                </div>
                <div id="investment-projection-${inv.id}" style="margin-top: 1rem; font-size: 0.85rem; color: var(--accent);">Calculating projection...</div>
            </div>`;

        calculateInvestmentProjection(inv.id, inv.initial_investment, inv.monthly_contribution, inv.annual_return_rate, inv.annual_fee_rate, inv.term_months);
    });
    html += '</div>';
    container.innerHTML = html;
};

window.calculateInvestmentProjection = function (id, initial, monthly, rate, fee, months) {
    const monthlyRate = parseFloat(rate) / 100 / 12;
    const monthlyFee = parseFloat(fee) / 100 / 12;
    let balance = parseFloat(initial);
    const totalMonths = parseInt(months);

    for (let i = 0; i < totalMonths; i++) {
        balance = (balance * (1 + monthlyRate - monthlyFee)) + parseFloat(monthly);
    }

    const el = document.getElementById(`investment-projection-${id}`);
    if (el) {
        el.innerHTML = `Projected Value: <strong style="font-size: 1.1rem;">${window.budgetSym || '$'}${balance.toFixed(2)}</strong>`;
    }
};

window.deleteSimulatedInvestment = async function (id) {
    if (!confirm('Remove this simulated asset?')) return;
    try {
        const formData = new FormData();
        formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content);
        await fetch(`investment/delete/${id}`, { method: 'POST', body: formData });
        loadSimulatedInvestments();
        if (typeof showToast === 'function') showToast('Asset removed', 'success');
    } catch (e) {
        console.error(e);
    }
};

// ==========================================
// Unified Cash Flow Chart Logic
// ==========================================
window.loadUnifiedCashFlow = async function () {
    const scenarioId = new URLSearchParams(window.location.search).get('scenario') || '';
    try {
        const url = scenarioId ? `unified-cash-flow?scenario_id=${encodeURIComponent(scenarioId)}&months=12` : 'unified-cash-flow?months=12';
        const res = await fetch(url);

        const contentType = res.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const errorText = await res.text();
            console.error('❌ Server returned HTML instead of JSON. Snippet:', errorText.substring(0, 200));
            return;
        }

        const data = await res.json();

        if (data.success) {
            renderUnifiedCashFlowChart(data.unified_cash_flow);
        } else {
            console.warn('⚠️ Cash flow calculation warning:', data.message);
        }
    } catch (e) {
        console.error('❌ Unified cash flow fetch failed:', e);
    }
};

window.renderUnifiedCashFlowChart = function (flowData) {
    const canvas = document.getElementById('unifiedCashFlowChart');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');

    const existingChart = Chart.getChart(canvas);
    if (existingChart) existingChart.destroy();

    const labels = flowData.map(d => `Month ${d.month}`);
    const liveData = flowData.map(d => d.live_net_income);
    const simData = flowData.map(d => d.simulated_net_income);

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Live Net Income',
                    data: liveData,
                    borderColor: '#94a3b8',
                    backgroundColor: 'rgba(148, 163, 184, 0.1)',
                    fill: true,
                    tension: 0.4
                },
                {
                    label: 'Simulated Net Income (Sandbox)',
                    data: simData,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    fill: true,
                    tension: 0.4,
                    borderDash: [5, 5]
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } },
            scales: { y: { beginAtZero: true } }
        }
    });
};

document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('loanListContainer')) loadSimulatedLoans();
    if (document.getElementById('investmentListContainer')) loadSimulatedInvestments();
});

// ==========================================
// Financial Health & Recommendations Logic
// ==========================================
window.loadFinancialHealth = async function () {
    const container = document.getElementById('recommendationsContainer');
    if (container) container.innerHTML = '<div class="text-center text-secondary" style="padding: 2rem;"><i class="fas fa-spinner fa-spin"></i> Analyzing your financial data...</div>';

    try {
        const res = await fetch('health-analysis');
        const data = await res.json();
        if (data.success) {
            renderHealthScore(data.health);
            renderRecommendations(data.recommendations);
        }
    } catch (e) {
        console.error('Health analysis failed', e);
        if (container) container.innerHTML = '<p class="text-danger text-center">Failed to load analysis.</p>';
    }
};

window.renderHealthScore = function (health) {
    document.getElementById('overallHealthScore').textContent = health.overall_score;
    document.getElementById('scoreSavings').textContent = health.scores.savings;
    document.getElementById('scoreDebt').textContent = health.scores.debt;
    document.getElementById('scoreEmergency').textContent = health.scores.emergency;

    const canvas = document.getElementById('healthScoreChart');
    if (canvas) {
        const ctx = canvas.getContext('2d');
        const existing = Chart.getChart(canvas);
        if (existing) existing.destroy();

        let color = '#ef4444';
        if (health.overall_score >= 80) color = '#10b981';
        else if (health.overall_score >= 50) color = '#f59e0b';

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                datasets: [{
                    data: [health.overall_score, 100 - health.overall_score],
                    backgroundColor: [color, 'rgba(0,0,0,0.05)'],
                    borderWidth: 0
                }]
            },
            options: {
                cutout: '80%',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { tooltip: { enabled: false }, legend: { display: false } }
            }
        });
    }
};

window.renderRecommendations = function (recs) {
    const container = document.getElementById('recommendationsContainer');
    if (!container) return;

    if (!recs || recs.length === 0) {
        container.innerHTML = '<p class="text-secondary text-center">No recommendations at this time.</p>';
        return;
    }

    let html = '<div style="display: flex; flex-direction: column; gap: 0.75rem;">';
    recs.forEach(rec => {
        html += `
            <div style="display: flex; gap: 1rem; padding: 1rem; background: rgba(0,0,0,0.02); border-radius: 8px; border-left: 4px solid ${rec.color};">
                <div style="width: 40px; height: 40px; border-radius: 50%; background: ${rec.color}20; color: ${rec.color}; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i class="fas ${rec.icon}"></i>
                </div>
                <div>
                    <h4 style="margin: 0 0 0.25rem; font-size: 0.95rem; color: var(--text-primary);">${rec.title}</h4>
                    <p style="margin: 0; font-size: 0.85rem; color: var(--text-secondary); line-height: 1.4;">${rec.description}</p>
                </div>
            </div>`;
    });
    html += '</div>';
    container.innerHTML = html;
};

if (document.getElementById('healthScoreChart')) {
    loadFinancialHealth();
}

// ==========================================
// Monte Carlo Probability Simulator Logic
// ==========================================
let monteCarloChart = null;

window.runMonteCarlo = async function (e) {
    e.preventDefault();
    const form = document.getElementById('monteCarloForm');
    const formData = new FormData(form);
    formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content);

    const btn = form.querySelector('button[type="submit"]');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Simulating...';
    btn.disabled = true;

    try {
        const res = await fetch('/sandbox/monte-carlo', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
            document.getElementById('monteCarloResults').style.display = 'block';
            document.getElementById('mcProbability').textContent = data.data.probability_of_success + '%';
            document.getElementById('mcProbability').style.color = data.data.probability_of_success >= 70 ? 'var(--success)' : (data.data.probability_of_success >= 40 ? 'var(--accent)' : 'var(--danger)');
            document.getElementById('mcMedian').textContent = '$' + parseFloat(data.data.percentiles.median_50th).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            document.getElementById('mcBest').textContent = '$' + parseFloat(data.data.percentiles.best_case_90th).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            renderMonteCarloChart(data.data.median_schedule);
        } else {
            if (typeof showToast === 'function') showToast('Simulation failed', 'error');
        }
    } catch (err) {
        console.error(err);
        if (typeof showToast === 'function') showToast('Network error', 'error');
    } finally {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
};

window.renderMonteCarloChart = function (schedule) {
    const canvas = document.getElementById('monteCarloChart');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');

    if (monteCarloChart) monteCarloChart.destroy();

    const labels = schedule.map((_, i) => `Month ${i + 1}`);

    monteCarloChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Projected Median Balance',
                data: schedule,
                borderColor: '#8b5cf6',
                backgroundColor: 'rgba(139, 92, 246, 0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { callback: function (value) { return '$' + value / 1000 + 'k'; } } } }
        }
    });
};

// ==========================================
// Scenario Comparison Logic
// ==========================================
window.runComparison = async function (e) {
    e.preventDefault();
    const form = document.getElementById('comparisonForm');
    const formData = new FormData(form);
    formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content);

    try {
        const res = await fetch('compare', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success && data.data.length > 0) {
            document.getElementById('comparisonResults').style.display = 'block';
            renderComparisonTable(data.data);
        } else {
            if (typeof showToast === 'function') showToast('Select at least one scenario', 'error');
        }
    } catch (err) {
        console.error(err);
    }
};

window.renderComparisonTable = function (comparisonData) {
    const table = document.getElementById('comparisonTable');
    if (!table) return;

    let headerHtml = '<tr><th>Metric</th>';
    comparisonData.forEach(s => {
        headerHtml += `<th>${s.name}</th>`;
    });
    headerHtml += '</tr>';
    table.querySelector('thead').innerHTML = headerHtml;

    const metrics = ['gross_income', 'net_income', 'monthly_savings', 'annual_savings', '10_year_projection'];
    const labels = ['Gross Income', 'Net Income', 'Monthly Savings', 'Annual Savings', '10-Year Projection'];

    let bodyHtml = '';
    metrics.forEach((metric, index) => {
        bodyHtml += `<tr><td><strong>${labels[index]}</strong></td>`;

        const values = comparisonData.map(s => s[metric]);
        const maxVal = Math.max(...values);

        comparisonData.forEach(s => {
            const isMax = s[metric] === maxVal && values.length > 1;
            const style = isMax ? 'color: var(--success); font-weight: bold;' : '';
            bodyHtml += `<td style="${style}">$${parseFloat(s[metric]).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>`;
        });
        bodyHtml += '</tr>';
    });
    table.querySelector('tbody').innerHTML = bodyHtml;
};