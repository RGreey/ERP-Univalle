<div class="modal fade" id="crearNovedadModal" tabindex="-1" aria-labelledby="crearNovedadModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="{{ route('novedades.store') }}" method="POST">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title" id="crearNovedadModalLabel">Nueva Novedad</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          @if($errors->any())
            <div class="alert alert-danger">
              <ul class="mb-0">
                @foreach($errors->all() as $error)
                  <li>{{ $error }}</li>
                @endforeach
              </ul>
            </div>
          @endif
          <div class="mb-3">
            <label for="titulo" class="form-label">Título</label>
            <input type="text" class="form-control" id="titulo" name="titulo" required value="{{ old('titulo') }}">
          </div>
          <div class="mb-3">
            <label for="descripcion" class="form-label">Descripción</label>
            <textarea class="form-control" id="descripcion" name="descripcion" rows="3" required>{{ old('descripcion') }}</textarea>
          </div>
          <div class="mb-3">
            <label for="tipo" class="form-label">Tipo</label>
            <input type="text" class="form-control" id="tipo" name="tipo" required value="{{ old('tipo') }}" placeholder="Ej: Electricidad, Plomería, etc.">
          </div>
          <div class="mb-3">
            <label for="lugar_id" class="form-label">Lugar</label>
            <select class="form-select text-dark bg-white" id="lugar_id" name="lugar_id" required style="color:#222;">
              <option value="">Seleccione una sede</option>
              @foreach(App\Models\Lugar::all() as $lugar)
                <option value="{{ $lugar->id }}" {{ old('lugar_id') == $lugar->id ? 'selected' : '' }}>{{ $lugar->nombreLugar }}</option>
              @endforeach
            </select>
          </div>
          <div class="mb-3">
            <label for="ubicacion_detallada" class="form-label">Ubicación detallada</label>
            <input type="text" class="form-control" id="ubicacion_detallada" name="ubicacion_detallada" required value="{{ old('ubicacion_detallada') }}" placeholder="Ej: Corredor segundo piso, cerca al aula 201">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Crear novedad</button>
        </div>
      </form>
    </div>
  </div>
</div> 