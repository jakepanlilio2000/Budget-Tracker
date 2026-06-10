<?php $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>

<style>
    .calc-layout {
        display: grid; 
        grid-template-columns: 2fr 1fr; 
        gap: 24px; 
        align-items: start;
    }
    .calc-row-item {
        display: grid;
        grid-template-columns: 1fr 120px 120px auto;
        gap: 12px;
        align-items: center;
        background: var(--bg-primary);
        padding: 16px;
        border: 1px solid var(--border);
        border-radius: 8px;
    }
    
    /* Mobile Responsiveness */
    @media (max-width: 900px) {
        .calc-layout { 
            grid-template-columns: 1fr; 
            display: flex; 
            flex-direction: column-reverse; /* Put Results on top of mobile view */
        }
        .calc-row-item { 
            grid-template-columns: 1fr 1fr; 
        }
        .calc-row-item .row-label { 
            grid-column: 1 / -1; 
        }
        .calc-row-item .row-del { 
            justify-self: end; 
        }
    }
</style>

<header class="top-bar">
    <div class="top-bar-left">
        <h1><i class="fa-solid fa-calculator" style="color: var(--accent-blue); margin-right: 8px;"></i> Live Calculator</h1>
        <p style="color: var(--text-secondary);">Sandbox your budget. Import your current month or add custom rows.</p>
    </div>
    <div class="top-bar-right">
        <button id="calc-import-btn" data-pid="<?= htmlspecialchars((string)$profile_id) ?>" class="btn ghost">
            <i class="fa-solid fa-file-import"></i> Import This Month
        </button>
    </div>
</header>

<div class="calc-layout">
    <div class="card" id="calc-items-container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
            <h3 style="margin: 0;"><i class="fa-solid fa-list" style="color: var(--text-muted); margin-right: 8px;"></i> Line Items</h3>
            <button id="calc-add-row" class="btn ghost"><i class="fa-solid fa-plus"></i> Add Row</button>
        </div>
        <div id="calc-rows" style="display: flex; flex-direction: column; gap: 12px;">
            <div id="empty-state" style="text-align: center; color: var(--text-muted); padding: 32px;">No items loaded. Import your ledger or add a custom row to start simulating.</div>
        </div>
    </div>

    <div class="card calc-result-panel" style="position: sticky; top: 24px;">
        <h3 style="margin-bottom: 24px;"><i class="fa-solid fa-receipt" style="color: var(--text-muted); margin-right: 8px;"></i> Results</h3>
        <div style="display: flex; flex-direction: column; gap: 16px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="color: var(--text-secondary); font-size: 14px; font-weight: bold;">TOTAL INFLOW</span>
                <div style="display: flex; gap: 4px; font-weight: bold; color: var(--accent-green); font-size: 18px;">
                    <span class="currency-label"><?= htmlspecialchars($profile['currency'] ?? '₱') ?></span>
                    <span id="calc-res-in" class="amount">0.00</span>
                </div>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="color: var(--text-secondary); font-size: 14px; font-weight: bold;">TOTAL OUTFLOW</span>
                <div style="display: flex; gap: 4px; font-weight: bold; color: var(--accent-red); font-size: 18px;">
                    <span class="currency-label"><?= htmlspecialchars($profile['currency'] ?? '₱') ?></span>
                    <span id="calc-res-out" class="amount">0.00</span>
                </div>
            </div>
            <hr style="border: 0; border-top: 1px dashed var(--border);">
            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 24px; font-weight: bold;">
                <span>NET</span>
                <div style="display: flex; gap: 4px;" id="calc-net-wrapper">
                    <span id="calc-res-sign"></span>
                    <span class="currency-label"><?= htmlspecialchars($profile['currency'] ?? '₱') ?></span>
                    <span id="calc-res-net" class="amount">0.00</span>
                </div>
            </div>
        </div>
        <div style="margin-top: 32px;">
            <button class="btn primary" style="width: 100%; padding: 16px; font-size: 16px;" onclick="alert('Session saving will be implemented soon!')"><i class="fa-solid fa-floppy-disk"></i> Save Calculation</button>
        </div>
    </div>
</div>

<script>
// SPA SAFE WRAPPER: Executes immediately upon DOM injection, bypassing DOMContentLoaded delays
(function() {
    const basePath = '<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') ?>';
    const importBtn = document.getElementById('calc-import-btn');
    if (!importBtn) return; // Failsafe

    const profileId = importBtn.dataset.pid;
    const rowsContainer = document.getElementById('calc-rows');
    const emptyState = document.getElementById('empty-state');
    const addRowBtn = document.getElementById('calc-add-row');
    
    // Live calculation triggers
    function updateTotals() {
        let totalIn = 0;
        let totalOut = 0;

        document.querySelectorAll('.calc-row-item').forEach(row => {
            const amount = parseFloat(row.querySelector('.row-amt').value) || 0;
            const type = row.querySelector('.row-type').value;

            if (type === 'inflow') totalIn += amount;
            else totalOut += amount;
        });

        const net = totalIn - totalOut;

        document.getElementById('calc-res-in').textContent = totalIn.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('calc-res-out').textContent = totalOut.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        
        const netEl = document.getElementById('calc-res-net');
        const signEl = document.getElementById('calc-res-sign');
        const wrapEl = document.getElementById('calc-net-wrapper');
        
        netEl.textContent = Math.abs(net).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        
        if (net >= 0) {
            signEl.textContent = '+';
            wrapEl.style.color = 'var(--accent-green)';
        } else {
            signEl.textContent = '-';
            wrapEl.style.color = 'var(--accent-red)';
        }
    }

    // Row Generation with fixed trash button styling
    function createRow(label = '', amount = '', type = 'outflow') {
        if (emptyState) emptyState.style.display = 'none';

        const row = document.createElement('div');
        row.className = 'calc-row-item';
        
      row.innerHTML = `
    <input type="text" class="row-label" value="${label}" placeholder="Description" style="width: 100%;">
    <input type="number" class="row-amt" value="${amount}" placeholder="0.00" step="0.01" style="width: 100%;">
    <select class="row-type" style="width: 100%;">
        <option value="outflow" ${type === 'outflow' ? 'selected' : ''}>Outflow</option>
        <option value="inflow" ${type === 'inflow' ? 'selected' : ''}>Inflow</option>
    </select>

    <button type="button"
        class="btn ghost row-del"
        style="border: 1px solid var(--border); padding: 8px 12px; font-size: 13px; color: var(--accent-red); background: transparent; cursor: pointer;">
        <i class="fa-solid fa-trash-can"></i>
    </button>
`;

        // Bind events for live calculation
        row.querySelector('.row-amt').addEventListener('input', updateTotals);
        row.querySelector('.row-type').addEventListener('change', updateTotals);
        
        row.querySelector('.row-del').addEventListener('click', () => {
            row.remove();
            if (rowsContainer.children.length === 0 && emptyState) { 
                rowsContainer.appendChild(emptyState);
                emptyState.style.display = 'block';
            }
            updateTotals();
        });

        rowsContainer.appendChild(row);
    }

    // Replace button to drop old event listeners, preventing stacking clicks
    if (addRowBtn) {
        const newAddBtn = addRowBtn.cloneNode(true);
        addRowBtn.parentNode.replaceChild(newAddBtn, addRowBtn);
        newAddBtn.addEventListener('click', () => {
            createRow();
            updateTotals();
        });
    }

    // Import Data Button logic replacement
    if (importBtn) {
        const newImportBtn = importBtn.cloneNode(true);
        importBtn.parentNode.replaceChild(newImportBtn, importBtn);
        newImportBtn.addEventListener('click', async function() {
            this.disabled = true;
            this.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Fetching...';
            
            try {
                const now = new Date();
                const yyyy = now.getFullYear();
                const mm = String(now.getMonth() + 1).padStart(2, '0');
                const periodMonth = `${yyyy}-${mm}`;

                const res = await fetch(`${basePath}/calculator/${profileId}/import?period=${periodMonth}`);
                const data = await res.json();
                
                if (data.items && data.items.length > 0) {
                    rowsContainer.innerHTML = ''; // Clear existing
                    data.items.forEach(item => {
                        createRow(item.label, item.amount, item.type);
                    });
                    updateTotals();
                } else {
                    alert('No active transactions found for this month.');
                }
            } catch (e) {
                console.error(e);
                alert('Failed to import period data.');
            } finally {
                this.disabled = false;
                this.innerHTML = '<i class="fa-solid fa-file-import"></i> Import This Month';
            }
        });
    }

    // Initial safe trigger
    typeof window.initializeActiveViewHelpers === 'function' && window.initializeActiveViewHelpers();
})();
</script>