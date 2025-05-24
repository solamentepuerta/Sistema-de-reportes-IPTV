            <footer class="mt-5 pt-4 pb-3">
                <div class="text-center">
                    <p class="text-muted mb-0">Panel de Administración <?php echo SITE_NAME; ?> &copy; <?php echo date('Y'); ?></p>
                    <p class="mb-0 text-muted small mt-1">Desarrollado por <a href="https://profesordepapel.com/" target="_blank" class="text-decoration-none fw-bold">Profesor de Papel</a></p>
                </div>
            </footer>
        </div> <!-- Cierre del content-wrapper -->
    </div> <!-- Cierre del admin-wrapper -->
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Código para manejar el funcionamiento responsive del sidebar
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarWidth = 250;
            const sidebar = document.querySelector('.sidebar');
            const contentWrapper = document.querySelector('.content-wrapper');
            const sidebarToggle = document.getElementById('sidebarToggle');
            
            // Función para ajustar el diseño basado en el tamaño de la ventana
            function adjustLayout() {
                if (window.innerWidth < 992) {
                    sidebar.style.transform = 'translateX(-100%)';
                    contentWrapper.style.marginLeft = '0';
                    contentWrapper.style.width = '100%';
                } else {
                    sidebar.style.transform = 'translateX(0)';
                    contentWrapper.style.marginLeft = sidebarWidth + 'px';
                    contentWrapper.style.width = `calc(100% - ${sidebarWidth}px)`;
                }
            }
            
            // Manejador para el botón de alternar sidebar
            sidebarToggle.addEventListener('click', function() {
                if (sidebar.style.transform === 'translateX(0px)') {
                    sidebar.style.transform = 'translateX(-100%)';
                } else {
                    sidebar.style.transform = 'translateX(0)';
                }
            });
            
            // Ajustar diseño al cargar la página
            adjustLayout();
            
            // Ajustar diseño cuando cambia el tamaño de la ventana
            window.addEventListener('resize', adjustLayout);
        });
    </script>
</body>
</html> 