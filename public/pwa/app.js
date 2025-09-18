// Vue.js App para PWA de Novedades
const { createApp } = Vue;

createApp({
    data() {
        return {
            novedades: [],
            loading: false,
            selectedNovedad: null,
            selectedFiles: [],
            uploading: false,
            uploadProgress: 0,
            cameraModal: null,
            detallesModal: null,
            // Evidencias mantenimiento
            evidenciasModal: null,
            actividades: [],
            evForm: { sede: 'MI', mes: new Date().getMonth()+1, anio: new Date().getFullYear() },
            evYears: Array.from({length: 6}, (_,i)=> new Date().getFullYear()-2+i),
            evCurrent: { actividad_id: '', files: [] },
            evLotes: [],
            evSubmitting: false
        }
    },
    
    mounted() {
        console.log('🚀 Vue.js iniciado correctamente');
        this.loadNovedades();
        this.cameraModal = new bootstrap.Modal(document.getElementById('cameraModal'));
        this.evidenciasModal = new bootstrap.Modal(document.getElementById('evidenciasMantenimientoModal'));
        axios.defaults.headers.common['Accept'] = 'application/json';
    },
    
    methods: {
        // Ver detalles de novedad
        verDetallesNovedad(novedad) {
            console.log('🖱️ Click en novedad:', novedad.id);
            this.selectedNovedad = novedad;
            
            const modalElement = document.getElementById('detallesModal');
            console.log('🔍 Modal encontrado:', modalElement);
            
            if (modalElement) {
                this.detallesModal = new bootstrap.Modal(modalElement);
                this.detallesModal.show();
                console.log('✅ Modal abierto');
            } else {
                console.error('❌ Modal no encontrado');
            }
        },
        
        // Cerrar modal de detalles
        closeDetallesModal() {
            if (this.detallesModal) {
                this.detallesModal.hide();
            }
        },
        
        // Cargar novedades
        async loadNovedades() {
            try {
                this.loading = true;
                const response = await axios.get('/api/pwa/novedades');
                this.novedades = response.data;
                
            } catch (error) {
                console.error('Error cargando novedades:', error);
                this.showError('Error al cargar las novedades');
            } finally {
                this.loading = false;
            }
        },
        
        // Refrescar datos
        refreshData() {
            this.loadNovedades();
        },

        // Evidencias Mantenimiento (PWA)
        async openEvidenciasModal() {
            try {
                const { data } = await axios.get('/api/pwa/actividades-mantenimiento');
                this.actividades = data;
                this.evCurrent = { actividad_id: '', files: [] };
                this.evLotes = [];
                this.evidenciasModal.show();
            } catch (e) {
                this.showError('No se pudieron cargar las actividades');
            }
        },
        closeEvidenciasModal() {
            this.evidenciasModal.hide();
        },
        evHandleFiles(e) {
            const files = Array.from(e.target.files || []);
            this.evCurrent.files = [];
            files.forEach(file => {
                if (!file.type.startsWith('image/')) return;
                const reader = new FileReader();
                reader.onload = (ev) => {
                    this.evCurrent.files.push({ file, preview: ev.target.result, descripcion: '' });
                };
                reader.readAsDataURL(file);
            });
        },
        evAddActividadLote() {
            if (!this.evCurrent.actividad_id || this.evCurrent.files.length===0) return;
            this.evLotes.push({ actividad_id: this.evCurrent.actividad_id, files: this.evCurrent.files });
            this.evCurrent = { actividad_id: '', files: [] };
        },
        getActividadNombre(id) {
            const a = this.actividades.find(x=>x.id===id);
            return a ? a.actividad : id;
        },
        async evSubmit() {
            if (this.evLotes.length===0) return;
            this.evSubmitting = true;
            try {
                // Construir payload como array de fotos con actividad_id
                const formData = new FormData();
                formData.append('sede', this.evForm.sede);
                formData.append('mes', this.evForm.mes);
                formData.append('anio', this.evForm.anio);

                let idx = 0;
                this.evLotes.forEach(lote => {
                    lote.files.forEach(f => {
                        formData.append(`fotos[${idx}][actividad_id]`, lote.actividad_id);
                        formData.append(`fotos[${idx}][archivo]`, f.file);
                        if (f.descripcion) {
                            formData.append(`fotos[${idx}][descripcion]`, f.descripcion);
                        }
                        idx++;
                    });
                });
                await axios.post('/api/pwa/evidencias/guardar', formData, {
                    headers: { 'Content-Type': 'multipart/form-data' }
                });
                this.showSuccess('Evidencias registradas');
                this.closeEvidenciasModal();
            } catch (e) {
                this.showError('Error registrando evidencias');
            } finally {
                this.evSubmitting = false;
            }
        },
        
        // Seleccionar novedad para agregar evidencia
        selectNovedad(novedad) {
            this.selectedNovedad = novedad;
            this.selectedFiles = [];
            this.cameraModal.show();
        },
        
        // Manejar selección de archivos
        handleFileSelect(event) {
            const files = Array.from(event.target.files);
            this.selectedFiles = [];
            
            files.forEach(file => {
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        this.selectedFiles.push({
                            file: file,
                            preview: e.target.result,
                            descripcion: ''
                        });
                    };
                    reader.readAsDataURL(file);
                }
            });
        },
        
        // Subir evidencias
        async uploadEvidencias() {
            if (this.selectedFiles.length === 0) return;
            
            try {
                this.uploading = true;
                this.uploadProgress = 0;
                
                for (let i = 0; i < this.selectedFiles.length; i++) {
                    const fileData = this.selectedFiles[i];
                    const formData = new FormData();
                    
                    formData.append('archivo', fileData.file);
                    if (fileData.descripcion) {
                        formData.append('descripcion', fileData.descripcion);
                    }
                    
                    await axios.post(`/api/pwa/novedades/${this.selectedNovedad.id}/evidencia`, formData, {
                        headers: {
                            'Content-Type': 'multipart/form-data'
                        },
                        onUploadProgress: (progressEvent) => {
                            const progress = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                            this.uploadProgress = progress;
                        }
                    });
                    
                    // Actualizar progreso general
                    this.uploadProgress = ((i + 1) / this.selectedFiles.length) * 100;
                }
                
                this.showSuccess('Evidencias subidas correctamente');
                this.closeCameraModal();
                this.loadNovedades(); // Recargar lista
                
            } catch (error) {
                console.error('Error subiendo evidencias:', error);
                this.showError('Error al subir las evidencias');
            } finally {
                this.uploading = false;
                this.uploadProgress = 0;
            }
        },
        
        // Marcar como mantenimiento realizado
        async marcarMantenimientoRealizado(novedad) {
            try {
                const response = await axios.post(`/api/pwa/novedades/${novedad.id}/mantenimiento-realizado`);
                
                if (response.data.success) {
                    this.showSuccess('Estado cambiado a mantenimiento realizado');
                    this.loadNovedades(); // Recargar lista
                } else {
                    this.showError(response.data.message || 'Error al cambiar el estado');
                }
            } catch (error) {
                console.error('Error cambiando estado:', error);
                this.showError('Error al cambiar el estado');
            }
        },
        
        // Ver evidencia en tamaño completo
        verEvidencia(evidencia) {
            const url = '/storages/' + evidencia.archivo_url;
            window.open(url, '_blank');
        },
        
        // Obtener texto del estado
        getStatusText(estado) {
            const estados = {
                'pendiente': 'Pendiente',
                'mantenimiento realizado': 'Mantenimiento Realizado',
                'cerrada': 'Cerrada'
            };
            return estados[estado] || estado;
        },
        
        // Obtener clase del badge según estado
        getStatusBadgeClass(estado) {
            const clases = {
                'pendiente': 'badge-pendiente',
                'mantenimiento realizado': 'badge-mantenimiento-realizado',
                'cerrada': 'badge-cerrada'
            };
            return clases[estado] || 'badge-secondary';
        },
        
        // Formatear fecha
        formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('es-ES', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        },
        
        // Cerrar modal de cámara
        closeCameraModal() {
            this.cameraModal.hide();
            this.selectedFiles = [];
            this.selectedNovedad = null;
        },
        
        // Mostrar mensaje de éxito
        showSuccess(message) {
            const toast = new bootstrap.Toast(document.getElementById('successToast'));
            document.querySelector('#successToast .toast-body').textContent = message;
            toast.show();
        },
        
        // Mostrar mensaje de error
        showError(message) {
            const toast = document.getElementById('errorToast');
            if (toast) {
                const toastBody = toast.querySelector('.toast-body');
                if (toastBody) {
                    toastBody.textContent = message;
                }
                const bsToast = new bootstrap.Toast(toast);
                bsToast.show();
            }
        }
    }
}).mount('#app'); 