        </main>
    </div>
</div>

<!-- Bootstrap 5 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Common Admin interactions -->
<script>
    // Confirm delete actions
    document.querySelectorAll('.btn-delete-confirm').forEach(btn => {
        btn.addEventListener('click', function(e) {
            if(!confirm('Are you sure you want to permanently delete this record? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });
</script>
</body>
</html>
