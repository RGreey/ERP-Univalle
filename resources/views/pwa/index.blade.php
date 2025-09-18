<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Novedades ERP Univalle</title>
    
    <!-- PWA Meta tags -->
    <meta name="theme-color" content="#0d6efd">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Novedades">
    
    <!-- Manifest -->
    <link rel="manifest" href="/pwa/manifest.json">
    
    <!-- Icons -->
    <link rel="icon" type="image/png" sizes="32x32" href="/pwa/icons/icon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/pwa/icons/icon-16x16.png">
    <link rel="apple-touch-icon" href="/pwa/icons/icon-152x152.png">
    
    <!-- Styles -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="/pwa/style.css" rel="stylesheet">
    
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    <div id="app">
        <!-- Loading -->
        <div v-if="loading" class="loading-overlay">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
        </div>
        
        <!-- Header -->
        <header class="bg-primary text-white p-3">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h5 mb-0">
                    <i class="fas fa-tools me-2"></i>
                    Novedades
                </h1>
                <button @click="refreshData" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
        </header>
        
        <!-- Main Content -->
        <main class="container-fluid p-3">
            <!-- Novedades List -->
            <div v-if="novedades.length > 0">
                <div class="row">
                    <div class="col-12">
                        <h6 class="text-muted mb-3">
                            <i class="fas fa-list me-2"></i>
                                                         Novedades Pendientes (@{{ novedades.length }})
                        </h6>
                    </div>
                </div>
                
                <div class="row">
                    <div v-for="novedad in novedades" :key="novedad.id" class="col-md-6 col-lg-4 mb-3">
                        <div class="card h-100" style="border: 2px solid #007bff;">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                                         <h6 class="card-title mb-0">@{{ novedad.titulo }}</h6>
                                     <span :class="getStatusBadgeClass(novedad.estado_novedad)" class="badge">
                                         @{{ getStatusText(novedad.estado_novedad) }}
                                     </span>
                                </div>
                                
                                                                 <p class="card-text small mb-3">@{{ novedad.descripcion }}</p>
                                
                                <!-- Botón para ver detalles -->
                                <div class="mb-3">
                                    <button @click="verDetallesNovedad(novedad)" 
                                            onclick="console.log('🔘 Click directo en botón')"
                                            class="btn btn-outline-primary btn-sm w-100"
                                            style="border: 2px solid red;">
                                        <i class="fas fa-eye me-1"></i>
                                                                                 Ver Detalles (ID: @{{ novedad.id }})
                                    </button>
                                </div>
                                
                                <!-- Indicador de evidencias -->
                                <div v-if="novedad.evidencias && novedad.evidencias.length > 0" class="mb-3">
                                    <small class="text-success">
                                        <i class="fas fa-images me-1"></i>
                                                                                 @{{ novedad.evidencias.length }} evidencia(s) subida(s)
                                    </small>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar me-1"></i>
                                                                                 @{{ formatDate(novedad.fecha_solicitud) }}
                                    </small>
                                    
                                    <div class="btn-group" role="group">
                                        <button @click="selectNovedad(novedad)" 
                                                class="btn btn-primary btn-sm">
                                            <i class="fas fa-camera me-1"></i>
                                            Agregar Evidencia
                                        </button>
                                        
                                        <!-- Botón para marcar como mantenimiento realizado -->
                                        <button v-if="novedad.evidencias && novedad.evidencias.length > 0 && novedad.estado_novedad === 'pendiente'"
                                                @click="marcarMantenimientoRealizado(novedad)"
                                                class="btn btn-success btn-sm ms-2">
                                            <i class="fas fa-check-circle me-1"></i>
                                            Mantenimiento Realizado
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Empty State -->
            <div v-else class="text-center py-5">
                <i class="fas fa-check-circle text-success fa-3x mb-3"></i>
                <h5 class="text-muted">No hay novedades pendientes</h5>
                <p class="text-muted small">Todas las novedades han sido atendidas</p>
            </div>
        </main>
        
        <!-- Camera Modal -->
        <div class="modal fade" id="cameraModal" tabindex="-1">
            <div class="modal-dialog modal-fullscreen-sm-down">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-camera me-2"></i>
                            Agregar Evidencia
                        </h5>
                        <button type="button" class="btn-close" @click="closeCameraModal"></button>
                    </div>
                    <div class="modal-body">
                        <div v-if="selectedNovedad" class="mb-3">
                                                         <h6>@{{ selectedNovedad.titulo }}</h6>
                             <p class="text-muted small">@{{ selectedNovedad.descripcion }}</p>
                        </div>
                        
                        <!-- Camera Input -->
                        <div class="mb-3">
                            <label class="form-label">Tomar foto o seleccionar de galería</label>
                            <input type="file" 
                                   @change="handleFileSelect" 
                                   accept="image/*" 
                                   capture="environment"
                                   class="form-control"
                                   multiple>
                        </div>
                        
                        <!-- Preview -->
                        <div v-if="selectedFiles.length > 0" class="mb-3">
                            <h6>Vista previa:</h6>
                            <div class="row">
                                <div v-for="(file, index) in selectedFiles" :key="index" class="col-6 mb-2">
                                    <img :src="file.preview" class="img-fluid rounded" style="max-height: 150px;">
                                    <div class="mt-2">
                                        <input v-model="file.descripcion" 
                                               type="text" 
                                               class="form-control form-control-sm"
                                               placeholder="Descripción (opcional)">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Upload Progress -->
                        <div v-if="uploading" class="mb-3">
                            <div class="progress">
                                <div class="progress-bar" 
                                     :style="{width: uploadProgress + '%'}"
                                     role="progressbar">
                                                                         @{{ uploadProgress }}%
                                </div>
                            </div>
                            <small class="text-muted">Subiendo evidencias...</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="closeCameraModal">
                            Cancelar
                        </button>
                        <button type="button" 
                                class="btn btn-primary" 
                                @click="uploadEvidencias"
                                :disabled="selectedFiles.length === 0 || uploading">
                            <i class="fas fa-upload me-1"></i>
                            Subir Evidencias
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Detalles Novedad Modal -->
        <div class="modal fade" id="detallesModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-info-circle me-2"></i>
                            Detalles de la Novedad
                        </h5>
                        <button type="button" class="btn-close" @click="closeDetallesModal"></button>
                    </div>
                    <div class="modal-body" v-if="selectedNovedad">
                        <!-- Información de la novedad -->
                        <div class="row mb-4">
                            <div class="col-md-8">
                                                                 <h5 class="text-white">@{{ selectedNovedad.titulo }}</h5>
                                 <p class="text-muted mb-2">@{{ selectedNovedad.descripcion }}</p>
                                <div class="row">
                                    <div class="col-6">
                                        <small class="text-muted">
                                            <i class="fas fa-map-marker-alt me-1"></i>
                                                                                         @{{ selectedNovedad.lugar ? selectedNovedad.lugar.nombreLugar : 'N/A' }}
                                        </small>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">
                                            <i class="fas fa-calendar me-1"></i>
                                                                                         @{{ formatDate(selectedNovedad.fecha_solicitud) }}
                                        </small>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <span :class="getStatusBadgeClass(selectedNovedad.estado_novedad)" class="badge">
                                                                                 @{{ getStatusText(selectedNovedad.estado_novedad) }}
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-4 text-end">
                                <button @click="selectNovedad(selectedNovedad)" 
                                        class="btn btn-primary btn-sm">
                                    <i class="fas fa-camera me-1"></i>
                                    Agregar Evidencia
                                </button>
                            </div>
                        </div>
                        
                        <!-- Evidencias -->
                        <div v-if="selectedNovedad.evidencias && selectedNovedad.evidencias.length > 0">
                            <h6 class="text-dark mb-3">
                                <i class="fas fa-images me-2"></i>
                                                                 Evidencias (@{{ selectedNovedad.evidencias.length }})
                            </h6>
                            <div class="row">
                                <div v-for="evidencia in selectedNovedad.evidencias" :key="evidencia.id" class="col-md-4 mb-3">
                                    <div class="card">
                                        <img :src="'/storages/' + evidencia.archivo_url" 
                                             class="card-img-top" 
                                             style="height: 200px; object-fit: cover;"
                                             @click="verEvidencia(evidencia)">
                                        <div class="card-body p-2">
                                                                                         <small class="text-muted">@{{ evidencia.descripcion || 'Sin descripción' }}</small>
                                             <br>
                                             <small class="text-muted">@{{ formatDate(evidencia.fecha_subida) }}</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Sin evidencias -->
                        <div v-else class="text-center py-4">
                            <i class="fas fa-images text-muted fa-3x mb-3"></i>
                            <h6 class="text-muted">No hay evidencias subidas</h6>
                            <p class="text-muted small">Agrega evidencias para marcar como mantenimiento realizado</p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="closeDetallesModal">
                            Cerrar
                        </button>
                        <button v-if="selectedNovedad && selectedNovedad.evidencias && selectedNovedad.evidencias.length > 0 && selectedNovedad.estado_novedad === 'pendiente'"
                                @click="marcarMantenimientoRealizado(selectedNovedad)"
                                class="btn btn-success">
                            <i class="fas fa-check-circle me-1"></i>
                            Marcar como Mantenimiento Realizado
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Success Toast -->
        <div class="toast-container position-fixed bottom-0 end-0 p-3">
            <div id="successToast" class="toast" role="alert">
                <div class="toast-header bg-success text-white">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong class="me-auto">Éxito</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body">
                    Evidencias subidas correctamente
                </div>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/vue/3.3.4/vue.global.min.js"></script>
    <script>
        // Fallback para Vue si CDN falla
        if (typeof Vue === 'undefined') {
            document.write('<script src="https://unpkg.com/vue@3/dist/vue.global.js"><\/script>');
        }
    </script>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/axios/1.4.0/axios.min.js"></script>
    <script>
        // Fallback para Axios si CDN falla
        if (typeof axios === 'undefined') {
            document.write('<script src="https://unpkg.com/axios/dist/axios.min.js"><\/script>');
        }
    </script>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // Fallback para Bootstrap si CDN falla
        if (typeof bootstrap === 'undefined') {
            document.write('<script src="https://unpkg.com/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"><\/script>');
        }
    </script>
    
    <script src="/pwa/app.js"></script>
    
    <!-- Register Service Worker -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/pwa/sw.js')
                    .then((registration) => {
                        console.log('SW registrado:', registration);
                    })
                    .catch((error) => {
                        console.log('SW error:', error);
                    });
            });
        }
    </script>
</body>
</html> 