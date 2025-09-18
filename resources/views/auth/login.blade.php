<!doctype html>
<html lang="en">
<head>
    <title>ERP Univalle</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous" />
    <link rel="stylesheet" href="{{ asset('assets/estiloUnivalle.css')}}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<section class="vh-100 gradient-custom">
    <div class="container py-5 h-100">
        <div class="row justify-content-center align-items-center h-100">
            <div class="col-sm-10 col-md-8 col-lg-6 col-xl-4">
                <div class="card bg-white text-white" style="border-radius: 1rem;">
                    <img src="{{ asset('imagenes/logou.png')}}" class="login-logo">
                    <div class="card-body p-4 text-center">
                        <div class="mb-md-4 mt-md-3 pb-4">
                            <form action="{{route('login')}}" method="POST">
                                @csrf
                                <div class="form-outline form-white mb-3">
                                    <input type="email" name="email" class="form-control form-control-lg" placeholder="Correo" autocomplete="email"/>
                                </div>
                                <div class="form-outline form-white mb-3">
                                    <div class="input-group">
                                        <input type="password" name="password" id="password" class="form-control form-control-lg @error('password') is-invalid @enderror" placeholder="Contraseña"/>
                                        <div>
                                            <button class="btn btn-danger" type="button" id="togglePassword">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <button class="btn btn-danger btn-lg px-5" type="submit">Entrar</button>
                            </form>
                        </div>
                        <div>
                            <p class="mb-4 redtext">¿Olvidó su contraseña? <a href="{{ route('password.request') }}" class="fw-bold redtext">Recuperar</a></p>
                            <p class="mb-0 redtext">¿No tienes una cuenta? <a href="{{route('register')}}" class="fw-bold redtext">Registro</a></p>
                        </div>
                    </div> 
                </div>
            </div>
        </div>
    </div>
</section>
<script>
    const togglePassword = document.querySelector('#togglePassword');
    const password = document.querySelector('#password');
    const eyeIcon = togglePassword.querySelector('i');

    togglePassword.addEventListener('click', function (e) {
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);
        eyeIcon.classList.toggle('bi-eye');
        eyeIcon.classList.toggle('bi-eye-slash');
    });

    // Mostrar la alerta de registro exitoso si es necesario
    window.onload = function() {
        @if(session('registroExitoso'))
            Swal.fire({
                icon: 'success',
                title: 'Registro exitoso',
                text: '¡Tu cuenta ha sido registrada correctamente!'
            });
        @endif

        // Mostrar la alerta de restablecimiento de contraseña exitoso si es necesario
        @if(session('status'))
            Swal.fire({
                icon: 'success',
                title: 'Contraseña restablecida exitosamente',
                text: '{{ session('status') }}'
            });
        @endif
    };
</script>
<script>
    // Mostrar la alerta de SweetAlert2 para errores de validación del formulario
    @if ($errors->any())
        let errorMessage = 'Se encontraron algunos errores al iniciar sesión:<br><ul>';

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
