<?php
// includes/footer.php
?>
    </main>
</div>
</div>

<script>
// Auto format number with dots on input
document.addEventListener('input', function (e) {
    if (e.target.classList.contains('number-format-input')) {
        let value = e.target.value.replace(/\D/g, "");
        e.target.value = value.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }
});

// Strip dots before form submit
document.addEventListener('submit', function (e) {
    e.target.querySelectorAll('.number-format-input').forEach(input => {
        input.value = input.value.replace(/\./g, "");
    });
});
</script>
</body>
</html>