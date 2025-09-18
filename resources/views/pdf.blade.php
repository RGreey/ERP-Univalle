<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generar PDF desde HTML</title>
    <!-- Incluir jsPDF desde CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.4.0/jspdf.umd.min.js"></script>
</head>
<body>
    <?php include '../resources/views/plantillapdf.blade.php'; ?>
    
    <!-- Script para generar el PDF -->
    <script>
        // Función para generar el PDF
        function generarPDF() {
            // Crear una instancia de jsPDF
            var doc = new jsPDF();
            
            // Obtener el contenido de la plantilla HTML
            var contenido = document.documentElement.innerHTML;
            
            // Eliminar la primera línea (DOCTYPE) para evitar problemas al cargar HTML en jsPDF
            contenido = contenido.substring(contenido.indexOf('<html'));
            
            // Agregar el contenido al PDF
            doc.html(contenido, {
                callback: function(doc) {
                    // Guardar el PDF
                    doc.save('evento.pdf');
                }
            });
        }
        
        // Llamar a la función para generar el PDF cuando la página se cargue
        window.onload = function() {
            generarPDF();
        };
    </script>
</body>
</html>
