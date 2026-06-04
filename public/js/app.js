// ==========================================
// GLOBAL HELPERS
// ==========================================

// Split View Engine (Paycheck Mode)
window.applySplitView = (isSplit) => {
    const multiplier = isSplit ? 0.5 : 1;
    const btn = document.getElementById('toggle-split-view');
    
    if (btn) {
        if (isSplit) {
            btn.innerHTML = '🌗 Split (50%)';
            btn.style.borderColor = 'var(--accent-blue)';
            btn.style.color = 'var(--accent-blue)';
        } else {
            btn.innerHTML = '🌕 Full Month';
            btn.style.borderColor = 'var(--border)';
            btn.style.color = 'var(--text-secondary)';
        }
    }

    // 1. Update Summary Cards
    ['inflow', 'outflow', 'net', 'cum'].forEach(key => {
        const el = document.getElementById(`summary-${key}`);
        if(el) {
            const fullVal = parseFloat(el.getAttribute('data-full-val')) || 0;
            const targetVal = fullVal * multiplier;
            if (key === 'net') {
                const signEl = document.getElementById('summary-sign');
                if(signEl) signEl.innerText = fullVal >= 0 ? '+' : '-';
                window.animateValue(el, Math.abs(targetVal), 400);
            } else {
                window.animateValue(el, targetVal, 400);
            }
        }
    });

    // 2. Update Transaction Rows
    document.querySelectorAll('.tx-amount').forEach(el => {
        const fullVal = parseFloat(el.getAttribute('data-full-val')) || 0;
        const displayEl = el.querySelector('.editable-amount');
        if(displayEl) displayEl.innerText = (fullVal * multiplier).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    });

    // 3. Update Category Footers (Subtotals)
    document.querySelectorAll('.cat-subtotal').forEach(el => {
        const fullVal = parseFloat(el.getAttribute('data-full-val')) || 0;
        const displayEl = el.querySelector('span');
        if(displayEl) displayEl.innerText = (fullVal * multiplier).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    });
};

// Count-up animation helper
window.animateValue = function(obj, end, duration = 500) {
    if (!obj) return;
    if (localStorage.getItem('pref_no_anim') === 'true' || document.documentElement.classList.contains('no-anim-mode')) duration = 0;

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

window.initializeTimelineScrollEngine = () => {
    const tabsContainer = document.getElementById('period-tabs');
    if (!tabsContainer || tabsContainer.dataset.swipeInitialized) return;
    
    tabsContainer.dataset.swipeInitialized = "true";

    let isDown = false;
    let startX;
    let scrollLeft;

    // --- Mouse Swipe Event Interfaces ---
    tabsContainer.addEventListener('mousedown', (e) => {
        isDown = true;
        tabsContainer.style.cursor = 'grabbing';
        startX = e.pageX - tabsContainer.offsetLeft;
        scrollLeft = tabsContainer.scrollLeft;
    });

    tabsContainer.addEventListener('mouseleave', () => {
        isDown = false;
        tabsContainer.style.cursor = 'pointer';
    });

    tabsContainer.addEventListener('mouseup', () => {
        isDown = false;
        tabsContainer.style.cursor = 'pointer';
    });

    tabsContainer.addEventListener('mousemove', (e) => {
        if (!isDown) return;
        e.preventDefault();
        const x = e.pageX - tabsContainer.offsetLeft;
        const walk = (x - startX) * 2; // Drag multiplier speed control
        tabsContainer.scrollLeft = scrollLeft - walk;
    });

    // --- Native Touch Momentum Safeguards ---
    tabsContainer.style.webkitOverflowScrolling = 'touch';
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
    const cancelBtn = document.getElementById('confirm-cancel');
    
    const newOkBtn = okBtn.cloneNode(true);
    const newCancelBtn = cancelBtn.cloneNode(true);
    
    okBtn.parentNode.replaceChild(newOkBtn, okBtn);
    cancelBtn.parentNode.replaceChild(newCancelBtn, cancelBtn);
    
    newOkBtn.addEventListener('click', () => {
        modal.classList.remove('active');
        onConfirm();
    });

    newCancelBtn.addEventListener('click', () => {
        modal.classList.remove('active');
    });
};


// ==========================================
// EVENT DELEGATION
// ==========================================


// 1. ALL CLICK EVENTS
document.addEventListener('click', async (e) => {
    const basePath = typeof BASE_PATH !== 'undefined' ? BASE_PATH : '';
    const token = typeof CSRF_TOKEN !== 'undefined' ? CSRF_TOKEN : '';

    // --- Mobile Layout Handlers ---
    if (e.target.closest('#mobile-nav-toggle')) { document.querySelector('.sidebar')?.classList.toggle('open'); return; }
    if (e.target.closest('a.nav-item')) { document.querySelector('.sidebar')?.classList.remove('open'); }

    // --- Split Dashboard Matrix Multiplier ---
    if (e.target.closest('#toggle-split-view')) {
        const isSplit = localStorage.getItem('pref_split_view') !== 'true';
        localStorage.setItem('pref_split_view', isSplit);
        window.applySplitView(isSplit);
        return;
    }

    // --- Category Dropdown Table Collapse Engine ---
    const toggleCollapse = e.target.closest('.toggle-collapse');
    if (toggleCollapse) {
        e.preventDefault();
        e.stopPropagation();
        const freezeY = window.scrollY;
        
        const rows = toggleCollapse.closest('.category-section').querySelector('.category-rows');
        if (rows) {
            rows.style.display = rows.style.display === 'none' ? 'block' : 'none';
        }
        
        window.scrollTo({ top: freezeY, behavior: 'instant' });
        return;
    }

    // --- Shared Emoji Picker Trigger Hook ---
    const emojiTrigger = e.target.closest('.emoji-picker-trigger');
    if (emojiTrigger) {
        e.preventDefault();
        window.activeEmojiTargetInput = emojiTrigger;
        document.getElementById('emoji-picker-modal').classList.add('active');
        return;
    }

    // --- Unified Token Return Completion Callback Interceptor ---
    const emojiOption = e.target.closest('.emoji-option');
    if (emojiOption && window.activeEmojiTargetInput) {
        window.activeEmojiTargetInput.value = emojiOption.innerText;
        document.getElementById('emoji-picker-modal').classList.remove('active');
        window.activeEmojiTargetInput = null;
        return;
    }

    // --- Asynchronous Category Form Scraper & Loader ---
    const openCatEditModalBtn = e.target.closest('.open-cat-edit-modal-btn');
    if (openCatEditModalBtn) {
        e.preventDefault();
        const targetUrl = openCatEditModalBtn.dataset.url;
        document.body.style.cursor = 'wait';

        try {
            const response = await fetch(targetUrl);
            const htmlText = await response.text();
            const parser = new DOMParser();
            const virtualDoc = parser.parseFromString(htmlText, 'text/html');
            const targetForm = virtualDoc.querySelector('form');
            const targetBody = document.getElementById('edit-category-modal-body');
            
            if (targetForm && targetBody) {
                targetBody.innerHTML = '';
                targetBody.appendChild(targetForm);
                document.getElementById('edit-category-modal').classList.add('active');
            } else {
                window.location.href = targetUrl;
            }
        } catch (err) {
            window.location.href = targetUrl;
        } finally {
            document.body.style.cursor = 'default';
        }
        return;
    }

    // --- Dynamic Entry Form Scraper & Modal Injected Initializer ---
    const openEditModalBtn = e.target.closest('.open-edit-modal-btn');
    if (openEditModalBtn) {
        e.preventDefault();
        const targetUrl = openEditModalBtn.dataset.url;
        document.body.style.cursor = 'wait';

        try {
            const response = await fetch(targetUrl);
            const htmlText = await response.text();
            
            const parser = new DOMParser();
            const virtualDoc = parser.parseFromString(htmlText, 'text/html');
            
            const baseForm = virtualDoc.querySelector('.card form');
            const targetContainer = document.getElementById('edit-modal-form-body');
            
            if (baseForm && targetContainer) {
                targetContainer.innerHTML = '';
                targetContainer.appendChild(baseForm);
                
                // Active structural invocation
                document.getElementById('edit-entry-modal').classList.add('active');
                
                // Cascade frequency change events natively to render correct subfields block layout
                const freqSelect = targetContainer.querySelector('#frequency_type');
                if (freqSelect) {
                    const changeEvent = new Event('change', { bubbles: true });
                    freqSelect.dispatchEvent(changeEvent);
                }
            } else {
                window.location.href = targetUrl;
            }
        } catch (err) {
            window.location.href = targetUrl;
        } finally {
            document.body.style.cursor = 'default';
        }
        return;
    }

    // --- Modal Closing Mechanics Failsafe ---
    if (e.target.closest('.close-modal') || (e.target.classList.contains('modal') && e.target.classList.contains('active'))) {
        const activeModal = e.target.closest('.modal') || e.target;
        activeModal.classList.remove('active');
        return;
    }

    const navLink = e.target.closest('.period-tab') || e.target.closest('a.nav-item');
    if (navLink) {
        let url = navLink.getAttribute('href') || `${basePath}/dashboard/${navLink.dataset.pid}?period=${navLink.dataset.date}`;
        if (url.includes('logout') || navLink.target === '_blank' || url === '#') return;
        
        e.preventDefault();
        document.body.style.cursor = 'wait';
        
        const currentScrollPositionY = window.scrollY;
        const isSidebarNavigation = navLink.closest('.sidebar') !== null;

        try {
            const response = await fetch(url);
            const htmlText = await response.text();
            
            const parser = new DOMParser();
            const doc = parser.parseFromString(htmlText, 'text/html');
            
            // TARGET APP SHELL WRAPPERS
            const currentWorkspace = document.getElementById('spa-workspace');
            const newWorkspace = doc.getElementById('spa-workspace');
            const currentSidebar = document.querySelector('.sidebar');
            const newSidebar = doc.querySelector('.sidebar');
            const currentNavStrip = document.querySelector('.period-nav-strip');
            const newNavStrip = doc.querySelector('.period-nav-strip');

            // 1. Swap Inner Content Area Workspace
            if (currentWorkspace && newWorkspace) {
                currentWorkspace.innerHTML = newWorkspace.innerHTML;
            } else {
                const currentMain = document.querySelector('.main-content');
                const newMain = doc.querySelector('.main-content');
                if (currentMain && newMain) currentMain.innerHTML = newMain.innerHTML;
            }

            // 2. Sync Sidebar Structural Layout Options Natively
            if (currentSidebar && newSidebar) {
                const currentNavLinks = currentSidebar.querySelector('.nav-links');
                const newNavLinks = newSidebar.querySelector('.nav-links');
                const sidebarScrollPos = currentNavLinks ? currentNavLinks.scrollTop : 0;

                currentSidebar.innerHTML = newSidebar.innerHTML;

                const updatedNavLinks = currentSidebar.querySelector('.nav-links');
                if (updatedNavLinks) updatedNavLinks.scrollTop = sidebarScrollPos;
            }

            // 3. Sync Persistent Horizontal Timeline Period Strip Navbar
            if (currentNavStrip && newNavStrip) {
                currentNavStrip.innerHTML = newNavStrip.innerHTML;
            } else if (currentNavStrip && !newNavStrip) {
                currentNavStrip.remove();
            } else if (!currentNavStrip && newNavStrip) {
                const mobileHeader = document.querySelector('.mobile-only');
                if (mobileHeader) {
                    mobileHeader.insertAdjacentElement('afterend', newNavStrip.cloneNode(true));
                } else {
                    const mainContent = document.querySelector('.main-content');
                    mainContent?.prepend(newNavStrip.cloneNode(true));
                }
            }

            // 4. CRITICAL CHART FIX: Extract, construct, and evaluate dynamic view script tags programmatically
            const targetMainArea = currentWorkspace || document.querySelector('.main-content');
            if (targetMainArea) {
                const scripts = targetMainArea.querySelectorAll('script');
                scripts.forEach(oldScript => {
                    const newScript = document.createElement('script');
                    // Copy over metadata attributes safely
                    Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                    newScript.innerHTML = oldScript.innerHTML;
                    
                    // Replace item inside DOM tree context to trigger browser runtime evaluation execution
                    oldScript.parentNode.replaceChild(newScript, oldScript);
                });
            }

            // 5. Commit browser history metrics
            window.history.pushState({}, '', url);
            document.title = doc.title;
            
            // 6. Defer bootstrapper execution until layout properties render completely
            setTimeout(() => {
                if (typeof window.initializeActiveViewHelpers === 'function') {
                    window.initializeActiveViewHelpers();
                }
            }, 0);

            // 7. Enforce context scroll position boundaries
            if (isSidebarNavigation) {
                window.scrollTo({ top: 0, behavior: 'instant' });
            } else {
                window.scrollTo({ top: currentScrollPositionY, behavior: 'instant' });
            }
        } catch (err) {
            console.error('SPA Context Navigation Failure:', err);
            window.location.href = url;
        } finally {
            document.body.style.cursor = 'default';
        }
        return;
    }

    // --- System Emoji/Icon Matrix Modal Chooser ---
    if (e.target.classList.contains('emoji-option')) {
        const targetInput = document.getElementById('category-icon-input');
        if (targetInput) targetInput.value = e.target.innerText;
        document.getElementById('emoji-picker-modal').classList.remove('active');
        return;
    }

    // --- Unified Route Template Navigation Swapper ---
    const periodTab = e.target.closest('.period-tab') || e.target.closest('a.nav-item');
    if (periodTab) {
        let url = periodTab.getAttribute('href') || `${basePath}/dashboard/${periodTab.dataset.pid}?period=${periodTab.dataset.date}`;
        if (url.includes('logout') || periodTab.target === '_blank' || url === '#') return;
        
        e.preventDefault();
        document.body.style.cursor = 'wait';
        try {
            const response = await fetch(url);
            const htmlText = await response.text();
            const parser = new DOMParser();
            const doc = parser.parseFromString(htmlText, 'text/html');
            const currentMain = document.querySelector('.main-content');
            const newMain = doc.querySelector('.main-content');
            const currentNav = document.querySelector('.nav-links');
            const newNav = doc.querySelector('.nav-links');

            if (currentMain && newMain) {
                currentMain.innerHTML = newMain.innerHTML;
                if (currentNav && newNav) {
                    const savedScroll = currentNav.scrollTop;
                    currentNav.innerHTML = newNav.innerHTML;
                    currentNav.scrollTop = savedScroll;
                }
                window.history.pushState({}, '', url);
                document.title = doc.title;
                window.initializeActiveViewHelpers();
            } else { window.location.href = url; }
        } catch (err) { window.location.href = url; }
        finally { document.body.style.cursor = 'default'; }
        return;
    }

    // --- Master Entry Open Dynamic Form Modal Handler ---
    const editEntryBtn = e.target.closest('.edit-entry-modal-btn');
    if (editEntryBtn) {
        e.preventDefault();
        const id = editEntryBtn.dataset.id;
        try {
            const res = await fetch(`${basePath}/entries/edit-data/${id}`);
            const html = await res.text();
            document.getElementById('edit-entry-modal-body').innerHTML = html;
            document.getElementById('edit-entry-modal').classList.add('active');
        } catch(err) { window.showToast('Could not fetch record parameters', 'error'); }
        return;
    }

    // --- Dynamic Target Eraser Processing Route (MODAL FOR ALL DELETES FIXED) ---
    const deleteBtn = e.target.closest('.delete-cat-btn, .delete-entry-btn, .delete-income-btn, .delete-spend-btn, .delete-goal-btn, .delete-profile-btn');
    if (deleteBtn) {
        e.preventDefault();
        const id = deleteBtn.dataset.id;
        const name = deleteBtn.dataset.name;
        const containerRow = deleteBtn.closest('.cat-row, .tx-row, [style*="border-bottom"]');
        
        let typeLabel = 'Record', endpoint = '';
        if (deleteBtn.classList.contains('delete-cat-btn')) { typeLabel = 'Category'; endpoint = `${basePath}/categories/${id}/delete`; }
        if (deleteBtn.classList.contains('delete-entry-btn')) { typeLabel = 'Master Entry'; endpoint = `${basePath}/entries/${id}/delete`; }
        if (deleteBtn.classList.contains('delete-income-btn')) { typeLabel = 'Revenue Record'; endpoint = `${basePath}/income/delete/${id}`; }
        if (deleteBtn.classList.contains('delete-spend-btn')) { typeLabel = 'Shopping Expenditure'; endpoint = `${basePath}/shopping/delete/${id}`; }
        if (deleteBtn.classList.contains('delete-goal-btn')) { typeLabel = 'Savings Goal'; endpoint = `${basePath}/vault/delete/${id}`; }
        if (deleteBtn.classList.contains('delete-profile-btn')) { typeLabel = 'Entire Profile Node'; endpoint = `${basePath}/profile/${id}/delete`; }

        window.confirmAction(`Delete ${typeLabel}`, `Are you absolutely certain you want to erase "${name}"? This operation cannot be undone.`, async () => {
            const formData = new FormData();
            formData.append('csrf_token', token);
            try {
                const res = await fetch(endpoint, { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    if (deleteBtn.classList.contains('delete-profile-btn')) { window.location.href = `${basePath}/`; }
                    else {
                        containerRow ? containerRow.remove() : window.location.reload();
                        window.showToast(`${typeLabel} deleted successfully`, 'success');
                    }
                } else { window.showToast(data.error || 'Operation rejected', 'error'); }
            } catch (err) { window.showToast('Communication fault executing erasure', 'error'); }
        });
        return;
    }

    // --- Modal Closing Mechanics ---
    if (e.target.closest('.close-modal') || e.target.classList.contains('modal')) {
        e.target.closest('.modal').classList.remove('active');
    }
});


// 2. ALL CHANGE EVENTS (Selects, Checkboxes)
document.addEventListener('change', async (e) => {
    const basePath = typeof BASE_PATH !== 'undefined' ? BASE_PATH : '';
    const token = typeof CSRF_TOKEN !== 'undefined' ? CSRF_TOKEN : '';

    if (e.target.closest('.card') && document.getElementById('calc-price')) {
    window.runAdvancedLoanSandboxCalculation();
    }

    // --- Master Global Preferences Slider Processing Engine ---
    if (e.target.id && e.target.id.startsWith('pref-')) {
        const key = e.target.id.replace('pref-', ''); // privacy, animations, compact, zen
        const isChecked = e.target.checked;
        const rootClass = key === 'animations' ? 'no-anim-mode' : `${key}-mode`;
        
        if (isChecked) document.documentElement.classList.add(rootClass);
        else document.documentElement.classList.remove(rootClass);

        const targetNode = document.querySelector('[data-pid]');
        const pid = targetNode ? targetNode.dataset.pid : '0';

        const formData = new FormData();
        formData.append('csrf_token', token);
        formData.append('key', `pref_${key}`);
        formData.append('state', isChecked ? '1' : '0');

        try {
            const res = await fetch(`${basePath}/preferences/${pid}/toggle`, { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) window.showToast('Configurations mapped to database');
        } catch (err) {
            e.target.checked = !isChecked;
            if (!isChecked) document.documentElement.classList.add(rootClass);
            else document.documentElement.classList.remove(rootClass);
            window.showToast('Network error syncing properties', 'error');
        }
        return;
    }

    // --- Master Dashboard Checkbox Dynamic Toggle Engine ---
    if (e.target.classList.contains('tx-check')) {
        const freezeY = window.scrollY;
        
        const container = e.target.closest('.tx-row');
        if (!container) return;
        
        const txId = container.dataset.id;
        const isChecked = e.target.checked;
        
        container.classList.toggle('unchecked', !isChecked);
        
        const formData = new FormData();
        formData.append('csrf_token', token);
        formData.append('state', isChecked);

        try {
            const res = await fetch(`${basePath}/dashboard/tx/${txId}/toggle`, { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                document.getElementById('summary-inflow').setAttribute('data-full-val', data.summary.total_inflow);
                document.getElementById('summary-outflow').setAttribute('data-full-val', data.summary.total_outflow);
                document.getElementById('summary-net').setAttribute('data-full-val', data.summary.net);
                document.getElementById('summary-cum').setAttribute('data-full-val', data.summary.cumulative);
                window.applySplitView(localStorage.getItem('pref_split_view') === 'true');
                
                // Keep viewport locked during async state updates
                window.scrollTo({ top: freezeY, behavior: 'instant' });
            }
        } catch (err) { 
            e.target.checked = !isChecked; 
            container.classList.toggle('unchecked', isChecked);
        }
        return;
    }

    // --- Form Frequency Subfields Rules Switcher ---
    if (e.target.id === 'frequency_type') {
        const val = e.target.value;
        document.querySelectorAll('.freq-subfield').forEach(el => el.style.display = 'none');
        if (val === 'semi_monthly') document.getElementById('sm-fields').style.display = 'block';
        if (val === 'custom_months') document.getElementById('installment-fields').style.display = 'block';
        if (val === 'one_time') document.getElementById('onetime-fields').style.display = 'block';
    }
});


// 3. ALL INPUT EVENTS (Typing in fields)
document.addEventListener('input', (e) => {
    if (e.target.closest('.card') && document.getElementById('calc-price')) {
    window.runAdvancedLoanSandboxCalculation();
    }

    if (e.target.id === 'quick-salary-input') {
        const plannerBreakdown = document.getElementById('planner-breakdown');
        if (!plannerBreakdown) return;
        let cleanVal = e.target.value.replace(/[^0-9.]/g, '');
        e.target.value = cleanVal;
        const salary = parseFloat(cleanVal) || 0;
        if (salary === 0) { plannerBreakdown.innerHTML = '<span style="color: var(--text-muted);">Enter amount...</span>'; return; }

        let html = '';
        if (typeof window.monthOutflows !== 'undefined') {
            for (const [dateStr, outflow] of Object.entries(window.monthOutflows)) {
                const net = salary - outflow;
                html += `
                    <div style="display:flex; justify-content:space-between; margin-bottom: 8px; font-size:13px;">
                        <span style="color: var(--text-secondary);">${new Date(dateStr).toLocaleDateString('en-US', {month:'short',day:'numeric'})} Net</span>
                        <span class="amount" style="color: ${net >= 0 ? 'var(--accent-green)' : 'var(--accent-red)'}; font-weight: bold;">
                            ${net >= 0 ? '+' : ''}${window.currencySym || ''} ${net.toLocaleString('en-US', {minimumFractionDigits:2})}
                        </span>
                    </div>`;
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
        
        const txRow = e.target.closest('.tx-row');
        if (!txRow) return;
        
        const txId = txRow.dataset.id;
        const basePath = typeof BASE_PATH !== 'undefined' ? BASE_PATH : '';
        const token = typeof CSRF_TOKEN !== 'undefined' ? CSRF_TOKEN : '';

        const dbMultiplier = localStorage.getItem('pref_split_view') === 'true' ? 2 : 1;
        const displayedAmount = parseFloat(e.target.innerText.replace(/[^0-9.-]/g, '')) || 0;
        const targetCleanAmount = displayedAmount * dbMultiplier;

        const formData = new FormData();
        formData.append('csrf_token', token);
        formData.append('amount', targetCleanAmount);

        try {
            const res = await fetch(`${basePath}/dashboard/tx/${txId}/amount`, { method: 'POST', body: formData });
            const data = await res.json();
            
            if (data.success) {

                const targetTxAmount = e.target.closest('.tx-amount');
                if (targetTxAmount) {
                    targetTxAmount.setAttribute('data-full-val', data.amount);
                }

                e.target.innerText = (data.amount / dbMultiplier).toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
                
                if (typeof window.runAdvancedLoanSandboxCalculation === 'function' && document.getElementById('calc-price')) {
                    window.runAdvancedLoanSandboxCalculation();
                }
                
                window.showToast('Ledger balance synchronized successfully', 'success');
            } else {
                window.showToast(data.error || 'Server rejected synchronization modification', 'error');
               
            }
        } catch (err) { 
            window.showToast('Network communication fault executing inline save adjustment', 'error');
        }
    }
}, true);

// ==========================================
// ON HARD LOAD ONLY
// ==========================================
document.addEventListener('DOMContentLoaded', () => {

    window.initializeActiveViewHelpers();

    // Initial Dashboard Numbers Animation
    const summarySpans = document.querySelectorAll('.summary-card h3 span');
    summarySpans.forEach(span => {
        const finalValue = span.innerText;
        span.innerText = '0.00'; 
        window.animateValue(span, finalValue.replace(/,/g, ''), 800); 
    });

    // Apply Split View on Hard Load
    if (localStorage.getItem('pref_split_view') === 'true') {
        window.applySplitView(true);
    } else {
        window.applySplitView(false); // Triggers the default animations!
    }

    // Auto-scroll the active period tab to the center
    const activeTab = document.querySelector('.period-tab.active');
    if (activeTab) {
        activeTab.scrollIntoView({ inline: 'center', block: 'nearest' }); 
    }
});

// ==========================================
// SPA ENGINE 
// ==========================================


window.initializeActiveViewHelpers = () => {

    window.initializeTimelineScrollEngine();
    window.renderViewChartsIfPresent();
    const calcRows = document.getElementById('calc-rows');
    if (calcRows && !calcRows.dataset.initialized) {
        calcRows.dataset.initialized = "true";
        if(calcRows.children.length === 0) {
            window.addCalculatorRow('Initial Value', '0.00', 'outflow');
        }
    }
    const categoryList = document.getElementById('category-list');
    if (categoryList && !categoryList.dataset.dragInitialized) {
        categoryList.dataset.dragInitialized = "true";
        window.setupCategoryDragAndDrop(categoryList);
    }
    const activeTab = document.querySelector('.period-tab.active');
    if (activeTab) {
        activeTab.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
    }
    if (document.getElementById('calc-principal')) { window.runCompoundForecasterCalculation(); }
    if (document.getElementById('calc-price')) { window.runAdvancedLoanSandboxCalculation(); }
    if (document.getElementById('toggle-split-view')) {
        window.applySplitView(localStorage.getItem('pref_split_view') === 'true');
    }
};

// Global Calculator Predictive Sandbox Trajectory Engine
window.calculateLiveForecastScenario = () => {
    const amountInput = document.querySelector('input[name="amount"]');
    const typeSelect = document.querySelector('select[name="type"]');
    const monthSelect = document.querySelector('select[name="month"]');
    if (!amountInput || !window.activeForecastChartInstance) return;

    let liveAmount = parseFloat(amountInput.value.replace(/[^0-9.]/g, '')) || 0;
    let liveSimData = [...window.forecastChartData.sim];
    if (liveAmount > 0) {
        const modifier = typeSelect.value === 'inflow' ? liveAmount : -liveAmount;
        for (let i = (parseInt(monthSelect.value) - 1); i < 12; i++) { liveSimData[i] += modifier; }
    }
    window.activeForecastChartInstance.data.datasets[1].data = liveSimData;
    window.activeForecastChartInstance.update('none');
};

// Investment Compound Processing Engine
window.runCompoundForecasterCalculation = () => {
    let p = parseFloat(document.getElementById('calc-principal')?.value) || 0;
    let m = parseFloat(document.getElementById('calc-monthly')?.value) || 0;
    let r = (parseFloat(document.getElementById('calc-rate')?.value) || 0) / 100 / 12;
    let t = (parseFloat(document.getElementById('calc-years')?.value) || 0) * 12;
    let totalPrincipal = p + (m * t);
    let futureValue = p;
    for (let i = 0; i < t; i++) { futureValue = (futureValue + m) * (1 + r); }
    if(document.getElementById('res-principal')) {
        document.getElementById('res-principal').innerText = '₱ ' + totalPrincipal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('res-interest').innerText = '₱ ' + (futureValue - totalPrincipal).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('res-total').innerText = '₱ ' + futureValue.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }
};

// Advanced Loan Sandbox Core Calculation Module
// Advanced Loan Sandbox Core Calculation Module
window.runAdvancedLoanSandboxCalculation = () => {
    let price = parseFloat(document.getElementById('calc-price')?.value) || 0;
    let down = parseFloat(document.getElementById('calc-down')?.value) || 0;
    let rateVal = parseFloat(document.getElementById('calc-rate')?.value) || 0;
    let rateType = document.getElementById('calc-rate-type')?.value;

    let termValue = parseFloat(document.getElementById('calc-term-value')?.value) || 0;
    let termType = document.getElementById('calc-term-type')?.value; 
    
    let freq = parseInt(document.getElementById('calc-freq')?.value) || 12;
    let upfrontFee = parseFloat(document.getElementById('calc-upfront-fee')?.value) || 0;
    let recurringFee = parseFloat(document.getElementById('calc-recurring-fee')?.value) || 0;
    let extra = parseFloat(document.getElementById('calc-extra')?.value) || 0;
    
    // NEW: Resolution View Metric Mapping Rule Selector
    let resolutionMode = document.getElementById('chart-resolution-selector')?.value || 'auto';

    let principal = Math.max(0, price - down);
    if(document.getElementById('display-principal')) {
        document.getElementById('display-principal').innerText = '₱ ' + principal.toLocaleString('en-US', {minimumFractionDigits: 2});
    }

    let r = (rateType === 'annual') ? ((rateVal / 100) / freq) : (((rateVal * 12) / 100) / freq);

    let n = (termType === 'years') ? (termValue * freq) : Math.ceil(termValue * (freq / 12));
    if (n <= 0) n = 1;

    let basePayment = (r === 0) ? (principal / n) : (principal * (r * Math.pow(1 + r, n)) / (Math.pow(1 + r, n) - 1));
    if (!isFinite(basePayment)) basePayment = 0;

    let requiredTotalPayment = basePayment + recurringFee;
    let actualPayment = requiredTotalPayment + extra;
    let effectivePIPayment = basePayment + extra; 

    let freqLabel = freq === 52 ? 'Wk' : freq === 26 ? 'Bi-Wk' : freq === 12 ? 'Mo' : 'Yr';
    if(document.getElementById('lbl-payment')) {
        document.getElementById('lbl-payment').innerText = `${freqLabel} P&I:`;
        document.getElementById('lbl-total-payment').innerText = `Required ${freqLabel} Payment:`;
    }

    let balance = principal, totalInterest = 0, totalRecurringFeesPaid = 0, periodsTaken = 0;
    
    // Complete high-fidelity array matrices compiled natively for every tracking period
    let rawLabels = ['Start'], 
        rawBalances = [principal], 
        rawInterest = [0], 
        rawFees = [upfrontFee],
        rawWeeks = [0],
        rawMonths = [0];

    for (let i = 1; i <= n; i++) {
        if (balance <= 0) break;
        let interestForPeriod = balance * r;
        let principalForPeriod = effectivePIPayment - interestForPeriod;
        
        if (principalForPeriod > balance) {
            principalForPeriod = balance;
            effectivePIPayment = principalForPeriod + interestForPeriod;
            actualPayment = effectivePIPayment + recurringFee;
        }
        
        balance -= principalForPeriod;
        totalInterest += interestForPeriod;
        totalRecurringFeesPaid += recurringFee;
        periodsTaken++;

        // Track running chronological conversions variables
        let weekMark = Math.round(periodsTaken * (52 / freq));
        let monthMark = Math.round(periodsTaken * (12 / freq));

        rawLabels.push(i);
        rawBalances.push(Math.max(0, balance));
        rawInterest.push(totalInterest);
        rawFees.push(upfrontFee + totalRecurringFeesPaid);
        rawWeeks.push(weekMark);
        rawMonths.push(monthMark);
    }

    // Dynamic payoff text annotation formatting
    if (document.getElementById('res-payoff-date')) {
        let totalMonthsTaken = Math.ceil(periodsTaken * (12 / freq));
        let payoffYears = Math.floor(totalMonthsTaken / 12);
        let payoffMonths = totalMonthsTaken % 12;
        let payoffStr = payoffYears > 0 ? `${payoffYears} Yr ${payoffMonths} Mo` : `${payoffMonths} Mo`;
        document.getElementById('res-payoff-date').innerText = `Payoff: ${payoffStr} (${periodsTaken} periods)`;
    }

    // RESOLUTION INTERVENTIONAL DOWNSAMPLING ENGINE
    let chartLabels = ['Start'], chartBalances = [principal], chartCumulativeInterest = [0], chartCumulativeFees = [upfrontFee];
    let lastWeekAdded = 0, lastMonthAdded = 0, lastQuarterAdded = 0, lastYearAdded = 0;

    for (let i = 1; i < rawLabels.length; i++) {
        let includePoint = false;
        let labelMarker = '';

        let currentWk = rawWeeks[i];
        let currentMo = rawMonths[i];
        let currentQtr = Math.ceil(currentMo / 3);
        let currentYr = Math.ceil(currentMo / 12);

        // Auto selection density bounds configuration
        let activeResolution = resolutionMode;
        if (activeResolution === 'auto') {
            if (periodsTaken <= 24) activeResolution = 'weekly';
            else if (periodsTaken <= 60) activeResolution = 'monthly';
            else if (periodsTaken <= 240) activeResolution = 'quarterly';
            else activeResolution = 'annually';
        }

        switch (activeResolution) {
            case 'weekly':
                if (currentWk > lastWeekAdded || i === rawLabels.length - 1) {
                    includePoint = true; lastWeekAdded = currentWk; labelMarker = `Wk ${currentWk}`;
                }
                break;
            case 'semi_monthly':
                // Check intervals at double month velocity paths
                let semiMark = Math.round(rawWeeks[i] / 2);
                if (semiMark > lastWeekAdded || i === rawLabels.length - 1) {
                    includePoint = true; lastWeekAdded = semiMark; labelMarker = `Pt ${semiMark}`;
                }
                break;
            case 'monthly':
                if (currentMo > lastMonthAdded || i === rawLabels.length - 1) {
                    includePoint = true; lastMonthAdded = currentMo; labelMarker = `Mo ${currentMo}`;
                }
                break;
            case 'quarterly':
                if (currentQtr > lastQuarterAdded || i === rawLabels.length - 1) {
                    includePoint = true; lastQuarterAdded = currentQtr; labelMarker = `Qtr ${currentQtr}`;
                }
                break;
            case 'annually':
                if (currentYr > lastYearAdded || i === rawLabels.length - 1) {
                    includePoint = true; lastYearAdded = currentYr; labelMarker = `Yr ${currentYr}`;
                }
                break;
        }

        if (includePoint) {
            chartLabels.push(labelMarker);
            chartBalances.push(rawBalances[i]);
            chartCumulativeInterest.push(rawInterest[i]);
            chartCumulativeFees.push(rawFees[i]);
        }
    }

    // Set globally scoped output vectors for chart initial paints logic
    window.chartLabels = chartLabels;
    window.chartBalances = chartBalances;
    window.chartCumulativeInterest = chartCumulativeInterest;
    window.chartCumulativeFees = chartCumulativeFees;

    if(document.getElementById('res-base-payment')) {
        const fmt = (num) => '₱ ' + num.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('res-base-payment').innerText = fmt(basePayment);
        document.getElementById('res-fee-payment').innerText = '+ ' + fmt(recurringFee);
        document.getElementById('res-total-payment').innerText = fmt(requiredTotalPayment);
        document.getElementById('res-interest').innerText = fmt(totalInterest);
        document.getElementById('res-total-fees').innerText = fmt(upfrontFee + totalRecurringFeesPaid);
        document.getElementById('res-total').innerText = fmt(principal + totalInterest + upfrontFee + totalRecurringFeesPaid + down);
        
        const extraRow = document.getElementById('extra-payment-row');
        if (extra > 0) {
            extraRow.style.display = 'flex';
            document.getElementById('res-actual-payment').innerText = fmt(actualPayment);
        } else { extraRow.style.display = 'none'; }

        const savingsCard = document.getElementById('savings-card');
        let baseTotalInterest = (r === 0) ? 0 : (basePayment * n) - principal;
        let savedInterestAndFees = Math.max(0, (baseTotalInterest + (recurringFee * n)) - (totalInterest + totalRecurringFeesPaid));
        let savedPeriods = Math.max(0, n - periodsTaken);
        if (extra > 0 && savedInterestAndFees > 0) {
            savingsCard.style.display = 'block';
            document.getElementById('res-saved-interest').innerText = fmt(savedInterestAndFees);
            document.getElementById('res-saved-time').innerText = `${savedPeriods} periods early`;
        } else { savingsCard.style.display = 'none'; }
    }

    const canvas = document.getElementById('amortizationChart');
    if (canvas) {
        let existingChart = Chart.getChart(canvas);
        if (existingChart) {
            existingChart.data.labels = chartLabels;
            existingChart.data.datasets[0].data = chartBalances;
            existingChart.data.datasets[1].data = chartCumulativeInterest;
            existingChart.data.datasets[2].data = chartCumulativeFees;
            existingChart.update('none'); 
        } else {
            if (typeof window.renderViewChartsIfPresent === 'function') { window.renderViewChartsIfPresent(); }
        }
    }
};

// Global Centralized Chart Context Manager
window.renderViewChartsIfPresent = () => {
    // 1. Read layout properties safely
    const style = getComputedStyle(document.body);
    
    // to stop Chart.js from reading empty variables as pure black.
    const colorText = style.getPropertyValue('--text-secondary').trim() || '#8b949e';
    const colorGrid = style.getPropertyValue('--border').trim() || '#30363d';
    const colorBlue = style.getPropertyValue('--accent-blue').trim() || '#58a6ff';
    const colorGreen = style.getPropertyValue('--accent-green').trim() || '#3fb950';
    const colorRed = style.getPropertyValue('--accent-red').trim() || '#f85149';
    const colorYellow = style.getPropertyValue('--accent-yellow').trim() || '#d29922';
    const colorCardBg = style.getPropertyValue('--bg-card').trim() || '#161b22';

    // --- A. Advanced Loan Sandbox Amortization Chart ---
    const loanCanvas = document.getElementById('amortizationChart');
    if (loanCanvas) {
        let existingChart = Chart.getChart(loanCanvas);
        if (existingChart) existingChart.destroy();

        new Chart(loanCanvas.getContext('2d'), {
            type: 'line',
            data: {
                labels: window.chartLabels || ['Start'],
                datasets: [
                    { 
                        label: 'Remaining Balance (Equity)', 
                        data: window.chartBalances || [0], 
                        borderColor: colorBlue, 
                        backgroundColor: colorBlue + '10', 
                        borderWidth: 3, 
                        fill: true,
                        tension: 0.1,
                        pointRadius: 2
                    },
                    { 
                        label: 'Cumulative Interest Thrown Away', 
                        data: window.chartCumulativeInterest || [0], 
                        borderColor: colorRed, 
                        backgroundColor: colorRed + '08', 
                        borderWidth: 2, 
                        fill: true,
                        tension: 0.1,
                        pointRadius: 0,
                        borderDash: [3, 3]
                    },
                    { 
                        label: 'Sunk Fees (Upfront + Periodic)', 
                        data: window.chartCumulativeFees || [0], 
                        borderColor: colorYellow, 
                        backgroundColor: colorYellow + '05', 
                        borderWidth: 2, 
                        fill: true,
                        tension: 0.1,
                        pointRadius: 0
                    }
                ]
            },
            options: { 
                responsive: true, 
                maintainAspectRatio: false, 
                plugins: { 
                    legend: { 
                        display: true, 
                        position: 'top',
                        labels: { color: colorText, boxWidth: 12, font: { family: 'DM Sans', size: 12 } }
                    },
                    tooltip: { 
                        mode: 'index',
                        intersect: false,
                        backgroundColor: colorCardBg, 
                        titleColor: '#fff', 
                        bodyColor: '#e6edf3',
                        bodyFont: { family: 'JetBrains Mono' }
                    }
                }, 
                scales: { 
                    x: { grid: { color: colorGrid }, ticks: { color: colorText } }, 
                    y: { beginAtZero: true, grid: { color: colorGrid }, ticks: { color: colorText, callback: function(v) { return '₱' + (v/1000) + 'k'; } } } 
                },
                interaction: { mode: 'index', axis: 'x', intersect: false }
            }
        });
    }

    // --- B. Forecast Simulation Trajectory Line Graph ---
   const forecastCanvas = document.getElementById('forecastChart');
    if (forecastCanvas && typeof window.forecastChartData !== 'undefined') {
        let existingChart = Chart.getChart(forecastCanvas);
        if (existingChart) existingChart.destroy();

        const dynamicLabels = window.forecastChartData.labels || ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        window.activeForecastChartInstance = new Chart(forecastCanvas.getContext('2d'), {
            type: 'line',
            data: {
                labels: dynamicLabels,
                datasets: [
                    { label: 'Reality Balance', data: window.forecastChartData.base, borderColor: colorBlue, borderWidth: 2, tension: 0.1 },
                    { label: 'Simulation View', data: window.forecastChartData.sim, borderColor: colorYellow, backgroundColor: colorYellow + '10', borderWidth: 3, borderDash: [5, 5], fill: true, pointBackgroundColor: colorYellow }
                ]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { labels: { color: colorText } } }, scales: { x: { grid: { color: colorGrid }, ticks: { color: colorText } }, y: { grid: { color: colorGrid }, ticks: { color: colorText } } } }
        });
    }

    // --- C. Month-over-Month Cashflow Line Graph ---
    const cashflowCanvas = document.getElementById('cashflowChart');
    if (cashflowCanvas && typeof window.insightsCashflowData !== 'undefined') {
        let existingCashflow = Chart.getChart(cashflowCanvas);
        if (existingCashflow) existingCashflow.destroy();

        const dynamicLabels = window.insightsCashflowData.labels || ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        new Chart(cashflowCanvas.getContext('2d'), {
            type: 'line',
            data: {
                labels: dynamicLabels,
                datasets: [
                    { label: 'Inflow Streams', data: window.insightsCashflowData.inflow, borderColor: colorGreen, backgroundColor: colorGreen + '10', borderWidth: 2, tension: 0.3, fill: true },
                    { label: 'Outflow Streams', data: window.insightsCashflowData.outflow, borderColor: colorRed, borderWidth: 2, borderDash: [4, 4], tension: 0.3 }
                ]
            },
            options: { 
                responsive: true, 
                maintainAspectRatio: false, 
                plugins: { legend: { labels: { color: colorText } } }, 
                scales: { 
                    x: { grid: { color: colorGrid }, ticks: { color: colorText } }, 
                    y: { beginAtZero: true, grid: { color: colorGrid }, ticks: { color: colorText } } 
                } 
            }
        });
    }

    // --- D. Expense Allocation Doughnut Chart ---
    const expenseCanvas = document.getElementById('expenseChart');
    if (expenseCanvas && typeof window.insightsExpenseData !== 'undefined') {
        let existingExpense = Chart.getChart(expenseCanvas);
        if (existingExpense) existingExpense.destroy();

        new Chart(expenseCanvas.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: window.insightsExpenseData.labels,
                datasets: [{ data: window.insightsExpenseData.data, backgroundColor: window.insightsExpenseData.colors, borderWidth: 2, borderColor: colorCardBg }]
            },
            options: { responsive: true, maintainAspectRatio: false, cutout: '70%', plugins: { legend: { position: 'right', labels: { color: colorText } } } }
        });
    }

    // --- E. Global Portfolio Cross-Node Performance Bars ---
    const globalCanvas = document.getElementById('globalChart');
    if (globalCanvas && typeof window.globalPortfolioChartData !== 'undefined') {
        let existingChart = Chart.getChart(globalCanvas);
        if (existingChart) existingChart.destroy();

        new Chart(globalCanvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: window.globalPortfolioChartData.labels,
                datasets: [
                    { label: 'Inflow', data: window.globalPortfolioChartData.inflow, backgroundColor: colorGreen, borderRadius: 4 },
                    { label: 'Outflow', data: window.globalPortfolioChartData.outflow, backgroundColor: colorRed, borderRadius: 4 }
                ]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { labels: { color: colorText } } }, scales: { x: { grid: { display: false }, ticks: { color: colorText } }, y: { grid: { color: colorGrid }, ticks: { color: colorText } } } }
        });
    }
};

window.setupCategoryDragAndDrop = (list) => {
    let draggedItem = null;
    list.addEventListener('dragstart', e => {
        draggedItem = e.target.closest('.cat-row');
        if (draggedItem) setTimeout(() => draggedItem.style.opacity = '0.4', 0);
    });
    list.addEventListener('dragend', () => {
        if (draggedItem) draggedItem.style.opacity = '1';
        draggedItem = null;
        window.syncCategorySorting();
    });
    list.addEventListener('dragover', e => {
        e.preventDefault();
        const afterElement = [...list.querySelectorAll('.cat-row:not([style*="opacity: 0.4"])')].reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = e.clientY - box.top - box.height / 2;
            return (offset < 0 && offset > closest.offset) ? { offset: offset, element: child } : closest;
        }, { offset: Number.NEGATIVE_INFINITY }).element;
        if (draggedItem) {
            if (afterElement == null) list.appendChild(draggedItem);
            else list.insertBefore(draggedItem, afterElement);
        }
    });
};

// Category Reorder Sync Processing API
window.syncCategorySorting = async () => {
    const list = document.getElementById('category-list');
    if (!list) return;

    // 1. Prioritize reading the data attribute off the list itself, fallback to year-selector if on dashboard
    const listPid = list.dataset.pid;
    const selector = document.querySelector('#year-selector');
    const pid = listPid || (selector ? selector.dataset.pid : null);

    if (!pid) {
        window.showToast('Routing context missing: profile ID not found', 'error');
        return;
    }

    const rows = [...list.querySelectorAll('.cat-row')];
    const ids = rows.map(row => parseInt(row.dataset.id));
    const basePath = typeof BASE_PATH !== 'undefined' ? BASE_PATH : '';

    try {
        const res = await fetch(`${basePath}/categories/${pid}/reorder`, {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ 
                csrf_token: typeof CSRF_TOKEN !== 'undefined' ? CSRF_TOKEN : '', 
                ids: ids 
            })
        });
        
        const data = await res.json();
        if (data.success) {
            window.showToast('Display configurations updated', 'success');
        } else {
            window.showToast(data.error || 'Failed to update order', 'error');
        }
    } catch (err) {
        window.showToast('Network error during arrangement sync', 'error');
    }
};

// Global Calculator Row Generator Utility
window.addCalculatorRow = (label = '', amount = '', type = 'outflow', checked = true) => {
    const calcRows = document.getElementById('calc-rows');
    if (!calcRows) return;

    const div = document.createElement('div');
    div.className = 'calc-row-grid';
    div.innerHTML = `
        <input type="checkbox" class="calc-check" ${checked ? 'checked' : ''}>
        <input type="text" class="calc-label" placeholder="Item Name" value="${label}" style="margin: 0;">
        <input type="text" inputmode="decimal" class="calc-amount amount" placeholder="0.00" value="${amount}" style="margin: 0;">
        <select class="calc-type" style="margin: 0;">
            <option value="outflow" ${type === 'outflow' ? 'selected' : ''}>Outflow</option>
            <option value="inflow" ${type === 'inflow' ? 'selected' : ''}>Inflow</option>
        </select>
        <button class="icon-btn danger calc-remove" style="justify-self: end;">🗑</button>
    `;
    calcRows.appendChild(div);
    window.computeCalculatorTotals();
};

// Global Calculator Core Processor
window.computeCalculatorTotals = () => {
    let inflow = 0;
    let outflow = 0;
    
    document.querySelectorAll('.calc-row-grid').forEach(row => {
        const isChecked = row.querySelector('.calc-check')?.checked;
        if (!isChecked) return;
        
        const amt = parseFloat(row.querySelector('.calc-amount')?.value || 0);
        const type = row.querySelector('.calc-type')?.value;
        if (type === 'inflow') inflow += amt;
        else outflow += amt;
    });
    
    const inEl = document.getElementById('calc-res-in');
    const outEl = document.getElementById('calc-res-out');
    const netEl = document.getElementById('calc-res-net');
    
    if (inEl) inEl.innerText = inflow.toFixed(2);
    if (outEl) outEl.innerText = outflow.toFixed(2);
    
    if (netEl) {
        const net = inflow - outflow;
        netEl.innerText = (net >= 0 ? '+' : '') + net.toFixed(2);
        netEl.style.color = net >= 0 ? 'var(--accent-green)' : 'var(--accent-red)';
    }
};