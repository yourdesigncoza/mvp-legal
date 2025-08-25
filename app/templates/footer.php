        </div>
    </main>

    <!-- Footer -->
    <footer class="footer position-relative mt-5 fs-9 py-3 pt-4 bg-primary-lighter" style="height: auto;">
        <div class="container">
            <div class="row g-0 justify-content-between align-items-center">
                <div class="col-12 col-sm-auto text-center">
                    <p class="mb-0 text-body-tertiary">
                        Appeal Prospect MVP &copy; <?= date('Y') ?> 
                        <span class="d-none d-sm-inline-block mx-1">|</span>
                        <br class="d-sm-none">
                        Demo Application - Not for Legal Advice
                    </p>
                </div>
                <div class="col-12 col-sm-auto text-center">
                    <p class="mb-0 text-body-tertiary">
                        Developed by DEVAI
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- DropzoneJS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.js"></script>
    
    <!-- Disable DropzoneJS auto-discovery -->
    <script>
        Dropzone.autoDiscover = false;
    </script>
    
    <!-- Phoenix Config -->
    <script>
        // Phoenix configuration
        window.config = {
            config: {
                phoenixIsRTL: false,
                phoenixTheme: 'light'
            }
        };
    </script>

    <!-- Custom JavaScript -->
    <script>
        // Auto-dismiss alerts after 5 seconds targeting .alert-hide
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert-hide');
            alerts.forEach(alert => {
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });

        // Form validation helper
        function validateForm(formId) {
            const form = document.getElementById(formId);
            if (!form) return false;
            
            const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
            let isValid = true;
            
            inputs.forEach(input => {
                if (!input.value.trim()) {
                    input.classList.add('is-invalid');
                    isValid = false;
                } else {
                    input.classList.remove('is-invalid');
                    input.classList.add('is-valid');
                }
            });
            
            return isValid;
        }

        // File upload preview
        function previewFile(input, previewId) {
            const preview = document.getElementById(previewId);
            const file = input.files[0];
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    if (file.type.startsWith('image/')) {
                        preview.innerHTML = `<img src="${e.target.result}" class="img-fluid rounded" style="max-height: 200px;">`;
                    } else {
                        preview.innerHTML = `<div class="alert alert-subtle-info">
                            <i class="fas fa-file me-2"></i>
                            Selected: ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)
                        </div>`;
                    }
                };
                reader.readAsDataURL(file);
            } else {
                preview.innerHTML = '';
            }
        }

        // Loading spinner helper
        function showLoading(buttonId, text = 'Processing...') {
            const button = document.getElementById(buttonId);
            if (button) {
                button.disabled = true;
                button.innerHTML = `
                    <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                    ${text}
                `;
            }
        }

        function hideLoading(buttonId, originalText = 'Submit') {
            const button = document.getElementById(buttonId);
            if (button) {
                button.disabled = false;
                button.innerHTML = originalText;
            }
        }
    </script>

    <?php if (isset($additional_scripts)): ?>
        <?= $additional_scripts ?>
    <?php endif; ?>

</body>
</html>