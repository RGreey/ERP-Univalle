<!doctype html>
<html lang="en">
<head>
    <title>ERP Univalle</title>
    
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous" />
    <link rel="stylesheet" href="{{ asset('assets/estiloUnivalle.css')}}">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</head>

<body>
<section class="vh-100 gradient-custom">
    <div class="container py-5 h-100">
        <div class="row justify-content-center align-items-center h-100">
            <div class="col-md-8 col-lg-6 col-xl-5">
                <div class="card bg-white text-white" style="border-radius: 1rem;">
                    <img src="{{ asset('imagenes/logou.png')}}" class="login-logo">
                    <div class="card-body p-md-5 text-center">
                        <div class="mb-md-5 pb-3">
                            <div class="mb-4">
                                <div class="alert alert-info" style="font-size:0.97rem;">
                                    <i class="fa-solid fa-info-circle me-1"></i>
                                    El rol que selecciones será revisado y aprobado por un administrador.<br>
                                    Si seleccionas "Profesor" o "Administrativo", tu acceso estará pendiente de validación. Por defecto, tu cuenta será registrada como <b>Estudiante</b> hasta su aprobación.
                                </div>
                            </div>
                            <form action="{{ route('register') }}" method="POST">
                                @csrf
                                <div class="form-outline form-white mb-4">
                                    <input type="text" name="name" class="form-control form-control-lg" placeholder="Nombre" autocomplete="name" value="{{ old('name') }}" />
                                </div>

                                <div class="form-outline form-white mb-4">
                                    <label for="rol" class="form-label text-dark">Solicitar rol</label>
                                    <select class="form-control form-control-lg" name="rol" id="rol">
                                        <option value="Estudiante">Estudiante</option>
                                        <option value="Profesor">Profesor</option>
                                        <option value="Administrativo">Administrativo</option>
                                    </select>
                                </div>
                                                                                                
                                <div class="form-outline form-white mb-4">
                                    <input type="email" name="email" class="form-control form-control-lg" placeholder="Correo" autocomplete="email" value="{{ old('email') }}" />
                                </div>

                                <div class="form-outline form-white mb-4">
                                    <input type="password" name="password" class="form-control form-control-lg" placeholder="Contraseña" value="{{ old('password') }}" />
                                </div>

                                <div class="form-outline form-white mb-4">
                                    <input type="password" name="password_confirmation" class="form-control form-control-lg" placeholder="Confirmar contraseña" value="{{ old('password_confirmation') }}" />
                                </div>

                                <button class="btn btn-danger btn-lg px-5" type="submit">Registrarse</button>
                            </form>
                        </div>

                        <div>
                            <p class="mb-0 redtext">¿Ya tienes una cuenta? <a href="{{ route('login') }}" class="fw-bold redtext">Acceder</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    // Mostrar la alerta de SweetAlert2 para errores de validación del formulario
    @if ($errors->any())
        let errorMessage = 'Se encontraron algunos errores al procesar el formulario:<br><ul>';

        @foreach ($errors->all() as $error)
            errorMessage += `<li>{{ $error }}</li>`;
        @endforeach

        errorMessage += '</ul>';

        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            html: errorMessage
        });
    @endif
</script>

</body>
</html>
