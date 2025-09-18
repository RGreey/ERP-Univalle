<!doctype html>
<html lang="en">
<head>
    <title>Restablecer contraseña</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <link rel="stylesheet" href="{{ asset('assets/estiloUnivalle.css')}}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
<section class="vh-100 gradient-custom">
    <div class="container py-5 h-100">
        <div class="row justify-content-center align-items-center h-100">
            <div class="col-12 col-md-8 col-lg-6 col-xl-5">
                <div class="card bg-white text-white" style="border-radius: 1rem;">
                    <div class="card-body p-5 text-center">
                        <h3 class="mb-5 text-dark">Restablecer contraseña</h3>
                        <div class="card-body p-4 text-center">
                            <form method="POST" action="{{ route('password.update') }}">
                                @csrf

                                <input type="hidden" name="token" value="{{ $token }}">

                                <div class="mb-3">                    
                                    @if(session('status'))
                                        <div class="alert alert-success">
                                            {{ session('status') }}
                                        </div>
                                    @endif
                                    <input id="email" type="email" class="form-control form-control-lg @error('email') is-invalid @enderror" name="email" value="{{ $email ?? old('email') }}" required autocomplete="email" autofocus placeholder="Correo">
                                    @error('email')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <input id="password" type="password" class="form-control form-control-lg @error('password') is-invalid @enderror" name="password" required autocomplete="new-password" placeholder="Nueva Contraseña">
                                    @error('password')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <input id="password-confirm" type="password" class="form-control form-control-lg" name="password_confirmation" required autocomplete="new-password" placeholder="Confirmar contraseña">
                                </div>

                                <button type="submit" class="btn btn-danger btn-lg px-5">{{ __('Reset Password') }}</button>
                            </form>
                        </div>
                    </div>    
                </div>
            </div>
        </div>
    </div>
</section>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js" integrity="sha384-BBtl+eGJRgqQAUMxJ7pMwbEyER4l1g+O15P+16Ep7Q9Q+zqX6gSbd85u4mG4QzX+" crossorigin="anonymous"></script>
<script>
    // Función para mostrar la alerta de éxito
    function mostrarAlerta() {
        Swal.fire({
            icon: 'success',
            title: '¡Contraseña restablecida!',
            text: '{{ session('status') }}'
        }).then((result) => {
            // Redirigir después de hacer clic en el botón de confirmación
            if (result.isConfirmed) {
                window.location.href = "{{ route('login') }}";
            }
        });
    }

    // Mostrar la alerta de éxito si es necesario
    window.onload = function() {
        @if(session('status'))
            mostrarAlerta();
        @endif

        // Mostrar la alerta de errores de validación si es necesario
        @if ($errors->any())
            let errorMessage = 'Se encontraron algunos errores:<br><ul>';

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
    };
</script>

</body>
</html>
