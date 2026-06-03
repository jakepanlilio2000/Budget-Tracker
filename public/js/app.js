// ==========================================
// GLOBAL HELPERS
// ==========================================

// Count-up animation helper
window.animateValue = function(obj, end, duration = 500) {
    if (!obj) return;
    if (localStorage.getItem('pref_no_anim') === 'true') duration = 0;

    let startTimestamp = null;
    const startVal = parseFloat(obj.innerText.replace(/,/g, '')) || 0;
    const finalVal = parseFloat(end);
    
    if (duration === 0) {
        obj.innerText = finalVal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        return;
    }

    const step = (timestamp) => {
        if (!startTimestamp) startTimestamp = timestamp;
        const progress = Math.min((timestamp - startTimestamp) / duration, 1);
        const easeProgress = progress * (2 - progress);
        const current = startVal + (finalVal - startVal) * easeProgress;
        
        obj.innerText = current.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        if (progress < 1) {
            window.requestAnimationFrame(step);
        } else {
            obj.innerText = finalVal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }
    };
    window.requestAnimationFrame(step);
};

// Toast Notifications
window.showToast = (message, type = 'success') => {
    const container = document.getElementById('toast-container');
    if (!container) return;
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerText = message;
    container.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'fadeOut 0.3s ease forwards';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
};

// Confirm Dialog
window.confirmAction = (title, message, onConfirm) => {
    const modal = document.getElementById('confirm-modal');
    if (!modal) return;
    document.getElementById('confirm-title').innerText = title;
    document.getElementById('confirm-message').innerText = message;
    modal.classList.add('active');
    
    const okBtn = document.getElementById('confirm-ok');
    const newOkBtn = okBtn.cloneNode(true);
    okBtn.parentNode.replaceChild(newOkBtn, okBtn);
    
    newOkBtn.addEventListener('click', () => {
        modal.classList.remove('active');
        onConfirm();
    });
};


// ==========================================
// EVENT DELEGATION (SPA SAFE)
// ==========================================

// 1. ALL CLICK EVENTS
document.addEventListener('click', async (e) => {
    
    // --- Mobile Nav Toggle ---
    if (e.target.closest('#mobile-nav-toggle')) {
        document.querySelector('.sidebar')?.classList.toggle('open');
        return;
    }

    // --- Mobile Nav Auto-Close (When a link is clicked) ---
    if (e.target.closest('a.nav-item')) {
        document.querySelector('.sidebar')?.classList.remove('open');
    }

    // --- Period Tab Switcher ---
    const periodTab = e.target.closest('.period-tab');
    if (periodTab) {
        e.preventDefault(); 
        const date = periodTab.dataset.date;
        const pid = periodTab.dataset.pid;
        const url = `${typeof BASE_PATH !== 'undefined' ? BASE_PATH : ''}/dashboard/${pid}?period=${date}`;
        
        document.querySelectorAll('.period-tab').forEach(t => t.classList.remove('active', 'past-month'));
        periodTab.classList.add('active');
        document.body.style.cursor = 'wait';

        try {
            const response = await fetch(url);
            const htmlText = await response.text();
            const parser = new DOMParser();
            const doc = parser.parseFromString(htmlText, 'text/html');
            
            const currentMain = document.querySelector('.main-content');
            const newMain = doc.querySelector('.main-content');
            
            if (currentMain && newMain) {
                currentMain.innerHTML = newMain.innerHTML;
                window.history.pushState({}, '', url);
                
                // CRITICAL FIX: Snap the newly loaded tab into the center of the screen
                const newActiveTab = document.querySelector('.period-tab.active');
                if (newActiveTab) {
                    newActiveTab.scrollIntoView({ inline: 'center', block: 'nearest' });
                }
                
                const scripts = currentMain.querySelectorAll('script');
                scripts.forEach(oldScript => {
                    const newScript = document.createElement('script');
                    Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                    newScript.innerHTML = oldScript.innerHTML;
                    oldScript.parentNode.replaceChild(newScript, oldScript);
                });
            } else {
                window.location.href = url;
            }
        } catch (err) {
            window.location.href = url;
        } finally {
            document.body.style.cursor = 'default';
        }
        return;
    }

    // --- Period Horizontal Scrolling ---
    if (e.target.closest('#prev-period')) {
        document.getElementById('period-tabs')?.scrollBy({ left: -100, behavior: 'smooth' });
    }
    if (e.target.closest('#next-period')) {
        document.getElementById('period-tabs')?.scrollBy({ left: 100, behavior: 'smooth' });
    }

    // --- Category Collapsible ---
    const toggleCollapse = e.target.closest('.toggle-collapse');
    if (toggleCollapse) {
        const rows = toggleCollapse.closest('.category-section').querySelector('.category-rows');
        rows.style.display = rows.style.display === 'none' ? 'block' : 'none';
    }

    // --- Modal Closing ---
    if (e.target.closest('.close-modal')) {
        e.target.closest('.modal').classList.remove('active');
    }
    if (e.target.classList.contains('modal')) {
        e.target.classList.remove('active');
    }
});


// 2. ALL CHANGE EVENTS (Selects, Checkboxes)
document.addEventListener('change', async (e) => {
    
    // --- Year Selector ---
    if (e.target.id === 'year-selector') {
        const url = new URL(window.location.href);
        url.searchParams.set('year', e.target.value);
        window.location.href = url.toString();
        return;
    }

    // --- Frequency Form Switcher ---
    if (e.target.id === 'frequency_type') {
        const val = e.target.value;
        document.querySelectorAll('.freq-subfield').forEach(el => el.style.display = 'none');
        if (val === 'semi_monthly') document.getElementById('sm-fields').style.display = 'block';
        if (val === 'custom_months') document.getElementById('installment-fields').style.display = 'block';
        return;
    }

    // --- Universal Transaction Checkbox Toggle ---
    if (e.target.classList.contains('tx-check')) {
        const container = e.target.closest('.tx-row') || e.target.closest('.checklist-item');
        if (!container) return;
        
        const txId = container.dataset.id;
        const isChecked = e.target.checked;
        
        if (container.classList.contains('tx-row')) {
            container.classList.toggle('unchecked', !isChecked);
        } else {
            container.classList.toggle('paid', isChecked);
        }
        
        const formData = new FormData();
        // Assuming CSRF_TOKEN is defined globally in your header
        formData.append('csrf_token', typeof CSRF_TOKEN !== 'undefined' ? CSRF_TOKEN : '');
        formData.append('state', isChecked);

        try {
            const res = await fetch(`${typeof BASE_PATH !== 'undefined' ? BASE_PATH : ''}/dashboard/tx/${txId}/toggle`, { method: 'POST', body: formData });
            const data = await res.json();
            
            if (data.success) {
                // Update summary cards
                window.animateValue(document.getElementById('summary-inflow'), data.summary.total_inflow);
                window.animateValue(document.getElementById('summary-outflow'), data.summary.total_outflow);
                window.animateValue(document.getElementById('summary-net'), data.summary.net);
                window.animateValue(document.getElementById('summary-cum'), data.summary.cumulative);
                
                const netEl = document.getElementById('summary-net');
                if(netEl) netEl.closest('.summary-card').className = `card summary-card ${data.summary.net >= 0 ? 'positive' : 'negative'}`;
                
                // Re-trigger paycheck planner if it has a value
                const quickInput = document.getElementById('quick-salary-input');
                if (quickInput && quickInput.value) {
                    quickInput.dispatchEvent(new Event('input', { bubbles: true })); 
                }
            }
        } catch (err) {
            e.target.checked = !isChecked; 
            showToast('Network error, toggle reverted', 'error');
        }
    }
});


// 3. ALL INPUT EVENTS (Typing in fields)
document.addEventListener('input', (e) => {
    
    // --- Paycheck Planner ---
    if (e.target.id === 'quick-salary-input') {
        const plannerBreakdown = document.getElementById('planner-breakdown');
        if (!plannerBreakdown) return;

        let cleanVal = e.target.value.replace(/[^0-9.]/g, '');
        if ((cleanVal.match(/\./g) || []).length > 1) {
            cleanVal = cleanVal.replace(/\.+$/, '');
        }
        e.target.value = cleanVal;

        const salary = parseFloat(cleanVal) || 0;
        
        if (salary === 0) {
            plannerBreakdown.innerHTML = '<span style="color: var(--text-muted); font-size: 13px;">Enter amount to calculate splits...</span>';
            return;
        }

        let html = '';
        if (typeof window.monthOutflows !== 'undefined') {
            for (const [dateStr, outflow] of Object.entries(window.monthOutflows)) {
                const dateObj = new Date(dateStr);
                const prettyDate = dateObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                
                const net = salary - outflow;
                const colorClass = net >= 0 ? 'var(--accent-green)' : 'var(--accent-red)';
                const sign = net >= 0 ? '+' : '';

                html += `
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 8px;">
                        <span style="color: var(--text-secondary); font-size: 13px;">${prettyDate} Net</span>
                        <span class="amount" style="color: ${colorClass}; font-weight: bold;">
                            ${sign}${typeof window.currencySym !== 'undefined' ? window.currencySym : ''} ${net.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                        </span>
                    </div>
                `;
            }
        }
        plannerBreakdown.innerHTML = html;
    }
});


// 4. INLINE TABLE AMOUNT EDITING (Double Click & Blur)
document.addEventListener('dblclick', (e) => {
    if (e.target.classList.contains('editable-amount')) {
        e.target.setAttribute('contenteditable', 'true');
        e.target.focus();
        
        const range = document.createRange();
        range.selectNodeContents(e.target);
        const sel = window.getSelection();
        sel.removeAllRanges();
        sel.addRange(range);
    }
});

// Use 'true' for the capture phase because 'blur' doesn't bubble naturally
document.addEventListener('blur', async (e) => {
    if (e.target.classList.contains('editable-amount') && e.target.getAttribute('contenteditable') === 'true') {
        e.target.setAttribute('contenteditable', 'false');
        const row = e.target.closest('.tx-row');
        const txId = row.dataset.id;
        const newAmount = e.target.innerText.replace(/[^0-9.]/g, '');

        const formData = new FormData();
        formData.append('csrf_token', typeof CSRF_TOKEN !== 'undefined' ? CSRF_TOKEN : '');
        formData.append('amount', newAmount);

        try {
            const res = await fetch(`${typeof BASE_PATH !== 'undefined' ? BASE_PATH : ''}/dashboard/tx/${txId}/amount`, { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                e.target.innerText = parseFloat(data.amount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                showToast('Amount updated', 'success');
            }
        } catch (err) {
            showToast('Failed to save', 'error');
        }
    }
}, true);


// ==========================================
// ON HARD LOAD ONLY
// ==========================================
document.addEventListener('DOMContentLoaded', () => {
    // Initial Dashboard Numbers Animation
    const summarySpans = document.querySelectorAll('.summary-card h3 span');
    summarySpans.forEach(span => {
        const finalValue = span.innerText;
        span.innerText = '0.00'; 
        window.animateValue(span, finalValue.replace(/,/g, ''), 800); 
    });

    // Auto-scroll the active period tab to the center
    const activeTab = document.querySelector('.period-tab.active');
    if (activeTab) {
        activeTab.scrollIntoView({ inline: 'center', block: 'nearest' }); 
    }
});