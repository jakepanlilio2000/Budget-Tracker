<?php $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>
<header class="top-bar">
    <h1>Live Calculator</h1>
    <button id="calc-import-btn" data-pid="<?= $profile_id ?>" class="btn ghost">📥 Import Current Period</button>
</header>

<div class="calc-layout">
    
    <div class="card" id="calc-items-container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
            <h3 style="margin: 0;">Line Items</h3>
            <button id="calc-add-row" class="btn ghost">+ Add Row</button>
        </div>
        
        <div id="calc-rows" style="display: flex; flex-direction: column; gap: 12px;">
            </div>
    </div>

    <div class="card calc-result-panel" style="position: sticky; top: 24px;">
        <h3 style="margin-bottom: 24px;">Results</h3>
        <div style="display: flex; flex-direction: column; gap: 16px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="color: var(--text-secondary); font-size: 14px; font-weight: bold;">TOTAL INFLOW</span>
                <span id="calc-res-in" class="amount" style="color: var(--accent-green); font-size: 18px;">0.00</span>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="color: var(--text-secondary); font-size: 14px; font-weight: bold;">TOTAL OUTFLOW</span>
                <span id="calc-res-out" class="amount" style="color: var(--accent-red); font-size: 18px;">0.00</span>
            </div>
            <hr style="border: 0; border-top: 1px dashed var(--border);">
            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 24px; font-weight: bold;">
                <span>NET</span>
                <span id="calc-res-net" class="amount">0.00</span>
            </div>
        </div>
        <div style="margin-top: 32px;">
            <button class="btn primary" style="width: 100%; padding: 16px; font-size: 16px;">Save Calculation</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const calcRows = document.getElementById('calc-rows');
    const calcAddBtn = document.getElementById('calc-add-row');
    const importBtn = document.getElementById('calc-import-btn');

    const compute = () => {
        let inflow = 0;
        let outflow = 0;
        document.querySelectorAll('.calc-row-grid').forEach(row => {
            const isChecked = row.querySelector('.calc-check').checked;
            if (!isChecked) return;
            const amt = parseFloat(row.querySelector('.calc-amount').value || 0);
            const type = row.querySelector('.calc-type').value;
            if (type === 'inflow') inflow += amt;
            else outflow += amt;
        });
        
        document.getElementById('calc-res-in').innerText = inflow.toFixed(2);
        document.getElementById('calc-res-out').innerText = outflow.toFixed(2);
        const net = inflow - outflow;
        const netEl = document.getElementById('calc-res-net');
        netEl.innerText = (net >= 0 ? '+' : '') + net.toFixed(2);
        netEl.style.color = net >= 0 ? 'var(--accent-green)' : 'var(--accent-red)';
    };

    const addRow = (label = '', amount = '', type = 'outflow', checked = true) => {
        const div = document.createElement('div');
        // Apply the new responsive grid class
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
        
        div.querySelectorAll('input, select').forEach(el => el.addEventListener('input', compute));
        div.querySelector('.calc-remove').addEventListener('click', () => { div.remove(); compute(); });
        calcRows.appendChild(div);
        compute();
    };

    calcAddBtn.addEventListener('click', () => addRow());
    addRow('Initial Value', '0.00', 'outflow');

    importBtn?.addEventListener('click', async (e) => {
        const pid = e.target.dataset.pid;
        calcRows.innerHTML = ''; 
        try {
            const res = await fetch(`<?= $basePath ?>/calculator/${pid}/import`);
            const data = await res.json();
            data.items.forEach(item => addRow(item.label, item.amount, item.type, item.checked));
            showToast('Period data imported successfully.');
        } catch (err) {
            showToast('Failed to import data.', 'error');
        }
    });
});
</script>