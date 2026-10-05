<?php /** components/footer.php */ ?>
<?php if (empty($bare)): ?>
        </main>
        <footer class="text-center text-muted small py-3 no-print">
            &copy; <?= date('Y') ?> <?= e(setting('organization', 'Klub Olahraga')) ?>
        </footer>
    </div>
</div>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
