<?php
declare(strict_types=1);
use App\Core\Auth;

$pageTitle = 'What-If Sandbox';
ob_start();
$sym = $baseCurrency['symbol'];
?>

<div class="page-header flex-between" style="flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1>What-If Sandbox</h1>
        <p class="text-secondary">Simulate future financial decisions without affecting real data.</p>
    </div>
    <a href="<?= url('/forecast') ?>" class="btn" style="background: var(--text-secondary); color: white;"><i
            class="fas fa-arrow-left"></i> Back to Forecast</a>
</div>

<div class="grid" style="grid-template-columns: 1fr 1fr; gap: 1.5rem;">
    <!-- Simulator Controls -->
    <div class="card glass">
        <h3><i class="fas fa-flask"></i> Simulation Parameters</h3>
        <form id="sandboxForm" class="form-stack mt-3">
            <div class="form-group">
                <label>Projection Period (Days)</label>
                <input type="number" id="simDays" value="30" min="1" max="365">
            </div>

            <h4 style="margin-top: 1rem; border-top: 1px solid var(--border-color); padding-top: 1rem;">Simulated Events
            </h4>
            <div id="simEventsContainer"></div>
            <button type="button" class="btn btn-sm"
                style="background: var(--accent); color: white; margin-top: 0.5rem;" onclick="addSimEvent()">
                <i class="fas fa-plus"></i> Add Event
            </button>

            <div class="flex-between mt-4" style="border-top: 1px solid var(--border-color); padding-top: 1rem;">
                <button type="button" class="btn" style="background: var(--text-secondary); color: white;"
                    onclick="runSimulation()">
                    <i class="fas fa-play"></i> Run Simulation
                </button>
                <button type="button" class="btn btn-primary" onclick="saveScenario()">
                    <i class="fas fa-save"></i> Save Scenario
                </button>
            </div>
        </form>
    </div>

    <!-- Results -->
    <div class="card glass">
        <h3>Simulated Outcome</h3>
        <div id="simResults" class="mt-3">
            <p class="text-secondary text-center" style="padding: 2rem;">Add events and click "Run Simulation" to see
                the projected outcome.</p>
        </div>
    </div>
</div>

<!-- Saved Scenarios -->
<?php if (!empty($scenarios)): ?>
    <div class="card glass mt-4">
        <h3>Saved Scenarios</h3>
        <div class="grid grid-3 mt-3">
            <?php foreach ($scenarios as $sc): ?>
                <div
                    style="padding: 1rem; background: rgba(0,0,0,0.02); border-radius: 8px; border: 1px solid var(--border-color);">
                    <h4 style="margin: 0 0 0.5rem;"><?= e($sc['name']) ?></h4>
                    <small class="text-secondary"><?= e(date('M d, Y', strtotime($sc['created_at']))) ?></small>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<script>
    const sym = '<?= $sym ?>';
    let eventCount = 0;

    function addSimEvent() {
        eventCount++;
        const container = document.getElementById('simEventsContainer');
        const div = document.createElement('div');
        div.className = 'grid grid-4';
        div.style.cssText = 'gap: 0.5rem; margin-bottom: 0.5rem; align-items: end;';
        div.id = `simEvent_${eventCount}`;
        div.innerHTML = `
        <div class="form-group" style="margin:0;">
            <label style="font-size:0.8rem;">Date</label>
            <input type="date" name="sim_date[]" class="sim-date" value="<?= date('Y-m-d', strtotime('+7 days')) ?>">
        </div>
        <div class="form-group" style="margin:0;">
            <label style="font-size:0.8rem;">Type</label>
            <select name="sim_type[]" class="sim-type">
                <option value="expense">Expense</option>
                <option value="income">Income</option>
            </select>
        </div>
        <div class="form-group" style="margin:0;">
            <label style="font-size:0.8rem;">Amount</label>
            <input type="number" step="0.01" name="sim_amount[]" class="sim-amount" placeholder="0.00">
        </div>
        <button type="button" class="btn btn-sm" style="background:var(--danger); color:white; height:38px;" onclick="this.parentElement.remove()">×</button>
    `;
        container.appendChild(div);
    }

    async function runSimulation() {
        const formData = new FormData();
        formData.append('days', document.getElementById('simDays').value);

        document.querySelectorAll('#simEventsContainer > div').forEach(row => {
            formData.append('sim_date[]', row.querySelector('.sim-date').value);
            formData.append('sim_type[]', row.querySelector('.sim-type').value);
            formData.append('sim_amount[]', row.querySelector('.sim-amount').value);
        });

        const resWrap = document.getElementById('simResults');
        resWrap.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Calculating...</div>';

        try {
            const res = await fetch('<?= url('/forecast/run-sandbox') ?>', { method: 'POST', body: formData });
            const data = await res.json();

            if (data.success) {
                const f = data.forecast;
                const sym = data.baseCurrency.symbol;
                const color = f.final_balance >= 0 ? 'var(--success)' : 'var(--danger)';

                resWrap.innerHTML = `
                <div class="grid grid-2" style="gap: 1rem; text-align: center;">
                    <div style="padding: 1rem; background: rgba(16,185,129,0.05); border-radius: 8px;">
                        <small class="text-secondary">Projected Final Balance</small>
                        <h2 style="color: ${color}; margin: 0.5rem 0 0;" class="sensitive-data">${sym}${f.final_balance.toFixed(2)}</h2>
                    </div>
                    <div style="padding: 1rem; background: rgba(59,130,246,0.05); border-radius: 8px;">
                        <small class="text-secondary">Net Cash Flow</small>
                        <h2 style="color: var(--accent); margin: 0.5rem 0 0;" class="sensitive-data">${sym}${f.net_flow.toFixed(2)}</h2>
                    </div>
                </div>
                ${f.warnings.includes('cash_shortage') ? '<div class="alert alert-danger mt-3" style="margin-bottom:0;"><i class="fas fa-exclamation-triangle"></i> Warning: Simulated balance drops below zero!</div>' : ''}
            `;
            }
        } catch (err) {
            resWrap.innerHTML = '<div class="alert alert-danger">Simulation failed.</div>';
        }
    }

    function saveScenario() {
        const name = prompt('Enter a name for this scenario:');
        if (!name) return;

        const formData = new FormData();
        formData.append('name', name);
        formData.append('scenario_data[days]', document.getElementById('simDays').value);

        let i = 0;
        document.querySelectorAll('#simEventsContainer > div').forEach(row => {
            formData.append(`scenario_data[events][${i}][date]`, row.querySelector('.sim-date').value);
            formData.append(`scenario_data[events][${i}][type]`, row.querySelector('.sim-type').value);
            formData.append(`scenario_data[events][${i}][amount]`, row.querySelector('.sim-amount').value);
            i++;
        });

        // Create a temporary form to submit with CSRF
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?= url('/forecast/save-scenario') ?>';
        form.innerHTML = '<?= \App\Core\CSRF::field() ?>';

        for (let [key, val] of formData.entries()) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = val;
            form.appendChild(input);
        }
        document.body.appendChild(form);
        form.submit();
    }
</script>

<?php
$content = ob_get_clean();
$this->view('layouts.app', ['pageTitle' => $pageTitle, 'content' => $content]);
?>