<!doctype html>
<html lang="es">
<head>
    <title>Verifica tu correo - ERP Univalle</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/estiloUnivalle.css')}}">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<section class="vh-100 gradient-custom">
    <div class="container py-5 h-100">
        <div class="row justify-content-center align-items-center h-100">
            <div class="col-md-8 col-lg-6 col-xl-5">
                <div class="card bg-white text-dark" style="border-radius: 1rem;">
                    <div class="card-body p-md-5 text-center">
                        <h3 class="mb-4">Verifica tu correo electrónico</h3>
                        <p>Te hemos enviado un enlace de verificación a tu correo institucional.<br>
                        Por favor, revisa tu bandeja de entrada y haz clic en el enlace para activar tu cuenta.</p>
                        <p class="text-muted">¿No recibiste el correo?</p>
                        <form method="POST" action="{{ route('verification.send') }}">
                            @csrf
                            <button type="submit" class="btn btn-primary">Reenviar enlace de verificación</button>
                        </form>
                        @if (session('message'))
                            <script>
                                Swal.fire({
                                    icon: 'success',
                                    title: '¡Enlace reenviado!',
                                    text: '{{ session('message') }}',
                                    confirmButtonText: 'Aceptar'
                                });
                            </script>
                        @endif
                        <div class="alert alert-info mt-3">
                            <b>¿Problemas al verificar?</b><br>
                            Si al hacer clic en el enlace de verificación te pidió iniciar sesión, por favor vuelve a hacer clic en el enlace del correo después de iniciar sesión.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
</body>
</html>
