document.addEventListener('DOMContentLoaded', () => {

    // 7. Count-up animation helper (UPGRADED)
    function animateValue(obj, end, duration = 500) {
        if (!obj) return;
        
        // 🎨 Check Preferences: Instantly skip animation if the user disabled it
        if (localStorage.getItem('pref_no_anim') === 'true') duration = 0;

        let startTimestamp = null;
        // Safely strip out existing commas to do pure math
        const startVal = parseFloat(obj.innerText.replace(/,/g, '')) || 0;
        const finalVal = parseFloat(end);
        
        // If duration is 0, just snap the number into place instantly
        if (duration === 0) {
            obj.innerText = finalVal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            return;
        }

        const step = (timestamp) => {
            if (!startTimestamp) startTimestamp = timestamp;
            const progress = Math.min((timestamp - startTimestamp) / duration, 1);
            const easeProgress = progress * (2 - progress); // Smooth deceleration
            const current = startVal + (finalVal - startVal) * easeProgress;
            
            // Format with commas on the fly
            obj.innerText = current.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            
            if (progress < 1) {
                window.requestAnimationFrame(step);
            } else {
                obj.innerText = finalVal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            }
        };
        window.requestAnimationFrame(step);
    }

    // 🎇 Trigger animation on Dashboard Load
    const summarySpans = document.querySelectorAll('.summary-card h3 span');
    summarySpans.forEach(span => {
        const finalValue = span.innerText;
        span.innerText = '0.00'; // Reset to zero
        animateValue(span, finalValue.replace(/,/g, ''), 800); // Animate up
    });


    // 1. Period tab switcher (Clean Redirect)
    const periodTabs = document.querySelectorAll('.period-tab');
    periodTabs.forEach(tab => {
        tab.addEventListener('click', (e) => {
            const date = e.target.dataset.date;
            const pid = e.target.dataset.pid;
            window.location.href = `${BASE_PATH}/dashboard/${pid}?period=${date}`;
        });
    });

    // 14. Year selector
    const yearSelector = document.getElementById('year-selector');
    if (yearSelector) {
        yearSelector.addEventListener('change', (e) => {
            const url = new URL(window.location.href);
            url.searchParams.set('year', e.target.value);
            window.location.href = url.toString();
        });
    }

    const activeTab = document.querySelector('.period-tab.active');
    if (activeTab) {
        activeTab.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
    }

    // 2. Universal Checkbox toggle
    document.addEventListener('change', async (e) => {
        if (e.target.classList.contains('tx-check')) {
            const container = e.target.closest('.tx-row') || e.target.closest('.checklist-item');
            if (!container) return;
            
            const txId = container.dataset.id;
            const isChecked = e.target.checked;
            
            // Visual toggle
            if (container.classList.contains('tx-row')) {
                container.classList.toggle('unchecked', !isChecked);
            } else {
                container.classList.toggle('paid', isChecked);
            }
            
            const formData = new FormData();
            formData.append('csrf_token', CSRF_TOKEN);
            formData.append('state', isChecked);

            try {
                const res = await fetch(`${BASE_PATH}/dashboard/tx/${txId}/toggle`, { method: 'POST', body: formData });
                const data = await res.json();
                
                if (data.success) {
                    // 🎇 Update the summary cards instantly using our new animation function!
                    animateValue(document.getElementById('summary-inflow'), data.summary.total_inflow);
                    animateValue(document.getElementById('summary-outflow'), data.summary.total_outflow);
                    animateValue(document.getElementById('summary-net'), data.summary.net);
                    animateValue(document.getElementById('summary-cum'), data.summary.cumulative);
                    
                    // Update Color Classes for Net
                    const netEl = document.getElementById('summary-net');
                    netEl.closest('.summary-card').className = `card summary-card ${data.summary.net >= 0 ? 'positive' : 'negative'}`;
                    
                    const quickInput = document.getElementById('quick-salary-input');
                    if (quickInput && quickInput.value) {
                        quickInput.dispatchEvent(new Event('input')); 
                    }
                }
            } catch (err) {
                e.target.checked = !isChecked; 
                showToast('Network error, toggle reverted', 'error');
            }
        }
    });

    // 3. Inline amount editing
    document.getElementById('budget-table')?.addEventListener('dblclick', (e) => {
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

    document.getElementById('budget-table')?.addEventListener('blur', async (e) => {
        if (e.target.classList.contains('editable-amount') && e.target.getAttribute('contenteditable') === 'true') {
            e.target.setAttribute('contenteditable', 'false');
            const row = e.target.closest('.tx-row');
            const txId = row.dataset.id;
            const newAmount = e.target.innerText.replace(/[^0-9.]/g, '');

            const formData = new FormData();
            formData.append('csrf_token', CSRF_TOKEN);
            formData.append('amount', newAmount);

            try {
                const res = await fetch(`${BASE_PATH}/dashboard/tx/${txId}/amount`, { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    // Add formatting back when saving
                    e.target.innerText = parseFloat(data.amount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    showToast('Amount updated', 'success');
                }
            } catch (err) {
                showToast('Failed to save', 'error');
            }
        }
    }, true);

    // 5. Frequency form switcher
    const freqSelect = document.getElementById('frequency_type');
    if (freqSelect) {
        freqSelect.addEventListener('change', (e) => {
            const val = e.target.value;
            document.querySelectorAll('.freq-subfield').forEach(el => el.style.display = 'none');
            if (val === 'semi_monthly') document.getElementById('sm-fields').style.display = 'block';
            if (val === 'custom_months') document.getElementById('installment-fields').style.display = 'block';
        });
    }

    // 8. Toast notifications
    window.showToast = (message, type = 'success') => {
        const container = document.getElementById('toast-container');
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.innerText = message;
        container.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'fadeOut 0.3s ease forwards';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    };

    // 9. Modal system
    document.querySelectorAll('.close-modal').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.target.closest('.modal').classList.remove('active');
        });
    });
    window.addEventListener('click', (e) => {
        if (e.target.classList.contains('modal')) {
            e.target.classList.remove('active');
        }
    });

    // 10. Confirm dialog
    window.confirmAction = (title, message, onConfirm) => {
        const modal = document.getElementById('confirm-modal');
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

    // 11. Mobile nav toggle
    document.getElementById('mobile-nav-toggle')?.addEventListener('click', () => {
        document.querySelector('.sidebar').classList.toggle('open');
    });

    // 15. Period prev/next arrows (Horizontal Scroll)
    const tabsContainer = document.getElementById('period-tabs');
    document.getElementById('prev-period')?.addEventListener('click', () => {
        tabsContainer.scrollBy({ left: -100, behavior: 'smooth' });
    });
    document.getElementById('next-period')?.addEventListener('click', () => {
        tabsContainer.scrollBy({ left: 100, behavior: 'smooth' });
    });

    // Category collapsible
    document.querySelectorAll('.toggle-collapse').forEach(header => {
        header.addEventListener('click', (e) => {
            const rows = e.target.closest('.category-section').querySelector('.category-rows');
            rows.style.display = rows.style.display === 'none' ? 'block' : 'none';
        });
    });

    // 16. Paycheck Planner (Monthly Split)
    const quickInput = document.getElementById('quick-salary-input');
    const plannerBreakdown = document.getElementById('planner-breakdown');
    
    if (quickInput && plannerBreakdown) {
        quickInput.addEventListener('input', (e) => {
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
            if (typeof monthOutflows !== 'undefined') {
                for (const [dateStr, outflow] of Object.entries(monthOutflows)) {
                    const dateObj = new Date(dateStr);
                    const prettyDate = dateObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                    
                    const net = salary - outflow;
                    const colorClass = net >= 0 ? 'var(--accent-green)' : 'var(--accent-red)';
                    const sign = net >= 0 ? '+' : '';

                    html += `
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 8px;">
                            <span style="color: var(--text-secondary); font-size: 13px;">${prettyDate} Net</span>
                            <span class="amount" style="color: ${colorClass}; font-weight: bold;">
                                ${sign}${typeof currencySym !== 'undefined' ? currencySym : ''} ${net.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                            </span>
                        </div>
                    `;
                }
            }
            plannerBreakdown.innerHTML = html;
        });
    }
});