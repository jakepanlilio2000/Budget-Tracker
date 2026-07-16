<?php
declare(strict_types=1);
use App\Core\Auth;

$pageTitle = 'Categories';
ob_start();
?>
<div class="page-header flex-between">
    <h1>Categories</h1>
    <button class="btn btn-primary" onclick="document.getElementById('addCatModal').style.display='flex'">
        <i class="fas fa-plus"></i> Add Category
    </button>
</div>

<div class="grid grid-3">
    <?php
    $activeCats = array_filter($categories, fn($c) => empty($c['is_archived']));
    $archivedCats = array_filter($categories, fn($c) => !empty($c['is_archived']));
    ?>

    <?php foreach ($activeCats as $cat): ?>
        <div class="card glass" style="border-left: 4px solid <?= e($cat['color']) ?>;">
            <div class="flex-between">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <div
                        style="width: 40px; height: 40px; border-radius: 10px; background: <?= e($cat['color']) ?>20; color: <?= e($cat['color']) ?>; display: flex; align-items: center; justify-content: center;">
                        <i class="fas <?= e($cat['icon'] ?: 'fa-tag') ?>"></i>
                    </div>
                    <div>
                        <h3 style="margin:0; font-size: 1rem;"><?= e($cat['name']) ?></h3>
                        <small class="text-secondary"><?= ucfirst(e($cat['type'])) ?></small>
                    </div>
                </div>
                <form method="POST" action="<?= url('/categories/archive/' . $cat['id']) ?>"
                    onsubmit="return confirm('Archive this category?')">
                    <?= \App\Core\CSRF::field() ?>
                    <button type="submit" class="btn btn-sm" style="background:transparent; color:var(--text-secondary);"><i
                            class="fas fa-archive"></i></button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if (!empty($archivedCats)): ?>
    <h3 class="mt-4 mb-3" style="color: var(--text-secondary);">Archived Categories</h3>
    <div class="grid grid-3" style="opacity: 0.6;">
        <?php foreach ($archivedCats as $cat): ?>
            <div class="card glass" style="border-left: 4px solid var(--text-secondary);">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <i class="fas <?= e($cat['icon'] ?: 'fa-tag') ?>" style="color: var(--text-secondary);"></i>
                    <div style="flex: 1;">
                        <h3 style="margin:0; font-size: 1rem; text-decoration: line-through;"><?= e($cat['name']) ?></h3>
                        <small class="text-secondary"><?= ucfirst(e($cat['type'])) ?></small>
                    </div>
                </div>
                <div style="margin-top: 0.75rem; text-align: right;">
                    <form method="POST" action="<?= url('/categories/delete/' . $cat['id']) ?>" style="display: inline;"
                        onsubmit="return confirm('️ PERMANENT DELETE ⚠️\n\nThis will permanently delete this category and cannot be undone.\n\nAre you sure?')">
                        <?= \App\Core\CSRF::field() ?>
                        <button type="submit" class="btn btn-sm" style="background: var(--danger); color: white;">
                            <i class="fas fa-trash-alt"></i> Delete Permanently
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Add Category Modal -->
<div id="addCatModal" class="modal-overlay" style="display: none;"
    onclick="if(event.target===this)this.style.display='none'">
    <div class="modal-content glass" style="padding: 1.5rem; max-width: 500px;">
        <h3>Add Category</h3>
        <form method="POST" action="<?= url('/categories/store') ?>" class="form-stack mt-3">
            <?= \App\Core\CSRF::field() ?>
            <div class="form-group"><label>Name</label><input type="text" name="name" required></div>
            <div class="grid grid-2">
                <div class="form-group"><label>Type</label>
                    <select name="type">
                        <option value="expense">Expense</option>
                        <option value="income">Income</option>
                    </select>
                </div>
                <div class="form-group"><label>Color</label><input type="color" name="color" value="#3b82f6"
                        style="height: 40px; width: 100%;"></div>
            </div>

            <div class="form-group">
                <label>Icon</label>
                <input type="hidden" name="icon" id="selectedIcon" value="fa-tag">
                <div id="iconGrid"
                    style="display: grid; grid-template-columns: repeat(6, 1fr); gap: 0.5rem; max-height: 150px; overflow-y: auto; padding: 0.5rem; background: var(--bg-glass-solid); border-radius: 8px; border: 1px solid var(--border-color);">
                    <!-- Icons injected via JS -->
                </div>
                <small class="text-secondary" id="iconNameDisplay">Selected: fa-tag</small>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Save Category</button>
        </form>
    </div>
</div>

<script>
    // Comprehensive list of common finance/lifestyle FontAwesome icons
    const faIcons = [
        // General
        'fa-tag', 'fa-bookmark', 'fa-star', 'fa-heart', 'fa-bell', 'fa-flag', 'fa-fire', 'fa-leaf', 'fa-seedling',
        'fa-sun', 'fa-moon', 'fa-cloud', 'fa-snowflake', 'fa-rainbow', 'fa-bolt', 'fa-gem', 'fa-crown', 'fa-medal',

        // Finance
        'fa-wallet', 'fa-money-bill', 'fa-money-bill-wave', 'fa-coins', 'fa-piggy-bank',
        'fa-credit-card', 'fa-cash-register', 'fa-receipt', 'fa-file-invoice-dollar',
        'fa-chart-line', 'fa-chart-pie', 'fa-chart-bar', 'fa-percent',
        'fa-hand-holding-usd', 'fa-donate', 'fa-sack-dollar',

        // Shopping
        'fa-shopping-cart', 'fa-shopping-basket', 'fa-shopping-bag',
        'fa-store', 'fa-store-alt', 'fa-cart-plus', 'fa-barcode',
        'fa-box', 'fa-box-open', 'fa-boxes', 'fa-gift',

        // Food & Drinks
        'fa-utensils', 'fa-hamburger', 'fa-pizza-slice', 'fa-hotdog',
        'fa-ice-cream', 'fa-birthday-cake', 'fa-cookie',
        'fa-apple-alt', 'fa-carrot', 'fa-fish', 'fa-drumstick-bite',
        'fa-cheese', 'fa-coffee', 'fa-mug-hot', 'fa-wine-glass',
        'fa-beer', 'fa-cocktail', 'fa-glass-martini',

        // Transportation
        'fa-car', 'fa-car-side', 'fa-bus', 'fa-train', 'fa-subway',
        'fa-plane', 'fa-helicopter', 'fa-ship', 'fa-bicycle',
        'fa-motorcycle', 'fa-gas-pump', 'fa-taxi', 'fa-road',
        'fa-traffic-light',

        // Home
        'fa-home', 'fa-building', 'fa-city', 'fa-warehouse',
        'fa-door-open', 'fa-couch', 'fa-bed', 'fa-bath',
        'fa-toilet', 'fa-lightbulb', 'fa-plug', 'fa-fan',
        'fa-tools', 'fa-hammer', 'fa-wrench', 'fa-key',

        // Technology
        'fa-laptop', 'fa-desktop', 'fa-server', 'fa-database',
        'fa-mobile-alt', 'fa-tablet-alt', 'fa-tv', 'fa-print',
        'fa-keyboard', 'fa-mouse', 'fa-headphones', 'fa-camera',
        'fa-video', 'fa-microphone', 'fa-wifi', 'fa-satellite-dish',
        'fa-sd-card', 'fa-memory', 'fa-microchip', 'fa-usb',

        // Health
        'fa-heartbeat', 'fa-heart', 'fa-hospital', 'fa-clinic-medical',
        'fa-user-md', 'fa-stethoscope', 'fa-pills', 'fa-capsules',
        'fa-syringe', 'fa-band-aid', 'fa-first-aid', 'fa-briefcase-medical',
        'fa-ambulance', 'fa-dna',

        // Education
        'fa-book', 'fa-book-open', 'fa-graduation-cap', 'fa-school',
        'fa-pencil-alt', 'fa-pen', 'fa-marker', 'fa-ruler',
        'fa-calculator', 'fa-atlas', 'fa-globe',

        // Office
        'fa-briefcase', 'fa-user-tie', 'fa-users', 'fa-id-card',
        'fa-folder', 'fa-folder-open', 'fa-file', 'fa-file-alt',
        'fa-clipboard', 'fa-calendar', 'fa-calendar-check',
        'fa-clock', 'fa-stopwatch', 'fa-tasks',

        // Sports
        'fa-football-ball', 'fa-basketball-ball', 'fa-baseball-ball',
        'fa-table-tennis', 'fa-dumbbell', 'fa-running', 'fa-swimmer',
        'fa-futbol', 'fa-volleyball-ball', 'fa-golf-ball',

        // Entertainment
        'fa-film', 'fa-tv', 'fa-music', 'fa-guitar', 'fa-drum',
        'fa-headphones', 'fa-gamepad', 'fa-chess', 'fa-puzzle-piece',
        'fa-ticket-alt', 'fa-theater-masks',

        // Clothing
        'fa-tshirt', 'fa-shoe-prints', 'fa-glasses',
        'fa-ring', 'fa-gem',

        // Nature
        'fa-tree', 'fa-leaf', 'fa-seedling', 'fa-mountain',
        'fa-water', 'fa-umbrella', 'fa-sun', 'fa-cloud-sun',

        // Pets
        'fa-paw', 'fa-dog', 'fa-cat', 'fa-dove', 'fa-crow',
        'fa-horse', 'fa-fish',

        // Travel
        'fa-suitcase', 'fa-map', 'fa-map-marked-alt',
        'fa-map-marker-alt', 'fa-passport', 'fa-route',
        'fa-compass', 'fa-globe-americas',

        // Utilities
        'fa-bolt', 'fa-faucet', 'fa-fire-extinguisher',
        'fa-recycle', 'fa-trash', 'fa-dumpster', 'fa-broom',
        'fa-soap', 'fa-spray-can',

        // Communication
        'fa-phone', 'fa-phone-alt', 'fa-envelope', 'fa-envelope-open',
        'fa-comment', 'fa-comments', 'fa-paper-plane', 'fa-inbox',

        // Security
        'fa-lock', 'fa-lock-open', 'fa-shield-alt', 'fa-user-shield',
        'fa-fingerprint', 'fa-key',

        // Religion / Charity
        'fa-church', 'fa-pray', 'fa-hands-helping', 'fa-hand-holding-heart',

        // Miscellaneous
        'fa-anchor', 'fa-binoculars', 'fa-bomb', 'fa-bug', 'fa-campground',
        'fa-crosshairs', 'fa-feather', 'fa-magic', 'fa-magnet',
        'fa-paperclip', 'fa-parachute-box', 'fa-rocket',
        'fa-search', 'fa-shopping-cart', 'fa-skull', 'fa-snowman',
        'fa-space-shuttle', 'fa-thumbtack', 'fa-toolbox', 'fa-trophy',
        'fa-user', 'fa-users-cog', 'fa-user-friends', 'fa-user-graduate'
    ];

    const iconGrid = document.getElementById('iconGrid');
    const selectedIconInput = document.getElementById('selectedIcon');
    const iconNameDisplay = document.getElementById('iconNameDisplay');

    faIcons.forEach(icon => {
        const div = document.createElement('div');
        div.style.cssText = 'display:flex; align-items:center; justify-content:center; height:40px; border-radius:6px; cursor:pointer; border:1px solid transparent; transition:all 0.2s;';
        div.innerHTML = `<i class="fas ${icon}" style="font-size:1.2rem; color:var(--text-secondary);"></i>`;
        div.onclick = () => {
            document.querySelectorAll('#iconGrid div').forEach(d => {
                d.style.background = 'transparent';
                d.style.borderColor = 'transparent';
                d.querySelector('i').style.color = 'var(--text-secondary)';
            });
            div.style.background = 'rgba(59, 130, 246, 0.1)';
            div.style.borderColor = 'var(--accent)';
            div.querySelector('i').style.color = 'var(--accent)';
            selectedIconInput.value = icon;
            iconNameDisplay.textContent = `Selected: ${icon}`;
        };
        iconGrid.appendChild(div);
    });
    // Select first by default
    iconGrid.children[0].click();
</script>
<?php
$content = ob_get_clean();
$this->view('layouts.app', ['pageTitle' => $pageTitle, 'content' => $content]);
?>