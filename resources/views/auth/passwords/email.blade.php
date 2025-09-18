<!doctype html>
<html lang="en">
<head>
    <title>Restablecer contraseña</title>

    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous" />
    <link rel="stylesheet" href="{{ asset('assets/estiloUnivalle.css')}}">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
<section class="vh-100 gradient-custom">
    <div class="container py-5 h-100">
        <div class="row d-flex justify-content-center align-items-center h-100">
            <div class="col-12 col-md-8 col-lg-6 col-xl-5">
                <div class="card bg-white text-white" style="border-radius: 1rem;">
                    <div class="card-body p-5 text-center">
                        <h3 class="mb-5 text-dark">Restablecer contraseña</h3>
                        <p class="mt-3 text-dark">
                            Si su información se encuentra en el ERP, le enviaremos un  correo electrónico con las instrucciones para poder acceder de nuevo.
                        </p>
                        <form id="resetPasswordForm" method="POST" action="{{ route('password.email') }}">
                            @csrf

                            <div class="form-outline form-white mb-4">
                                <input type="email" name="email" id="email" class="form-control form-control-lg" placeholder="Correo electrónico" required />
                            </div>

                            <button type="submit" class="btn btn-danger btn-lg px-5">Enviar</button>
                        </form>
                        <button class="btn mt-3 redtext fw-bold" onclick="window.location.href='{{ route('login') }}'" >Volver al login</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js" integrity="sha384-BBtl+eGJRgqQAUMxJ7pMwbEyER4l1g+O15P+16Ep7Q9Q+zqX6gSbd85u4mG4QzX+" crossorigin="anonymous"></script>
<script>
    document.getElementById('resetPasswordForm').addEventListener('submit', function(event) {
        var email = document.getElementById('email').value;
        if (!email) {
            event.preventDefault();
            alert('Por favor, ingrese su correo electrónico.');
        }
    });

    // Mostrar la alerta de éxito si es necesario
    window.onload = function() {
        @if(session('status'))
            Swal.fire({
                icon: 'success',
                title: '¡Correo enviado!',
                text: '{{ session('status') }}'
            });
        @endif
    };
</script>
</body>
</html>
