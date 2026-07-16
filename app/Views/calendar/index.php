<?php
declare(strict_types=1);
use App\Core\Auth;

$pageTitle = 'Financial Calendar';
ob_start();
$sym = $baseCurrency['symbol'];
?>

<!-- FullCalendar CDN -->
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css' rel='stylesheet' />
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>

<div class="page-header flex-between" style="flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1>Financial Calendar</h1>
        <p class="text-secondary">Unified view of all your financial events and obligations.</p>
    </div>
</div>

<div class="grid" style="grid-template-columns: 1fr 350px; gap: 1.5rem; align-items: start;">
    <!-- Calendar View -->
    <div class="card glass" style="padding: 1.5rem;">
        <div id="financialCalendar"></div>
    </div>

    <!-- Day Summary Drawer -->
    <div class="card glass" id="daySummaryCard" style="position: sticky; top: 80px;">
        <h3 id="summaryTitle">Daily Summary</h3>
        <div id="summaryContent" class="mt-3">
            <p class="text-secondary text-center" style="padding: 2rem;">Click a date on the calendar to view the
                financial summary for that day.</p>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const calendarEl = document.getElementById('financialCalendar');
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        const textColor = isDark ? '#f8fafc' : '#1e293b';
        const borderColor = isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.1)';

        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,listWeek'
            },
            themeSystem: 'standard',
            height: 'auto',
            events: '<?= url('/calendar/events') ?>',
            eventDisplay: 'block',
            displayEventTime: false,

            eventContent: function (arg) {
                let icon = arg.event.extendedProps.icon || 'fa-circle';
                let color = arg.event.backgroundColor || '#64748b';
                return {
                    html: `<div style="display:flex; align-items:center; gap:0.4rem; font-size:0.8rem; font-weight:500;">
                    <i class="fas ${icon}" style="color:${color};"></i>
                    <span>${arg.event.title}</span>
                </div>`
                };
            },

            // Handle Date Clicks for Daily Summary
            dateClick: function (info) {
                loadDaySummary(info.dateStr);
            },
            eventClick: function (info) {
                loadDaySummary(info.event.startStr);
            }
        });

        calendar.render();
    });

    async function loadDaySummary(dateStr) {
        const titleEl = document.getElementById('summaryTitle');
        const contentEl = document.getElementById('summaryContent');

        titleEl.textContent = 'Loading...';
        contentEl.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i></div>';

        try {
            const res = await fetch(`<?= url('/calendar/day-summary') ?>?date=${dateStr}`);
            const data = await res.json();

            if (data.success) {
                const s = data.summary;
                const sym = s.baseCurrency.symbol;
                const dateObj = new Date(s.date + 'T00:00:00');
                const formattedDate = dateObj.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });

                titleEl.textContent = formattedDate;

                let html = `
                <div class="grid grid-3" style="gap: 0.5rem; text-align: center; margin-bottom: 1rem;">
                    <div style="padding: 0.5rem; background: rgba(16,185,129,0.05); border-radius: 6px;">
                        <small class="text-secondary">Income</small>
                        <div class="sensitive-data" style="font-weight:bold; color:var(--success);">${sym}${s.total_income.toFixed(2)}</div>
                    </div>
                    <div style="padding: 0.5rem; background: rgba(239,68,68,0.05); border-radius: 6px;">
                        <small class="text-secondary">Expense</small>
                        <div class="sensitive-data" style="font-weight:bold; color:var(--danger);">${sym}${s.total_expense.toFixed(2)}</div>
                    </div>
                    <div style="padding: 0.5rem; background: rgba(59,130,246,0.05); border-radius: 6px;">
                        <small class="text-secondary">Net</small>
                        <div class="sensitive-data" style="font-weight:bold; color:${s.net_flow >= 0 ? 'var(--success)' : 'var(--danger)'};">${sym}${s.net_flow.toFixed(2)}</div>
                    </div>
                </div>
            `;

                if (s.events.length === 0) {
                    html += '<p class="text-secondary text-center" style="padding: 1rem;">No financial events on this day.</p>';
                } else {
                    html += '<div style="display:flex; flex-direction:column; gap:0.5rem; max-height: 400px; overflow-y:auto;">';
                    s.events.forEach(e => {
                        html += `
                        <div style="display:flex; align-items:center; gap:0.75rem; padding:0.5rem; background:rgba(0,0,0,0.02); border-radius:6px; border-left:3px solid ${e.color};">
                            <i class="fas ${e.icon}" style="color:${e.color}; width:20px; text-align:center;"></i>
                            <div style="flex:1; font-size:0.85rem;">
                                <div style="font-weight:500;">${e.title}</div>
                                <small class="text-secondary">${e.module.replace('_', ' ')}</small>
                            </div>
                            <div class="sensitive-data" style="font-weight:bold; font-size:0.85rem;">${e.currency_symbol}${e.amount.toFixed(2)}</div>
                        </div>
                    `;
                    });
                    html += '</div>';
                }

                contentEl.innerHTML = html;
            }
        } catch (err) {
            contentEl.innerHTML = '<div class="alert alert-danger">Failed to load summary.</div>';
        }
    }
</script>

<?php
$content = ob_get_clean();
$this->view('layouts.app', ['pageTitle' => $pageTitle, 'content' => $content]);
?>