</div> </main>
    
    <?php if (isset($profile['id']) || isset($profile_id)): ?>
    <?php $pid = $profile['id'] ?? $profile_id; ?>
    <nav class="bottom-nav mobile-only">
        <a href="<?= $basePath ?>/dashboard/<?= $pid ?>"><i class="fa-solid fa-chart-pie"></i></a>
        <a href="<?= $basePath ?>/entries/<?= $pid ?>"><i class="fa-solid fa-file-pen"></i></a>
        <a href="<?= $basePath ?>/calculator/<?= $pid ?>"><i class="fa-solid fa-calculator"></i></a>
        <a href="<?= $basePath ?>/profile/<?= $pid ?>/edit"><i class="fa-solid fa-gear"></i></a>
    </nav>
    <?php endif; ?>

    <div id="toast-container"></div>
    
    <div id="confirm-modal" class="modal">
        <div class="modal-content drawer">
            <h3 id="confirm-title">Are you sure?</h3>
            <p id="confirm-message">This action cannot be undone.</p>
            <div class="modal-actions">
                <button id="confirm-cancel" class="btn ghost">Cancel</button>
                <button id="confirm-ok" class="btn danger">Confirm</button>
            </div>
        </div>
    </div>

    </div> <script src="<?= $basePath ?>/public/js/app.js"></script>
</body>
</html>